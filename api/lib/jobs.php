<?php
// Tâches automatiques (e-mails programmés, rappels, nettoyage).
// Exécutées par cron en ligne de commande (voir docs/SCHEDULED_JOBS.md), jamais par une requête web.

// Vrai si l'utilisateur a déjà reçu ce type de notification dans les dernières 24 heures
function notified_within_24h(string $userId, string $kind): bool {
    $stmt = db()->prepare('SELECT 1 FROM notifications_log WHERE user_id = ? AND kind = ? AND sent_at > ?');
    $stmt->execute([$userId, $kind, time() - 86400]);
    return (bool)$stmt->fetch();
}

function mark_notified(string $userId, string $kind): void {
    db()->prepare('INSERT INTO notifications_log (user_id, kind, sent_at) VALUES (?, ?, ?)')
        ->execute([$userId, $kind, time()]);
}

// Exemple de tâche : un rappel par jour et par personne, au lieu d'un e-mail par élément
function job_daily_reminders(): array {
    $sent = 0;
    $users = db()->query('SELECT id, email FROM users')->fetchAll();
    foreach ($users as $user) {
        if (notified_within_24h($user['id'], 'daily_reminder')) continue;
        // Ici : construire la liste des éléments à rappeler pour cet utilisateur.
        $items = [];
        if (!$items) continue;

        $lines = array_map(fn($i) => '- ' . $i, $items);
        $text = "Bonjour,\n\nRappels du jour :\n" . implode("\n", $lines) . "\n\n" . APP_URL;
        if (send_mail($user['email'], 'Vos rappels du jour', $text)) {
            mark_notified($user['id'], 'daily_reminder');
            $sent++;
        }
    }
    return ['sent' => $sent];
}
