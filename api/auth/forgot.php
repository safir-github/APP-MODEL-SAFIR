<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/rate_limit.php';
require_once __DIR__ . '/../lib/mail.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

if (rate_limited('forgot:' . client_ip(), 20)) {
    fail('Trop de tentatives. Réessayez dans quelques minutes.', 429);
}
record_attempt('forgot:' . client_ip());

$in = json_input();
$email = strtolower(trim((string)($in['email'] ?? '')));

// Même réponse, que le compte existe ou non (ne révèle pas les adresses enregistrées)
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = db()->prepare('SELECT id, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($user = $stmt->fetch()) {
        // Jeton à usage unique : seule son empreinte est stockée
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        db()->prepare('INSERT INTO password_resets (id, user_id, expires_at) VALUES (?, ?, ?)')
            ->execute([hash('sha256', $token), $user['id'], time() + 3600]);

        $link = APP_URL . '/reinitialiser?token=' . $token;
        $text = "Bonjour,\n\nUne demande de réinitialisation de mot de passe a été faite pour votre compte.\n"
              . "Pour choisir un nouveau mot de passe, ouvrez ce lien (valable 1 heure) :\n" . $link
              . "\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.";
        send_mail($user['email'], 'Réinitialisation de votre mot de passe', $text);
    }
}

json_out(['ok' => true, 'message' => 'Si un compte existe pour cette adresse, un e-mail vient d’être envoyé.']);
