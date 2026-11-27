/**
 * Прогон распакованного релизного архива в php-wasm.
 *
 *   cd v2/preview && node archive-check.mjs /tmp/site2
 *
 * Проверяет то, что увидит игрок после установки: установщик, вход,
 * отдачу статики и — главное — что CSS со скрытием (#loading) реально
 * доезжает до страницы с версионированным адресом.
 *
 * Отдельный скрипт, потому что PHP CLI в песочнице нет, а сервер превью
 * монтирует только каталог v2/ из репозитория.
 */
import { PhpNode } from 'php-wasm/PhpNode.mjs';
import sqlite from 'php-wasm-sqlite';
import fs from 'node:fs';
import path from 'node:path';

const DIR = path.resolve(process.argv[2] || '/tmp/site2');
if (!fs.existsSync(path.join(DIR, 'index.php'))) {
  console.error('Нет index.php в', DIR);
  process.exit(1);
}

const php = new PhpNode({ version: '8.1', sharedLibs: [sqlite] });
let out = '';
php.addEventListener('output', (e) => { out += String(e.detail[0] ?? ''); });

// ------------------------------------------------------------------ монтирование
async function mount(dir, vDir, depth = 0) {
  if (depth > 6) return;
  let entries = [];
  try { entries = fs.readdirSync(dir, { withFileTypes: true }); } catch { return; }
  try { await php.mkdir(vDir); } catch { /* уже есть */ }
  for (const e of entries) {
    if (e.name === 'var' || e.name === 'node_modules') continue;
    const src = path.join(dir, e.name);
    const dst = `${vDir}/${e.name}`;
    if (e.isDirectory()) await mount(src, dst, depth + 1);
    else if (/\.(php|js|css|sql|svg|json|html|txt)$/.test(e.name)) {
      await php.writeFile(dst, fs.readFileSync(src));
    }
  }
}

await php.writeFile('/config.json', JSON.stringify({
  APP_ENV: 'production',
  DB_DRIVER: 'sqlite',
  DB_SQLITE_PATH: '/var/miniage.sqlite',
  SEED_DEMO: '1',
  TIMEZONE: 'Europe/Moscow',
}));
await mount(DIR, '/site');
try { await php.mkdir('/var'); } catch { /* есть */ }
out = '';
await php.run('<?php echo "boot";');

// ------------------------------------------------------------------ помощники
let cookieSid = '';
let csrf = '';

/** Один HTTP-запрос через фронт-контроллер. */
async function req(method, url, opts = {}) {
  const { form = null, json = null } = opts;
  let body = '';
  let ct = '';
  if (json) { body = JSON.stringify(json); ct = 'application/json'; }
  else if (form) { body = new URLSearchParams(form).toString(); ct = 'application/x-www-form-urlencoded'; }

  const headers = { host: 'localhost:8080', 'user-agent': 'archive-check' };
  if (ct) headers['content-type'] = ct;
  if (cookieSid) headers.cookie = `miniage_sid=${cookieSid}`;
  if (json) headers['x-requested-with'] = 'XMLHttpRequest';

  const post = {};
  if (form) for (const [k, v] of Object.entries(form)) post[k] = String(v);

  const payload = JSON.stringify({
    method,
    uri: url,
    get: {},
    post,
    cookies: cookieSid ? { miniage_sid: cookieSid } : {},
    files: {},
    body,
    headers,
    server: {
      REMOTE_ADDR: '127.0.0.1',
      HTTP_USER_AGENT: 'archive-check',
      HTTP_HOST: 'localhost:8080',
      REQUEST_METHOD: method,
      REQUEST_URI: url,
      SCRIPT_NAME: '/index.php',
      SERVER_PROTOCOL: 'HTTP/1.1',
      HTTP_X_REQUESTED_WITH: headers['x-requested-with'] || '',
      HTTP_ACCEPT: headers.accept || '',
      HTTP_X_MINIAGE_SESSION: headers['x-miniage-session'] || '',
    },
  });

  // Ответ забираем так же, как preview-мост: тело — в ob_start, заголовки —
  // через headers_list(). Разбирать сырой вывод нельзя: HTML сам содержит
  // пустые строки, и любая эвристика по "\n\n" обрезает страницу.
  const b64 = Buffer.from(payload, 'utf8').toString('base64'); // payload уже JSON-строка
  out = '';
  await php.run(`<?php
while (ob_get_level() > 0) { ob_end_clean(); }
header_remove();
if (!defined('MINIAGE_WASM')) { define('MINIAGE_WASM', true); }
$__p = json_decode(base64_decode("${b64}"), true);
$_GET = $__p['get'];
$_POST = $__p['post'];
$_COOKIE = $__p['cookies'];
$_FILES = [];
$_SERVER = $__p['server'];
$_REQUEST = array_merge($_GET, $_POST);
$GLOBALS['MINIAGE_RAW_BODY'] = $__p['body'];
ob_start();
chdir('/site');
require '/site/index.php';
$__html = ob_get_clean();
$__status = http_response_code() ?: 200;
echo "@@R@@";
echo base64_encode(json_encode([
  'status' => $__status,
  'headers' => headers_list(),
  'body' => $__html,
]));
`);

  const marker = '@@R@@';
  const idx = out.lastIndexOf(marker);
  if (idx < 0) {
    console.error('  [php] нет маркера ответа, вывод:', JSON.stringify(out.slice(0, 600)));
    return { status: '000', headers: {}, text: '', error: out.slice(0, 400) };
  }
  let parsed;
  try {
    parsed = JSON.parse(Buffer.from(out.slice(idx + marker.length).trim(), 'base64').toString('utf8'));
  } catch (e) {
    console.error('  [php] битый json:', String(e).slice(0, 300));
    return { status: '000', headers: {}, text: '', error: String(e) };
  }

  const hdrs = {};
  for (const line of parsed.headers || []) {
    const i = line.indexOf(':');
    if (i > 0) hdrs[line.slice(0, i).trim().toLowerCase()] = line.slice(i + 1).trim();
  }
  const setCookie = (parsed.headers || []).find((h) => /^set-cookie:\s*miniage_sid=/i.test(h));
  if (setCookie) cookieSid = setCookie.split(';')[0].split('=')[1];
  const sidHdr = hdrs['x-miniage-session'];
  if (sidHdr) cookieSid = sidHdr;

  return { status: String(parsed.status), headers: hdrs, text: String(parsed.body ?? '') };
}

const pass = [];
const fail = [];
const check = (name, ok, info = '') => {
  (ok ? pass : fail).push(name);
  console.log(`${ok ? 'OK  ' : 'БАГ '} ${name}${info ? ' — ' + info : ''}`);
};

// ------------------------------------------------------------------ 1. статика и оболочка
const home = await req('GET', '/');
check('GET / → 200', home.status === '200', `HTTP ${home.status}`);
check('/ отдаёт canvas', home.text.includes('<canvas id="game-canvas"'));
check('/ отдаёт экран входа', /<div class="auth" id="auth">/.test(home.text));
check('/ отдаёт спиннер скрытым', /<div class="loading" id="loading" hidden>/.test(home.text));

const cssVer = (home.text.match(/\/assets\/css\/app\.css\?v=([0-9]+)/) || [])[1];
check('ссылка на CSS версионирована', !!cssVer, cssVer ? `?v=${cssVer}` : 'нет ?v=');

const jsVer = (home.text.match(/\/assets\/js\/main\.js\?v=([0-9]+)/) || [])[1];
check('ссылка на JS версионирована', !!jsVer, jsVer ? `?v=${jsVer}` : 'нет ?v=');

const css = await req('GET', `/assets/css/app.css?v=${cssVer}`);
check('CSS отдаётся', css.status === '200' && css.text.includes('display: flex'));
check('CSS: [hidden] { display:none !important }', /\[hidden\]\s*\{[^}]*display:\s*none\s*!important/.test(css.text));
check('CSS: правило стоит до .loading',
  css.text.indexOf('[hidden] { display: none !important; }') < css.text.indexOf('.loading {'));

const js = await req('GET', `/assets/js/main.js?v=${jsVer}`);
// main.js — точка входа без export: у него import-ы и вызов init().
check('JS отдаётся', js.status === '200' && js.text.includes("from './api.js'"),
  `${js.text.length} б`);

// ------------------------------------------------------------------ 2. установщик
const installGet = await req('GET', '/install.php');
check('GET /install.php → 200 (форма)', installGet.status === '200' && installGet.text.includes('<form'),
  `HTTP ${installGet.status}`);

const installPost = await req('POST', '/install.php', {
  form: { db_driver: 'sqlite', app_name: 'Miniage', seed_demo: '1', seed_demo_pass: 'demo', admin_pass: '' },
});
const instOk = installPost.status === '200' && /Готово|установлена/i.test(installPost.text);
check('POST /install.php → установка', instOk, (installPost.text.match(/Готово[^<]{0,40}|Ошибка[^<]{0,60}/) || [''])[0].trim());

const installAgain = await req('GET', '/install.php');
check('повторный заход: «уже установлена»', /уже установлена/i.test(installAgain.text));

// ------------------------------------------------------------------ 3. игра после установки
const boot = await req('GET', '/api/bootstrap');
let bootData = {};
try { bootData = JSON.parse(boot.text); } catch { /* ниже упадёт проверка */ }
csrf = bootData.csrf || '';
check('bootstrap → JSON ok', bootData.ok === true);
check('bootstrap: гость без аккаунта', bootData.account === null);
check('bootstrap: 4 зоны', (bootData.zones || []).length === 4, `${(bootData.zones || []).length} зон`);

const login = await req('POST', '/api/login', { json: { _csrf: csrf, nick: 'demo', pass: 'demo' } });
let loginData = {};
try { loginData = JSON.parse(login.text); } catch { /* проверка ниже */ }
check('вход demo/demo', loginData.ok === true, loginData.error || '');
if (loginData.csrf) csrf = loginData.csrf;

const boot2 = await req('GET', '/api/bootstrap');
let b2 = {};
try { b2 = JSON.parse(boot2.text); } catch { /* ниже */ }
check('после входа есть персонаж', !!b2.character, b2.character ? `${b2.character.name} ур.${b2.character.level}` : 'нет');

const health = await req('GET', '/api/health');
check('/api/health → ok', health.status === '200' && health.text.includes('"ok":true'));

const api404 = await req('GET', '/api/nope');
check('/api/nope → 404 not_found', api404.status === '404' && api404.text.includes('not_found'));

const trav = await req('GET', '/../src/Core/Config.php');
check('path traversal → 404', trav.status === '404' || trav.status === '403', `HTTP ${trav.status}`);

// ------------------------------------------------------------------ итог
console.log(`\n== Итог: ${pass.length} OK, ${fail.length} провалов ==`);
if (fail.length) {
  console.log('Провалы: ' + fail.join(', '));
  process.exit(1);
}
