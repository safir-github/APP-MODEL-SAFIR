# Démarrer une nouvelle application à partir du modèle

Cette procédure s'adresse à toi, et à tout agent qui crée une application à partir de ce modèle.
Elle explique comment récupérer le modèle, le copier dans un nouveau projet, et séparer l'historique Git
du modèle de celui de l'application. À suivre dans l'ordre. Ne pas sauter d'étape.

## Principe
- Le modèle (`app-model`) est un dépôt public, sur GitHub : `https://github.com/safir-github/APP-MODEL-SAFIR`.
- Chaque application est un **nouveau dépôt**, avec son propre historique Git.
- Le modèle n'est **jamais** modifié depuis une application. Il se fait évoluer dans son propre dépôt,
  puis les améliorations se répercutent dans les applications par copie et CHANGELOG (voir `README.md`).
- Ne jamais pousser une application vers le dépôt `APP-MODEL-SAFIR`.

## Étape 1 : choisir la version du modèle
- Branche à utiliser : **`main`**. C'est la version stable, celle que tout nouvel agent doit cloner.
- Les branches `1.x` (par exemple `1.1`) sont les branches de travail du modèle : elles reçoivent les prochaines
  modifications, puis sont fusionnées dans `main` par le responsable du modèle. Ne pas cloner une branche `1.x`
  pour démarrer un projet, sauf demande explicite.
- Vérifier que `main` contient bien la version attendue (après fusion) avant de cloner.
- Noter le numéro de version à copier, c'est-à-dire le hash court du dernier commit de `main` :

```bash
git ls-remote https://github.com/safir-github/APP-MODEL-SAFIR.git refs/heads/main
```

La première colonne est le hash complet. Garder les 7 premiers caractères pour la suite.

## Étape 2 : récupérer le modèle dans un dossier neuf
Choisir un dossier parent (par exemple `~/LOCAL MAC/DEV/`) et un nom pour la nouvelle application (par exemple `MON-APP`).
Le dossier ne doit pas exister.

```bash
cd "$HOME/LOCAL MAC/DEV"
git clone --branch main https://github.com/safir-github/APP-MODEL-SAFIR.git MON-APP
cd MON-APP
git log --oneline -1
```

La dernière commande doit afficher le commit de `main` noté à l'étape 1.

## Étape 3 : détacher l'historique du modèle
Le dossier `.git` contient tout l'historique du modèle. On le supprime pour que la nouvelle application
ne contienne aucun commit du modèle. Le hash noté à l'étape 1 sera gardé dans le premier commit.

```bash
cd "$HOME/LOCAL MAC/DEV/MON-APP"
MODELE_COMMIT=$(git rev-parse --short HEAD)
rm -rf .git
```

Vérifier : `ls -a` ne doit plus afficher `.git`.

## Étape 4 : créer le dépôt de l'application
```bash
git init -b main
git add .
git commit -m "Initialisation depuis le modèle app-model (commit ${MODELE_COMMIT})

Co-Authored-By: Claude Haiku 5.5 <noreply@anthropic.com>"
```

Remplacer `MODELE_COMMIT` par la valeur notée si la variable a été perdue (le shell est réinitialisé
entre deux sessions) : elle se lit dans le premier commit du modèle, ou dans l'étape 1.

## Étape 5 : relier l'application à son propre dépôt GitHub
Créer un dépôt **vide** sur GitHub, au nom de l'application, puis :

```bash
git remote add origin https://github.com/UTILISATEUR/MON-APP.git
git push -u origin main
```

Vérifier : `git remote -v` ne doit pointer que vers le dépôt de l'application, jamais vers `APP-MODEL-SAFIR`.

## Étape 6 : préparer le poste de développement
```bash
cd "$HOME/LOCAL MAC/DEV/MON-APP"
npm install
cp api/config.local.php.example api/config.local.php
```

- `api/config.local.php` reste dans `.gitignore` : il ne doit jamais être commité.
- Dans ce fichier, adapter `APP_URL` et `MAIL_FROM` au projet.
- Vérifier : `npm run build` et `npm test` passent avant toute modification.

## Étape 7 : placer la maquette
- Copier le fichier HTML de la maquette, fourni par le designer, dans `design/` (par exemple `design/maquette-2026-11-01.html`).
- Puis suivre `docs/INTEGRATION.md`, étape par étape.
- Rappel : les styles du modèle sont **provisoires** (voir `docs/DESIGN.md`). Le cadre d'iPhone, la barre d'onglets
  (position) et les icônes d'authentification (présence) sont **fixes** : ne pas les supprimer.

## Étape 8 : adapter le projet
- Renommer le projet dans `package.json` (champ `name`) et les titres des pages (`<title>`, marque).
- Ajouter les tables du projet dans `api/db.php` (fonction `db_init_schema`), par `ALTER TABLE ... ADD COLUMN` dans un try/catch.
- Ajouter les contrôles propres au projet dans `tests/run.php`, après ceux du modèle.
- Le fichier `CHANGELOG.md` peut être gardé : il décrit le modèle. Ajouter en tête une entrée propre à l'application.

## Étape 9 : ce qu'il ne faut jamais faire
- **Ne pas** pousser de commit de l'application vers `APP-MODEL-SAFIR`.
- **Ne pas** garder le dossier `.git` du modèle dans la copie (étape 3).
- **Ne pas** recopier des données réelles (utilisateurs, base SQLite, identifiants FTP) depuis un autre projet.
- **Ne pas** modifier `CLAUDE.md` sans l'accord de l'utilisateur.
- **Ne pas** modifier le modèle depuis une application. Si une amélioration du modèle est trouvée pendant le projet,
  la noter (date, raison) et la proposer à l'utilisateur ; elle sera faite dans le dépôt du modèle.

## Étape 10 : déployer
Une fois l'application prête, suivre `docs/PREMIER_PROJET.md` (hébergement, e-mails, tâche planifiée)
et `docs/DEPLOY.md`.

## Résumé en une page
1. `git clone --branch main <dépôt du modèle> MON-APP`
2. `rm -rf .git` (après avoir noté le hash du commit du modèle)
3. `git init -b main && git add . && git commit` (message : « Initialisation depuis le modèle … »)
4. `git remote add origin <dépôt de l'application>` puis `git push -u origin main`
5. `npm install`, `cp api/config.local.php.example api/config.local.php`, `npm run build`, `npm test`
6. Maquette dans `design/`, puis `docs/INTEGRATION.md`
7. Adapter le nom du projet, la base, les tests ; déployer avec `docs/PREMIER_PROJET.md`
