<?php
require_once __DIR__ . '/../bootstrap.php';

// Journal des accès administrateur (qui a consulté quoi, quand)
$admin = require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') fail('Méthode non autorisée', 405);

$stmt = db()->query('SELECT l.action, l.target, l.created_at, u.email AS admin_email
    FROM admin_log l LEFT JOIN users u ON u.id = l.admin_id ORDER BY l.created_at DESC LIMIT 100');
json_out(['ok' => true, 'entries' => $stmt->fetchAll()]);
