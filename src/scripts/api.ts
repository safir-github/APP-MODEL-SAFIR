// Client d'API : toutes les requêtes vers /api/*.php, avec la session et le cookie « Rester connecté ».
const API_BASE = '';

export type ApiResult = { ok: boolean; status: number; data: any };

async function request(path: string, opts: RequestInit = {}): Promise<ApiResult> {
  const res = await fetch(`${API_BASE}${path}`, {
    credentials: 'include',
    ...opts,
    headers: { 'Content-Type': 'application/json', ...(opts.headers || {}) },
  });
  let data: any = null;
  try {
    data = await res.json();
  } catch {
    data = null;
  }
  return { ok: res.ok, status: res.status, data };
}

export const api = {
  me: () => request('/api/auth/me.php'),
  login: (email: string, password: string) =>
    request('/api/auth/login.php', { method: 'POST', body: JSON.stringify({ email, password }) }),
  register: (email: string, password: string) =>
    request('/api/auth/register.php', { method: 'POST', body: JSON.stringify({ email, password }) }),
  logout: () => request('/api/auth/logout.php', { method: 'POST' }),
  forgot: (email: string) =>
    request('/api/auth/forgot.php', { method: 'POST', body: JSON.stringify({ email }) }),
  reset: (token: string, password: string) =>
    request('/api/auth/reset.php', { method: 'POST', body: JSON.stringify({ token, password }) }),
  changePassword: (currentPassword: string, newPassword: string) =>
    request('/api/auth/change_password.php', {
      method: 'POST',
      body: JSON.stringify({ current_password: currentPassword, new_password: newPassword }),
    }),
  deleteAccount: (password: string) =>
    request('/api/auth/delete_account.php', { method: 'POST', body: JSON.stringify({ password }) }),
  updateProfile: (name: string) =>
    request('/api/auth/update_profile.php', { method: 'POST', body: JSON.stringify({ name }) }),
  requestEmailChange: (password: string, email: string) =>
    request('/api/auth/request_email_change.php', { method: 'POST', body: JSON.stringify({ password, email }) }),
  adminUsers: (q = '') => request(`/api/admin/users.php?q=${encodeURIComponent(q)}`),
  adminOverview: () => request('/api/admin/overview.php'),
  adminEvents: (params: Record<string, string>) =>
    request(`/api/admin/events.php?${new URLSearchParams(params).toString()}`),
  adminJournal: () => request('/api/admin/journal.php'),
  consent: (accept: boolean) =>
    request('/api/consent.php', { method: 'POST', body: JSON.stringify({ accept }) }),
  track: (path: string) =>
    request('/api/track.php', { method: 'POST', body: JSON.stringify({ path }) }),
  confirmEmail: (token: string) =>
    request('/api/auth/confirm_email.php', { method: 'POST', body: JSON.stringify({ token }) }),
};
