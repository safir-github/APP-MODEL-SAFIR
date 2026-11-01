# E-mails

Aucune application du modèle n'envoie encore d'e-mail. Options à trancher le moment venu :
- **Fonction `mail()` de PHP** : gratuite chez l'hébergeur, mais souvent classée spam sans SPF/DKIM.
- **Service d'envoi transactionnel** (API HTTP, avec clé dans `config.local.php`) : meilleure délivrabilité.
- Usages prévus : confirmation d'inscription, réinitialisation du mot de passe, alertes.

Décision à prendre avant d'en ajouter un : fournisseur, domaine d'expédition, modèles de messages.
Une fois choisi, documenter ici la configuration DNS (SPF, DKIM) et l'endpoint d'envoi.
