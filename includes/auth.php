<?php
// ── Session & Authentication helpers ─────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    // Security headers
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/** Redirect to login if not authenticated */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/** Redirect to dashboard if already authenticated */
function requireGuest(): void {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'       => (int)$_SESSION['user_id'],
        'name'     => $_SESSION['user_name'],
        'email'    => $_SESSION['user_email'],
        'username' => $_SESSION['user_username'],
    ];
}

function setUserSession(array $user): void {
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_username'] = $user['username'];
    session_regenerate_id(true);
}

// ── CSRF helpers ──────────────────────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ── Output sanitisation ───────────────────────────────────────────────────────

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
