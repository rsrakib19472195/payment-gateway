<?php
/**
 * Twixo payment callback
 *
 * Verifies a transaction ID against SMS-ingested payment_sms rows,
 * marks it used, and returns JSON. Hook your own ledger/credit logic
 * inside the success block below.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$result = json_decode($json, true);

if (!$result) {
    $result = [
        'transaction_id' => $_POST['transaction_id'] ?? '',
        'uid'            => $_POST['uid'] ?? '',
        'number'         => $_POST['number'] ?? '',
        'amount'         => $_POST['amount'] ?? '',
        'name'           => $_POST['name'] ?? '',
        'payment_method' => $_POST['payment_method'] ?? '',
        'status'         => $_POST['status'] ?? '',
        'gateway'        => $_POST['gateway'] ?? '',
    ];
}

if (empty($result['transaction_id']) || empty($result['uid']) || empty($result['amount'])) {
    http_response_code(400);
    echo json_encode([
        'status'  => false,
        'message' => 'Required fields missing: transaction_id, uid, and amount are required.',
    ]);
    exit;
}

if (strlen($result['transaction_id']) < 5) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid transaction ID format.']);
    exit;
}

$amount = (float) str_replace([','], '', (string) $result['amount']);
if ($amount <= 0 || $amount > 100000) {
    http_response_code(400);
    echo json_encode([
        'status'  => false,
        'message' => 'Invalid amount. Amount must be between 0 and 100,000 BDT.',
    ]);
    exit;
}

if (!is_numeric($result['uid']) || (float) $result['uid'] <= 0) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid user ID.']);
    exit;
}

if (!isset($result['status']) || (string) $result['status'] !== '1') {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Payment verification failed. Invalid status.']);
    exit;
}

$uid             = $result['uid'];
$cus_name        = $result['name'] ?? 'Unknown';
$transaction_id  = $result['transaction_id'];
$payment_method  = $result['gateway'] ?? $result['payment_method'] ?? 'twixo';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Database connection failed.']);
    exit;
}

try {
    $stmt = $conn->prepare('SELECT * FROM `payment_sms` WHERE `transaction_id` = ? LIMIT 1');
    $stmt->execute([$transaction_id]);
    $paymentSms = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paymentSms) {
        throw new Exception('Invalid Transaction ID');
    }

    if ((int) $paymentSms['status'] === 1) {
        throw new Exception('Transaction ID already used.');
    }

    if ((int) $paymentSms['amount'] !== (int) $amount) {
        throw new Exception('Invalid payment amount');
    }

    if (strtolower((string) $paymentSms['method']) !== strtolower((string) $payment_method)) {
        throw new Exception('Invalid payment method');
    }

    // Optional: reject if already recorded in your own ledger table
    // $stmt = $conn->prepare('SELECT id FROM your_transactions WHERE transaction_id = ?');
    // $stmt->execute([$transaction_id]);
    // if ($stmt->fetch()) {
    //     throw new Exception('Transaction ID already used.');
    // }

    $conn->beginTransaction();

    /*
     * === Merchant integration point ===
     * Credit the user / create an order here using $uid, $amount, $cus_name, etc.
     *
     * Example:
     *   $stmt = $conn->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
     *   $stmt->execute([$amount, $uid]);
     */

    $stmt = $conn->prepare(
        'INSERT INTO `payment_logs`
            (`uid`, `name`, `amount`, `method`, `transaction_id`, `ip_address`, `gateway`, `created_at`)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $uid,
        $cus_name,
        $amount,
        $payment_method,
        $transaction_id,
        get_client_ip(),
        'TWIXO',
    ]);

    $stmt = $conn->prepare('UPDATE `payment_sms` SET status = 1 WHERE `transaction_id` = ?');
    $stmt->execute([$transaction_id]);

    $conn->commit();

    echo json_encode(['status' => true, 'message' => 'Payment Successful.']);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    error_log('Twixo callback: ' . $e->getMessage());
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage() ?: 'Something went wrong!',
    ]);
}
