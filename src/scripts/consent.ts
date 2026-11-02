// Consentement à la mesure d'audience et envoi des pages vues (uniquement si accepté).
//
// Règle : la bannière ne s'affiche qu'une seule fois. Dès qu'un choix (accepter ou refuser) est fait,
// elle ne réapparaît plus. Le choix est gardé dans le navigateur (localStorage) et répété au serveur
// par le cookie mc_consent. Si ce cookie a disparu alors que le choix existe, on le recrée en silence.
import { api } from './api';

const KEY = 'app-consent';

function serverCookieSet(): boolean {
  return document.cookie.split('; ').some((c) => c.startsWith('mc_consent='));
}

export async function initConsent(): Promise<void> {
  const banner = document.getElementById('consent-banner');
  const choice = localStorage.getItem(KEY);

  if (choice === null) {
    banner?.removeAttribute('hidden');
  } else if (!serverCookieSet()) {
    // Choix déjà fait, mais cookie absent (expiré ou effacé) : on le recrée sans rien afficher
    await api.consent(choice === 'accept');
  }

  banner?.addEventListener('click', async (event) => {
    const button = (event.target as HTMLElement).closest<HTMLButtonElement>('[data-consent]');
    if (!button) return;
    const accept = button.dataset.consent === 'accept';
    localStorage.setItem(KEY, accept ? 'accept' : 'refuse');
    await api.consent(accept);
    banner?.setAttribute('hidden', '');
    if (accept) sendPageView();
  });

  if (choice === 'accept') sendPageView();
}

// Une page vue = un chemin seul. Le serveur refuse tout envoi sans le cookie de consentement.
export function sendPageView(): void {
  api.track(window.location.pathname);
}
