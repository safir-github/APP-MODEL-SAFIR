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
    [PHP_BINARY, '-S', "127.0.0.1:$port", "$root/dev-router.php"],
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
    check('suppression : aucun événement de l\'utilisateur ne reste', (int)$db->query("SELECT COUNT(*) FROM events e JOIN users u ON u.id = e.user_id WHERE u.email = 'bob@test.local'")->fetchColumn() === 0);
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

    // --- Journal d'événements des comptes ---
    $evt = fn(string $type) => (int)$db->query("SELECT COUNT(*) FROM events e JOIN users u ON u.id = e.user_id WHERE u.email = 'alice2@test.local' AND e.type = '$type'")->fetchColumn();
    check('événements : inscription enregistrée', $evt('signup') >= 1);
    check('événements : connexion enregistrée', $evt('login') >= 1);
    $last = $db->query("SELECT last_login_at FROM users WHERE email = 'alice2@test.local'")->fetchColumn();
    check('événements : dernière connexion mise à jour', $last !== false && (int)$last > 0 && (int)$last <= time());
    $paths = $db->query("SELECT COUNT(*) FROM events WHERE path LIKE '%?%' OR path LIKE '%@%'")->fetchColumn();
    check('événements : aucun paramètre d\'URL ni adresse e-mail enregistrés', (int)$paths === 0);
    $devices = $db->query("SELECT COUNT(*) FROM events WHERE device NOT IN ('mobile','desktop')")->fetchColumn();
    check('événements : classe d\'appareil seulement mobile ou desktop', (int)$devices === 0);

    // --- Rôle administrateur (attribué seulement en ligne de commande) ---
    $r = request($a, 'GET', '/api/auth/me.php');
    check('admin : compte ordinaire sans rôle administrateur', ($r['json']['user']['is_admin'] ?? 1) == 0);
    $r = request($a, 'GET', '/api/admin/users.php');
    check('admin : liste refusée à un compte ordinaire (403)', $r['status'] === 403);
    $anonyme = [];
    $r = request($anonyme, 'GET', '/api/admin/users.php');
    check('admin : liste refusée sans connexion (401)', $r['status'] === 401);
    $r = request($anonyme, 'GET', '/api/cli/make_admin.php');
    check('admin : script de promotion refusé par le web (403)', $r['status'] === 403);

    $env = 'APP_DB_PATH=' . escapeshellarg($dbFile) . ' ';
    $out = shell_exec($env . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/api/cli/make_admin.php") . ' ' . escapeshellarg('alice2@test.local') . ' 2>&1');
    check('admin : promotion en ligne de commande', str_contains((string)$out, 'maintenant administrateur'));
    $r = request($a, 'GET', '/api/auth/me.php');
    check('admin : le profil indique le rôle', ($r['json']['user']['is_admin'] ?? 0) == 1);
    $r = request($a, 'GET', '/api/admin/users.php');
    check('admin : liste accessible à l\'administrateur', $r['status'] === 200 && count($r['json']['users'] ?? []) >= 1);

    $out = shell_exec($env . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/api/cli/make_admin.php") . ' ' . escapeshellarg('alice2@test.local') . ' --revoke 2>&1');
    check('admin : retrait du rôle en ligne de commande', str_contains((string)$out, 'retiré'));
    $r = request($a, 'GET', '/api/admin/users.php');
    check('admin : liste de nouveau refusée après retrait (403)', $r['status'] === 403);

    // --- Consentement et pages vues ---
    $sans = [];
    $total = fn() => (int)$db->query("SELECT COUNT(*) FROM events WHERE type = 'page_view'")->fetchColumn();
    $avant = $total();
    $r = request($sans, 'POST', '/api/track.php', ['path' => '/']);
    check('suivi : sans consentement, aucune page vue enregistrée (204)', $r['status'] === 204 && $total() === $avant);
    $cj = [];
    $r = request($cj, 'POST', '/api/consent.php', ['accept' => true]);
    check('consentement : accepté et mémorisé par cookie', $r['status'] === 200 && ($cj['mc_consent'] ?? '') === '1');
    $r = request($cj, 'POST', '/api/track.php', ['path' => '/login']);
    check('suivi : avec consentement, page vue enregistrée', $r['status'] === 204 && $total() === $avant + 1);
    $r = request($cj, 'POST', '/api/track.php', ['path' => '/compte?token=secret']);
    check('suivi : paramètres d\'URL refusés (400)', $r['status'] === 400);
    $r = request($cj, 'POST', '/api/track.php', ['path' => 'https://exemple.fr/']);
    check('suivi : URL complète refusée (400)', $r['status'] === 400);
    $r = request($cj, 'POST', '/api/consent.php', ['accept' => false]);
    check('consentement : refus mémorisé, plus de suivi', ($cj['mc_consent'] ?? '') === '0');
    $avant2 = $total();
    $r = request($cj, 'POST', '/api/track.php', ['path' => '/']);
    check('suivi : après refus, rien n\'est enregistré', $total() === $avant2);

    // --- Tableau de bord (administrateur) ---
    $out = shell_exec($env . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/api/cli/make_admin.php") . ' ' . escapeshellarg('alice2@test.local') . ' 2>&1');
    $r = request($a, 'GET', '/api/admin/overview.php');
    check('tableau : vue d\'ensemble accessible à l\'administrateur', $r['status'] === 200 && isset($r['json']['accounts']) && isset($r['json']['daily14']));
    $r = request($anonyme, 'GET', '/api/admin/overview.php');
    check('tableau : vue d\'ensemble refusée sans connexion (401)', $r['status'] === 401);
    $r = request($a, 'GET', '/api/admin/events.php?type=bidon');
    check('tableau : type d\'événement inconnu refusé (400)', $r['status'] === 400);
    $r = request($a, 'GET', '/api/admin/events.php?days=7');
    check('tableau : activité listée', $r['status'] === 200 && is_array($r['json']['events'] ?? null));
    $uid = $db->query("SELECT id FROM users WHERE email = 'alice2@test.local'")->fetchColumn();
    $r = request($a, 'GET', '/api/admin/events.php?user_id=' . $uid . '&days=90');
    check('tableau : activité d\'un compte listée', $r['status'] === 200 && count($r['json']['events'] ?? []) >= 1);
    $r = request($a, 'GET', '/api/admin/users.php?q=alice2');
    check('tableau : recherche de compte', $r['status'] === 200 && count($r['json']['users'] ?? []) === 1);
    $r = request($a, 'GET', '/api/admin/journal.php');
    check('tableau : journal des accès tracé', $r['status'] === 200 && count($r['json']['entries'] ?? []) >= 3);
    $r = request($anonyme, 'GET', '/api/admin/events.php');
    check('tableau : activité refusée sans droits (401)', $r['status'] === 401);
    shell_exec($env . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/api/cli/make_admin.php") . ' ' . escapeshellarg('alice2@test.local') . ' --revoke 2>&1');

    // --- Déconnexion ---
    $r = request($a, 'POST', '/api/auth/logout.php');
    check('déconnexion : 200', $r['status'] === 200);
    unset($a['PHPSESSID']);
    $r = request($a, 'GET', '/api/auth/me.php');
    check('après déconnexion : plus d\'utilisateur', ($r['json']['ok'] ?? true) === false);
    check('événements : déconnexion enregistrée', (int)$db->query("SELECT COUNT(*) FROM events e JOIN users u ON u.id = e.user_id WHERE u.email = 'alice2@test.local' AND e.type = 'logout'")->fetchColumn() >= 1);

    // --- Cron : refusé par le web, accepté en ligne de commande ---
    $r = request($h, 'GET', '/api/cron/run.php');
    check('tâche planifiée : refusée par le web (403)', $r['status'] === 403);

    $out = shell_exec('APP_DB_PATH=' . escapeshellarg($dbFile) . ' ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/api/cron/run.php"));
    check('notification administrateur : résumé 24 h dans la tâche planifiée', str_contains((string)$out, '"admin_digest"') && str_contains((string)$out, '"active_accounts"'));
    check('événements : purge des 90 jours dans la tâche planifiée', str_contains((string)$out, '"purge_events"'));
    check('tâche planifiée : exécutée en ligne de commande', str_contains((string)$out, '"daily_reminders"'));
} catch (Throwable $e) {
    check('exécution des tests : ' . $e->getMessage(), false);
} finally {
    proc_terminate($proc);
    proc_close($proc);
    foreach (['', '-wal', '-shm'] as $suffix) @unlink($dbFile . $suffix);
}

// --- Pages légales : aucun marqueur « À RENSEIGNER » en diffusion ---
$legal = (string)file_get_contents("$root/src/legal.ts");
$draftFalse = (bool)preg_match('/draft:\s*false/', $legal);
$markers = substr_count($legal, 'À RENSEIGNER');
check('pages légales : plus de marqueur « À RENSEIGNER » quand draft vaut false', !$draftFalse || $markers === 0);
if ($draftFalse && is_dir("$root/dist")) {
    foreach (['conditions', 'confidentialite'] as $page) {
        $html = (string)@file_get_contents("$root/dist/$page/index.html");
        check("pages légales : /$page sans marqueur dans le build", $html !== '' && !str_contains($html, 'À RENSEIGNER'));
    }
}

echo PHP_EOL . "Résultat : $passed réussi(s), $failed échec(s)." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
