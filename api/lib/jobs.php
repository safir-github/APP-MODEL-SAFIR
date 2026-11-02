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

// Suppression des événements de plus de 90 jours (voir docs/ANALYTICS.md)
function job_purge_events(): array {
    $stmt = db()->prepare('DELETE FROM events WHERE created_at < ?');
    $stmt->execute([time() - 90 * 86400]);
    $events = $stmt->rowCount();
    // Même durée pour le journal des accès administrateur (voir docs/ANALYTICS.md)
    $stmt = db()->prepare('DELETE FROM admin_log WHERE created_at < ?');
    $stmt->execute([time() - 90 * 86400]);
    return ['deleted' => $events, 'admin_log_deleted' => $stmt->rowCount()];
}

// Résumé d'activité envoyé à chaque administrateur, une fois par 24 h (règle 6 de CLAUDE.md : un e-mail par personne et par jour).
// Chiffres seulement : aucune adresse ni donnée individuelle dans l'e-mail. Voir docs/ANALYTICS.md.
function job_admin_activity_digest(): array {
    $pdo = db();
    $since = time() - 86400;
    $stats = [
        'active_accounts' => (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM events WHERE user_id IS NOT NULL AND created_at >= $since")->fetchColumn(),
        'signups' => (int)$pdo->query("SELECT COUNT(*) FROM events WHERE type = 'signup' AND created_at >= $since")->fetchColumn(),
        'logins' => (int)$pdo->query("SELECT COUNT(*) FROM events WHERE type = 'login' AND created_at >= $since")->fetchColumn(),
        'visits' => (int)$pdo->query("SELECT COUNT(*) FROM events WHERE type = 'page_view' AND created_at >= $since")->fetchColumn(),
    ];

    $admins = $pdo->query('SELECT id, email FROM users WHERE is_admin = 1')->fetchAll();
    $sent = 0;
    foreach ($admins as $admin) {
        if (notified_within_24h($admin['id'], 'admin_digest')) continue;
        $text = "Bonjour,\n\nActivité des dernières 24 heures :\n"
              . "- comptes actifs : {$stats['active_accounts']}\n"
              . "- nouvelles inscriptions : {$stats['signups']}\n"
              . "- connexions : {$stats['logins']}\n"
              . "- pages vues (visiteurs ayant accepté la mesure d'audience) : {$stats['visits']}\n\n"
              . "Détail dans le tableau de bord : " . APP_URL . "/admin";
        if (send_mail($admin['email'], 'Activité de votre application (24 h)', $text)) {
            mark_notified($admin['id'], 'admin_digest');
            $sent++;
        }
    }
    return ['administrators' => count($admins), 'sent' => $sent] + $stats;
}
