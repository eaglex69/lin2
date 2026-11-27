// Checks theme/ajax.js (page changes without reloads) against the demo server:
//   node demo/server.mjs --port 8080 &        cd ui && npm install && node ajax_test.mjs      (BASE=http://127.0.0.1:8081 for another port)
import { BASE, open, reporter, reset, sleep } from './lib.mjs';

await reset();
const { browser, page, log } = await open();
const { check, done } = reporter();
const ev = (fn, ...a) => page.evaluate(fn, ...a);
const path = () => ev(() => location.pathname + location.search);
const waitPath = async (re, ms = 6000) => { try { await page.waitForFunction((r) => new RegExp(r).test(location.pathname + location.search), { timeout: ms }, re); return true; } catch (e) { return false; } };

// ---- S1: link click, no reload ----
await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await sleep(400);
await ev(() => { window.__m = 1; window.scrollTo(0, 300); });
const y0 = await ev(() => window.pageYOffset);
check('S0 ajax.js is running', await ev(() => !!(window.LX && window.LX.ready)));
await page.click('a[href="news.php?"]');
check('S1 link click changes the address', await waitPath('^/news\\.php'), await path());
await sleep(300);
check('S1 no page reload (a marker set on the old page survives)', (await ev(() => window.__m)) === 1);
check('S1 exactly one frame and one header after the swap', (await ev(() => [document.querySelectorAll('#lx-app').length, document.querySelectorAll('.lx-head').length].join())) === '1,1');
check('S1 the new page starts at the top', (await ev(() => window.pageYOffset)) === 0);
check('S1 title is set', await ev(() => document.title.length > 0), await ev(() => document.title));

// ---- S2: back / forward ----
await ev(() => history.back());
check('S2 Back returns to /', await waitPath('^/$'), await path());
await sleep(500);
check('S2 Back does not reload', (await ev(() => window.__m)) === 1);
const y1 = await ev(() => window.pageYOffset);
check('S2 Back restores the scroll position', Math.abs(y1 - y0) <= 6, `before=${y0} after=${y1}`);
await ev(() => history.forward());
check('S2 Forward returns to news.php', await waitPath('^/news\\.php'), await path());
await sleep(300);
check('S2 Forward does not reload', (await ev(() => window.__m)) === 1);

// ---- S3: POST form (the chat form) ----
const msg = 'ajax test ' + Date.now();
await page.evaluate((m) => { document.querySelector('textarea[name=zin]').value = m; }, msg);
const before = log.requests.length;
await page.click('form[name=guestbook] input[type=submit]');
await sleep(1200);
const text = await ev(() => document.body.innerText);
check('S3 chat POST is answered in place', (await ev(() => window.__m)) === 1, await path());
check('S3 the answer is shown (the message, or the "10 minutes in the game" rule for a new character)', text.indexOf(msg) >= 0 || text.indexOf('В чате можно писать') >= 0);
check('S3 the POST went out as a background request', log.requests.slice(before).some((r) => r.startsWith('POST /chat.php')));

// ---- S4: GET form ----
await ev(() => { document.body.insertAdjacentHTML('beforeend', '<form id="tf" method="get" action="/search.php"><input name="nick" value="Tester"><input name="go" value="go"></form>'); document.getElementById('tf').requestSubmit(); });
check('S4 GET form opens search.php with its fields', await waitPath('^/search\\.php\\?nick=Tester&go=go$'), await path());
await sleep(300);
check('S4 GET form does not reload', (await ev(() => window.__m)) === 1);

// ---- S5: links that must be left to the browser ----
const res5 = await ev(() => {
  const out = {};
  const h = (e) => { out[e.target.id] = e.defaultPrevented; e.preventDefault(); };
  window.addEventListener('click', h);
  const mk = (id, attrs) => { const a = document.createElement('a'); a.id = id; a.textContent = id; for (const k in attrs) a.setAttribute(k, attrs[k]); document.body.appendChild(a); return a; };
  [
    mk('x_ext', { href: 'http://example.com/' }), mk('x_blank', { href: '/news.php', target: '_blank' }), mk('x_mail', { href: 'mailto:a@b.c' }),
    mk('x_zip', { href: '/foo.zip' }), mk('x_hash', { href: '#top' }), mk('x_off', { href: '/news.php', 'data-lx': 'off' }), mk('x_js', { href: 'javascript:void(0)' }),
    mk('x_dl', { href: '/news.php', download: '' }),
  ].forEach((a) => a.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 })));
  mk('x_mod', { href: '/news.php' }).dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, button: 0, ctrlKey: true }));
  window.removeEventListener('click', h);
  return out;
});
check('S5 other sites, target=_blank, mailto, files, #hash, data-lx="off", javascript:, download, Ctrl+click are left alone', Object.values(res5).every((v) => v === false), JSON.stringify(res5));

// ---- S6: an answer that is not one of our pages -> normal navigation ----
await ev(() => { window.__m = 1; const a = document.createElement('a'); a.id = 'x404'; a.href = '/nope_404.php'; a.textContent = 'x'; document.body.appendChild(a); a.click(); });
check('S6 a missing page opens', await waitPath('^/nope_404\\.php'), await path());
await sleep(900);
check('S6 ... as a real page load', (await ev(() => window.__m)) === undefined);

// ---- S7: scripts of the new page run ----
await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await sleep(400);
await ev(() => { window.__m = 1; });
const fn0 = await ev(() => typeof window.xhs);
await ev(() => LX.go('/chat.php'));
await waitPath('^/chat\\.php');
await sleep(500);
const fn1 = await ev(() => typeof window.xhs + '/' + typeof window.InsertSmile);
check('S7 inline scripts of chat.php run after the swap', /function/.test(fn1), `before=${fn0} after=${fn1}`);
check('S7 ... without a reload', (await ev(() => window.__m)) === 1);

// ---- S8: two quick navigations: the last one wins ----
await ev(() => { LX.go('/news.php?'); LX.go('/'); });
await sleep(1500);
check('S8 the last navigation wins', (await path()) === '/', await path());

// ---- S9: opening the page we are on does not stack history ----
const hl = await ev(() => history.length);
await ev(() => LX.go(location.href));
await sleep(700);
check('S9 same address replaces the history entry', (await ev(() => history.length)) === hl, `${hl} -> ${await ev(() => history.length)}`);

await done(browser, log);
