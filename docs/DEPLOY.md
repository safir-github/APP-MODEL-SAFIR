# Déploiement (hébergement mutualisé en FTP)

1. `npm run build` (génère `dist/`).
2. Renseigner `.env.deploy` (gitignoré) : `FTP_HOST`, `FTP_USER`, `FTP_PASS`, `REMOTE_ROOT`.
3. `python3 deploy.py` : envoie `dist/` à la racine du site et `api/` dans `api/`.
4. Vérifier : la page d'accueil répond en 200, et l'API répond (`api/auth/me.php` → JSON).

## Règles
- Ne jamais committer `.env.deploy`, `config.local.php`, la base SQLite ni un identifiant en clair.
- La base de production n'est jamais écrasée par le déploiement (`data/` et `*.db` sont exclus).
- Un timeout FTP se règle en relançant la commande une fois.
- La première fois : créer `api/config.local.php` sur le serveur, avec `DB_PATH` hors du site public.
