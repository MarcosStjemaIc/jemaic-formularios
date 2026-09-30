<?php
declare(strict_types=1);

const STATUSES = ['nuevo' => 'Nuevo', 'en_curso' => 'En curso', 'listo' => 'Listo', 'archivado' => 'Archivado'];

function admin_render(string $view, array $vars = []): void
{
    $vars += ['user' => current_user(), 'flash' => flash()];
    echo render('admin/' . $view, $vars, 'admin');
}

function admin_dispatch(string $path, string $method)
{
    header('Cache-Control: no-store');

    // --- Sin sesión ---
    if ($path === '/admin/login') return admin_login($method);
    if ($path === '/admin/setup') return admin_setup($method);

    $user = require_login();
    if ($method === 'POST') csrf_check();

    if ($path === '/admin/logout' && $method === 'POST') { logout(); redirect('admin/login'); }
    if ($path === '/admin' || $path === '/admin/') return admin_dashboard();

    if ($path === '/admin/forms/new' && $method === 'POST') return admin_form_create();

    if (preg_match('#^/admin/forms/(\d+)/(edit|duplicate|toggle|delete|responses|export)(?:\.csv)?$#', $path, $m)) {
        $id = (int) $m[1];
        $row = form_row($id);
        if (!$row) { http_response_code(404); echo render_error('Formulario inexistente.', 'No encontrado'); return; }
        switch ($m[2]) {
            case 'edit':      return $method === 'POST' ? admin_form_save($row) : admin_form_edit($row);
            case 'duplicate': return admin_form_duplicate($row);
            case 'toggle':    return admin_form_toggle($row);
            case 'delete':    return admin_form_delete($row);
            case 'responses': return admin_responses($row);
            case 'export':    return admin_export($row);
        }
    }

    if (preg_match('#^/admin/responses/(\d+)(?:/(update|delete))?$#', $path, $m)) {
        $sub = q_one('SELECT * FROM submissions WHERE id = ?', [(int) $m[1]]);
        if (!$sub) { http_response_code(404); echo render_error('Respuesta inexistente.', 'No encontrada'); return; }
        if (($m[2] ?? '') === 'update' && $method === 'POST') return admin_response_update($sub);
        if (($m[2] ?? '') === 'delete' && $method === 'POST') return admin_response_delete($sub);
        return admin_response($sub);
    }

    if (preg_match('#^/admin/files/(\d+)/([a-z0-9_]+)/(\d+)$#', $path, $m)) return admin_file((int) $m[1], $m[2], (int) $m[3]);

    if ($path === '/admin/users') return admin_users($method);
    if (preg_match('#^/admin/users/(\d+)/delete$#', $path, $m) && $method === 'POST') return admin_user_delete((int) $m[1]);
    if ($path === '/admin/password' && $method === 'POST') return admin_password();

    http_response_code(404);
    echo render_error('No encontramos esa página del panel.', 'No encontrada');
}

// ---- Acceso -----------------------------------------------------------------------------
function admin_login(string $method): void
{
    if (current_user()) redirect('admin');
    $error = '';
    if ($method === 'POST') {
        csrf_check();
        if (login_blocked()) {
            $error = 'Demasiados intentos. Esperá unos minutos y probá de nuevo.';
        } elseif (attempt_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            $to = $_SESSION['after_login'] ?? '/admin';
            unset($_SESSION['after_login']);
            redirect(str_starts_with((string) $to, '/admin') || str_starts_with((string) $to, url('admin')) ? (string) $to : 'admin');
        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    }
    echo render('admin/login', ['error' => $error, 'pageTitle' => 'Ingresar'], 'admin_bare');
}

function admin_setup(string $method): void
{
    $key = (string) ($_GET['key'] ?? $_POST['key'] ?? '');
    $real = (string) cfg('setup_key', '');
    $count = (int) q_val('SELECT COUNT(*) FROM users');
    if ($count > 0) { flash('El panel ya está configurado. Ingresá con tu usuario.', 'ok'); redirect('admin/login'); }
    if ($real === '' || str_starts_with($real, 'CAMBIAR') || !hash_equals($real, $key)) {
        http_response_code(403);
        echo render_error('La clave de instalación no es válida. Revisá el valor de setup_key en config.php.', 'Acceso denegado');
        return;
    }
    $error = '';
    $v = ['name' => '', 'email' => ''];
    if ($method === 'POST') {
        csrf_check();
        $v = ['name' => trim((string) ($_POST['name'] ?? '')), 'email' => mb_strtolower(trim((string) ($_POST['email'] ?? '')))];
        $pass = (string) ($_POST['password'] ?? '');
        if ($v['name'] === '') $error = 'Escribí tu nombre.';
        elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $error = 'Revisá el email.';
        elseif ($e = password_problem($pass)) $error = $e;
        elseif ($pass !== (string) ($_POST['password2'] ?? '')) $error = 'Las contraseñas no coinciden.';
        else {
            db_insert('users', ['email' => $v['email'], 'name' => $v['name'], 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'created_at' => now()]);
            attempt_login($v['email'], $pass);
            flash('¡Listo! Ya podés administrar tus formularios.');
            redirect('admin');
        }
    }
    echo render('admin/setup', ['error' => $error, 'v' => $v, 'key' => $key, 'pageTitle' => 'Crear administrador'], 'admin_bare');
}

// ---- Panel principal ------------------------------------------------------------------------
function admin_dashboard(): void
{
    $forms = q_all('SELECT * FROM forms ORDER BY sort_order, id');
    $stats = [];
    foreach (q_all("SELECT form_id, COUNT(*) AS total, SUM(CASE WHEN status = 'nuevo' THEN 1 ELSE 0 END) AS nuevos, MAX(created_at) AS last FROM submissions GROUP BY form_id") as $s) $stats[$s['form_id']] = $s;
    foreach ($forms as &$f) { $f['_def'] = form_def($f); $f['_stats'] = $stats[$f['id']] ?? ['total' => 0, 'nuevos' => 0, 'last' => null]; }
    unset($f);
    $recent = q_all('SELECT s.id, s.ref, s.status, s.created_at, s.answers, f.title AS form_title, f.definition FROM submissions s JOIN forms f ON f.id = s.form_id ORDER BY s.created_at DESC, s.id DESC LIMIT 8');
    admin_render('dashboard', ['forms' => $forms, 'recent' => $recent, 'pageTitle' => 'Formularios']);
}

/** Texto identificatorio de una respuesta según los campos "list_fields" del formulario. */
function submission_title(array $answers, array $def): string
{
    $by = [];
    foreach ($answers as $a) $by[$a['k']] = $a;
    $parts = [];
    foreach ($def['list_fields'] as $k) {
        if (!empty($by[$k])) { $t = answer_text($by[$k]['value'], $by[$k]['type']); if ($t !== '') $parts[] = $t; }
        if (count($parts) >= 2) break;
    }
    return implode(' · ', $parts) ?: '(sin nombre)';
}

// ---- Formularios: crear / duplicar / activar / borrar -------------------------------------------
function unique_slug(string $base, ?int $exceptId = null): string
{
    $base = slugify($base) ?: 'formulario';
    $slug = $base; $n = 2;
    while (q_val('SELECT id FROM forms WHERE slug = ?' . ($exceptId ? ' AND id <> ' . (int) $exceptId : ''), [$slug])) $slug = $base . '-' . $n++;
    return $slug;
}

function admin_form_create(): void
{
    $title = trim((string) ($_POST['title'] ?? '')) ?: 'Formulario nuevo';
    $def = normalize_definition([
        'title' => $title, 'subtitle' => '', 'intro' => 'Completá este formulario.',
        'steps' => [['id' => 'paso-1', 'title' => 'Tus datos', 'description' => '', 'fields' => [
            ['type' => 'text', 'name' => 'nombre', 'label' => 'Nombre y apellido', 'required' => true],
            ['type' => 'email', 'name' => 'email', 'label' => 'Correo electrónico', 'required' => true],
        ]]],
        'client_email_field' => 'email', 'list_fields' => ['nombre'],
    ]);
    $id = db_insert('forms', [
        'slug' => unique_slug($title), 'title' => $title, 'active' => 0, 'sort_order' => (int) q_val('SELECT COALESCE(MAX(sort_order),0)+1 FROM forms'),
        'definition' => json_encode($def, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now(),
    ]);
    flash('Formulario creado. Está desactivado hasta que lo publiques.');
    redirect('admin/forms/' . $id . '/edit');
}

function admin_form_duplicate(array $row): void
{
    if (!is_post()) redirect('admin');
    $def = form_def($row);
    $def['title'] .= ' (copia)';
    $id = db_insert('forms', [
        'slug' => unique_slug($row['slug'] . '-copia'), 'title' => $def['title'], 'active' => 0,
        'sort_order' => (int) q_val('SELECT COALESCE(MAX(sort_order),0)+1 FROM forms'),
        'definition' => json_encode($def, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now(),
    ]);
    flash('Copia creada (desactivada).');
    redirect('admin/forms/' . $id . '/edit');
}

function admin_form_toggle(array $row): void
{
    if (!is_post()) redirect('admin');
    q('UPDATE forms SET active = ?, updated_at = ? WHERE id = ?', [(int) !$row['active'], now(), $row['id']]);
    flash($row['active'] ? 'Formulario desactivado: ya no se puede completar.' : 'Formulario publicado.');
    redirect('admin');
}

function admin_form_delete(array $row): void
{
    if (!is_post()) redirect('admin');
    $n = (int) q_val('SELECT COUNT(*) FROM submissions WHERE form_id = ?', [$row['id']]);
    if ($n > 0 && trim((string) ($_POST['confirm'] ?? '')) !== $row['slug']) {
        flash('Para borrar un formulario con respuestas, escribí su dirección (' . $row['slug'] . ') para confirmar.', 'err');
        redirect('admin/forms/' . $row['id'] . '/edit');
    }
    foreach (q_all('SELECT id FROM submissions WHERE form_id = ?', [$row['id']]) as $s) delete_submission_files((int) $s['id']);
    q('DELETE FROM submissions WHERE form_id = ?', [$row['id']]);
    q('DELETE FROM forms WHERE id = ?', [$row['id']]);
    flash('Formulario borrado.');
    redirect('admin');
}

// ---- Editor ------------------------------------------------------------------------------------------
function admin_form_edit(array $row, ?array $draft = null, array $errors = []): void
{
    $def = $draft ?? form_def($row);
    admin_render('edit', ['row' => $row, 'def' => $def, 'errors' => $errors, 'pageTitle' => 'Editar: ' . $row['title'],
        'nResponses' => (int) q_val('SELECT COUNT(*) FROM submissions WHERE form_id = ?', [$row['id']])]);
}

function admin_form_save(array $row): void
{
    $errors = [];
    $steps = json_decode((string) ($_POST['steps_json'] ?? ''), true);
    if (!is_array($steps)) { $errors[] = 'No pudimos leer los pasos del formulario. Recargá la página e intentá de nuevo.'; $steps = []; }

    $slug = slugify((string) ($_POST['slug'] ?? ''));
    if ($slug === '') $errors[] = 'La dirección del formulario no puede estar vacía.';
    elseif (q_val('SELECT id FROM forms WHERE slug = ? AND id <> ?', [$slug, $row['id']])) $errors[] = 'Ya existe otro formulario con esa dirección.';

    $def = normalize_definition([
        'title' => trim((string) ($_POST['title'] ?? '')) ?: 'Formulario sin título',
        'subtitle' => trim((string) ($_POST['subtitle'] ?? '')),
        'intro' => trim((string) ($_POST['intro'] ?? '')),
        'icon' => isset(FORM_ICONS[$_POST['icon'] ?? '']) ? $_POST['icon'] : 'card',
        'brand' => ($_POST['brand'] ?? '') === 'planazo' ? 'planazo' : 'jema',
        'accent' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST['accent'] ?? '')) ? $_POST['accent'] : '',
        'submit_label' => trim((string) ($_POST['submit_label'] ?? '')) ?: 'Enviar formulario',
        'thanks_title' => trim((string) ($_POST['thanks_title'] ?? '')) ?: '¡Gracias!',
        'thanks_text' => trim((string) ($_POST['thanks_text'] ?? '')),
        'notify' => (string) ($_POST['notify'] ?? ''),
        'client_email_field' => (string) ($_POST['client_email_field'] ?? ''),
        'list_fields' => (array) ($_POST['list_fields'] ?? []),
        'steps' => $steps,
    ]);
    if (!$def['steps']) $errors[] = 'El formulario necesita al menos un paso con campos.';
    $names = array_column(all_fields($def), 'name');
    $def['list_fields'] = array_values(array_intersect($def['list_fields'], $names));
    if (!in_array($def['client_email_field'], $names, true)) $def['client_email_field'] = '';

    if ($errors) {
        flash(implode(' ', $errors), 'err');
        $row['slug'] = $slug ?: $row['slug'];
        admin_form_edit($row, $def, $errors);
        return;
    }
    q('UPDATE forms SET slug = ?, title = ?, active = ?, definition = ?, updated_at = ? WHERE id = ?', [
        $slug, $def['title'], !empty($_POST['active']) ? 1 : 0, json_encode($def, JSON_UNESCAPED_UNICODE), now(), $row['id'],
    ]);
    flash('Cambios guardados.');
    redirect('admin/forms/' . $row['id'] . '/edit');
}

// ---- Respuestas ---------------------------------------------------------------------------------------
function admin_responses(array $row): void
{
    $def = form_def($row);
    $status = (string) ($_GET['status'] ?? '');
    $qs = trim((string) ($_GET['q'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $per = 25;

    $where = 'form_id = ?'; $params = [$row['id']];
    if (isset(STATUSES[$status])) { $where .= ' AND status = ?'; $params[] = $status; } else { $status = ''; }
    if ($qs !== '') { $where .= ' AND (search_text LIKE ? OR ref LIKE ?)'; $params[] = '%' . mb_strtolower(str_replace(['%', '_'], ['\%', '\_'], $qs)) . '%'; $params[] = '%' . $qs . '%'; }

    $total = (int) q_val('SELECT COUNT(*) FROM submissions WHERE ' . $where, $params);
    $pages = max(1, (int) ceil($total / $per));
    $page = min($page, $pages);
    $rows = q_all('SELECT id, ref, status, answers, files, created_at FROM submissions WHERE ' . $where . ' ORDER BY created_at DESC, id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $params);

    $counts = ['' => (int) q_val('SELECT COUNT(*) FROM submissions WHERE form_id = ?', [$row['id']])];
    foreach (q_all('SELECT status, COUNT(*) AS n FROM submissions WHERE form_id = ? GROUP BY status', [$row['id']]) as $c) $counts[$c['status']] = (int) $c['n'];

    // Columnas: los list_fields del formulario (máx. 4)
    $cols = [];
    $byName = [];
    foreach (all_fields($def) as $f) $byName[$f['name']] = $f;
    foreach ($def['list_fields'] as $k) if (isset($byName[$k])) $cols[] = $byName[$k];
    $cols = array_slice($cols, 0, 4);

    admin_render('responses', compact('row', 'def', 'rows', 'cols', 'counts', 'status', 'qs', 'page', 'pages', 'total') + ['pageTitle' => 'Respuestas: ' . $row['title']]);
}

function admin_response(array $sub): void
{
    $row = form_row((int) $sub['form_id']);
    $def = $row ? form_def($row) : normalize_definition([]);
    $answers = json_decode((string) $sub['answers'], true) ?: [];
    $files = json_decode((string) ($sub['files'] ?? ''), true) ?: [];
    $prev = q_val('SELECT id FROM submissions WHERE form_id = ? AND (created_at > ? OR (created_at = ? AND id > ?)) ORDER BY created_at ASC, id ASC LIMIT 1', [$sub['form_id'], $sub['created_at'], $sub['created_at'], $sub['id']]);
    $next = q_val('SELECT id FROM submissions WHERE form_id = ? AND (created_at < ? OR (created_at = ? AND id < ?)) ORDER BY created_at DESC, id DESC LIMIT 1', [$sub['form_id'], $sub['created_at'], $sub['created_at'], $sub['id']]);
    // Al abrir una respuesta nueva, pasa a "En curso"? No: el cambio de estado es manual.
    admin_render('response', compact('sub', 'row', 'def', 'answers', 'files', 'prev', 'next') + ['pageTitle' => $sub['ref']]);
}

function admin_response_update(array $sub): void
{
    $status = (string) ($_POST['status'] ?? $sub['status']);
    if (!isset(STATUSES[$status])) $status = $sub['status'];
    q('UPDATE submissions SET status = ?, notes = ?, updated_at = ? WHERE id = ?', [$status, mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 5000), now(), $sub['id']]);
    flash('Guardado.');
    redirect('admin/responses/' . $sub['id']);
}

function admin_response_delete(array $sub): void
{
    delete_submission_files((int) $sub['id']);
    q('DELETE FROM submissions WHERE id = ?', [$sub['id']]);
    flash('Respuesta borrada.');
    redirect('admin/forms/' . $sub['form_id'] . '/responses');
}

function admin_file(int $sid, string $field, int $idx): void
{
    $sub = q_one('SELECT files FROM submissions WHERE id = ?', [$sid]);
    $files = json_decode((string) ($sub['files'] ?? ''), true) ?: [];
    $f = $files[$field][$idx] ?? null;
    $full = $f ? realpath(STORAGE_DIR . '/uploads/' . $f['path']) : false;
    $root = realpath(STORAGE_DIR . '/uploads');
    if (!$f || !$full || !$root || !str_starts_with($full, $root . DIRECTORY_SEPARATOR) || !is_file($full)) {
        http_response_code(404); echo render_error('Archivo no encontrado.', 'No encontrado'); return;
    }
    $mime = (string) ($f['mime'] ?? 'application/octet-stream');
    $inline = preg_match('#^(image/(jpeg|png|webp|gif)|application/pdf|audio/)#', $mime) === 1;
    header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
    header('Content-Length: ' . filesize($full));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: ' . (($inline && empty($_GET['dl'])) ? 'inline' : 'attachment') . '; filename="' . rawurlencode($f['name']) . '"');
    readfile($full);
    exit;
}

// ---- Exportar a CSV (se abre directo en Excel) --------------------------------------------------------------
function csv_safe(string $v): string
{
    return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) ? "'" . $v : $v;
}

function admin_export(array $row): void
{
    $def = form_def($row);
    $subs = q_all('SELECT * FROM submissions WHERE form_id = ? ORDER BY created_at ASC, id ASC', [$row['id']]);

    // Columnas: campos actuales + claves viejas que aparezcan en respuestas antiguas
    $cols = [];
    foreach (all_fields($def) as $f) $cols[$f['name']] = $f['label'];
    $decoded = [];
    foreach ($subs as $s) {
        $a = json_decode((string) $s['answers'], true) ?: [];
        $decoded[$s['id']] = $a;
        foreach ($a as $x) if (!isset($cols[$x['k']])) $cols[$x['k']] = $x['label'] . ' (campo anterior)';
    }

    $filename = slugify($row['slug']) . '-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea bien los acentos
    $delim = ';';
    fputcsv($out, array_merge(['Código', 'Fecha de envío', 'Estado'], array_values($cols), ['Archivos', 'Notas internas']), $delim, '"', '');
    foreach ($subs as $s) {
        $by = [];
        foreach ($decoded[$s['id']] as $x) $by[$x['k']] = answer_text($x['value'], $x['type']);
        $files = json_decode((string) ($s['files'] ?? ''), true) ?: [];
        $fileNames = [];
        foreach ($files as $list) foreach ($list as $f) $fileNames[] = $f['name'];
        $line = [$s['ref'], fmt_datetime($s['created_at']), STATUSES[$s['status']] ?? $s['status']];
        foreach (array_keys($cols) as $k) $line[] = csv_safe($by[$k] ?? '');
        $line[] = implode(' | ', $fileNames);
        $line[] = csv_safe((string) $s['notes']);
        fputcsv($out, $line, $delim, '"', '');
    }
    fclose($out);
    exit;
}

// ---- Usuarios --------------------------------------------------------------------------------------------------------
function admin_users(string $method): void
{
    if ($method === 'POST') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $pass = (string) ($_POST['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) flash('Completá nombre y un email válido.', 'err');
        elseif ($e = password_problem($pass)) flash($e, 'err');
        elseif (q_val('SELECT id FROM users WHERE email = ?', [$email])) flash('Ya existe un usuario con ese email.', 'err');
        else { db_insert('users', ['email' => $email, 'name' => $name, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'created_at' => now()]); flash('Usuario creado.'); }
        redirect('admin/users');
    }
    admin_render('users', ['users' => q_all('SELECT id, name, email, created_at, last_login FROM users ORDER BY id'), 'pageTitle' => 'Usuarios']);
}

function admin_user_delete(int $id): void
{
    $me = current_user();
    if ($id === (int) $me['id']) flash('No podés borrar tu propio usuario.', 'err');
    elseif ((int) q_val('SELECT COUNT(*) FROM users') <= 1) flash('Tiene que quedar al menos un usuario.', 'err');
    else { q('DELETE FROM users WHERE id = ?', [$id]); flash('Usuario borrado.'); }
    redirect('admin/users');
}

function admin_password(): void
{
    $me = current_user();
    $row = q_one('SELECT password_hash FROM users WHERE id = ?', [$me['id']]);
    $new = (string) ($_POST['new'] ?? '');
    if (!password_verify((string) ($_POST['current'] ?? ''), (string) $row['password_hash'])) flash('La contraseña actual no es correcta.', 'err');
    elseif ($e = password_problem($new)) flash($e, 'err');
    elseif ($new !== (string) ($_POST['new2'] ?? '')) flash('Las contraseñas nuevas no coinciden.', 'err');
    else { q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]); flash('Contraseña actualizada.'); }
    redirect('admin/users');
}
