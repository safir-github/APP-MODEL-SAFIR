// Logique des pages login / register : soumission via fetch vers l'API PHP.
import { api } from './api';

type Mode = 'login' | 'register';

function getMode(): Mode {
  return window.location.pathname.includes('register') ? 'register' : 'login';
}

function show(id: string, on: boolean) {
  const el = document.getElementById(id);
  if (el) el.hidden = !on;
}

function setError(msg: string) {
  const el = document.getElementById('error-msg');
  if (!el) return;
  el.textContent = msg;
  el.hidden = !msg;
}

function init() {
  const form = document.getElementById('auth-form') as HTMLFormElement | null;
  const submitBtn = document.getElementById('submit-btn') as HTMLButtonElement | null;
  if (!form) return;
  const mode = getMode();

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    setError('');
    const fd = new FormData(form);
    const email = String(fd.get('email') || '').trim();
    const password = String(fd.get('password') || '');
    if (!email || !password) {
      setError('Merci de remplir les deux champs.');
      return;
    }
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.label = submitBtn.textContent || '';
      submitBtn.textContent = '...';
    }
    show('spinner', true);

    try {
      const { ok, data } =
        mode === 'register' ? await api.register(email, password) : await api.login(email, password);
      if (!ok) {
        throw new Error(data?.error || 'Échec de la connexion.');
      }
      const params = new URLSearchParams(window.location.search);
      const from = params.get('from') || '/';
      window.location.href = from;
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur inconnue.');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = submitBtn.dataset.label || '';
      }
      show('spinner', false);
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
