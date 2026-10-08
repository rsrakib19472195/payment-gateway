<?php
/**
 * Twixo — copy this file to config.php and fill in your values.
 * Do not commit config.php with real credentials.
 *
 * Branding: APP_NAME / TWIXO_* constants are required. Removing Twixo
 * attribution violates the Twixo free-use license.
 */

// Debug mode (set false in production)
define('DEBUG_MODE', false);

// Application settings
define('APP_NAME', 'Twixo');
define('APP_VERSION', '1.0.0');
define('APP_TAGLINE', 'Free Payment Automation');

// Official Twixo branding (do not change — required for free use)
define('TWIXO_BRAND', 'Twixo');
define('TWIXO_URL', 'https://twixo.sweez.xyz');
define('TWIXO_LOGO_URL', 'https://twixo.sweez.xyz/twixo.png');
define('TWIXO_CREDIT', 'Powered by Twixo');

// Callback URL — usually your own callback.php on this install
// Example: https://yourdomain.com/pay/callback.php
define('CALLBACK_API_URL', 'https://yourdomain.com/pay/callback.php');

// Payment gateway numbers (your personal/merchant wallet numbers)
define('BKASH_NUMBER', '01XXXXXXXXX');
define('NAGAD_NUMBER', '01XXXXXXXXX');
define('ROCKET_NUMBER', '01XXXXXXXXX');
define('UPAY_NUMBER', '01XXXXXXXXX');

// SMS reader API credentials (change these — used by the Twixo app)
define('SMS_ACCESS_KEY', 'CHANGE_ME_ACCESS_KEY');
define('SMS_API_KEY', 'CHANGE_ME_API_KEY');

// Security
define('ENCRYPTION_KEY', 'CHANGE_ME_LONG_RANDOM_STRING');
define('SESSION_LIFETIME', 3600);

date_default_timezone_set('Asia/Dhaka');

// Database
$servername = 'localhost';
$username   = 'your_db_user';
$password   = 'your_db_password';
$dbname     = 'your_db_name';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Connection failed: ' . $e->getMessage());
    die('Database connection error.');
}

function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED'])) return $_SERVER['HTTP_X_FORWARDED'];
    if (!empty($_SERVER['HTTP_FORWARDED_FOR'])) return $_SERVER['HTTP_FORWARDED_FOR'];
    if (!empty($_SERVER['HTTP_FORWARDED'])) return $_SERVER['HTTP_FORWARDED'];
    if (!empty($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
    return 'UNKNOWN';
}
