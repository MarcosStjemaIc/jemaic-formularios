<?php
/**
 * Configuración de formularios.jemaic.com
 *
 * 1) Copiá este archivo como config.php
 * 2) Completá los datos de la base de datos (hPanel → Bases de datos → MySQL)
 * 3) Completá el correo SMTP (hPanel → Correos electrónicos)
 *
 * config.php NO se debe compartir ni subir a GitHub (ya está en .gitignore).
 */
return [
    'app_name'  => 'Jema Imagen Creativa',
    'base_url'  => 'https://formularios.jemaic.com',   // sin barra final
    'timezone'  => 'America/Argentina/Buenos_Aires',
    'debug'     => false,                                // true solo para probar

    // Clave que se pide UNA sola vez para crear el primer usuario administrador
    // (entrás a  https://formularios.jemaic.com/admin/setup?key=TU_CLAVE ).
    // Poné algo largo y aleatorio.
    'setup_key' => 'CAMBIAR-POR-UNA-CLAVE-LARGA',

    'db' => [
        'driver'  => 'mysql',            // 'mysql' en Hostinger; 'sqlite' solo para pruebas locales
        'host'    => 'localhost',
        'name'    => 'u000000000_formularios',
        'user'    => 'u000000000_formularios',
        'pass'    => '',
        'charset' => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/storage/dev.sqlite',
    ],

    'mail' => [
        'driver'     => 'smtp',          // 'smtp' (recomendado) o 'mail' (función mail() del servidor)
        'from_email' => 'formularios@jemaic.com',
        'from_name'  => 'Jema Imagen Creativa',
        'smtp' => [
            'host'   => 'smtp.hostinger.com',
            'port'   => 465,
            'secure' => 'ssl',           // 'ssl' (465) o 'tls' (587)
            'user'   => 'formularios@jemaic.com',
            'pass'   => '',
        ],
        // Correos que reciben el aviso cuando alguien completa un formulario
        // (cada formulario puede sumar los suyos desde el editor).
        'notify' => [ /* 'info@jemaic.com' */ ],
    ],

    'uploads' => [
        'max_file_mb'       => 15,
        'max_files_per_field' => 12,
        'allowed_ext'       => ['jpg','jpeg','png','webp','heic','gif','pdf','doc','docx','txt','mp3','m4a'],
    ],

    // WhatsApp de la coordinadora (solo números, con código de país). Se usa en el botón de la página de "gracias".
    'coordinator_whatsapp' => '5492804343587',
    'coordinator_name'     => 'la coordinadora',

    // Máximo de envíos por IP por hora (anti-spam)
    'rate_limit_per_hour' => 30,
];
