<?php
declare(strict_types=1);

$id = trim((string)($_GET['transaction_id'] ?? ''));

if ($id === '') {
    json_response(['success'=>false,'error'=>'transaction_id_required'], 422);
}

$stmt = db()->prepare("
SELECT id, transaction_id, service, sender, amount, sms_balance,
       raw_sms, verification_reason, device_id, sms_timestamp,
       payload_json, status, api_status, created_at, updated_at
FROM transactions
WHERE transaction_id = :id
ORDER BY id DESC
LIMIT 1
");
$stmt->execute([':id' => $id]);

$row = $stmt->fetch();

if (!$row) {
    json_response([
        'success' => false,
        'error' => 'transaction_not_found'
    ], 404);
}

$row['payload'] = json_decode($row['payload_json'], true);
unset($row['payload_json']);

json_response([
    'success' => true,
    'data' => $row
]);
