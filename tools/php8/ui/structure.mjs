// Debug helper: prints how the content cell of a page is built - the child nodes of td.centertd
// (text / tag.class), so that the "blocks" script can be checked against real pages.
//   AS=Tester node structure.mjs /bank.php? /job.php? ...   (all URLs share one session)
import { open, BASE } from './lib.mjs';

const urls = process.argv.slice(2);
if (!urls.length) { console.log('usage: node structure.mjs /path [/path ...]'); process.exit(2); }
const { browser, page, log } = await open({ nick: process.env.AS || 'Tester', width: +(process.env.W || 390) });
for (const u of urls) {
  await page.goto(BASE + u, { waitUntil: 'networkidle2' });
  await new Promise((r) => setTimeout(r, 300));
  const out = await page.evaluate(() => {
    const cell = document.querySelector('td.centertd');
    if (!cell) return 'no td.centertd';
    const d = (n) => {
      if (n.nodeType === 3) { const t = n.textContent.replace(/\s+/g, ' ').trim(); return t ? '"' + t.slice(0, 18) + '"' : ''; }
      if (n.nodeType !== 1) return '';
      return n.tagName.toLowerCase() + (n.id ? '#' + n.id : '') + (n.className && typeof n.className === 'string' ? '.' + n.className.trim().split(/\s+/).join('.') : '');
    };
    // descend into the wrappers that legacy pages leave open (font, center, plain div)
    const wrap = (n) => n.nodeType === 1 && (n.tagName === 'FONT' || n.tagName === 'CENTER' || (n.tagName === 'DIV' && !n.className && !n.id));
    const walk = (parent, depth) => {
      const out = [];
      for (const n of parent.childNodes) {
        const s = d(n);
        if (!s) continue;
        if (wrap(n) && depth < 4) out.push('[' + s + ' > ' + walk(n, depth + 1).join(' ') + ']');
        else out.push(s);
      }
      return out;
    };
    const kids = walk(cell, 0);
    return kids.length + ' nodes: ' + kids.slice(0, 60).join(' ');
  });
  console.log(u + '\n   ' + out.slice(0, 900));
}
if (log.errors.length) console.log('JS errors:', log.errors.join('\n'));
await browser.close();
