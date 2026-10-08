<?php
declare(strict_types=1);

$pdo = db();

$total = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$received = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status='received'")->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM transactions WHERE created_at >= :start"
);
$stmt->execute([':start' => strtotime('today')]);
$today = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
SELECT service, COUNT(*) AS count, COALESCE(SUM(amount),0) AS total_amount
FROM transactions
GROUP BY service
ORDER BY count DESC
");
$stmt->execute();

json_response([
    'success' => true,
    'data' => [
        'total_transactions' => $total,
        'received_transactions' => $received,
        'today_transactions' => $today,
        'by_service' => $stmt->fetchAll()
    ]
]);
