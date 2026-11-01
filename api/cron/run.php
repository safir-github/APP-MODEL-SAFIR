<?php
// Point d'entrée des tâches planifiées. À lancer UNIQUEMENT en ligne de commande :
//   php /chemin/vers/api/cron/run.php
// Refuse toute exécution par le web (pas de secret dans une URL).
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

define('APP_RUNNING', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../db.php';
require __DIR__ . '/../helpers.php';
require __DIR__ . '/../lib/mail.php';
require __DIR__ . '/../lib/jobs.php';

$started = date('c');
$results = [
    'daily_reminders' => job_daily_reminders(),
];
echo json_encode(['started' => $started, 'results' => $results], JSON_UNESCAPED_UNICODE) . PHP_EOL;
