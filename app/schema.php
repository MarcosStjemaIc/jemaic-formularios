<?php
declare(strict_types=1);

/** Crea las tablas si no existen (se ejecuta en cada arranque; es barato y idempotente). */
function ensure_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    // Una vez creado todo, dejamos una marca para no repetirlo en cada visita.
    // (Para reinstalar en otra base de datos: borrar storage/.schema_ok)
    $marker = STORAGE_DIR . '/.schema_ok';
    if (is_file($marker)) { upgrade_seeded_forms(); return; }

    $lite = db_sqlite();
    $pk   = $lite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $big  = $lite ? 'TEXT' : 'LONGTEXT';
    $tail = $lite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $dt   = $lite ? 'TEXT' : 'DATETIME';

    $tables = [
        "CREATE TABLE IF NOT EXISTS forms (
            id $pk,
            slug VARCHAR(80) NOT NULL UNIQUE,
            title VARCHAR(200) NOT NULL,
            active TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            definition $big NOT NULL,
            created_at $dt NOT NULL,
            updated_at $dt NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS submissions (
            id $pk,
            form_id INT NOT NULL,
            ref VARCHAR(16) NOT NULL UNIQUE,
            status VARCHAR(20) NOT NULL DEFAULT 'nuevo',
            answers $big NOT NULL,
            files $big NULL,
            search_text $big NULL,
            notes $big NULL,
            ip_hash VARCHAR(64) NULL,
            created_at $dt NOT NULL,
            updated_at $dt NOT NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS users (
            id $pk,
            email VARCHAR(190) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at $dt NOT NULL,
            last_login $dt NULL
        )$tail",
        "CREATE TABLE IF NOT EXISTS login_attempts (
            id $pk,
            ip_hash VARCHAR(64) NOT NULL,
            created_at $dt NOT NULL
        )$tail",
    ];
    foreach ($tables as $sql) db()->exec($sql);

    if (!$lite) {
        // Índices (ignorar si ya existen)
        foreach ([
            'CREATE INDEX idx_sub_form ON submissions (form_id, created_at)',
            'CREATE INDEX idx_login_ip ON login_attempts (ip_hash, created_at)',
        ] as $sql) {
            try { db()->exec($sql); } catch (Throwable $e) { /* ya existe */ }
        }
    } else {
        db()->exec('CREATE INDEX IF NOT EXISTS idx_sub_form ON submissions (form_id, created_at)');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_login_ip ON login_attempts (ip_hash, created_at)');
    }

    seed_forms_if_empty();
    @file_put_contents($marker, date('c'));
}

/** Carga los formularios iniciales la primera vez. */
function seed_forms_if_empty(): void
{
    if ((int) q_val('SELECT COUNT(*) FROM forms') > 0) return;
    $order = 0;
    foreach (['informacion-previa', 'pre-sesion', 'fotoclip', 'invitacion-digital'] as $slug) {
        $file = APP_DIR . '/seeds/' . $slug . '.php';
        if (!is_file($file)) continue;
        $def = require $file;
        $def = normalize_definition($def);
        db_insert('forms', [
            'slug' => $slug,
            'title' => $def['title'],
            'active' => 1,
            'sort_order' => ++$order,
            'definition' => json_encode($def, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

/**
 * Actualiza una sola vez los formularios que vienen con el sistema cuando su versión cambia
 * (hoy: la invitación digital v2). Guarda una copia de la versión anterior en storage/.
 * Las respuestas ya recibidas no cambian: cada una guarda su propia copia de las preguntas.
 */
function upgrade_seeded_forms(): void
{
    $marker = STORAGE_DIR . '/.forms_v2';
    if (is_file($marker)) { upgrade_theme_covers(); return; }
    $file = APP_DIR . '/seeds/invitacion-digital.php';
    $row = q_one('SELECT * FROM forms WHERE slug = ?', ['invitacion-digital']);
    if ($row && is_file($file)) {
        $old = json_decode((string) $row['definition'], true) ?: [];
        if ((int) ($old['version'] ?? 1) < 2) {
            @file_put_contents(STORAGE_DIR . '/backup-invitacion-digital-v1.json', (string) $row['definition']);
            $def = normalize_definition(require $file);
            q('UPDATE forms SET title = ?, definition = ?, updated_at = ? WHERE id = ?', [$def['title'], json_encode($def, JSON_UNESCAPED_UNICODE), now(), $row['id']]);
            app_log('Formulario invitacion-digital actualizado a la versión 2 (copia anterior en storage/backup-invitacion-digital-v1.json).');
        }
    }
    @file_put_contents($marker, date('c'));
    upgrade_theme_covers();
}

/** v3: las opciones de "Diseño de portada" muestran la portada completa (assets/covers). No toca nada más del formulario. */
function upgrade_theme_covers(): void
{
    $marker = STORAGE_DIR . '/.forms_v3';
    if (is_file($marker)) { upgrade_split_style(); return; }
    $row = q_one('SELECT * FROM forms WHERE slug = ?', ['invitacion-digital']);
    $def = $row ? json_decode((string) $row['definition'], true) : null;
    if (is_array($def) && (int) ($def['version'] ?? 1) === 2) {
        foreach ($def['steps'] as &$st) {
            foreach ($st['fields'] as &$f) {
                if (($f['name'] ?? '') !== 'theme' || empty($f['options'])) continue;
                $f['img_shape'] = 'tall';
                foreach ($f['options'] as &$o) {
                    if (!empty($o['img']) && preg_match('#/sample-covers/([a-z]+)-small\.webp$#', (string) $o['img'], $m) && is_file(APP_ROOT . '/assets/covers/' . $m[1] . '.webp')) {
                        $o['img'] = '/assets/covers/' . $m[1] . '.webp';
                    }
                }
                unset($o);
            }
            unset($f);
        }
        unset($st);
        $def['version'] = 3;
        q('UPDATE forms SET definition = ?, updated_at = ? WHERE id = ?', [json_encode($def, JSON_UNESCAPED_UNICODE), now(), $row['id']]);
        app_log('Invitación digital: portadas completas en "Diseño de portada" (v3).');
    }
    @file_put_contents($marker, date('c'));
    upgrade_split_style();
}

/**
 * v4: el paso "El estilo" se divide en tres (diseño, colores, letras y detalles) para que los colores
 * tengan su propia pantalla y no se puedan saltear. Solo reacomoda esas preguntas; conserva el resto.
 */
function upgrade_split_style(): void
{
    $marker = STORAGE_DIR . '/.forms_v4';
    if (is_file($marker)) return;
    $row = q_one('SELECT * FROM forms WHERE slug = ?', ['invitacion-digital']);
    $def = $row ? json_decode((string) $row['definition'], true) : null;
    if (is_array($def) && (int) ($def['version'] ?? 1) === 3) {
        $steps = [];
        foreach ($def['steps'] as $st) {
            $names = array_column($st['fields'] ?? [], 'name');
            if (!in_array('theme', $names, true) || !in_array('look', $names, true)) { $steps[] = $st; continue; }
            $design = []; $colors = []; $rest = [];
            foreach ($st['fields'] as $f) {
                $n = $f['name'] ?? '';
                if ($n === 'look') {
                    unset($f['default']);
                    $f['required'] = true;
                    $f['label'] = '¿Qué colores querés para tu invitación?';
                    $f['help'] = 'Tocá una opción para seguir. Con “Prefiero elegir mis colores” elegís vos la letra, el acento y el fondo.';
                    $colors[] = $f;
                } elseif ($n === 'colors') { $colors[] = $f; }
                elseif ($n === 'theme' || ($f['type'] ?? '') === 'info') { $design[] = $f; }
                else { $rest[] = $f; }
            }
            $base = array_diff_key($st, ['fields' => 1, 'id' => 1, 'title' => 1, 'description' => 1]);
            $steps[] = $base + ['id' => 'diseno', 'title' => 'Diseño de portada', 'description' => 'Elegí la portada que más te guste. Si no sabés, dejá “Que lo elija Jema”.', 'fields' => $design];
            $steps[] = $base + ['id' => 'colores', 'title' => 'Los colores', 'description' => 'Elegí una combinación lista o armá la tuya con tus tres colores.', 'fields' => $colors];
            if ($rest) $steps[] = $base + ['id' => 'detalles', 'title' => 'Letras y detalles', 'description' => 'Todo es opcional: si no sabés, dejá “Que lo elija Jema” y nosotros lo elegimos por vos.', 'fields' => $rest];
        }
        $def['steps'] = $steps;
        $def['version'] = 4;
        q('UPDATE forms SET definition = ?, updated_at = ? WHERE id = ?', [json_encode($def, JSON_UNESCAPED_UNICODE), now(), $row['id']]);
        app_log('Invitación digital: los colores pasan a ser un paso propio y obligatorio (v4).');
    }
    @file_put_contents($marker, date('c'));
}
