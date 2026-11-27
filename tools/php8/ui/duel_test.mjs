// Checks the turn based fight screens (pk.php, combat.php, fight.php) restyled with inc/lin2_ui.php (lin2_ui_duel) and the way
// battle.js carries the HP bars from one page to the next:
//   node demo/server.mjs --port 8080 &        cd ui && npm install && node duel_test.mjs [outdir-for-screenshots]
import fs from 'node:fs';
import { BASE, open, reporter, reset, sleep, sql } from './lib.mjs';

const out = process.argv[2];
if (out) fs.mkdirSync(out, { recursive: true });
const { check, done } = reporter();
const now = Math.floor(Date.now() / 1000);

await reset();
await sql(`INSERT INTO pk (usr,enemy,napal,xod,tip,ltime,block,udar,mag,pk) VALUES ('Tester','Mage01',1,'not','','${now}',0,0,0,''),('Mage01','Tester',0,'not','','${now}',0,0,0,'')`);

const { browser, page, log } = await open();
const ev = (fn, ...a) => page.evaluate(fn, ...a);
const shot = async (name) => { if (out) await page.screenshot({ path: `${out}/${name}.png` }); };
const view = () => ev(() => ({
  url: location.pathname + location.search, marker: window.__m || 0,
  foe: document.querySelector('.lx-foe') && +document.querySelector('.lx-foe').getAttribute('data-hp'),
  me: document.querySelector('.lx-me') && +document.querySelector('.lx-me').getAttribute('data-hp'),
  floats: Array.from(document.querySelectorAll('.lx-fl')).map((e) => e.className.replace('lx-fl ', '') + '|' + e.textContent),
  text: document.querySelector('.centertd').innerText.replace(/\s+/g, ' '),
}));

// ---- pk.php ----
await page.goto(BASE + '/pk.php', { waitUntil: 'domcontentloaded' });
await sleep(500);
await ev(() => { window.__m = 1; });
const dom = await ev(() => ({
  duel: !!document.querySelector('.lx-bt.lx-duel'), form: document.querySelector('.lx-duel form') && document.querySelector('.lx-duel form').getAttribute('action'),
  selects: Array.from(document.querySelectorAll('.lx-duel select')).map((s) => s.name + ':' + s.options.length).join(','), btn: !!document.querySelector('button#lx-atk[type=submit]'),
  foe: document.querySelector('.lx-foe') && document.querySelector('.lx-foe').getAttribute('data-name'), card: !!document.querySelector('.lx-card .lx-art img'),
}));
check('D1 pk.php shows the duel screen: enemy panel, card, hit/block pickers, round button', dom.duel && dom.form === 'pk.php?mod=ataka' && dom.selects === 'udar:3,block:3' && dom.btn && dom.foe === 'Mage01' && dom.card, JSON.stringify(dom));
await shot('1_duel');

await page.select('select[name=udar]', '1');
await page.select('select[name=block]', '2');
await page.click('#lx-atk');
await sleep(1500);
let v = await view();
check('D2 the move is sent in the background and the page says "waiting for the opponent"', v.marker === 1 && /Противник не сделал хода/.test(v.text) && log.requests.some((r) => r === 'POST /pk.php?mod=ataka'), v.text.slice(0, 120));

// the opponent moves; refresh -> the round is played
await sql("UPDATE pk SET xod='wait',tip='ataka',udar=2,block=3 WHERE usr='Mage01'");
await page.click('a[href^="pk.php?r="]');
await sleep(1200);
v = await view();
check('D3 the round is played ("Ход окончен") without a reload', v.marker === 1 && /Ход окончен/.test(v.text), v.text.slice(0, 120));

// the blows land (the demo data makes damage 0): put the numbers in the database, then "Продолжить"
await sql("UPDATE users SET hp=1100 WHERE usr='Mage01'");
await sql("UPDATE users SET hp=2000 WHERE usr='Tester'");
await page.click('a[href^="pk.php?r="]');
const seen = new Set();
const t0 = Date.now();
while (Date.now() - t0 < 3200) { (await view()).floats.forEach((f) => seen.add(f)); if (Date.now() - t0 > 800) await shot('2_duel_round'); await sleep(60); }
v = await view();
check('D4 the next round: the enemy bar goes 1900 -> 1100 with a floating "-800" over the card', v.foe === 1100 && [...seen].some((f) => /^card atk\|-800$/.test(f)), [...seen].join(' ; '));
check('D4 ... and your bar goes down with a red number', [...seen].some((f) => /^me\|-\d+$/.test(f)), [...seen].join(' ; '));

// ---- combat.php (arena) ----
await sql('DELETE FROM pk');
await sql(`INSERT INTO arena (usr,enemy,xod,tip,ltime,block,udar,mag,pk) VALUES ('Tester','Mage01','not','','${now}',0,0,0,''),('Mage01','Tester','not','','${now}',0,0,0,'')`);
await page.goto(BASE + '/combat.php', { waitUntil: 'domcontentloaded' });
await sleep(400);
const ar = await ev(() => ({ form: document.querySelector('.lx-duel form') && document.querySelector('.lx-duel form').getAttribute('action'), foe: document.querySelector('.lx-foe') && document.querySelector('.lx-foe').getAttribute('data-name') }));
check('D5 combat.php (arena) shows the same screen', ar.form === 'combat.php?mod=ataka' && ar.foe === 'Mage01', JSON.stringify(ar));

// ---- fight.php (castle guard) ----
await sql('DELETE FROM arena');
await sql(`INSERT INTO tmp_zamok (usr,mob,city,ltime) VALUES ('Tester',28,'Orc Village','${now}')`);
await sql("UPDATE guards SET hp=9000,status='battle',oponent='Tester' WHERE id=28");
await page.goto(BASE + '/fight.php', { waitUntil: 'domcontentloaded' });
await sleep(400);
const fg = await ev(() => ({ form: document.querySelector('.lx-duel form') && document.querySelector('.lx-duel form').getAttribute('action'), foe: document.querySelector('.lx-foe') && document.querySelector('.lx-foe').getAttribute('data-name'), udar: document.querySelector('select[name=udar]') && document.querySelector('select[name=udar]').options.length }));
check('D6 fight.php (castle guard) shows the same screen, skills are options of the "hit" list', fg.form === 'fight.php?mod=ataka' && /Guard/.test(fg.foe || '') && fg.udar >= 3, JSON.stringify(fg));
await shot('3_castle');

await done(browser, log);
