# Changelog

Toutes les améliorations du modèle, de la plus récente à la plus ancienne.
Format : date, puis ce qui a changé et pourquoi. Les reports dans les projets sont notés
sous « Reporté dans ».

## 2026-11-01 (icônes des pages d'authentification)

- Barre d'icônes sur connexion, inscription et réinitialisation : recharger, partager l'application,
  copier le lien de la page (lien direct vers connexion ou inscription), thème clair / sombre.
- Icône œil pour afficher / masquer le mot de passe. Pourquoi : demande de l'utilisateur, repris de Ma Compta IA.

## 2026-11-01 (nom et adresse e-mail)

- **Nom** : colonne `users.name` (ALTER TABLE), `api/auth/update_profile.php`. Pourquoi : présent dans Muslim Kit.
- **Changer l'adresse e-mail** : `request_email_change.php` et `confirm_email.php`, table `email_changes`.
  Le lien de confirmation part vers la nouvelle adresse, pour éviter une prise de compte. Présent dans Muslim Kit (sans confirmation).
- **Pages** : `/compte` (nom, e-mail) et `/confirmer-email?token=…`.
- Tests : 41 contrôles (`npm test`).

## 2026-11-01 (comptes, pages de mot de passe, tests)

- **Changer le mot de passe** : `api/auth/change_password.php`. Mot de passe actuel demandé, limite d'essais,
  et déconnexion des autres appareils (leurs jetons « Rester connecté » sont supprimés).
  Pourquoi : fonction présente dans Muslim Kit et Playhouse Lab.
- **Supprimer le compte** : `api/auth/delete_account.php`. Mot de passe demandé ; supprime jetons, journal des e-mails
  et utilisateur. Pourquoi : fonction présente dans Muslim Kit et Talib Notes.
- **Pages du front** : `/mot-de-passe-oublie` (lien depuis la connexion), `/reinitialiser?token=…` (manquante jusqu'ici),
  `/compte` (changer le mot de passe, supprimer le compte ; lien « Mon compte » sur l'accueil).
- **Tests automatiques** : `tests/run.php`, lancé par `npm test`. Serveur PHP sur base temporaire
  (`APP_DB_PATH`), vrais appels HTTP, 29 contrôles (« Rester connecté » 90 jours compris). Voir `docs/TESTS.md`.
- `api/config.php` : `DB_PATH` peut être donné par la variable d'environnement `APP_DB_PATH` (utilisé par les tests).

## 2026-11-01 (agent et maquettes)

- `CLAUDE.md` à la racine : règles et carte du dépôt, lues automatiquement par Claude.
- `docs/INTEGRATION.md` : procédure en six étapes pour fusionner le modèle et une maquette HTML.
- `design/README.md` : format attendu des maquettes.

## 2026-11-01 (socle front)

- Socle front : `package.json`, configuration Astro (sortie statique), TypeScript, Tailwind v4.
- Mises en page `AppLayout` et `AuthLayout` (thème initialisé avant le rendu).
- Pages : connexion, inscription, accueil avec garde d'authentification et déconnexion.
- Client d'API `src/scripts/api.ts` (session et cookie « Rester connecté » inclus).
- `docs/DESIGN.md` : contrat des maquettes (tokens, thèmes, contraste, composants) et procédure d'intégration.
- Correction : `me.php` ne dépend plus de la table `transactions` (propre à une application).

## 2026-11-01 (suite) : e-mails, tâches planifiées, sécurité des comptes

- **Tâches planifiées** : `api/cron/run.php`, lancé en ligne de commande par cron (cPanel o2switch).
  Refuse l'exécution par le web. Limite d'un e-mail par 24 h et par personne (`notifications_log`).
  Pourquoi : les rappels de Muslim Kit passaient par une URL avec un jeton (exposé dans un fichier du dépôt).
- **E-mails** : `api/lib/mail.php` (fonction `mail()` de l'hébergeur), `MAIL_FROM` et `APP_URL` configurables.
- **Mot de passe oublié** : `api/auth/forgot.php` et `reset.php`, jeton à usage unique, valable 1 h,
  seule son empreinte est stockée. Réponse identique que le compte existe ou non.
  Repris de Sourcing App.
- **Anti-force brute** : 20 tentatives de connexion par 15 min et par IP (`api/lib/rate_limit.php`).
  Repris de Sourcing App.
- Documentation : EMAIL.md, SCHEDULED_JOBS.md, PASSWORD_RESET.md.

## 2026-11-01

### Authentification : « Rester connecté » pendant 90 jours
- Table `auth_tokens` : seule l'empreinte sha256 du jeton est stockée.
- Connexion et inscription créent un cookie `mc_remember` (httponly, SameSite=Lax, 90 jours).
- Si la session PHP a disparu (hébergement, iOS), le jeton rouvre la session et se prolonge.
- La déconnexion supprime le jeton et son cookie.
- Pourquoi : sur iPhone, une application ajoutée à l'écran d'accueil perdait la connexion
  après quelques minutes, à cause de la durée de vie des sessions PHP sur l'hébergement.

### Interface
- Thème clair / sombre : attribut `data-theme` sur `<html>`, choix mémorisé, appliqué avant
  le premier rendu (pas de clignotement).
- Barre d'onglets : fond opaque, bordure et ombre nettes dans les deux thèmes.
- Barre d'onglets animée : se replie en rond pendant un changement d'onglet (les icônes disparaissent),
  puis se déplie en pilule ; même effet à l'arrivée sur l'application.
- Notification centrée qui disparaît seule (2,2 s ; 4,2 s pour une erreur).
- Erreurs non rattrapées (promesses rejetées, erreurs JavaScript) affichées à l'écran.
- Transition de chargement de 1 seconde minimum entre deux onglets (points animés, voile discret).
- Sections repliables en accordéon (une seule ouverte à la fois) avec animation de hauteur.

### Déploiement
- `deploy.py` : identifiants uniquement dans l'environnement ou `.env.deploy` (gitignoré).

## Modèle de départ
- Architecture Astro + PHP/SQLite, API JSON, sessions, base SQLite créée automatiquement.
- Schéma : table `users` ; migrations par `ALTER TABLE ... ADD COLUMN` dans un `try/catch`.
