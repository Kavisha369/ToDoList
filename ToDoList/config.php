<?php
// ============================================================
//  config.php  —  Database connection & global settings
// ============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'todolist_db');
define('DB_USER', 'root');      // XAMPP default
define('DB_PASS', '');          // XAMPP default (no password)
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',    'TaskFlow');
define('ADMIN_USER',  'admin');
define('SESSION_KEY', 'tf_user');

// ── Start session if not already started ──────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── PDO Connection ────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error rather than showing it
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ── Auth helpers ─────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION[SESSION_KEY]);
}

function currentUser(): ?array {
    return $_SESSION[SESSION_KEY] ?? null;
}

function isAdmin(): bool {
    return (currentUser()['role'] ?? '') === 'admin';
}

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireAdmin(string $redirect = 'dashboard.php'): void {
    requireLogin();
    if (!isAdmin()) {
        header("Location: $redirect");
        exit;
    }
}

// ── CSRF helpers ──────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ── Flash messages ────────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
