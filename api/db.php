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
}
