<?php
require __DIR__ . '/../bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('Méthode non autorisée', 405);
}

$in = json_input();
$email = strtolower(trim((string)($in['email'] ?? '')));
$password = (string)($in['password'] ?? '');

$stmt = db()->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    unauthorized('Email ou mot de passe incorrect.');
}

$_SESSION['user_id'] = $user['id'];
remember_user($user['id']);

json_out(['ok' => true, 'user' => ['id' => $user['id'], 'email' => $user['email']]]);
