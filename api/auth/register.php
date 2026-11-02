<?php
require __DIR__ . '/../bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail('Méthode non autorisée', 405);
}

$in = json_input();
$email = strtolower(trim((string)($in['email'] ?? '')));
$password = (string)($in['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    bad_request('Email invalide.');
}
if (strlen($password) < 6) {
    bad_request('Mot de passe : 6 caractères minimum.');
}

$check = db()->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    conflict('Un compte existe déjà avec cet email.');
}

$id = bin2hex(random_bytes(12));
$hash = password_hash($password, PASSWORD_DEFAULT);
db()->prepare('INSERT INTO users (id, email, password_hash) VALUES (?, ?, ?)')
    ->execute([$id, $email, $hash]);

$_SESSION['user_id'] = $id;
remember_user($id);
log_event($id, 'signup', '/register');

json_out(['ok' => true, 'user' => ['id' => $id, 'email' => $email]]);
