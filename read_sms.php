<?php
/**
 * Twixo SMS ingest endpoint
 * Receives parsed payment SMS from the Twixo mobile app.
 */
require_once __DIR__ . '/config.php';

$valid_access_key = SMS_ACCESS_KEY;
$valid_api_key    = SMS_API_KEY;

function respond($status, $message, $data = null) {
    $response = [
        'status'  => $status,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

function extract_last_balance($transaction_data, $raw_sms) {
    $possibleKeys = ['lastBalance', 'last_balance', 'accountBalance', 'balance'];

    foreach ($possibleKeys as $key) {
        if (isset($transaction_data[$key]) && $transaction_data[$key] !== '') {
            $clean = str_replace([',', 'Tk', 'TK', ' '], '', (string) $transaction_data[$key]);
            if (is_numeric($clean)) {
                return (float) $clean;
            }
        }
    }

    if (!empty($raw_sms)) {
        $sms = preg_replace('/\s+/', ' ', $raw_sms);

        $patterns = [
            '/(?:Your\s*)?A\/C\s*Balance\s*:?\s*(?:Tk\.?\s*)?([0-9,]+(?:\.[0-9]+)?)/i',
            '/Account\s*Balance\s*:?\s*(?:Tk\.?\s*)?([0-9,]+(?:\.[0-9]+)?)/i',
            '/(?:Current|Available)\s*Balance\s*:?\s*(?:Tk\.?\s*)?([0-9,]+(?:\.[0-9]+)?)/i',
            '/Balance\s*:?\s*(?:Tk\.?\s*)?([0-9,]+(?:\.[0-9]+)?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sms, $matches)) {
                $clean = str_replace(',', '', $matches[1]);
                if (is_numeric($clean)) {
                    return (float) $clean;
                }
            }
        }
    }

    return 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'No data posted');
}

$jsonData = file_get_contents('php://input');
$data     = json_decode($jsonData, true);

if (DEBUG_MODE) {
    $logFile = __DIR__ . '/sms_log.txt';
    file_put_contents($logFile, json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
}

if ($data === null) {
    respond('error', 'Invalid JSON data');
}

if (!isset($data['access_key']) || !isset($data['api_key'])) {
    respond('error', 'Missing API credentials');
}

if ($data['access_key'] !== $valid_access_key || $data['api_key'] !== $valid_api_key) {
    respond('error', 'Invalid API credentials');
}

$transaction_data = $data['transaction_data'] ?? null;
$raw_sms          = $data['raw_sms'] ?? null;

if (!$transaction_data) {
    respond('error', 'Missing transaction data');
}

try {
    $checkStmt = $conn->prepare('SELECT COUNT(*) FROM payment_sms WHERE transaction_id = :transaction_id');
    $checkStmt->bindParam(':transaction_id', $transaction_data['transactionId']);
    $checkStmt->execute();

    if ($checkStmt->fetchColumn() > 0) {
        respond('error', 'Duplicate transaction ID — already exists.');
    }

    $stmt = $conn->prepare(
        'INSERT INTO payment_sms
            (transaction_id, track_id, sender_number, amount, method, fee, status, raw_sms, last_balance)
         VALUES
            (:transaction_id, :track_id, :sender_number, :amount, :method, :fee, :status, :raw_sms, :last_balance)'
    );

    $stmt->bindParam(':transaction_id', $transaction_data['transactionId']);
    $stmt->bindParam(':track_id', $transaction_data['id']);

    $sender_number = $transaction_data['senderPhone'] ?? '';

    if (
        empty($sender_number) &&
        isset($transaction_data['service']) &&
        strtolower($transaction_data['service']) === 'nagad' &&
        !empty($raw_sms)
    ) {
        if (preg_match('/Uddokta:\s*(01\d{9})/i', $raw_sms, $matches)) {
            $sender_number = $matches[1];
        }
    }

    $stmt->bindParam(':sender_number', $sender_number);
    $stmt->bindParam(':amount', $transaction_data['amount']);
    $stmt->bindParam(':method', $transaction_data['service']);
    $stmt->bindParam(':fee', $transaction_data['fee']);

    $status = 0;
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':raw_sms', $raw_sms);

    $last_balance = extract_last_balance($transaction_data, $raw_sms);
    $stmt->bindParam(':last_balance', $last_balance);

    $stmt->execute();

    respond('success', 'Transaction data saved successfully', ['id' => $conn->lastInsertId()]);
} catch (PDOException $e) {
    error_log('Twixo SMS DB error: ' . $e->getMessage());
    respond('error', 'Database error occurred');
}
