// Tests lin2i_sql_split() from install.php (the function that cuts shema.sql into single queries).
//
//   node smoke/sqlsplit.mjs [php-version]       (default 8.3)
//
//  1. hand-written tricky cases with the expected result;
//  2. the real dumps: statement kinds and counts;
//  3. a second, independent implementation (char-by-char state machine, written here in JS) must give the same result
//     on the real dumps and on several thousand random inputs built from the nasty tokens of the SQL lexer.
// The PHP function jumps from special character to special character with strcspn(), the reference below walks one
// character at a time - they share the rules, not the code.
import { startPhp, REPO } from '../common.mjs';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

const version = process.argv[2] || '8.3';
let checks = 0, failures = 0;
const check = (name, ok, detail = '') => { checks++; if (!ok) { failures++; console.log('  FAIL ' + name + (detail ? '  ::  ' + String(detail).slice(0, 600) : '')); } };

// ---------------------------------------------------------------------------------------------------------------
// reference implementation
// ---------------------------------------------------------------------------------------------------------------
function refSplit(sql) {
  const out = [];
  let i = sql.startsWith('\uFEFF') ? 1 : 0;
  const n = sql.length;
  let start = -1;
  const isWs = c => c === ' ' || c === '\t' || c === '\r' || c === '\n';
  const lineEnd = (from) => { const e = sql.indexOf('\n', from); return e < 0 ? n : e + 1; };
  const dashComment = (p) => sql[p] === '-' && sql[p + 1] === '-' && (p + 2 >= n || isWs(sql[p + 2]));
  while (i < n) {
    const c = sql[i];
    if (start < 0) {
      if (isWs(c)) { i++; continue; }
      if (c === '#' || dashComment(i)) { i = lineEnd(i); continue; }
      if (c === '/' && sql[i + 1] === '*' && sql[i + 2] !== '!') { const e = sql.indexOf('*/', i + 2); i = e < 0 ? n : e + 2; continue; }
      start = i;
    }
    if (c === "'" || c === '"' || c === '`') {
      i++;
      while (i < n) {
        if (sql[i] === '\\' && c !== '`') { i += 2; continue; }
        if (sql[i] === c) { if (sql[i + 1] === c) { i += 2; continue; } i++; break; }
        i++;
      }
      continue;
    }
    if (c === '#' || dashComment(i)) { i = lineEnd(i); continue; }
    if (c === '/' && sql[i + 1] === '*') { const e = sql.indexOf('*/', i + 2); i = e < 0 ? n : e + 2; continue; }
    if (c === ';') { const t = sql.slice(start, i).trim(); if (t) out.push(t); start = -1; i++; continue; }
    i++;
  }
  if (start >= 0) { const t = sql.slice(start).trim(); if (t) out.push(t); }
  return out;
}

// ---------------------------------------------------------------------------------------------------------------
const php = await startPhp(version, { '/work': REPO });
if (!php.fileExists('/internal/sqlsplit')) php.mkdir('/internal/sqlsplit');
async function phpSplit(inputs) {
  php.writeFile('/internal/sqlsplit/in.json', JSON.stringify(inputs));
  const r = await php.run({ code: `<?php
    define('LIN2I_LIB', 1);
    require '/work/install.php';
    $in = json_decode(file_get_contents('/internal/sqlsplit/in.json'), true);
    $out = array();
    foreach ($in as $s) { $out[] = lin2i_sql_split($s); }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);` });
  try { return JSON.parse(r.text); } catch { throw new Error('PHP output is not JSON: ' + (r.text + r.errors).slice(0, 500)); }
}

// ---------------------------------------------------------------------------------------------------------------
// 1. hand-written cases
// ---------------------------------------------------------------------------------------------------------------
const cases = [
  ['two statements', 'SELECT 1; SELECT 2;', ['SELECT 1', 'SELECT 2']],
  ['no trailing delimiter', 'SELECT 1; SELECT 2', ['SELECT 1', 'SELECT 2']],
  ['empty statements are dropped', ';;  ; SELECT 1;;', ['SELECT 1']],
  ['semicolon in single quotes', "INSERT INTO t VALUES ('a;b'); SELECT 2;", ["INSERT INTO t VALUES ('a;b')", 'SELECT 2']],
  ['semicolon in double quotes', 'SELECT "a;b"; SELECT 2;', ['SELECT "a;b"', 'SELECT 2']],
  ['semicolon in backticks', 'SELECT `a;b` FROM t; SELECT 2;', ['SELECT `a;b` FROM t', 'SELECT 2']],
  ['backslash-escaped quote', "INSERT INTO t VALUES ('it\\'s; fine'); SELECT 2;", ["INSERT INTO t VALUES ('it\\'s; fine')", 'SELECT 2']],
  ['doubled quote', "INSERT INTO t VALUES ('it''s; fine'); SELECT 2;", ["INSERT INTO t VALUES ('it''s; fine')", 'SELECT 2']],
  ['escaped backslash before the closing quote', "SELECT 'a\\\\'; SELECT 2;", ["SELECT 'a\\\\'", 'SELECT 2']],
  ['doubled backtick', 'SELECT `a``;b`; SELECT 2;', ['SELECT `a``;b`', 'SELECT 2']],
  ['line comment (-- ) hides a semicolon', 'SELECT 1 -- ; not the end\n; SELECT 2;', ['SELECT 1 -- ; not the end', 'SELECT 2']],
  ['# comment hides a semicolon', 'SELECT 1 # ; not the end\n; SELECT 2;', ['SELECT 1 # ; not the end', 'SELECT 2']],
  ['"--" without a space is not a comment', 'SELECT 1--1; SELECT 2;', ['SELECT 1--1', 'SELECT 2']],
  ['block comment hides a semicolon', 'SELECT /* ; */ 1; SELECT 2;', ['SELECT /* ; */ 1', 'SELECT 2']],
  ['comments between statements are dropped', '-- head\n# also\n/* block */ SELECT 1; -- tail\n/* x */ SELECT 2; -- end', ['SELECT 1', 'SELECT 2']],
  ['executable comment is a statement', '/*!40101 SET NAMES utf8 */;\nSELECT 1;', ['/*!40101 SET NAMES utf8 */', 'SELECT 1']],
  ['CRLF line endings', 'SELECT 1;\r\nSELECT 2;\r\n', ['SELECT 1', 'SELECT 2']],
  ['CRLF after "--"', 'SELECT 1; --\r\nSELECT 2;', ['SELECT 1', 'SELECT 2']],
  ['"--" at the very end', 'SELECT 1; --', ['SELECT 1']],
  ['unterminated string swallows the rest', "SELECT 'abc; SELECT 2;", ["SELECT 'abc; SELECT 2;"]],
  ['unterminated block comment', 'SELECT 1; /* never closed ; SELECT 2;', ['SELECT 1']],
  ['multi-line statement keeps its inner newlines', 'CREATE TABLE `a` (\n`id` int(11) NOT NULL\n) ENGINE=MyISAM;\nSELECT 1;', ['CREATE TABLE `a` (\n`id` int(11) NOT NULL\n) ENGINE=MyISAM', 'SELECT 1']],
  ['Cyrillic text', "INSERT INTO t VALUES ('Привет; мир'); SELECT 'Да';", ["INSERT INTO t VALUES ('Привет; мир')", "SELECT 'Да'"]],
  ['UTF-8 BOM at the start', '\uFEFFSELECT 1;', ['SELECT 1']],
  ['DELIMITER block (trigger body with semicolons)', 'DELIMITER //\nCREATE TRIGGER t BEFORE INSERT ON x FOR EACH ROW BEGIN SET @a=1; SET @b=2; END//\nDELIMITER ;\nSELECT 3;', ['CREATE TRIGGER t BEFORE INSERT ON x FOR EACH ROW BEGIN SET @a=1; SET @b=2; END', 'SELECT 3']],
  ['delimiter command is case-insensitive', 'delimiter $$\nSELECT 1$$\nDELIMITER ;\nSELECT 2;', ['SELECT 1', 'SELECT 2']],
  ['the word "delimiter" in a statement is not a command', "SELECT 'DELIMITER //'; SELECT 2;", ["SELECT 'DELIMITER //'", 'SELECT 2']],
  ['empty input', '', []],
  ['only comments', '-- a\n# b\n/* c */\n', []],
];
const got1 = await phpSplit(cases.map(c => c[1]));
cases.forEach((c, i) => check('case: ' + c[0], JSON.stringify(got1[i]) === JSON.stringify(c[2]), 'got ' + JSON.stringify(got1[i]) + ' expected ' + JSON.stringify(c[2])));

// ---------------------------------------------------------------------------------------------------------------
// 2./3. the real dumps
// ---------------------------------------------------------------------------------------------------------------
for (const file of ['shema.sql', 'shema_extra.sql']) {
  const sql = readFileSync(join(REPO, file), 'utf8');
  const [stmts] = await phpSplit([sql]);
  const ref = refSplit(sql);
  const kinds = {};
  for (const s of stmts) { const k = /^(\/\*!|[A-Za-z]+(?:\s+[A-Za-z]+)?)/.exec(s)[1].toUpperCase().replace(/\s+/g, ' '); kinds[k] = (kinds[k] || 0) + 1; }
  console.log(`  ${file}: ${stmts.length} statements ${JSON.stringify(kinds)}`);
  check(file + ': PHP and the reference agree on every statement', stmts.length === ref.length && stmts.every((s, i) => s === ref[i]), `${stmts.length} vs ${ref.length}`);
  check(file + ': every statement is a known kind', Object.keys(kinds).every(k => ['CREATE TABLE', 'ALTER TABLE', 'INSERT INTO', 'SET SQL', 'SET TIME', '/*!'].includes(k)), JSON.stringify(kinds));
  check(file + ': no statement is cut inside a string (every one has balanced quotes)', stmts.every(s => (s.replace(/\\./g, '').match(/'/g) || []).length % 2 === 0 || /''/.test(s)));
  check(file + ': splitting is lossless (joined statements contain every CREATE TABLE name)', [...sql.matchAll(/^CREATE TABLE IF NOT EXISTS `([^`]+)`/gm)].every(m => stmts.some(s => s.startsWith('CREATE TABLE IF NOT EXISTS `' + m[1] + '`'))));
  if (file === 'shema.sql') check('shema.sql: 144 CREATE, 252 ALTER, 64 INSERT', kinds['CREATE TABLE'] === 144 && kinds['ALTER TABLE'] === 252 && kinds['INSERT INTO'] === 64, JSON.stringify(kinds));
}

// ---------------------------------------------------------------------------------------------------------------
// random inputs
// ---------------------------------------------------------------------------------------------------------------
let seed = 12345;
const rnd = () => (seed = (seed * 1103515245 + 12345) & 0x7fffffff) / 0x7fffffff;
const pick = a => a[Math.floor(rnd() * a.length)];
const TOK = ['SELECT', ' ', ' ', '\n', '\r\n', '\t', ';', ';', "'a;b'", '"x;y"', '`t;t`', "'it''s'", "'it\\'s'", "'\\\\'", '-- c;\n', '--x', '1--1', '# c;\n', '/* c;d */', '/*!40101 SET x */',
  '`id`', '(', ')', ',', '=', "'", '"', '`', '\\', '/', '*', '-', '#', 'Привет', 'INSERT INTO t VALUES (1,2)', '/*', '*/', "'\\'", "''", '""'];
const inputs = [];
for (let k = 0; k < 4000; k++) { let s = ''; const len = 1 + Math.floor(rnd() * 24); for (let j = 0; j < len; j++) s += pick(TOK); inputs.push(s); }
const phpOut = await phpSplit(inputs);
let diff = 0, first = null;
inputs.forEach((s, i) => { const r = refSplit(s); if (JSON.stringify(r) !== JSON.stringify(phpOut[i])) { diff++; if (!first) first = { input: s, php: phpOut[i], ref: r }; } });
check(`random inputs: PHP and the reference agree on ${inputs.length} cases`, diff === 0, diff + ' differ, first: ' + JSON.stringify(first));

console.log(failures ? `\nFAILED: ${failures} of ${checks} checks` : `\nOK: ${checks} checks passed on PHP ${version}`);
process.exit(failures ? 1 : 0);
