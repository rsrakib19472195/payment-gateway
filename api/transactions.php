<?php
declare(strict_types=1);

$limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
$service = trim((string)($_GET['service'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$where = [];
$params = [];

if ($service !== '') {
    $where[] = 'service = :service';
    $params[':service'] = $service;
}

if ($status !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}

$sql = "
SELECT id, transaction_id, service, sender, amount, sms_balance,
       verification_reason, device_id, sms_timestamp,
       status, api_status, created_at, updated_at
FROM transactions
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY created_at DESC LIMIT ' . $limit;

$stmt = db()->prepare($sql);
$stmt->execute($params);

json_response([
    'success' => true,
    'count' => $stmt->rowCount(),
    'data' => $stmt->fetchAll()
]);
