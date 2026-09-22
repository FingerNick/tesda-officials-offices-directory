<?php
declare(strict_types=1);

session_start();
$config = require __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) return $pdo;
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string {
    global $config;
    $base = $config['app_url'];
    if ($base === '') {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        // Admin pages live one directory below the application root.
        $base = basename($scriptDir) === 'admin' ? dirname($scriptDir) : $scriptDir;
        $base = rtrim($base, '/.');
    }
    return $base . '/' . ltrim($path, '/');
}
function redirect(string $path = ''): never { header('Location: ' . url($path)); exit; }
function photo_url(string $path): string { return url(implode('/', array_map('rawurlencode', explode('/', $path)))); }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_token'] ?? '')) {
        http_response_code(419); exit('Your session expired. Please go back and try again.');
    }
}
function flash(string $type, string $message): void { $_SESSION['flash'] = compact('type', 'message'); }
function take_flash(): ?array { $v = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $v; }
function is_admin(): bool { return isset($_SESSION['admin_id']); }
function require_admin(): void { if (!is_admin()) redirect('admin/login.php'); }
