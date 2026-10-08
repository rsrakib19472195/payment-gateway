<?php
declare(strict_types=1);

[$data, $raw] = request_json();

$transactionId = (string) first_value($data, [
    'transaction_id', 'transactionId', 'trx_id', 'trxId', 'id'
], '');

$service = (string) first_value($data, [
    'service', 'service_name', 'serviceName', 'gateway'
], 'unknown');

$sender = (string) first_value($data, [
    'sender', 'sender_phone', 'senderPhone', 'phone'
], '');

$amountRaw = first_value($data, [
    'amount', 'total_amount', 'totalAmount'
], null);

$amount = is_numeric($amountRaw) ? (float)$amountRaw : null;

$smsBalanceRaw = first_value($data, [
    'sms_balance', 'smsBalance', 'balance', 'last_balance'
], null);

$smsBalance = is_numeric($smsBalanceRaw) ? (float)$smsBalanceRaw : null;

$rawSms = (string) first_value($data, [
    'raw_sms', 'rawSms', 'message', 'sms'
], '');

$reason = (string) first_value($data, [
    'verification_reason', 'verificationReason', 'reason'
], '');

$deviceId = (string) first_value($data, [
    'deviceId', 'device_id'
], '');

$timestampRaw = first_value($data, [
    'sms_timestamp', 'smsTimestamp', 'timestamp'
], null);

$smsTimestamp = is_numeric($timestampRaw) ? (int)$timestampRaw : null;

if ($transactionId === '') {
    json_response([
        'success' => false,
        'error' => 'transaction_id_required',
        'message' => 'transaction_id is required'
    ], 422);
}

$now = time();
$pdo = db();

$sql = "
INSERT INTO transactions
(transaction_id, service, sender, amount, sms_balance, raw_sms,
 verification_reason, device_id, sms_timestamp, payload_json,
 status, api_status, created_at, updated_at)
VALUES
(:transaction_id, :service, :sender, :amount, :sms_balance, :raw_sms,
 :reason, :device_id, :sms_timestamp, :payload_json,
 'received', 'received', :created_at, :updated_at)
ON CONFLICT(transaction_id, service) DO UPDATE SET
sender=excluded.sender,
amount=excluded.amount,
sms_balance=excluded.sms_balance,
raw_sms=excluded.raw_sms,
verification_reason=excluded.verification_reason,
device_id=excluded.device_id,
sms_timestamp=excluded.sms_timestamp,
payload_json=excluded.payload_json,
updated_at=excluded.updated_at
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':transaction_id' => $transactionId,
    ':service' => $service,
    ':sender' => $sender,
    ':amount' => $amount,
    ':sms_balance' => $smsBalance,
    ':raw_sms' => $rawSms,
    ':reason' => $reason,
    ':device_id' => $deviceId,
    ':sms_timestamp' => $smsTimestamp,
    ':payload_json' => $raw,
    ':created_at' => $now,
    ':updated_at' => $now,
]);

$stmt = $pdo->prepare("
    SELECT id, transaction_id, service, sender, amount, sms_balance,
           verification_reason, device_id, sms_timestamp,
           status, api_status, created_at, updated_at
    FROM transactions
    WHERE transaction_id = :transaction_id AND service = :service
    LIMIT 1
");
$stmt->execute([
    ':transaction_id' => $transactionId,
    ':service' => $service
]);

$row = $stmt->fetch();

log_api('Transaction received: ' . $transactionId . ' / ' . $service);

json_response([
    'success' => true,
    'message' => 'Transaction received successfully',
    'data' => $row
]);
