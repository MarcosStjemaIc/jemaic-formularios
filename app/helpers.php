<?php
declare(strict_types=1);

// ---- Salida segura ---------------------------------------------------------
function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    static $bp = null;
    if ($bp !== null) return $bp;
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return $bp = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function abs_url(string $path = ''): string
{
    $base = rtrim((string) cfg('base_url', ''), '/');
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
    }
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string) filemtime($file), -6) : '1';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $path, int $code = 302): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)), true, $code);
    exit;
}

// ---- Sesión, flash, CSRF ---------------------------------------------------
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('jema_forms');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function flash(?string $msg = null, string $type = 'ok')
{
    start_session();
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void
{
    start_session();
    $sent = (string) ($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $sent)) {
        http_response_code(419);
        exit(render_error('Tu sesión venció. Volvé atrás, recargá la página y probá de nuevo.'));
    }
}

// ---- Petición --------------------------------------------------------------
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function ip_hash(): string
{
    return hash('sha256', client_ip() . '|' . (string) cfg('setup_key', 'x'));
}

// ---- Textos ----------------------------------------------------------------
function slugify(string $s, string $sep = '-'): string
{
    $s = mb_strtolower(trim($s));
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c']);
    $s = preg_replace('/[^a-z0-9]+/', $sep, $s) ?? '';
    return trim($s, $sep);
}

function new_ref(): string
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    do {
        $r = '';
        for ($i = 0; $i < 6; $i++) $r .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        $ref = 'JM-' . $r;
    } while (q_val('SELECT 1 FROM submissions WHERE ref = ?', [$ref]));
    return $ref;
}

function fmt_date(?string $ymd): string
{
    if (!$ymd || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m)) return (string) $ymd;
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return (int) $m[3] . ' de ' . $meses[(int) $m[2] - 1] . ' de ' . $m[1];
}

function fmt_datetime(?string $dt): string
{
    if (!$dt) return '';
    $t = strtotime($dt);
    return $t ? date('d/m/Y H:i', $t) : $dt;
}

function fmt_bytes(int $n): string
{
    if ($n < 1024) return $n . ' B';
    if ($n < 1048576) return round($n / 1024) . ' KB';
    return round($n / 1048576, 1) . ' MB';
}

/** Convierte una respuesta (string|array) a texto plano para listados y exportes. */
function answer_text($value, string $type = 'text'): string
{
    if (is_array($value)) {
        $value = array_map(fn($v) => is_array($v) ? implode(' · ', array_map('strval', $v)) : (string) $v, $value);
        return implode(in_array($type, ['repeater', 'captions'], true) ? "\n" : ' | ', array_filter($value, fn($v) => $v !== ''));
    }
    $v = (string) $value;
    if ($type === 'date') return fmt_date($v);
    if ($type === 'datetime' && preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}:\d{2})$/', $v, $m)) return $m[3] . '/' . $m[2] . '/' . $m[1] . ' · ' . $m[4] . ' hs';
    return $v;
}

// ---- Vistas ----------------------------------------------------------------
function render(string $view, array $vars = [], string $layout = 'public'): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_DIR . '/views/' . $view . '.php';
    $content = ob_get_clean();
    if ($layout === '') return $content;
    ob_start();
    require APP_DIR . '/views/' . $layout . '/layout.php';
    return (string) ob_get_clean();
}

function render_error(string $msg, string $title = 'Ups'): string
{
    return render('error', ['title' => $title, 'msg' => $msg], 'public');
}

function json_out($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Iconos SVG (trazo simple, heredan color) ---------------------------------
function icon(string $name, int $size = 28): string
{
    $paths = [
        'camera'  => '<path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13" r="3.5"/>',
        'heart'   => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
        'film'    => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M7 5v14M17 5v14M3 9.5h4M3 14.5h4M17 9.5h4M17 14.5h4"/>',
        'card'    => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="M19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7.7-2Z"/>',
        'calendar'=> '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'check'   => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'upload'  => '<path d="M12 16V4M7 9l5-5 5 5M4 20h16"/>',
        'file'    => '<path d="M6 3h8l4 4v14H6V3Z"/><path d="M14 3v4h4"/>',
        'whatsapp'=> '<path d="M4 20l1.3-4.2A8 8 0 1 1 8.4 19L4 20Z"/><path d="M9 9.5c.3 2 2 3.8 4.5 4.5l1.2-1.2-1.6-.9-.7.6c-.9-.4-1.6-1.100-2-2l.6-.7-.9-1.600L9 9.500Z"/>',
    ];
    $p = $paths[$name] ?? $paths['card'];
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

const FORM_ICONS = ['camera' => 'Cámara', 'heart' => 'Corazón', 'film' => 'Video', 'card' => 'Tarjeta', 'sparkle' => 'Destello', 'calendar' => 'Calendario'];
