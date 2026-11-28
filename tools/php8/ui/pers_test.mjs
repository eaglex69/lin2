// Checks the character page (pers.php: the doll with worn items on both sides, stats under the portrait, skill points), the profile
// of another player (search.php) and the "blocks" script (no loose text on the bare window) against the demo server:
//   node demo/server.mjs --port 8080 &       cd ui && node pers_test.mjs        (BASE=http://127.0.0.1:8081 for another port)
// The database is reset and filled with a bag (seed.mjs) first; the test dresses Tester in twelve items.
import { BASE, open, reporter, reset, sleep, sql } from './lib.mjs';
import { seedBag } from './seed.mjs';

await reset();
await seedBag();
// Tester: one item of every kind on (the club is worn already), 12 skill points
await sql(`UPDATE item SET image='yes' WHERE id IN (SELECT MIN(id) FROM item WHERE usr='Tester' AND image='not' AND tip != 'weapon' GROUP BY tip)`);
await sql(`UPDATE users SET skill=0 WHERE usr='Tester'`);
const { browser, page, log } = await open({ width: 390, height: 900 });
const { check, done } = reporter();
const ev = (fn, ...a) => page.evaluate(fn, ...a);
const path = () => ev(() => location.pathname + location.search);
const waitPath = async (re, ms = 8000) => { try { await page.waitForFunction((r) => new RegExp(r).test(location.pathname + location.search), { timeout: ms }, re); await sleep(300); return true; } catch (e) { return false; } };
const texts = (sel) => ev((s) => [...document.querySelectorAll(s)].map((e) => e.textContent.replace(/\s+/g, ' ').trim()), sel);
const count = (sel) => ev((s) => document.querySelectorAll(s).length, sel);
const one = async (q) => { const r = await sql(q); return r.rows && r.rows[0] ? r.rows[0][0] : undefined; };
const num = (s) => Number(String(s).replace(/[^0-9-]/g, ''));
const rect = (sel) => ev((s) => { const e = document.querySelector(s); if (!e) return null; const r = e.getBoundingClientRect(); return { l: r.left, t: r.top + scrollY, r: r.right, b: r.bottom + scrollY, w: r.width, h: r.height }; }, sel);
const noOverflow = () => ev(() => document.documentElement.scrollWidth <= window.innerWidth);
const phpErrors = () => ev(() => /(Fatal error|Warning:|Notice:|Deprecated:|Parse error|Uncaught)/.test(document.body.innerText));
const idle = async () => { try { await page.waitForFunction(() => !document.documentElement.classList.contains('lx-busy'), { timeout: 8000 }); } catch (e) { /* ignore */ } await sleep(150); };
const go = async (u) => { await page.goto(BASE + u, { waitUntil: 'domcontentloaded' }); await sleep(450); };

// ---------------------------------------------------------------- P: the character page
await go('/pers.php');
check('P0 the page opens without PHP messages', (await texts('.lx-banner b')).join() === 'Персонаж' && !(await phpErrors()), (await texts('.lx-banner b')).join());
const who = await texts('.lx-who > div');
check('P1 name bar: nick and rank | level, race and class', who.length === 2 && /^Tester\b/.test(who[0]) && /24 ур\./.test(who[1]) && /Воин/.test(who[1]), who.join(' | '));
check('P1 tabs: Инфо (current), Умения, Снаряжение, Настройки', (await texts('.lx-tabs .lx-tab')).join() === 'Инфо,Умения,Снаряжение,Настройки' && (await texts('.lx-tabs .lx-tab.on')).join() === 'Инфо', (await texts('.lx-tabs .lx-tab')).join());
const tips = ['weapon', 'golova', 'body', 'pants', 'ruki', 'nogi', 'plash', 'shit', 'amulet', 'poyas', 'rpoyas', 'kolco', 'rkolco'];
const slotTips = await ev(() => [...document.querySelectorAll('.lx-doll .lx-sl')].map((e) => (/\bs-([a-z]+)\b/.exec(e.className) || [])[1]));
check('P2 the doll has 13 slots, one of every kind', slotTips.length === 13 && tips.every((t) => slotTips.filter((x) => x === t).length === 1), slotTips.join(','));
const fig = await rect('.lx-doll .lx-fig');
const lefts = await ev(() => ['golova', 'body', 'pants', 'ruki', 'nogi', 'plash'].map((t) => { const r = document.querySelector('.lx-doll .s-' + t).getBoundingClientRect(); return [r.right, r.width]; }));
const rights = await ev(() => ['shit', 'amulet', 'poyas', 'rpoyas', 'kolco', 'rkolco'].map((t) => { const r = document.querySelector('.lx-doll .s-' + t).getBoundingClientRect(); return [r.left, r.width]; }));
check('P3 six worn items stand to the LEFT of the portrait, six to the RIGHT', fig && lefts.every(([r]) => r <= fig.l + 1) && rights.every(([l]) => l >= fig.r - 1), JSON.stringify({ fig, lefts: lefts[0], rights: rights[0] }));
const wr = await rect('.lx-doll .s-weapon');
check('P3 the weapon slot is above the portrait', wr && fig && wr.b <= fig.t + 6, JSON.stringify({ wr, figTop: fig && fig.t }));
check('P3 the slots are square and about the same size', await ev(() => [...document.querySelectorAll('.lx-doll .lx-dcol .lx-sl')].every((e) => { const r = e.getBoundingClientRect(); return Math.abs(r.width - r.height) < 2 && r.width >= 44 && r.width <= 60; })));
const panelTop = await ev(() => { const p = [...document.querySelectorAll('.lx-pn .lx-pt')].map((e) => [e.textContent.replace(/\s+/g, ' ').trim(), e.getBoundingClientRect().top + scrollY]); return p; });
const hp = panelTop.find((p) => /^Здоровье/.test(p[0])), ch = panelTop.find((p) => /^Характеристики/.test(p[0]));
check('P3 stats are in blocks UNDER the portrait (characteristics, no duplicate health block)', !hp && ch && fig && ch[1] >= fig.b, JSON.stringify({ figBottom: fig && fig.b, panelTop }));
check('P3 every part of the page is a block: no loose text on the window', (await ev(() => { const bad = []; const cell = document.querySelector('td.centertd'); const SURF = /(^|\s)(msg|dot|inoy|pic|silka|info|event|news|forum|inf|pkboy|lich|head|down|down2|adm|admin|foot|navigation|zagolovok|paty|logtext|chattext|chat2|lx-[\w-]+)(\s|$)/; const w = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT); let n; while ((n = w.nextNode())) { if (!/\S/.test(n.nodeValue.replace(/\u00a0/g, ' ')) || n.parentElement.closest('script,style,noscript')) continue; let el = n.parentElement, ok = false; while (el && el !== cell) { if (SURF.test(typeof el.className === 'string' ? el.className : '')) { ok = true; break; } el = el.parentElement; } if (!ok) bad.push(n.nodeValue.trim().slice(0, 30)); } return bad; })).length === 0);
check('P3 no sideways scroll', await noOverflow());

// worn items
const on = await ev(() => [...document.querySelectorAll('.lx-doll .lx-sl.on')].map((e) => (/\bs-([a-z]+)\b/.exec(e.className) || [])[1]));
check('P4 all 13 slots are worn (the club and twelve items)', on.length === 13, on.join(','));
const links = await ev(() => [...document.querySelectorAll('.lx-doll .lx-sl.on')].map((e) => [e.querySelector('a.lx-slot').getAttribute('href'), (e.querySelector('a.lx-un') || {}).getAttribute ? e.querySelector('a.lx-un').getAttribute('href') : null, (e.querySelector('.lx-gr') || {}).textContent]));
check('P4 a worn slot links to the item page, the red cross to «Снять»', links.every((l) => /^inventar\.php\?mod=info&id=\d+$/.test(l[0]) && /^snyat\.php\?tip=[a-z]+$/.test(l[1] || '')), JSON.stringify(links[0]));
check('P4 every worn slot has its grade mark', links.every((l) => /^(NG|D|C|B|A|S|R)$/.test(l[2] || '')));
const me = (await sql(`SELECT patt,matt,pdef,mdef,money,hp,hpall,mp,mpall,almaz,gold FROM users WHERE usr='Tester'`)).rows[0];
const stat = await ev(() => { const o = {}; document.querySelectorAll('.lx-kv .r').forEach((r) => { o[r.querySelector('i').textContent.trim()] = r.querySelector('b').textContent.replace(/\s+/g, ' ').trim(); }); return o; });
check('P5 the numbers are the player\'s own (attack, defence, money, Coin of Luck)', num(stat['Физ. атака']) === me[0] && num(stat['Маг. атака']) === me[1] && num(stat['Физ. защита']) === me[2] && num(stat['Маг. защита']) === me[3] && num(stat['Аден']) === me[4] && num(stat['Coin of Luck']) === me[9], JSON.stringify(stat) + ' vs ' + JSON.stringify(me));
check('P5 health and mana bars show hp / mp in the top header', (await texts('.lx-head .lx-bar b')).join('|').includes(`HP ${me[5]}/${me[6]}`) && (await texts('.lx-head .lx-bar b')).join('|').includes(`MP ${me[7]}/${me[8]}`), (await texts('.lx-head .lx-bar b')).join('|'));
const blocks = await texts('.lx-pn .lx-pt');
check('P5 blocks: Характеристики, Богатство, Сведения, Бои, Время, Подарки (without Здоровье)', !blocks.some((b) => b.startsWith('Здоровье')) && ['Характеристики', 'Богатство', 'Сведения', 'Бои', 'Время', 'Подарки'].every((t) => blocks.some((b) => b.startsWith(t))), blocks.join(','));

// take an item off: the red cross of the shield
await ev(() => { window.__m = 1; });
await page.click('.lx-doll .s-shit a.lx-un');
check('P6 the red cross leads to snyat.php without a reload', await waitPath('^/snyat\\.php') && (await ev(() => window.__m)) === 1, await path());
check('P6 the shield is taken off (message and database)', /Вы сняли/.test(await ev(() => document.body.innerText)) && (await one(`SELECT COUNT(*) FROM item WHERE usr='Tester' AND tip='shit' AND image='yes'`)) == 0);
await go('/pers.php');
const after = await ev(() => ({ on: document.querySelectorAll('.lx-doll .lx-sl.on').length, empty: [...document.querySelectorAll('.lx-doll .lx-sl.empty')].map((e) => [(/\bs-([a-z]+)\b/.exec(e.className) || [])[1], e.querySelector('a.lx-slot') && e.querySelector('a.lx-slot').getAttribute('href')]) }));
check('P6 afterwards 12 slots are worn, the shield slot is empty and opens that part of the bag', after.on === 12 && after.empty.length === 1 && after.empty[0][0] === 'shit' && after.empty[0][1] === 'inventar.php?mod=item&o=shit', JSON.stringify(after));
await page.click('.lx-doll .s-shit a.lx-slot');
check('P6 …the empty slot opens the shields of the bag', await waitPath('inventar\\.php\\?mod=item&o=shit') && (await count('.lx-it')) > 0, await path());

// skill points
await sql(`UPDATE users SET skill=12 WHERE usr='Tester'`);
await go('/pers.php');
const plus = await ev(() => [...document.querySelectorAll('a.lx-plus')].map((a) => a.getAttribute('href')));
check('P7 with skill points every stat has a «+» button', ['pers.php?hp', 'pers.php?mp', 'pers.php?patt', 'pers.php?matt', 'pers.php?pdef', 'pers.php?mdef'].every((h) => plus.includes(h)), plus.join(' '));
check('P7 the points are shown (title of the characteristics) and the form for 5+ points is there', /Очков навыков: 12/.test((await texts('.lx-pn .lx-pt')).join('|')) && (await count('form[action="pers.php?ac=skill"] select[name=what]')) === 1);
const p0 = me[0];
await page.click('a.lx-plus[href="pers.php?patt"]');
await sleep(900); await idle();
const r1 = (await sql(`SELECT patt,skill FROM users WHERE usr='Tester'`)).rows[0];
check('P7 «+3» at the attack: +3 physical attack, one point spent', r1[0] === p0 + 3 && r1[1] === 11, JSON.stringify(r1));
check('P7 the page says what was raised', /Повышено: Физ\. атака \+3/.test(await ev(() => document.body.innerText)));
await page.select('select[name=what]', 'hp');
await page.$eval('input[name=col]', (e) => { e.value = '2'; });
const hpall0 = await one(`SELECT hpall FROM users WHERE usr='Tester'`);
await page.click('form[action="pers.php?ac=skill"] input[type=submit]');
await sleep(900); await idle();
const r2 = (await sql(`SELECT hpall,skill FROM users WHERE usr='Tester'`)).rows[0];
check('P7 the form: 2 points into life = +60 life, 2 points spent', r2[0] === hpall0 + 60 && r2[1] === 9, JSON.stringify(r2));
await page.select('select[name=what]', 'no');
await page.click('form[action="pers.php?ac=skill"] input[type=submit]');
await sleep(900); await idle();
check('P7 «Выбрать» spends nothing and asks to choose', /Выберите, что повышать/.test(await ev(() => document.body.innerText)) && (await one(`SELECT skill FROM users WHERE usr='Tester'`)) == 9);
await go('/pers.php?hp&mp');
check('P7 two stats in one link cost two points (not one)', (await one(`SELECT skill FROM users WHERE usr='Tester'`)) == 7);
check('P7 without skill points there are no «+» buttons', (await (async () => { await sql(`UPDATE users SET skill=0 WHERE usr='Tester'`); await go('/pers.php'); return (await count('a.lx-plus[href^="pers.php?"]')) === 0; })()));

// equipment page
await go('/pers.php?act=shmot');
check('P8 «Снаряжение»: the doll and a row with «Снять» for every worn item', (await count('.lx-doll .lx-sl.on')) === 12 && (await count('.lx-it')) === 12 && (await count('.lx-it a.lx-btn.red')) === 12, `${await count('.lx-doll .lx-sl.on')} / ${await count('.lx-it')}`);
check('P8 the tab «Снаряжение» is the current one', (await texts('.lx-tabs .lx-tab.on')).join() === 'Снаряжение');

// skills page
const mags = (await sql(`SELECT id,name,activ FROM mag WHERE usr='Tester'`)).rows;
await go('/pers.php?act=mag');
check('P9 «Умения»: one block per skill of the database', (await count('.lx-pn .lx-pt a.lx-sk')) === mags.length && mags.length > 0, `${await count('.lx-pn .lx-pt a.lx-sk')} vs ${mags.length}`);
if (mags.length) {
  const was = mags[0][2];
  await page.click('.lx-pn .lx-pt a.lx-sk');
  await sleep(900); await idle();
  const now = await one(`SELECT activ FROM mag WHERE id=${mags[0][0]}`);
  check('P9 a click on the name switches the skill on / off', was !== now, `${was} -> ${now}`);
}
check('P9 the delete button and the radio buttons are in one form', (await count('form[action$="activ=del"] input[type=radio][name=result]')) === mags.length && (await count('form[action$="activ=del"] input[type=submit]')) === 1);

// ---------------------------------------------------------------- S: profile of another player
await go('/search.php?go=go&nick=Mage01');
check('S1 profile: name bar, tabs, doll', (await count('.lx-who')) === 1 && (await count('.lx-doll .lx-sl')) === 13 && (await texts('.lx-tabs .lx-tab.on')).join() === 'Инфо', (await texts('.lx-who')).join());
const mw = (await sql(`SELECT tip FROM item WHERE usr='Mage01' AND image='yes'`)).rows.map((r) => r[0]);
const mon = await ev(() => [...document.querySelectorAll('.lx-doll .lx-sl.on')].map((e) => (/\bs-([a-z]+)\b/.exec(e.className) || [])[1]));
check('S1 the worn items of Mage01 are on the doll', mon.length === mw.length && mw.every((t) => mon.includes(t)), `${mon.join(',')} vs ${mw.join(',')}`);
check('S1 nothing can be taken off somebody else', (await count('.lx-doll a.lx-un')) === 0 && (await ev(() => [...document.querySelectorAll('.lx-doll a.lx-slot')].every((a) => /^inventar\.php\?mod=in&id=\d+$/.test(a.getAttribute('href'))))));
const mp = await rect('.lx-doll .lx-fig');
const mh = await ev(() => { const p = [...document.querySelectorAll('.lx-pn .lx-pt')].find((e) => /^Здоровье/.test(e.textContent)); return p ? p.getBoundingClientRect().top + scrollY : 0; });
check('S1 health and stats are under the portrait', mp && mh >= mp.b, `${mp && mp.b} vs ${mh}`);
const mage = (await sql(`SELECT patt,matt,pdef,mdef,hp,hpall FROM users WHERE usr='Mage01'`)).rows[0];
const ms = await ev(() => { const o = {}; document.querySelectorAll('.lx-kv .r').forEach((r) => { o[r.querySelector('i').textContent.trim()] = r.querySelector('b').textContent.replace(/\s+/g, ' ').trim(); }); return o; });
check('S1 his numbers are shown (attack, defence)', num(ms['Физ. атака']) === mage[0] && num(ms['Маг. атака']) === mage[1] && num(ms['Физ. защита']) === mage[2] && num(ms['Маг. защита']) === mage[3], JSON.stringify(ms));
check('S1 no sideways scroll, no PHP messages', (await noOverflow()) && !(await phpErrors()));
await page.click('.lx-tabs a.lx-tab');
check('S2 the tab «Снаряжение» opens the list', await waitPath('go=shmot') && (await count('.lx-doll')) === 1 && (await count('.lx-it')) === mw.length, await path());

// ---------------------------------------------------------------- B: blocks (no loose text on the bare window)
const orphanList = () => ev(() => {
  const bad = [];
  const cell = document.querySelector('td.centertd');
  if (!cell) return ['no content cell'];
  const SURF = /(^|\s)(msg|dot|inoy|pic|silka|info|event|news|forum|inf|pkboy|lich|head|down|down2|adm|admin|foot|navigation|zagolovok|paty|logtext|chattext|chat2|lx-[\w-]+)(\s|$)/;
  const w = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
  let n;
  while ((n = w.nextNode())) {
    if (!/\S/.test(n.nodeValue.replace(/\u00a0/g, ' ')) || n.parentElement.closest('script,style,noscript')) continue;
    let el = n.parentElement, ok = false;
    while (el && el !== cell) { if (SURF.test(typeof el.className === 'string' ? el.className : '')) { ok = true; break; } el = el.parentElement; }
    if (!ok) bad.push(n.nodeValue.trim().slice(0, 30));
  }
  return bad;
});
await sql(`UPDATE users SET hp=hpall WHERE usr='Tester'`);
await go('/to_gorod.php?');
const pages = ['/gorod.php?', '/bank.php?', '/job.php?', '/faq.php', '/rating.php', '/opt.php', '/clan.php', '/msg.php', '/news.php', '/online.php', '/users.php', '/okrestnosti.php', '/inventar.php', '/shop.php?', '/sklad.php?', '/chat.php?', '/lotomy.php?', '/portal.php?', '/bazar.php?', '/hram.php?', '/gallery.php?', '/pitomec.php?'];
const loose = {};
for (const u of pages) {
  await page.goto(BASE + u, { waitUntil: 'domcontentloaded' });
  await sleep(350);
  const o = await orphanList();
  if (o.length) loose[u] = o.slice(0, 3);
}
check('B1 on 22 pages every piece of text lives in a block', Object.keys(loose).length === 0, JSON.stringify(loose));
// a page change by AJAX gets its blocks as well
await page.goto(BASE + '/bank.php?', { waitUntil: 'domcontentloaded' });
await sleep(400);
await ev(() => { window.__m = 1; });
await page.click('.inoy a');
await sleep(900); await idle();
check('B2 after a page change without reload the new page has blocks, too', (await ev(() => window.__m)) === 1 && (await count('td.centertd .lx-blk')) > 0 && (await orphanList()).length === 0, await path());
await page.goBack();
await sleep(900); await idle();
check('B2 «Назад» brings the page back with its blocks', (await count('td.centertd .lx-blk')) > 0 && (await orphanList()).length === 0, await path());
// the script changes nothing but the wrappers: the text of the page is the same with and without it
const same = await ev(async () => {
  const r = await fetch(location.href, { credentials: 'same-origin' });
  const html = await r.text();
  const doc = new DOMParser().parseFromString(html, 'text/html');
  const norm = (s) => s.replace(/\s+/g, '');
  const text = (d) => { const c = d.querySelector('td.centertd').cloneNode(true); c.querySelectorAll('.lx-head').forEach((e) => e.remove()); return norm(c.textContent); };   // the header: mana regrows between the two requests
  const a = text(document);
  const b = text(doc);
  let i = 0; while (i < a.length && a[i] === b[i]) i++;
  return [a === b, a.length, b.length, a.slice(Math.max(0, i - 25), i + 25), b.slice(Math.max(0, i - 25), i + 25)];
});
check('B3 blocks only move nodes: the text of the page is exactly the server\'s text', same[0], JSON.stringify(same));
// guests: login and registration
const g = await open({ width: 390, height: 900, guest: true });
for (const u of ['/', '/reg.php?']) {
  await g.page.goto(BASE + u, { waitUntil: 'domcontentloaded' });
  await sleep(450);
  const o = await g.page.evaluate(() => {
    const bad = [];
    const cell = document.querySelector('td.centertd');
    const SURF = /(^|\s)(msg|dot|inoy|pic|silka|info|event|news|forum|inf|pkboy|lich|head|down|down2|adm|admin|foot|navigation|zagolovok|paty|logtext|chattext|chat2|lx-[\w-]+)(\s|$)/;
    const w = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
    let n;
    while ((n = w.nextNode())) {
      if (!/\S/.test(n.nodeValue.replace(/\u00a0/g, ' ')) || n.parentElement.closest('script,style,noscript')) continue;
      let el = n.parentElement, ok = false;
      while (el && el !== cell) { if (SURF.test(typeof el.className === 'string' ? el.className : '')) { ok = true; break; } el = el.parentElement; }
      if (!ok) bad.push(n.nodeValue.trim().slice(0, 30));
    }
    return bad;
  });
  check(`B4 guest page ${u}: every piece of text lives in a block`, o.length === 0, o.join(' | '));
}
await g.browser.close();

check('no JavaScript errors on any page', log.errors.length === 0, log.errors.join(' | '));
await browser.close();
done();
