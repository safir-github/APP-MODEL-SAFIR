<?php
require __DIR__ . '/../bootstrap.php';

$logged = current_user();
if ($logged) log_event($logged['id'], 'logout', '/logout');
forget_remember();

// Vide la session et détruit le cookie.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
}
session_destroy();

json_out(['ok' => true]);
