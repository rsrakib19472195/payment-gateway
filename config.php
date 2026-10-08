<?php
/**
 * Twixo Payment Gateway — public configuration
 * Copy values from config.example.php. Replace placeholders before going live.
 *
 * Free use requires keeping Twixo branding visible and intact.
 * Official site: https://twixo.sweez.xyz
 */

// Prefer local overrides (gitignored) when present
if (is_readable(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Debug mode
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

// Application settings
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Twixo');
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}
if (!defined('APP_TAGLINE')) {
    define('APP_TAGLINE', 'Free Payment Automation');
}

// Official Twixo branding — required; do not remove or blank out
if (!defined('TWIXO_BRAND')) {
    define('TWIXO_BRAND', 'Twixo');
}
if (!defined('TWIXO_URL')) {
    define('TWIXO_URL', 'https://twixo.sweez.xyz');
}
if (!defined('TWIXO_LOGO_URL')) {
    define('TWIXO_LOGO_URL', 'https://twixo.sweez.xyz/twixo.png');
}
if (!defined('TWIXO_CREDIT')) {
    define('TWIXO_CREDIT', 'Powered by Twixo');
}

// Enforce branding constants (blocks empty overrides from config.local.php)
if (TWIXO_BRAND !== 'Twixo' || TWIXO_URL !== 'https://twixo.sweez.xyz') {
    http_response_code(500);
    die('Twixo branding configuration is invalid. Restore official brand settings.');
}

// Callback URL — set to your installed callback.php
if (!defined('CALLBACK_API_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    define('CALLBACK_API_URL', $protocol . $host . $base . '/callback.php');
}

// Payment gateway numbers (set in config.local.php)
if (!defined('BKASH_NUMBER')) {
    define('BKASH_NUMBER', '01XXXXXXXXX');
}
if (!defined('NAGAD_NUMBER')) {
    define('NAGAD_NUMBER', '01XXXXXXXXX');
}
if (!defined('ROCKET_NUMBER')) {
    define('ROCKET_NUMBER', '01XXXXXXXXX');
}
if (!defined('UPAY_NUMBER')) {
    define('UPAY_NUMBER', '01XXXXXXXXX');
}

// SMS reader API credentials (set in config.local.php)
if (!defined('SMS_ACCESS_KEY')) {
    define('SMS_ACCESS_KEY', 'CHANGE_ME_ACCESS_KEY');
}
if (!defined('SMS_API_KEY')) {
    define('SMS_API_KEY', 'CHANGE_ME_API_KEY');
}

// Security
if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', 'CHANGE_ME_LONG_RANDOM_STRING');
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 3600);
}

date_default_timezone_set('Asia/Dhaka');

// Database (override via config.local.php)
if (!isset($servername)) {
    $servername = 'localhost';
}
if (!isset($username)) {
    $username = 'twixo_user';
}
if (!isset($password)) {
    $password = 'CHANGE_ME_DB_PASSWORD';
}
if (!isset($dbname)) {
    $dbname = 'twixo';
}

$conn = null;
try {
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Twixo DB connection failed: ' . $e->getMessage());
    $conn = null;
    // API endpoints that need DB will return an error; UI pages can still render.
}

function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    }
    if (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_FORWARDED_FOR'];
    }
    if (!empty($_SERVER['HTTP_FORWARDED'])) {
        return $_SERVER['HTTP_FORWARDED'];
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return 'UNKNOWN';
}

/**
 * Resolve payment wallet number for a method.
 */
function twixo_wallet_number(string $method): string {
    switch (strtolower($method)) {
        case 'bkash':
            return BKASH_NUMBER;
        case 'nagad':
            return NAGAD_NUMBER;
        case 'rocket':
            return ROCKET_NUMBER;
        case 'upay':
            return UPAY_NUMBER;
        default:
            return '';
    }
}
