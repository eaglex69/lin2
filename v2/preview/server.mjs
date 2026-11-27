/**
 * preview/server.mjs — режим превью для песочницы, где нет PHP и MySQL.
 *
 * Запускает НАСТОЯЩИЙ PHP 8.1 (php-wasm), монтирует файлы v2/ в виртуальную
 * ФС, отдаёт статику из public/ и передаёт запросы в public/index.php.
 *
 * Боевой деплой этим сервером НЕ пользуется: там Apache/Nginx + PHP-FPM + MySQL.
 * Здесь PHP тоже настоящий — исполняется тем же кодом, что и на проде.
 *
 * Запуск: node preview/server.mjs   (PORT=3000 по умолчанию)
 */
import { PhpNode } from 'php-wasm/PhpNode.mjs';
import sqlite from 'php-wasm-sqlite';
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const V2 = path.resolve(__dirname, '..');
const PUBLIC = path.join(V2, 'public');
const PORT = Number(process.env.PORT || 3000);

// ---------------------------------------------------------------- PHP-рантайм
const php = new PhpNode({
  version: '8.1',
  sharedLibs: [sqlite],
  ini: 'display_errors=0\nlog_errors=1\nerror_reporting=E_ALL\nmemory_limit=256M\ndate.timezone=Europe/Moscow',
});

let stdout = '';
let stderr = '';
php.addEventListener('output', (e) => { stdout += e.detail[0]; });
php.addEventListener('error', (e) => { stderr += e.detail[0]; });

const MIME = {
  '.css': 'text/css; charset=utf-8', '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8', '.svg': 'image/svg+xml',
  '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg',
  '.gif': 'image/gif', '.ico': 'image/x-icon', '.woff2': 'font/woff2',
  '.html': 'text/html; charset=utf-8', '.txt': 'text/plain; charset=utf-8',
};

/** Рекурсивно копирует исходники в виртуальную ФС PHP (без var/ и node_modules). */
async function mountFs(dir, vDir, depth = 0) {
  if (depth > 6) return;
  let entries = [];
  try { entries = fs.readdirSync(dir, { withFileTypes: true }); } catch { return; }
  try { await php.mkdir(vDir); } catch { /* уже есть */ }
  for (const entry of entries) {
    if (entry.name === 'node_modules' || entry.name === 'var' || entry.name === '.git') continue;
    const src = path.join(dir, entry.name);
    const dst = `${vDir}/${entry.name}`;
    if (entry.isDirectory()) {
      await mountFs(src, dst, depth + 1);
    } else if (/\.(php|js|css|svg|json|html|sql)$/.test(entry.name)) {
      await php.writeFile(dst, fs.readFileSync(src));
    }
  }
}

async function initPhp() {
  // конфиг для PHP (Config.php читает /config.json)
  await php.writeFile('/config.json', JSON.stringify({
    APP_ENV: 'production',
    DB_DRIVER: 'sqlite',
    DB_SQLITE_PATH: '/var/miniage.sqlite',
    SEED_DEMO: '1',
    TIMEZONE: 'Europe/Moscow',
  }));

  await mountFs(V2, '/v2');
  try { await php.mkdir('/var'); } catch { /* есть */ }

  // прогреваем PHP один раз
  stdout = ''; stderr = '';
  await php.run('<?php echo "boot";');
  console.log('PHP', await version());
}

async function version() {
  stdout = '';
  await php.run('<?php echo PHP_VERSION;');
  return stdout.trim();
}

// ---------------------------------------------------------------- HTTP-сервер
function readBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    let size = 0;
    req.on('data', (c) => {
      size += c.length;
      if (size > 4e6) { reject(new Error('too_large')); req.destroy(); return; }
      chunks.push(c);
    });
    req.on('end', () => resolve(Buffer.concat(chunks)));
    req.on('error', reject);
  });
}

function parseCookies(header) {
  const out = {};
  (header || '').split(';').forEach((part) => {
    const i = part.indexOf('=');
    if (i > 0) out[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1).trim());
  });
  return out;
}

function serveStatic(res, urlPath) {
  if (!urlPath.startsWith('/assets/') && urlPath !== '/favicon.ico') return false;
  const rel = urlPath === '/favicon.ico' ? '/assets/img/favicon.svg' : urlPath;
  const file = path.normalize(path.join(PUBLIC, rel));
  if (!file.startsWith(PUBLIC) || !fs.existsSync(file) || !fs.statSync(file).isFile()) return false;
  const ext = path.extname(file).toLowerCase();
  res.writeHead(200, {
    'Content-Type': MIME[ext] || 'application/octet-stream',
    'Cache-Control': 'public, max-age=300',
  });
  res.end(fs.readFileSync(file));
  return true;
}

// Очередь: php-wasm однопоточный, запросы нельзя мешать одновременно.
let queue = Promise.resolve();
function serialized(fn) {
  const run = queue.then(fn, fn);
  queue = run.catch(() => {});
  return run;
}

/** Переносит SQLite-файл из ФС PHP на диск и обратно (персистентность). */
const DB_DISK = path.join(V2, 'var', 'miniage.sqlite');
let pullErrors = 0;
async function pullDb() {
  try {
    const bytes = await php.readFile('/var/miniage.sqlite');
    if (!bytes || !bytes.length) return;
    fs.mkdirSync(path.dirname(DB_DISK), { recursive: true });
    fs.writeFileSync(DB_DISK, Buffer.from(bytes));
  } catch (e) {
    // Раньше ошибка молча глоталась: изменения оставались только в памяти
    // php-wasm и терялись при рестарте. Теперь хотя бы видно в логе.
    if (pullErrors++ < 5) console.error('[pullDb]', e && e.message ? e.message : e);
  }
}
async function pushDb() {
  try {
    if (!fs.existsSync(DB_DISK)) return false;
    await php.writeFile('/var/miniage.sqlite', fs.readFileSync(DB_DISK));
    return true;
  } catch { return false; }
}

async function phpRequest(req, body, query) {
  const headers = {};
  for (const [k, v] of Object.entries(req.headers)) {
    if (typeof v === 'string') headers[k] = v;
  }
  const payload = {
    method: req.method,
    uri: req.url,
    get: query,
    post: {},
    cookies: parseCookies(req.headers.cookie),
    files: {},
    body: body.toString('utf8'),
    headers,
    server: {
      // БАГ: REMOTE_ADDR собирался из заголовка X-Forwarded-For, который
      // целиком подконтролен клиенту. Из-за этого лимит регистраций
      // («5 в час с адреса») обходился сменой заголовка: 7 регистраций
      // подряд проходили, а в accounts.ip писались 10.0.0.1..10.0.0.6.
      // Берём настоящий адрес TCP-соединения; XFF передаём отдельным
      // заголовком, чтобы PHP применил его только для доверенного прокси.
      REMOTE_ADDR: (req.socket && req.socket.remoteAddress) || '127.0.0.1',
      HTTP_X_FORWARDED_FOR: (req.headers['x-forwarded-for'] || '').toString(),
      HTTP_USER_AGENT: req.headers['user-agent'] || '',
      HTTP_HOST: req.headers.host || `localhost:${PORT}`,
    },
  };

  const ct = (req.headers['content-type'] || '').toLowerCase();
  if (ct.includes('application/json') && payload.body) {
    // PHP-код сам разберёт rawBody через Request::json()
  } else if (ct.includes('application/x-www-form-urlencoded') && payload.body) {
    for (const [k, v] of new URLSearchParams(payload.body)) payload.post[k] = v;
  } else if (ct.includes('multipart/form-data') && payload.body) {
    const boundary = ct.split('boundary=')[1];
    if (boundary) {
      for (const part of payload.body.split('--' + boundary)) {
        const m = part.match(/name="([^"]+)"\r?\n(?:[^\r\n]*\r?\n)?\r?\n([\s\S]*?)\r?\n$/);
        if (m) payload.post[m[1]] = m[2];
      }
    }
  }

  // Payload передаём в коде (base64), а не через файл: после refresh()
  // php-wasm может отдавать устаревшее содержимое записанных файлов.
  const payloadB64 = Buffer.from(JSON.stringify(payload), 'utf8').toString('base64');

  stdout = '';
  stderr = '';

  await php.run(`<?php
// --- сброс состояния между запросами (константы/классы остаются, это норм.)
while (ob_get_level() > 0) { ob_end_clean(); }
header_remove();
http_response_code(200);
// Полный сброс нативной сессии: иначе $_SESSION «протекает» между клиентами
if (session_status() === PHP_SESSION_ACTIVE) {
  $_SESSION = [];
  @session_destroy();
}
@session_id('');
if (!defined('MINIAGE_WASM')) { define('MINIAGE_WASM', true); }
ob_start();
$__payload = json_decode(base64_decode('${payloadB64}'), true);
$_GET = $__payload['get'];
$_POST = $__payload['post'];
$_COOKIE = $__payload['cookies'];
$_FILES = [];
$_SERVER = array_merge($__payload['server'], [
  'REQUEST_METHOD' => $__payload['method'],
  'REQUEST_URI' => $__payload['uri'],
  'SCRIPT_NAME' => '/index.php',
  'SERVER_PROTOCOL' => 'HTTP/1.1',
  'HTTP_X_REQUESTED_WITH' => $__payload['headers']['x-requested-with'] ?? '',
  'HTTP_ACCEPT' => $__payload['headers']['accept'] ?? '',
  'HTTP_X_CSRF_TOKEN' => $__payload['headers']['x-csrf-token'] ?? '',
  'HTTP_X_FORWARDED_PROTO' => $__payload['headers']['x-forwarded-proto'] ?? '',
  'HTTP_X_MINIAGE_SESSION' => $__payload['headers']['x-miniage-session'] ?? '',
]);
$_REQUEST = array_merge($_GET, $_POST);
$GLOBALS['MINIAGE_RAW_BODY'] = $__payload['body'];
chdir('/v2/public');
require '/v2/public/index.php';
$__html = ob_get_clean();
$__status = http_response_code() ?: 200;
// WAL -> основной файл, иначе pullDb() сохранит снимок без последних коммитов
try { \App\Core\Database::checkpoint(); } catch (Throwable $e) {}
echo "\n@@MINIAGE_RESPONSE@@";
echo base64_encode(json_encode([
  'status' => $__status,
  'headers' => headers_list(),
  'body' => $__html,
  'uri' => $_SERVER['REQUEST_URI'] ?? 'NONE',
]));
`);

  await pullDb();

  const marker = '@@MINIAGE_RESPONSE@@';
  const idx = stdout.lastIndexOf(marker);
  if (idx < 0) {
    throw new Error('PHP не вернул ответ: ' + (stderr || stdout).slice(0, 800));
  }
  const encoded = stdout.slice(idx + marker.length).trim();
  let parsed;
  try {
    parsed = JSON.parse(Buffer.from(encoded, 'base64').toString('utf8'));
  } catch (e) {
    throw new Error('Не удалось разобрать ответ PHP: ' + encoded.slice(0, 200));
  }

  // headers_list() -> объект
  const outHeaders = {};
  const cookies = [];
  for (const line of parsed.headers || []) {
    const idx = line.indexOf(':');
    if (idx < 0) continue;
    const name = line.slice(0, idx).trim();
    const value = line.slice(idx + 1).trim();
    if (name.toLowerCase() === 'set-cookie') cookies.push(value);
    else if (name.toLowerCase() !== 'x-powered-by') outHeaders[name] = value;
  }
  if (cookies.length) outHeaders['Set-Cookie'] = cookies;
  if (!outHeaders['Content-Type']) outHeaders['Content-Type'] = 'text/html; charset=utf-8';
  outHeaders['X-Powered-By'] = 'PHP 8.1 (wasm) + Miniage v2';

  return { status: parsed.status || 200, headers: outHeaders, body: parsed.body ?? '' };
}

const server = http.createServer((req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
  const urlPath = url.pathname;

  if (urlPath === '/__preview/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: true, mode: 'php-wasm', db: 'sqlite' }));
    return;
  }

  if (req.method === 'GET' && serveStatic(res, urlPath)) return;

  const query = Object.fromEntries(url.searchParams.entries());

  readBody(req)
    .then((body) => serialized(() => phpRequest(req, body, query)))
    .then((result) => {
      res.writeHead(result.status, result.headers);
      res.end(result.body);
    })
    .catch((err) => {
      console.error('[preview]', err);
      res.writeHead(500, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(`<!doctype html><meta charset="utf-8"><h1>Ошибка PHP-превью</h1>
<pre style="white-space:pre-wrap">${String(err.message).replace(/[<>&]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]))}</pre>
<p>Полный лог — в консоли процесса.</p>`);
    });
});

await initPhp();
await pushDb();

server.listen(PORT, '0.0.0.0', () => {
  console.log(`Miniage v2 (PHP 8.1 wasm + SQLite) -> http://0.0.0.0:${PORT}`);
  console.log('Демо-вход: demo / demo');
});
