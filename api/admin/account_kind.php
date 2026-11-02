<?php
require_once __DIR__ . '/../bootstrap.php';

// Changement de catégorie d'un compte. Tracé dans admin_log.
$admin = require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

const ACCOUNT_KINDS = ['reel', 'test', 'agent', 'interne'];

$in = json_input();
$userId = (string)($in['user_id'] ?? '');
$kind = (string)($in['kind'] ?? '');
if ($userId === '') bad_request('Compte manquant.');
if (!in_array($kind, ACCOUNT_KINDS, true)) bad_request('Catégorie inconnue.');

$stmt = db()->prepare('UPDATE users SET account_kind = ? WHERE id = ?');
$stmt->execute([$kind, $userId]);
if ($stmt->rowCount() === 0) fail('Compte introuvable.', 404);

log_admin($admin['id'], 'set_account_kind', $userId . ' => ' . $kind);
json_out(['ok' => true, 'kind' => $kind]);
