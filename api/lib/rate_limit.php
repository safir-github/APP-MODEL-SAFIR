<?php
// Limitation des tentatives (anti-force brute) stockée en base.
// Chaque clé (ex. 'login:IP', 'forgot:IP') a un compteur qui expire après 15 minutes.

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'inconnu';
}

function rate_limited(string $key, int $max): bool {
    $stmt = db()->prepare('SELECT hits, reset_at FROM rate_limits WHERE k = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row && (int)$row['reset_at'] > time() && (int)$row['hits'] >= $max;
}

function record_attempt(string $key): void {
    $now = time();
    $reset = $now + 900;
    db()->prepare(
        'INSERT INTO rate_limits (k, hits, reset_at) VALUES (?, 1, ?)
         ON CONFLICT(k) DO UPDATE SET
           hits = CASE WHEN reset_at < ? THEN 1 ELSE hits + 1 END,
           reset_at = CASE WHEN reset_at < ? THEN ? ELSE reset_at END'
    )->execute([$key, $reset, $now, $now, $reset]);
}
