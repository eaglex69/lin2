'use strict';
/**
 * server.js — точка входа Node.js-версии Miniage.
 * Аналог Apache + PHP: принимает HTTP, разбирает ?GET и POST, вызывает
 * обработчик страницы (src/routes.js) и отдаёт WAP XHTML, как PHP echo.
 *
 * Запуск:  node server.js        (порт 3000, или PORT=8080 node server.js)
 * Демо-вход: логин demo / пароль demo
 */
const http = require('http');
const fs = require('fs');
const path = require('path');
const { auth, reissue } = require('./src/auth');
const { ROUTES, stubPage, withCtx } = require('./src/routes');
const { DB_FILE, dbRestored } = require('./src/db');

const PORT = Number(process.env.PORT || 3000);
const ROOT = path.join(__dirname, '..'); // корень репозитория (PHP-оригиналы + theme/)

// Статика: URL-префикс -> папка в репозитории
const STATIC = [
  ['/theme/', path.join(ROOT, 'theme')],
  ['/pic/', path.join(ROOT, 'theme', 'pic')],
  ['/images/', path.join(ROOT, 'images')],
  ['/forum/pic/', path.join(ROOT, 'forum', 'pic')],
  ['/forum/files/', path.join(ROOT, 'forum', 'files')],
];
const MIME = { '.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png', '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg', '.gif': 'image/gif', '.ico': 'image/x-icon', '.txt': 'text/plain', '.html': 'text/html' };

function serveStatic(req, res, urlPath) {
  // Корневые файлы: favicon.ico, logo1.png, 1.gif, snow.js
  if (/^\/(favicon\.ico|logo1\.png|1\.gif|snow\.js)$/.test(urlPath)) {
    const f = path.join(ROOT, decodeURIComponent(urlPath.slice(1)));
    if (fs.existsSync(f)) { res.writeHead(200, { 'Content-Type': MIME[path.extname(f)] || 'application/octet-stream' }); return fs.createReadStream(f).pipe(res); }
  }
  for (const [prefix, dir] of STATIC) {
    if (urlPath.startsWith(prefix)) {
      const rel = decodeURIComponent(urlPath.slice(prefix.length));
      const f = path.normalize(path.join(dir, rel));
      if (!f.startsWith(dir)) { res.writeHead(403); return res.end('Forbidden'); } // защита от ../
      if (fs.existsSync(f) && fs.statSync(f).isFile()) {
        res.writeHead(200, { 'Content-Type': MIME[path.extname(f).toLowerCase()] || 'application/octet-stream', 'Cache-Control': 'public, max-age=86400' });
        return fs.createReadStream(f).pipe(res);
      }
      res.writeHead(404); return res.end('Not found');
    }
  }
  return false;
}

function parseBody(req, cb) {
  const chunks = [];
  let size = 0;
  req.on('data', c => { size += c.length; if (size > 2e6) req.destroy(); else chunks.push(c); });
  req.on('end', () => {
    const raw = Buffer.concat(chunks).toString('utf8');
    const ct = req.headers['content-type'] || '';
    const body = {};
    if (ct.includes('application/x-www-form-urlencoded')) {
      new URLSearchParams(raw).forEach((v, k) => { body[k] = v; });
    } else if (ct.includes('multipart/form-data')) {
      // Минимальный парсер multipart (только текстовые поля, как в PHP $_POST)
      const boundary = ct.split('boundary=')[1];
      if (boundary) {
        raw.split('--' + boundary).forEach(part => {
          const m = part.match(/name="([^"]+)"\r?\n\r?\n([\s\S]*?)\r?\n$/);
          if (m) body[m[1]] = m[2];
        });
      }
    }
    cb(body);
  });
}

const handlers = {};
for (const [file, fn] of Object.entries(ROUTES)) handlers[file] = withCtx(fn);

const server = http.createServer((req, res) => {
  const u = new URL(req.url, 'http://localhost');
  const urlPath = u.pathname;
  const query = Object.fromEntries(u.searchParams.entries());

  if (urlPath === '/api/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ ok: true, engine: 'node', port: PORT, db: dbRestored ? 'restored' : 'seed', dbFile: DB_FILE }));
  }

  const st = serveStatic(req, res, urlPath);
  if (st !== false) return;

  // ctx уже знает про ?s=... (сессия через URL, если cookie заблокированы)
  const ctx = auth(req, query);
  ctx.query = query;
  ctx.method = req.method;

  const file = urlPath === '/' ? 'index.php' : path.basename(urlPath);
  const handler = handlers[file] || withCtx((c, r, s) => stubPage(c, r, s, file));

  const run = rawBody => {
    // POST: sid может прийти скрытым полем формы — поднимаем его в query
    const body = rawBody || {};
    if (body.s && !ctx.query.s) { ctx.query.s = body.s; if (!ctx.sid) { const c2 = auth(req, ctx.query); Object.assign(ctx, c2); ctx.query = query; ctx.query.s = body.s; } }
    try {
      // cookie переиздаём ДО обработчика — после res.end() заголовки уже отправлены
      reissue(ctx, res);
      handler(ctx, req, res, body);
    } catch (e) {
      console.error(e);
      if (!res.headersSent) {
        res.writeHead(500, { 'Content-Type': 'text/html; charset=UTF-8' });
        res.end('Внутренняя ошибка сервера (Node.js). Попробуйте позже.');
      }
    }
  };

  if (req.method === 'POST') parseBody(req, run);
  else run({});
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`Miniage (Node.js) запущен: http://0.0.0.0:${PORT}`);
  console.log(`Данные: ${dbRestored ? 'восстановлены из ' + DB_FILE : 'демо-сид (файл ещё не создан)'}`);
  console.log('Демо-вход: demo / demo  |  Регистрация: /reg.php');
});
