# Mot de passe oublié

Flux (déjà dans le modèle : `api/auth/forgot.php`, `api/auth/reset.php`) :
1. L'utilisateur saisit son e-mail. La réponse est **toujours la même**, que le compte existe ou non.
2. Si le compte existe : un jeton aléatoire de 32 octets est créé. Seule son empreinte sha256 est stockée,
   avec une expiration d'1 heure (table `password_resets`).
3. Le lien `APP_URL/reinitialiser?token=...` est envoyé par e-mail (texte brut).
4. La page de l'app appelle `reset.php` avec le jeton et le nouveau mot de passe (8 caractères minimum).
5. Le jeton est marqué comme utilisé : il ne fonctionne plus.

Points d'attention :
- Limitation : 20 demandes par 15 minutes et par IP (`rate_limit.php`).
- La page `/reinitialiser` (et `/mot-de-passe-oublie`) est dans le modèle : `src/pages/`. Une application peut la modifier, mais ne doit pas la supprimer.
- Nécessite `send_mail()` opérationnel (voir EMAIL.md).
