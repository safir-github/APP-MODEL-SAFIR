// Valeurs propres à chaque application, utilisées par /conditions et /confidentialite.
// Le modèle contient des marqueurs « À RENSEIGNER » : une application ne peut pas être publiée
// tant qu'il en reste (voir docs/LEGAL.md). Ne jamais écrire ici un secret ni une donnée personnelle réelle
// si le dépôt est public : dans un projet public, renseigner seulement des informations déjà publiques.
export const LEGAL = {
  appName: 'Mon app',
  // Personne physique ou morale qui édite l'application et répond des données
  editor: 'À RENSEIGNER : nom, statut et adresse de l’éditeur',
  contactEmail: 'À RENSEIGNER : adresse de contact',
  // Hébergeur et pays où sont stockées les données
  hosting: 'À RENSEIGNER : hébergeur et pays',
  // Lois applicables (par exemple RGPD pour la France, loi 09-08 pour le Maroc)
  laws: 'À RENSEIGNER : lois applicables aux utilisateurs',
  // Services tiers réellement activés (IA, envoi d'e-mails, etc.). Vide = aucun.
  thirdParties: [] as string[],
  // Durées de conservation réelles
  retentionAccount: 'Tant que le compte existe. Supprimées lorsque vous supprimez votre compte.',
  retentionBackups: 'À RENSEIGNER : durée de conservation dans les sauvegardes',
  // Date de dernière mise à jour des pages (format AAAA-MM-JJ)
  lastUpdate: '2026-11-02',
  // true tant que les textes n'ont pas été relus et validés : affiche un avertissement
  draft: true,
};
