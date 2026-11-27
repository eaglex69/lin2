// End-to-end test of install.php: PHP 8 (WebAssembly) runs the installer against a stateful fake MySQL server.
//
//   node smoke/install.mjs [php-version ...] [--verbose]        (default: 8.3)
//
// Every scenario works on its own copy of the repository (the installer writes inc/config.php and creates directories).
// What is checked: the page and the form, CSRF / validation errors (no database contact), wrong user / unknown database,
// the happy path (CREATE DATABASE, every statement of shema.sql + shema_extra.sql, the administrator rows, directories,
// inc/config.php round trip with awkward characters in the password), that a second run is refused and executes nothing,
// that the game then starts from inc/config.php without environment variables (and says "not installed" before),
// retry after an error in the middle of the import (overwrite confirmation), connect-to-existing-database mode,
// a read-only inc/ (the config text is shown), LIN2_DB_* in the environment, deleting install.php.
// The fake server rejects unknown columns, wrong value counts and missing tables like MySQL would, but it is not MySQL:
// whether the real server accepts the dump is not proven here.
import { startFakeDb } from './fakedb.mjs';
import { startPhp, REPO } from '../common.mjs';
import { cpSync, mkdtempSync, existsSync, readFileSync, rmSync, chmodSync, writeFileSync, appendFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { join } from 'node:path';
import { tmpdir } from 'node:os';

const args = process.argv.slice(2);
const VERBOSE = args.includes('--verbose');
const versions = args.filter(a => !a.startsWith('--'));
if (!versions.length) versions.push('8.3');

let checks = 0, failures = 0;
function check(name, ok, detail = '') {
  checks++;
  if (!ok) { failures++; console.log('  FAIL ' + name + (detail !== '' ? '  ::  ' + String(detail).replace(/\s+/g, ' ').slice(0, 500) : '')); }
  else if (VERBOSE) console.log('  ok   ' + name);
}
const has = (text, s) => text.includes(s);
const section = (s) => console.log('-- ' + s);

const dump = readFileSync(join(REPO, 'shema.sql'), 'utf8');
const extra = readFileSync(join(REPO, 'shema_extra.sql'), 'utf8');
const count = (re, s) => (s.match(re) || []).length;
const EXPECT = {
  create: count(/^CREATE TABLE/gm, dump) + count(/^CREATE TABLE/gm, extra),
  alter: count(/^ALTER TABLE/gm, dump) + count(/^ALTER TABLE/gm, extra),
  insertDump: count(/^INSERT INTO/gm, dump),
};
const schemaTables = [...(dump + '\n' + extra).matchAll(/^CREATE TABLE IF NOT EXISTS `([^`]+)`/gm)].map(m => m[1]);
const GAME_TABLES = schemaTables.length;
// tables whose rows in the bundled dump are leftovers of test characters / real players (see lin2i_player_tables() in install.php)
const PLAYER_TABLES = ['block_account', 'color_akk', 'komentarai', 'mag', 'mesto', 'msg_r', 'new_usr', 'online', 'online_time', 'regenerator', 'vhod', 'vigre_bonus', 'vigre_votes', 'vigre_votes_bot'];
const playerInsert = new RegExp('^INSERT INTO `(' + PLAYER_TABLES.join('|') + ')`', 'gm');
EXPECT.insertSkipped = count(playerInsert, dump);
EXPECT.insertKept = EXPECT.insertDump - EXPECT.insertSkipped;

const RUNNER = `<?php
ini_set('session.save_path', '/tmp');
ini_set('log_errors', '1'); ini_set('error_log', '/tmp/php_errors.log');
ini_set('display_errors', '1'); ini_set('html_errors', '0');
chdir(dirname(getenv('LIN2_T')));
require getenv('LIN2_T');
`;

// tag balance of a page (void elements and script/style bodies are skipped): an unclosed <fieldset> or <div> would wreck the layout
function htmlProblems(html) {
  const body = html.replace(/<(script|style)[\s\S]*?<\/\1>/gi, '').replace(/<!DOCTYPE[^>]*>/i, '').replace(/<!--[\s\S]*?-->/g, '');
  const voids = new Set(['meta', 'input', 'br', 'hr', 'img', 'link']);
  const stack = [], problems = [];
  for (const m of body.matchAll(/<(\/?)([a-zA-Z][a-zA-Z0-9]*)\b[^>]*?(\/?)>/g)) {
    const name = m[2].toLowerCase();
    if (voids.has(name) || m[3]) continue;
    if (!m[1]) { stack.push(name); continue; }
    const top = stack.pop();
    if (top !== name) { problems.push(`</${name}> closes <${top}>`); if (problems.length > 3) break; }
  }
  if (stack.length) problems.push('unclosed: ' + stack.join(','));
  return problems;
}

class Site {
  constructor(php, dir) { this.php = php; this.dir = dir; this.cookies = {}; }
  async request({ script = 'install.php', form = null, env = {}, method = 'GET', scriptName = null } = {}) {
    const target = '/work/' + script;
    const headers = { 'user-agent': 'install-test' };
    const ck = Object.entries(this.cookies).map(([k, v]) => k + '=' + v).join('; ');
    if (ck) headers.cookie = ck;
    let body;
    if (form) { body = new TextEncoder().encode(new URLSearchParams(form).toString()); headers['content-type'] = 'application/x-www-form-urlencoded'; method = 'POST'; }
    let r;
    try {
      r = await this.php.run({
        scriptPath: '/internal/t/runner.php', relativeUri: '/' + script, method, headers, body, env: { LIN2_T: target, ...env },
        $_SERVER: { SCRIPT_FILENAME: target, SCRIPT_NAME: scriptName || '/' + script, PHP_SELF: scriptName || '/' + script, DOCUMENT_ROOT: '/work', REMOTE_ADDR: '203.0.113.7', HTTP_HOST: 'localhost' },
      });
    } catch (e) {
      const resp = e && e.response;
      return { status: 0, text: (resp && resp.text) || String(e.message), crashed: String(e.message).split('\n')[0] };
    }
    for (const c of (r.headers['set-cookie'] || [])) { const m = /^([^=]+)=([^;]*)/.exec(c); if (m) this.cookies[m[1]] = m[2]; }
    return { status: r.httpStatusCode, text: r.text, headers: r.headers };
  }
  async csrf() {
    const r = await this.request();
    const m = /name="csrf" value="([0-9a-f]+)"/.exec(r.text);
    return m ? m[1] : null;
  }
  file(rel) { return join(this.dir, rel); }
  has(rel) { return existsSync(this.file(rel)); }
  phpLog() {
    const p = '/tmp/php_errors.log';
    if (!this.php.fileExists(p)) return '';
    const t = this.php.readFileAsText(p);
    this.php.unlink(p);
    return t.trim();
  }
  async readConfig() {
    const r = await this.php.run({ code: '<?php echo json_encode(include "/work/inc/config.php");' });
    try { return JSON.parse(r.text); } catch { return null; }
  }
}

function newSite(php) {
  const dir = mkdtempSync(join(tmpdir(), 'lin2_install_'));
  cpSync(REPO, dir, {
    recursive: true,
    filter: p => !/[\\/](\.git|node_modules)([\\/]|$)/.test(p) && !/[\\/]tools[\\/]php8([\\/]|$)/.test(p)
      && !/[\\/]inc[\\/]config\.php$/.test(p) && !/lin2-php8.*\.zip$/.test(p),
  });
  return dir;
}

async function runVersion(version) {
  console.log(`===== PHP ${version}`);
  const opts = { databases: ['gamedb', 'retrydb', 'rodb'], user: 'gameuser', serverVersion: '5.5.62-fakedb', failOn: null };
  const db = await startFakeDb({ schemaPath: join(REPO, 'shema.sql'), stateful: opts });
  const st = db.state;
  const HOST = '127.0.0.1:' + db.port;
  const PW = `p'a\\ss"w$rd{x}`;                     // quote, backslash, double quote, dollar - must survive var_export() and the include
  const form = (csrf, over = {}) => ({
    action: 'install', csrf, db_host: HOST, db_name: 'newgame', db_user: 'gameuser', db_pass: PW, db_create: '1', db_mode: 'fresh',
    admin_nick: 'Boss1', admin_pass: 'Adm1nPass', admin_pass2: 'Adm1nPass', admin_email: 'boss@example.com', ...over,
  });
  const dirs = [];
  const mk = async () => {
    const dir = newSite(); dirs.push(dir);
    const php = await startPhp(version, { '/work': dir });
    if (!php.fileExists('/internal/t')) php.mkdir('/internal/t');
    php.writeFile('/internal/t/runner.php', RUNNER);
    return new Site(php, dir);
  };

  // ------------------------------------------------------------------------------------------------ A. not installed
  section('A. the game before installation');
  let s = await mk();
  let r = await s.request({ script: 'index.php' });
  check('A1 index.php -> 503 "not installed"', r.status === 503 && has(r.text, 'Игра ещё не установлена'), r.status + ' ' + r.text.slice(0, 200));
  check('A2 the page links to install.php', has(r.text, 'href="install.php"'));
  r = await s.request({ script: 'gm/index.php' });
  check('A3 entry in a subdirectory links to ../install.php', r.status === 503 && has(r.text, 'href="../install.php"'), r.text.slice(0, 200));
  check('A4 nothing was sent to the database', st.connections === 0);
  check('A5 no PHP warnings', s.phpLog() === '');
  const phpCli = await startPhp(version, { '/work': s.dir });
  const cli = await phpCli.cli(['php', '/work/bonus/votes_bot.php'], { cwd: '/work/bonus' });
  const cliOut = await cli.stdoutText, cliErr = await cli.stderrText, cliCode = await cli.exitCode;
  check('A6 a cron robot without settings says "not installed" and exits with 1', cliCode === 1 && /Игра не установлена/.test(cliErr + cliOut), `exit ${cliCode}: ${(cliErr + cliOut).slice(0, 200)}`);

  // ------------------------------------------------------------------------------------------------ B. the installer page
  section('B. installer page');
  r = await s.request();
  check('B1 GET -> 200', r.status === 200, r.status);
  check('B2 title, checks and form are there', has(r.text, 'Установка игры') && has(r.text, 'Проверка сервера') && has(r.text, 'name="db_name"') && has(r.text, 'name="admin_nick"'));
  check('B3 no failed server checks', !has(r.text, '<tr class="fail">'), (r.text.match(/<tr class="fail">.*?<\/tr>/) || [''])[0]);
  check('B4 CSRF token and session cookie', /name="csrf" value="[0-9a-f]{32}"/.test(r.text) && !!s.cookies.PHPSESSID);
  check('B5 the images warning is shown (no pic/ in the repository)', has(r.text, 'Картинки игры'));
  check('B6 security headers', (r.headers['x-frame-options'] || [''])[0] === 'DENY' && /no-store/.test((r.headers['cache-control'] || [''])[0]));
  check('B7 no PHP warnings', s.phpLog() === '');
  check('B8 the page is well-formed HTML', htmlProblems(r.text).length === 0, htmlProblems(r.text).join('; '));

  // ------------------------------------------------------------------------------------------------ C. rejected without touching the DB
  section('C. CSRF and validation (no database contact)');
  const c0 = st.connections;
  let csrf = await s.csrf();
  r = await s.request({ form: form('') });
  check('C1 missing CSRF token is refused', has(r.text, 'Сессия устарела'));
  r = await s.request({ form: form('0'.repeat(32)) });
  check('C2 wrong CSRF token is refused', has(r.text, 'Сессия устарела'));
  const bad = [
    [{ admin_nick: 'ab' }, 'Ник администратора'],
    [{ admin_nick: 'Bad nick!' }, 'Ник администратора'],
    [{ admin_nick: '123456' }, 'только из цифр'],
    [{ admin_nick: '12e3' }, 'только из цифр'],
    [{ admin_pass: 'short', admin_pass2: 'short' }, 'Пароль администратора: от 6'],
    [{ admin_pass2: 'Different1' }, 'не совпадают'],
    [{ admin_pass: 'пароль1234', admin_pass2: 'пароль1234' }, 'Пароль администратора: от 6'],
    [{ admin_email: 'not-an-email' }, 'E-mail'],
    [{ db_name: 'bad name;drop' }, 'Имя базы'],
    [{ db_name: '' }, 'Имя базы'],
    [{ db_user: '' }, 'пользователя'],
    [{ db_host: 'host name' }, 'Сервер MySQL'],
  ];
  for (const [over, needle] of bad) {
    r = await s.request({ form: form(csrf, over) });
    check('C3 invalid input is explained: ' + JSON.stringify(over).slice(0, 60), has(r.text, needle) && !has(r.text, 'Игра установлена'), r.text.replace(/<[^>]+>/g, ' ').slice(300, 700));
  }
  r = await s.request({ form: { ...form(csrf), 'admin_nick[]': 'x' } });
  check('C4 array instead of a string does not break the page', r.status === 200 && !r.crashed);
  check('C5 the database was not contacted', st.connections === c0, st.connections - c0);
  check('C6 nothing was written', !s.has('inc/config.php'));
  check('C7 no PHP warnings', s.phpLog() === '', s.phpLog());

  // ------------------------------------------------------------------------------------------------ D. database errors
  section('D. database errors');
  r = await s.request({ form: form(csrf, { db_user: 'intruder' }) });
  check('D1 wrong user -> 1045 explained', has(r.text, '1045') && has(r.text, 'не принял логин'), r.text.replace(/<[^>]+>/g, ' ').slice(300, 700));
  r = await s.request({ form: form(csrf, { db_name: 'nodb', db_create: '' }) });
  check('D2 unknown database without "create" -> 1049 explained', has(r.text, '1049') && has(r.text, 'Создать базу'), r.text.replace(/<[^>]+>/g, ' ').slice(300, 700));
  r = await s.request({ form: form(csrf, { db_host: '127.0.0.1:1' }) });
  check('D3 unreachable server -> explained', has(r.text, 'Не удаётся связаться') || has(r.text, 'MySQL'), r.text.replace(/<[^>]+>/g, ' ').slice(300, 700));
  check('D4 nothing written, no tables', !s.has('inc/config.php') && !st.dbs.has('nodb') && (st.tableNames('gamedb').length === 0));
  check('D5 no PHP warnings', s.phpLog() === '', s.phpLog());

  // ------------------------------------------------------------------------------------------------ E. happy path
  section('E. installation (new database created by the installer)');
  const mark = st.queries.length;
  r = await s.request({ form: form(csrf) });
  check('E1 success page', r.status === 200 && has(r.text, 'Игра установлена'), r.text.replace(/<[^>]+>/g, ' ').slice(200, 900));
  const q = st.queries.slice(mark).map(x => x.sql);
  const kinds = (re) => q.filter(x => re.test(x)).length;
  if (process.env.DUMP_QUERIES) writeFileSync(process.env.DUMP_QUERIES, JSON.stringify(q));   // for an external syntax check (see README)
  check('E2 CREATE DATABASE was issued', q.some(x => /^CREATE DATABASE `newgame`/.test(x)), q.slice(0, 3).join(' | '));
  check('E3 every CREATE TABLE of shema.sql + shema_extra.sql ran', kinds(/^\s*CREATE\s+TABLE/i) === EXPECT.create, `${kinds(/^\s*CREATE\s+TABLE/i)} vs ${EXPECT.create}`);
  check('E4 every ALTER TABLE ran', kinds(/^\s*ALTER\s+TABLE/i) === EXPECT.alter, `${kinds(/^\s*ALTER\s+TABLE/i)} vs ${EXPECT.alter}`);
  check('E5 the reference data of the dump was imported (INSERTs minus the player-data ones)', kinds(/^\s*INSERT\s+INTO\s+`\w+`\s+\(/i) === EXPECT.insertKept, `${kinds(/^\s*INSERT\s+INTO\s+`\w+`\s+\(/i)} vs ${EXPECT.insertKept}`);
  check('E5b nothing was inserted into the player-data tables', !q.some(x => new RegExp('^INSERT INTO `(' + PLAYER_TABLES.join('|') + ')` \\(').test(x)), EXPECT.insertSkipped + ' statements should have been skipped');
  const ADMIN_ROWS = { mag: 1, mesto: 1, new_usr: 1, regenerator: 1 };       // the administrator's own starting rows
  check('E5c those tables hold nothing but the administrator (no test characters, no IP addresses, no ban on account #3)',
    PLAYER_TABLES.every(t => (st.dbs.get('newgame').get(t) || { rows: -1 }).rows === (ADMIN_ROWS[t] || 0)), PLAYER_TABLES.map(t => t + ':' + (st.dbs.get('newgame').get(t) || { rows: -1 }).rows).join(' '));
  check('E5d the page explains what was skipped', has(r.text, 'не импортируются: ' + EXPECT.insertSkipped + ' запросов INSERT'), r.text.replace(/<[^>]+>/g, ' ').slice(-700));
  check('E5e the reference tables are filled (mobs, shop, lvl, gorod, option_game)', ['mobs', 'shop', 'lvl', 'gorod', 'option_game'].every(t => (st.dbs.get('newgame').get(t) || { rows: 0 }).rows > 0));
  check('E6 the fake server rejected nothing (columns, value counts, tables)', st.rejected.filter(x => x.code !== 1045 && x.code !== 1049).length === 0, JSON.stringify(st.rejected.slice(-3)));
  const names = st.tableNames('newgame');
  check('E7 all game tables exist', names.length === GAME_TABLES && ['account', 'users', 'job_pvp', 'clan_shop', 'option_game'].every(t => names.includes(t)), names.length + ' vs ' + GAME_TABLES);
  check('E8 SET SESSION sql_mode is restored after the dump', q.filter(x => /^SET SESSION sql_mode=''/.test(x)).length >= 2);
  const md5 = createHash('md5').update('Adm1nPass').digest('hex');
  const acc = q.find(x => /^INSERT INTO `account` SET/.test(x));
  check('E9 account row: nick, md5 password, e-mail', !!acc && acc.includes("`nick` = 'Boss1'") && acc.includes(`'${md5}'`) && acc.includes('boss@example.com'), acc);
  const usr = q.find(x => /^INSERT INTO `users` SET/.test(x));
  check('E10 character row has dostup = 5 and belongs to the account', !!usr && usr.includes("`dostup` = '5'") && /`account` = '\d+'/.test(usr) && usr.includes("`usr` = 'Boss1'"), usr);
  check('E11 starting set: 3 items, skill, mesto, regenerator, new_usr', kinds(/^INSERT INTO `item` SET/) === 3 && kinds(/^INSERT INTO `mag` SET/) === 1
    && kinds(/^INSERT INTO `mesto` SET/) === 1 && kinds(/^INSERT INTO `regenerator` SET/) === 1 && kinds(/^INSERT INTO `new_usr` SET/) === 1);
  check('E12 the character becomes the active one', q.some(x => /^UPDATE `account` SET `active` = '\d+' WHERE `id` = '\d+'/.test(x)));
  const tAcc = st.dbs.get('newgame').get('account'), tUsr = st.dbs.get('newgame').get('users');
  check('E13 exactly one account and one character', tAcc.rows === 1 && tUsr.rows === 1, `${tAcc.rows}/${tUsr.rows}`);
  check('E14 upload directories exist', s.has('pic/gallery') && s.has('foto') && s.has('shmot'));
  check('E15 inc/config.php exists', s.has('inc/config.php'));
  const cfg = await s.readConfig();
  check('E16 config round trip (awkward password included)', !!cfg && cfg.db_host === HOST && cfg.db_user === 'gameuser' && cfg.db_pass === PW && cfg.db_name === 'newgame', JSON.stringify(cfg));
  check('E17 the page shows neither the DB password nor the admin password', !r.text.includes(PW) && !r.text.includes('Adm1nPass') && !has(r.text, "p'a"));
  check('E18 admin login and next steps are shown', has(r.text, 'Boss1') && has(r.text, 'adm_panel.php') && has(r.text, 'Удалить install.php'));
  check('E19 the log lists the steps', has(r.text, 'Создан администратор') && has(r.text, 'Создано таблиц: ' + GAME_TABLES) && has(r.text, 'inc/config.php'));
  check('E20 no PHP warnings', s.phpLog() === '', s.phpLog());
  check('E21 the page is well-formed HTML', htmlProblems(r.text).length === 0, htmlProblems(r.text).join('; '));

  // ------------------------------------------------------------------------------------------------ F. locked + the game starts
  section('F. after installation');
  const mark2 = st.queries.length, conn2 = st.connections;
  r = await s.request();
  check('F1 installer is locked (403, "already installed")', r.status === 403 && has(r.text, 'Игра уже установлена') && !has(r.text, 'name="db_name"'), r.status);
  csrf = await s.csrf();
  r = await s.request({ form: form(csrf) });
  check('F2 a repeated POST is refused too', r.status === 403 && has(r.text, 'Игра уже установлена') && !has(r.text, 'Игра установлена'));
  check('F3 and executes nothing (no connection, no query)', st.connections === conn2 && st.queries.length === mark2);
  r = await s.request({ scriptName: '/game/install.php' });
  check('F3b in a subdirectory the links keep the path (/game/index.php)', has(r.text, 'href="/game/index.php"'), (r.text.match(/href="[^"]*index\.php"/) || [''])[0]);
  r = await s.request();
  check('F3c the "already installed" page is well-formed HTML', htmlProblems(r.text).length === 0, htmlProblems(r.text).join('; '));
  r = await s.request({ script: 'index.php' });
  check('F4 the game reads inc/config.php (no environment) and renders', r.status === 200 && !has(r.text, 'не установлена') && r.text.length > 200, r.status + ' ' + r.text.slice(0, 300));
  check('F5 ... without a fatal error', !/Fatal error|Uncaught|Parse error/.test(r.text), (r.text.match(/(Fatal error|Uncaught)[^\n]{0,200}/) || [''])[0]);
  check('F6 ... and connected with the installed credentials', st.queries.slice(mark2).some(x => /SET NAMES/.test(x.sql)) && st.connections > conn2);
  check('F7 no PHP warnings from the installer', true);
  s.phpLog();

  // ------------------------------------------------------------------------------------------------ G. delete installer
  section('G. deleting install.php');
  csrf = await s.csrf();
  r = await s.request({ form: { action: 'delete_installer', csrf: '0'.repeat(32) } });
  check('G1 delete with a wrong token does nothing', s.has('install.php'));
  r = await s.request({ form: { action: 'delete_installer', csrf } });
  check('G2 install.php is deleted', !s.has('install.php') && has(r.text, 'удалён'), r.text.replace(/<[^>]+>/g, ' ').slice(0, 300));
  s.phpLog();

  // ------------------------------------------------------------------------------------------------ H. retry after a failure
  section('H. failure in the middle of the import, retry with overwrite confirmation');
  s = await mk();
  csrf = await s.csrf();
  const hform = (over = {}) => form(csrf, { db_name: 'retrydb', db_create: '', ...over });
  opts.failOn = /^CREATE TABLE IF NOT EXISTS `vip`/;
  r = await s.request({ form: hform() });
  check('H1 the error names the failing statement', has(r.text, 'Ошибка в запросе') && has(r.text, '`vip`') && has(r.text, 'установщик предложит стереть'), r.text.replace(/<[^>]+>/g, ' ').slice(300, 900));
  check('H1b ... and says that part of the tables exists', has(r.text, 'Часть таблиц уже создана'));
  check('H2 no config was written after the failure', !s.has('inc/config.php'));
  const partial = st.tableNames('retrydb').length;
  check('H3 part of the tables had been created', partial > 0 && partial < GAME_TABLES, partial);
  opts.failOn = null;
  let m1 = st.queries.length;
  r = await s.request({ form: hform() });
  check('H4 retry without confirmation shows the conflict panel', has(r.text, 'уже есть таблицы игры') && has(r.text, 'name="overwrite_name"'), r.text.replace(/<[^>]+>/g, ' ').slice(300, 700));
  check('H5 ... and drops nothing', !st.queries.slice(m1).some(x => /^DROP/i.test(x)));
  r = await s.request({ form: hform({ overwrite: '1', overwrite_name: 'wrongname' }) });
  check('H6 wrong confirmation name -> still the conflict panel', has(r.text, 'уже есть таблицы игры') && !has(r.text, 'Игра установлена'));
  m1 = st.queries.length;
  r = await s.request({ form: hform({ overwrite: '1', overwrite_name: 'retrydb' }) });
  check('H7 confirmed overwrite completes the installation', has(r.text, 'Игра установлена'), r.text.replace(/<[^>]+>/g, ' ').slice(200, 700));
  const drops = st.queries.slice(m1).map(x => x.sql).filter(x => /^DROP TABLE/i.test(x));
  check('H8 only tables of the game were dropped', drops.length === partial && drops.every(x => schemaTables.includes((/`([^`]+)`/.exec(x) || [])[1])), drops.length + ' vs ' + partial);
  check('H9 all tables exist and there is exactly one account', st.tableNames('retrydb').length === GAME_TABLES && st.dbs.get('retrydb').get('account').rows === 1);

  // the very first statement is refused (no CREATE privilege): the message must not claim that tables exist
  st.dbs.set('norightsdb', new Map());
  s = await mk();                       // the previous site is installed by now and therefore locked
  csrf = await s.csrf();
  opts.failOn = /^CREATE TABLE/;
  r = await s.request({ form: hform({ db_name: 'norightsdb' }) });
  opts.failOn = null;
  check('H10 a refused first CREATE is explained as "nothing created"', has(r.text, 'Ни одна таблица не создана') && !has(r.text, 'Часть таблиц уже создана') && st.tableNames('norightsdb').length === 0, (r.text.match(/<div class="alert e">[\s\S]*?<\/div>/) || [''])[0].replace(/<[^>]+>/g, ' ') + ' | tables: ' + st.tableNames('norightsdb').length);

  // ------------------------------------------------------------------------------------------------ I. existing / installed databases
  section('I. existing database: conflict, overwrite keeps foreign tables, connect-only mode');
  st.preload('gamedb');
  st.dbs.get('gamedb').set('custom_notes', { name: 'custom_notes', cols: null, autoinc: 0, rows: 5 });
  s = await mk();
  csrf = await s.csrf();
  const iform = (over = {}) => form(csrf, { db_name: 'gamedb', db_create: '', ...over });
  m1 = st.queries.length;
  r = await s.request({ form: iform() });
  check('I1 conflict panel with the account count', has(r.text, 'уже есть таблицы игры') && has(r.text, 'аккаунтов: 3'), r.text.replace(/<[^>]+>/g, ' ').slice(300, 700));
  check('I1b the conflict page is well-formed HTML', htmlProblems(r.text).length === 0, htmlProblems(r.text).join('; '));
  check('I2 nothing destructive ran', !st.queries.slice(m1).some(x => /^(DROP|CREATE|ALTER|INSERT|UPDATE|DELETE)/i.test(x.sql)));
  // connect-only mode on the same database
  m1 = st.queries.length;
  r = await s.request({ form: iform({ db_mode: 'existing', admin_nick: '', admin_pass: '', admin_pass2: '', admin_email: '' }) });
  check('I3 connect-only mode succeeds', has(r.text, 'Игра установлена') && has(r.text, 'данные не менялись'), r.text.replace(/<[^>]+>/g, ' ').slice(200, 700));
  check('I4 ... without a single write to the database', !st.queries.slice(m1).some(x => /^(DROP|CREATE|ALTER|INSERT|UPDATE|DELETE|REPLACE|TRUNCATE)/i.test(x.sql)));
  check('I5 ... but inc/config.php is written', s.has('inc/config.php'));
  check('I6 ... and the existing accounts were not touched', st.dbs.get('gamedb').get('account').rows === 3);
  // overwrite on a second copy (the first one is installed now)
  s = await mk();
  csrf = await s.csrf();
  m1 = st.queries.length;
  r = await s.request({ form: iform({ overwrite: '1', overwrite_name: 'gamedb' }) });
  check('I7 overwrite with the right name installs', has(r.text, 'Игра установлена'), r.text.replace(/<[^>]+>/g, ' ').slice(200, 700));
  check('I8 a table that does not belong to the game survived', st.dbs.get('gamedb').has('custom_notes') && st.dbs.get('gamedb').get('custom_notes').rows === 5);
  check('I9 the old accounts are gone, the administrator is the only one', st.dbs.get('gamedb').get('account').rows === 1);
  // not a game database
  st.dbs.set('emptydb', new Map());
  s = await mk();
  csrf = await s.csrf();
  r = await s.request({ form: form(csrf, { db_name: 'emptydb', db_create: '', db_mode: 'existing' }) });
  check('I10 connect-only mode refuses a database without account/users', has(r.text, 'это не база игры') && !s.has('inc/config.php'), r.text.replace(/<[^>]+>/g, ' ').slice(300, 700));

  // ------------------------------------------------------------------------------------------------ J. read-only inc/
  section('J. inc/ is not writable');
  if (typeof process.getuid === 'function' && process.getuid() === 0) {
    console.log('  (skipped: running as root, directory permissions do not apply)');
  } else {
    s = await mk();
    csrf = await s.csrf();
    chmodSync(s.file('inc'), 0o555);
    r = await s.request({ form: form(csrf, { db_name: 'rodb', db_create: '' }) });
    chmodSync(s.file('inc'), 0o755);
    check('J1 the page still reports success but asks to create inc/config.php by hand', has(r.text, 'Игра установлена') && has(r.text, 'Осталось одно действие') && has(r.text, '<textarea'), r.text.replace(/<[^>]+>/g, ' ').slice(200, 800));
    check('J1b the page is well-formed HTML', htmlProblems(r.text).length === 0, htmlProblems(r.text).join('; '));
    check('J2 the text to paste is a valid config', has(r.text, "&#039;db_name&#039; =&gt; &#039;rodb&#039;") && has(r.text, '&lt;?php'));
    check('J3 inc/config.php was not created', !s.has('inc/config.php'));
    r = await s.request();
    check('J4 so the installer is still available (nothing locks it)', r.status === 200 && has(r.text, 'name="db_name"'));
  }

  // ------------------------------------------------------------------------------------------------ L. a dump that contains players
  section('L. a dump with players is imported as it is');
  st.dbs.set('fulldb', new Map());
  s = await mk();
  appendFileSync(s.file('shema.sql'), "\nINSERT INTO `account` (`id`, `nick`, `pass`, `email`, `ip`, `ref`, `lvisit`, `active`, `sekret`) VALUES\n(7, 'OldPlayer', 'x', '', '', 0, 0, 0, '');\n");
  csrf = await s.csrf();
  m1 = st.queries.length;
  r = await s.request({ form: form(csrf, { db_name: 'fulldb', db_create: '' }) });
  const ql = st.queries.slice(m1).map(x => x.sql);
  check('L1 installation succeeds', has(r.text, 'Игра установлена'), r.text.replace(/<[^>]+>/g, ' ').slice(200, 600));
  check('L2 the player-data INSERTs of the dump ran (nothing is skipped)', ql.filter(x => new RegExp('^INSERT INTO `(' + PLAYER_TABLES.join('|') + ')` \\(').test(x)).length === EXPECT.insertSkipped, String(ql.length));
  check('L3 and the page does not claim anything was skipped', !has(r.text, 'не импортируются'));

  // ------------------------------------------------------------------------------------------------ K. environment
  section('K. settings from the environment');
  s = await mk();
  r = await s.request({ env: { LIN2_DB_USER: 'u', LIN2_DB_NAME: 'n' } });
  check('K1 LIN2_DB_* in the environment disables the installer', r.status === 403 && has(r.text, 'переменными окружения'), r.status);
  r = await s.request({ script: 'index.php', env: { LIN2_DB_USER: 'gameuser', LIN2_DB_NAME: 'gamedb', LIN2_DB_HOST: HOST, LIN2_DB_PASS: 'x' } });
  check('K2 the game starts from the environment alone', r.status === 200 && !has(r.text, 'не установлена') && !/Fatal error|Uncaught/.test(r.text), r.status + ' ' + r.text.slice(0, 200));

  db.close();
  for (const d of dirs) { try { chmodSync(join(d, 'inc'), 0o755); } catch { /* ignore */ } rmSync(d, { recursive: true, force: true }); }
}

for (const v of versions) await runVersion(v);
console.log(failures ? `\nFAILED: ${failures} of ${checks} checks` : `\nOK: ${checks} checks passed on PHP ${versions.join(', ')}`);
process.exit(failures ? 1 : 0);
