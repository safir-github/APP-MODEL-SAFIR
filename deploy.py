#!/usr/bin/env python3
"""
Déploiement FTP de Ma Compta IA (architecture statique + PHP) vers o2switch.

Usage :
  python3 deploy.py            # Upload dist/ + api/

Architectures prises en charge par ce script :
  - Frontend statique : le dossier dist/ (généré par `npm run build`)
  - Backend PHP       : le dossier api/ (endpoints + includes)
  - Aucun build côté serveur, aucun module natif : PHP + SQLite (PDO) sont
    déjà disponibles chez o2switch. On ne UPLOADE JAMAIS node_modules, .env,
    ni le fichier SQLite (data/).

Identifiants : lus depuis .env.deploy (gitignoré), jamais en clair dans le script.
"""

import os
import sys
import ftplib
from pathlib import Path

ROOT = Path(__file__).parent.resolve()

# Exclusions systématiques : secrets, données locales, build artifacts, deps.
# NB : api/lib DOIT être uploadé (contient currency.php, ai.php, helpers_tx.php).
EXCLUDE_DIRS = {
    '.git', 'node_modules', '__pycache__', '.astro', 'data', '.vscode', '.idea',
}
EXCLUDE_FILES = {
    '.env', '.env.deploy', '.env.deploy.example', '.env.example',
    '.env.production', '.env.local',
    'compta.db', 'compta.db-shm', 'compta.db-wal', 'database.sqlite',
    'config.local.php',  # secrets serveur — ne pas écraser la prod
    '.DS_Store',
    'exemple-deploy.py',
}


def load_env(path: Path) -> dict:
    env = {}
    if not path.exists():
        return env
    for line in path.read_text(encoding='utf-8').splitlines():
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, v = line.split('=', 1)
        env[k.strip()] = v.strip().strip('"').strip("'")
    return env


env = load_env(ROOT / '.env.deploy')
for k in ('FTP_HOST', 'FTP_USER', 'FTP_PASS', 'REMOTE_ROOT'):
    if os.environ.get(k):
        env[k] = os.environ[k]

FTP_HOST = env.get('FTP_HOST')
FTP_USER = env.get('FTP_USER')
FTP_PASS = env.get('FTP_PASS')
REMOTE_ROOT = env.get('REMOTE_ROOT', '/ma-compta-ia.oclavier.fr').rstrip('/')

if not all([FTP_HOST, FTP_USER, FTP_PASS]):
    print('ERREUR : FTP_HOST / FTP_USER / FTP_PASS manquants.')
    print('Remplis .env.deploy (voir .env.deploy.example).')
    sys.exit(1)


def ensure_remote_dir(ftp: ftplib.FTP, path: str):
    try:
        ftp.cwd(path)
        return
    except ftplib.error_perm:
        pass
    parent = '/'.join(path.rstrip('/').split('/')[:-1]) or '/'
    ensure_remote_dir(ftp, parent)
    ftp.cwd(parent)
    try:
        ftp.mkd(path)
    except ftplib.error_perm:
        pass
    ftp.cwd(path)


def upload_file(ftp: ftplib.FTP, local_path: str, remote_name: str):
    print(f'  ↑ {remote_name}')
    with open(local_path, 'rb') as f:
        ftp.storbinary(f'STOR {remote_name}', f)


def upload_dir(ftp: ftplib.FTP, local_dir: str, remote_dir: str):
    print(f'→ {remote_dir}/')
    ensure_remote_dir(ftp, remote_dir)
    for item in sorted(os.listdir(local_dir)):
        ftp.cwd(remote_dir)  # S'assurer qu'on est dans le bon dossier à chaque itération
        local_path = os.path.join(local_dir, item)
        if os.path.isdir(local_path):
            if item in EXCLUDE_DIRS:
                print(f'  ⊘ {item}/ (exclu)')
                continue
            upload_dir(ftp, local_path, f'{remote_dir}/{item}')
        elif os.path.isfile(local_path):
            if item in EXCLUDE_FILES:
                print(f'  ⊘ {item} (exclu)')
                continue
            upload_file(ftp, local_path, item)


def main():
    local_dist = ROOT / 'dist'
    local_api = ROOT / 'api'
    if not local_dist.is_dir():
        print("ERREUR : dossier dist/ absent. Lance d'abord : npm run build")
        sys.exit(1)
    if not local_api.is_dir():
        print('ERREUR : dossier api/ absent.')
        sys.exit(1)

    print(f'Connexion FTP à {FTP_HOST} ...')
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, 21, timeout=30)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.set_pasv(True)
    print('Connecté.')

    print(f'\n[1/2] Upload de dist/ → {REMOTE_ROOT}/')
    # Les fichiers de dist/ vont à la racine du sous-domaine (servis directement).
    upload_dir(ftp, str(local_dist), REMOTE_ROOT)

    print(f'\n[2/2] Upload de api/ → {REMOTE_ROOT}/api')
    upload_dir(ftp, str(local_api), f'{REMOTE_ROOT}/api')

    ftp.quit()
    print('\n✓ Déploiement terminé.')
    print('Actions post-déploi (une seule fois) :')
    print(f'  1. Crée api/config.local.php sur le serveur avec :')
    print("     <?php define('ZAI_API_KEY', '...');")
    print("     define('DB_PATH', '/home/USER/var/compta/compta.db');  // HORS public_html")
    print('  2. Assure-toi que le dossier parent de DB_PATH est inscriptible.')
    print("  3. C'est tout — aucune commande serveur à lancer (PHP + SQLite natifs).")


if __name__ == '__main__':
    main()
