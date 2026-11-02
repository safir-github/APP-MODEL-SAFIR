<?php
require_once __DIR__ . '/../bootstrap.php';

// Liste des comptes, réservée aux administrateurs (vérifiée côté serveur).
// ?q= recherche par e-mail ou nom. ?reel=1 ne garde que les comptes de catégorie « reel ».
$admin = require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') fail('Méthode non autorisée', 405);

$q = trim((string)($_GET['q'] ?? ''));
$reelOnly = (string)($_GET['reel'] ?? '') === '1';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(email LIKE ? OR name LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($reelOnly) {
    $where[] = "account_kind = 'reel'";
}
$sql = 'SELECT id, email, name, is_admin, account_kind, created_at, last_login_at FROM users'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);

log_admin($admin['id'], 'view_users', $q);
json_out(['ok' => true, 'users' => $stmt->fetchAll()]);
