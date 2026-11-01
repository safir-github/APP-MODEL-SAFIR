# Liste de contrôle : premier déploiement d'une application

À faire une fois, sur la première application réelle créée à partir du modèle. Ces contrôles ne se font pas dans le modèle lui-même, qui n'est pas un site en ligne.

## 1. Création
- [ ] Copier le modèle sans le dossier `.git`, renommer `package.json` et les titres des pages.
- [ ] Ajouter les tables propres au projet dans `api/db.php` (`db_init_schema`).
- [ ] `npm install`, `npm run build`, `npm test` : tout passe.

## 2. Maquette (si elle existe)
- [ ] Suivre `docs/INTEGRATION.md`, étape par étape. Noter ce qui bloque, et corriger la procédure dans le modèle.
- [ ] Comparer chaque écran à la maquette, à 393 × 852, dans les deux thèmes.

## 3. Hébergement (o2switch)
- [ ] Créer `.env.deploy` (identifiants FTP, jamais commités) et `api/config.local.php` sur le serveur, avec `DB_PATH` hors du site public.
- [ ] `python3 deploy.py`, puis vérifier : la page d'accueil répond 200, `api/auth/me.php` renvoie du JSON.
- [ ] Tester le parcours complet : inscription, déconnexion, connexion, « Rester connecté » (fermer et rouvrir le navigateur).

## 4. E-mails (`docs/EMAIL.md`)
- [ ] Renseigner `MAIL_FROM` et `APP_URL` dans `config.local.php`.
- [ ] Configurer SPF et DKIM dans cPanel pour le domaine.
- [ ] Envoyer un vrai mot de passe oublié et un changement d'adresse à une boîte de test. Vérifier qu'ils n'arrivent pas en spam.
- [ ] Décider : garder `mail()` ou passer à un service transactionnel. Noter la décision dans `docs/EMAIL.md`.

## 5. Tâches planifiées (`docs/SCHEDULED_JOBS.md`)
- [ ] Ajouter la tâche cron en ligne de commande dans cPanel.
- [ ] Le lendemain, vérifier la sortie et la table `notifications_log`.
- [ ] Vérifier que `api/cron/run.php` renvoie 403 par le web.

## 6. Clôture
- [ ] Noter dans le CHANGELOG du modèle toute amélioration réutilisable découverte pendant ce projet, avec la date.
- [ ] Remplacer l'ancien dossier `APP-MODEL/` du projet par une copie du modèle, si on en a un.
