// Click simulator: opens a page of the game as a logged-in player and CLICKS its links the way a browser does - cookies and
// the PHP session are kept, redirects are followed, a path that is not a file of the site gets the web server's 404.
//
//   node smoke/click.mjs [--php 8.3] [--page okrestnosti.php] [--link <regex on href>] [--all] [--prod]
//
//   --page   page to open (default okrestnosti.php, the list of mobs; "okrestnosti.php?mod=info&id=10" is the mob card)
//   --link   which links to click: a regex tested against the href as written in the HTML (default: the "attack the mob" links, ok1231 or ok.php?id=1231)
//   --all    click every distinct matching link (at most 12), not just the first one
//   --then   (repeatable) after the first click, click the first link of the page you landed on that matches this regex, and so on:
//            --link '^ok\.php' --then '^battle\.php\?mod=ataka' = attack the mob, then hit it
//   --show   list the links of every page you land on (what could the player click here?)
//   ROOT=<dir> tests another copy of the site (e.g. an unpacked archive, or `git archive <old sha>` to reproduce a bug)
//
// This is what run.mjs --links cannot do: a link such as <a href="ok1231"> points at a "file" that never existed (the old host
// rewrote it), and only a request shows what the server does with it. Exit code 1 when a click ends in 404, a PHP fatal error,
// or a page whose own links lead nowhere.
import { startFakeDb } from './fakedb.mjs';
import { startPhp, REPO } from '../common.mjs';
import { findBadLinks, linkTargets } from './links.mjs';
import { cpSync, existsSync, statSync, rmSync } from 'node:fs';
import { join, posix } from 'node:path';
import { tmpdir } from 'node:os';

const args = process.argv.slice(2);
const opt = (name, def) => { const i = args.indexOf('--' + name); return i >= 0 ? (args[i + 1] && !args[i + 1].startsWith('--') ? args[i + 1] : true) : def; };
const VERSION = String(opt('php', '8.3'));
const PAGE = String(opt('page', 'okrestnosti.php'));
const LINK = new RegExp(String(opt('link', '^ok(?:\\d|\\.php)')));      // ok1231 (old pretty URL) or ok.php?id=1231
const ALL = !!opt('all', false);
const SHOW = !!opt('show', false);
const THEN = args.flatMap((a, i) => a === '--then' && args[i + 1] ? [new RegExp(args[i + 1])] : []);
const PROD = !!opt('prod', false);
const COOKIES = { nick: 'Tester', password: 'abc' };      // the logged-in player of the smoke tests (see fakedb.mjs)

const RUNNER = `<?php
error_reporting(-1);
// messages go to the error log (stderr), not into the page: a warning printed before header() would break the very redirects
// this tool follows ("headers already sent") - that is how PHP behaves, not a bug of the game
ini_set('display_errors', '0');
ini_set('html_errors', '0');
ini_set('log_errors', '1');
ini_set('memory_limit', '256M');
$__target = getenv('SMOKE_TARGET');
chdir(dirname($__target));
require $__target;
`;

const TMP = join(tmpdir(), 'lin2_click_' + process.pid);
cpSync(REPO, TMP, { recursive: true, filter: p => !/[\\/](\.git|node_modules)([\\/]|$)/.test(p) && !/[\\/]tools[\\/]php8([\\/]|$)/.test(p) });
const db = await startFakeDb({ schemaPath: join(REPO, 'shema.sql'), remember: true });      // remember: ok.php's INSERT INTO tmp ("in battle") is visible to battle.php
const php = await startPhp(VERSION, { '/work': TMP });
if (!php.fileExists('/internal/smoke')) php.mkdir('/internal/smoke');
php.writeFile('/internal/smoke/runner.php', RUNNER);
const env = { LIN2_DB_HOST: '127.0.0.1:' + db.port, LIN2_DB_USER: 'smoke', LIN2_DB_PASS: 'smoke', LIN2_DB_NAME: 'siska' };
if (!PROD) env.LIN2_DEBUG = '1';

// ---- a minimal web server + browser -----------------------------------------------------------------------------------
class Browser {
  constructor() { this.jar = { ...COOKIES }; }
  cookieHeader() { return Object.entries(this.jar).map(([k, v]) => `${k}=${v}`).join('; '); }
  // one HTTP request; `url` is an absolute path with an optional query: /ok.php?id=10
  async request(url) {
    const [pathPart, ...q] = url.split('?');
    const query = q.join('?');
    let path = posix.normalize(decodeURIComponent(pathPart));
    let file = join(TMP, path);
    if (existsSync(file) && statSync(file).isDirectory()) { path = posix.join(path, 'index.php'); file = join(TMP, path); }   // DirectoryIndex
    if (!existsSync(file)) return { status: 404, url, path, text: `Not Found - The requested URL ${pathPart} was not found on this server.`, location: null };
    if (!path.endsWith('.php')) return { status: 200, url, path, text: '(static file)', location: null };
    let text = '', errors = '', status = 200, location = null, thrown = null;
    try {
      const r = await php.run({
        scriptPath: '/internal/smoke/runner.php',
        relativeUri: path + (query ? '?' + query : ''),
        method: 'GET',
        headers: { 'user-agent': 'Mozilla/5.0 (click)', cookie: this.cookieHeader() },
        env: { SMOKE_TARGET: '/work' + path, ...env },
        $_SERVER: { SCRIPT_FILENAME: '/work' + path, SCRIPT_NAME: path, PHP_SELF: path, DOCUMENT_ROOT: '/work', REMOTE_ADDR: '127.0.0.1', HTTP_HOST: 'localhost', SERVER_NAME: 'localhost' },
      });
      text = r.text; errors = r.errors || ''; status = r.httpStatusCode;
      location = (r.headers.location && r.headers.location[0]) || null;
      for (const c of r.headers['set-cookie'] || []) { const m = /^([^=;\s]+)=([^;]*)/.exec(c); if (m) this.jar[m[1]] = m[2]; }
    } catch (e) {
      thrown = String(e && e.message || e).split('\n')[0];
      if (e && e.response) { try { text = e.response.text; errors = e.response.errors || ''; } catch { /* ignore */ } }
    }
    const fatal = /(?:Fatal error|Parse error): (.*?) in \/work\/(\S+) on line (\d+)/.exec(text + '\n' + errors) || (PROD ? /PHP Uncaught (\w+): (.*?) in \/work\/(\S+?):(\d+)/.exec(errors) : null);
    return { status, url, path, text, errors, location, thrown, fatal: fatal ? fatal.slice(1).join(' ') : (thrown || null) };
  }
  // follows redirects like a browser; returns the list of hops
  async visit(url, hops = []) {
    for (let i = 0; i < 6; i++) {
      const r = await this.request(url);
      hops.push(r);
      if (!r.location || r.status < 300 || r.status >= 400) return hops;
      url = /^https?:/i.test(r.location) ? r.location.replace(/^https?:\/\/[^/]+/i, '') || '/' : posix.join(r.location[0] === '/' ? '/' : posix.dirname(r.path) + '/', r.location);
    }
    hops.push({ status: 0, url, path: url, text: '', fatal: 'too many redirects' });
    return hops;
  }
}

const resolveHref = (pagePath, href) => {
  const h = href.replace(/&amp;/g, '&').trim();
  if (h[0] === '/') return h;
  const [p, ...q] = h.split('?');
  return posix.normalize(posix.join(posix.dirname(pagePath), p || posix.basename(pagePath))) + (q.length ? '?' + q.join('?') : '');
};

// ---- scenario ------------------------------------------------------------------------------------------------------------
let failed = false;
const say = (s = '') => console.log(s);
// warnings / deprecations the page printed (debug mode only), without the "Undefined variable / array key" noise of old code
const NOISE = /^(Undefined variable|Undefined array key|Undefined property|Trying to access array offset|Attempt to read property)/;
const diagnostics = text => [...text.matchAll(/^(?:PHP )?(Warning|Notice|Deprecated|Fatal error):\s+(.*?) in \/work\/(\S+) on line (\d+)/gm)].filter(m => !NOISE.test(m[2])).map(m => `${m[1]}: ${m[2].slice(0, 130)} (${m[3]}:${m[4]})`);
const flag = r => r.status === 404 ? '404 NOT FOUND' : r.fatal ? 'PHP FATAL: ' + r.fatal.slice(0, 160) : String(r.status);

const b = new Browser();
const startUrl = '/' + PAGE.replace(/^\//, '');
say(`PHP ${VERSION}${PROD ? ' (production mode)' : ''}, site: ${REPO}`);
const first = await b.visit(startUrl);
const page = first[first.length - 1];
say(`open   GET ${startUrl}  ->  ${flag(page)}${first.length > 1 ? `  (after ${first.length - 1} redirect${first.length > 2 ? 's' : ''}, now at ${page.url})` : ''}`);
if (page.status !== 200 || page.fatal) { failed = true; }

const links = [...new Map(linkTargets(page.text).filter(([k]) => k === 'a').map(([, h]) => [h, h])).keys()].filter(h => LINK.test(h.replace(/&amp;/g, '&')));
if (!links.length) { say(`       no link matching /${LINK.source}/ on this page - nothing to click`); failed = true; }
const todo = ALL ? links.slice(0, 12) : links.slice(0, 1);
say(`       ${links.length} link(s) match /${LINK.source}/${ALL ? '' : ', clicking the first'}`);

async function click(from, href, label = 'click ') {
  const target = resolveHref(from.path, href);
  const hops = await b.visit(target);
  say(`${label} <a href="${href}">  ->  ${target}`);
  hops.forEach((h, i) => {
    say(`         ${i === 0 ? 'GET' : ' ->'} ${h.url}   ${flag(h)}${h.location ? '   Location: ' + h.location : ''}`);
    if (h.status === 404 || h.fatal) failed = true;
  });
  const last = hops[hops.length - 1];
  if (last.status === 200 && last.path.endsWith('.php')) {
    const bad = findBadLinks(TMP, last.path.slice(1), last.text, null).bad;
    if (bad.length) { say(`         the page you land on has ${bad.length} link(s) to missing files, e.g. ${bad.slice(0, 3).map(x => x.target).join(', ')}`); failed = true; }
    const d = diagnostics(last.errors || '');
    if (d.length) say(`         PHP messages on this page (debug mode): ${d.length}${d.length > 8 ? ', first 8' : ''}\n           ` + d.slice(0, 8).join('\n           '));
    if (SHOW) say('         links here: ' + [...new Set(linkTargets(last.text).filter(([k]) => k === 'a').map(([, h]) => h.replace(/&amp;/g, '&')))].slice(0, 30).join('   '));
  }
  return last;
}

let lastPage = page;
for (const href of todo) lastPage = await click(page, href);
for (const re of THEN) {                       // --then: keep clicking along one path
  const next = [...new Set(linkTargets(lastPage.text).filter(([k]) => k === 'a').map(([, h]) => h))].find(h => re.test(h.replace(/&amp;/g, '&')));
  if (!next) { say(`then   no link matching /${re.source}/ on ${lastPage.url}`); failed = true; break; }
  lastPage = await click(lastPage, next, 'then  ');
  if (lastPage.status !== 200) break;
}
say();
say(failed ? 'FAILED: a click ended in "Not Found" / a PHP fatal error, or nothing was clickable' : 'OK: every click reached an existing page');
db.close();
rmSync(TMP, { recursive: true, force: true });
process.exit(failed ? 1 : 0);
