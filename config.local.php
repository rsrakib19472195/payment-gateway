<?php
/**
 * Copy to config.local.php and fill in your secrets.
 * config.local.php is gitignored — never commit real credentials.
 */

define('DEBUG_MODE', false);

// Your wallet / personal numbers
define('BKASH_NUMBER', '01XXXXXXXXX');
define('NAGAD_NUMBER', '01XXXXXXXXX');
define('ROCKET_NUMBER', '01XXXXXXXXX');
define('UPAY_NUMBER', '01XXXXXXXXX');

// Must match the keys configured in the Twixo mobile app
define('SMS_ACCESS_KEY', 'generate-a-long-random-access-key');
define('SMS_API_KEY', 'generate-a-long-random-api-key');

define('ENCRYPTION_KEY', 'generate-another-long-random-string');

// Absolute URL to this install's callback.php
define('CALLBACK_API_URL', 'https://yourdomain.com/pay/callback.php');

$servername = getenv('DB_HOST') ?: 'localhost';
$port       = getenv('DB_PORT') ?: '3306';
$username   = getenv('DB_USER') ?: 'twixo_user';
$password   = getenv('DB_PASSWORD') ?: '';
$dbname     = getenv('DB_NAME') ?: 'twixo';

// Do NOT redefine TWIXO_BRAND / TWIXO_URL — required for free use.
$sslMode = getenv('DB_SSL') ?: 'REQUIRED';

$dsn = "mysql:host={$servername};port={$port};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

if (strtoupper($sslMode) === 'REQUIRED') {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}
