<?php
require_once __DIR__ . '/../bootstrap.php';

// Fiche détaillée d'un compte. Réservée aux administrateurs, consultation tracée dans admin_log.
// Aucune adresse IP. Données calculées à partir de events et de users.
$admin = require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') fail('Méthode non autorisée', 405);

$id = (string)($_GET['id'] ?? '');
if ($id === '') bad_request('Compte manquant.');

$pdo = db();
$stmt = $pdo->prepare('SELECT id, email, name, is_admin, account_kind, created_at, last_login_at FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) fail('Compte introuvable.', 404);

$now = time();
$q = function (string $sql, array $params = []) use ($pdo) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
};

// Jours d'activité distincts (sur 90 jours), du plus récent au plus ancien
$dayRows = $q("SELECT DISTINCT date(created_at, 'unixepoch') AS day FROM events WHERE user_id = ? AND created_at >= ? ORDER BY day DESC",
    [$id, $now - 90 * 86400])->fetchAll(PDO::FETCH_COLUMN);
$activeDays = function (int $days) use ($dayRows, $now): int {
    $since = date('Y-m-d', $now - $days * 86400);
    return count(array_filter($dayRows, fn ($d) => $d >= $since));
};

// Série en cours : jours consécutifs d'activité, en comptant aujourd'hui ou hier comme point de départ
$streak = 0;
$cursor = new DateTimeImmutable(date('Y-m-d', $now));
$set = array_flip($dayRows);
if (!isset($set[$cursor->format('Y-m-d')])) $cursor = $cursor->modify('-1 day');
while (isset($set[$cursor->format('Y-m-d')])) {
    $streak++;
    $cursor = $cursor->modify('-1 day');
}

$lastVisit = $q("SELECT MAX(created_at) FROM events WHERE user_id = ? AND type = 'page_view'", [$id])->fetchColumn();
$firstLogin = $q("SELECT MIN(created_at) FROM events WHERE user_id = ? AND type = 'login'", [$id])->fetchColumn();
$lastActivity = $q('SELECT MAX(created_at) FROM events WHERE user_id = ?', [$id])->fetchColumn();
$loginsWeek = (int)$q("SELECT COUNT(*) FROM events WHERE user_id = ? AND type = 'login' AND created_at >= ?", [$id, $now - 7 * 86400])->fetchColumn();
$logins90 = (int)$q("SELECT COUNT(*) FROM events WHERE user_id = ? AND type = 'login' AND created_at >= ?", [$id, $now - 90 * 86400])->fetchColumn();

$actions = $q("SELECT type, COUNT(*) AS n FROM events WHERE user_id = ? AND created_at >= ? GROUP BY type ORDER BY n DESC",
    [$id, $now - 90 * 86400])->fetchAll();
$pages = $q("SELECT path, COUNT(*) AS n FROM events WHERE user_id = ? AND type = 'page_view' AND created_at >= ? GROUP BY path ORDER BY n DESC LIMIT 10",
    [$id, $now - 90 * 86400])->fetchAll();
$devices = $q("SELECT device, COUNT(*) AS n FROM events WHERE user_id = ? AND created_at >= ? GROUP BY device",
    [$id, $now - 90 * 86400])->fetchAll();
$chronology = $q('SELECT type, path, device, created_at FROM events WHERE user_id = ? ORDER BY created_at DESC LIMIT 50', [$id])->fetchAll();

// État lisible
if ($lastActivity === false || $lastActivity === null) {
    $state = 'Jamais connecté';
} else {
    $days = (int)floor(($now - (int)$lastActivity) / 86400);
    $state = $days <= 7 ? 'Actif' : "Inactif depuis $days jours";
}

log_admin($admin['id'], 'view_account', $id);
json_out([
    'ok' => true,
    'identity' => [
        'id' => $user['id'], 'email' => $user['email'], 'name' => $user['name'],
        'is_admin' => (int)$user['is_admin'], 'account_kind' => $user['account_kind'],
    ],
    'dates' => [
        'created_at' => $user['created_at'],
        'first_login' => $firstLogin ?: null,
        'last_login' => $user['last_login_at'] ? (int)$user['last_login_at'] : null,
        'last_activity' => $lastActivity ? (int)$lastActivity : null,
        'last_visit' => $lastVisit ? (int)$lastVisit : null,
    ],
    'frequency' => [
        'active_days_7' => $activeDays(7),
        'active_days_30' => $activeDays(30),
        'active_days_90' => $activeDays(90),
        'logins_last_7_days' => $loginsWeek,
        'logins90' => $logins90,
        'logins_per_week_90' => round($logins90 / (90 / 7), 1),
        'streak_days' => $streak,
    ],
    'state' => $state,
    'actions90' => $actions,
    'pages90' => $pages,
    'pagesNote' => count($pages) === 0 ? "Aucune page vue enregistrée pour ce compte sur 90 jours (mesure d'audience non acceptée, ou aucune visite)." : null,
    'devices90' => $devices,
    'chronology' => $chronology,
]);
