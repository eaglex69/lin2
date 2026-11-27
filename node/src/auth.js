'use strict';
/**
 * auth.js — порт inc/auth.php + enter.php
 *
 * PHP: сессия $_SESSION['nick']/['password'] (md5), cookie nick/password
 *      на 3 часа при "Запомнить меня", активный персонаж account.active.
 * Node: та же логика, сессия — cookie sid + Map в памяти сервера.
 *
 * ВАЖНО (превью в iframe): браузеры блокируют сторонние (third-party) cookie,
 * если игра открыта во фрейме. Поэтому помимо cookie sid передаётся в URL
 * параметром ?s=<sid> (см. layout.link), и сессия восстанавливается из него.
 * Cookie по-прежнему работает и переиздаётся при первой возможности.
 */
const { Q, md5, saveUser, db, save } = require('./db');
const { regeneration, checkLevel } = require('./func');

const sessions = new Map(); // sid -> {nick, password}
let sidSeq = 0;
function newSid() { return 's' + (++sidSeq) + '_' + Date.now().toString(36); }

function parseCookies(req) {
  const out = {};
  const h = req.headers.cookie || '';
  h.split(';').forEach(p => {
    const i = p.indexOf('=');
    if (i > 0) {
      const k = p.slice(0, i).trim();
      const v = p.slice(i + 1).trim();
      try { out[k] = decodeURIComponent(v); } catch (e) { out[k] = v; }
    }
  });
  return out;
}

/**
 * Аналог inc/auth.php: возвращает контекст запроса.
 * ctx = { user_id: 0|1, auth, account, uid, active, udata (персонаж), log, pas, sid, ses_in_url }
 */
function auth(req, query) {
  const q = query || {};
  const cookies = parseCookies(req);
  let nick = null, pas = null, sid = null;

  if (cookies.sid && sessions.has(cookies.sid)) {
    sid = cookies.sid;
  } else if (q.s && sessions.has(q.s)) {
    // Резервный путь: sid из URL (cookie заблокированы в iframe)
    sid = q.s;
  } else if (cookies.nick && cookies.password) {
    nick = cookies.nick; pas = cookies.password;
  }
  if (sid) { nick = sessions.get(sid).nick; pas = sessions.get(sid).password; }

  const ctx = {
    user_id: 0, auth: 0, account: null, uid: 0, active: 0, udata: null,
    log: '', pas: pas || '', sid: sid || null, cookies,
    ses_in_url: !!(sid && !cookies.sid), // нужно переиздать cookie
  };

  if (nick && pas) {
    const acc = Q.accountByNick(nick);
    if (acc && acc.pass === pas) {
      ctx.user_id = 1; ctx.auth = 1; ctx.account = acc; ctx.uid = acc.id;
      ctx.active = acc.active || 0;
      if (ctx.active) {
        const u = Q.userById(ctx.active);
        if (u) {
          ctx.udata = u;
          ctx.log = u.usr;
          // inc/auth.php: lvisit + online + регенерация + проверка уровня
          const d = new Date();
          u.lvisit = d.toLocaleDateString('ru-RU', { day: '2-digit', month: 'long', year: 'numeric' }) + ' ' + d.toLocaleTimeString('ru-RU');
          const t = Math.floor(Date.now() / 1000);
          const on = db.online.find(o => o.usr === u.usr);
          if (on) on.laikas = t; else db.online.push({ usr: u.usr, laikas: t });
          regeneration(u);
          checkLevel(u);
          saveUser(u);
        }
      }
    }
  }
  return ctx;
}

/** Заголовки Set-Cookie для сессии (используется при входе и при переиздании). */
function sessionCookie(sid, mem) {
  const list = [`sid=${sid}; Path=/; HttpOnly; SameSite=Lax`];
  if (mem) {
    const s = sessions.get(sid);
    if (s) {
      list.push(`nick=${encodeURIComponent(s.nick)}; Path=/; Max-Age=${3600 * 3}; HttpOnly; SameSite=Lax`);
      list.push(`password=${s.password}; Path=/; Max-Age=${3600 * 3}; HttpOnly; SameSite=Lax`);
    }
  }
  return list;
}

/**
 * Досылает cookie сессии, если она пришла в URL (?s=), но не в cookie.
 * Вызывается в конце обработки страницы.
 */
function reissue(ctx, res) {
  if (!ctx || !ctx.sid || !ctx.ses_in_url) return;
  if (res.headersSent) return;
  try { res.setHeader('Set-Cookie', sessionCookie(ctx.sid, false)); } catch (e) { /* уже отправлено */ }
}

/** enter.php: вход по логину/паролю (md5), опц. cookie на 3 часа */
function login(req, body) {
  let log = String(body.log || '').trim();
  const pas = md5(String(body.pas || ''));
  // enter.php: вход по ID аккаунта, если логин — число
  if (/^\d+$/.test(log)) {
    const acc = Q.accountById(Number(log));
    if (acc) log = acc.nick;
  }
  const acc = Q.accountByNick(log);
  if (acc && acc.pass === pas) {
    const sid = newSid();
    sessions.set(sid, { nick: log, password: pas });
    acc.lvisit = Math.floor(Date.now() / 1000);
    db.vhod.push({ usr: log, ip: clientIp(req), brow: req.headers['user-agent'] || '', data: new Date().toLocaleString('ru-RU') });
    save();
    return { ok: true, nick: log, sid, headers: { 'Set-Cookie': sessionCookie(sid, String(body.mem) === '1') } };
  }
  return { ok: false };
}

function logout(req, sidFromUrl) {
  const c = parseCookies(req);
  // Сессия могла прийти и cookie, и через ?s= — удаляем обе
  if (c.sid) sessions.delete(c.sid);
  if (sidFromUrl) sessions.delete(sidFromUrl);
  return {
    'Set-Cookie': [
      'sid=; Path=/; Max-Age=0; HttpOnly; SameSite=Lax',
      'nick=; Path=/; Max-Age=0',
      'password=; Path=/; Max-Age=0',
    ],
  };
}

function clientIp(req) {
  return (req.headers['x-forwarded-for'] || req.socket.remoteAddress || '').split(',')[0].trim();
}

module.exports = { auth, login, logout, parseCookies, clientIp, sessions, sessionCookie, reissue };
