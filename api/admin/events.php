<?php
require_once __DIR__ . '/../bootstrap.php';

// Activité : événements filtrables. Consulter l'activité d'un compte précis est tracé dans admin_log.
$admin = require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') fail('Méthode non autorisée', 405);

$types = ['signup', 'login', 'logout', 'page_view'];
$type = (string)($_GET['type'] ?? '');
$days = min(90, max(1, (int)($_GET['days'] ?? 7)));
$limit = min(200, max(1, (int)($_GET['limit'] ?? 100)));
$offset = max(0, (int)($_GET['offset'] ?? 0));
$userId = (string)($_GET['user_id'] ?? '');

$where = ['e.created_at >= ?'];
$params = [time() - $days * 86400];
if ($type !== '') {
    if (!in_array($type, $types, true)) bad_request('Type d\'événement inconnu.');
    $where[] = 'e.type = ?';
    $params[] = $type;
}
if ($userId !== '') {
    $where[] = 'e.user_id = ?';
    $params[] = $userId;
}

$sql = 'SELECT e.id, e.type, e.path, e.device, e.created_at, u.email
        FROM events e LEFT JOIN users u ON u.id = e.user_id
        WHERE ' . implode(' AND ', $where) . ' ORDER BY e.created_at DESC LIMIT ? OFFSET ?';
$stmt = db()->prepare($sql);
foreach ($params as $i => $value) $stmt->bindValue($i + 1, $value);
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();

if ($userId !== '') log_admin($admin['id'], 'view_user_activity', $userId);
json_out(['ok' => true, 'events' => $stmt->fetchAll()]);
