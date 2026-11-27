// Looks for pages that are wider than a phone screen (horizontal scrolling):
//   node overflow.mjs <width> /url1 /url2 ...        (AS=Admin1 for the admin pages, BASE=http://127.0.0.1:8081 for another server)
// Prints OVERFLOW for a page wider than the screen, BANNED when the page is the "your character was blocked" notice
// (then the check says nothing about the page itself), ok otherwise. Exit code 1 when something overflows.
import { BASE, open, sleep } from './lib.mjs';

const W = Number(process.argv[2] || 360);
const urls = process.argv.slice(3);
if (!urls.length) { console.log('usage: node overflow.mjs <width> /url ...'); process.exit(2); }
const { browser, page } = await open({ width: W, height: 800, mobile: true, nick: process.env.AS || 'Tester' });
let bad = 0;
for (const u of urls) {
  try {
    await page.goto(BASE + u, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await sleep(350);
    const r = await page.evaluate(() => {
      const de = document.documentElement;
      let worst = null;
      document.querySelectorAll('body *').forEach((e) => {
        const b = e.getBoundingClientRect();
        if (b.right > de.clientWidth + 1 && b.width > 0 && getComputedStyle(e).position !== 'fixed' && (!worst || b.right > worst.right)) worst = { tag: e.tagName + '.' + e.className, right: Math.round(b.right) };
      });
      return { sw: de.scrollWidth, cw: de.clientWidth, worst, banned: /Заблокирован/.test(document.body.innerText) };
    });
    const over = r.sw > r.cw;
    if (over) bad++;
    console.log(over ? 'OVERFLOW' : (r.banned ? 'BANNED  ' : 'ok      '), u, JSON.stringify(r));
  } catch (e) { console.log('FAIL', u, e.message.split('\n')[0]); }
}
await browser.close();
process.exit(bad ? 1 : 0);
