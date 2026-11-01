<?php
require __DIR__ . '/../bootstrap.php';

// Renvoie l'utilisateur connecté, ou { ok: false, user: null } (HTTP 200) si personne n'est connecté.
$user = current_user();
if (!$user) {
    json_out(['ok' => false, 'user' => null], 200);
}

json_out(['ok' => true, 'user' => $user]);
