<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/rate_limit.php';
require_once __DIR__ . '/../lib/mail.php';

$user = require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

if (rate_limited('email:' . client_ip(), 20)) {
    fail('Trop de tentatives. Réessayez dans quelques minutes.', 429);
}

// Mot de passe demandé : une session volée ne suffit pas à changer l'adresse de récupération
$in = json_input();
$password = (string)($in['password'] ?? '');
$newEmail = strtolower(trim((string)($in['email'] ?? '')));

$stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();
if (!$row || !password_verify($password, $row['password_hash'])) {
    record_attempt('email:' . client_ip());
    fail('Mot de passe incorrect.', 403);
}

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) bad_request('Adresse e-mail invalide.');
if ($newEmail === strtolower($user['email'])) bad_request('Cette adresse est déjà la vôtre.');

$check = db()->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$newEmail]);
if ($check->fetch()) conflict('Cette adresse e-mail est déjà utilisée par un autre compte.');

// Une seule demande en attente par personne : la nouvelle remplace l'ancienne
$pdo = db();
$pdo->prepare('DELETE FROM email_changes WHERE user_id = ?')->execute([$user['id']]);

// Le lien part vers la NOUVELLE adresse : seul quelqu'un qui la contrôle peut le confirmer
$token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
$pdo->prepare('INSERT INTO email_changes (token_hash, user_id, new_email, expires_at) VALUES (?, ?, ?, ?)')
    ->execute([hash('sha256', $token), $user['id'], $newEmail, time() + 3600]);

$link = APP_URL . '/confirmer-email?token=' . $token;
$text = "Bonjour,\n\nUne demande de changement d'adresse e-mail a été faite pour votre compte.\n"
      . "Pour confirmer cette nouvelle adresse, ouvrez ce lien (valable 1 heure) :\n" . $link
      . "\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.";
send_mail($newEmail, 'Confirmez votre nouvelle adresse e-mail', $text);

// Réponse identique que l'envoi ait réussi ou non, pour ne pas dépendre de la délivrabilité
json_out(['ok' => true, 'message' => 'Un e-mail de confirmation vient d’être envoyé à la nouvelle adresse.']);
