# Journal d'événements, consentement et tableau de bord administrateur

## Vue d'ensemble
- **Journal des comptes** (`events`) : inscription, connexion, déconnexion. Nécessaire au fonctionnement et à la sécurité.
- **Pages vues** (`events`, type `page_view`) : uniquement après acceptation par le visiteur (bannière de consentement).
- **Journal des accès administrateur** (`admin_log`) : chaque consultation des données par un administrateur est notée.
- **Tableau de bord** (`/admin`, réservé aux administrateurs) : vue d'ensemble, comptes, activité, journal.

## Ce qui est enregistré
Table `events` : `user_id` (vide pour un visiteur non connecté), `type`, `path`, `device`, `created_at`.
- `path` : le chemin seul. Un chemin contenant `?`, `@`, ou une URL complète est refusé par le serveur.
- `device` : seulement `mobile` ou `desktop`, déduit de l'en-tête User-Agent. Le texte complet n'est jamais gardé.
- **Jamais** : adresse IP, identifiant de visiteur, identifiant de session, contenu saisi par l'utilisateur, jeton.
- `users.last_login_at` : date de la dernière connexion.

## Consentement
- Au premier accès, une bannière propose « Refuser » et « Accepter », avec le même poids visuel.
- Le choix est mémorisé dans le navigateur (`localStorage`, clé `app-consent`) et envoyé au serveur par le cookie
  `mc_consent` (180 jours, `1` ou `0`). Ce cookie ne sert qu'à retenir le choix.
- Sans `mc_consent=1`, `api/track.php` n'enregistre rien : il répond 204 sans écrire.
- Refuser n'a aucune conséquence sur l'usage de l'application.

## Conservation
La tâche planifiée (`api/cron/run.php`) supprime les événements et le journal des accès administrateur de plus de 90 jours (`job_purge_events`).

## Tableau de bord
- `api/admin/overview.php` : chiffres agrégés (comptes, inscriptions, comptes actifs, visites, appareils, pages les plus vues, 14 derniers jours).
- `api/admin/users.php?q=` : recherche de comptes.
- `api/admin/events.php` : activité, filtrable par type, période (1 à 90 jours) et compte. Consulter l'activité d'un compte
  est tracé dans `admin_log`.
- `api/admin/journal.php` : journal des accès administrateur.
- Tous les endpoints commencent par `require_admin()` : vérification côté serveur à chaque requête.

## Obligations à respecter pour chaque nouvelle collecte
Toute nouvelle donnée collectée (nouvel événement, nouveau champ, nouveau service) impose de :
1. mettre à jour `src/pages/confidentialite.astro` et `docs/LEGAL.md` avant la mise en production ;
2. vérifier que la donnée n'est pas un identifiant, une adresse IP ni un contenu saisi ;
3. ajouter le contrôle correspondant dans `tests/run.php`.
