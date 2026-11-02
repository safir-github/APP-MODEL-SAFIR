# Pages légales : conditions d'utilisation et politique de confidentialité

Le modèle fournit les deux pages (`/conditions` et `/confidentialite`) et les liens depuis l'inscription
et « Mon compte ». Chaque application renseigne ses valeurs dans **`src/legal.ts`**. Les textes sont
communs ; seules les valeurs changent.

## Ce que contient le modèle
- `src/pages/conditions.astro` : conditions d'utilisation (objet, compte, usage, disponibilité, données, suppression, contact).
- `src/pages/confidentialite.astro` : politique de confidentialité (responsable, données, cookies, hébergement,
  accès de l'éditeur, services tiers, conservation, droits).
- `src/legal.ts` : les valeurs propres à l'application. Tant que `draft` vaut `true`, les pages affichent
  un avertissement « version de travail ».
- Les marqueurs « À RENSEIGNER » apparaissent tels quels dans les pages : une application ne doit pas être
  diffusée tant qu'il en reste.

## Procédure pour une nouvelle application
1. Renseigner `src/legal.ts` : éditeur, contact, hébergeur et pays, lois applicables, durées de conservation.
2. Lister les services tiers **réellement activés** (IA, dictée, envoi d'e-mails…) dans `thirdParties`.
   Si une fonction tierce est désactivée au lancement, ne pas la lister.
3. Relire les deux pages point par point, avec les fonctions réelles de l'application.
4. Faire valider le texte par un juriste (RGPD et lois du pays des utilisateurs) avant de passer `draft` à `false`.
5. Mettre `draft` à `false` et `lastUpdate` à la date de publication.

## Règle de mise à jour (obligatoire)
Toute modification qui touche aux données doit être reflétée dans les pages **avant la mise en production** :
- nouveau cookie, nouveau service tiers, nouvelle collecte (suivi, statistiques, journal d'événements) ;
- nouvelle durée de conservation, nouvelle sauvegarde ;
- accès nouveau de l'éditeur aux données.
Exemple : la mise en place d'un suivi d'utilisation rend fausse la phrase « Aucun cookie publicitaire » si elle
s'accompagne d'un cookie. Dans ce cas, mettre à jour la page **et** `lastUpdate`, puis relire.

## Liste de contrôle avant diffusion (à copier dans le projet)
- [ ] `src/legal.ts` : plus aucun « À RENSEIGNER ». Le test `npm test` échoue si `draft` vaut `false` avec un marqueur restant, et vérifie le build.
- [ ] `draft` vaut `false`, `lastUpdate` est la date de publication.
- [ ] Les services tiers listés correspondent aux fonctions réellement activées.
- [ ] Les durées de conservation sont vraies, sauvegardes comprises.
- [ ] Les pages sont accessibles depuis l'inscription, et depuis « Mon compte ».
- [ ] Texte relu, et validé par un juriste pour les lois applicables.
- [ ] Demande d'accès et de suppression : le contact répond (un test réel).

## Limites
- Ces textes ne sont pas un avis juridique. Ils décrivent le fonctionnement du modèle ; une application doit
  les vérifier contre son propre fonctionnement.
- Pour les utilisateurs au Maroc, la loi 09-08 et la CNDP sont mentionnées dans `laws` : à compléter selon le projet.
