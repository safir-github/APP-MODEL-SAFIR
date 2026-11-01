// Actions des icônes des pages d'authentification (voir src/components/AuthTools.astro).
// Les boutons « afficher le mot de passe » sont reliés par data-toggle-password="<id du champ>".
import { isDarkTheme, setTheme } from './theme';

function flash(button: HTMLElement, text: string) {
  const previous = button.title;
  button.title = text;
  button.setAttribute('aria-label', text);
  setTimeout(() => {
    button.title = previous;
    button.setAttribute('aria-label', previous);
  }, 1800);
}

function refreshThemeIcon() {
  const dark = isDarkTheme();
  document.querySelector('[data-theme-icon="to-light"]')?.classList.toggle('hidden', !dark);
  document.querySelector('[data-theme-icon="to-dark"]')?.classList.toggle('hidden', dark);
}

async function share(button: HTMLElement) {
  const data = { title: document.title, text: document.title, url: window.location.origin };
  if (navigator.share) {
    try {
      await navigator.share(data);
    } catch {
      // Partage annulé par l'utilisateur : rien à faire
    }
    return;
  }
  await copyText(window.location.origin, button, 'Lien de l’application copié');
}

async function copyText(text: string, button: HTMLElement, message: string) {
  try {
    await navigator.clipboard.writeText(text);
    flash(button, message);
  } catch {
    window.prompt('Copiez ce lien :', text);
  }
}

document.addEventListener('click', async (event) => {
  const target = event.target as HTMLElement;

  const toggle = target.closest<HTMLButtonElement>('[data-toggle-password]');
  if (toggle) {
    const input = document.getElementById(toggle.dataset.togglePassword || '') as HTMLInputElement | null;
    if (!input) return;
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    toggle.setAttribute('aria-label', showing ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    toggle.querySelector('[data-eye="open"]')?.classList.toggle('hidden', !showing);
    toggle.querySelector('[data-eye="closed"]')?.classList.toggle('hidden', showing);
    return;
  }

  const button = target.closest<HTMLButtonElement>('[data-action]');
  if (!button) return;

  switch (button.dataset.action) {
    case 'reload':
      window.location.reload();
      break;
    case 'share':
      await share(button);
      break;
    case 'copy-link':
      // Lien direct vers la page courante (connexion ou inscription)
      await copyText(window.location.href, button, 'Lien copié');
      break;
    case 'theme':
      setTheme(!isDarkTheme());
      refreshThemeIcon();
      break;
  }
});

refreshThemeIcon();
