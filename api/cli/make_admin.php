<?php
// Attribue ou retire le rôle administrateur. À lancer UNIQUEMENT en ligne de commande :
//   php api/cli/make_admin.php adresse@exemple.fr            (rend administrateur)
//   php api/cli/make_admin.php adresse@exemple.fr --revoke   (retire le rôle)
// Refuse toute exécution par le web : personne ne peut se promouvoir depuis l'interface.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

define('APP_RUNNING', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../db.php';

$email = strtolower(trim((string)($argv[1] ?? '')));
$revoke = in_array('--revoke', array_slice($argv, 2), true);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage : php api/cli/make_admin.php adresse@exemple.fr [--revoke]\n");
    exit(1);
}

$stmt = db()->prepare('UPDATE users SET is_admin = ? WHERE email = ?');
$stmt->execute([$revoke ? 0 : 1, $email]);

if ($stmt->rowCount() === 0) {
    fwrite(STDERR, "Aucun compte pour cette adresse : inscrivez-vous d'abord dans l'application.\n");
    exit(1);
}

echo $revoke ? "Rôle administrateur retiré pour $email\n" : "$email est maintenant administrateur\n";
