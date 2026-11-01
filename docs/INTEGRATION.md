# Intégrer une maquette HTML dans le modèle (procédure pour l'agent)

Entrées : ce dépôt (le modèle) + un fichier HTML autonome de maquette, dans `design/`.
Sortie : une application qui garde toute l'architecture du modèle et reprend l'apparence de la maquette.

## Étape 1 : lire
1. `CLAUDE.md`, `README.md`, `CHANGELOG.md`.
2. `docs/*.md` (architecture, authentification, interface, maquettes).
3. Le fichier HTML de la maquette, en entier : repérer le bloc `<style>` (tokens et composants),
   les écrans (`<section>` ou équivalents), et les données fictives dans `<script>`.

## Étape 2 : extraire les tokens
Avant tout : les styles du modèle sont **provisoires** (voir docs/DESIGN.md). Les valeurs de test
de `tokens.css` sont remplacées par celles de la maquette, sans en garder aucune.
Pour chaque variable CSS de la maquette (dans `:root` et `[data-theme=...]`) :
- la reporter dans `src/styles/tokens.css` avec le même nom ;
- si la maquette a un thème clair et un thème sombre, les deux sont reportés.
Ne pas renommer les tokens du modèle ; ajouter ceux qui manquent.

## Étape 3 : extraire les composants
Pour chaque classe de composant de la maquette (carte, ligne, groupe, bouton, en-tête…) :
- recopier les valeurs exactes (padding, rayon, taille et graisse de police, opacité) dans `src/styles/global.css` ;
- garder le même nom de classe, préfixé si nécessaire pour éviter les collisions.

## Étape 4 : écrans
Pour chaque écran de la maquette :
- créer une page dans `src/pages/` (ou une vue dans la page principale si c'est une sous-vue) ;
- remplacer les données fictives par des appels à `src/scripts/api.ts` ;
- garder la garde d'authentification (`me.php`) sur les pages protégées.
Les pages de connexion, d'inscription et d'accueil existent déjà : les adapter sans casser leurs identifiants
(`auth-form`, `submit-btn`, `password-input`, `error-msg`).

## Étape 5 : données et API
- Une donnée de la maquette devient une table ou une colonne (`api/db.php`) et un endpoint (`api/…`).
- Aucune donnée fictive ne reste dans le code final.
- Les calculs (totaux, pourcentages) sont faits côté serveur ou dans une fonction nommée et testée.

## Étape 6 : vérification (obligatoire)
1. `npm run build` sans erreur.
2. `php -l` sur chaque fichier PHP modifié.
3. Lancer en local (`npm run build` puis `php -S localhost:8000 dev-router.php`) et comparer chaque écran avec la maquette, à 393 × 852, dans les deux thèmes.
4. Vérifier le contraste dans les deux thèmes.
5. Tester un parcours : inscription, connexion, action principale, déconnexion.
6. Ajouter une entrée au `CHANGELOG.md` pour chaque amélioration réutilisable.

## Ce qu'il ne faut pas faire
- Ne pas recopier les données fictives de la maquette.
- Ne pas mettre de couleur en dur dans les pages.
- Ne pas modifier `api/auth/`, `api/lib/mail.php` ou `api/cron/` sans raison documentée : ce sont les parties communes.
- Ne pas inventer une fonctionnalité absente de la maquette ni du modèle sans la signaler à l'utilisateur.
