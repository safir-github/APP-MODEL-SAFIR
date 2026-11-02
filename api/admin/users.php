<?php
require_once __DIR__ . '/../bootstrap.php';

// Liste des comptes, réservée aux administrateurs (vérifiée côté serveur).
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') fail('Méthode non autorisée', 405);

$rows = db()->query('SELECT id, email, name, is_admin, created_at FROM users ORDER BY created_at DESC')->fetchAll();

json_out(['ok' => true, 'users' => $rows]);
