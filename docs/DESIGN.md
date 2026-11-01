# Design : comment une maquette se branche sur le modèle

## Statut des styles du modèle : PROVISOIRES
Tous les styles livrés avec le modèle (couleurs, polices, rayons, ombres, mise en page des pages,
icônes, cadre d'iPhone) sont **des styles de test**. Ils servent uniquement à vérifier que les
interfaces fonctionnent et s'affichent correctement dans le modèle.

- Ils seront **entièrement remplacés** par les styles de la maquette que le designer fournit au début
  de chaque application (dossier `design/`).
- Ne pas les adapter ni les compléter pour une application réelle : les remplacer, en suivant la
  procédure ci-dessous et `docs/INTEGRATION.md`.
- Les fichiers concernés portent un bandeau « STYLES DE TEST » : `src/styles/tokens.css`,
  `src/styles/global.css`, `src/styles/device.css`.
- Le cadre d'iPhone (`device.css`, `DeviceFrame.astro`) est un outil de prévisualisation : il peut
  rester, mais ses couleurs et tailles doivent suivre la maquette.

Principe : le modèle fournit l'architecture (API, authentification, déploiement, logique).
Chaque application reçoit sa **maquette** du designer, qui fournit l'apparence.

## Le contrat à respecter (pour le designer et pour le développeur)
1. **Tokens uniquement** : couleurs, polices, rayons, espacements et ombres sont des variables CSS
   définies dans `src/styles/tokens.css`. Aucune couleur écrite en dur dans une page.
2. **Deux thèmes** : le thème sombre est la base ; le thème clair est défini par `html[data-theme="light"]`.
   Chaque maquette livre les deux, ou la règle de conversion.
3. **Contraste** : texte normal au moins 4,5:1, grands titres et montants au moins 3:1, dans les deux thèmes.
4. **Mobile d'abord** : tout doit tenir sur 393 × 852 pt, sans défilement horizontal.
5. **Composants nommés** : chaque élément réutilisé (carte, ligne, bouton, en-tête, barre d'onglets,
   notification, feuille de saisie) a une classe CSS et une seule.
6. **Mouvements** : ouverture et fermeture par la hauteur, barre d'onglets qui se replie et se déplie,
   notification centrée. Voir docs/UI.md.

## Intégrer une maquette (procédure)
1. Recopier les valeurs de la maquette dans `tokens.css` : couleurs (sombre et clair), rayons, polices.
2. Pour chaque composant, créer une classe dans `src/styles/global.css` avec les valeurs exactes.
3. Appliquer les classes dans les pages (`src/pages/`, `src/layouts/`).
4. Comparer les captures : maquette et application, au même format (393 × 852), dans les deux thèmes.
5. Consigner la maquette dans `design/` (fichier HTML source, daté) et noter la version dans le CHANGELOG.

## Ce qui ne se copie pas
- Les données fictives de la maquette : l'application affiche les vraies données.
- Les règles métier affichées (budgets, totaux) : elles viennent de l'API.
