<?php
require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/rate_limit.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('Méthode non autorisée', 405);
}

// Anti-force brute : 20 tentatives par 15 minutes et par adresse IP
if (rate_limited('login:' . client_ip(), 20)) {
    fail('Trop de tentatives. Réessayez dans quelques minutes.', 429);
}

$in = json_input();
$email = strtolower(trim((string)($in['email'] ?? '')));
$password = (string)($in['password'] ?? '');

$stmt = db()->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    record_attempt('login:' . client_ip());
    unauthorized('Email ou mot de passe incorrect.');
}

$_SESSION['user_id'] = $user['id'];
remember_user($user['id']);
db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([time(), $user['id']]);
log_event($user['id'], 'login', '/login');

json_out(['ok' => true, 'user' => ['id' => $user['id'], 'email' => $user['email']]]);
