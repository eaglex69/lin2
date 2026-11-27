/**
 * api.js — тонкий AJAX-клиент к PHP API.
 * Все запросы идут на /api/*, возвращают JSON {ok:true,...} | {ok:false,error}.
 * CSRF-токен приходит из /api/bootstrap и подставляется в каждый POST.
 */

let csrf = '';
let sessionId = '';
let sessionInUrl = false;

const SID_KEY = 'miniage.sid';

function storedSid() {
  try { return localStorage.getItem(SID_KEY) || ''; } catch { return ''; }
}

function storeSid(sid) {
  if (!sid) return;
  try { localStorage.setItem(SID_KEY, sid); } catch { /* приватный режим */ }
}

export function setSession(data) {
  if (data.csrf) csrf = data.csrf;
  if (data.session) {
    sessionId = data.session.id || '';
    sessionInUrl = !!data.session.in_url;
  }
}

/** sid для iframe-режима: cookie заблокированы — передаём параметром. */
function currentSid() {
  return sessionId || storedSid();
}

/**
 * sid передаём заголовком (работает всегда) и параметром ?s= (страховка
 * для iframe, где заголовки и cookie могут теряться).
 */
function withSession(url) {
  const sid = currentSid();
  if (!sid) return url;
  return url + (url.includes('?') ? '&' : '?') + 's=' + encodeURIComponent(sid);
}

export class ApiError extends Error {
  constructor(message, code, status) {
    super(message);
    this.code = code;
    this.status = status;
  }
}

async function request(method, url, body) {
  const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  const sid = currentSid();
  if (sid) headers['X-Miniage-Session'] = sid;
  const opts = { method, headers, credentials: 'same-origin' };
  if (method !== 'GET') {
    const payload = Object.assign({ _csrf: csrf }, body || {});
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(payload);
  }

  let res;
  try {
    res = await fetch(withSession(url), opts);
  } catch (e) {
    throw new ApiError('Нет связи с сервером', 'network', 0);
  }

  // Сервер всегда дублирует актуальный sid заголовком — сохраняем его
  const headerSid = res.headers.get('X-Miniage-Session');
  if (headerSid) { sessionId = headerSid; storeSid(headerSid); }

  let data = null;
  const text = await res.text();
  try { data = text ? JSON.parse(text) : {}; } catch { data = { ok: false, error: text.slice(0, 200) }; }

  if (data && data.csrf) csrf = data.csrf;
  if (!res.ok || (data && data.ok === false)) {
    throw new ApiError((data && data.error) || `HTTP ${res.status}`, (data && data.code) || 'http', res.status);
  }
  return data;
}

export const api = {
  get: (url) => request('GET', url),
  post: (url, body) => request('POST', url, body),

  bootstrap: () => request('GET', '/api/bootstrap'),
  login: (nick, pass) => request('POST', '/api/login', { nick, pass }),
  register: (data) => request('POST', '/api/register', data),
  logout: () => request('POST', '/api/logout'),

  zones: () => request('GET', '/api/zones'),
  zone: (key) => request('GET', `/api/zones/${encodeURIComponent(key)}`),
  battleStart: (mobId) => request('POST', '/api/battle/start', { mob_id: mobId }),
  battleAttack: (battleId) => request('POST', '/api/battle/attack', { battle_id: battleId }),
  battleFlee: (battleId) => request('POST', '/api/battle/flee', { battle_id: battleId }),
  battleCurrent: () => request('GET', '/api/battle/current'),
  move: (x, y, city) => request('POST', '/api/move', { x, y, city }),
  heal: () => request('POST', '/api/heal'),

  shop: (kind = 'all') => request('GET', `/api/shop?kind=${encodeURIComponent(kind)}`),
  buy: (code, qty = 1) => request('POST', '/api/shop/buy', { code, qty }),
  sell: (invId, qty = 1) => request('POST', '/api/shop/sell', { inv_id: invId, qty }),
  inventory: () => request('GET', '/api/inventory'),
  equip: (invId, equip = true) => request('POST', '/api/inventory/equip', { inv_id: invId, equip }),
  useItem: (invId) => request('POST', '/api/inventory/use', { inv_id: invId }),

  chat: (channel = 'global', after = 0) =>
    request('GET', `/api/chat?channel=${encodeURIComponent(channel)}&after=${after}`),
  chatSend: (text, channel = 'global') => request('POST', '/api/chat', { text, channel }),
  rating: () => request('GET', '/api/rating'),
  news: () => request('GET', '/api/news'),
};
