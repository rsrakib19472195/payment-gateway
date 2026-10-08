<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . ltrim($path, '/');

header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
header('Access-Control-Allow-Headers: Content-Type, X-Access-Key, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($path === '/' || $path === '/health') {
    json_response([
        'success' => true,
        'service' => 'Twixo API Support Server',
        'status' => 'online',
        'time' => date('c'),
        'version' => '1.0.0'
    ]);
}

authenticate();

if ($path === '/api/transaction' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api/transaction.php';
    exit;
}

if ($path === '/api/transactions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require __DIR__ . '/api/transactions.php';
    exit;
}

if (preg_match('#^/api/transaction/([^/]+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $_GET['transaction_id'] = urldecode($m[1]);
    require __DIR__ . '/api/transaction_get.php';
    exit;
}

if ($path === '/api/stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require __DIR__ . '/api/stats.php';
    exit;
}

if ($path === '/api/balance' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require __DIR__ . '/api/balance.php';
    exit;
}

json_response([
    'success' => false,
    'error' => 'not_found',
    'message' => 'Endpoint not found'
], 404);
