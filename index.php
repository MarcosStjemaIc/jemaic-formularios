<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

// Servidor de desarrollo de PHP: dejar pasar archivos estáticos
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    if ($p !== '/' && is_file(__DIR__ . $p) && !preg_match('#^/(app|storage|tools|config)#', $p) && !str_ends_with($p, '.php')) return false;
}

header('X-Robots-Tag: noindex');
ensure_schema();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$bp = base_path();
if ($bp !== '' && str_starts_with($path, $bp)) $path = substr($path, strlen($bp));
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---- Rutas públicas ----------------------------------------------------------------
if ($path === '/') {
    $rows = q_all('SELECT * FROM forms WHERE active = 1 ORDER BY sort_order, id');
    foreach ($rows as &$r) $r['_def'] = form_def($r);
    unset($r);
    echo render('public/home', ['forms' => $rows, 'pageTitle' => 'Formularios']);
    exit;
}

if (preg_match('#^/f/([a-z0-9\-]+)(/gracias)?$#', $path, $m)) {
    $row = form_row_by_slug($m[1]);
    if (!$row || (!(int) $row['active'] && !current_user())) {
        http_response_code(404);
        echo render_error('Este formulario no está disponible por el momento.', 'No encontrado');
        exit;
    }
    $def = form_def($row);
    $common = ['pageTitle' => $def['title'], 'accent' => $def['accent'], 'brand' => $def['brand']];

    // Página de gracias
    if (!empty($m[2])) {
        start_session();
        $ref = $_SESSION['last_ref'][$row['slug']] ?? '';
        echo render('public/thanks', ['row' => $row, 'def' => $def, 'ref' => $ref, 'bodyClass' => 'is-thanks'] + $common);
        exit;
    }

    // Envío
    if ($method === 'POST') {
        csrf_check();
        $errors = [];
        // Anti-spam: campo trampa, tiempo mínimo y límite por IP
        $tooFast = (time() - (int) ($_POST['_t'] ?? 0)) < 4;
        $rate = (int) q_val('SELECT COUNT(*) FROM submissions WHERE ip_hash = ? AND created_at > ?', [ip_hash(), date('Y-m-d H:i:s', time() - 3600)]);
        if (!empty($_POST['website']) || $tooFast) {
            // Parece un robot: fingimos éxito sin guardar nada
            start_session();
            redirect('f/' . $row['slug'] . '/gracias');
        }
        if ($rate >= (int) cfg('rate_limit_per_hour', 30)) {
            http_response_code(429);
            echo render_error('Recibimos muchos envíos desde tu conexión. Probá de nuevo en un rato.', 'Demasiados envíos');
            exit;
        }
        if (!empty($_SERVER['CONTENT_LENGTH']) && empty($_POST) && empty($_FILES)) {
            http_response_code(413);
            echo render_error('Los archivos son demasiado pesados para enviarlos juntos. Probá con menos archivos o más livianos.', 'Archivos muy grandes');
            exit;
        }

        [$sub, $errors, $values] = handle_submission($row, $def, $_POST, $_FILES);
        if ($errors) {
            http_response_code(422);
            echo render('public/form', ['row' => $row, 'def' => $def, 'values' => $values, 'errors' => $errors] + $common);
            exit;
        }
        start_session();
        $_SESSION['last_ref'][$row['slug']] = $sub['ref'];
        // Respondemos primero y mandamos los emails después
        header('Location: ' . url('f/' . $row['slug'] . '/gracias'), true, 303);
        header('Connection: close');
        header('Content-Length: 0');
        session_write_close();
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request(); else { @ob_end_flush(); @flush(); }
        ignore_user_abort(true);
        send_submission_emails($row, $def, $sub);
        exit;
    }

    // Mostrar formulario
    echo render('public/form', ['row' => $row, 'def' => $def, 'values' => [], 'errors' => []] + $common);
    exit;
}

// ---- Panel de administración ---------------------------------------------------------
if (str_starts_with($path, '/admin')) {
    require APP_DIR . '/admin.php';
    admin_dispatch($path, $method);
    exit;
}

http_response_code(404);
echo render_error('No encontramos la página que buscabas.', 'No encontrada');
