# Tâches planifiées (cron)

Les envois automatiques (rappels, séquences d'e-mails, nettoyage) ne se déclenchent pas quand
quelqu'un ouvre l'application : une tâche cron les lance à heure fixe, même si personne n'est connecté.

## Principe (à respecter)
- Le point d'entrée est `api/cron/run.php`, lancé **en ligne de commande** :
  `php /home/UTILISATEUR/chemin/vers/api/cron/run.php`
- Le script refuse toute exécution par le web (HTTP 403). **Aucun secret dans une URL.**
- Les tâches sont des fonctions dans `api/lib/jobs.php`, appelées depuis `run.php`.
- Chaque tâche doit être **idempotente** : la relancer ne doit pas renvoyer les mêmes e-mails
  (contrôle par `notified_within_24h()` / `mark_notified()`).

## Configuration sur o2switch (cPanel)
1. cPanel > **Tâches Cron** (Cron Jobs).
2. Fréquence : une fois par jour (`0 9 * * *` pour 9 h, par exemple).
3. Commande : `/usr/local/bin/php /home/UTILISATEUR/chemin/vers/api/cron/run.php > /dev/null 2>&1`
   (le chemin de PHP peut être vérifié dans cPanel ; le dossier `api/` doit être sur le serveur).
4. Vérifier le lendemain : la sortie JSON contient le nombre d'e-mails envoyés, et la table
   `notifications_log` se remplit.

## Ce qu'il faut éviter (leçons de Muslim Kit)
- Ne pas déclencher les rappels par une URL avec un jeton (`cron_reminders.php?token=...`) :
  le jeton se retrouve dans les journaux du serveur, dans l'historique du navigateur et dans les dépôts Git.
  Un jeton a été écrit dans un fichier de documentation du projet : **il doit être changé**.
- Ne pas envoyer un e-mail par élément en retard : utiliser un seul e-mail récapitulatif, avec une limite
  d'un e-mail par 24 heures et par personne.
- Ne pas envoyer à la connexion : l'ouverture de l'app ne doit jamais déclencher d'e-mails.

## Ajouter une tâche
1. Écrire `job_xxx()` dans `api/lib/jobs.php`, qui renvoie un tableau de résultats.
2. L'appeler dans `api/cron/run.php`, dans `$results`.
3. Tester en local : `php api/cron/run.php`.
