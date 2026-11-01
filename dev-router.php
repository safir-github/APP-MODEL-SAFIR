<?php
// Routeur pour le serveur de dev PHP local : `php -S localhost:8000 dev-router.php`
//
// Rôles :
// 1. Servir l'API (/api/*.php) — exécutée par PHP.
// 2. Servir le build statique (dist/) pour l'UI — preview prod en une commande.
// 3. Bloquer l'accès direct aux fichiers sensibles (data/, includes, secrets).
//
// Pour le dev avec HMR, lance plutôt `npm run dev` (Astro :4321) + ce serveur PHP
// (l'UI en dev pointe sur http://localhost:8000 via PUBLIC_API_BASE).

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// 1. Fichiers/dossiers sensibles : interdits.
if (preg_match('#^/(data|\.env|\.git|node_modules|src)(/|$)#', $path)
    || preg_match('#^/api/(config|config\.local|db|helpers|bootstrap)(\.php)?/?$#', $path)
    || preg_match('#^/api/lib/#', $path)
    || preg_match('#\.local\.php$#', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

// 2. Endpoints API : laisse php -S exécuter le script ./api/.../.php
if (strpos($path, '/api/') === 0) {
    return false;
}

// 3. UI statique : sert depuis dist/.
$distRoot = __DIR__ . '/dist';
if (!is_dir($distRoot)) {
    http_response_code(500);
    echo "dist/ absent — lance d'abord : npm run build";
    return true;
}

// Normalise le chemin vers un fichier dist.
$file = $distRoot . $path;
if ($path === '/' || $path === '') {
    $file = $distRoot . '/index.html';
} elseif (!is_file($file)) {
    // Routes "propres" Astro : /login -> /login/index.html, sinon fallback index.html.
    $candidate = $distRoot . rtrim($path, '/') . '/index.html';
    $file = is_file($candidate) ? $candidate : $distRoot . '/index.html';
}

if (!is_file($file)) {
    http_response_code(404);
    echo '404 Not Found';
    return true;
}

$mime = match (pathinfo($file, PATHINFO_EXTENSION)) {
    'html' => 'text/html; charset=utf-8',
    'css'  => 'text/css; charset=utf-8',
    'js'   => 'application/javascript; charset=utf-8',
    'json' => 'application/json; charset=utf-8',
    'svg'  => 'image/svg+xml',
    'png'  => 'image/png',
    'jpg', 'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
    'ico'  => 'image/x-icon',
    default => mime_content_type($file) ?: 'application/octet-stream',
};
header('Content-Type: ' . $mime);
readfile($file);
return true;
