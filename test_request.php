<?php
// CLI test helper. Run:
// php test_request.php https://your-domain.example YOUR_API_KEY

if ($argc < 3) {
    exit("Usage: php test_request.php BASE_URL API_KEY\n");
}

$base = rtrim($argv[1], '/');
$key = $argv[2];

$payload = [
    'transaction_id' => 'TEST-' . date('YmdHis'),
    'service' => 'test',
    'sender' => '01700000000',
    'amount' => 100,
    'sms_balance' => 900,
    'raw_sms' => 'Test transaction: received Tk 100',
    'verification_reason' => 'test',
    'deviceId' => 'test-device',
    'sms_timestamp' => time()
];

$ch = curl_init($base . '/api/transaction');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Access-Key: ' . $key
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($ch);
if ($response === false) {
    fwrite(STDERR, curl_error($ch) . PHP_EOL);
    exit(1);
}

echo $response . PHP_EOL;
curl_close($ch);
