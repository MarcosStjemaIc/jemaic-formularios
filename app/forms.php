<?php
declare(strict_types=1);

const FIELD_TYPES = [
    'text' => 'Texto corto', 'textarea' => 'Texto largo', 'email' => 'Email', 'tel' => 'Teléfono',
    'url' => 'Enlace', 'date' => 'Fecha', 'time' => 'Hora', 'number' => 'Número',
    'select' => 'Lista desplegable', 'radio' => 'Una opción', 'checkbox' => 'Varias opciones',
    'consent' => 'Casilla de aceptación', 'file' => 'Archivos', 'colors' => 'Colores (hasta 3)',
    'palette' => 'Paletas sugeridas', 'info' => 'Texto informativo',
    'switch' => 'Interruptor de sección', 'datetime' => 'Fecha y hora', 'repeater' => 'Lista de filas',
];
const REPEATER_SUBTYPES = ['text', 'textarea', 'time', 'select'];
const CHOICE_TYPES = ['select', 'radio', 'checkbox', 'palette'];

// ---- Acceso a formularios ------------------------------------------------------
function form_row_by_slug(string $slug): ?array
{
    return q_one('SELECT * FROM forms WHERE slug = ?', [$slug]);
}

function form_row(int $id): ?array
{
    return q_one('SELECT * FROM forms WHERE id = ?', [$id]);
}

function form_def(array $row): array
{
    $def = json_decode((string) $row['definition'], true);
    return normalize_definition(is_array($def) ? $def : []);
}

/** Deja la definición completa y coherente (nombres únicos, opciones normalizadas, etc.). */
function normalize_definition(array $d): array
{
    $d += [
        'title' => 'Formulario sin título', 'subtitle' => '', 'intro' => '', 'icon' => 'card',
        'brand' => 'jema', 'accent' => '', 'submit_label' => 'Enviar formulario',
        'thanks_title' => '¡Gracias! Recibimos tu formulario', 'thanks_text' => 'En breve nos ponemos en contacto para confirmar los detalles.',
        'notify' => [], 'client_email_field' => '', 'list_fields' => [], 'steps' => [],
    ];
    $d['notify'] = array_values(array_filter(array_map('trim', is_array($d['notify']) ? $d['notify'] : preg_split('/[\s,;]+/', (string) $d['notify'])), fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    $d['list_fields'] = array_values(array_filter((array) $d['list_fields']));
    $used = [];
    $steps = [];
    foreach ((array) $d['steps'] as $si => $s) {
        if (!is_array($s)) continue;
        $s += ['id' => 's' . ($si + 1), 'title' => 'Paso ' . ($si + 1), 'description' => '', 'fields' => []];
        $fields = [];
        foreach ((array) $s['fields'] as $f) {
            if (!is_array($f)) continue;
            $f += ['type' => 'text', 'label' => '', 'required' => false, 'help' => '', 'placeholder' => ''];
            if (!isset(FIELD_TYPES[$f['type']])) $f['type'] = 'text';
            $name = slugify((string) ($f['name'] ?? ''), '_');
            if ($name === '') $name = slugify((string) $f['label'], '_') ?: $f['type'];
            $name = mb_substr($name, 0, 40);
            $base = $name; $n = 2;
            while (isset($used[$name])) $name = $base . '_' . $n++;
            $used[$name] = true;
            $f['name'] = $name;
            $f['required'] = !empty($f['required']);
            if (in_array($f['type'], CHOICE_TYPES, true)) {
                $f['options'] = normalize_options($f['options'] ?? []);
                $f['other'] = !empty($f['other']);
            }
            if (!empty($f['show_if']) && (!is_array($f['show_if']) || empty($f['show_if']['field']))) unset($f['show_if']);
            foreach (['max', 'min', 'max_count', 'max_files', 'max_mb', 'min_rows', 'max_rows', 'need'] as $k) if (isset($f[$k])) $f[$k] = max(0, (int) $f[$k]);
            if ($f['type'] === 'repeater') {
                $subs = [];
                foreach ((array) ($f['fields'] ?? []) as $sf) {
                    if (!is_array($sf) || empty($sf['name'])) continue;
                    $sf += ['type' => 'text', 'label' => '', 'required' => false, 'placeholder' => ''];
                    if (!in_array($sf['type'], REPEATER_SUBTYPES, true)) $sf['type'] = 'text';
                    $sf['name'] = slugify((string) $sf['name'], '_');
                    $sf['required'] = !empty($sf['required']);
                    if (isset($sf['max'])) $sf['max'] = max(0, (int) $sf['max']);
                    if ($sf['type'] === 'select') $sf['options'] = normalize_options($sf['options'] ?? []);
                    $subs[] = $sf;
                }
                $f['fields'] = $subs;
                $f['min_rows'] = $f['min_rows'] ?? 0;
                $f['max_rows'] = max(1, $f['max_rows'] ?? 10);
            }
            $fields[] = $f;
        }
        $s['fields'] = $fields;
        if (!empty($s['show_if']) && (!is_array($s['show_if']) || empty($s['show_if']['field']))) unset($s['show_if']);
        $steps[] = $s;
    }
    $d['steps'] = $steps;
    return $d;
}

function normalize_options($opts): array
{
    if (is_string($opts)) {
        // Una por línea: "Etiqueta" o "valor|Etiqueta" o "valor|Etiqueta|descripción"
        $lines = preg_split('/\R/', $opts) ?: [];
        $opts = [];
        foreach ($lines as $ln) {
            $ln = trim($ln);
            if ($ln === '') continue;
            $p = array_map('trim', explode('|', $ln));
            $opts[] = count($p) === 1 ? ['value' => $p[0], 'label' => $p[0]] : ['value' => $p[0], 'label' => $p[1] ?? $p[0], 'desc' => $p[2] ?? ''];
        }
    }
    $out = [];
    $seen = [];
    foreach ((array) $opts as $o) {
        if (is_string($o)) $o = ['value' => $o, 'label' => $o];
        if (!is_array($o)) continue;
        $label = trim((string) ($o['label'] ?? $o['value'] ?? ''));
        if ($label === '') continue;
        $value = trim((string) ($o['value'] ?? ''));
        if ($value === '') $value = $label;
        if (isset($seen[$value])) continue;
        $seen[$value] = true;
        $item = ['value' => $value, 'label' => $label];
        if (!empty($o['desc'])) $item['desc'] = (string) $o['desc'];
        if (!empty($o['colors'])) $item['colors'] = array_values(array_filter((array) $o['colors'], fn($c) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $c)));
        if (!empty($o['img']) && preg_match('#^(https://|/)[^\s"\'<>]+$#', (string) $o['img'])) $item['img'] = (string) $o['img'];
        if (!empty($o['demo']) && preg_match('/^(clock|font):[A-Za-z]{2,20}$/', (string) $o['demo'])) $item['demo'] = (string) $o['demo'];
        $out[] = $item;
    }
    return $out;
}

/** Devuelve todos los campos (no "info") en orden, con el índice de paso. */
function all_fields(array $def, bool $includeInfo = false): array
{
    $out = [];
    foreach ($def['steps'] as $si => $s) {
        foreach ($s['fields'] as $f) {
            if (!$includeInfo && $f['type'] === 'info') continue;
            $f['_step'] = $si;
            $f['_step_title'] = $s['title'];
            $out[] = $f;
        }
    }
    return $out;
}

// ---- Condiciones (mostrar si...) ------------------------------------------------
function cond_true(?array $cond, array $values): bool
{
    if (!$cond || empty($cond['field'])) return true;
    $v = $values[$cond['field']] ?? null;
    $vals = is_array($v) ? array_map('strval', $v) : (($v === null || $v === '') ? [] : [(string) $v]);
    if (!empty($cond['not_empty'])) return count($vals) > 0;
    $wanted = isset($cond['in']) ? array_map('strval', (array) $cond['in']) : (isset($cond['equals']) ? [(string) $cond['equals']] : []);
    if (!$wanted) return true;
    return count(array_intersect($vals, $wanted)) > 0;
}

// ---- Validación y guardado --------------------------------------------------------
/**
 * @return array{0: array, 1: array, 2: array, 3: array} [answers, errors, values, files]
 *   answers: lista ordenada [{k,label,type,step,value}] lista para guardar
 *   errors:  [name => mensaje]
 *   values:  [name => valor limpio] (para volver a mostrar el formulario si hay errores)
 *   files:   [name => [[name,path,size,mime], ...]] (ya movidos a disco; path relativo a storage/)
 */
function validate_submission(array $def, array $post, array $filesInput, ?int $submissionId = null): array
{
    $raw = is_array($post['f'] ?? null) ? $post['f'] : [];
    $values = [];
    $errors = [];
    $answers = [];
    $files = [];
    $pendingFiles = [];

    // 1) Valores crudos (para evaluar condiciones)
    foreach (all_fields($def) as $f) {
        $values[$f['name']] = clean_value($f, $raw);
    }

    foreach ($def['steps'] as $si => $s) {
        if (!cond_true($s['show_if'] ?? null, $values)) continue;
        foreach ($s['fields'] as $f) {
            if ($f['type'] === 'info') continue;
            if (!cond_true($f['show_if'] ?? null, $values)) { $values[$f['name']] = is_array($values[$f['name']]) ? [] : ''; continue; }
            $name = $f['name'];
            $val = $values[$name];
            $label = $f['label'];
            $isEmpty = is_array($val) ? count($val) === 0 : trim((string) $val) === '';

            if ($f['type'] === 'file') {
                $up = extract_uploads($filesInput, $name);
                $link = !empty($f['link_alt']) ? mb_substr(trim((string) ($raw[$name . '__link'] ?? '')), 0, 500) : '';
                if ($link !== '' && !preg_match('#^https?://#i', $link)) $link = 'https://' . $link;
                if ($f['required'] && !$up && $link === '') { $errors[$name] = !empty($f['link_alt']) ? 'Subí una foto o pegá un link.' : 'Adjuntá al menos un archivo.'; continue; }
                if ($link !== '' && (!filter_var($link, FILTER_VALIDATE_URL) || !str_contains($link, '.'))) { $errors[$name] = 'El link no parece válido: copialo completo desde Drive, Google Fotos o WeTransfer.'; continue; }
                $pendingFiles[$name] = $up;
                $errs = validate_uploads($up, $f);
                if ($errs) { $errors[$name] = $errs; continue; }
                $answers[] = ['k' => $name, 'label' => $label, 'type' => 'file', 'step' => $s['title'], 'value' => array_map(fn($u) => $u['name'], $up)];
                if ($link !== '') $answers[] = ['k' => $name . '__link', 'label' => $label . ' (link)', 'type' => 'url', 'step' => $s['title'], 'value' => $link, 'raw' => $link];
                if (!empty($f['captions'])) {
                    $caps = array_slice(array_map(fn($c) => mb_substr(trim((string) $c), 0, (int) ($f['caption_max'] ?? 160)), (array) ($raw[$name . '__cap'] ?? [])), 0, count($up));
                    if (array_filter($caps, fn($c) => $c !== '')) {
                        $answers[] = ['k' => $name . '__cap', 'label' => 'Textos de las fotos', 'type' => 'captions', 'step' => $s['title'],
                            'value' => array_values(array_filter(array_map(fn($c, $i) => $c !== '' ? 'Foto ' . ($i + 1) . ': ' . $c : '', $caps, array_keys($caps)))), 'raw' => $caps];
                    }
                }
                continue;
            }

            if ($f['type'] === 'repeater') {
                $rows = is_array($val) ? $val : [];
                $min = (int) ($f['min_rows'] ?? 0);
                if ($f['required'] && count($rows) < max(1, $min)) { $errors[$name] = $min > 1 ? 'Completá al menos ' . $min . '.' : 'Completá al menos una fila.'; continue; }
                $rowErr = null;
                foreach ($rows as $ri => $r) {
                    foreach ($f['fields'] as $sf) {
                        $sv = (string) ($r[$sf['name']] ?? '');
                        if ($sv === '') { if ($sf['required']) $rowErr = 'En la fila ' . ($ri + 1) . ' falta “' . $sf['label'] . '”.'; continue; }
                        if ($sf['type'] === 'time' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $sv)) $rowErr = 'Revisá la hora de la fila ' . ($ri + 1) . '.';
                        if ($sf['type'] === 'select' && !option_valid($sf, $sv)) $rowErr = 'Revisá la fila ' . ($ri + 1) . '.';
                    }
                    if ($rowErr) break;
                }
                if ($rowErr) { $errors[$name] = $rowErr; continue; }
                $answers[] = ['k' => $name, 'label' => $label, 'type' => 'repeater', 'step' => $s['title'], 'value' => repeater_lines($f, $rows), 'raw' => $rows];
                continue;
            }

            if ($isEmpty) {
                if ($f['required']) $errors[$name] = $f['type'] === 'consent' ? 'Necesitamos que aceptes para continuar.' : 'Este dato es obligatorio.';
                $answers[] = ['k' => $name, 'label' => $label, 'type' => $f['type'], 'step' => $s['title'], 'value' => is_array($val) ? [] : ''];
                continue;
            }

            if ($err = type_error($f, $val)) { $errors[$name] = $err; }
            elseif (($f['rule']['type'] ?? '') === 'before' && ($ref = (string) ($values[$f['rule']['ref'] ?? ''] ?? '')) !== '' && strcmp((string) $val, $ref) >= 0) {
                $errors[$name] = (string) ($f['rule']['message'] ?? 'Tiene que ser antes de la fecha del evento.');
            }
            $answers[] = ['k' => $name, 'label' => $label, 'type' => $f['type'], 'step' => $s['title'], 'value' => display_value($f, $val), 'raw' => $val];
        }
    }

    // 2) Mover archivos solo si todo es válido
    if (!$errors && $submissionId !== null) {
        foreach ($pendingFiles as $name => $ups) {
            foreach ($ups as $u) {
                $saved = save_upload($u, $submissionId);
                if ($saved) $files[$name][] = $saved;
            }
        }
    }
    return [$answers, $errors, $values, $files, $pendingFiles];
}

function clean_value(array $f, array $raw)
{
    $name = $f['name'];
    $v = $raw[$name] ?? null;
    $type = $f['type'];
    if ($type === 'file' || $type === 'info') return '';
    if ($type === 'switch') return ((string) (is_array($v) ? end($v) : $v) === '1') ? 'Sí' : 'No';
    if ($type === 'repeater') {
        $rows = [];
        foreach (is_array($v) ? $v : [] as $r) {
            if (!is_array($r)) continue;
            $row = [];
            foreach ($f['fields'] ?? [] as $sf) {
                $sv = trim((string) (is_array($r[$sf['name']] ?? null) ? '' : ($r[$sf['name']] ?? '')));
                $row[$sf['name']] = mb_substr($sv, 0, (int) ($sf['max'] ?? ($sf['type'] === 'textarea' ? 2000 : 300)));
            }
            if (implode('', $row) !== '') $rows[] = $row;
        }
        return array_slice($rows, 0, (int) ($f['max_rows'] ?? 10));
    }
    if (in_array($type, ['checkbox', 'palette'], true)) {
        $arr = is_array($v) ? array_map(fn($x) => trim((string) $x), $v) : [];
        $arr = array_values(array_filter($arr, fn($x) => $x !== ''));
        $other = trim((string) ($raw[$name . '__otro'] ?? ''));
        if (!empty($f['other']) && $other !== '' && in_array('__otro', $arr, true)) {
            $arr = array_values(array_filter($arr, fn($x) => $x !== '__otro'));
            $arr[] = 'Otro: ' . mb_substr($other, 0, 200);
        } else {
            $arr = array_values(array_filter($arr, fn($x) => $x !== '__otro'));
        }
        return array_slice($arr, 0, (int) ($f['max_count'] ?? 40) ?: 40);
    }
    if ($type === 'consent') return ($v === '1' || $v === 'on' || $v === 'si') ? 'Sí' : '';
    $s = is_array($v) ? '' : trim((string) $v);
    if (($type === 'radio' || $type === 'select') && $s === '__otro') {
        $other = trim((string) ($raw[$name . '__otro'] ?? ''));
        return $other !== '' ? 'Otro: ' . mb_substr($other, 0, 200) : '';
    }
    if ($type === 'tel' && ($f['normalize'] ?? '') === 'digits') return preg_replace('/\D+/', '', $s);
    $max = (int) ($f['max'] ?? 0) ?: ($type === 'textarea' ? 8000 : 500);
    return mb_substr($s, 0, $max);
}

function type_error(array $f, $val): ?string
{
    $t = $f['type'];
    if ($t === 'email' && !filter_var($val, FILTER_VALIDATE_EMAIL)) return 'Revisá el email: parece que falta algo.';
    if ($t === 'tel' && ($f['normalize'] ?? '') === 'digits') { if (!preg_match('/^\d{8,15}$/', (string) $val)) return 'Escribí el número con código de país, por ejemplo +54 9 280 412 3456.'; }
    elseif ($t === 'tel' && !preg_match('/^[+()\d\s.\-]{6,25}$/', (string) $val)) return 'Ingresá un teléfono válido, por ejemplo +54 9 280 123 4567.';
    if ($t === 'datetime' && !preg_match('/^\d{4}-\d{2}-\d{2}T([01]\d|2[0-3]):[0-5]\d$/', (string) $val)) return 'Elegí la fecha y la hora.';
    if ($t === 'url') {
        $u = (string) $val;
        if (!preg_match('#^https?://#i', $u)) $u = 'https://' . $u;
        if (!filter_var($u, FILTER_VALIDATE_URL) || !str_contains($u, '.')) return 'Pegá un enlace válido (que empiece con https://).';
    }
    if ($t === 'date') {
        $d = DateTime::createFromFormat('Y-m-d', (string) $val);
        if (!$d || $d->format('Y-m-d') !== $val) return 'Elegí una fecha válida.';
    }
    if ($t === 'time' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $val)) return 'Elegí una hora válida.';
    if ($t === 'number' && !is_numeric($val)) return 'Ingresá solo números.';
    if ($t === 'colors' && !preg_match('/^(#[0-9a-fA-F]{6})(\s*,\s*#[0-9a-fA-F]{6}){0,2}$/', (string) $val)) return 'Elegí hasta 3 colores.';
    if ($t === 'colors' && !empty($f['need']) && count(explode(',', (string) $val)) < (int) $f['need']) return 'Elegí los ' . (int) $f['need'] . ' colores.';
    if (in_array($t, ['select', 'radio'], true) && !option_valid($f, (string) $val)) return 'Elegí una de las opciones.';
    if (in_array($t, ['checkbox', 'palette'], true)) {
        foreach ((array) $val as $x) if (!option_valid($f, (string) $x)) return 'Hay una opción que no es válida.';
        if (!empty($f['min']) && count((array) $val) < (int) $f['min']) return 'Elegí al menos ' . (int) $f['min'] . '.';
    }
    return null;
}

/** Filas de una lista, en texto legible ("21:30 · Recepción"). */
function repeater_lines(array $f, array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        $parts = [];
        foreach ($f['fields'] as $sf) {
            $v = (string) ($r[$sf['name']] ?? '');
            if ($v === '') continue;
            if ($sf['type'] === 'select') $v = ($sf['short'] ?? $sf['label']) . ': ' . display_value($sf + ['type' => 'select'], $v);
            $parts[] = $v;
        }
        if ($parts) $out[] = implode(' · ', $parts);
    }
    return $out;
}

/** Guarda el texto de la opción (no su código interno) para que el panel y los emails sean legibles. */
function display_value(array $f, $val)
{
    if (!in_array($f['type'], ['select', 'radio', 'checkbox', 'palette'], true)) return $val;
    $map = [];
    foreach ($f['options'] ?? [] as $o) $map[(string) $o['value']] = $o['label'];
    if (is_array($val)) return array_map(fn($v) => $map[(string) $v] ?? $v, $val);
    return $map[(string) $val] ?? $val;
}

function option_valid(array $f, string $val): bool
{
    if (str_starts_with($val, 'Otro: ') && !empty($f['other'])) return true;
    foreach ($f['options'] ?? [] as $o) if ((string) $o['value'] === $val) return true;
    return false;
}

// ---- Archivos ---------------------------------------------------------------------
function extract_uploads(array $filesInput, string $name): array
{
    $f = $filesInput['files'] ?? null;
    if (!$f || !isset($f['name'][$name])) return [];
    $out = [];
    $names = (array) $f['name'][$name];
    foreach ($names as $i => $orig) {
        $err = (array) $f['error'][$name];
        if (($err[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = [
            'name' => (string) $orig,
            'tmp' => (string) ((array) $f['tmp_name'][$name])[$i],
            'size' => (int) ((array) $f['size'][$name])[$i],
            'error' => (int) $err[$i],
        ];
    }
    return $out;
}

function validate_uploads(array $ups, array $f = []): ?string
{
    $mb = (int) ($f['max_mb'] ?? 0) ?: (int) cfg('uploads.max_file_mb', 15);
    $max = $mb * 1048576;
    $maxN = (int) ($f['max_files'] ?? 0) ?: (int) cfg('uploads.max_files_per_field', 12);
    if (count($ups) > $maxN) return $maxN === 1 ? 'Elegí un solo archivo.' : 'Podés subir hasta ' . $maxN . ' archivos.';
    $allowed = !empty($f['ext']) ? array_values(array_intersect((array) $f['ext'], (array) cfg('uploads.allowed_ext', []))) : (array) cfg('uploads.allowed_ext', []);
    foreach ($ups as $u) {
        if ($u['error'] !== UPLOAD_ERR_OK) {
            return $u['error'] === UPLOAD_ERR_INI_SIZE || $u['error'] === UPLOAD_ERR_FORM_SIZE
                ? '“' . $u['name'] . '” es demasiado pesado.' : 'No pudimos subir “' . $u['name'] . '”. Probá de nuevo.';
        }
        if ($u['size'] > $max) return '“' . $u['name'] . '” supera los ' . $mb . ' MB.';
        $ext = strtolower(pathinfo($u['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) return 'El formato de “' . $u['name'] . '” no está permitido (usá ' . strtoupper(implode(', ', array_diff($allowed, ['jpeg']))) . ').';
        if (!is_uploaded_file($u['tmp']) && !defined('ALLOW_TEST_UPLOADS')) return 'Archivo inválido.';
    }
    return null;
}

function save_upload(array $u, int $submissionId): ?array
{
    $ext = strtolower(pathinfo($u['name'], PATHINFO_EXTENSION));
    $dir = STORAGE_DIR . '/uploads/' . $submissionId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return null;
    $stored = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $stored;
    $moved = defined('ALLOW_TEST_UPLOADS') ? copy($u['tmp'], $dest) : move_uploaded_file($u['tmp'], $dest);
    if (!$moved) return null;
    $mime = function_exists('mime_content_type') ? (string) @mime_content_type($dest) : 'application/octet-stream';
    $orig = preg_replace('/[^\p{L}\p{N}\-_. ()]+/u', '_', basename($u['name'])) ?: ('archivo.' . $ext);
    return ['name' => $orig, 'path' => $submissionId . '/' . $stored, 'size' => (int) filesize($dest), 'mime' => $mime];
}

function delete_submission_files(int $submissionId): void
{
    $dir = STORAGE_DIR . '/uploads/' . $submissionId;
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/*') ?: [] as $f) @unlink($f);
    @rmdir($dir);
}

// ---- Guardar envío completo ---------------------------------------------------------
/** Devuelve [ref, errors, values]. Si hay errores, no guarda nada. */
function handle_submission(array $formRow, array $def, array $post, array $filesInput): array
{
    // 1º validar sin mover archivos
    [$answers, $errors, $values, , $pending] = validate_submission($def, $post, $filesInput, null);
    if ($errors) return [null, $errors, $values];

    $ref = new_ref();
    $now = now();
    $id = db_insert('submissions', [
        'form_id' => $formRow['id'], 'ref' => $ref, 'status' => 'nuevo',
        'answers' => json_encode($answers, JSON_UNESCAPED_UNICODE), 'files' => null, 'search_text' => '',
        'notes' => '', 'ip_hash' => ip_hash(), 'created_at' => $now, 'updated_at' => $now,
    ]);

    $saved = [];
    foreach ($pending as $name => $ups) {
        foreach ($ups as $u) {
            $s = save_upload($u, $id);
            if ($s) $saved[$name][] = $s;
        }
    }
    $search = [];
    foreach ($answers as $a) $search[] = answer_text($a['value'], $a['type']);
    foreach ($saved as $list) foreach ($list as $s) $search[] = $s['name'];
    q('UPDATE submissions SET files = ?, search_text = ? WHERE id = ?', [
        $saved ? json_encode($saved, JSON_UNESCAPED_UNICODE) : null,
        mb_strtolower(implode(' ', $search)), $id,
    ]);
    return [['id' => $id, 'ref' => $ref, 'answers' => $answers, 'files' => $saved], [], $values];
}

// ---- Exportación para el editor de Qué Planazo -------------------------------------------
/** Arma el JSON con las claves del editor de Qué Planazo a partir de una respuesta guardada. */
function planazo_export(array $answers, array $files, string $ref = ''): array
{
    $r = [];
    foreach ($answers as $a) $r[$a['k']] = $a['raw'] ?? $a['value'];
    $v = fn(string $k, $d = '') => ($r[$k] ?? $d) === '' ? $d : ($r[$k] ?? $d);
    $names = fn(string $k) => array_map(fn($f) => $f['name'], $files[$k] ?? []);
    $first = fn(string $k) => $names($k)[0] ?? null;
    $off = [];
    foreach (['countdown', 'intro', 'gallery', 'details', 'trivia', 'capsule', 'extras', 'rsvp'] as $sec) {
        if (($r['sec_' . $sec] ?? 'Sí') === 'No') $off[] = $sec;
    }
    $colors = array_values(array_filter(array_map('trim', explode(',', (string) $v('colors')))));
    $trivia = array_map(fn($t) => [
        'question' => $t['question'] ?? '', 'a' => $t['a'] ?? '', 'b' => $t['b'] ?? '', 'c' => $t['c'] ?? '',
        'correct' => (int) ($t['correct'] ?? 0),
    ], is_array($r['trivia'] ?? null) ? $r['trivia'] : []);
    $schedule = array_map(fn($x) => ['time' => $x['time'] ?? '', 'label' => $x['label'] ?? ''], is_array($r['schedule'] ?? null) ? $r['schedule'] : []);
    $gallery = $names('gallery_photos');
    $caps = is_array($r['gallery_photos__cap'] ?? null) ? $r['gallery_photos__cap'] : [];
    return [
        'ref' => $ref,
        'host' => ['name' => $v('host_name'), 'email' => $v('host_email'), 'phone' => $v('host_phone')],
        'category' => $v('category'),
        'title' => $v('title'),
        'starts_at' => $v('starts_at'),
        'deadline' => $v('deadline'),
        'timezone' => 'America/Argentina/Buenos_Aires',
        'style' => [
            'theme' => $v('theme', 'jema'), 'look' => $v('look', 'jema'),
            'colors' => $colors,
            'font_titles' => $v('font_titles', 'jema'), 'font_texts' => $v('font_texts', 'jema'),
            'motif_style' => $v('motif_style', 'jema'), 'metal_style' => $v('metal_style', 'jema'),
            'background_style' => $v('background_style', 'jema'), 'mascot_style' => $v('mascot_style', 'jema'),
            'countdown_style' => $v('countdown_style', 'jema'),
        ],
        'cover' => ['file' => $first('cover_photo'), 'link' => $v('cover_photo__link', null)],
        'background_photo' => ['file' => $first('background_photo'), 'link' => null],
        'greeting' => $v('greeting'),
        'tagline' => $v('tagline'),
        'sections_off' => $off,
        'intro' => $v('intro'),
        'gallery' => [
            'title' => $v('gallery_title') ?: 'Pedacitos de nuestra historia',
            'files' => $gallery,
            'captions' => array_map(fn($i) => (string) ($caps[$i] ?? ''), array_keys($gallery)),
            'link' => $v('gallery_photos__link', null),
        ],
        'venue' => $v('venue'), 'address' => $v('address'), 'dress' => $v('dress'),
        'schedule' => $schedule,
        'trivia' => $trivia,
        'music_url' => $v('music_url'),
        'audio' => ['file' => $first('audio'), 'title' => $v('audio_title')],
        'gift' => $v('gift'),
        'menus' => array_values((array) $v('menus', [])),
    ];
}
