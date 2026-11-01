// Thème clair / sombre : sombre par défaut, choix mémorisé dans localStorage.
// Pour éviter le clignotement, placer aussi dans le <head> :
//   <script is:inline>document.documentElement.dataset.theme = localStorage.getItem('app-theme') || 'dark';</script>

export function setTheme(dark: boolean): void {
  document.documentElement.dataset.theme = dark ? 'dark' : 'light';
  localStorage.setItem('app-theme', dark ? 'dark' : 'light');
}

export function isDarkTheme(): boolean {
  return document.documentElement.dataset.theme !== 'light';
}
