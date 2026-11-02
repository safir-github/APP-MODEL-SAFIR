# Workflow du modèle central

Le modèle central (dépôt `APP-MODEL-SAFIR`, dossier local `app-model`) est la source commune de tous les projets d'application. Ce document fixe qui fait quoi, dans quel ordre, et ce que chacun ne doit pas toucher. Il existe en deux exemplaires : celui-ci (côté Ma Compta IA) et celui que l'agent du modèle tient à jour dans le dépôt du modèle.

## Rôles

- **Agent du modèle central** : seul à écrire dans le dépôt du modèle. Il développe chaque fonctionnalité dans un commit isolé, pousse sur une branche dédiée, puis prévient l'agent de l'application.
- **Agent de Ma Compta IA** : ne modifie jamais le dépôt du modèle. Il récupère le travail depuis le modèle, le reporte dans Ma Compta IA, teste, committe sur la branche de travail, puis déploie.
- **Utilisateur** : décide de ce qui est développé et valide les étapes qui sortent du dépôt (push, merge, déploiement en production).

## Étapes d'une fonctionnalité

1. L'utilisateur demande une fonctionnalité à l'agent du modèle.
2. L'agent du modèle la développe dans **un commit isolé**, sur une branche dédiée (par exemple `1.x`, nouvelle version). Il ne mélange pas plusieurs fonctionnalités dans un même commit.
3. Il lance les tests du modèle et vérifie qu'ils passent.
4. Il pousse la branche sur GitHub. Il ne pousse pas sur `main` sans accord de l'utilisateur.
5. **Il prévient automatiquement l'agent de Ma Compta IA**, sans attendre l'utilisateur. Le message contient :
   - le nom de la branche et le hash du commit,
   - ce qui a été fait et pourquoi,
   - comment il a été fait (fichiers touchés, nouvelles colonnes, nouvelles routes),
   - ce qui n'a pas pu être vérifié.
6. L'agent de Ma Compta IA lit le commit dans le modèle (lecture seule), vérifie que le hash et le contenu correspondent au message, puis porte le changement.
7. L'agent de Ma Compta IA teste, committe sur sa branche de travail, puis déploie.
8. Il rend compte à l'utilisateur de ce qui a été repris, ce qui a été adapté et ce qui reste à faire.

## Règles

- Un commit du modèle = une fonctionnalité. Un commit de Ma Compta IA peut reprendre une ou plusieurs fonctionnalités du modèle, mais le message doit les citer avec leur hash.
- Ne jamais modifier le dépôt du modèle depuis Ma Compta IA. Une demande de changement passe par un message à l'agent du modèle.
- Ne jamais dire qu'une fonctionnalité est « faite » tant qu'elle n'est pas poussée et vérifiable dans le dépôt. Un message ne suffit pas.
- Si un hash annoncé n'existe pas dans le dépôt, l'agent de Ma Compta IA le signale immédiatement à l'utilisateur et ne porte rien.
- Les décisions de sécurité et de données (suppression, changement de rôle, export) sont validées par l'utilisateur avant d'être développées.

## Ce que Ma Compta IA reprend du modèle

Le dashboard admin en particulier : classification des comptes (`account_kind`), fiche détaillée par compte, affichage journalier. Ces fonctions doivent d'abord exister dans le modèle, dans un commit isolé, avant d'être reprises.
