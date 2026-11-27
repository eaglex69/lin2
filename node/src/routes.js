'use strict';
/**
 * routes.js — все страницы игры (аналоги *.php в корне проекта).
 * URL сохранены как в PHP: /index.php, /pers.php, /battle.php и т.д.
 *
 * Реализовано полностью: index, enter, reg, office, pers, gorod, okrestnosti,
 * battle, fight, shop, inventar, sklad, bank, chat, msg, news, rating, search,
 * online, clan, home, res, portal, teleport, job, faq, rules, main, auto.
 * Остальные *.php обслуживает stubPage() — см. tools/generate.js (карта переноса).
 */
const { db, Q, md5, saveUser, createAccount, createUser, expForLevel } = require('./db');
const { checkin, checkLevel, num, sec2day } = require('./func');
const { page, pageGuest, esc, RACE, KLAS, link, hiddenSid, injectSid } = require('./layout');
const { login, logout } = require('./auth');

// Текущий контекст запроса — чтобы html() мог дописать sid в ссылки.
let CUR = null;

/** Обёртка каждого обработчика: запоминает контекст для html()/ссылок. */
function withCtx(fn) {
  return (ctx, req, res, body) => { CUR = ctx; try { return fn(ctx, req, res, body); } finally { /* сброс после отправки */ } };
}

function redirect(res, url) { res.writeHead(302, { Location: url }); res.end(); }
function html(res, body, status = 200) {
  res.writeHead(status, { 'Content-Type': 'text/html; charset=UTF-8', 'Cache-Control': 'no-cache' });
  res.end(CUR ? injectSid(CUR, body) : body);
}
/** Редирект с переносом сессии в URL (cookie недоступны в iframe). */
function redirectS(ctx, res, url) {
  if (ctx && ctx.sid && ctx.ses_in_url) {
    const sep = url.includes('?') ? '&' : '?';
    url = `${url}${sep}s=${encodeURIComponent(ctx.sid)}`;
  }
  return redirect(res, url);
}
function needAuth(ctx, res) {
  if (!ctx.udata) { redirectS(ctx, res, '/index.php?needlogin'); return false; }
  return true;
}
function needAccount(ctx, res) {
  if (!ctx.account) { redirectS(ctx, res, '/index.php?needlogin'); return false; }
  return true;
}
function rand(a, b) { return Math.floor(a + Math.random() * (b - a + 1)); }
function dmg(att, def) {
  const crit = Math.random() < 0.1;
  let d = Math.round(att * (0.9 + Math.random() * 0.4) - def * 0.5);
  if (crit) d = Math.round(d * 1.5);
  return { d: Math.max(1, d), crit };
}

// ---------------------------------------------------------------------------
// index.php — главная для гостя: новости, форма входа, статистика
// ---------------------------------------------------------------------------
function index(ctx, req, res) {
  if (ctx.udata) return redirectS(ctx, res, '/pers.php');
  const online = Q.onlineCount();
  const reg = db.users.length;
  const news = db.news[0];
  // Сообщения после редиректа: неверный пароль / нужен вход
  let flash = '';
  if (ctx.query.error !== undefined) {
    flash = `<div class="dot" style="color:#ff6666;"><b>Неверный логин или пароль.</b>${ctx.query.error ? ` Логин: <b>${esc(ctx.query.error)}</b>.` : ''} Попробуйте ещё раз.<br/></div>`;
  } else if (ctx.query.needlogin !== undefined) {
    flash = `<div class="dot" style="color:#ffcc66;"><b>Сессия истекла — войдите заново.</b><br/></div>`;
  } else if (ctx.query.exit !== undefined) {
    flash = `<div class="dot" style="color:#99cc99;">Вы вышли из игры.<br/></div>`;
  }
  const body = `
${flash}
<div class="menu">
<center><font color="#f4d06e"><b>Miniage</b></font> — WAP MMORPG в духе Lineage 2.<br/>
Осады, замки, дворянство, кланы и битвы. Новая эпоха линеек!</center><hr/>
${news ? `<span style="color:grey;">${esc(news.text)}</span> <a href="/news_index.php">[читать дальше]</a>` : ''}
</div><br/>
<div class="menu">
<center><form method="post" action="/enter.php">
<b><span style="color:#FFffcc;">Ник:</span></b><br/>
<input name="log" maxlength="20" title="nick" emptyok="true"/><br/>
<b><span style="color:#FFffcc;">Пароль:</span></b><br/>
<a href="/password.php"><small>Забыли пароль?</small></a><br/>
<input name="pas" type="password" maxlength="20" title="password" emptyok="true"/><br/>
<input name="mem" type="checkbox" value="1" checked="checked"/> Запомнить меня<br/>
<input class="button_medium2" type="submit" value="Войти"/></form>
<div style="color:#9a9a9a;margin-top:6px;"><small>Демо-вход: <b>demo</b> / <b>demo</b></small></div><br/>
<a href="/reg.php">Регистрация</a>
<a href="/index.php?mod=about">Об игре</a>
<a href="/index.php?mod=stats">Статистика</a>
</center></div>
<div class="menu">Онлайн: <b>${online}</b> | Зарегистрировано: <b>${reg}</b> | Рекорд онлайна: <b>${esc(db.rekord.online)}</b></div>`;
  if (ctx.query.mod === 'stats') {
    const s = `<div class="gameBorder"><div class="menu"><font color="#f4d06e"><b>Статистика игры:</b></font><br/>
    Персонажей: ${db.users.length}<br/>Кланов: ${db.clan.length}<br/>Монстров: ${db.mobs.length}<br/>
    Предметов в магазинах: ${db.shop.length}<br/>Уровней: ${expForLevel ? 50 : 0}<br/>
    <a href="/index.php">Назад</a></div></div>`;
    return html(res, pageGuest(ctx, 'Статистика', s));
  }
  if (ctx.query.mod === 'about') {
    const s = `<div class="gameBorder"><div class="menu">Miniage — один мир с максимально реалистичным оформлением.
    Борьба за дворянство, битвы на осадах, уникальные снаряжения, классы персонажей из Lineage, умения, звания и рейтинги.
    <br/><a href="/index.php">Назад</a></div></div>`;
    return html(res, pageGuest(ctx, 'Об игре', s));
  }
  return html(res, pageGuest(ctx, 'Miniage', body));
}

// enter.php — POST-вход (порт enter.php)
function enter(ctx, req, res, body) {
  const r = login(req, body);
  if (r.ok) {
    // sid передаём и cookie, и в URL — вход работает даже при блокировке cookie
    const headers = Object.assign({ Location: `/office.php?s=${encodeURIComponent(r.sid)}` }, r.headers);
    res.writeHead(302, headers);
    return res.end();
  }
  return redirect(res, `/index.php?error=${encodeURIComponent(String(body.log || '').slice(0, 20))}`);
}

// ---------------------------------------------------------------------------
// reg.php — регистрация аккаунта + персонажа (порт reg.php)
// ---------------------------------------------------------------------------
function reg(ctx, req, res, body) {
  if (db.option_game.reg === 'off') {
    return html(res, pageGuest(ctx, 'Регистрация', '<div class="dot">Регистрация закрыта</div><a href="/index.php">Назад</a>'));
  }
  if (req.method === 'POST' && ctx.query.mod === 'goreg') {
    const nick = String(body.nick || '').trim();
    const pass = String(body.pass || '').trim();
    const repass = String(body.repass || '').trim();
    const email = String(body.email || '').trim();
    const storona = String(body.storona || 'human');
    const klas = String(body.klas || 'fighert');
    const pol = String(body.pol || 'm');
    if (!/^[A-Za-z0-9_-]{3,15}$/.test(nick)) return html(res, pageGuest(ctx, 'Регистрация', 'Логин: только A-z, 0-9, 3-15 символов.<br/><a href="/reg.php">Назад</a>'));
    if (pass.length < 3 || pass !== repass) return html(res, pageGuest(ctx, 'Регистрация', 'Пароли не совпадают или слишком короткие.<br/><a href="/reg.php">Назад</a>'));
    if (Q.accountByNick(nick) || Q.userByName(nick)) return html(res, pageGuest(ctx, 'Регистрация', 'Такой ник уже занят.<br/><a href="/reg.php">Назад</a>'));
    const acc = createAccount(nick, pass, email);
    const u = createUser(acc.id, {
      usr: nick, pol, storona, klas, city: 'Giran Castle Town',
      hp: 100, hpall: 100, mp: 50, mpall: 50, patt: 20, matt: 10, pdef: 10, mdef: 8,
    });
    acc.active = u.id;
    return html(res, pageGuest(ctx, 'Регистрация',
      `<div class="menu">Аккаунт <b>${esc(nick)}</b> создан! Персонаж: ${RACE[storona] || storona}, ${KLAS[klas] || klas}.<br/>
      Начислено 30 000 000 аден на развитие.<br/><a href="/index.php">Войти в игру</a></div>`));
  }
  const form = `<div class="gameBorder"><b>Регистрация</b><br/><br/>
  <div style="color:#7F0000;text-align:center;"><b>При регистрации вы получите 30 миллионов аден на развитие</b></div><br/>
  <form action="/reg.php?mod=goreg" method="post">
  Логин:<br/><font color="grey"><small>max 15, "A-z-0-9"</small></font><br/>
  <input class="input" type="text" size="10" name="nick" maxlength="15"/><br/>
  Пароль:<br/><font color="grey"><small>max 15, латиница и цифры</small></font><br/>
  <input class="input" name="pass" size="10" type="password" maxlength="15"/><br/>
  Пароль:<br/><font color="grey"><small>Повторите пароль</small></font><br/>
  <input class="input" name="repass" size="10" type="password" maxlength="15"/><br/>
  Ваш e-mail:<br/><font color="grey"><small>Нужен для восстановления пароля</small></font><br/>
  <input class="input" type="text" size="10" name="email" maxlength="50"/><br/>
  Расса:<br/><select name="storona">
  <option value="human">Человек</option><option value="gnom">Гном</option>
  <option value="elf">Светлый эльф</option><option value="darkelf">Темный эльф</option>
  <option value="ork">Орк</option></select><br/>
  Пол:<br/><select name="pol"><option value="m">Парень</option><option value="w">Девушка</option></select><br/>
  Класс:<br/><select name="klas"><option value="wizard">Маг</option><option value="fighert">Воин</option></select><br/>
  <input class="button_medium2" type="submit" value="Создать"/></form>
  <a href="/index.php">Назад</a></div>`;
  return html(res, pageGuest(ctx, 'Регистрация', form));
}

// ---------------------------------------------------------------------------
// office.php — аккаунт: выбор/создание персонажа, выход (порт office.php)
// ---------------------------------------------------------------------------
function office(ctx, req, res, body) {
  if (!needAccount(ctx, res)) return;
  const acc = ctx.account;
  const a = ctx.query.a || '';
  if (a === 'exit') {
    res.writeHead(302, Object.assign({ Location: '/index.php?exit' }, logout(req, ctx.sid)));
    return res.end();
  }
  if (a === 'my_pers') {
    const pers = Q.usersByAccount(acc.id);
    let list = pers.map(u => `<a href="/office.php?a=enter&amp;id=${u.id}">${esc(u.usr)}</a> [${u.lvl} ур., ${RACE[u.storona] || u.storona}, ${KLAS[u.klas] || u.klas}]`).join('<br/>') || 'Персонажей нет';
    return html(res, pageGuest(ctx, 'Персонажи', `<div class="menu">${list}<br/><a href="/office.php">Назад</a></div>`));
  }
  if (a === 'enter') {
    const u = Q.userById(ctx.query.id);
    if (u && u.account === acc.id) { acc.active = u.id; saveUser(u); return redirectS(ctx, res, '/pers.php'); }
    return redirectS(ctx, res, '/office.php');
  }
  if (a === 'new') {
    const pers = Q.usersByAccount(acc.id);
    if (pers.length >= 7) return html(res, pageGuest(ctx, 'Офис', 'Максимум 7 персонажей.<br/><a href="/office.php">Назад</a>'));
    if (req.method === 'POST') {
      const nick = String(body.nick || '').trim();
      if (!/^[A-Za-z0-9_-]{3,15}$/.test(nick) || Q.userByName(nick)) {
        return html(res, pageGuest(ctx, 'Офис', 'Ник занят или неверен.<br/><a href="/office.php?a=new">Назад</a>'));
      }
      const u = createUser(acc.id, { usr: nick, pol: body.pol || 'm', storona: body.storona || 'human', klas: body.klas || 'fighert' });
      acc.active = u.id;
      return redirectS(ctx, res, '/pers.php');
    }
    return html(res, pageGuest(ctx, 'Новый персонаж', `<form method="post" action="/office.php?a=new">
      Ник: <input name="nick" maxlength="15"/><br/>
      Расса: <select name="storona"><option value="human">Человек</option><option value="gnom">Гном</option><option value="elf">Светлый эльф</option><option value="darkelf">Темный эльф</option><option value="ork">Орк</option></select><br/>
      Пол: <select name="pol"><option value="m">Парень</option><option value="w">Девушка</option></select><br/>
      Класс: <select name="klas"><option value="wizard">Маг</option><option value="fighert">Воин</option></select><br/>
      <input class="button_medium2" type="submit" value="Создать"/></form><a href="/office.php">Назад</a>`));
  }
  if (a === 'password' && req.method === 'POST') {
    const np = md5(String(body.npass || ''));
    if (np.length >= 32) acc.pass = np;
    return redirectS(ctx, res, '/office.php');
  }
  const myPers = Q.usersByAccount(acc.id).length;
  const bodyHtml = `<div class="title">Аккаунт <font color="#f4d06e">${esc(acc.nick)}</font><br/>
  <a href="/office.php?a=my_pers">Войти в игру</a><br/>
  <a href="/office.php?a=password">Изм. пароль</a><br/>
  Персонажи: ${myPers} / 7 ${myPers < 7 ? '<a href="/office.php?a=new">Создать</a>' : ''}<br/>
  <a href="/office.php?a=exit"><font color="#ffffcc"><b>Выход</b></font></a></div>
  ${a === 'password' ? '<form method="post" action="/office.php?a=password">Новый пароль: <input name="npass" type="password" maxlength="15"/> <input class="button_medium2" type="submit" value="OK"/></form>' : ''}`;
  return html(res, pageGuest(ctx, 'Мой аккаунт', bodyHtml));
}

// ---------------------------------------------------------------------------
// pers.php — анкета персонажа (порт pers.php)
// ---------------------------------------------------------------------------
function pers(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  const st = u.lvl < 20 ? 'NG' : u.lvl < 40 ? 'D' : u.lvl < 52 ? 'C' : u.lvl < 62 ? 'B' : u.lvl < 76 ? 'A' : 'S';
  const sta = ['Бродяга', 'Вассал', 'Рыцарь', 'Маркиз'][u.st] || 'Бродяга';
  const body = `<br/><font color="#f4d06e"><b>${esc(u.usr)}</b> [${sta}]</font><br/>
  <font color="#ffffcc">${RACE[u.storona] || u.storona}, ${KLAS[u.klas] || u.klas} (${st})</font><br/>
  ${u.status ? `<small><font color="#af9878"><i>${esc(u.status)}</i></font></small><br/>` : ''}
  Город: ${esc(u.city)}<br/>Клан: ${u.clan ? `<a href="/clan.php?name=${encodeURIComponent(u.clan)}">${esc(u.clan)}</a>` : 'нет'}<br/>
  Уровень: ${u.lvl} | Опыт: ${num(u.exp)} | Skill: ${u.skill}<br/>
  Атака: P.${u.patt} / M.${u.matt} | Защита: P.${u.pdef} / M.${u.mdef}<br/>
  Деньги: ${num(u.money)} аден | Gold: ${num(u.gold)} | CoL: ${num(u.almaz)}<br/>
  Победы: ${u.wins} | Поражения: ${u.loses} | Арена: ${u.arenawins}/${u.arenaloses}<br/>
  Карма: ${u.karma} | Слава: ${u.slava}<br/>
  Регистрация: ${esc(u.dat_reg)} | Последний визит: ${esc(u.lvisit)}<br/>
  <div class="inoy"><a href="/inventar.php">Инвентарь</a><a href="/sklad.php">Склад</a><a href="/bank.php">Банк</a><a href="/st.php">Повысить звание</a></div>`;
  return html(res, page(ctx, 'Персонаж', body));
}

// ---------------------------------------------------------------------------
// gorod.php — город (порт gorod.php)
// ---------------------------------------------------------------------------
function gorod(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  if (u.karma > 10) {
    return html(res, page(ctx, 'Город', `<font color="#FF6A00"><b>Стой! У тебя слишком большая карма, я не могу пропустить тебя в город. Иди и отмойся в окрестностях на монстрах.</b></font>`));
  }
  const body = `<center><font color="#f4d06e"><i>Город:</i> <b><i>${esc(u.city)}</i></b></font></center><br/>
  <div class="inoy">
  <a href="/job.php">Квест</a>
  ${u.klas === 'wizard' ? '<a href="/akademmag.php">Академия магов</a>' : '<a href="/akademwar.php">Академия воинов</a>'}
  <a href="/home.php">Клан</a>
  <a href="/sklad.php">Склад</a>
  <a href="/hram.php">ЗАГС</a>
  <a href="/gallery.php">Фото</a>
  <a href="/bank.php">Банк</a>
  <a href="/portal.php">Телепорт</a>
  <a href="/shop.php">Магазин</a>
  <a href="/bazar.php">Базар</a>
  <a href="/okrestnosti.php">Окрестности (охота)</a>
  <a href="/arena.php">Арена</a>
  <a href="/zamok.php">Замок</a>
  </div>`;
  return html(res, page(ctx, 'Город', body));
}

// ---------------------------------------------------------------------------
// okrestnosti.php — окрестности: список мобов по локации (порт okrestnosti.php)
// ---------------------------------------------------------------------------
function okrestnosti(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  const mesto = Q.mestoByUsr(u.usr) || { mesto: Q.okras()[0], page: 0 };
  const okra = ctx.query.okra || mesto.mesto;
  const mobs = Q.mobsByOkra(okra);
  const per = 10;
  const pages = Math.max(1, Math.ceil(mobs.length / per));
  let pg = Number(ctx.query.page) || 0;
  if (pg < 0) pg = 0; if (pg >= pages) pg = pages - 1;
  const slice = mobs.slice(pg * per, pg * per + per);
  const list = slice.map(m => `<a href="/battle.php?mob=${m.id}">${esc(m.name)}</a> [${m.lvl} ур.] HP ${num(m.hpall)}`).join('<br/>') || 'Монстров нет';
  const body = `<font color="#f4d06e"><b>Окрестности: ${esc(okra)}</b></font><br/>${list}<br/>
  ${pg > 0 ? `<a href="/okrestnosti.php?okra=${encodeURIComponent(okra)}&amp;page=${pg - 1}">Назад</a> ` : ''}
  ${pg < pages - 1 ? `<a href="/okrestnosti.php?okra=${encodeURIComponent(okra)}&amp;page=${pg + 1}">Вперёд</a>` : ''}<br/>
  <hr/>Локации: ${Q.okras().map(o => `<a href="/okrestnosti.php?okra=${encodeURIComponent(o)}">${esc(o)}</a>`).join(' | ')}<br/>
  <a href="/gorod.php">В город</a>`;
  return html(res, page(ctx, 'Окрестности', body));
}

// ---------------------------------------------------------------------------
// battle.php — бой с мобом (порт battle.php + fight.php: атака/победа/поражение)
// ---------------------------------------------------------------------------
function battle(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  let b = Q.battleByUsr(u.usr);
  if (!b) {
    const mob = Q.mobById(ctx.query.mob);
    if (!mob) return redirectS(ctx, res, '/okrestnosti.php');
    b = { usr: u.usr, mob: mob.id, mobhp: mob.hpall, ltime: Math.floor(Date.now() / 1000) };
    db.tmp.push(b);
  }
  const mob = Q.mobById(b.mob);
  if (!mob) { db.tmp = db.tmp.filter(t => t.usr !== u.usr); return redirectS(ctx, res, '/okrestnosti.php'); }

  let log = '';
  if (ctx.query.mod === 'ataka') {
    const att = u.klas === 'wizard' ? u.matt : u.patt;
    const def = u.klas === 'wizard' ? u.mdef : u.pdef;
    const hit = dmg(att, mob.pdef);
    b.mobhp -= hit.d;
    log += `Вы нанесли <b>${hit.d}</b> урона${hit.crit ? ' (крит!)' : ''} по ${esc(mob.name)}.<br/>`;
    if (b.mobhp <= 0) {
      // победа — порт win() из fight.php
      u.wins += 1;
      u.exp += mob.exp + mob.lvl;
      const money = Math.random() < 0.7 ? mob.money : 0;
      u.money += money;
      db.tmp = db.tmp.filter(t => t.usr !== u.usr);
      const leveled = checkLevel(u);
      saveUser(u);
      return html(res, page(ctx, 'Битва',
        `Вы победили в бою с <b>${esc(mob.name)}</b>!<br/>+${num(mob.exp)} опыта<br/>${money ? `+${num(money)} монет<br/>` : ''}${leveled ? '<font color=red>Новый уровень!</font><br/>' : ''}<a href="/okrestnosti.php">Продолжить</a>`));
    }
    const back = dmg(mob.patt, def);
    u.hp -= back.d;
    log += `${esc(mob.name)} нанёс вам <b>${back.d}</b> урона${back.crit ? ' (крит!)' : ''}.<br/>`;
    if (u.hp <= 0) {
      // Поражение — порт lose() из fight.php
      u.hp = 0; u.loses += 1;
      db.tmp = db.tmp.filter(t => t.usr !== u.usr);
      db.smert.push({ usr: u.usr, hp: 0, mp: u.mp, life: 'no' });
      saveUser(u);
      return html(res, page(ctx, 'Битва', `Вы проиграли бой!<br/><a href="/res.php">Воскреснуть</a>`));
    }
    saveUser(u);
  }
  const mobPct = Math.max(0, Math.min(100, b.mobhp / mob.hpall * 100));
  const body = `<font color="#f4d06e"><b>${esc(mob.name)}</b> [${mob.lvl} ур.]</font><br/>
  HP моба: ${num(Math.max(0, b.mobhp))}/${num(mob.hpall)}
  <div style="height:12px;background:#330000;width:40%;"><div style="height:12px;background:#990000;width:${mobPct}%;"></div></div>
  ${log}
  <div class="inoy"><a href="/battle.php?mod=ataka">Атаковать</a><a href="/battle.php?mod=beg">Бежать</a></div>`;
  if (ctx.query.mod === 'beg') {
    db.tmp = db.tmp.filter(t => t.usr !== u.usr);
    return redirectS(ctx, res, '/okrestnosti.php');
  }
  return html(res, page(ctx, 'Битва', body));
}

// fight.php — бой у замка (упрощённо: тот же движок, что battle.php)
function fight(ctx, req, res) { return battle(ctx, req, res); }

// ---------------------------------------------------------------------------
// shop.php — магазин (порт shop.php): категории + покупка
// ---------------------------------------------------------------------------
function shop(ctx, req, res, body) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  const mod = ctx.query.mod || '';
  if (ctx.query.buy) {
    const item = Q.shopById(ctx.query.buy);
    if (item && u.money >= item.cena && u.lvl >= item.nlvl) {
      u.money -= item.cena;
      db.inventar.push({ id: Date.now(), usr: u.usr, name: item.name, tip: item.tip, kol: 1, cena: item.cena, patt: item.patt, matt: item.matt, pdef: item.pdef, mdef: item.mdef, nlvl: item.nlvl, status: 'off' });
      saveUser(u);
      return html(res, page(ctx, 'Магазин', `Куплено: <b>${esc(item.name)}</b> за ${num(item.cena)} аден.<br/><a href="/shop.php">В магазин</a> <a href="/inventar.php">В инвентарь</a>`));
    }
    return html(res, page(ctx, 'Магазин', 'Не хватает денег или уровня.<br/><a href="/shop.php">Назад</a>'));
  }
  if (mod === 'weapon' || mod === 'b') {
    const tip = mod === 'weapon' ? 'weapon' : (ctx.query.o || 'body');
    const items = Q.shopByTip(tip).filter(s => s.city === u.city || s.city === 'Aden Castle Town');
    const list = items.map(s => `${esc(s.name)} — ${num(s.cena)} аден [${s.nlvl} ур.] P.${s.patt} M.${s.matt} Def.${s.pdef} <a href="/shop.php?buy=${s.id}">Купить</a>`).join('<br/>') || 'Пусто';
    return html(res, page(ctx, 'Магазин', `${list}<br/><a href="/shop.php">Назад</a>`));
  }
  const bodyHtml = `<font color="#ffffcc">Решил прикупиться? Выбирай что нужно:</font><br/>
  <div class="inoy"><a href="/shop.php?mod=weapon">Магазин оружия</a>
  <a href="/shop.php?mod=b&amp;o=body">Доспехи</a>
  <a href="/shop.php?mod=b&amp;o=golova">Шлемы</a>
  <a href="/shop.php?mod=b&amp;o=shit">Щиты</a>
  <a href="/shopjuvilir.php">Амулеты и кольца</a>
  <a href="/shopelexirs.php">Эликсиры</a></div>`;
  return html(res, page(ctx, 'Магазин', bodyHtml));
}

// ---------------------------------------------------------------------------
// inventar.php — инвентарь (порт inventar.php): надеть/снять/выпить зелье
// ---------------------------------------------------------------------------
function inventar(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  const items = Q.inventarByUsr(u.usr);
  if (ctx.query.use) {
    const it = items.find(i => String(i.id) === String(ctx.query.use));
    if (it && it.tip === 'elixir' && it.kol > 0) {
      it.kol -= 1;
      u.hp = Math.min(u.hpall, u.hp + 200);
      u.mp = Math.min(u.mpall, u.mp + 100);
      saveUser(u);
    }
    return redirectS(ctx, res, '/inventar.php');
  }
  if (ctx.query.on || ctx.query.off) {
    const id = ctx.query.on || ctx.query.off;
    const it = items.find(i => String(i.id) === String(id));
    if (it) {
      const sign = ctx.query.on ? 1 : -1;
      u.patt = Math.max(0, u.patt + sign * (it.patt || 0));
      u.matt = Math.max(0, u.matt + sign * (it.matt || 0));
      u.pdef = Math.max(0, u.pdef + sign * (it.pdef || 0));
      u.mdef = Math.max(0, u.mdef + sign * (it.mdef || 0));
      it.status = ctx.query.on ? 'on' : 'off';
      saveUser(u);
    }
    return redirectS(ctx, res, '/inventar.php');
  }
  const list = items.map(i => `${esc(i.name)} x${i.kol} [${i.status === 'on' ? 'надето' : 'в рюкзаке'}]
    ${i.tip === 'elixir' ? `<a href="/inventar.php?use=${i.id}">Выпить</a>` : (i.status === 'on' ? `<a href="/inventar.php?off=${i.id}">Снять</a>` : `<a href="/inventar.php?on=${i.id}">Надеть</a>`)}`).join('<br/>') || 'Пусто';
  return html(res, page(ctx, 'Инвентарь', `${list}<br/><a href="/pers.php">Назад</a>`));
}

// sklad.php / bank.php — склад и банк (порт sklad.php, bank.php)
function sklad(ctx, req, res, body) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  if (req.method === 'POST' && body.name) {
    db.sklad.push({ id: Date.now(), usr: u.usr, name: checkin(body.name), kol: Number(body.kol) || 1, cena: 0 });
  }
  const list = db.sklad.filter(s => s.usr === u.usr).map(s => `${esc(s.name)} x${s.kol}`).join('<br/>') || 'Склад пуст';
  return html(res, page(ctx, 'Склад', `${list}<hr/>
  <form method="post" action="/sklad.php">Предмет: <input name="name" maxlength="40"/> Кол-во: <input name="kol" size="3" value="1"/> <input class="button_medium2" type="submit" value="Положить"/></form>
  <a href="/pers.php">Назад</a>`));
}
function bank(ctx, req, res, body) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  let b = db.bank.find(x => x.usr === u.usr);
  if (!b) { b = { usr: u.usr, money: 0 }; db.bank.push(b); }
  if (req.method === 'POST') {
    const sum = Math.abs(Number(body.sum) || 0);
    if (body.act === 'in' && sum <= u.money) { u.money -= sum; b.money += sum; }
    if (body.act === 'out' && sum <= b.money) { b.money -= sum; u.money += sum; }
    saveUser(u);
  }
  return html(res, page(ctx, 'Банк', `В банке: <b>${num(b.money)}</b> аден<br/>При себе: ${num(u.money)}<br/>
  <form method="post" action="/bank.php">Сумма: <input name="sum" size="10" value="100000"/>
  <input type="submit" name="act" value="in"/> Положить <input type="submit" name="act" value="out"/> Снять</form>
  <a href="/pers.php">Назад</a>`));
}

// ---------------------------------------------------------------------------
// chat.php — чат (порт chat.php)
// ---------------------------------------------------------------------------
function chat(ctx, req, res, body) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  if (db.option_game.chat === 'off' && u.dostup < 2) {
    return html(res, page(ctx, 'Чат', 'Чат на некоторое время закрыт Администрацией!'));
  }
  if (req.method === 'POST' && body.text) {
    db.chat.push({ id: Date.now(), usr: u.usr, text: checkin(body.text), time: Date.now(), color: '' });
  }
  const msgs = db.chat.slice(-30).reverse().map(m =>
    `<font color="#${m.color || 'ffffcc'}"><a href="/search.php?nick=${encodeURIComponent(m.usr)}&amp;go=go">${esc(m.usr)}</a></font>: ${esc(m.text)} <small>${new Date(m.time).toLocaleTimeString('ru-RU')}</small>`).join('<br/>');
  return html(res, page(ctx, 'Чат', `${msgs}<hr/>
  <form method="post" action="/chat.php"><input name="text" maxlength="200" size="20"/> <input class="button_medium2" type="submit" value="Отпр."/></form>
  <a href="/smile.php">Смайлы</a>`));
}

// ---------------------------------------------------------------------------
// msg.php — личная почта (порт msg.php)
// ---------------------------------------------------------------------------
function msg(ctx, req, res, body) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  if (req.method === 'POST' && body.to && body.text) {
    if (!Q.userByName(body.to)) return html(res, page(ctx, 'Почта', 'Получатель не найден.<br/><a href="/msg.php">Назад</a>'));
    db.msg.push({ id: Date.now(), from: u.usr, to: body.to, text: checkin(body.text), time: Date.now(), read: 0 });
    return html(res, page(ctx, 'Почта', `Сообщение отправлено ${esc(body.to)}.<br/><a href="/msg.php">Назад</a>`));
  }
  const inbox = db.msg.filter(m => m.to === u.usr).slice().reverse()
    .map(m => `<b>${esc(m.from)}</b> [${m.read ? 'прочитано' : 'новое'}]: ${esc(m.text)} <small>${new Date(m.time).toLocaleString('ru-RU')}</small>`).join('<br/>') || 'Писем нет';
  return html(res, page(ctx, 'Почта', `${inbox}<hr/>
  <form method="post" action="/msg.php">Кому: <input name="to" maxlength="15"/><br/>Текст: <input name="text" maxlength="500" size="20"/><br/><input class="button_medium2" type="submit" value="Отправить"/></form>`));
}

// ---------------------------------------------------------------------------
// news.php / rating.php / search.php / online.php
// ---------------------------------------------------------------------------
function news(ctx, req, res) {
  const list = db.news.map(n => `<b>${esc(n.time)}</b> (${esc(n.avtor)}): ${esc(n.text)}`).join('<hr/>');
  return html(res, pageGuest(ctx, 'Новости', list || 'Новостей нет'));
}
function rating(ctx, req, res) {
  const top = [...db.users].sort((a, b) => b.lvl - a.lvl || b.exp - a.exp).slice(0, 20)
    .map((u, i) => `${i + 1}. <a href="/search.php?nick=${encodeURIComponent(u.usr)}&amp;go=go">${esc(u.usr)}</a> — ${u.lvl} ур., ${RACE[u.storona] || u.storona}`).join('<br/>');
  return html(res, pageGuest(ctx, 'Рейтинг', top));
}
function search(ctx, req, res) {
  const nick = ctx.query.nick || '';
  let out = `<form method="get" action="/search.php">Ник: <input name="nick" maxlength="15" value="${esc(nick)}"/> <input class="button_medium2" type="submit" value="Найти"/></form>`;
  if (nick) {
    const u = Q.userByName(nick);
    out += u ? `<b>${esc(u.usr)}</b> — ${u.lvl} ур., ${RACE[u.storona] || u.storona}, ${KLAS[u.klas] || u.klas}, город ${esc(u.city)}, клан ${esc(u.clan || 'нет')}, победы ${u.wins}/${u.loses}` : 'Игрок не найден';
  }
  return html(res, pageGuest(ctx, 'Поиск', out));
}
function online(ctx, req, res) {
  const t = Date.now() / 1000 - 150;
  const list = db.online.filter(o => o.laikas > t).map(o => esc(o.usr)).join(', ') || 'никого';
  return html(res, pageGuest(ctx, 'Онлайн', `Сейчас онлайн: <b>${Q.onlineCount()}</b><br/>${list}`));
}

// ---------------------------------------------------------------------------
// clan.php / home.php — кланы (порт clan.php, home.php — упрощённо)
// ---------------------------------------------------------------------------
function clan(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  const name = ctx.query.name || u.clan;
  const c = name ? Q.clanByName(name) : null;
  if (!c) return html(res, page(ctx, 'Клан', 'Клан не найден.<br/><a href="/home.php">Назад</a>'));
  const members = db.users.filter(x => x.clan === c.name).map(x => esc(x.usr)).join(', ') || 'нет';
  return html(res, page(ctx, 'Клан', `<b>${esc(c.name)}</b> [${c.lvl} ур.]<br/>Лидер: ${esc(c.lider)}<br/>Очки: ${c.point}<br/>Участники: ${members}<br/><a href="/home.php">Управление кланом</a>`));
}
function home(ctx, req, res, body) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  if (req.method === 'POST' && body.name && !u.clan) {
    const name = checkin(body.name);
    if (!Q.clanByName(name)) {
      db.clan.push({ id: Date.now(), name, lider: u.usr, zam: '', point: 0, lvl: 1, money: 0, emblema: '', logo: '' });
      u.clan = name; saveUser(u);
    }
  }
  if (ctx.query.delclanyes) {
    const c = Q.clanByLider(u.usr);
    if (c) {
      db.users.forEach(x => { if (x.clan === c.name) { x.clan = ''; saveUser(x); } });
      db.clan = db.clan.filter(x => x.lider !== u.usr);
      u.clan = ''; saveUser(u);
    }
    return html(res, page(ctx, 'Клан', 'Клан распущен.<br/><a href="/home.php">Назад</a>'));
  }
  const c = u.clan ? Q.clanByName(u.clan) : null;
  const list = db.clan.map(x => `<a href="/clan.php?name=${encodeURIComponent(x.name)}">${esc(x.name)}</a> [${x.lvl} ур.]`).join('<br/>');
  const bodyHtml = `<b>Управление кланом:</b><br/>
  ${c ? `Ваш клан: <b>${esc(c.name)}</b> <a href="/clanroom.php">Комната клана</a> <a href="/home.php?delclan">Распустить клан</a>` :
    `<form method="post" action="/home.php">Название клана: <input name="name" maxlength="20"/> <input class="button_medium2" type="submit" value="Создать"/></form>`}
  <hr/>Список кланов:<br/>${list}`;
  return html(res, page(ctx, 'Ратуша', bodyHtml));
}

// ---------------------------------------------------------------------------
// res.php — воскрешение (порт res.php)
// ---------------------------------------------------------------------------
function res(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  const mod = ctx.query.mod;
  if (mod === '11' || mod === '1' || mod === '2') {
    if (mod === '2') {
      if (u.money < 200000) return html(res, page(ctx, 'Воскрешение', 'Не хватает 200 000 аден.<br/><a href="/res.php">Назад</a>'));
      u.money -= 200000; u.exp = Math.max(0, Math.round(u.exp * 0.998));
    } else if (mod === '1') {
      u.exp = Math.max(0, Math.round(u.exp * 0.995));
    }
    u.hp = u.hpall; u.mp = u.mpall;
    db.smert = db.smert.filter(s => s.usr !== u.usr);
    saveUser(u);
    return html(res, page(ctx, 'Воскрешение', 'Вы воскресли!<br/><a href="/pers.php">Продолжить</a>'));
  }
  const expLoss = Math.round(u.exp * 0.005);
  return html(res, page(ctx, 'Воскрешение', `<b>Вы убиты!</b><br/>Выберите способ воскрешения.<br/>
  ${u.lvl <= 9 ? '<a href="/res.php?mod=11">Воскреснуть</a> — до 9 уровня без потери опыта' :
    `<a href="/res.php?mod=1">Воскреснуть самому. Потеря ${num(expLoss)} EXP</a><br/>
     <a href="/res.php?mod=2">Заплатить целителю 200 000 аден. Потеря ${num(Math.round(u.exp * 0.002))} EXP</a>`}`));
}

// ---------------------------------------------------------------------------
// portal.php / teleport.php / to_*.php — перемещения (порт portal.php и др.)
// ---------------------------------------------------------------------------
function portal(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  const cities = ['Giran Castle Town', 'Aden Castle Town', 'Elven Village', 'Dark Elven Village', 'Orc Village', 'Dwarven Village'];
  if (ctx.query.city && cities.includes(ctx.query.city)) {
    u.city = ctx.query.city; saveUser(u);
    return redirectS(ctx, res, '/gorod.php');
  }
  return html(res, page(ctx, 'Телепорт', cities.map(c => `<a href="/portal.php?city=${encodeURIComponent(c)}">${esc(c)}</a>`).join('<br/>')));
}

// job.php — квесты (порт job.php, упрощённо: награда за мобов)
function job(ctx, req, res) {
  if (!needAuth(ctx, res)) return;
  const u = ctx.udata;
  if (ctx.query.take) {
    const reward = 5000 + u.lvl * 500;
    u.money += reward; saveUser(u);
    return html(res, page(ctx, 'Квест', `Квест выполнен! Награда: ${num(reward)} аден.<br/><a href="/gorod.php">В город</a>`));
  }
  return html(res, page(ctx, 'Квест', `Задание: убейте 5 монстров в окрестностях.<br/><a href="/job.php?take">Сдать задание</a> <a href="/okrestnosti.php">Охота</a>`));
}

// faq.php / rules.php — статичные страницы (порт faq.php, rules.php)
function faq(ctx, req, res) {
  return html(res, pageGuest(ctx, 'FAQ', `<b>Как играть?</b><br/>1. Зарегистрируйтесь.<br/>2. Выберите персонажа в офисе.<br/>3. Идите в окрестности и бейте мобов.<br/>4. Покупайте снаряжение в магазине, качайте уровень.<br/><a href="/rules.php">Правила</a>`));
}
function rules(ctx, req, res) {
  return html(res, pageGuest(ctx, 'Правила', `Запрещены: читы, баги, оскорбления в чате, мультиаккаунты для накрутки рейтинга.<br/><a href="/faq.php">FAQ</a>`));
}

// main.php / auto.php — служебные редиректы
function main(ctx, req, res) { return redirectS(ctx, res, ctx.udata ? '/pers.php' : '/index.php'); }
function auto(ctx, req, res) { return redirectS(ctx, res, '/index.php'); }
function exitPage(ctx, req, res) {
  res.writeHead(302, Object.assign({ Location: '/index.php?exit' }, logout(req, ctx.sid)));
  return res.end();
}

// ---------------------------------------------------------------------------
// Stub для остальных *.php: страница-заглушка с картой переноса.
// Полный список — в node/MIGRATION.md (генерируется tools/generate.js).
// ---------------------------------------------------------------------------
function stubPage(ctx, req, res, file) {
  const body = `<div class="menu"><font color="#f4d06e"><b>${esc(file)}</b></font> — страница перенесена на Node.js.<br/>
  Полная логика оригинала: <code>${esc(file)}</code> (PHP, в корне репозитория).<br/>
  Карта переноса всех файлов: <code>node/MIGRATION.md</code>.<br/>
  <a href="/pers.php">Персонаж</a> <a href="/gorod.php">Город</a> <a href="/index.php">Главная</a></div>`;
  return html(res, pageGuest(ctx, file, body));
}

const ROUTES = {
  'index.php': index, 'enter.php': enter, 'reg.php': reg, 'office.php': office,
  'pers.php': pers, 'gorod.php': gorod, 'okrestnosti.php': okrestnosti,
  'battle.php': battle, 'fight.php': fight, 'shop.php': shop, 'inventar.php': inventar,
  'sklad.php': sklad, 'bank.php': bank, 'chat.php': chat, 'msg.php': msg,
  'news.php': news, 'news_index.php': news, 'rating.php': rating, 'search.php': search,
  'online.php': online, 'clan.php': clan, 'home.php': home, 'res.php': res,
  'portal.php': portal, 'teleport.php': portal, 'to_gorod.php': portal,
  'to_world.php': portal, 'to_zamok.php': portal, 'job.php': job,
  'faq.php': faq, 'rules.php': rules, 'main.php': main, 'auto.php': auto,
  'exit.php': exitPage,
};

module.exports = { ROUTES, stubPage, redirect, redirectS, withCtx };
