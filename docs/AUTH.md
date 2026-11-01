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
