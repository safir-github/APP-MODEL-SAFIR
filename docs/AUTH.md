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

## Pourquoi un jeton en plus de la session
Les sessions PHP peuvent être nettoyées par l'hébergeur avant leur durée de vie, et les applications
installées sur l'écran d'accueil d'un iPhone perdent parfois leurs cookies. Le jeton en base
ne dépend pas du nettoyage des fichiers de session.

## Test rapide (local)
1. `curl -c jar -H 'Content-Type: application/json' -d '{"email":"...","password":"..."}' localhost:8000/api/auth/login.php`
2. Supprimer la ligne `PHPSESSID` du fichier `jar` : `api/auth/me.php` doit encore renvoyer l'utilisateur.
