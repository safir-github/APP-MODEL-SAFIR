# E-mails

## Méthode retenue : `mail()` de l'hébergeur
Utilisée dans Muslim Kit et Sourcing App. Gratuite, sans compte tiers, configurée par o2switch.
Le module est `api/lib/mail.php`, fonction `send_mail($to, $subject, $text)`.

### Réglages obligatoires
- `MAIL_FROM` (config.local.php) : expéditeur, par exemple `Mon App <contact@mon-domaine.fr>`.
- `APP_URL` : adresse publique de l'app, utilisée dans les liens.
- **Délivrabilité** : sans SPF et DKIM sur le domaine, les messages finissent souvent en spam.
  Vérifier dans cPanel (zone DNS) que l'enregistrement SPF autorise le serveur o2switch et que DKIM est activé.

### Règles d'envoi
- Une seule notification par personne et par type, au plus une fois par 24 heures : table `notifications_log`.
- Regrouper plusieurs rappels dans un seul e-mail (« digest ») plutôt qu'un e-mail par élément.
- Ne jamais envoyer d'e-mail pendant une requête web pour une tâche automatique : utiliser la tâche planifiée
  (voir SCHEDULED_JOBS.md).
- Texte brut pour les messages système (réinitialisation). Un gabarit HTML sobre peut être ajouté pour les rappels.

### Quand changer de méthode
Passer à un service transactionnel (API HTTP, clé dans config.local.php) si :
- les messages arrivent en spam malgré SPF/DKIM ;
- le volume dépasse quelques centaines d'e-mails par jour.
Dans ce cas, seul le corps de `send_mail()` change.
