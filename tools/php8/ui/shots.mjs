// Full-page screenshots of pages of the demo server:
//   node shots.mjs <outdir> [--as Tester|Mage01|Admin1] [--guest] [--w 540] [--prefix p] [--maxh 4000] [--base http://127.0.0.1:8080] /url1 /url2 ...
// (the demo signs the player in by cookies; Admin1 is the administrator, visit gm/ and total/ only as Admin1: a normal player
//  who opens them gets banned by the game itself)
import fs from 'node:fs';
import { open, sleep } from './lib.mjs';

const a = process.argv.slice(2);
const outdir = a.shift();
const o = { as: 'Tester', guest: false, w: 540, h: 900, prefix: 'p', base: process.env.BASE || 'http://127.0.0.1:8080', maxh: 4000 };
const urls = [];
while (a.length) { const x = a.shift(); if (x === '--guest') o.guest = true; else if (x.startsWith('--')) o[x.slice(2)] = a.shift(); else urls.push(x); }
if (!outdir || !urls.length) { console.log('usage: node shots.mjs <outdir> [--as Nick] [--guest] [--w 540] [--prefix p] [--maxh 4000] /url ...'); process.exit(2); }
fs.mkdirSync(outdir, { recursive: true });

const { browser, page, log } = await open({ base: o.base, nick: o.as, guest: o.guest, width: Number(o.w), height: Number(o.h) });
let n = 0;
for (const u of urls) {
  try {
    log.errors.length = 0;
    const r = await page.goto(o.base + u, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await sleep(500);
    const name = `${o.prefix}_${String(++n).padStart(2, '0')}_${u.replace(/[^a-z0-9]+/gi, '_').replace(/^_|_$/g, '').slice(0, 40) || 'root'}.png`;
    const h = await page.evaluate(() => document.documentElement.scrollHeight);
    await page.screenshot(h > Number(o.maxh) ? { path: `${outdir}/${name}`, clip: { x: 0, y: 0, width: Number(o.w), height: Number(o.maxh) } } : { path: `${outdir}/${name}`, fullPage: true });
    console.log(r ? r.status() : '-', u, '->', name, `(h=${h})`, await page.title(), log.errors.length ? 'JSERR ' + log.errors.join('|') : '');
  } catch (e) { console.log('FAIL', u, e.message.split('\n')[0]); }
}
await browser.close();
process.exit(0);
