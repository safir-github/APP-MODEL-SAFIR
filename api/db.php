<?php
// Connexion SQLite (PDO) + création automatique du schéma.

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    db_init_schema($pdo);
    return $pdo;
}

// Schéma commun à toutes les apps. Ajouter ici les tables propres au projet.
// Règle : toute nouvelle colonne se fait par ALTER TABLE ... ADD COLUMN dans un try/catch.
function db_init_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            TEXT PRIMARY KEY,
            email         TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at    TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Réinitialisation du mot de passe : jeton à usage unique, empreinte seulement, valable 1 h
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id TEXT PRIMARY KEY,
            user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            expires_at INTEGER NOT NULL,
            used_at INTEGER
        );
    ");

    // Limitation des tentatives (anti-force brute)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            k TEXT PRIMARY KEY,
            hits INTEGER NOT NULL,
            reset_at INTEGER NOT NULL
        );
    ");

    // Journal des e-mails automatiques : sert à limiter à un e-mail par jour et par personne
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications_log (
            user_id TEXT NOT NULL,
            kind TEXT NOT NULL,
            sent_at INTEGER NOT NULL
        );
    ");

    // Jetons « Rester connecté » : seule l'empreinte (sha256) du jeton est stockée
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS auth_tokens (
                token_hash TEXT PRIMARY KEY,
                user_id TEXT NOT NULL,
                expires_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            );
        ");
    } catch (Throwable $e) {}

    // Nom affiché (modifiable depuis « Mon compte »)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN name TEXT NOT NULL DEFAULT ''");
    } catch (Throwable $e) {}

    // Rôle administrateur : 0 = utilisateur, 1 = administrateur. Attribué uniquement en ligne de commande
    // (api/cli/make_admin.php), jamais depuis le web.
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0");
    } catch (Throwable $e) {}

    // Dernière connexion, pour le tableau de bord administrateur
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login_at INTEGER");
    } catch (Throwable $e) {}

    // Journal d'événements des comptes (inscription, connexion, déconnexion). Conservé 90 jours (tâche planifiée).
    // Pas d'adresse IP, pas d'identifiant de session, pas de contenu saisi : seulement le type, la page et la classe d'appareil.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT,
            type TEXT NOT NULL,
            path TEXT NOT NULL DEFAULT '',
            device TEXT NOT NULL DEFAULT '',
            created_at INTEGER NOT NULL
        );
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_events_created ON events (created_at)");

    // Changement d'adresse e-mail en attente : seule l'empreinte du lien de confirmation est stockée
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_changes (
            token_hash TEXT PRIMARY KEY,
            user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            new_email TEXT NOT NULL,
            expires_at INTEGER NOT NULL
        );
    ");
}
