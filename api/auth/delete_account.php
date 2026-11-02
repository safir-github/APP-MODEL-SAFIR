<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/rate_limit.php';

$user = require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

if (rate_limited('delete:' . client_ip(), 20)) {
    fail('Trop de tentatives. Réessayez dans quelques minutes.', 429);
}

// Confirmation par le mot de passe : une session volée ne suffit pas à supprimer le compte
$in = json_input();
$password = (string)($in['password'] ?? '');

$stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();
if (!$row || !password_verify($password, $row['password_hash'])) {
    record_attempt('delete:' . client_ip());
    fail('Mot de passe incorrect.', 403);
}

// Suppression définitive. Le journal d'événements (events) est aussi effacé : ce sont des données personnelles.
// Les tables liées à users.id avec ON DELETE CASCADE partent seules ;
// les autres (jetons, journal des e-mails) sont supprimées ici. Une table ajoutée par un projet
// sans cascade doit être ajoutée à cette liste (voir docs/AUTH.md).
$pdo = db();
$pdo->beginTransaction();
$pdo->prepare('DELETE FROM auth_tokens WHERE user_id = ?')->execute([$user['id']]);
$pdo->prepare('DELETE FROM notifications_log WHERE user_id = ?')->execute([$user['id']]);
$pdo->prepare('DELETE FROM events WHERE user_id = ?')->execute([$user['id']]);
$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
$pdo->commit();

forget_remember();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
}
session_destroy();

json_out(['ok' => true]);
