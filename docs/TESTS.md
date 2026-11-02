# Tests automatiques

Lancer : `npm test` (ou `php tests/run.php`). Aucune dépendance à installer, PHP suffit.

Prérequis :
- PHP en ligne de commande. `npm test` appelle `php` : il doit être dans le PATH. Le script lance les sous-processus
  avec le binaire PHP qui l'exécute (`PHP_BINARY`), donc `php tests/run.php` fonctionne même si `php` n'est pas dans le PATH.
- Le port 8099 doit être libre (serveur de test).

Base de test : le script crée une base SQLite temporaire et la passe au serveur de test par la variable
d'environnement **`APP_DB_PATH`** (lue par `api/config.php`). Cette variable n'est jamais à définir en production ;
la base de production est fixée par `DB_PATH` dans `api/config.local.php` (voir `config.local.php.example`).

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

## Vérification dans le navigateur (à refaire après chaque changement d'interface)
Tests lancés à la main sur `php -S localhost:8000 dev-router.php` après `npm run build`.

**Ordinateur (1024 × 768), cadre d'iPhone affiché**
- [ ] Connexion : œil du mot de passe, bascule de thème, copie du lien, partage (repli : copie), recharger.
- [ ] Connexion avec un mauvais mot de passe : message d'erreur ; avec le bon : redirection vers l'accueil.
- [ ] Accueil : barre d'onglets en bas, fixe pendant le défilement du contenu.
- [ ] Compte : nom, changement d'e-mail (mauvais puis bon mot de passe), changement de mot de passe (mauvais puis bon), suppression du compte.
- [ ] Mot de passe oublié, réinitialisation (deux mots de passe différents, puis identiques), confirmation d'e-mail sans jeton : messages corrects.
- [ ] Déconnexion ; `/compte` sans connexion redirige vers `/login?from=/compte`.

**Mobile (375 × 812)**
- [ ] Pas de cadre d'iPhone ; contenu sans défilement horizontal.
- [ ] Barre d'onglets fixée en bas de la fenêtre, pendant le défilement.
- [ ] Mêmes parcours que sur ordinateur.

Limites : l'envoi réel des e-mails et l'affichage sur un vrai téléphone ne sont pas vérifiés ici (voir docs/PREMIER_PROJET.md).
