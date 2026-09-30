<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('STORAGE_DIR', APP_ROOT . '/storage');

// ---- Configuración -------------------------------------------------------
$configFile = APP_ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Falta configuración</title>'
       . '<body style="font-family:system-ui;max-width:560px;margin:15vh auto;padding:0 20px;line-height:1.5">'
       . '<h1>Falta config.php</h1><p>Copiá <code>config.sample.php</code> como <code>config.php</code> '
       . 'y completá los datos de la base de datos y del correo.</p></body>';
    exit;
}
$GLOBALS['CONFIG'] = require $configFile;

function cfg(string $path, $default = null)
{
    $v = $GLOBALS['CONFIG'];
    foreach (explode('.', $path) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return $default;
        $v = $v[$k];
    }
    return $v;
}

date_default_timezone_set((string) cfg('timezone', 'America/Argentina/Buenos_Aires'));
mb_internal_encoding('UTF-8');

// ---- Errores -------------------------------------------------------------
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_DIR . '/logs/error.log');
ini_set('display_errors', cfg('debug') ? '1' : '0');
error_reporting(E_ALL);

function app_log(string $msg): void
{
    @file_put_contents(STORAGE_DIR . '/logs/app.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

set_exception_handler(function (Throwable $e) {
    app_log('EXCEPTION ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $detail = cfg('debug') ? '<pre>' . htmlspecialchars((string) $e) . '</pre>' : '';
    echo '<!doctype html><meta charset="utf-8"><title>Error</title><body style="font-family:system-ui;max-width:560px;margin:15vh auto;padding:0 20px;line-height:1.5">'
       . '<h1>Algo salió mal</h1><p>Ya lo registramos. Probá de nuevo en unos minutos.</p>' . $detail . '</body>';
});

foreach (['db', 'helpers', 'auth', 'forms', 'mail', 'schema', 'render'] as $part) {
    require APP_DIR . '/' . $part . '.php';
}
