# Interface

## Thème
- Sombre par défaut. Clair via `<html data-theme="light">`. Choix mémorisé (`localStorage`).
- Les couleurs passent par des variables CSS (`--bg`, `--text`, `--surface`, `--line`, `--tab-bg`…).
  Éviter les couleurs écrites en dur, sinon le thème clair ne les voit pas.
- Contraste : vérifier chaque en-tête dans les deux thèmes (texte, montants, boutons).

## Notifications
- Une seule notification à la fois, centrée sur l'écran, qui disparaît seule.
- Succès, information, erreur : même forme, couleur d'icône différente.
- Erreurs : toujours affichées, jamais silencieuses.

## Mouvements
- Ouverture / fermeture d'un bloc : animer la hauteur (Web Animations API), avec un délai de secours
  pour terminer même si l'animation est suspendue.
- Accordéon : ouvrir un bloc referme les autres du même groupe.
- Changement d'onglet : la barre se replie en rond, un chargement d'au moins 1 s s'affiche, puis la barre se déplie.
- Éviter `requestAnimationFrame` pour déclencher des états visibles : préférer un court `setTimeout`.
- Ne jamais laisser une `transform` ou une `animation` avec `fill-mode: both` sur un conteneur qui
  contient un élément `position: fixed` (la barre d'onglets) : elle ne serait plus fixée à l'écran.

## Feuilles de saisie
- Une feuille monte du bas de l'écran, avec une poignée. Un pavé numérique 3 colonnes pour les montants.
