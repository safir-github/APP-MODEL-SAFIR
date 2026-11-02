<?php
require_once __DIR__ . '/../bootstrap.php';

// Vue d'ensemble : chiffres agrégés. Aucune donnée individuelle ici.
// Les comptes non « reel » (test, agent, interne) sont exclus de tous les chiffres. Leurs événements restent dans events.
$admin = require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') fail('Méthode non autorisée', 405);

$pdo = db();
$now = time();
// Événements à retenir : visiteurs anonymes (user_id vide) et comptes de catégorie « reel »
const REEL_EVENT = "(e.user_id IS NULL OR e.user_id IN (SELECT id FROM users WHERE account_kind = 'reel'))";
const REEL_ACCOUNT_EVENT = "e.user_id IN (SELECT id FROM users WHERE account_kind = 'reel')";

$count = function (string $sql, array $params = []) use ($pdo): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
};

$days14 = $pdo->prepare("SELECT date(e.created_at, 'unixepoch') AS day,
        SUM(e.type = 'page_view') AS visits, SUM(e.type = 'signup') AS signups
    FROM events e WHERE e.created_at >= ? AND " . REEL_EVENT . " GROUP BY day ORDER BY day");
$days14->execute([$now - 14 * 86400]);

$devices = $pdo->prepare("SELECT e.device, COUNT(*) AS n FROM events e WHERE e.type = 'page_view' AND e.created_at >= ? AND " . REEL_EVENT . " GROUP BY e.device");
$devices->execute([$now - 30 * 86400]);

$paths = $pdo->prepare("SELECT e.path, COUNT(*) AS n FROM events e WHERE e.type = 'page_view' AND e.created_at >= ? AND " . REEL_EVENT . " GROUP BY e.path ORDER BY n DESC LIMIT 10");
$paths->execute([$now - 30 * 86400]);

$signups = fn (int $days) => $count("SELECT COUNT(*) FROM events e WHERE e.type = 'signup' AND e.created_at >= ? AND " . REEL_EVENT, [$now - $days * 86400]);
$visits = fn (int $days) => $count("SELECT COUNT(*) FROM events e WHERE e.type = 'page_view' AND e.created_at >= ? AND " . REEL_EVENT, [$now - $days * 86400]);
$active = fn (int $days) => $count("SELECT COUNT(DISTINCT e.user_id) FROM events e WHERE e.user_id IS NOT NULL AND e.created_at >= ? AND " . REEL_ACCOUNT_EVENT, [$now - $days * 86400]);

// Période précédente (7 jours juste avant) pour les variations affichées dans le tableau de bord
$signupsPrev = $count("SELECT COUNT(*) FROM events e WHERE e.type = 'signup' AND e.created_at >= ? AND e.created_at < ? AND " . REEL_EVENT, [$now - 14 * 86400, $now - 7 * 86400]);
$visitsPrev = $count("SELECT COUNT(*) FROM events e WHERE e.type = 'page_view' AND e.created_at >= ? AND e.created_at < ? AND " . REEL_EVENT, [$now - 14 * 86400, $now - 7 * 86400]);
$activePrev = $count("SELECT COUNT(DISTINCT e.user_id) FROM events e WHERE e.user_id IS NOT NULL AND e.created_at >= ? AND e.created_at < ? AND " . REEL_ACCOUNT_EVENT, [$now - 14 * 86400, $now - 7 * 86400]);

log_admin($admin['id'], 'view_overview');
json_out([
    'ok' => true,
    'accounts' => $count("SELECT COUNT(*) FROM users WHERE account_kind = 'reel'"),
    'signups' => ['7' => $signups(7), '30' => $signups(30)],
    'active' => ['1' => $active(1), '7' => $active(7)],
    'visits' => ['7' => $visits(7), '30' => $visits(30)],
    'devices30' => $devices->fetchAll(),
    'topPaths30' => $paths->fetchAll(),
    'previous7' => ['signups' => $signupsPrev, 'visits' => $visitsPrev, 'active' => $activePrev],
    'daily14' => $days14->fetchAll(),
]);
