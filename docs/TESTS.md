# Tests automatiques

Lancer : `npm test` (ou `php tests/run.php`). Aucune dépendance à installer, PHP suffit.

## Ce qui est testé
`tests/run.php` lance un serveur PHP sur une **base SQLite temporaire** (variable `APP_DB_PATH`),
envoie de vraies requêtes HTTP et vérifie les réponses :
- inscription, connexion, mauvais mot de passe ;
- « Rester connecté » : profil renvoyé sans session PHP, jeton de 90 jours, jeton stocké sous forme d'empreinte ;
- changement de mot de passe, et déconnexion des autres appareils ;
- mot de passe oublié (réponse identique pour un compte inconnu) et réinitialisation à usage unique ;
- suppression de compte (mot de passe demandé, jetons et journal supprimés) ;
- déconnexion ;
- tâche planifiée refusée par le web (403) et exécutée en ligne de commande.

Le script se termine par `Résultat : N réussi(s), M échec(s)` et un code de sortie 1 en cas d'échec.

## Quand l'exécuter
- Avant chaque commit qui touche `api/` (règle de `CLAUDE.md`).
- Après avoir ajouté un endpoint : ajouter son contrôle dans `tests/run.php`.

## Limites
- Les e-mails ne sont pas vérifiés (la fonction `mail()` ne fonctionne pas en local) : le jeton de réinitialisation est inséré directement en base par le test.
- Le front (pages Astro) n'est pas testé automatiquement : `npm run build` vérifie seulement qu'il compile.
- Un test de plus par projet : ajouter les contrôles propres à l'application dans le même fichier, après les contrôles du modèle.
