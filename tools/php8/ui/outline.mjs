// Debug helper: indented outline (tags, classes, short text) of the content cell td.centertd of a page.
//   AS=Tester node outline.mjs /to_gorod.php? /bank.php?        (all URLs share one session; the last one is printed)
//   DEPTH=7 LINES=120 node outline.mjs /pers.php
import { open, BASE } from './lib.mjs';

const urls = process.argv.slice(2);
if (!urls.length) { console.log('usage: node outline.mjs /path [/path ...]'); process.exit(2); }
const { browser, page, log } = await open({ nick: process.env.AS || 'Tester', width: +(process.env.W || 390) });
for (const u of urls) {
  await page.goto(BASE + u, { waitUntil: 'networkidle2' });
}
await new Promise((r) => setTimeout(r, 300));
const depth = +(process.env.DEPTH || 6);
const lines = +(process.env.LINES || 80);
const out = await page.evaluate((depth, lines) => {
  const cell = document.querySelector('td.centertd');
  if (!cell) return 'no td.centertd';
  const res = [];
  const walk = (n, d) => {
    if (res.length >= lines) return;
    const pad = '  '.repeat(d);
    if (n.nodeType === 3) {
      const t = n.textContent.replace(/\s+/g, ' ').trim();
      if (t) res.push(pad + '"' + t.slice(0, 40) + '"');
      return;
    }
    if (n.nodeType !== 1) return;
    const tag = n.tagName.toLowerCase();
    if (tag === 'script' || tag === 'style') return;
    const cls = typeof n.className === 'string' && n.className.trim() ? '.' + n.className.trim().split(/\s+/).join('.') : '';
    res.push(pad + '<' + tag + (n.id ? '#' + n.id : '') + cls + '>');
    if (d < depth) for (const c of n.childNodes) walk(c, d + 1);
  };
  for (const c of cell.childNodes) walk(c, 0);
  return res.join('\n');
}, depth, lines);
console.log(out);
if (log.errors.length) console.log('JS errors:', log.errors.join('\n'));
await browser.close();
