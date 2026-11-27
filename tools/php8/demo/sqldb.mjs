// A small REAL database for the demo and for browser tests: it speaks the MySQL wire protocol (so PHP's mysqli connects to it
// as to a normal MySQL server) and stores the data in SQLite (node:sqlite, Node 22.13+).
//
// The game is written for MySQL 5, so every statement is translated first (translate() below):
//   * INSERT ... SET a=1  ->  INSERT ... (a) VALUES (1);  INSERT IGNORE -> INSERT OR IGNORE
//   * UPDATE / DELETE ... [ORDER BY] LIMIT n  ->  WHERE rowid IN (SELECT rowid ... LIMIT n)
//   * "double quoted" strings and \' \\ \n escapes, && and ||, RAND(), NOW(), CONCAT(), IF() ...
//   * CREATE TABLE (MySQL types, ENGINE=...) and the ALTER TABLE forms of a phpMyAdmin 4.2 dump (ADD PRIMARY KEY,
//     MODIFY ... AUTO_INCREMENT, ADD KEY / UNIQUE KEY): a table is rebuilt in SQLite
//   * text columns are NOCASE (MySQL compares strings case-insensitively), NOT NULL columns get the implicit default
//     (0 / '') the way MySQL does with sql_mode=''
//
// It is a development tool, NOT a MySQL replacement: it exists so that the game can be run, clicked through and tested
// (state changes, fights that are won and lost) without a MySQL server. Statements it cannot run are logged.
import mysql from 'mysql2';
import { DatabaseSync } from 'node:sqlite';
import { createHash } from 'node:crypto';
import { appendFileSync } from 'node:fs';

// ---------------------------------------------------------------------------------------------------------------
// tokenizer
// ---------------------------------------------------------------------------------------------------------------
const OPS3 = ['<=>'];
const OPS2 = ['<=', '>=', '<>', '!=', '&&', '||', ':=', '<<', '>>'];

export function tokenize(sql) {
  const toks = [];
  const n = sql.length;
  let i = 0;
  const ESC = { n: '\n', r: '\r', t: '\t', '0': '\0', b: '\b', Z: '\x1a' };
  while (i < n) {
    const c = sql[i];
    if (/\s/.test(c)) { let j = i + 1; while (j < n && /\s/.test(sql[j])) j++; toks.push({ t: 'ws', v: sql.slice(i, j) }); i = j; continue; }
    if (c === '/' && sql[i + 1] === '*') {
      const e = sql.indexOf('*/', i + 2);
      const body = sql.slice(i + 2, e < 0 ? n : e);
      if (body[0] === '!') toks.push(...tokenize(body.replace(/^!\d*/, '')));      // /*!40101 SET NAMES utf8 */ is executed
      i = e < 0 ? n : e + 2; continue;
    }
    if ((c === '-' && sql[i + 1] === '-' && /\s/.test(sql[i + 2] || ' ')) || c === '#') { const e = sql.indexOf('\n', i); i = e < 0 ? n : e + 1; continue; }
    if (c === "'" || c === '"') {
      let j = i + 1, v = '';
      while (j < n) {
        const d = sql[j];
        if (d === '\\' && j + 1 < n) { const x = sql[j + 1]; v += x in ESC ? ESC[x] : x; j += 2; continue; }
        if (d === c) { if (sql[j + 1] === c) { v += c; j += 2; continue; } break; }
        v += d; j++;
      }
      toks.push({ t: 'str', v }); i = j + 1; continue;
    }
    if (c === '`') {
      let j = i + 1, v = '';
      while (j < n) { if (sql[j] === '`') { if (sql[j + 1] === '`') { v += '`'; j += 2; continue; } break; } v += sql[j++]; }
      toks.push({ t: 'id', v }); i = j + 1; continue;
    }
    if (/[0-9]/.test(c) || (c === '.' && /[0-9]/.test(sql[i + 1] || ''))) {
      const m = /^(?:0[xX][0-9a-fA-F]+|\d*\.?\d+(?:[eE][+-]?\d+)?)/.exec(sql.slice(i));
      toks.push({ t: 'num', v: m[0] }); i += m[0].length; continue;
    }
    if (/[A-Za-z_@$\u0080-\uffff]/.test(c)) {
      let j = i + 1; while (j < n && /[A-Za-z0-9_@$\u0080-\uffff]/.test(sql[j])) j++;
      toks.push({ t: 'word', v: sql.slice(i, j) }); i = j; continue;
    }
    const o3 = OPS3.find(o => sql.startsWith(o, i)); if (o3) { toks.push({ t: 'op', v: o3 }); i += 3; continue; }
    const o2 = OPS2.find(o => sql.startsWith(o, i)); if (o2) { toks.push({ t: 'op', v: o2 }); i += 2; continue; }
    toks.push({ t: 'op', v: c }); i++;
  }
  return toks;
}

// a NUL character cannot be part of the SQL text sent to SQLite (it ends the statement): the dump has such a value in `mobs`
const q = s => { const p = String(s).split('\0').map(x => "'" + x.replace(/'/g, "''") + "'"); return p.length === 1 ? p[0] : '(' + p.join(' || char(0) || ') + ')'; };
const qid = s => '"' + String(s).replace(/"/g, '""') + '"';
const up = t => (t && t.t === 'word' ? t.v.toUpperCase() : '');
const sig = toks => toks.filter(t => t.t !== 'ws');

// function names that SQLite does not have (or has under another name)
const RENAME = { RAND: 'random', LCASE: 'lower', UCASE: 'upper', CHAR_LENGTH: 'length', CHARACTER_LENGTH: 'length', CEILING: 'ceil', POWER: 'pow', IF: 'iif',
  GREATEST: 'max', LEAST: 'min', SUBSTRING: 'substr', CURRENT_TIMESTAMP: 'now', SYSDATE: 'now', LOCALTIME: 'now', LOCALTIMESTAMP: 'now' };

function emit(toks) {
  let out = '';
  for (let k = 0; k < toks.length; k++) {
    const t = toks[k];
    switch (t.t) {
      case 'ws': out += t.v; break;
      case 'str': out += q(t.v); break;
      case 'id': out += qid(t.v); break;
      case 'word': {
        const U = t.v.toUpperCase();
        let nx = k + 1; while (toks[nx] && toks[nx].t === 'ws') nx++;
        const isCall = toks[nx] && toks[nx].t === 'op' && toks[nx].v === '(';
        if (isCall && RENAME[U]) out += RENAME[U];
        else if (!isCall && (U === 'CURRENT_TIMESTAMP' || U === 'LOCALTIME' || U === 'LOCALTIMESTAMP')) out += 'now()';
        else if (U === 'DIV') out += '/';
        else if (U === 'UNSIGNED' || U === 'SIGNED') out += 'INTEGER';
        else out += t.v;
        break;
      }
      case 'op': out += t.v === '&&' ? ' AND ' : t.v === '||' ? ' OR ' : t.v === '<=>' ? ' IS ' : t.v === ':=' ? '=' : t.v; break;
      default: out += t.v;
    }
  }
  return out;
}

// splits a token list at top-level commas
function splitCommas(toks) {
  const parts = []; let cur = [], depth = 0;
  for (const t of toks) {
    if (t.t === 'op' && t.v === '(') depth++;
    if (t.t === 'op' && t.v === ')') depth--;
    if (depth === 0 && t.t === 'op' && t.v === ',') { parts.push(cur); cur = []; } else cur.push(t);
  }
  if (cur.length) parts.push(cur);
  return parts;
}

// index of the first top-level keyword (depth 0) of the list, from `from`
function findKw(toks, kw, from = 0) {
  let depth = 0;
  for (let k = from; k < toks.length; k++) {
    const t = toks[k];
    if (t.t === 'op' && t.v === '(') depth++;
    else if (t.t === 'op' && t.v === ')') depth--;
    else if (depth === 0 && up(t) === kw) return k;
  }
  return -1;
}
const trim = toks => { let a = 0, b = toks.length; while (a < b && toks[a].t === 'ws') a++; while (b > a && toks[b - 1].t === 'ws') b--; return toks.slice(a, b); };
const ident = t => (t && (t.t === 'id' || t.t === 'word') ? t.v : null);

// ---------------------------------------------------------------------------------------------------------------
// CREATE TABLE / ALTER TABLE: the schema is kept as data and the SQLite table is (re)built from it
// ---------------------------------------------------------------------------------------------------------------
const NUM_TYPES = new Set(['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'integer', 'year', 'bit', 'bool', 'boolean']);
const REAL_TYPES = new Set(['decimal', 'numeric', 'float', 'double', 'real']);
const TIME_TYPES = new Set(['date', 'datetime', 'timestamp', 'time']);

function colDef(toks) {
  // `name` type[(args)] [UNSIGNED] [CHARACTER SET x] [COLLATE y] [NOT NULL|NULL] [DEFAULT v] [AUTO_INCREMENT] [COMMENT 'x'] ...
  const s = sig(toks);
  const col = { name: ident(s[0]), type: (ident(s[1]) || 'text').toLowerCase(), notNull: false, def: undefined, auto: false };
  for (let k = 2; k < s.length; k++) {
    const U = up(s[k]);
    if (U === 'NOT' && up(s[k + 1]) === 'NULL') { col.notNull = true; k++; }
    else if (U === 'AUTO_INCREMENT') col.auto = true;
    else if (U === 'DEFAULT') {
      const v = s[k + 1]; k++;
      if (!v) continue;
      if (v.t === 'str') col.def = q(v.v);
      else if (v.t === 'num') col.def = v.v;
      else if (v.t === 'op' && v.v === '-' && s[k + 1] && s[k + 1].t === 'num') { col.def = '-' + s[k + 1].v; k++; }
      else if (up(v) === 'NULL') col.def = 'NULL';
      else if (up(v) === 'CURRENT_TIMESTAMP') { col.def = "(datetime('now','localtime'))"; if (s[k + 1] && s[k + 1].v === '(') k += 2; }
      else col.def = v.v;
    }
  }
  return col;
}

function sqliteType(col) {
  if (NUM_TYPES.has(col.type)) return 'INTEGER';
  if (REAL_TYPES.has(col.type)) return 'REAL';
  if (col.type.endsWith('blob') || col.type === 'binary' || col.type === 'varbinary') return 'BLOB';
  return 'TEXT COLLATE NOCASE';
}

function implicitDefault(col) {
  if (NUM_TYPES.has(col.type) || REAL_TYPES.has(col.type)) return '0';
  if (col.type === 'date') return "'0000-00-00'";
  if (col.type === 'datetime' || col.type === 'timestamp') return "'0000-00-00 00:00:00'";
  return "''";
}

function ddlOf(name, meta) {
  const lines = [];
  const autoCol = meta.cols.find(c => c.auto);
  for (const c of meta.cols) {
    let d = qid(c.name) + ' ' + sqliteType(c);
    if (autoCol === c) d = qid(c.name) + ' INTEGER PRIMARY KEY AUTOINCREMENT';
    else {
      if (c.notNull) d += ' NOT NULL';
      const def = c.def !== undefined ? c.def : c.notNull ? implicitDefault(c) : undefined;
      if (def !== undefined) d += ' DEFAULT ' + def;
    }
    lines.push(d);
  }
  if (!autoCol && meta.pk && meta.pk.length) lines.push('PRIMARY KEY (' + meta.pk.map(qid).join(', ') + ')');
  for (const u of meta.uniques || []) lines.push('UNIQUE (' + u.map(qid).join(', ') + ')');
  return `CREATE TABLE ${qid(name)} (\n  ${lines.join(',\n  ')}\n)`;
}

function parseCreate(toks) {
  const s = sig(toks);
  let k = 0; while (up(s[k]) !== 'TABLE') k++; k++;
  let ine = false;
  if (up(s[k]) === 'IF') { ine = true; k += 3; }
  let name = ident(s[k]); k++;
  if (s[k] && s[k].v === '.') { name = ident(s[k + 1]); k += 2; }
  while (s[k] && s[k].v !== '(') k++;
  // body = tokens between the matching parentheses
  let depth = 0, a = k + 1, b = k;
  for (let j = k; j < s.length; j++) { if (s[j].v === '(' && s[j].t === 'op') depth++; if (s[j].v === ')' && s[j].t === 'op' && --depth === 0) { b = j; break; } }
  const meta = { cols: [], pk: [], uniques: [] };
  for (const part of splitCommas(s.slice(a, b))) {
    const p = sig(part);
    if (!p.length) continue;
    const first = up(p[0]);
    const colsOf = () => { const o = p.findIndex(t => t.v === '(' && t.t === 'op'); const out = []; for (let j = o + 1; j < p.length && p[j].v !== ')'; j++) { const id = ident(p[j]); if (id !== null && p[j + 1] && p[j + 1].v === '(') { out.push(id); while (p[j].v !== ')') j++; } else if (id !== null) out.push(id); } return out; };
    if (first === 'PRIMARY') meta.pk = colsOf();
    else if (first === 'UNIQUE') meta.uniques.push(colsOf());
    else if (first === 'KEY' || first === 'INDEX' || first === 'FULLTEXT' || first === 'SPATIAL' || first === 'CONSTRAINT' || first === 'FOREIGN') { /* not needed */ }
    else meta.cols.push(colDef(part));
  }
  return { name, meta, ifNotExists: ine };
}

// ---------------------------------------------------------------------------------------------------------------
// the engine
// ---------------------------------------------------------------------------------------------------------------
export class SqlEngine {
  constructor({ file = ':memory:', dbName = 'siska', logFile = null } = {}) {
    this.db = new DatabaseSync(file);
    this.dbName = dbName;
    this.logFile = logFile;
    this.meta = new Map();
    this.stats = { queries: 0, errors: 0 };
    this.errors = [];
    const fn = (name, f, varargs = true) => this.db.function(name, { varargs, deterministic: false }, f);
    const now = () => { const d = new Date(); const p = x => String(x).padStart(2, '0'); return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`; };
    const toDate = v => { if (v === null || v === undefined) return null; if (typeof v === 'number') return new Date(v * 1000); const d = new Date(String(v).replace(' ', 'T')); return isNaN(d) ? null : d; };
    fn('now', () => now());
    fn('curdate', () => now().slice(0, 10));
    fn('curtime', () => now().slice(11));
    fn('unix_timestamp', (v) => { if (v === undefined) return Math.floor(Date.now() / 1000); const d = toDate(v); return d ? Math.floor(d.getTime() / 1000) : 0; });
    fn('from_unixtime', (v) => { const d = new Date(Number(v) * 1000); const p = x => String(x).padStart(2, '0'); return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`; });
    fn('concat', (...a) => (a.some(x => x === null) ? null : a.map(String).join('')));
    fn('concat_ws', (sep, ...a) => a.filter(x => x !== null).map(String).join(String(sep)));
    fn('find_in_set', (x, list) => { const i = String(list).split(',').indexOf(String(x)); return i < 0 ? 0 : i + 1; });
    fn('left', (s, n) => String(s).slice(0, Number(n)));
    fn('right', (s, n) => (Number(n) > 0 ? String(s).slice(-Number(n)) : ''));
    fn('lpad', (s, n, p) => String(s).padStart(Number(n), String(p)).slice(-Number(n)));
    fn('rpad', (s, n, p) => String(s).padEnd(Number(n), String(p)).slice(0, Number(n)));
    fn('md5', (s) => createHash('md5').update(String(s)).digest('hex'));
    fn('mod', (a, b) => Number(a) % Number(b));
    fn('truncate', (a, d) => { const f = 10 ** Number(d); return Math.trunc(Number(a) * f) / f; });
    fn('field', (x, ...a) => a.findIndex(v => String(v) === String(x)) + 1);
    fn('regexp', (re, s) => (s !== null && new RegExp(String(re), 'i').test(String(s)) ? 1 : 0), true);
    fn('date_format', (d, f) => { const t = toDate(d); if (!t) return null; const p = x => String(x).padStart(2, '0'); return String(f).replace(/%([YmdHisyM])/g, (_, c) => ({ Y: t.getFullYear(), m: p(t.getMonth() + 1), d: p(t.getDate()), H: p(t.getHours()), i: p(t.getMinutes()), s: p(t.getSeconds()), y: String(t.getFullYear()).slice(2), M: t.toLocaleString('en', { month: 'long' }) })[c]); });
    fn('datediff', (a, b) => Math.round((toDate(a) - toDate(b)) / 86400000));
  }

  log(line) { if (this.logFile) appendFileSync(this.logFile, line + '\n'); }

  tableNames() { return this.db.prepare("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name").all().map(r => r.name); }

  metaOf(name) {
    const key = name.toLowerCase();
    if (this.meta.has(key)) return this.meta.get(key);
    const rows = this.db.prepare(`PRAGMA table_info(${qid(name)})`).all();
    if (!rows.length) return null;
    const m = { cols: rows.map(r => ({ name: r.name, type: /INT/i.test(r.type) ? 'int' : /REAL/i.test(r.type) ? 'double' : 'text', notNull: !!r.notnull, def: r.dflt_value === null ? undefined : r.dflt_value, auto: false })), pk: rows.filter(r => r.pk).map(r => r.name), uniques: [] };
    this.meta.set(key, m);
    return m;
  }

  createTable(parsed) {
    const exists = this.db.prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name = ? COLLATE NOCASE").get(parsed.name);
    if (exists) { if (parsed.ifNotExists) return { kind: 'run', changes: 0 }; throw Object.assign(new Error(`Table '${parsed.name}' already exists`), { mysql: 1050 }); }
    this.meta.set(parsed.name.toLowerCase(), parsed.meta);
    this.db.exec(ddlOf(parsed.name, parsed.meta));
    return { kind: 'run', changes: 0 };
  }

  rebuild(name, meta) {
    const old = name + '__old';
    const cols = this.db.prepare(`PRAGMA table_info(${qid(name)})`).all().map(r => r.name).filter(c => meta.cols.some(m => m.name.toLowerCase() === c.toLowerCase()));
    this.db.exec('BEGIN');
    try {
      this.db.exec(`ALTER TABLE ${qid(name)} RENAME TO ${qid(old)}`);
      this.db.exec(ddlOf(name, meta));
      if (cols.length) this.db.exec(`INSERT OR IGNORE INTO ${qid(name)} (${cols.map(qid).join(',')}) SELECT ${cols.map(qid).join(',')} FROM ${qid(old)}`);
      this.db.exec(`DROP TABLE ${qid(old)}`);
      this.db.exec('COMMIT');
    } catch (e) { this.db.exec('ROLLBACK'); throw e; }
  }

  alterTable(toks) {
    const s = sig(toks);
    let k = 0; while (up(s[k]) !== 'TABLE') k++; k++;
    const name = ident(s[k]); k++;
    const meta = this.metaOf(name);
    if (!meta) throw Object.assign(new Error(`Table '${this.dbName}.${name}' doesn't exist`), { mysql: 1146 });
    let changed = false;
    for (const part of splitCommas(s.slice(k))) {
      const p = sig(part);
      if (!p.length) continue;
      const U = up(p[0]);
      const colsOf = (from) => { const out = []; for (let j = from; j < p.length; j++) { if (p[j].v === '(' && p[j].t === 'op') continue; if (p[j].v === ')' && p[j].t === 'op') break; const id = ident(p[j]); if (id !== null) { if (p[j + 1] && p[j + 1].v === '(') { while (p[j].v !== ')') j++; } out.push(id); } } return out; };
      if (U === 'ADD' && up(p[1]) === 'PRIMARY') { meta.pk = colsOf(3); changed = true; }
      else if (U === 'ADD' && up(p[1]) === 'UNIQUE') { const o = p.findIndex(t => t.v === '(' && t.t === 'op'); meta.uniques.push(colsOf(o)); changed = true; }
      else if (U === 'ADD' && (up(p[1]) === 'KEY' || up(p[1]) === 'INDEX' || up(p[1]) === 'FULLTEXT')) { /* indexes only speed things up */ }
      else if (U === 'MODIFY' || U === 'CHANGE') {
        const def = colDef(U === 'CHANGE' ? part.slice(part.indexOf(p[2])) : part.slice(part.indexOf(p[1])));
        const target = meta.cols.findIndex(c => c.name.toLowerCase() === (U === 'CHANGE' ? ident(p[1]) : def.name).toLowerCase());
        if (target >= 0) { def.name = U === 'CHANGE' ? def.name : meta.cols[target].name; meta.cols[target] = def; changed = true; }
      } else if (U === 'ADD' && (up(p[1]) === 'COLUMN' || ident(p[1]))) {
        const from = up(p[1]) === 'COLUMN' ? part.indexOf(p[2]) : part.indexOf(p[1]);
        const def = colDef(part.slice(from));
        if (!meta.cols.some(c => c.name.toLowerCase() === def.name.toLowerCase())) { meta.cols.push(def); changed = true; }
      } else if (U === 'DROP' && up(p[1]) !== 'PRIMARY' && up(p[1]) !== 'KEY' && up(p[1]) !== 'INDEX') {
        const nm = ident(up(p[1]) === 'COLUMN' ? p[2] : p[1]);
        const idx = meta.cols.findIndex(c => c.name.toLowerCase() === String(nm).toLowerCase());
        if (idx >= 0) { meta.cols.splice(idx, 1); changed = true; }
      }
      // AUTO_INCREMENT=N, ENGINE=..., CHARSET=...: ignored
    }
    if (changed) this.rebuild(name, meta);
    return { kind: 'run', changes: 0 };
  }

  // ------------------------------------------------------------------------------------------------------------
  // statement translation
  // ------------------------------------------------------------------------------------------------------------
  translate(sqlRaw) {
    let toks = tokenize(sqlRaw);
    while (toks.length && (toks[toks.length - 1].t === 'ws' || (toks[toks.length - 1].t === 'op' && toks[toks.length - 1].v === ';'))) toks.pop();
    toks = trim(toks);
    const s = sig(toks);
    if (!s.length) return { kind: 'noop' };
    const kw = up(s[0]);

    if (['SET', 'USE', 'LOCK', 'UNLOCK', 'GRANT', 'FLUSH', 'OPTIMIZE', 'REPAIR', 'ANALYZE', 'CHECK', 'KILL', 'DO'].includes(kw)) return { kind: 'noop' };
    if (kw === 'CREATE' && ['DATABASE', 'SCHEMA'].includes(up(s[1]))) return { kind: 'noop' };
    if (kw === 'DROP' && ['DATABASE', 'SCHEMA'].includes(up(s[1]))) return { kind: 'noop' };
    if (kw === 'CREATE' && up(s[1]) === 'TABLE') return { kind: 'create', parsed: parseCreate(toks) };
    if (kw === 'ALTER' && up(s[1]) === 'TABLE') return { kind: 'alter', toks };
    if (kw === 'DROP' && up(s[1]) === 'TABLE') {
      const names = []; let ifExists = false;
      for (let j = 2; j < s.length; j++) { if (up(s[j]) === 'IF') { ifExists = true; j += 1; continue; } const id = ident(s[j]); if (id !== null && up(s[j]) !== 'IF' && up(s[j]) !== 'EXISTS') names.push(id); }
      return { kind: 'drop', names, ifExists };
    }
    if (kw === 'TRUNCATE') { const name = ident(s[up(s[1]) === 'TABLE' ? 2 : 1]); return { kind: 'run', sql: `DELETE FROM ${qid(name)}` }; }
    if (kw === 'SHOW') {
      const what = up(s[1]);
      if (what === 'TABLES') return { kind: 'show-tables' };
      if (what === 'COLUMNS' || what === 'FIELDS') return { kind: 'show-columns', table: ident(s[3]) };
      return { kind: 'rows-const', cols: ['Value'], rows: [['1']] };
    }
    if (kw === 'DESCRIBE' || kw === 'DESC') return { kind: 'show-columns', table: ident(s[1]) };
    if (kw === 'EXPLAIN') return { kind: 'rows-const', cols: ['Value'], rows: [['1']] };
    if (kw === 'BEGIN' || kw === 'COMMIT' || kw === 'ROLLBACK' || kw === 'START') return { kind: 'noop' };

    if (kw === 'SELECT' && s.length > 1 && /^(version|database|schema)$/i.test(s[1].v) && s[2] && s[2].v === '(') {
      return { kind: 'rows-const', cols: [sqlRaw.replace(/^\s*select\s+/i, '').replace(/;\s*$/, '')], rows: [[/^version/i.test(s[1].v) ? '8.0.0-sqlite-demo' : this.dbName]] };
    }
    if (kw === 'SELECT' && s.length === 2 && s[1].v.startsWith('@@')) return { kind: 'rows-const', cols: [s[1].v], rows: [['8.0.0-sqlite-demo']] };

    if (kw === 'INSERT' || kw === 'REPLACE') return { kind: 'run', sql: this.translateInsert(toks), insert: true };
    if (kw === 'UPDATE') return { kind: 'run', sql: this.translateUpdate(toks) };
    if (kw === 'DELETE') return { kind: 'run', sql: this.translateDelete(toks) };

    // SELECT and everything else: drop SQL_CALC_FOUND_ROWS, FOR UPDATE, LOCK IN SHARE MODE
    let t2 = toks.filter(t => !(t.t === 'word' && /^(SQL_CALC_FOUND_ROWS|SQL_NO_CACHE|SQL_CACHE|STRAIGHT_JOIN|HIGH_PRIORITY)$/i.test(t.v)));
    const f = findKw(t2, 'FOR'); if (f >= 0 && up(sig(t2.slice(f))[1]) === 'UPDATE') t2 = t2.slice(0, f);
    return { kind: kw === 'SELECT' || kw === 'WITH' || kw === 'PRAGMA' ? 'rows' : 'run', sql: emit(t2) };
  }

  translateInsert(toks) {
    // INSERT [LOW_PRIORITY|DELAYED|HIGH_PRIORITY] [IGNORE] [INTO] tbl [(cols)] VALUES ... | SET a=1, b=2 | SELECT ...
    let k = 0; const out = [];
    const head = []; let ignore = false;
    const s = toks;
    while (k < s.length && s[k].t === 'ws') k++;
    const verb = up(s[k]); k++;
    for (; k < s.length; k++) {
      const U = up(s[k]);
      if (s[k].t === 'ws') continue;
      if (U === 'IGNORE') { ignore = true; continue; }
      if (U === 'LOW_PRIORITY' || U === 'DELAYED' || U === 'HIGH_PRIORITY') continue;
      if (U === 'INTO') continue;
      break;
    }
    let table = ident(s[k]); k++;
    if (s[k] && s[k].v === '.' && s[k].t === 'op') { table = ident(s[k + 1]); k += 2; }
    const rest = s.slice(k);
    const si = findKw(rest, 'SET');
    const first = sig(rest)[0];
    const dup = findKw(rest, 'ON');
    let body;
    if (si >= 0 && (first && up(first) === 'SET')) {
      const assigns = splitCommas(rest.slice(si + 1));
      const cols = [], vals = [];
      for (const a of assigns) {
        const as = trim(a); const eq = as.findIndex(t => t.t === 'op' && t.v === '=');
        cols.push(qid(ident(as[0]))); vals.push(emit(trim(as.slice(eq + 1))));
      }
      body = `(${cols.join(', ')}) VALUES (${vals.join(', ')})`;
    } else body = emit(dup >= 0 ? rest.slice(0, dup) : rest);
    return `${verb === 'REPLACE' ? 'INSERT OR REPLACE' : ignore ? 'INSERT OR IGNORE' : 'INSERT'} INTO ${qid(table)} ${body}`;
  }

  // splits "UPDATE/DELETE ... [WHERE w] [ORDER BY o] [LIMIT n]" and rebuilds it for SQLite (no ORDER BY / LIMIT there)
  tail(toks) {
    const lim = findKw(toks, 'LIMIT');
    const ord = findKw(toks, 'ORDER');
    const whr = findKw(toks, 'WHERE');
    const end = toks.length;
    const cut = [whr, ord, lim].filter(x => x >= 0);
    const headEnd = cut.length ? Math.min(...cut) : end;
    const clause = (from, to) => (from < 0 ? [] : toks.slice(from, to));
    const next = (pos) => Math.min(...[ord, lim, end].filter(x => x > pos));
    const where = whr >= 0 ? clause(whr + 1, next(whr)) : [];
    const order = ord >= 0 ? clause(findKw(toks, 'BY', ord) + 1, lim >= 0 && lim > ord ? lim : end) : [];
    const limit = lim >= 0 ? clause(lim + 1, end) : [];
    return { head: toks.slice(0, headEnd), where, order, limit };
  }

  translateUpdate(toks) {
    const { head, where, order, limit } = this.tail(toks);
    // head: UPDATE [LOW_PRIORITY] [IGNORE] tbl SET a=1,b=2
    const sidx = findKw(head, 'SET');
    const pre = sig(head.slice(0, sidx)).filter(t => !/^(UPDATE|LOW_PRIORITY|IGNORE)$/i.test(t.v));
    let table = ident(pre[pre.length - 1]);
    const setSql = emit(head.slice(sidx));
    const w = emit(where).trim();
    if (limit.length) {
      const sub = `SELECT rowid FROM ${qid(table)}${w ? ' WHERE ' + w : ''}${order.length ? ' ORDER BY ' + emit(order) : ''} LIMIT ${emit(limit).trim()}`;
      return `UPDATE ${qid(table)} ${setSql} WHERE rowid IN (${sub})`;
    }
    return `UPDATE ${qid(table)} ${setSql}${w ? ' WHERE ' + w : ''}`;
  }

  translateDelete(toks) {
    const { head, where, order, limit } = this.tail(toks);
    const pre = sig(head).filter(t => !/^(DELETE|FROM|LOW_PRIORITY|QUICK|IGNORE)$/i.test(t.v));
    let table = ident(pre[pre.length - 1]);
    const w = emit(where).trim();
    if (limit.length) {
      const sub = `SELECT rowid FROM ${qid(table)}${w ? ' WHERE ' + w : ''}${order.length ? ' ORDER BY ' + emit(order) : ''} LIMIT ${emit(limit).trim()}`;
      return `DELETE FROM ${qid(table)} WHERE rowid IN (${sub})`;
    }
    return `DELETE FROM ${qid(table)}${w ? ' WHERE ' + w : ''}`;
  }

  // ------------------------------------------------------------------------------------------------------------
  // execution: returns { cols, rows } for result sets, or { affected, insertId }
  // ------------------------------------------------------------------------------------------------------------
  run(sqlRaw) {
    this.stats.queries++;
    let tr;
    try {
      tr = this.translate(sqlRaw);
      switch (tr.kind) {
        case 'noop': return { affected: 0, insertId: 0 };
        case 'rows-const': return { cols: tr.cols, rows: tr.rows };
        case 'create': { this.createTable(tr.parsed); return { affected: 0, insertId: 0 }; }
        case 'alter': { this.alterTable(tr.toks); return { affected: 0, insertId: 0 }; }
        case 'drop': {
          for (const n of tr.names) {
            const had = this.db.prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name = ? COLLATE NOCASE").get(n);
            if (!had && !tr.ifExists) throw Object.assign(new Error(`Unknown table '${n}'`), { mysql: 1051 });
            this.db.exec(`DROP TABLE IF EXISTS ${qid(n)}`); this.meta.delete(n.toLowerCase());
          }
          return { affected: 0, insertId: 0 };
        }
        case 'show-tables': return { cols: ['Tables_in_' + this.dbName], rows: this.tableNames().map(n => [n]) };
        case 'show-columns': {
          const rows = this.db.prepare(`PRAGMA table_info(${qid(tr.table)})`).all().map(r => [r.name, r.type, r.notnull ? 'NO' : 'YES', r.pk ? 'PRI' : '', r.dflt_value, '']);
          return { cols: ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'], rows };
        }
        case 'rows': {
          const st = this.db.prepare(tr.sql); st.setReturnArrays(true);
          const rows = st.all();
          const cols = st.columns().map(c => c.name);
          return { cols, rows };
        }
        default: {
          const r = this.db.prepare(tr.sql).run();
          return { affected: Number(r.changes), insertId: tr.insert ? Number(r.lastInsertRowid) : 0 };
        }
      }
    } catch (e) {
      this.stats.errors++;
      const msg = String(e && e.message || e);
      const rec = { sql: sqlRaw.replace(/\s+/g, ' ').slice(0, 300), translated: tr && tr.sql ? tr.sql.replace(/\s+/g, ' ').slice(0, 300) : '', message: msg };
      if (this.errors.length < 200) this.errors.push(rec);
      this.log('ERROR ' + msg + '\n   in:  ' + rec.sql + (rec.translated ? '\n   sql: ' + rec.translated : ''));
      let code = e.mysql || 1064;
      if (/no such table/i.test(msg)) code = 1146;
      else if (/no such column/i.test(msg) || /has no column named/i.test(msg)) code = 1054;
      else if (/UNIQUE constraint failed|PRIMARY KEY/i.test(msg)) code = 1062;
      else if (/NOT NULL constraint/i.test(msg)) code = 1048;
      throw Object.assign(new Error(msg), { mysql: code });
    }
  }
}

// splits a dump into statements (quote- and comment-aware; ';' ends a statement)
export function splitSql(text) {
  const out = []; let cur = '', q = null, i = 0;
  const n = text.length;
  while (i < n) {
    const c = text[i];
    if (q) {
      cur += c;
      if (c === '\\' && q !== '`') { cur += text[i + 1] || ''; i += 2; continue; }
      if (c === q) { if (text[i + 1] === q) { cur += q; i += 2; continue; } q = null; }
      i++; continue;
    }
    if (c === "'" || c === '"' || c === '`') { q = c; cur += c; i++; continue; }
    if (c === '-' && text[i + 1] === '-' && /\s/.test(text[i + 2] || ' ')) { const e = text.indexOf('\n', i); i = e < 0 ? n : e + 1; continue; }
    if (c === '#') { const e = text.indexOf('\n', i); i = e < 0 ? n : e + 1; continue; }
    if (c === '/' && text[i + 1] === '*' && text[i + 2] !== '!') { const e = text.indexOf('*/', i + 2); i = e < 0 ? n : e + 2; continue; }
    if (c === ';') { if (cur.trim()) out.push(cur.trim()); cur = ''; i++; continue; }
    cur += c; i++;
  }
  if (cur.trim()) out.push(cur.trim());
  return out;
}

// ---------------------------------------------------------------------------------------------------------------
// the MySQL protocol front end (same packet handling as smoke/fakedb.mjs, whose notes apply here as well)
// ---------------------------------------------------------------------------------------------------------------
const lenenc = n => (n < 251 ? Buffer.from([n]) : n < 65536 ? Buffer.from([0xfc, n & 0xff, n >> 8]) : (() => { const b = Buffer.alloc(9); b[0] = 0xfe; b.writeBigUInt64LE(BigInt(n), 1); return b; })());

function writeOk(conn, { affectedRows = 0, insertId = 0 } = {}) {
  const payload = Buffer.concat([Buffer.from([0]), lenenc(affectedRows), lenenc(insertId), Buffer.from([0x02, 0x00, 0x00, 0x00, 0x00])]);
  const header = Buffer.from([payload.length & 0xff, (payload.length >> 8) & 0xff, (payload.length >> 16) & 0xff, conn.sequenceId & 0xff]);
  conn.stream.write(Buffer.concat([header, payload]));
  conn.sequenceId = (conn.sequenceId + 1) % 256;
}

export function startSqlDb({ engine, port = 0, host = '127.0.0.1' } = {}) {
  const server = mysql.createServer();
  const colDef = name => ({ catalog: 'def', schema: engine.dbName, table: '', orgTable: '', name: String(name), orgName: String(name), characterSet: 33, columnLength: 1024, columnType: 253, flags: 0, decimals: 0 });
  const str = v => (v === null || v === undefined ? null : v instanceof Uint8Array ? Buffer.from(v) : typeof v === 'bigint' ? v.toString() : typeof v === 'number' ? (Number.isInteger(v) ? String(v) : String(v)) : String(v));
  const handle = (conn, sql) => {
    try {
      const r = engine.run(sql);
      if (r.cols) {
        conn.writeColumns(r.cols.map(colDef));
        for (const row of r.rows) conn.writeTextRow(row.map(str));
        conn.writeEof();
      } else writeOk(conn, { affectedRows: r.affected, insertId: r.insertId });
    } catch (e) {
      conn.writeError({ code: e.mysql || 1064, message: e.message });
    }
  };
  server.on('connection', conn => {
    conn.serverHandshake({
      protocolVersion: 10, serverVersion: '8.0.0-sqlite-demo', connectionId: 1, statusFlags: 2,
      characterSet: 33, capabilityFlags: 1 | 2 | 4 | 8 | 512 | 8192 | 32768 | 524288,
      authCallback: (_params, cb) => { cb(null); conn.sequenceId = 0; },
    });
    conn.stream.setNoDelay(true);
    const reply = fn => (...args) => { conn.sequenceId = 1; conn.stream.cork(); try { fn(...args); } catch (e) { conn.sequenceId = 1; conn.writeError({ code: 1064, message: 'demo db: ' + e.message }); } conn.stream.uncork(); conn.sequenceId = 0; };
    conn.on('query', reply(sql => handle(conn, sql)));
    conn.on('stmt_prepare', reply(sql => handle(conn, sql)));
    conn.on('init_db', reply(() => writeOk(conn)));
    conn.on('ping', reply(() => writeOk(conn)));
    conn.on('quit', () => conn.stream.end());
    conn.on('error', () => {});
  });
  return new Promise(resolve => server.listen(port, host, () => resolve({ port: server._server.address().port, engine, close: () => server.close() })));
}
