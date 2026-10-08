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

$servername = 'localhost';
$username   = 'twixo_user';
$password   = 'your_strong_password';
$dbname     = 'twixo';

// Do NOT redefine TWIXO_BRAND / TWIXO_URL — required for free use.
