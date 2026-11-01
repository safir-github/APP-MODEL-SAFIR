<?php
require __DIR__ . '/../bootstrap.php';

$user = current_user();
if (!$user) {
    json_out(['ok' => false, 'user' => null], 200);
}

// Récupérer les métriques du compte
$txCountStmt = db()->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = ?');
$txCountStmt->execute([$user['id']]);
$totalTransactions = (int)$txCountStmt->fetchColumn();

$user['total_transactions'] = $totalTransactions;

json_out(['ok' => true, 'user' => $user]);
