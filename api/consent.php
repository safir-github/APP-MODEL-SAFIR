<?php
require_once __DIR__ . '/bootstrap.php';

// Enregistre le choix du visiteur (accepter ou refuser la mesure d'audience).
// Le cookie mc_consent ne sert qu'à retenir ce choix : il ne suit personne.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

$in = json_input();
$accept = ($in['accept'] ?? false) === true;
$expires = time() + 60 * 60 * 24 * 180;
setcookie('mc_consent', $accept ? '1' : '0', [
    'expires' => $expires,
    'path' => '/',
    'httponly' => false,
    'samesite' => 'Lax',
    'secure' => is_https_request(),
]);

json_out(['ok' => true, 'accept' => $accept]);
