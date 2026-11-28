// Checks the tile city (gorod.php), the three shops and the inventory against the demo server, on a phone-sized screen:
//   node demo/server.mjs --port 8080 &       cd ui && node shop_test.mjs        (BASE=http://127.0.0.1:8081 for another port)
// The database is reset and filled with a bag (seed.mjs) first.
import { BASE, open, reporter, reset, sleep, sql } from './lib.mjs';
import { seedBag } from './seed.mjs';

await reset();
await seedBag();
const { browser, page, log } = await open({ width: 390, height: 900 });
const { check, done } = reporter();
const ev = (fn, ...a) => page.evaluate(fn, ...a);
const path = () => ev(() => location.pathname + location.search);
const waitPath = async (re, ms = 8000) => { try { await page.waitForFunction((r) => new RegExp(r).test(location.pathname + location.search), { timeout: ms }, re); await sleep(250); return true; } catch (e) { return false; } };
const idle = async () => { try { await page.waitForFunction(() => !document.documentElement.classList.contains('lx-busy'), { timeout: 8000 }); } catch (e) { /* ignore */ } await sleep(150); };
const texts = (sel) => ev((s) => [...document.querySelectorAll(s)].map((e) => e.textContent.replace(/\s+/g, ' ').trim()), sel);
const count = (sel) => ev((s) => document.querySelectorAll(s).length, sel);
const num = (s) => Number(String(s).replace(/[^0-9]/g, ''));
const one = async (q) => { const r = await sql(q); return r.rows && r.rows[0] ? r.rows[0][0] : undefined; };
/** click by a real mouse click on an element chosen by selector + text; returns false when there is none */
const clickText = async (sel, text) => {
  const ok = await ev((s, t) => {
    const el = [...document.querySelectorAll(s)].find((e) => e.textContent.replace(/\s+/g, ' ').trim() === t);
    if (!el) return false;
    el.scrollIntoView({ block: 'center' });
    el.setAttribute('data-hit', '1');
    return true;
  }, sel, text);
  if (!ok) return false;
  await page.click('[data-hit="1"]');
  await ev(() => document.querySelectorAll('[data-hit]').forEach((e) => e.removeAttribute('data-hit')));
  return true;
};
const noOverflow = () => ev(() => document.documentElement.scrollWidth <= window.innerWidth);
const phpErrors = () => ev(() => /(Fatal error|Warning:|Notice:|Deprecated:|Parse error|Uncaught)/.test(document.body.innerText));

// ---------------------------------------------------------------- G: the city
await page.goto(BASE + '/to_gorod.php?', { waitUntil: 'domcontentloaded' });
await sleep(600);
await ev(() => { window.__m = 1; });
check('G0 «Город» (to_gorod.php) lands on gorod.php', /^\/gorod\.php/.test(await path()), await path());
const need = ['job.php?', 'akademwar.php?', 'home.php?', 'sklad.php?', 'hram.php?', 'gallery.php?', 'bank.php?', 'portal.php?', 'arena.php?', 'pitomec.php?', 'baf_vip.php?'];
const hrefs = await ev(() => [...document.querySelectorAll('.lx-tiles .lx-tile')].map((a) => a.getAttribute('href')));
check('G1 the 11 city links are tiles with the old targets', need.every((h) => hrefs.includes(h)), 'missing: ' + need.filter((h) => !hrefs.includes(h)).join(' '));
const geo = await ev(() => [...document.querySelectorAll('.lx-tiles:not(.big):not(.sm) .lx-tile')].slice(0, 9).map((a) => { const r = a.getBoundingClientRect(); return [Math.round(r.left), Math.round(r.top), Math.round(r.width)]; }));
check('G2 tiles are three per row and fit the screen', geo.length === 9 && geo[0][1] === geo[1][1] && geo[1][1] === geo[2][1] && geo[3][1] > geo[0][1] && geo.every((g) => g[0] >= 0 && g[0] + g[2] <= 390), JSON.stringify(geo.slice(0, 4)));
const pics = await ev(() => [...document.querySelectorAll('.lx-tiles .lx-tile .ic')].slice(0, 11).map((e) => getComputedStyle(e).backgroundImage));
check('G3 the 11 tiles have painted pictures', pics.length === 11 && pics.every((p) => /fantasy\/t-[a-z]+\.jpg/.test(p)), pics.filter((p) => !/t-/.test(p)).join(','));
check('G4 the PvP tile is red and the VIP buff tile is gold', await ev(() => /\bhot\b/.test(document.querySelector('a[href="arena.php?"]').className) && /\bvip\b/.test(document.querySelector('a[href="baf_vip.php?"]').className)));
check('G5 no sideways scroll', await noOverflow());
await page.click('a[href="gorod.php?pl=shop_zone"]');
check('G6 «Торговая площадь» opens in place', await waitPath('pl=shop_zone') && (await ev(() => window.__m)) === 1, await path());
const zone = await ev(() => [...document.querySelectorAll('.lx-tiles .lx-tile')].map((a) => a.getAttribute('href')));
check('G7 the shop zone has the shop tiles', ['shop.php?', 'shop_lux.php?', 'shop_vip.php?', 'shop_gold.php?', 'shopsoul.php?', 'bazar_vip.php?', 'col_bonus.php?'].every((h) => zone.includes(h)), zone.join(' '));

// ---------------------------------------------------------------- S: shops
await page.click('a.lx-tile[href="shop.php?"]');
check('S1 shop tile opens shop.php', await waitPath('^/shop\\.php'), await path());
check('S1 three section tiles (weapons, armour, jewellery)', (await count('.lx-tiles.big .lx-tile')) === 3, String(await count('.lx-tiles.big .lx-tile')));
await page.click('a.lx-tile[href="shop.php?mod=weapon"]');
check('S2 weapon shop opens', await waitPath('mod=weapon'), await path());
check('S2 red tabs show the open section', (await texts('.lx-tab.on')).join() === 'Оружие', (await texts('.lx-tab.on')).join());
const wDb = await sql(`SELECT name,cena FROM shop WHERE city != '' AND tip='weapon' AND tip2='ydar' AND nlvl=0 ORDER BY cena`);
const wCards = await texts('.lx-goods .lx-gd .nm');
check('S2 one card per weapon of the default kind and grade', wCards.length === wDb.rows.length && wCards.length > 0, `${wCards.length} vs ${wDb.rows.length}`);
const wPrices = (await texts('.lx-goods .lx-gd .lx-pr')).map(num);
check('S2 cards are sorted by price', wPrices.every((p, i) => i === 0 || p >= wPrices[i - 1]), wPrices.join(','));
check('S2 the grade mark of every card is NG', (await texts('.lx-goods .lx-gd .lx-gr')).every((t) => t === 'NG'));
check('S2 every card has a «Купить» button that leads to sell.php', await ev(() => [...document.querySelectorAll('.lx-goods .lx-gd')].every((c) => /^sell\.php\?act=ask&id=\d+$/.test(c.querySelector('a.lx-btn').getAttribute('href')))));
check('S2 chips: seven grades + nine kinds, the current ones are not links', (await count('.lx-chip')) === 16 && (await texts('.lx-chip.on')).join() === 'NG,Ударное', (await texts('.lx-chip.on')).join());
check('S2 no sideways scroll', await noOverflow());
await clickText('a.lx-chip', 'D');
check('S3 grade chip D', await waitPath('lvlus=20'), await path());
await clickText('a.lx-chip', 'Луки');
check('S3 kind chip «Луки» keeps the grade', await waitPath('tip2=luk') && /lvlus=20/.test(await path()), await path());
const bowDb = await sql(`SELECT name FROM shop WHERE city != '' AND tip='weapon' AND tip2='luk' AND nlvl=20 ORDER BY cena`);
check('S3 the bows of grade D are listed in price order', JSON.stringify(await texts('.lx-goods .lx-gd .nm')) === JSON.stringify(bowDb.rows.map((r) => r[0])), (await texts('.lx-goods .lx-gd .nm')).join('|'));
await clickText('a.lx-tab', 'Броня');
check('S4 «Броня» tab', await waitPath('mod=b'), await path());
check('S4 seven armour tiles', (await count('.lx-tiles.sm .lx-tile')) === 7, String(await count('.lx-tiles.sm .lx-tile')));
await page.click('a.lx-tile[href="shop.php?mod=b&o=golova"]');
check('S4 helmets open', await waitPath('o=golova'), await path());
const hDb = await one(`SELECT COUNT(*) FROM shop WHERE city != '' AND tip='golova' AND nlvl=0`);
check('S4 one card per helmet of grade NG', (await count('.lx-goods .lx-gd')) === Number(hDb), `${await count('.lx-goods .lx-gd')} vs ${hDb}`);
const firstName = (await texts('.lx-goods .lx-gd .nm'))[0];
await page.click('.lx-goods .lx-gd a.nm');
check('S5 info sheet opens', await waitPath('mod=info&id=\\d+'), await path());
check('S5 the sheet shows the name, the price and «Купить» / «Назад»', (await texts('.lx-sheet h3'))[0].indexOf(firstName) === 0 && (await count('.lx-sheet .lx-pr.big')) === 1 && (await texts('.lx-sheet .lx-acts2 a')).join() === 'Купить,Назад', (await texts('.lx-sheet h3')).join() + ' | ' + (await texts('.lx-sheet .lx-acts2 a')).join());
await clickText('.lx-sheet .lx-acts2 a', 'Назад');
check('S5 «Назад» returns to the same list', await waitPath('mod=b&lvlus=0&o=golova'), await path());
const money0 = Number(await one(`SELECT money FROM users WHERE usr='Tester'`));
const items0 = Number(await one(`SELECT COUNT(*) FROM item WHERE usr='Tester'`));
const price0 = num((await texts('.lx-goods .lx-gd .lx-pr'))[0]);
await page.click('.lx-goods .lx-gd a.lx-btn');
check('S6 «Купить» asks first', await waitPath('^/sell\\.php\\?act=ask&id=\\d+'), await path());
check('S6 the question shows the item with «Да, купить» / «Нет»', (await texts('.lx-sheet .lx-acts2 a')).join() === 'Да, купить,Нет' && (await texts('.lx-hint')).join().indexOf('действительно') >= 0, (await texts('.lx-sheet .lx-acts2 a')).join());
await clickText('.lx-sheet .lx-acts2 a', 'Да, купить');
check('S6 purchase goes through', await waitPath('^/sell\\.php\\?id=\\d+$') && (await texts('.lx-sheet h3')).join().indexOf('куплена') >= 0, (await texts('.lx-sheet h3')).join());
const money1 = Number(await one(`SELECT money FROM users WHERE usr='Tester'`));
const items1 = Number(await one(`SELECT COUNT(*) FROM item WHERE usr='Tester'`));
check('S6 one item added, the price is taken', items1 === items0 + 1 && money0 - money1 > 0 && money0 - money1 <= price0, `items ${items0}->${items1}, money ${money0}->${money1}, price ${price0}`);
await page.goto(BASE + '/sell.php?act=ask&id=abc', { waitUntil: 'domcontentloaded' });
check('S6 a bad id gives the error message', (await texts('.lx-hint')).join().indexOf('Ошибка') >= 0 && !(await phpErrors()), (await texts('.lx-hint')).join());
// jewellery
await page.goto(BASE + '/shopjuvilir.php?', { waitUntil: 'domcontentloaded' });
await sleep(300);
check('S7 jewellery shop: the «Украшения» tab is open and there are five tiles', (await texts('.lx-tab.on')).join() === 'Украшения' && (await count('.lx-tiles .lx-tile')) === 5, (await texts('.lx-tab.on')).join());
await ev(() => { window.__m = 2; });
await page.click('a.lx-tile[href="shopjuvilir.php?mod=mag&o=kolco"]');
check('S7 rings open in place', await waitPath('mod=mag&o=kolco') && (await ev(() => window.__m)) === 2, await path());
const kDb = await one(`SELECT COUNT(*) FROM shop WHERE city != '' AND tip='kolco' AND nlvl=0`);
check('S7 one card per ring, six grade chips (no R)', (await count('.lx-goods .lx-gd')) === Number(kDb) && (await count('a.lx-chip, span.lx-chip')) === 6, `${await count('.lx-goods .lx-gd')} vs ${kDb}, chips ${await count('a.lx-chip, span.lx-chip')}`);
await page.click('.lx-goods .lx-gd a.nm');
check('S7 ring sheet', await waitPath('^/shopjuvilir\\.php\\?mod=info&id=\\d+') && (await texts('.lx-sheet .lx-acts2 a')).join() === 'Купить,Назад', await path());
await page.goto(BASE + '/shop.php?mod=b&o=poyas&lvlus=20', { waitUntil: 'domcontentloaded' });
check('S7 earrings are listed under the jewellery tab', (await texts('.lx-tab.on')).join() === 'Украшения' && (await count('.lx-goods .lx-gd')) > 0, (await texts('.lx-tab.on')).join());
// robustness: junk in the address
for (const [u, what] of [['/shop.php?mod=b&o=zzz%27', 'unknown type'], ['/shop.php?mod=info&id=abc', 'bad id'], ['/shop.php?mod=info&id=999999', 'missing id'], ['/shopjuvilir.php?mod=mag&o=golova', 'wrong type in the jewellery shop']]) {
  await page.goto(BASE + u, { waitUntil: 'domcontentloaded' });
  check('S8 ' + what + ' gives «Ошибка!» and no PHP message', (await texts('.lx-hint')).join().indexOf('Ошибка') >= 0 && !(await phpErrors()), u);
}
await page.goto(BASE + '/shop.php?mod=weapon&tip2=zzz&lvlus=abc', { waitUntil: 'domcontentloaded' });
check('S8 an unknown kind falls back to the default one', (await texts('.lx-chip.on')).join() === 'NG,Ударное' && !(await phpErrors()), (await texts('.lx-chip.on')).join());
await page.goto(BASE + '/shop.php?mod=soulshots', { waitUntil: 'domcontentloaded' });
check('S9 the soulshot offer is a row with a «Купить» button', (await count('.lx-it')) === 1 && (await texts('.lx-it a.lx-btn')).join() === 'Купить');

// ---------------------------------------------------------------- I: inventory
await page.goto(BASE + '/inventar.php', { waitUntil: 'domcontentloaded' });
await sleep(400);
await ev(() => { window.__m = 3; });
const tilesInv = await ev(() => [...document.querySelectorAll('.lx-tiles .lx-tile')].map((a) => [a.getAttribute('href'), (a.querySelector('.bd') || {}).textContent || '', a.className]));
let badgeOk = true; const badgeInfo = [];
for (const [href, badge, cls] of tilesInv) {
  const m = /mod=item&o=([a-z]+)/.exec(href);
  if (!m) continue;
  const n = Number(await one(`SELECT COUNT(*) FROM item WHERE usr='Tester' AND tip='${m[1]}' AND image='not'`));
  const shown = badge === '' ? 0 : Number(badge);
  if (n !== shown || (n === 0 && !/zero/.test(cls))) { badgeOk = false; badgeInfo.push(`${m[1]}: db ${n} shown ${shown}`); }
}
check('I1 every item type has a tile with the number of things in the bag', tilesInv.filter((t) => /mod=item/.test(t[0])).length === 13 && badgeOk, badgeInfo.join('; '));
check('I1 three groups with headings', (await texts('.lx-sub')).join('|').indexOf('Экипировка') >= 0 && (await texts('.lx-sub')).join('|').indexOf('Плащи и украшения') >= 0 && (await texts('.lx-sub')).join('|').indexOf('Расходное') >= 0, (await texts('.lx-sub')).join('|'));
check('I1 the hint names the grade for level 24 (D)', (await texts('.lx-hint')).join().indexOf('D грейда') >= 0, (await texts('.lx-hint')).join());
check('I1 «Снять вещи» is still there', await count('a.lx-btn[href="pers.php?act=shmot"]') === 1);
check('I1 other tiles: soulshots, resources, recipes, pet things', ['inventar.php?mod=soulshots', 'inventar.php?mod=res', 'inventar.php?mod=shems', 'inventar.php?mod=pits'].every((h) => tilesInv.some((t) => t[0] === h)));
check('I1 no sideways scroll', await noOverflow());
await page.click('a.lx-tile[href="inventar.php?mod=item&o=golova"]');
check('I2 helmets open in place', await waitPath('mod=item&o=golova') && (await ev(() => window.__m)) === 3, await path());
const gDb = Number(await one(`SELECT COUNT(*) FROM item WHERE usr='Tester' AND tip='golova' AND image='not'`));
check('I2 one row per helmet in the bag', (await count('.lx-it')) === gDb && gDb >= 3, `${await count('.lx-it')} vs ${gDb}`);
const rank = { NG: 0, D: 1, C: 2, B: 3, A: 4, S: 5, R: 6 };
const marks = (await texts('.lx-it .lx-gr')).map((t) => rank[t]);
check('I2 the best grade is on top', marks.every((m, i) => i === 0 || m <= marks[i - 1]), marks.join(','));
check('I2 a heading for every grade', (await count('.lx-grp')) === new Set(marks).size && (await count('.lx-grp')) >= 2, String(await count('.lx-grp')));
check('I2 each row has «Одеть» and «Выкинуть»', await ev(() => [...document.querySelectorAll('.lx-it')].every((r) => { const a = [...r.querySelectorAll('.lx-acts2 a')].map((x) => x.textContent.trim()); return a.join() === 'Одеть,Выкинуть'; })));
// throw away: «Нет» changes nothing, «Да» removes the thing
const lastRow = gDb - 1;
await ev((i) => { document.querySelectorAll('.lx-it')[i].querySelector('a.lx-btn.red').setAttribute('data-hit', '1'); }, 0);
await page.click('[data-hit="1"]');
check('I3 «Выкинуть» asks first', await waitPath('off=\\d+$') && (await texts('.lx-sheet h3')).join().indexOf('Выкинуть вещь') === 0, (await texts('.lx-sheet h3')).join());
await clickText('.lx-sheet .lx-acts2 a', 'Нет');
check('I3 «Нет» returns to the list, nothing is lost', await waitPath('mod=item&o=golova$') && Number(await one(`SELECT COUNT(*) FROM item WHERE usr='Tester' AND tip='golova' AND image='not'`)) === gDb, await path());
await ev(() => { document.querySelectorAll('.lx-it')[0].querySelector('a.lx-btn.red').setAttribute('data-hit', '1'); });
await page.click('[data-hit="1"]');
await waitPath('off=\\d+$');
await clickText('.lx-sheet .lx-acts2 a', 'Да');
check('I3 «Да» throws the thing away', await waitPath('&yes$') && Number(await one(`SELECT COUNT(*) FROM item WHERE usr='Tester' AND tip='golova' AND image='not'`)) === gDb - 1 && (await texts('.lx-hint')).join().indexOf('выкинута') >= 0, (await texts('.lx-hint')).join());
// put on: the weakest grade is the last row (level 24 can wear NG and D)
await page.goto(BASE + '/inventar.php?mod=item&o=golova', { waitUntil: 'domcontentloaded' });
const wearId = await ev(() => { const rows = [...document.querySelectorAll('.lx-it')]; const a = rows[rows.length - 1].querySelector('.lx-acts2 a'); return /id=(\d+)/.exec(a.getAttribute('href'))[1]; });
await ev(() => { const rows = [...document.querySelectorAll('.lx-it')]; rows[rows.length - 1].querySelector('.lx-acts2 a').setAttribute('data-hit', '1'); });
await page.click('[data-hit="1"]');
check('I4 «Одеть» puts the helmet on', await waitPath('^/dress\\.php\\?tip=golova&id=\\d+') && (await texts('.lx-sheet h3')).join().indexOf('Вы одели') === 0 && (await one(`SELECT image FROM item WHERE id=${wearId}`)) === 'yes', (await texts('.lx-sheet h3')).join());
await page.goto(BASE + '/dress.php?tip=golova&id=' + wearId, { waitUntil: 'domcontentloaded' });
check('I4 a second helmet is refused with «Сначала снимите…»', (await texts('.lx-hint')).join().indexOf('Сначала снимите') >= 0 && (await count('a.lx-btn[href="pers.php?act=shmot"]')) === 1, (await texts('.lx-hint')).join());
// weapon list: many things, kind line, info sheet of a bag item
await page.goto(BASE + '/inventar.php?mod=item&o=weapon', { waitUntil: 'domcontentloaded' });
check('I5 weapon rows show the kind of the weapon', (await texts('.lx-it .sub')).length > 5 && (await texts('.lx-it .sub')).every((t) => /^[А-Яа-яЁё\/ ]+$/.test(t)), (await texts('.lx-it .sub')).slice(0, 3).join());
await page.click('.lx-it a.nm');
check('I5 the info sheet of a bag item can put it on or throw it away', await waitPath('mod=info&id=\\d+') && (await texts('.lx-sheet .lx-acts2 a')).join() === 'Одеть,Выкинуть' && (await count('a.lx-btn.grey')) === 1, (await texts('.lx-sheet .lx-acts2 a')).join());
// resources, 10 per page
await page.goto(BASE + '/inventar.php?mod=res', { waitUntil: 'domcontentloaded' });
const resBadges = await texts('.lx-tiles .lx-tile .bd');
const resDb = [await one(`SELECT COUNT(*) FROM res WHERE usr='Tester' AND tip='res'`), await one(`SELECT COUNT(*) FROM res WHERE usr='Tester' AND tip='elexir'`), await one(`SELECT COUNT(*) FROM res WHERE usr='Tester' AND tip='scroll'`)].map(String);
check('I6 resources / elixirs / scrolls tiles carry their counts', JSON.stringify(resBadges) === JSON.stringify(resDb), resBadges.join() + ' vs ' + resDb.join());
await page.click('a.lx-tile[href="inventar.php?mod=res&tip=res"]');
await waitPath('tip=res$');
check('I6 first page: ten rows and a pager', (await count('.lx-it')) === 10 && (await count('.lx-pager a')) === 1 && (await texts('.lx-pager b')).join() === '1', `${await count('.lx-it')} rows`);
await page.click('.lx-pager a');
check('I6 page 2: the rest', await waitPath('page=2') && (await count('.lx-it')) === Number(resDb[0]) - 10 && (await texts('.lx-pager b')).join() === '2', `${await count('.lx-it')} rows`);
await ev(() => document.querySelector('.lx-it a.lx-btn.red').setAttribute('data-hit', '1'));
await page.click('[data-hit="1"]');
check('I6 «Выкинуть всё» asks first and keeps the page', await waitPath('ofr=\\d+&page=2') && (await texts('.lx-sheet h3')).join().indexOf('Выкинуть') === 0, await path());
await clickText('.lx-sheet .lx-acts2 a', 'Да');
await waitPath('&yes&page=2');
check('I6 the resource is gone after «Да»', Number(await one(`SELECT COUNT(*) FROM res WHERE usr='Tester' AND tip='res'`)) === Number(resDb[0]) - 1);
// elixirs: radio + count -> elexirs.php
await page.goto(BASE + '/inventar.php?mod=res&tip=elexirs', { waitUntil: 'domcontentloaded' });
check('I7 elixirs: a radio button in every row and a count field', (await count('.lx-it input[type=radio]')) === 3 && (await count('input[name=col]')) === 1 && (await count('input[name=ok]')) === 1);
await page.click('.lx-it input[type=radio]');
await page.type('input[name=col]', '1');
const before = log.requests.length;
await page.click('input[name=ok]');
await sleep(1200);
check('I7 the form is sent to elexirs.php (POST) and answered', log.requests.slice(before).some((r) => r.startsWith('POST /elexirs.php')) && (await count('#lx-app')) === 1 && !(await phpErrors()), log.requests.slice(before).join(' '));
await page.goto(BASE + '/inventar.php?mod=res&tip=scroll', { waitUntil: 'domcontentloaded' });
check('I7 scrolls have «Использовать» links to scroll.php', await ev(() => [...document.querySelectorAll('.lx-it a.lx-btn')].every((a) => /^scroll\.php\?id=\d+$/.test(a.getAttribute('href')))) && (await count('.lx-it')) === 2);
// soulshots
await page.goto(BASE + '/inventar.php?mod=soulshots', { waitUntil: 'domcontentloaded' });
check('I8 soulshots of the grade of the worn weapon are listed', (await count('.lx-it')) === 1 && (await texts('.lx-it a.lx-btn')).join() === 'Выключить', (await texts('.lx-it a.lx-btn')).join());
await page.click('.lx-it a.lx-btn');
check('I8 the switch is saved', await waitPath('mod=shot_off') && (await texts('.lx-hint')).join().indexOf('Изменения сохранены') >= 0 && Number(await one(`SELECT status FROM soulshots WHERE usr='Tester' AND nlvl=0`)) === 1, (await texts('.lx-hint')).join());
// pet things and recipes
await page.goto(BASE + '/inventar.php?mod=pits', { waitUntil: 'domcontentloaded' });
check('I9 pet things: four tiles with counts', (await count('.lx-tiles .lx-tile')) === 4 && (await texts('.lx-tiles .lx-tile .bd')).join() === '2,1,1,1', (await texts('.lx-tiles .lx-tile .bd')).join());
await page.click('a.lx-tile[href="inventar.php?mod=pits&o=weapon"]');
await waitPath('o=weapon');
check('I9 two pet weapons', (await count('.lx-it')) === 2);
await page.click('.lx-it a.lx-btn');
check('I9 the pet thing sheet', await waitPath('mod=infopit&id=\\d+') && (await count('.lx-rows span')) === 5, await path());
await page.goto(BASE + '/inventar.php?mod=shems', { waitUntil: 'domcontentloaded' });
check('I10 recipes: two rows', (await count('.lx-it')) === 2);
await page.goto(BASE + '/inventar.php?mod=item&o=zzz', { waitUntil: 'domcontentloaded' });
check('I11 an unknown type gives «Ошибка!»', (await texts('.lx-hint')).join().indexOf('Ошибка') >= 0 && !(await phpErrors()));

// ---------------------------------------------------------------- O: no sideways scroll on a 360 px screen
await page.setViewport({ width: 360, height: 800 });
const urls = ['/gorod.php?', '/gorod.php?pl=shop_zone', '/shop.php?', '/shop.php?mod=weapon&lvlus=40&tip2=koppik', '/shop.php?mod=b', '/shop.php?mod=b&o=nogi&lvlus=20', '/shopjuvilir.php?', '/shopjuvilir.php?mod=mag&o=amulet&lvlus=40',
  '/inventar.php', '/inventar.php?mod=item&o=weapon', '/inventar.php?mod=res', '/inventar.php?mod=res&tip=res', '/inventar.php?mod=res&tip=elexirs', '/inventar.php?mod=soulshots', '/inventar.php?mod=pits', '/inventar.php?mod=shems'];
const wide = [];
for (const u of urls) {
  await page.goto(BASE + u, { waitUntil: 'domcontentloaded' });
  await sleep(250);
  if (!(await noOverflow())) wide.push(u);
}
check('O1 no page is wider than a 360 px screen', wide.length === 0, wide.join(' '));
await done(browser, log);
