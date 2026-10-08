<?php
declare(strict_types=1);

/*
 * This endpoint reports the latest SMS balance seen from the app.
 * It does not invent a balance when none has been received.
 */

$service = trim((string)($_GET['service'] ?? ''));

$sql = "
SELECT service, sms_balance, sender, transaction_id, sms_timestamp, created_at
FROM transactions
WHERE sms_balance IS NOT NULL
";

$params = [];

if ($service !== '') {
    $sql .= " AND service = :service";
    $params[':service'] = $service;
}

$sql .= " ORDER BY created_at DESC LIMIT 1";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch();

if (!$row) {
    json_response([
        'success' => true,
        'data' => [
            'balance' => null,
            'message' => 'No balance received yet'
        ]
    ]);
}

json_response([
    'success' => true,
    'data' => [
        'balance' => (float)$row['sms_balance'],
        'service' => $row['service'],
        'transaction_id' => $row['transaction_id'],
        'updated_at' => $row['created_at']
    ]
]);
