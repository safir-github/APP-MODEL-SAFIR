<?php
// Tests d'intégration de l'API, sans dépendance : `php tests/run.php` (ou `npm test`).
// Lance un serveur PHP sur une base SQLite temporaire, envoie de vraies requêtes HTTP
// et vérifie les réponses. Voir docs/TESTS.md.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$root = dirname(__DIR__);
$port = 8099;
$base = "http://127.0.0.1:$port";
$dbFile = sys_get_temp_dir() . '/app-model-test-' . bin2hex(random_bytes(4)) . '.db';

$proc = proc_open(
    ['php', '-S', "127.0.0.1:$port", "$root/dev-router.php"],
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes,
    $root,
    ['APP_DB_PATH' => $dbFile, 'PATH' => getenv('PATH')]
);
if (!is_resource($proc)) {
    fwrite(STDERR, "Impossible de lancer le serveur de test.\n");
    exit(1);
}

$passed = 0;
$failed = 0;
function check(string $name, bool $ok): void {
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? '  ok   ' : '  ECHEC ') . $name . PHP_EOL;
}

// Requête HTTP avec un « navigateur » minimal : cookies renvoyés par le serveur, dans $jar.
function request(array &$jar, string $method, string $path, ?array $body = null): array {
    global $base;
    $headers = ['Content-Type: application/json'];
    if ($jar) {
        $pairs = [];
        foreach ($jar as $k => $v) $pairs[] = "$k=$v";
        $headers[] = 'Cookie: ' . implode('; ', $pairs);
    }
    $ctx = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $body === null ? '' : json_encode($body),
        'ignore_errors' => true,
        'timeout' => 10,
    ]]);
    $raw = @file_get_contents($base . $path, false, $ctx);
    $headerLines = $http_response_header ?? [];
    $status = (int)(explode(' ', $headerLines[0] ?? '')[1] ?? 0);
    foreach ($headerLines as $line) {
        if (stripos($line, 'Set-Cookie:') !== 0) continue;
        if (!preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $line, $m)) continue;
        if ($m[2] === '') unset($jar[$m[1]]); // cookie effacé par le serveur
        else $jar[$m[1]] = $m[2];
    }
    return ['status' => $status, 'json' => json_decode((string)$raw, true)];
}

// Attend que le serveur réponde
$ready = false;
for ($i = 0; $i < 50 && !$ready; $i++) {
    $sock = @fsockopen('127.0.0.1', $port);
    if ($sock) { fclose($sock); $ready = true; } else usleep(100000);
}

try {
    if (!$ready) throw new RuntimeException('Serveur de test non joignable.');
    echo "Tests d'API (base temporaire : $dbFile)" . PHP_EOL;

    // --- Inscription et « Rester connecté » (90 jours) ---
    $a = [];
    $r = request($a, 'POST', '/api/auth/register.php', ['email' => 'alice@test.local', 'password' => 'motdepasse1']);
    check('inscription répond 200', $r['status'] === 200 && ($r['json']['ok'] ?? false) === true);
    check('inscription pose le cookie mc_remember', isset($a['mc_remember']));

    $r = request($a, 'GET', '/api/auth/me.php');
    check('profil avec session : utilisateur renvoyé', ($r['json']['user']['email'] ?? '') === 'alice@test.local');

    // Session PHP perdue : seul le jeton « Rester connecté » reste
    unset($a['PHPSESSID']);
    $r = request($a, 'GET', '/api/auth/me.php');
    check('profil sans session : le jeton rouvre la connexion', ($r['json']['ok'] ?? false) === true);

    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tokens = $db->query('SELECT token_hash, expires_at, created_at FROM auth_tokens')->fetchAll(PDO::FETCH_ASSOC);
    check('jeton stocké sous forme d\'empreinte (sha256 de 64 caractères)', count($tokens) >= 1 && strlen($tokens[0]['token_hash']) === 64);
    check('jeton stocké ne contient pas le jeton en clair', count($tokens) >= 1 && $tokens[0]['token_hash'] !== $a['mc_remember']);
    check('jeton valable 90 jours', count($tokens) >= 1 && ($tokens[0]['expires_at'] - time()) > 89 * 86400 && ($tokens[0]['expires_at'] - time()) <= 90 * 86400);

    // --- Connexion ---
    $b = [];
    $r = request($b, 'POST', '/api/auth/login.php', ['email' => 'alice@test.local', 'password' => 'mauvais-mot']);
    check('connexion avec mauvais mot de passe : 401', $r['status'] === 401);

    $r = request($b, 'POST', '/api/auth/login.php', ['email' => 'alice@test.local', 'password' => 'motdepasse1']);
    check('connexion correcte : 200 et cookie mc_remember', $r['status'] === 200 && isset($b['mc_remember']));

    // --- Changement de mot de passe ---
    $r = request($a, 'POST', '/api/auth/change_password.php', ['current_password' => 'faux-mot-passe', 'new_password' => 'nouveau-mdp-2']);
    check('changement : mot de passe actuel faux refusé (403)', $r['status'] === 403);

    $r = request($a, 'POST', '/api/auth/change_password.php', ['current_password' => 'motdepasse1', 'new_password' => 'court']);
    check('changement : nouveau mot de passe trop court refusé (400)', $r['status'] === 400);

    $r = request($a, 'POST', '/api/auth/change_password.php', ['current_password' => 'motdepasse1', 'new_password' => 'nouveau-mdp-2']);
    check('changement : accepté (200)', $r['status'] === 200 && ($r['json']['ok'] ?? false) === true);

    unset($b['PHPSESSID']);
    $r = request($b, 'GET', '/api/auth/me.php');
    check('changement : les autres appareils sont déconnectés', ($r['json']['ok'] ?? true) === false);

    $r = request($a, 'GET', '/api/auth/me.php');
    check('changement : l\'appareil courant reste connecté', ($r['json']['ok'] ?? false) === true);

    $c = [];
    $r = request($c, 'POST', '/api/auth/login.php', ['email' => 'alice@test.local', 'password' => 'motdepasse1']);
    check('ancien mot de passe refusé', $r['status'] === 401);
    $r = request($c, 'POST', '/api/auth/login.php', ['email' => 'alice@test.local', 'password' => 'nouveau-mdp-2']);
    check('nouveau mot de passe accepté', $r['status'] === 200);

    // --- Mot de passe oublié et réinitialisation ---
    $f = [];
    $r = request($f, 'POST', '/api/auth/forgot.php', ['email' => 'inconnu@test.local']);
    check('mot de passe oublié : réponse identique pour un compte inconnu', $r['status'] === 200 && str_contains($r['json']['message'] ?? '', 'Si un compte existe'));

    $known = 'jeton-de-test-' . bin2hex(random_bytes(8));
    $uid = $db->query("SELECT id FROM users WHERE email = 'alice@test.local'")->fetchColumn();
    $db->prepare('INSERT INTO password_resets (id, user_id, expires_at) VALUES (?, ?, ?)')
        ->execute([hash('sha256', $known), $uid, time() + 3600]);

    $r = request($f, 'POST', '/api/auth/reset.php', ['token' => $known, 'password' => 'court']);
    check('réinitialisation : mot de passe court refusé (400)', $r['status'] === 400);
    $r = request($f, 'POST', '/api/auth/reset.php', ['token' => $known, 'password' => 'reinit-mdp-3']);
    check('réinitialisation : jeton valide accepté', $r['status'] === 200);
    $r = request($f, 'POST', '/api/auth/reset.php', ['token' => $known, 'password' => 'reinit-mdp-4']);
    check('réinitialisation : jeton utilisé une seule fois', $r['status'] === 400);

    $r = request($f, 'POST', '/api/auth/login.php', ['email' => 'alice@test.local', 'password' => 'reinit-mdp-3']);
    check('connexion avec le mot de passe réinitialisé', $r['status'] === 200);

    // --- Suppression de compte ---
    $g = [];
    request($g, 'POST', '/api/auth/register.php', ['email' => 'bob@test.local', 'password' => 'motdepasse-bob']);
    $r = request($g, 'POST', '/api/auth/delete_account.php', ['password' => 'mauvais-mdp']);
    check('suppression : mot de passe faux refusé (403)', $r['status'] === 403);

    $r = request($g, 'POST', '/api/auth/delete_account.php', ['password' => 'motdepasse-bob']);
    check('suppression : accepté (200)', $r['status'] === 200 && ($r['json']['ok'] ?? false) === true);
    check('suppression : aucun jeton de l\'utilisateur ne reste', (int)$db->query("SELECT COUNT(*) FROM auth_tokens t JOIN users u ON u.id = t.user_id WHERE u.email = 'bob@test.local'")->fetchColumn() === 0);

    $h = [];
    $r = request($h, 'POST', '/api/auth/login.php', ['email' => 'bob@test.local', 'password' => 'motdepasse-bob']);
    check('compte supprimé : connexion refusée', $r['status'] === 401);

    // --- Nom ---
    $r = request($a, 'POST', '/api/auth/update_profile.php', ['name' => str_repeat('x', 101)]);
    check('nom : plus de 100 caractères refusé (400)', $r['status'] === 400);
    $r = request($a, 'POST', '/api/auth/update_profile.php', ['name' => 'Alice Test']);
    check('nom : enregistré (200)', $r['status'] === 200 && ($r['json']['user']['name'] ?? '') === 'Alice Test');
    $r = request($a, 'GET', '/api/auth/me.php');
    check('nom : renvoyé par le profil', ($r['json']['user']['name'] ?? '') === 'Alice Test');

    // --- Changement d'adresse e-mail (confirmation par lien) ---
    $r = request($a, 'POST', '/api/auth/request_email_change.php', ['password' => 'faux-mdp', 'email' => 'alice2@test.local']);
    check('e-mail : mot de passe faux refusé (403)', $r['status'] === 403);
    $r = request($a, 'POST', '/api/auth/request_email_change.php', ['password' => 'reinit-mdp-3', 'email' => 'pas-une-adresse']);
    check('e-mail : adresse invalide refusée (400)', $r['status'] === 400);
    $tmp = [];
    request($tmp, 'POST', '/api/auth/register.php', ['email' => 'carol@test.local', 'password' => 'motdepasse-carol']);
    $r = request($a, 'POST', '/api/auth/request_email_change.php', ['password' => 'reinit-mdp-3', 'email' => 'carol@test.local']);
    check('e-mail : adresse déjà prise refusée (409)', $r['status'] === 409);
    $r = request($a, 'POST', '/api/auth/request_email_change.php', ['password' => 'reinit-mdp-3', 'email' => 'alice2@test.local']);
    check('e-mail : demande acceptée (200)', $r['status'] === 200 && ($r['json']['ok'] ?? false) === true);
    $r = request($a, 'GET', '/api/auth/me.php');
    check('e-mail : l\'adresse n\'a pas changé avant confirmation', ($r['json']['user']['email'] ?? '') === 'alice@test.local');

    $known2 = 'confirm-test-' . bin2hex(random_bytes(8));
    $db->prepare('UPDATE email_changes SET token_hash = ? WHERE user_id = ?')->execute([hash('sha256', $known2), $uid]);
    $r = request($a, 'POST', '/api/auth/confirm_email.php', ['token' => 'mauvais-lien']);
    check('e-mail : lien faux refusé (400)', $r['status'] === 400);
    $r = request($a, 'POST', '/api/auth/confirm_email.php', ['token' => $known2]);
    check('e-mail : lien confirmé (200)', $r['status'] === 200 && ($r['json']['email'] ?? '') === 'alice2@test.local');
    $r = request($a, 'POST', '/api/auth/confirm_email.php', ['token' => $known2]);
    check('e-mail : lien utilisable une seule fois', $r['status'] === 400);
    $r = request($f, 'POST', '/api/auth/login.php', ['email' => 'alice2@test.local', 'password' => 'reinit-mdp-3']);
    check('e-mail : connexion avec la nouvelle adresse', $r['status'] === 200);

    // --- Déconnexion ---
    $r = request($a, 'POST', '/api/auth/logout.php');
    check('déconnexion : 200', $r['status'] === 200);
    unset($a['PHPSESSID']);
    $r = request($a, 'GET', '/api/auth/me.php');
    check('après déconnexion : plus d\'utilisateur', ($r['json']['ok'] ?? true) === false);

    // --- Cron : refusé par le web, accepté en ligne de commande ---
    $r = request($h, 'GET', '/api/cron/run.php');
    check('tâche planifiée : refusée par le web (403)', $r['status'] === 403);

    $out = shell_exec('APP_DB_PATH=' . escapeshellarg($dbFile) . ' php ' . escapeshellarg("$root/api/cron/run.php"));
    check('tâche planifiée : exécutée en ligne de commande', str_contains((string)$out, '"daily_reminders"'));
} catch (Throwable $e) {
    check('exécution des tests : ' . $e->getMessage(), false);
} finally {
    proc_terminate($proc);
    proc_close($proc);
    foreach (['', '-wal', '-shm'] as $suffix) @unlink($dbFile . $suffix);
}

echo PHP_EOL . "Résultat : $passed réussi(s), $failed échec(s)." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
