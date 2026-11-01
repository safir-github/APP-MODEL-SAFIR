<?php
require_once __DIR__ . '/../bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

$in = json_input();
$token = (string)($in['token'] ?? '');
if ($token === '') fail('Ce lien de confirmation est invalide ou a expiré.', 400);

$pdo = db();
$stmt = $pdo->prepare('SELECT user_id, new_email FROM email_changes WHERE token_hash = ? AND expires_at > ?');
$stmt->execute([hash('sha256', $token), time()]);
$change = $stmt->fetch();
if (!$change) fail('Ce lien de confirmation est invalide ou a expiré.', 400);

// L'adresse a pu être prise entre-temps
$check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
$check->execute([$change['new_email'], $change['user_id']]);
if ($check->fetch()) conflict('Cette adresse e-mail est déjà utilisée par un autre compte.');

$pdo->beginTransaction();
$pdo->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$change['new_email'], $change['user_id']]);
$pdo->prepare('DELETE FROM email_changes WHERE user_id = ?')->execute([$change['user_id']]);
$pdo->commit();

json_out(['ok' => true, 'email' => $change['new_email']]);
