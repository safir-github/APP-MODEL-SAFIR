# Instructions pour l'agent (lire avant toute modification)

Ce dépôt est un **modèle d'application** : il sert de base à chaque nouvelle application.
Pour une application précise, ce modèle est combiné avec une **maquette HTML** du concept
(dossier `design/`). Suivre `docs/INTEGRATION.md` pour cette combinaison.

## Ce qu'il faut savoir
- Stack : Astro (sortie statique) + TypeScript + Tailwind v4 pour le front ; PHP natif + SQLite (PDO) pour l'API.
- Déploiement : FTP vers un hébergement mutualisé (o2switch) via `deploy.py`. Aucune commande serveur.
- Aucun secret dans le dépôt. Identifiants FTP dans `.env.deploy` (gitignoré), configuration serveur dans `api/config.local.php` (gitignoré).
- Les données de l'utilisateur ne doivent jamais être effacées ni réécrites par un déploiement.

## Règles non négociables
1. **Tokens, pas de couleurs en dur** : toute couleur, police, rayon ou ombre passe par `src/styles/tokens.css`.
2. **Deux thèmes** : sombre par défaut, clair via `html[data-theme="light"]`. Vérifier le contraste dans les deux.
3. **Une API = un fichier** dans `api/`, qui commence par `require __DIR__ . '/../bootstrap.php';`.
4. **Schéma** : toute nouvelle colonne se fait par `ALTER TABLE ... ADD COLUMN` dans un `try/catch`, dans `api/db.php`.
5. **Tâches automatiques** : uniquement via `api/cron/run.php` en ligne de commande, jamais par une URL avec un secret.
6. **E-mails** : passer par `send_mail()` (`api/lib/mail.php`). Une notification par personne et par 24 h au plus.
7. **Mouvements** : ne jamais mettre `transform` ou `animation` (fill `both`) sur un conteneur qui contient la barre d'onglets fixe.
8. **Journal** : toute amélioration réutilisable est notée dans `CHANGELOG.md`, avec la date.
9. **Commits** : message en français, ligne `Co-Authored-By` du modèle utilisé.

## Où trouver quoi
- Architecture : `docs/ARCHITECTURE.md`
- Authentification et « rester connecté » : `docs/AUTH.md`, `docs/PASSWORD_RESET.md`
- E-mails : `docs/EMAIL.md` ; tâches planifiées : `docs/SCHEDULED_JOBS.md`
- Interface, thèmes, mouvements : `docs/UI.md`
- Maquettes : `docs/DESIGN.md` ; intégration : `docs/INTEGRATION.md`
- Déploiement : `docs/DEPLOY.md`
- Tests : `docs/TESTS.md` ; premier déploiement d'une nouvelle application : `docs/PREMIER_PROJET.md`
