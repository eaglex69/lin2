// Checks the animated battle (theme/battle.js + battle.php) against the demo server:
//   node demo/server.mjs --port 8080 &        cd ui && npm install && node battle_test.mjs [outdir-for-screenshots]
// 1. without JavaScript the same buttons are plain links and the fight works,
// 2. with JavaScript: the fight opens without a reload, a hit shows the floating damage, the enemy bar shrinks and leaves a
//    yellow trail, the enemy hits back, the button comes back ("ВАШ ХОД"), the killing blow ends in the victory page,
// 3. a lost fight ends in the defeat page.
import fs from 'node:fs';
import { BASE, open, reporter, reset, sleep, sql } from './lib.mjs';

const out = process.argv[2];
if (out) fs.mkdirSync(out, { recursive: true });
const { check, done } = reporter();

await reset();
// three Gremlins of the first page: 1228 dies from one blow, 1229 is sturdy and hits back, 1231 kills the player
await sql("UPDATE mobs SET lvl=1,hp=30000,hpall=30000,patt=2600 WHERE id=1229");
await sql("UPDATE mobs SET lvl=1,hp=40000,hpall=40000,patt=90000 WHERE id=1231");

const { browser, page, log } = await open();
const ev = (fn, ...a) => page.evaluate(fn, ...a);
const shot = async (name) => { if (out) await page.screenshot({ path: `${out}/${name}.png` }); };
const state = () => ev(() => {
  const g = (s) => document.querySelector(s);
  const side = (s) => { const e = g(s); return e ? { hp: +e.getAttribute('data-hp'), max: +e.getAttribute('data-max') } : null; };
  const w = (s) => { const e = g(s); return e ? e.getBoundingClientRect().width : -1; };
  return {
    url: location.pathname + location.search, marker: window.__m || 0, foe: side('.lx-foe'), me: side('.lx-me'),
    turn: g('#lx-turn') && g('#lx-turn').textContent, atk: g('#lx-atk') && g('#lx-atk').className,
    floats: Array.from(document.querySelectorAll('.lx-fl')).map((e) => e.className + '|' + e.textContent),
    trailW: w('.lx-foe .trail'), fillW: w('.lx-foe .fill'), end: g('.lx-end') && g('.lx-end').className, art: !!g('.lx-art img'),
  };
});
const waitFor = async (fn, ms = 6000) => { const t0 = Date.now(); for (;;) { const v = await fn(); if (v) return v; if (Date.now() - t0 > ms) return null; await sleep(60); } };

// ---------- 1. no JavaScript ----------
await page.setJavaScriptEnabled(false);
await page.goto(BASE + '/okrestnosti.php', { waitUntil: 'domcontentloaded' });
await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('a[href="ok.php?id=1228"]')]);
let s = await state();
check('N1 without JS: the fight screen opens (plain link, redirect)', s.url.startsWith('/battle.php') && s.foe && s.me && s.art, JSON.stringify(s));
check('N1 ... with the attack button', /lx-atk/.test(s.atk || ''));
await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#lx-atk')]);
s = await state();
check('N1 without JS: the hit works and the fight ends in a victory page', s.end === 'lx-end win', JSON.stringify(s));
await page.setJavaScriptEnabled(true);

// ---------- 2. with JavaScript ----------
await page.goto(BASE + '/okrestnosti.php', { waitUntil: 'domcontentloaded' });
await sleep(400);
await ev(() => { window.__m = 1; });
await page.click('a[href="ok.php?id=1229"]');
await waitFor(async () => (await state()).foe);
await sleep(700);
s = await state();
check('B1 the fight opens without a reload', s.url.startsWith('/battle.php') && s.marker === 1, s.url);
check('B1 enemy and player panels, card, "ВАШ ХОД", enabled attack button', s.foe && s.me && s.art && s.turn === 'ВАШ ХОД' && !/off/.test(s.atk), JSON.stringify(s));
check('B1 both bars are full', s.foe.hp === s.foe.max && s.me.hp === s.me.max);
await shot('1_before');

const seen = new Set();
await page.click('#lx-atk');
const t0 = Date.now();
let early = null;
let trailLag = false;
let turnFoe = false;
while (Date.now() - t0 < 4800) {
  const x = await state();
  x.floats.forEach((f) => seen.add(f.split('|')[0].replace('lx-fl ', '') + '|' + f.split('|')[1]));
  if (Date.now() - t0 > 350 && !early) { early = x; await shot('2_hit_450'); }
  if (x.trailW > x.fillW + 2) trailLag = true;
  if (x.turn === 'ХОД ВРАГА') turnFoe = true;
  if (x.floats.some((f) => f.startsWith('lx-fl me')) && Date.now() - t0 > 900) await shot('3_enemy_hits');
  if (Date.now() - t0 > 1200 && !/off/.test(x.atk || '') && x.turn === 'ВАШ ХОД') break;
  await sleep(60);
}
s = await state();
check('B2 the blow shows the damage as a floating number over the card', [...seen].some((f) => /^card atk\|-\d/.test(f)), [...seen].join(' ; '));
check('B2 the enemy HP went down', s.foe.hp < s.foe.max, `${s.foe.hp}/${s.foe.max}`);
check('B2 the enemy bar leaves a yellow trail (it shrinks after the red bar)', trailLag);
check('B2 the attack button is grey while the round is played', early && /off/.test(early.atk || ''), early && early.atk);
check('B2 the enemy hits back: red number over the player panel, player HP went down', [...seen].some((f) => /^me\|-\d/.test(f)) && s.me.hp < s.me.max, `${s.me.hp}/${s.me.max}`);
check('B2 "ХОД ВРАГА" is shown while the enemy hits', turnFoe);
check('B2 the button is back and it is "ВАШ ХОД" again, no page reload', !/off/.test(s.atk || '') && s.turn === 'ВАШ ХОД' && s.marker === 1 && s.url === '/battle.php', JSON.stringify({ atk: s.atk, turn: s.turn, url: s.url }));
check('B2 the round was fetched in the background (no navigation to the hit address)', log.requests.some((r) => r === 'GET /battle.php?mod=ataka&udar=at') && s.url === '/battle.php');

// killing blow
await sql('UPDATE mobs SET hp=1 WHERE id=1229');
await page.click('#lx-atk');
let sawWin = false;
const t1 = Date.now();
while (Date.now() - t1 < 5000) { const x = await state(); if (x.turn === 'ПОБЕДА') sawWin = true; if (x.end) { s = x; break; } await sleep(60); }
check('B3 the killing blow: "ПОБЕДА" on the card, then the victory page', sawWin && s.end === 'lx-end win', JSON.stringify({ sawWin, end: s.end }));
check('B3 ... still without a reload', s.marker === 1);
await shot('4_victory');
await page.click('.lx-acts a');
await waitFor(async () => (await ev(() => /okrestnosti/.test(location.pathname))));
check('B3 "Продолжить" leads back to the list without a reload', (await state()).marker === 1);

// ---------- 3. defeat ----------
await ev(() => { const a = document.querySelector('a[href="ok.php?id=1231"]'); if (a) a.click(); });
await waitFor(async () => (await state()).foe);
await sleep(600);
await page.click('#lx-atk');
let sawLose = false;
const t2 = Date.now();
while (Date.now() - t2 < 5000) { const x = await state(); if (x.turn === 'ПОРАЖЕНИЕ') sawLose = true; if (x.end) { s = x; break; } await sleep(60); }
check('B4 a lost fight: "ПОРАЖЕНИЕ", then the defeat page', sawLose && s.end === 'lx-end lose', JSON.stringify({ sawLose, end: s.end }));
await shot('5_defeat');

await done(browser, log);
