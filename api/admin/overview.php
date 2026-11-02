<?php
require_once __DIR__ . '/../bootstrap.php';

// Vue d'ensemble : chiffres agrégés. Aucune donnée individuelle ici.
$admin = require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') fail('Méthode non autorisée', 405);

$pdo = db();
$now = time();
$count = function (string $sql, array $params = []) use ($pdo): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
};

$days14 = $pdo->prepare("SELECT date(created_at, 'unixepoch') AS day,
        SUM(type = 'page_view') AS visits, SUM(type = 'signup') AS signups
    FROM events WHERE created_at >= ? GROUP BY day ORDER BY day");
$days14->execute([$now - 14 * 86400]);

$devices = $pdo->prepare("SELECT device, COUNT(*) AS n FROM events WHERE type = 'page_view' AND created_at >= ? GROUP BY device");
$devices->execute([$now - 30 * 86400]);

$paths = $pdo->prepare("SELECT path, COUNT(*) AS n FROM events WHERE type = 'page_view' AND created_at >= ? GROUP BY path ORDER BY n DESC LIMIT 10");
$paths->execute([$now - 30 * 86400]);

log_admin($admin['id'], 'view_overview');
json_out([
    'ok' => true,
    'accounts' => $count('SELECT COUNT(*) FROM users'),
    'signups' => [
        '7' => $count("SELECT COUNT(*) FROM events WHERE type = 'signup' AND created_at >= ?", [$now - 7 * 86400]),
        '30' => $count("SELECT COUNT(*) FROM events WHERE type = 'signup' AND created_at >= ?", [$now - 30 * 86400]),
    ],
    'active' => [
        '1' => $count('SELECT COUNT(DISTINCT user_id) FROM events WHERE user_id IS NOT NULL AND created_at >= ?', [$now - 86400]),
        '7' => $count('SELECT COUNT(DISTINCT user_id) FROM events WHERE user_id IS NOT NULL AND created_at >= ?', [$now - 7 * 86400]),
    ],
    'visits' => [
        '7' => $count("SELECT COUNT(*) FROM events WHERE type = 'page_view' AND created_at >= ?", [$now - 7 * 86400]),
        '30' => $count("SELECT COUNT(*) FROM events WHERE type = 'page_view' AND created_at >= ?", [$now - 30 * 86400]),
    ],
    'devices30' => $devices->fetchAll(),
    'topPaths30' => $paths->fetchAll(),
    'daily14' => $days14->fetchAll(),
]);
