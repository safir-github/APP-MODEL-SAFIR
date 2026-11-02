// Consentement à la mesure d'audience et envoi des pages vues (uniquement si accepté).
import { api } from './api';

const KEY = 'app-consent';

export function initConsent(): void {
  const banner = document.getElementById('consent-banner');
  const choice = localStorage.getItem(KEY);

  if (choice === null) banner?.removeAttribute('hidden');

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
