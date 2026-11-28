// Runs the game for real - without MySQL and without a web server: PHP (WebAssembly) + a SQLite database that speaks the MySQL protocol.
//
//   node --disable-warning=ExperimentalWarning demo/server.mjs [--port 8080] [--php 8.3] [--root <site dir>] [--no-autologin]
//
// What you get: http://<host>:8080/ - the site from the repository (every request is run by PHP, files are read from disk on
// every request, so a change of a .php/.css/.js file is visible after a page reload), the data of shema.sql (7941 mobs, 308 shop items,
// 120 hunting grounds ...), three ready characters:
//     Tester / tester1   a warrior, level 24 (the one you are logged in as in the browser: no password is asked on the first visit)
//     Mage01 / tester1   a mage, level 22 (an opponent for the PvP pages)
//     Admin1 / tester1   the administrator (GM panels: /gm/, /adm_panel.php, /total/)
// Four monsters of the first hunting-ground page are made tough (Wolf Pack Leader, Skeleton Lord, Ётунские воины, Fire Demon), so that a fight
// lasts several rounds; the Gremlins (25-100 HP) die from one blow.
// Opening /_demo/login/<nick> logs in as that character, /_demo/reset gives the demo data back.
//
// It is a DEVELOPMENT tool: the data lives in memory, there is no HTTPS, no .htaccess. It is not meant to be a hosting for the game.
import http from 'node:http';
import { readFileSync, existsSync, statSync, createReadStream } from 'node:fs';
import { join, posix, extname, resolve } from 'node:path';
import { createHash } from 'node:crypto';
import { SqlEngine, splitSql, startSqlDb } from './sqldb.mjs';
import { startPhp, REPO } from '../common.mjs';

const args = process.argv.slice(2);
const opt = (name, def) => { const i = args.indexOf('--' + name); return i >= 0 ? (args[i + 1] && !args[i + 1].startsWith('--') ? args[i + 1] : true) : def; };
const PORT = Number(opt('port', 8080));
const HOST = String(opt('host', '0.0.0.0'));
const VERSION = String(opt('php', '8.3'));
const ROOT = resolve(String(opt('root', REPO)));
const AUTOLOGIN = !opt('no-autologin', false);
const QUIET = !!opt('quiet', false);
const LOGFILE = opt('log', null);

const MIME = { '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8', '.js': 'text/javascript; charset=utf-8', '.mjs': 'text/javascript; charset=utf-8', '.json': 'application/json',
  '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.gif': 'image/gif', '.svg': 'image/svg+xml', '.webp': 'image/webp', '.ico': 'image/x-icon',
  '.txt': 'text/plain; charset=utf-8', '.woff': 'font/woff', '.woff2': 'font/woff2', '.mp3': 'audio/mpeg', '.ogg': 'audio/ogg', '.zip': 'application/zip', '.apk': 'application/vnd.android.package-archive' };

const PASSWORD = 'tester1';
const md5 = s => createHash('md5').update(s).digest('hex');

// ---------------------------------------------------------------------------------------------------------------------
// database
// ---------------------------------------------------------------------------------------------------------------------
let engine = null, dbServer = null;
async function startDatabase() {
  if (dbServer) dbServer.close();
  engine = new SqlEngine({ logFile: LOGFILE });
  const t0 = Date.now();
  for (const f of ['shema.sql', 'shema_extra.sql']) {
    for (const s of splitSql(readFileSync(join(ROOT, f), 'utf8'))) { try { engine.run(s); } catch (e) { console.log('schema:', e.message); } }
  }
  dbServer = await startSqlDb({ engine, port: dbServer ? dbServer.port : 0 });
  if (!QUIET) console.log(`database: ${engine.tableNames().length} tables, ${engine.run('SELECT COUNT(*) FROM mobs').rows[0][0]} mobs (${Date.now() - t0} ms), mysql protocol on 127.0.0.1:${dbServer.port}`);
}

// ---------------------------------------------------------------------------------------------------------------------
// PHP
// ---------------------------------------------------------------------------------------------------------------------
const RUNNER = `<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0'); ini_set('html_errors', '0'); ini_set('log_errors', '1'); ini_set('memory_limit', '256M');
$__target = getenv('DEMO_TARGET');
chdir(dirname($__target));
require $__target;
`;

let php = null;
let queue = Promise.resolve();
const phpEnv = () => ({ LIN2_DB_HOST: '127.0.0.1:' + dbServer.port, LIN2_DB_USER: 'demo', LIN2_DB_PASS: 'demo', LIN2_DB_NAME: 'siska' });

async function startPhpRuntime() {
  php = await startPhp(VERSION, { '/work': ROOT });
  if (!php.fileExists('/internal/demo')) php.mkdir('/internal/demo');
  php.writeFile('/internal/demo/runner.php', RUNNER);
}

// runs one request; requests are queued (one PHP instance = one request at a time)
function runPhp(req) {
  const job = queue.then(() => doRunPhp(req));
  queue = job.catch(() => {});
  return job;
}

async function doRunPhp({ path, query, method, headers, body, remote, host }) {
  const opts = {
    scriptPath: '/internal/demo/runner.php',
    relativeUri: path + (query ? '?' + query : ''),
    method,
    headers,
    env: { DEMO_TARGET: '/work' + path, ...phpEnv() },
    $_SERVER: { SCRIPT_FILENAME: '/work' + path, SCRIPT_NAME: path, PHP_SELF: path, DOCUMENT_ROOT: '/work', REMOTE_ADDR: remote || '127.0.0.1', HTTP_HOST: host || 'localhost', SERVER_NAME: (host || 'localhost').split(':')[0], SERVER_PORT: String(PORT) },
  };
  if (body && body.length) opts.body = body;
  try {
    return await php.run(opts);
  } catch (e) {
    if (e && e.response) return e.response;           // a fatal error: the output so far is in the response
    throw e;
  }
}

// installs the three characters with the installer's own function (install.php), then tunes them
async function seedPlayers() {
  const code = `<?php
define('LIN2I_LIB', 1);
require '/work/install.php';
$link = mysqli_connect('127.0.0.1', 'demo', 'demo', 'siska', ${dbServer.port});
if (!$link) { echo 'connect failed'; exit(1); }
foreach (array('Admin1', 'Tester', 'Mage01') as $nick) {
    $err = '';
    if (!lin2i_create_admin($link, $nick, '${PASSWORD}', strtolower($nick) . '@example.com', $err)) { echo "create $nick failed: $err\\n"; }
}
echo "seeded\\n";
`;
  const r = await php.run({ code, env: phpEnv() });
  if (!/seeded/.test(r.text)) throw new Error('seeding failed: ' + r.text + (r.errors || ''));
  const q = s => engine.run(s);
  const now = Math.floor(Date.now() / 1000);
  // Tester: an ordinary warrior of level 24 at the "Orc Village" hunting ground that has mobs
  q(`UPDATE users SET dostup='0', lvl='24', klas='fighert', hp='2600', hpall='2600', mp='1250', mpall='1250', exp='640', patt='1480', matt='260', pdef='980', mdef='720', money='2540000', gold='120', almaz='12', skill='0', city='Orc Village', storona='human' WHERE usr='Tester'`);
  q(`UPDATE users SET dostup='0', lvl='22', klas='wizard', pol='f', hp='1900', hpall='1900', mp='2400', mpall='2400', exp='300', patt='260', matt='1420', pdef='520', mdef='990', money='820000', skill='0', city='Orc Village', storona='human' WHERE usr='Mage01'`);
  q(`UPDATE mesto SET mesto='Shadow of the Monster Tree', city='1', place='main' WHERE usr IN ('Tester','Mage01','Admin1')`);
  q(`UPDATE mag SET lvl='3' WHERE usr='Tester'`);
  // an equipped weapon for the fighter and the mage, so that the battle code finds a weapon
  q(`UPDATE item SET image='yes', patt='900', matt='120' WHERE usr='Tester' AND tip='weapon'`);
  q(`UPDATE item SET image='yes', patt='120', matt='900' WHERE usr='Mage01' AND tip='weapon'`);
  q(`INSERT INTO online SET usr='Mage01', laikas='${now + 100000}'`);
  // a few tough monsters on the first page of the hunting ground: the Gremlins of shema.sql die from one blow, which shows nothing of a battle
  // (the names pick the bundled portraits: lin2_ui_mob_art() takes crc32(name) % 4)
  for (const [id, name, hp, patt] of [[1252, 'Wolf Pack Leader', 6000, 1150], [1240, 'Skeleton Lord', 8000, 1100], [1242, 'Ётунские воины', 10000, 1050], [1256, 'Fire Demon', 5000, 1400]]) {
    q(`UPDATE mobs SET name='${name}', hp='${hp}', hpall='${hp}', patt='${patt}', matt='${patt}', pdef='300', mdef='300' WHERE id='${id}'`);
  }
}

// ---------------------------------------------------------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------------------------------------------------------
const readBody = req => new Promise((res, rej) => { const a = []; req.on('data', c => a.push(c)); req.on('end', () => res(Buffer.concat(a))); req.on('error', rej); });
const parseCookies = h => Object.fromEntries(String(h || '').split(/;\s*/).filter(Boolean).map(c => { const i = c.indexOf('='); return [c.slice(0, i), c.slice(i + 1)]; }));
const loginCookies = nick => [`nick=${encodeURIComponent(nick)}; Path=/; Max-Age=31536000`, `password=${md5(PASSWORD)}; Path=/; Max-Age=31536000`, 'PHPSESSID=; Path=/; Max-Age=0', 'demo_seen=1; Path=/; Max-Age=31536000'];

function sendStatic(res, file, req) {
  const st = statSync(file);
  res.writeHead(200, { 'content-type': MIME[extname(file).toLowerCase()] || 'application/octet-stream', 'content-length': st.size, 'cache-control': 'no-cache' });
  if (req.method === 'HEAD') return res.end();
  createReadStream(file).pipe(res);
}

const DENY = /^\/(inc|tools|\.git|node_modules)(\/|$)|^\/(shema|shema_extra)\.sql$|\.md$/i;

const server = http.createServer(async (req, res) => {
  const t0 = Date.now();
  try {
    const url = new URL(req.url, 'http://x');
    let path = posix.normalize(decodeURIComponent(url.pathname));
    if (!path.startsWith('/')) path = '/' + path;
    const cookies = parseCookies(req.headers.cookie);

    if (path === '/_demo/reset') { await startDatabase(); await seedPlayers(); res.writeHead(302, { location: '/', 'set-cookie': loginCookies('Tester') }); return res.end(); }
    if (path.startsWith('/_demo/login')) {
      const nick = path.split('/')[3] || 'Tester';
      res.writeHead(302, { location: url.searchParams.get('to') || '/', 'set-cookie': loginCookies(nick) }); return res.end();
    }
    if (path === '/_demo/logout') { res.writeHead(302, { location: '/', 'set-cookie': ['nick=; Path=/; Max-Age=0', 'password=; Path=/; Max-Age=0', 'PHPSESSID=; Path=/; Max-Age=0', 'demo_seen=1; Path=/; Max-Age=31536000'] }); return res.end(); }
    if (path === '/_demo/sql') {          // GET /_demo/sql?q=SELECT ... - for tests and debugging
      const q = url.searchParams.get('q') || '';
      try { res.writeHead(200, { 'content-type': 'application/json' }); return res.end(JSON.stringify(engine.run(q))); } catch (e) { res.writeHead(500, { 'content-type': 'application/json' }); return res.end(JSON.stringify({ error: e.message })); }
    }

    if (DENY.test(path)) { res.writeHead(403, { 'content-type': 'text/plain; charset=utf-8' }); return res.end('Forbidden'); }

    let file = join(ROOT, path);
    if (!file.startsWith(ROOT)) { res.writeHead(403); return res.end('Forbidden'); }
    if (existsSync(file) && statSync(file).isDirectory()) {
      if (!path.endsWith('/')) { res.writeHead(301, { location: path + '/' + url.search }); return res.end(); }
      path = posix.join(path, 'index.php'); file = join(ROOT, path);
    }
    if (!existsSync(file)) {
      res.writeHead(404, { 'content-type': 'text/html; charset=utf-8' });
      return res.end(`<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL ${path.replace(/[<>&]/g, '')} was not found on this server.</p></body></html>`);
    }
    if (!path.endsWith('.php')) return sendStatic(res, file, req);

    // first visit: log in as the demo character (the demo has no registration e-mails to wait for)
    const extra = [];
    if (AUTOLOGIN && !cookies.demo_seen && !cookies.nick && req.method === 'GET' && /text\/html|\*\/\*/.test(req.headers.accept || '*/*')) {
      extra.push(...loginCookies('Tester'));
      cookies.nick = 'Tester'; cookies.password = md5(PASSWORD);
      req.headers.cookie = Object.entries(cookies).map(([k, v]) => `${k}=${v}`).join('; ');
    }

    const body = ['POST', 'PUT', 'PATCH'].includes(req.method) ? await readBody(req) : null;
    const headers = {};
    for (const k of ['cookie', 'content-type', 'user-agent', 'accept', 'accept-language', 'referer', 'x-requested-with', 'origin']) if (req.headers[k]) headers[k] = req.headers[k];
    const r = await runPhp({ path, query: url.search.slice(1), method: req.method === 'HEAD' ? 'GET' : req.method, headers, body: body ? new Uint8Array(body) : null, remote: req.socket.remoteAddress, host: req.headers.host });

    const out = { 'cache-control': 'no-store' };
    const setCookies = [...extra];
    for (const [k, v] of Object.entries(r.headers || {})) {
      if (k === 'set-cookie') setCookies.push(...v); else if (k !== 'content-length' && k !== 'transfer-encoding') out[k] = v.length === 1 ? v[0] : v;
    }
    if (setCookies.length) out['set-cookie'] = setCookies;
    if (!out['content-type']) out['content-type'] = 'text/html; charset=UTF-8';
    const bytes = r.bytes ? Buffer.from(r.bytes) : Buffer.from(r.text || '', 'utf8');
    out['content-length'] = bytes.length;
    res.writeHead(r.httpStatusCode || 200, out);
    res.end(req.method === 'HEAD' ? undefined : bytes);
    if (!QUIET) console.log(`${req.method} ${req.url} -> ${r.httpStatusCode} ${bytes.length}B ${Date.now() - t0}ms${r.errors ? '  [php: ' + String(r.errors).split('\n')[0].slice(0, 160) + ']' : ''}`);
  } catch (e) {
    console.log('ERROR', req.method, req.url, e && e.stack || e);
    if (!res.headersSent) res.writeHead(500, { 'content-type': 'text/plain; charset=utf-8' });
    res.end('demo server error: ' + (e && e.message || e));
  }
});

await startDatabase();
await startPhpRuntime();
await seedPlayers();
server.listen(PORT, HOST, () => console.log(`demo: http://${HOST === '0.0.0.0' ? 'localhost' : HOST}:${PORT}/  (PHP ${VERSION}, site ${ROOT})`));
process.on('SIGTERM', () => process.exit(0));
