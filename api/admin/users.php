<?php
require_once __DIR__ . '/../bootstrap.php';

// Liste des comptes, réservée aux administrateurs (vérifiée côté serveur). Recherche par e-mail ou nom : ?q=
$admin = require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') fail('Méthode non autorisée', 405);

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT id, email, name, is_admin, created_at, last_login_at FROM users';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE email LIKE ? OR name LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);

log_admin($admin['id'], 'view_users', $q);
json_out(['ok' => true, 'users' => $stmt->fetchAll()]);
