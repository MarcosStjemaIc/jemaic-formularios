<?php
declare(strict_types=1);

/** Envía un email. Devuelve true si el servidor lo aceptó. Nunca lanza excepciones. */
function send_mail(string $to, string $subject, string $html, string $text = '', ?string $replyTo = null, array $attachments = []): bool
{
    try {
        $from = (string) cfg('mail.from_email', '');
        $fromName = (string) cfg('mail.from_name', 'Formularios');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || $from === '') return false;

        $boundary = 'b_' . bin2hex(random_bytes(8));
        $text = $text !== '' ? $text : trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</tr>|</h\d>#i', "\n", $html) ?? $html)));
        $enc = fn(string $s) => '=?UTF-8?B?' . base64_encode($s) . '?=';

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $enc($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $enc($subject),
            'MIME-Version: 1.0',
            'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . (parse_url(abs_url(), PHP_URL_HOST) ?: 'localhost') . '>',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        $mixed = $attachments ? 'm_' . bin2hex(random_bytes(8)) : '';
        if ($mixed) $headers[count($headers) - 1] = 'Content-Type: multipart/mixed; boundary="' . $mixed . '"';
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $headers[] = 'Reply-To: <' . $replyTo . '>';

        $body = '--' . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($text))
              . '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($html))
              . '--' . $boundary . "--\r\n";
        if ($mixed) {
            $alt = $body;
            $body = '--' . $mixed . "\r\nContent-Type: multipart/alternative; boundary=\"" . $boundary . "\"\r\n\r\n" . $alt;
            foreach ($attachments as $att) {
                $fname = str_replace(['"', "\r", "\n"], '', (string) $att['name']);
                $body .= '--' . $mixed . "\r\nContent-Type: " . ($att['mime'] ?? 'application/octet-stream') . '; name="' . $enc($fname) . "\"\r\n"
                    . "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"" . $enc($fname) . "\"\r\n\r\n"
                    . chunk_split(base64_encode((string) $att['data']));
            }
            $body .= '--' . $mixed . "--\r\n";
        }

        if (cfg('mail.driver', 'smtp') === 'mail') {
            $h = implode("\r\n", array_filter($headers, fn($x) => !str_starts_with($x, 'To:') && !str_starts_with($x, 'Subject:')));
            return @mail($to, $enc($subject), $body, $h, '-f' . $from);
        }
        return smtp_deliver($to, $from, implode("\r\n", $headers) . "\r\n\r\n" . $body);
    } catch (Throwable $e) {
        app_log('MAIL ERROR: ' . $e->getMessage());
        return false;
    }
}

function smtp_deliver(string $to, string $from, string $message): bool
{
    $c = (array) cfg('mail.smtp', []);
    $host = (string) ($c['host'] ?? '');
    $port = (int) ($c['port'] ?? 465);
    $secure = (string) ($c['secure'] ?? 'ssl');
    if ($host === '') { app_log('SMTP: falta host'); return false; }

    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => !cfg('debug'), 'verify_peer_name' => !cfg('debug')]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { app_log("SMTP conexión falló: $errstr ($errno)"); return false; }
    stream_set_timeout($fp, 12);

    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $out;
    };
    $cmd = function (string $line, array $ok) use ($fp, $read): bool {
        fwrite($fp, $line . "\r\n");
        $r = $read();
        $good = in_array((int) substr($r, 0, 3), $ok, true);
        if (!$good) app_log('SMTP respuesta inesperada a "' . (str_starts_with($line, 'AUTH') || strlen($line) > 60 ? substr($line, 0, 10) . '…' : $line) . '": ' . trim($r));
        return $good;
    };

    $greet = $read();
    if ((int) substr($greet, 0, 3) !== 220) { app_log('SMTP saludo: ' . trim($greet)); fclose($fp); return false; }
    $ehlo = 'EHLO ' . (parse_url(abs_url(), PHP_URL_HOST) ?: 'localhost');
    if (!$cmd($ehlo, [250])) { fclose($fp); return false; }

    if ($secure === 'tls') {
        if (!$cmd('STARTTLS', [220]) || !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($fp); return false; }
        if (!$cmd($ehlo, [250])) { fclose($fp); return false; }
    }
    if (!empty($c['user'])) {
        if (!$cmd('AUTH LOGIN', [334]) || !$cmd(base64_encode((string) $c['user']), [334]) || !$cmd(base64_encode((string) ($c['pass'] ?? '')), [235])) {
            fclose($fp); return false;
        }
    }
    $ok = $cmd('MAIL FROM:<' . $from . '>', [250]) && $cmd('RCPT TO:<' . $to . '>', [250, 251]) && $cmd('DATA', [354]);
    if ($ok) {
        $data = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $message));
        $data = str_replace("\n", "\r\n", (string) $data);
        fwrite($fp, $data . "\r\n.\r\n");
        $r = $read();
        $ok = (int) substr($r, 0, 3) === 250;
        if (!$ok) app_log('SMTP DATA rechazado: ' . trim($r));
    }
    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $ok;
}

// ---- Emails de un envío --------------------------------------------------------
function answers_table_html(array $answers, array $files = []): string
{
    $rows = '';
    $lastStep = null;
    foreach ($answers as $a) {
        $val = answer_text($a['value'], $a['type']);
        if ($val === '') continue;
        if ($a['step'] !== $lastStep) {
            $rows .= '<tr><td colspan="2" style="padding:18px 0 6px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#9a513a;font-weight:700">' . h($a['step']) . '</td></tr>';
            $lastStep = $a['step'];
        }
        $rows .= '<tr><td style="padding:6px 14px 6px 0;vertical-align:top;color:#68695e;width:38%;font-size:14px">' . h($a['label']) . '</td>'
              . '<td style="padding:6px 0;vertical-align:top;font-size:14px;color:#282b23">' . nl2br(h($val)) . '</td></tr>';
    }
    if ($files) {
        $n = array_sum(array_map('count', $files));
        $rows .= '<tr><td style="padding:10px 14px 6px 0;color:#68695e;font-size:14px">Archivos adjuntos</td><td style="padding:10px 0 6px;font-size:14px">' . $n . ' (se ven en el panel)</td></tr>';
    }
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">' . $rows . '</table>';
}

/** Versión en texto plano del resumen (para clientes de correo sin HTML). */
function answers_text(array $answers, array $files = []): string
{
    $out = '';
    $last = null;
    foreach ($answers as $a) {
        $val = answer_text($a['value'], $a['type']);
        if ($val === '') continue;
        if ($a['step'] !== $last) { $out .= "\n" . mb_strtoupper($a['step']) . "\n"; $last = $a['step']; }
        $out .= $a['label'] . ': ' . str_replace("\n", "\n    ", $val) . "\n";
    }
    if ($files) $out .= "\nArchivos adjuntos: " . array_sum(array_map('count', $files)) . " (se ven en el panel)\n";
    return trim($out);
}

function email_shell(string $title, string $inner): string
{
    return '<!doctype html><html><body style="margin:0;background:#f8f5ee;font-family:Arial,Helvetica,sans-serif">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:28px 14px">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border:1px solid #deded2;border-radius:14px">'
        . '<tr><td style="padding:26px 30px 8px"><div style="font-size:13px;letter-spacing:.14em;text-transform:uppercase;color:#68695e">' . h(cfg('app_name', 'Jema')) . '</div>'
        . '<h1 style="margin:8px 0 0;font-size:22px;color:#282b23;font-weight:600">' . h($title) . '</h1></td></tr>'
        . '<tr><td style="padding:10px 30px 30px">' . $inner . '</td></tr></table></td></tr></table></body></html>';
}

/** Manda el aviso al equipo y la confirmación al cliente. Se llama después de responder al navegador. */
function send_submission_emails(array $formRow, array $def, array $sub): void
{
    $answers = $sub['answers'];
    $byKey = [];
    foreach ($answers as $a) $byKey[$a['k']] = $a['value'];

    $who = '';
    foreach ($def['list_fields'] as $k) { if (!empty($byKey[$k]) && !is_array($byKey[$k])) { $who = (string) $byKey[$k]; break; } }
    $panel = abs_url('admin/responses/' . $sub['id']);

    // Aviso interno
    $to = array_values(array_unique(array_merge((array) cfg('mail.notify', []), $def['notify'])));
    $clientEmail = $def['client_email_field'] ? (string) ($byKey[$def['client_email_field']] ?? '') : '';
    $subject = 'Nuevo formulario: ' . $def['title'] . ($who !== '' ? ' — ' . $who : '');
    $inner = '<p style="font-size:14px;color:#282b23;margin:0 0 6px">Código <strong>' . h($sub['ref']) . '</strong> · ' . h(date('d/m/Y H:i')) . '</p>'
        . '<p style="margin:0 0 6px"><a href="' . h($panel) . '" style="display:inline-block;background:#282b23;color:#fff;text-decoration:none;padding:10px 18px;border-radius:999px;font-size:14px">Abrir en el panel</a></p>'
        . answers_table_html($answers, $sub['files']);
    // Adjuntos para el equipo: los archivos (si no pesan demasiado) y, si corresponde, el JSON para Qué Planazo.
    $atts = [];
    $total = 0; $skipped = 0;
    foreach ($sub['files'] as $list) {
        foreach ($list as $fl) {
            $full = STORAGE_DIR . '/uploads/' . $fl['path'];
            $size = is_file($full) ? (int) filesize($full) : 0;
            if (!$size || $total + $size > 18 * 1048576) { $skipped++; continue; }
            $total += $size;
            $atts[] = ['name' => $fl['name'], 'mime' => $fl['mime'] ?? 'application/octet-stream', 'data' => (string) file_get_contents($full)];
        }
    }
    if (($def['export'] ?? '') === 'queplanazo') {
        $atts[] = ['name' => 'invitacion-' . $sub['ref'] . '.json', 'mime' => 'application/json',
            'data' => json_encode(planazo_export($answers, $sub['files'], $sub['ref']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }
    if ($skipped) $inner .= '<p style="font-size:13px;color:#b44112;margin:14px 0 0">' . $skipped . ' archivo(s) no se adjuntaron porque el email quedaría muy pesado. Están en el panel.</p>';
    foreach ($to as $addr) send_mail($addr, $subject, email_shell($subject, $inner), 'Código ' . $sub['ref'] . ' · ' . date('d/m/Y H:i') . "\nAbrir en el panel: " . $panel . "\n\n" . answers_text($answers, $sub['files']), $clientEmail ?: null, $atts);

    // Confirmación al cliente
    if ($clientEmail !== '' && filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        $inner = '<p style="font-size:15px;line-height:1.55;color:#282b23;margin:0 0 14px">' . nl2br(h($def['thanks_text'])) . '</p>'
            . '<p style="font-size:13px;color:#68695e;margin:0 0 4px">Tu código de seguimiento: <strong style="color:#282b23">' . h($sub['ref']) . '</strong></p>'
            . '<p style="font-size:13px;color:#68695e;margin:0 0 10px">Esto es lo que nos enviaste:</p>'
            . answers_table_html($answers, $sub['files']);
        send_mail($clientEmail, $def['thanks_title'], email_shell($def['thanks_title'], $inner),
            $def['thanks_text'] . "\n\nTu código de seguimiento: " . $sub['ref'] . "\n\nEsto es lo que nos enviaste:\n\n" . answers_text($answers, $sub['files']));
    }
}
