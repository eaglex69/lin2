// Shared by the ui/*.mjs checks: starts headless Chromium (the build from @sparticuz/chromium, no system browser needed),
// opens one page that only talks to the demo server and signs in as a demo player.
import { createHash } from 'node:crypto';

process.env.AWS_EXECUTION_ENV = process.env.AWS_EXECUTION_ENV || 'AWS_Lambda_nodejs22.x';   // must be set BEFORE the module loads
const chromium = (await import('@sparticuz/chromium')).default;
const puppeteer = (await import('puppeteer-core')).default;

export const BASE = process.env.BASE || 'http://127.0.0.1:8080';
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** demo database: run an SQL statement (the demo uses SQLite behind the MySQL-flavoured API) */
export async function sql(q, base = BASE) {
  const r = await fetch(base + '/_demo/sql?q=' + encodeURIComponent(q));
  return r.json().catch(() => ({}));
}
export async function reset(base = BASE) { await fetch(base + '/_demo/reset'); }

export async function open({ base = BASE, nick = 'Tester', width = 540, height = 960, mobile = false, guest = false } = {}) {
  const browser = await puppeteer.launch({ executablePath: await chromium.executablePath(), headless: 'shell', args: [...chromium.args, '--no-sandbox', '--disable-gpu'] });
  const page = await browser.newPage();
  await page.setViewport({ width, height, deviceScaleFactor: 1, isMobile: mobile, hasTouch: mobile });
  await page.setRequestInterception(true);
  const log = { requests: [], errors: [] };
  page.on('request', (r) => {
    const u = r.url();
    log.requests.push(r.method() + ' ' + u.replace(base, ''));
    if (u.startsWith(base) || u.startsWith('data:') || u.startsWith('blob:')) r.continue(); else r.abort();   // no outside network
  });
  page.on('pageerror', (e) => log.errors.push('pageerror: ' + e.message));
  page.on('console', (m) => { if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) log.errors.push('console: ' + m.text()); });
  const host = new URL(base).hostname;
  const cookies = [{ name: 'demo_seen', value: '1', domain: host, path: '/' }];
  if (!guest) cookies.push({ name: 'nick', value: nick, domain: host, path: '/' }, { name: 'password', value: createHash('md5').update('tester1').digest('hex'), domain: host, path: '/' });
  await page.setCookie(...cookies);
  return { browser, page, log, base };
}

/** tiny assert-style reporter: check(name, ok, info); done() exits with 1 when something failed */
export function reporter() {
  let fails = 0;
  return {
    check(name, ok, info = '') { if (!ok) fails++; console.log(ok ? 'PASS' : 'FAIL', name, info); },
    async done(browser, log) {
      if (log) { console.log('JS errors:', log.errors.length ? '\n' + log.errors.join('\n') : 'none'); if (log.errors.length) fails++; }
      console.log(fails ? `\n${fails} FAILED` : '\nALL PASSED');
      if (browser) await browser.close();
      process.exit(fails ? 1 : 0);
    },
  };
}
