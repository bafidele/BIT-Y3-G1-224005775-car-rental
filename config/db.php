<?php
declare(strict_types=1);
// Load a local .env file without adding a framework or third-party dependency.
$envFile = dirname(__DIR__) . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== '' && getenv($key) === false) {
            $value = trim($value, "\"'");
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Kigali');

if (session_status() === PHP_SESSION_NONE) {
    session_name('CARRENTALSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'car_rental_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Car rental database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('The application could not connect to its database.');
}

function isStaffAccount(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true) && !empty($_SESSION['user_id']);
}

function isCustomerAccount(): bool {
    return ($_SESSION['role'] ?? '') === 'customer' && !empty($_SESSION['customer_id']);
}

function isAdminAccount(): bool {
    return ($_SESSION['role'] ?? '') === 'admin' && !empty($_SESSION['user_id']);
}

function isAuthenticated(): bool {
    return isStaffAccount() || isCustomerAccount();
}

function checkAuth(): void {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
    if (!empty($_SESSION['must_change_password']) && basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) !== 'change_password.php') {
        header('Location: change_password.php');
        exit;
    }
}

function requireStaff(): void {
    checkAuth();
    if (!isStaffAccount()) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

function requireAdmin(): void {
    checkAuth();
    if (!isAdminAccount()) {
        http_response_code(403);
        exit('Administrator access is required for this page.');
    }
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
