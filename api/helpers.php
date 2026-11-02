<?php
// Fonctions utilitaires communes à toutes les apps.

function json_out($data, int $status = 200): void {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function fail(string $message, int $status = 400): void {
    json_out(['ok' => false, 'error' => $message], $status);
}

function bad_request(string $message): void { fail($message, 400); }
function unauthorized(string $message = 'Non authentifié'): void { fail($message, 401); }
function conflict(string $message): void { fail($message, 409); }

// Démarre la session PHP avec un cookie de 30 jours, httponly, SameSite=Lax
function start_app_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $lifetime = 60 * 60 * 24 * 30;
    @ini_set('session.gc_maxlifetime', (string)$lifetime);
    @ini_set('session.cookie_lifetime', (string)$lifetime);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => is_https_request(),
    ]);
    session_start();
}

function is_https_request(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

// Utilisateur courant (session, ou jeton « Rester connecté »), ou null
function current_user(): ?array {
    start_app_session();
    if (empty($_SESSION['user_id'])) restore_from_remember_token();
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare('SELECT id, email, name, is_admin, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function require_auth(): array {
    $user = current_user();
    if (!$user) unauthorized();
    return $user;
}

// Réservé aux administrateurs. La vérification est faite ici, côté serveur, à chaque requête :
// l'affichage de l'interface admin ne sert qu'à l'ergonomie, jamais à la sécurité.
function require_admin(): array {
    $user = require_auth();
    if ((int)$user['is_admin'] !== 1) fail('Réservé aux administrateurs.', 403);
    return $user;
}

// ---------- « Rester connecté » (jeton de 90 jours) ----------
const REMEMBER_COOKIE = 'mc_remember';
const REMEMBER_SECONDS = 60 * 60 * 24 * 90;

function remember_cookie_options(int $expires): array {
    return ['expires' => $expires, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => is_https_request()];
}

// Crée un jeton pour l'utilisateur et le place dans un cookie sécurisé (90 jours)
function remember_user(string $userId): void {
    $token = bin2hex(random_bytes(32));
    $now = time();
    $pdo = db();
    $pdo->prepare('DELETE FROM auth_tokens WHERE expires_at < ?')->execute([$now]);
    $pdo->prepare('INSERT INTO auth_tokens (token_hash, user_id, expires_at, created_at) VALUES (?, ?, ?, ?)')
        ->execute([hash('sha256', $token), $userId, $now + REMEMBER_SECONDS, $now]);
    setcookie(REMEMBER_COOKIE, $token, remember_cookie_options($now + REMEMBER_SECONDS));
}

// Supprime le jeton courant (déconnexion) et son cookie
function forget_remember(): void {
    $token = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if ($token !== '') {
        db()->prepare('DELETE FROM auth_tokens WHERE token_hash = ?')->execute([hash('sha256', $token)]);
    }
    setcookie(REMEMBER_COOKIE, '', remember_cookie_options(time() - 3600));
}

// Si la session PHP a disparu, le jeton rouvre la session et se prolonge (glissant)
function restore_from_remember_token(): void {
    $token = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if ($token === '') return;
    $hash = hash('sha256', $token);
    $stmt = db()->prepare('SELECT user_id, expires_at FROM auth_tokens WHERE token_hash = ?');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['expires_at'] < time()) return;
    $_SESSION['user_id'] = $row['user_id'];
    $newExpiry = time() + REMEMBER_SECONDS;
    db()->prepare('UPDATE auth_tokens SET expires_at = ? WHERE token_hash = ?')->execute([$newExpiry, $hash]);
    setcookie(REMEMBER_COOKIE, $token, remember_cookie_options($newExpiry));
}

// ---------- Journal d'événements (voir docs/ANALYTICS.md) ----------
// Classe d'appareil déduite de l'en-tête User-Agent : seulement « mobile » ou « desktop », jamais le texte complet.
function device_class(): string {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    return preg_match('/mobile|iphone|android|ipad/', $ua) ? 'mobile' : 'desktop';
}

// Enregistre un événement. $path : chemin seul, sans paramètres d'URL.
function log_event(?string $userId, string $type, string $path = ''): void {
    db()->prepare('INSERT INTO events (user_id, type, path, device, created_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$userId, $type, substr(parse_url($path, PHP_URL_PATH) ?: '', 0, 200), device_class(), time()]);
}

// Journal des accès administrateur (voir docs/ANALYTICS.md)
function log_admin(string $adminId, string $action, string $target = ''): void {
    db()->prepare('INSERT INTO admin_log (admin_id, action, target, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$adminId, $action, $target, time()]);
}
