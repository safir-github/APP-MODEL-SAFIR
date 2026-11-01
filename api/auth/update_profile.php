<?php
require_once __DIR__ . '/../bootstrap.php';

$user = require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

// Le nom n'est pas sensible : pas de mot de passe demandé. L'e-mail passe par auth/request_email_change.php.
$in = json_input();
$name = trim((string)($in['name'] ?? ''));
if (mb_strlen($name) > 100) bad_request('Le nom ne doit pas dépasser 100 caractères.');

db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $user['id']]);

json_out(['ok' => true, 'user' => ['id' => $user['id'], 'email' => $user['email'], 'name' => $name]]);
