<?php
require_once __DIR__ . '/../bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

$in = json_input();
$token = (string)($in['token'] ?? '');
$password = (string)($in['password'] ?? '');
if ($token === '') fail('Ce lien de réinitialisation est invalide ou a expiré.', 400);
if (strlen($password) < 8 || strlen($password) > 200) fail('Le mot de passe doit contenir au moins 8 caractères.');

$pdo = db();
$stmt = $pdo->prepare('SELECT id, user_id FROM password_resets WHERE id = ? AND used_at IS NULL AND expires_at > ?');
$stmt->execute([hash('sha256', $token), time()]);
$reset = $stmt->fetch();
if (!$reset) fail('Ce lien de réinitialisation est invalide ou a expiré.', 400);

$pdo->beginTransaction();
$pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
    ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
$pdo->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?')->execute([time(), $reset['id']]);
$pdo->commit();

json_out(['ok' => true]);
