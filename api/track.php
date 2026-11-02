<?php
require_once __DIR__ . '/bootstrap.php';

// Pages vues. N'enregistre RIEN sans consentement : le cookie mc_consent=1 est posé par la bannière
// quand le visiteur accepte. Sans ce cookie, la requête est ignorée (204).
// Aucune adresse IP, aucun identifiant de visiteur : seulement la page (chemin) et la classe d'appareil.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Méthode non autorisée', 405);

if (($_COOKIE['mc_consent'] ?? '') !== '1') {
    http_response_code(204);
    exit;
}

$in = json_input();
$path = (string)($in['path'] ?? '');
// Chemin seul, commençant par « / » : pas d'URL complète, pas de paramètres
if ($path === '' || $path[0] !== '/' || strlen($path) > 200 || str_contains($path, '?') || str_contains($path, '@')) {
    bad_request('Chemin invalide.');
}

$user = current_user();
log_event($user ? $user['id'] : null, 'page_view', $path);

http_response_code(204);
