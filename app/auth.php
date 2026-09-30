<?php
declare(strict_types=1);

function current_user(): ?array
{
    start_session();
    $id = $_SESSION['uid'] ?? null;
    if (!$id) return null;
    static $cache = [];
    if (!isset($cache[$id])) $cache[$id] = q_one('SELECT id, email, name FROM users WHERE id = ?', [$id]);
    return $cache[$id];
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        start_session();
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '/admin';
        redirect('admin/login');
    }
    return $u;
}

function login_blocked(): bool
{
    q('DELETE FROM login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 3600)]);
    $n = (int) q_val('SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND created_at > ?',
        [ip_hash(), date('Y-m-d H:i:s', time() - 900)]);
    return $n >= 8;
}

function attempt_login(string $email, string $pass): bool
{
    $u = q_one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    // password_verify siempre corre (aunque no exista el usuario) para no dar pistas por tiempo
    $hash = $u['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuJ4S1cQeZ0G9m8v2nQ4mQ8XxYw3n5Kq6';
    $ok = password_verify($pass, $hash) && $u;
    if (!$ok) {
        db_insert('login_attempts', ['ip_hash' => ip_hash(), 'created_at' => now()]);
        return false;
    }
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    q('UPDATE users SET last_login = ? WHERE id = ?', [now(), $u['id']]);
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
    }
    return true;
}

function logout(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function password_problem(string $pass): ?string
{
    if (mb_strlen($pass) < 10) return 'La contraseña debe tener al menos 10 caracteres.';
    return null;
}
