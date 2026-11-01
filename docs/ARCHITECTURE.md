# Architecture

- **Front** : Astro (pages statiques) + TypeScript. Le rendu des listes est fait en JavaScript
  (chaînes HTML) ; les styles viennent de Tailwind et de `src/styles/tokens.css`.
- **API** : PHP 8, un fichier par endpoint dans `api/`, réponses JSON (`json_out`, `fail`).
- **Base** : SQLite via PDO, fichier hors du dossier public (`DB_PATH` dans `config.local.php`).
  Le schéma est créé automatiquement à la première connexion (`db_init_schema`).
- **Déploiement** : build statique `dist/` + dossier `api/` envoyés en FTP (`deploy.py`).
  Aucun processus serveur à lancer : PHP et SQLite sont fournis par l'hébergeur.
- **Développement** : `npm run build` puis `php -S localhost:8000 dev-router.php`.

## Règles
- Un endpoint = un fichier, qui commence par `require __DIR__ . '/../bootstrap.php';`.
- Toute entrée utilisateur est validée côté serveur (`bad_request`).
- Toute écriture passe par une requête préparée (PDO).
- Une nouvelle colonne se fait par `ALTER TABLE ... ADD COLUMN` dans un `try/catch`.
