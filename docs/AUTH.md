# Authentification

- Mot de passe haché avec `password_hash` (PASSWORD_DEFAULT).
- Session PHP : cookie `PHPSESSID` de 30 jours, httponly, SameSite=Lax, secure en HTTPS.
- **Rester connecté** : cookie `mc_remember` de 90 jours contenant un jeton aléatoire.
  - En base : seulement `sha256(jeton)`, la date d'expiration et l'utilisateur (table `auth_tokens`).
  - Chaque usage prolonge l'expiration (glissant). La déconnexion supprime le jeton et le cookie.
- `current_user()` : session, puis jeton si la session a disparu. Renvoie `null` sinon.
- `me.php` renvoie `{ ok: false, user: null }` (HTTP 200) quand personne n'est connecté.
- **Anti-force brute** : 20 tentatives de connexion échouées par 15 minutes et par IP (`rate_limit.php`).
- **Mot de passe oublié** : voir PASSWORD_RESET.md.
- **Changer le mot de passe** : `api/auth/change_password.php` (mot de passe actuel demandé, 8 caractères minimum).
  Les jetons « Rester connecté » des autres appareils sont supprimés ; l'appareil courant reste connecté.
- **Supprimer le compte** : `api/auth/delete_account.php` (mot de passe demandé). Supprime les jetons,
  le journal des e-mails et l'utilisateur ; les tables liées avec `ON DELETE CASCADE` suivent.
  **Pour chaque nouvelle table liée à `users`** : utiliser `ON DELETE CASCADE`, sinon l'ajouter à la liste de `delete_account.php`.
- **Nom** : `api/auth/update_profile.php` (100 caractères au plus, sans mot de passe).
- **Changer l'adresse e-mail** : `api/auth/request_email_change.php` (mot de passe demandé) envoie un lien à la **nouvelle** adresse ;
  `api/auth/confirm_email.php` applique le changement. Lien valable 1 h, usage unique, une demande en attente à la fois.
- Pages du front : `/login`, `/register`, `/mot-de-passe-oublie`, `/reinitialiser?token=…`, `/compte`, `/confirmer-email?token=…`.

## Pourquoi un jeton en plus de la session
Les sessions PHP peuvent être nettoyées par l'hébergeur avant leur durée de vie, et les applications
installées sur l'écran d'accueil d'un iPhone perdent parfois leurs cookies. Le jeton en base
ne dépend pas du nettoyage des fichiers de session.

## Test rapide (local)
1. `curl -c jar -H 'Content-Type: application/json' -d '{"email":"...","password":"..."}' localhost:8000/api/auth/login.php`
2. Supprimer la ligne `PHPSESSID` du fichier `jar` : `api/auth/me.php` doit encore renvoyer l'utilisateur.

## Rôle administrateur
- Colonne `users.is_admin` (0 ou 1), ajoutée par `ALTER TABLE` dans `api/db.php`. Aucune adresse e-mail n'est écrite dans le code.
- Attribution **uniquement en ligne de commande** : `php api/cli/make_admin.php adresse@exemple.fr` (ajouter `--revoke` pour retirer).
  Le script refuse toute exécution par le web (403). Sur l'hébergement, le lancer depuis le terminal cPanel ou en SSH.
- `require_admin()` (`api/helpers.php`) vérifie le rôle **à chaque requête**, côté serveur. Les endpoints admin vivent dans `api/admin/`.
- `api/auth/me.php` renvoie `is_admin` : le front s'en sert seulement pour **afficher** le lien « Administration » (page `/admin`).
  Cacher un élément n'est pas une protection : seule la vérification serveur protège les données.
- Une application ajoute ses propres pages admin en créant un endpoint dans `api/admin/` qui commence par `require_admin();`.

## Décisions de conception (à relire avant de reprendre le modèle)
- **Confirmation de l'adresse à l'inscription : non incluse.** L'inscription crée le compte immédiatement. La confirmation
  par lien existe seulement pour **changer** d'adresse (`request_email_change.php`, `confirm_email.php`). Une application qui
  exige la confirmation à l'inscription doit l'ajouter : même principe (jeton à usage unique, empreinte seule, lien valable 1 h).
  Raison : un parcours d'inscription sans étape supplémentaire, sauf décision contraire du projet.
- **Mot de passe oublié : la page `/reinitialiser` est fournie** par le modèle (`src/pages/reinitialiser.astro`), avec
  `/mot-de-passe-oublie`. Une application peut modifier leur apparence, pas les supprimer.
- **Limitation des tentatives** : présente sur connexion, mot de passe oublié, changement de mot de passe, suppression
  de compte et changement d'adresse. Toute application reprenant d'anciens endpoints (par exemple `update_password.php`)
  doit appliquer la même limite (`rate_limited()` / `record_attempt()`).
