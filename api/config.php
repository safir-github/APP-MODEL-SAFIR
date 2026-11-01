<?php
// Configuration globale — inclus uniquement, jamais appelé directement.
// api/config.local.php (gitignoré) surcharge ces valeurs (secrets, chemin de base en production).

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }
if (defined('APP_CONFIG_LOADED')) return;
define('APP_CONFIG_LOADED', true);

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// Base SQLite. En production : chemin ABSOLU hors public_html, dans config.local.php.
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/../data/app.db');
}

// Origines autorisées en développement (Astro :4321 -> PHP :8000). Inutile en production (même origine).
if (!defined('ALLOWED_DEV_ORIGINS')) {
    define('ALLOWED_DEV_ORIGINS', [
        'http://localhost:4321',
        'http://localhost:8000',
        'http://127.0.0.1:4321',
        'http://127.0.0.1:8000',
    ]);
}
