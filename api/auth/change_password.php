<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/rate_limit.php';

$user = require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

// Même limite que la connexion : le mot de passe actuel ne doit pas pouvoir être testé sans fin
if (rate_limited('password:' . client_ip(), 20)) {
    fail('Trop de tentatives. Réessayez dans quelques minutes.', 429);
}

$in = json_input();
$current = (string)($in['current_password'] ?? '');
$new = (string)($in['new_password'] ?? '');

$stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();
if (!$row || !password_verify($current, $row['password_hash'])) {
    record_attempt('password:' . client_ip());
    fail('Mot de passe actuel incorrect.', 403);
}
if (strlen($new) < 8 || strlen($new) > 200) {
    bad_request('Le nouveau mot de passe doit contenir entre 8 et 200 caractères.');
}

$pdo = db();
$pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
    ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);

// Les autres appareils sont déconnectés : on supprime leurs jetons « Rester connecté ».
// Le jeton de l'appareil courant est conservé.
$keep = hash('sha256', $_COOKIE[REMEMBER_COOKIE] ?? '');
$pdo->prepare('DELETE FROM auth_tokens WHERE user_id = ? AND token_hash != ?')
    ->execute([$user['id'], $keep]);

json_out(['ok' => true]);
