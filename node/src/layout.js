'use strict';
/**
 * layout.js — порт inc/head.php + inc/zag.php + inc/down.php + inc/end.php
 * WAP XHTML Mobile 1.0, шапка с HP/MP/EXP-барами (как в zag.php), подвал-навигация.
 *
 * Ссылки строятся через link(ctx, url): если cookie сессии заблокированы
 * (игра в iframe превью), sid добавляется в URL как ?s=...
 */
const { expPercent, num } = require('./func');

const RACE = { human: 'Человек', gnom: 'Гном', elf: 'Светлый Эльф', darkelf: 'Темный Эльф', ork: 'Орк', kamael: 'Камаэль' };
const KLAS = { wizard: 'Маг', fighert: 'Воин' };

function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

/**
 * Ссылка с переносом сессии: link(ctx, '/shop.php?mod=weapon') -> '/shop.php?mod=weapon&s=...'
 * Добавляет ?s= только когда cookie недоступны (иначе URL остаются чистыми).
 */
function link(ctx, url) {
  if (!ctx || !ctx.sid || !ctx.ses_in_url) return url;
  const hash = url.indexOf('#');
  const frag = hash >= 0 ? url.slice(hash) : '';
  const base = hash >= 0 ? url.slice(0, hash) : url;
  const sep = base.includes('?') ? '&' : '?';
  return `${base}${sep}s=${encodeURIComponent(ctx.sid)}${frag}`;
}

/** Форма: скрытое поле s, чтобы POST тоже сохранял сессию. */
function hiddenSid(ctx) {
  return ctx && ctx.sid && ctx.ses_in_url ? `<input type="hidden" name="s" value="${esc(ctx.sid)}"/>` : '';
}

/** inc/head.php — <head> + шапка miniage ru */
function head(ctx, title) {
  const log = ctx.log || 'Гость';
  return `<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head><meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>
<meta name="keywords" content="miniage,lineage 2,wap,онлайн,игра,битвы,осады,mmorpg" />
<meta name="description" content="Miniage — WAP MMORPG в духе Lineage 2: осады, замки, дворянство, кланы, битвы." />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>${esc(log)} | miniage</title>
<link rel="stylesheet" href="/theme/osn.css" type="text/css" />
</head><body>
<table width="100%" cellpadding="0" cellspacing="0"><tr><td class="lt1"></td>
<td height="48"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>
<td class="t1">&nbsp;</td>
<td height="48" width="232" align="center" valign="middle" class="tc"><div><font color="#af9878"><b>miniage <b>ru</b></b></font></div></td>
<td class="t1">&nbsp;</td></tr></table></td><td class="rt1">&nbsp;</td></tr>
<tr><td width="19" class="l">&nbsp;</td><td class="centertd">`;
}

/** inc/zag.php — блок персонажа: ник, уровень, HP/MP/EXP-бары */
function zag(ctx) {
  const u = ctx.udata;
  if (!u) return '';
  const hp = Math.max(0, Math.min(100, u.hp / u.hpall * 100));
  const mp = Math.max(0, Math.min(100, u.mp / u.mpall * 100));
  const exp = expPercent(u);
  return `<div class="gameBorder"><div class="fon"><div class="head">
<font color="#ffffcc"><b>${esc(u.usr)}</b> | </font><font color="#ffffcc">${u.lvl} ур.${exp >= 100 ? ' <font color=red>+</font>' : ''}</font><br/>
<div style="height:15px;background:#330000 url(/theme/pic/exp2.gif) repeat-x top;width:40%;font-size:15px;position:relative;">
<div style="height:15px;background:#990000 url(/theme/pic/hp.gif) repeat-x top;width:${hp}%;font-size:11px;position:relative;">
<div style="position:static;"><p align="left"><nobr>&nbsp;&nbsp;HP&nbsp;&nbsp;&nbsp;&nbsp;${num(u.hp)}/${num(u.hpall)}</nobr></p></div></div></div>
<div style="height:15px;background:#000066 url(/theme/pic/exp2.gif) repeat-x top;width:40%;font-size:15px;position:relative;">
<div style="height:15px;background:#3333FF url(/theme/pic/mp.gif) repeat-x top;width:${mp}%;font-size:11px;position:relative;">
<div style="position:static;"><p align="left"><nobr>&nbsp;&nbsp;MP&nbsp;&nbsp;&nbsp;&nbsp;${num(u.mp)}/${num(u.mpall)}</nobr></p></div></div></div>
<div style="height:15px;background:#757575 url(/theme/pic/exp2.gif) repeat-x top;width:40%;font-size:15px;position:relative;">
<div style="height:15px;background:#B300FF url(/theme/pic/exp.gif) repeat-x top;width:${exp}%;font-size:11px;position:relative;">
<div style="position:static;"><nobr><p align="left">&nbsp;&nbsp;EXP&nbsp;&nbsp;&nbsp;&nbsp;${exp}%</p></nobr></div></div></div>
<br/></div>`;
}

/** inc/down.php + foot — навигация внизу */
function down(ctx) {
  const u = ctx.udata;
  let nav = '';
  if (u) {
    nav = `<div class="inoy">
<a href="${link(ctx, '/pers.php')}">Персонаж</a>
<a href="${link(ctx, '/gorod.php')}">Город</a>
<a href="${link(ctx, '/okrestnosti.php')}">Окрестности</a>
<a href="${link(ctx, '/shop.php')}">Магазин</a>
<a href="${link(ctx, '/inventar.php')}">Инвентарь</a>
<a href="${link(ctx, '/chat.php')}">Чат</a>
<a href="${link(ctx, '/msg.php')}">Почта</a>
<a href="${link(ctx, '/office.php')}">Офис</a>
</div>`;
  } else {
    nav = `<div class="inoy"><a href="${link(ctx, '/index.php')}">Главная</a> <a href="${link(ctx, '/reg.php')}">Регистрация</a> <a href="${link(ctx, '/faq.php')}">FAQ</a></div>`;
  }
  return `</div></div></div><span style="text-align:center;">${nav}
<div class="foot">miniage ru &copy; 2013-${new Date().getFullYear()} | Node.js версия</div>
</span></td></tr></table></body></html>`;
}

/** Полная страница: head + zag + контент + down (аналог связки include в PHP) */
function page(ctx, title, body) {
  return head(ctx, title) + zag(ctx) + body + down(ctx);
}

/** Страница для гостя (index.php / reg.php — там zag.php не подключается) */
function pageGuest(ctx, title, body) {
  return head(ctx, title) + body + down(ctx);
}

/**
 * Переписывает все внутренние ссылки/формы в уже готовом HTML,
 * добавляя ?s=<sid> когда cookie недоступны. Одна точка вместо правки
 * каждой ссылки в routes.js.
 */
function injectSid(ctx, htmlText) {
  if (!ctx || !ctx.sid || !ctx.ses_in_url || !htmlText) return htmlText;
  const s = encodeURIComponent(ctx.sid);
  return htmlText
    // href="/foo.php?..." и href="/foo.php"
    .replace(/(href=")(\/[^"#?]*)(\?[^"]*)?(")/g, (m, a, path, q, z) => {
      if (!path.endsWith('.php') && !path.startsWith('/forum/')) return m;
      if (q && /[?&]s=/.test(q)) return m; // sid уже добавлен через link()
      return `${a}${path}${q ? q + '&' : '?'}s=${s}${z}`;
    })
    // action="/foo.php"
    .replace(/(action=")(\/[^"#?]*\.php)(\?[^"]*)?(")/g, (m, a, path, q, z) => {
      if (q && /[?&]s=/.test(q)) return m;
      return `${a}${path}${q ? q + '&' : '?'}s=${s}${z}`;
    });
}

module.exports = { head, zag, down, page, pageGuest, esc, RACE, KLAS, link, hiddenSid, injectSid };
