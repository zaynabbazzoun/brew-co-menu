<?php
// Shared session and login helpers for the admin area.

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('brew_co_admin');
    session_set_cookie_params([
        'lifetime' => 0, // Ends when the browser session ends.
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function adminIsLoggedIn(): bool
{
    startAdminSession();
    return isset($_SESSION['admin_id']) && (int) $_SESSION['admin_id'] > 0;
}

function requireAdmin(): void
{
    startAdminSession();
    header('Cache-Control: no-store, private');

    if (!adminIsLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    // Automatically sign out after 30 minutes of inactivity.
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
    if ($lastActivity === 0 || time() - $lastActivity > 1800) {
        endAdminSession();
        header('Location: login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function csrfToken(): string
{
    startAdminSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validCsrfToken(string $token): bool
{
    startAdminSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function endAdminSession(): void
{
    startAdminSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }
    session_destroy();
}
