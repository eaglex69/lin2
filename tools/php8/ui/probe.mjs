// Debug helper: opens a page as a demo player and prints the result of a JS expression (and optionally saves a screenshot).
//   AS=Tester W=390 node probe.mjs /okrestnosti.php "document.querySelectorAll('table').length"
//   node probe.mjs /gorod.php "[...document.querySelectorAll('.lx-tile')].map(e=>e.getBoundingClientRect().width)"
import { open, BASE } from './lib.mjs';

const [url, expr] = process.argv.slice(2);
if (!url || !expr) { console.log('usage: node probe.mjs /path "js expression"'); process.exit(2); }
const { browser, page, log } = await open({ nick: process.env.AS || 'Tester', width: +(process.env.W || 390), mobile: !!process.env.MOBILE });
await page.goto(BASE + url, { waitUntil: 'networkidle2' });
await new Promise((r) => setTimeout(r, +(process.env.WAIT || 500)));
const out = await page.evaluate(`(function(){ try { return JSON.stringify((${expr}), null, 1); } catch (e) { return 'ERR ' + e.message; } })()`);
console.log(out);
if (log.errors.length) console.log('JS errors:', log.errors.join('\n'));
await browser.close();
