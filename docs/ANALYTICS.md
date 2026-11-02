# Journal d'événements et tableau de bord administrateur

## Ce qui est enregistré (étape 1, en place)
Table `events` : `user_id`, `type`, `path`, `device`, `created_at`.
- Types : `signup`, `login`, `logout` (événements de compte, nécessaires au fonctionnement et à la sécurité).
- `path` : le chemin seul, sans paramètres d'URL.
- `device` : seulement `mobile` ou `desktop`, déduit de l'en-tête User-Agent. Le texte complet n'est jamais gardé.
- Aucune adresse IP, aucun identifiant de session, aucun contenu saisi par l'utilisateur.
- `users.last_login_at` : date de la dernière connexion (affichée dans l'administration).

## Conservation
La tâche planifiée (`api/cron/run.php`) supprime les événements de plus de 90 jours (`job_purge_events`).

## Ce qui n'est pas encore en place
- Suivi des pages vues et des visiteurs non connectés : uniquement après consentement (bannière), étape 2.
- Tableau de bord complet (vue d'ensemble, fil d'activité, filtres, journal des accès admin) : étape 3.
- Toute nouvelle collecte impose de mettre à jour `docs/LEGAL.md` et la politique de confidentialité avant mise en production.
