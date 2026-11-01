# App Model — squelette de mes applications web

Dépôt modèle à partir duquel je démarre toutes mes applications web (Astro + PHP/SQLite,
déployées sur hébergement mutualisé). Il contient les choix déjà validés : architecture,
authentification « rester connecté », déploiement FTP, thème clair/sombre, notifications.

## Comment démarrer une nouvelle application
1. Copier ce dossier (sans le dossier `.git`) dans le nouveau projet.
2. Renommer le projet dans `package.json` et les textes des pages (`<title>`, marque).
3. Créer `api/config.local.php` à partir de `api/config.local.php.example` (jamais commité).
4. Installer et lancer en local :
   - `npm install` puis `npm run build`
   - `php -S localhost:8000 dev-router.php`
5. Ajouter les tables propres au projet dans `api/db.php` (fonction `db_init_schema`).
6. Déployer avec `deploy.py` (identifiants dans `.env.deploy`, jamais dans le dépôt).

## Ce qui est dans le modèle
- `api/` : configuration, connexion à la base, helpers, authentification (inscription,
  connexion, déconnexion, profil) et jeton « Rester connecté » de 90 jours.
- `src/styles/tokens.css` : couleurs, thème clair/sombre, barre d'onglets, notification.
- `src/scripts/theme.ts` : bascule du thème, mémorisée.
- `deploy.py` : envoi FTP de `dist/` et `api/`, identifiants lus dans l'environnement.
- `dev-router.php` : serveur PHP de développement.
- `src/` : socle front (mises en page, pages de connexion, inscription, accueil, client d'API).
- `api/cron/` et `api/lib/jobs.php` : tâches planifiées en ligne de commande.
- `CLAUDE.md` : instructions pour l'agent (lu automatiquement).
- `design/` : maquettes HTML des concepts (voir design/README.md).
- `docs/` : architecture, authentification, déploiement, interface, e-mails, tâches planifiées, mot de passe oublié, maquettes (DESIGN.md).

## Historique
Toutes les améliorations sont notées dans [CHANGELOG.md](CHANGELOG.md). Quand une amélioration
est reportée dans une application existante, la date et le nom du projet sont ajoutés.

## Règles
- Aucun secret dans le dépôt (mots de passe, clés API, identifiants FTP).
- Une seule source de vérité : corriger le modèle, puis reporter la correction dans les projets.
- Chaque amélioration importante : une entrée dans le CHANGELOG, avec un commit dédié.
