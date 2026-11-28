// Renders all theme/fantasy/*.svg icons + additional UI icons (g-mail, g-gift, ic-chevron)
// to crisp 2x retina transparent raster PNGs via headless Chromium, then removes all .svg files.
import { readdirSync, readFileSync, unlinkSync } from 'node:fs';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const HERE = dirname(fileURLToPath(import.meta.url));
process.env.AWS_EXECUTION_ENV = process.env.AWS_EXECUTION_ENV || 'AWS_Lambda_nodejs22.x';
const req = createRequire(join(HERE, '../ui/package.json'));
const puppeteerMod = req('puppeteer-core');
const puppeteer = puppeteerMod.default || puppeteerMod;
const chromiumMod = req('@sparticuz/chromium');
const chromium = chromiumMod.default || chromiumMod;

const FANTASY_DIR = resolve(HERE, '../../../theme/fantasy');

const EXTRA_SVGS = {
  'ic-chevron': {
    w: 12,
    h: 8,
    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="8" viewBox="0 0 12 8"><path d="M1 1l5 5 5-5" fill="none" stroke="#863f10" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
  },
  'g-mail': {
    w: 64,
    h: 64,
    svg: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
      <defs>
        <linearGradient id="p" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff8de"/><stop offset=".55" stop-color="#f2cd7a"/><stop offset="1" stop-color="#b97a28"/></linearGradient>
        <linearGradient id="f" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fffdf0"/><stop offset="1" stop-color="#e5b34c"/></linearGradient>
        <linearGradient id="r" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ff836a"/><stop offset=".55" stop-color="#d8301f"/><stop offset="1" stop-color="#8c140c"/></linearGradient>
      </defs>
      <g stroke="#2b1405" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round">
        <rect x="6" y="13" width="52" height="38" rx="6" fill="url(#p)"/>
        <path d="M8 49 L24 33 M56 49 L40 33" stroke="#7c4714" stroke-width="2"/>
        <path d="M7 15 L32 36 L57 15 Z" fill="url(#f)"/>
        <circle cx="32" cy="35" r="6.2" fill="url(#r)" stroke-width="2"/>
        <circle cx="30.3" cy="33.2" r="1.6" fill="#ffd9cf" stroke="none"/>
      </g>
    </svg>`,
  },
  'g-gift': {
    w: 64,
    h: 64,
    svg: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
      <defs>
        <linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff3b8"/><stop offset=".5" stop-color="#f3b93f"/><stop offset="1" stop-color="#a8601a"/></linearGradient>
        <linearGradient id="r" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ff836a"/><stop offset=".55" stop-color="#d8301f"/><stop offset="1" stop-color="#8c140c"/></linearGradient>
      </defs>
      <g stroke="#2b1405" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round">
        <rect x="11" y="28" width="42" height="28" rx="4" fill="url(#r)"/>
        <rect x="8" y="19" width="48" height="11" rx="3" fill="url(#r)"/>
        <rect x="28" y="19" width="8" height="37" fill="url(#g)"/>
        <rect x="11" y="38" width="42" height="7" fill="url(#g)"/>
        <path d="M32 19 C23 19 17 14 19 9 C21 5 29 9 32 19 Z" fill="url(#g)"/>
        <path d="M32 19 C41 19 47 14 45 9 C43 5 35 9 32 19 Z" fill="url(#g)"/>
      </g>
    </svg>`,
  },
};

const browser = await puppeteer.launch({
  executablePath: await chromium.executablePath(),
  headless: 'shell',
  args: [...chromium.args, '--no-sandbox', '--disable-gpu'],
});

try {
  const page = await browser.newPage();
  const svgFiles = readdirSync(FANTASY_DIR).filter(f => f.endsWith('.svg')).sort();
  const tasks = [];

  for (const file of svgFiles) {
    const raw = readFileSync(join(FANTASY_DIR, file), 'utf8');
    const svgTag = (/<svg\b[^>]*>/.exec(raw) || [''])[0];
    const vb = /viewBox="\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)\s*"/.exec(svgTag);
    const wm = /\bwidth="([\d.]+)"/.exec(svgTag);
    const hm = /\bheight="([\d.]+)"/.exec(svgTag);
    const w = wm ? Number(wm[1]) : (vb ? Number(vb[1]) : 64);
    const h = hm ? Number(hm[1]) : (vb ? Number(vb[2]) : 64);
    tasks.push({ name: file.replace(/\.svg$/, ''), w, h, svg: raw, srcFile: join(FANTASY_DIR, file) });
  }

  for (const [name, spec] of Object.entries(EXTRA_SVGS)) {
    tasks.push({ name, w: spec.w, h: spec.h, svg: spec.svg, srcFile: null });
  }

  for (const t of tasks) {
    await page.setViewport({ width: Math.ceil(t.w), height: Math.ceil(t.h), deviceScaleFactor: 2 });
    const html = `<!DOCTYPE html><html><head><style>
      html,body{margin:0;padding:0;width:${t.w}px;height:${t.h}px;background:transparent;overflow:hidden}
      svg{display:block;width:${t.w}px;height:${t.h}px}
    </style></head><body>${t.svg}</body></html>`;
    await page.setContent(html, { waitUntil: 'load' });
    const outPath = join(FANTASY_DIR, `${t.name}.png`);
    await page.screenshot({ path: outPath, omitBackground: true, clip: { x: 0, y: 0, width: t.w, height: t.h } });
    if (t.srcFile) unlinkSync(t.srcFile);
  }

  console.log(`Rasterized ${tasks.length} icons to PNG and removed ${svgFiles.length} SVG files.`);
} finally {
  await browser.close();
}
