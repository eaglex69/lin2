// A tiny fake MySQL server for smoke-testing the PHP code without a real database.
//  * speaks the MySQL wire protocol (via the server side of mysql2), accepts any credentials
//  * knows every table/column from shema.sql and answers SELECTs with plausible dummy rows
//  * INSERT/UPDATE/DELETE/SET/... simply succeed; queries on unknown tables fail with ER_NO_SUCH_TABLE
//  * a WHERE clause that compares something with an empty string ('') returns an empty set
//    (that is how "guest / not found" situations are simulated)
// It is NOT a SQL engine: it is only meant to let PHP pages run far enough to expose runtime errors.
import mysql from 'mysql2';
import { readFileSync, appendFileSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';

export function parseSchema(sqlText) {
  const tables = {};
  const re = /CREATE TABLE (?:IF NOT EXISTS )?`([^`]+)` \(([\s\S]*?)\n\) ENGINE/g;
  let m;
  while ((m = re.exec(sqlText))) {
    const cols = [];
    for (const line of m[2].split('\n')) {
      const t = line.trim();
      const cm = /^`([^`]+)`\s+([a-z]+)(?:\(([^)]*)\))?/i.exec(t);
      if (cm) cols.push({ name: cm[1], type: cm[2].toLowerCase(), arg: cm[3] || '' });
    }
    tables[m[1].toLowerCase()] = cols;
  }
  return tables;
}

const INT = new Set(['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'year']);
const REAL = new Set(['decimal', 'float', 'double', 'numeric']);
const TEXTY = new Set(['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'blob', 'tinyblob', 'mediumblob', 'longblob']);

// tables that normally have no row for a "healthy" player (no ban, not in battle, ...)
const EMPTY_TABLES = new Set(['tmp', 'tmp_zamok', 'ban', 'block', 'block_ip', 'block_account', 'arena_wait', 'invite', 'alarm', 'paty', 'smert', 'logar', 'logpk', 'karma']);

const OVERRIDES = {
  '*': { usr: 'Tester', nick: 'Tester', login: 'Tester', log: 'Tester', pass: '9a618248b64db62d15b300a07b00580b', id: '1' },
  account: { id: '1', active: '1', nick: 'Tester' },
  users: { id: '1', lvl: '20', hp: '100', hpall: '100', mp: '50', mpall: '50', exp: '1000', klas: 'wizard', storona: 'human', city: 'Talking Island Village', clan: '', dostup: '0', status: '', ali: '', zamujem: '', site: '', ref: '', account: '1', pol: 'm', prof: '' },
  mesto: { place: 'index' },
  lvl: { exp: '5000' },
  tmp: { ltime: String(Math.floor(Date.now() / 1000)) },     // the fight row that ok.php creates (click.mjs, remember mode): started just now, not 50 years ago = not lost by timeout
  options: { theme: 'osn' },        // a real player has no theme or a stylesheet name from /theme; the dummy '1' would be a link to a file that never existed
  mobs: { status: 'on', tip: 'mob', hp: '100', hpall: '100', name: 'Wolf', lvl: '5' },     // 'on' = a mob that can be attacked: the page renders its "attack" link
};

// data profiles:  dummy = plausible values (default) | zero = every number and every string is 0 | empty = SELECTs return no rows
export function seedValue(table, col, rowIdx, profile = 'dummy') {
  if (profile === 'zero') {
    const keep = { id: '1', usr: 'Tester', nick: 'Tester', active: '1', pass: '9a618248b64db62d15b300a07b00580b', account: '1' };
    if (keep[col.name] !== undefined && (table === 'users' || table === 'account')) return keep[col.name];
    if (INT.has(col.type) || REAL.has(col.type)) return '0';
    if (col.type === 'enum' || col.type === 'set') { const f = /'([^']*)'/.exec(col.arg); return f ? f[1] : ''; }
    if (col.type === 'date') return '0000-00-00';
    if (col.type === 'datetime' || col.type === 'timestamp') return '0000-00-00 00:00:00';
    // text columns hold numbers as often as words in this game (e.g. regenerator.last varchar(25) = a timestamp), so '0',
    // not '' - empty strings are exercised through the request parameters (--garbage '')
    return '0';
  }
  const o = (OVERRIDES[table] || {})[col.name];
  if (o !== undefined) return o;
  const g = OVERRIDES['*'][col.name];
  if (g !== undefined && TEXTY.has(col.type)) return g;
  if (INT.has(col.type)) return String(10 + rowIdx);
  if (REAL.has(col.type)) return '10.5';
  if (col.type === 'enum' || col.type === 'set') { const f = /'([^']*)'/.exec(col.arg); return f ? f[1] : ''; }
  if (col.type === 'date') return '2026-01-01';
  if (col.type === 'datetime' || col.type === 'timestamp') return '2026-01-01 12:00:00';
  if (col.type === 'time') return '12:00:00';
  if (TEXTY.has(col.type)) return '1';
  return '1';
}

function splitTopLevel(s, sep = ',') {
  const out = []; let depth = 0, cur = '', q = null;
  for (let i = 0; i < s.length; i++) {
    const c = s[i];
    if (q) { cur += c; if (c === '\\') { cur += s[++i] || ''; } else if (c === q) q = null; continue; }
    if (c === "'" || c === '"' || c === '`') { q = c; cur += c; continue; }
    if (c === '(') depth++; else if (c === ')') depth--;
    if (c === sep && depth === 0) { out.push(cur); cur = ''; } else cur += c;
  }
  if (cur.trim()) out.push(cur);
  return out;
}

function findTopLevelKeyword(s, kw) {
  let depth = 0, q = null;
  const re = new RegExp('^' + kw + '\\b', 'i');
  for (let i = 0; i < s.length; i++) {
    const c = s[i];
    if (q) { if (c === '\\') i++; else if (c === q) q = null; continue; }
    if (c === "'" || c === '"' || c === '`') { q = c; continue; }
    if (c === '(') depth++; else if (c === ')') depth--;
    else if (depth === 0 && /\s/.test(s[i - 1] || ' ') && re.test(s.slice(i))) return i;
  }
  return -1;
}

// ---------------------------------------------------------------------------------------------------------------
// Stateful mode (used by smoke/install.mjs): the server remembers databases and tables, so the installer can be run
// against it. It still is not a SQL engine, but it is strict about what the installer is most likely to get wrong:
//  * wrong user  -> ER_ACCESS_DENIED (1045), unknown database -> 1049, no database selected -> 1046
//  * CREATE/DROP TABLE, SHOW TABLES, CREATE DATABASE, USE, SELECT VERSION(), SELECT COUNT(*) FROM t are real
//  * INSERT / UPDATE / DELETE on a missing table -> 1146; unknown column names -> 1054;
//    "INSERT ... (cols) VALUES (...)" with a different number of columns and values -> 1136
//  * every statement is recorded in state.queries
// ---------------------------------------------------------------------------------------------------------------
function identsOutsideStrings(sql) {
  const out = []; let q = null;
  for (let i = 0; i < sql.length; i++) {
    const c = sql[i];
    if (q) { if (c === '\\' && q !== '`') i++; else if (c === q) q = null; continue; }
    if (c === "'" || c === '"') { q = c; continue; }
    if (c === '`') { const e = sql.indexOf('`', i + 1); if (e < 0) break; out.push(sql.slice(i + 1, e)); i = e; }
  }
  return out;
}

function tuplesOf(s) {
  const out = []; let depth = 0, q = null, cur = '';
  for (let i = 0; i < s.length; i++) {
    const c = s[i];
    if (q) { cur += c; if (c === '\\') cur += s[++i] || ''; else if (c === q) q = null; continue; }
    if (c === "'" || c === '"' || c === '`') { q = c; cur += c; continue; }
    if (c === '(') { if (depth++ === 0) { cur = ''; continue; } }
    if (c === ')') { if (--depth === 0) { out.push(cur); cur = ''; continue; } }
    if (depth > 0) cur += c;
  }
  return out;
}

function makeStateful({ opts, schema, sendRows, writeOk, writeErr }) {
  const st = { user: opts.user || null, dbs: new Map(), queries: [], connections: 0, rejected: [] };
  for (const d of (opts.databases || [])) st.dbs.set(d, new Map());

  const newTable = (name, cols) => ({ name, cols: cols ? new Set(cols.map(c => c.name.toLowerCase())) : null, autoinc: 0, rows: 0 });
  // preload = 'schema': an already installed database (every table of shema.sql, three accounts)
  st.preload = (db) => {
    const t = st.dbs.get(db) || new Map(); st.dbs.set(db, t);
    for (const [name, cols] of Object.entries(schema)) t.set(name, newTable(name, cols));
    t.get('account').rows = 3;
  };
  st.tableNames = (db) => [...(st.dbs.get(db) || new Map()).values()].map(t => t.name);
  st.queriesSince = (n) => st.queries.slice(n).map(q => q.sql);

  const err = (conn, code, message) => { st.rejected.push({ code, message }); writeErr(conn, code, message); return true; };
  const colErr = (conn, tb, idents) => {
    if (!tb.cols) return null;
    for (const id of idents) if (id.toLowerCase() !== tb.name && !tb.cols.has(id.toLowerCase())) return err(conn, 1054, `Unknown column '${id}' in 'field list'`);
    return null;
  };

  const handler = (conn, sqlRaw) => {
    st.queries.push({ db: conn.__db || null, sql: sqlRaw });
    let sql = sqlRaw.replace(/^\s+/, '').replace(/;\s*$/, '');
    const cm = /^\/\*!\d+\s*([\s\S]*?)\s*\*\/$/.exec(sql);
    if (cm) sql = cm[1];
    const kw = (/^[A-Za-z]+/.exec(sql) || [''])[0].toUpperCase();
    const tables = () => st.dbs.get(conn.__db);
    const noDb = () => (!conn.__db ? err(conn, 1046, 'No database selected') : null);
    let m;

    if (opts.failOn && opts.failOn.test(sql)) return err(conn, 1064, 'You have an error in your SQL syntax (fakedb failOn)');
    if (kw === 'SET') { writeOk(conn); return true; }
    if (kw === 'USE') {
      m = /^USE\s+`?([^`\s]+)`?/i.exec(sql);
      if (!m || !st.dbs.has(m[1])) return err(conn, 1049, `Unknown database '${m ? m[1] : ''}'`);
      conn.__db = m[1]; writeOk(conn); return true;
    }
    if ((m = /^CREATE\s+(?:DATABASE|SCHEMA)\s+(IF\s+NOT\s+EXISTS\s+)?`?([^`\s]+)`?/i.exec(sql))) {
      if (st.dbs.has(m[2])) return m[1] ? (writeOk(conn), true) : err(conn, 1007, `Can't create database '${m[2]}'; database exists`);
      st.dbs.set(m[2], new Map()); writeOk(conn, { affectedRows: 1 }); return true;
    }
    if (/^SELECT\s+VERSION\(\)/i.test(sql)) { sendRows(conn, ['VERSION()'], [['5.5.62-fakedb']], ''); return true; }
    if (/^SELECT\s+DATABASE\(\)/i.test(sql)) { sendRows(conn, ['DATABASE()'], [[conn.__db || null]], ''); return true; }
    if (/^SHOW\s+TABLES/i.test(sql)) {
      if (noDb()) return true;
      sendRows(conn, ['Tables_in_' + conn.__db], [...tables().values()].map(t => [t.name]), ''); return true;
    }
    if ((m = /^SELECT\s+COUNT\(\*\)\s+FROM\s+`?(\w+)`?\s*$/i.exec(sql))) {
      if (noDb()) return true;
      const tb = tables().get(m[1].toLowerCase());
      if (!tb) return err(conn, 1146, `Table '${conn.__db}.${m[1]}' doesn't exist`);
      sendRows(conn, ['COUNT(*)'], [[String(tb.rows)]], ''); return true;
    }
    if ((m = /^CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?`([^`]+)`/i.exec(sql))) {
      if (noDb()) return true;
      const key = m[2].toLowerCase();
      if (tables().has(key)) return m[1] ? (writeOk(conn), true) : err(conn, 1050, `Table '${m[2]}' already exists`);
      const cols = parseSchema(sql + '\n')[key] || null;
      tables().set(key, newTable(m[2], cols && cols.length ? cols : null)); writeOk(conn); return true;
    }
    if ((m = /^DROP\s+TABLE\s+(IF\s+EXISTS\s+)?([\s\S]+)$/i.exec(sql))) {
      if (noDb()) return true;
      for (const id of identsOutsideStrings(m[2])) {
        const key = id.toLowerCase();
        if (!tables().delete(key) && !m[1]) return err(conn, 1051, `Unknown table '${id}'`);
      }
      writeOk(conn); return true;
    }
    if ((m = /^ALTER\s+TABLE\s+`?(\w+)`?/i.exec(sql))) {
      if (noDb()) return true;
      const tb = tables().get(m[1].toLowerCase());
      if (!tb) return err(conn, 1146, `Table '${conn.__db}.${m[1]}' doesn't exist`);
      // key names (ADD UNIQUE KEY `id_3` (`id`)) are not columns: cut them out before checking the column list
      const clauses = sql.slice(m[0].length).replace(/(?:UNIQUE\s+|FULLTEXT\s+)?(?:KEY|INDEX)\s+`[^`]*`/gi, '');
      if (tb.cols) { const bad = identsOutsideStrings(clauses).find(id => !tb.cols.has(id.toLowerCase())); if (bad) return err(conn, 1072, `Key column '${bad}' doesn't exist in table`); }
      writeOk(conn); return true;
    }
    if ((m = /^(INSERT|REPLACE)\s+(?:IGNORE\s+)?(?:INTO\s+)?`?(\w+)`?/i.exec(sql))) {
      if (noDb()) return true;
      const tb = tables().get(m[2].toLowerCase());
      if (!tb) return err(conn, 1146, `Table '${conn.__db}.${m[2]}' doesn't exist`);
      const rest = sql.slice(m[0].length);
      let n = 1;
      if (/^\s*SET\b/i.test(rest)) {
        const assigns = splitTopLevel(rest.replace(/^\s*SET\b/i, ''));
        const names = assigns.map(a => (/^\s*`?(\w+)`?\s*=/.exec(a) || [])[1]).filter(Boolean);
        if (names.length !== assigns.length) return err(conn, 1064, 'fakedb: cannot parse the SET list');
        const r = colErr(conn, tb, names); if (r) return r;
      } else {
        const lm = /^\s*\(([^)]*)\)\s*VALUES\b/i.exec(rest);
        const vAt = findTopLevelKeyword(rest, 'VALUES');
        if (vAt < 0) return err(conn, 1064, 'fakedb: unsupported INSERT form');
        const names = lm ? identsOutsideStrings(lm[1]).length ? identsOutsideStrings(lm[1]) : lm[1].split(',').map(s => s.trim()) : null;
        if (names) { const r = colErr(conn, tb, names); if (r) return r; }
        const rows = tuplesOf(rest.slice(vAt + 6));
        const width = names ? names.length : (tb.cols ? tb.cols.size : null);
        for (let i = 0; i < rows.length; i++) {
          const w = splitTopLevel(rows[i]).length;
          if (width !== null && w !== width) return err(conn, 1136, `Column count doesn't match value count at row ${i + 1}`);
        }
        n = rows.length || 1;
      }
      const first = tb.autoinc + 1; tb.autoinc += n; tb.rows += n;
      writeOk(conn, { affectedRows: n, insertId: first }); return true;
    }
    if ((m = /^UPDATE\s+(?:LOW_PRIORITY\s+)?(?:IGNORE\s+)?`?(\w+)`?\s+SET\b/i.exec(sql))) {
      if (noDb()) return true;
      const tb = tables().get(m[1].toLowerCase());
      if (!tb) return err(conn, 1146, `Table '${conn.__db}.${m[1]}' doesn't exist`);
      const r = colErr(conn, tb, identsOutsideStrings(sql.slice(m[0].length))); if (r) return r;
      writeOk(conn, { affectedRows: 1 }); return true;
    }
    if ((m = /^DELETE\s+FROM\s+`?(\w+)`?/i.exec(sql))) {
      if (noDb()) return true;
      const tb = tables().get(m[1].toLowerCase());
      if (!tb) return err(conn, 1146, `Table '${conn.__db}.${m[1]}' doesn't exist`);
      const r = colErr(conn, tb, identsOutsideStrings(sql.slice(m[0].length))); if (r) return r;
      const had = tb.rows; tb.rows = 0; writeOk(conn, { affectedRows: had }); return true;
    }
    return false;       // everything else (SELECTs of the game pages ...) is answered by the default handler
  };

  return {
    st,
    handle: handler,
    initDb: (conn, name) => {
      if (!st.dbs.has(name)) return err(conn, 1049, `Unknown database '${name}'`);
      conn.__db = name; writeOk(conn); return true;
    },
    auth: (params) => {
      if (st.user && params.user !== st.user) return { code: 1045, message: `Access denied for user '${params.user}'@'localhost' (using password: YES)` };
      if (params.database && !st.dbs.has(params.database)) return { code: 1049, message: `Unknown database '${params.database}'` };
      return null;
    },
  };
}

function lenenc(n) {
  if (n < 251) return Buffer.from([n]);
  if (n < 65536) return Buffer.from([0xfc, n & 0xff, n >> 8]);
  const b = Buffer.alloc(9); b[0] = 0xfe; b.writeBigUInt64LE(BigInt(n), 1); return b;
}

// mysql2's writeOk() produces an OK packet without the trailing info string; mysqlnd (PHP) always negotiates
// CLIENT_SESSION_TRACK and then expects it, so the OK packet is built by hand here.
function writeOkPacket(conn, { affectedRows = 0, insertId = 0 } = {}) {
  const payload = Buffer.concat([Buffer.from([0]), lenenc(affectedRows), lenenc(insertId), Buffer.from([0x02, 0x00, 0x00, 0x00, 0x00])]);
  const header = Buffer.from([payload.length & 0xff, (payload.length >> 8) & 0xff, (payload.length >> 16) & 0xff, conn.sequenceId & 0xff]);
  conn.stream.write(Buffer.concat([header, payload]));
  conn.sequenceId = (conn.sequenceId + 1) % 256;
}

// remember: true  ->  a table that is normally empty for a healthy player (tmp = "in battle", ban, ...) answers SELECTs with rows after
//                      an INSERT into it, and empty again after a DELETE. smoke/click.mjs uses it so that "attack the mob" (ok.php
//                      INSERTs into tmp) is followed by a battle.php that finds the fight. Off in the full run: that server is shared
//                      by all pages, so one page's INSERT would change what every other page sees.
export function startFakeDb({ schemaPath, port = 0, logFile = null, host = '127.0.0.1', debugPackets = false, profile = 'dummy', stateful = null, remember = false }) {
  const schema = parseSchema(readFileSync(schemaPath, 'utf8'));
  const extraPath = join(dirname(schemaPath), 'shema_extra.sql');
  if (!stateful && existsSync(extraPath)) Object.assign(schema, parseSchema(readFileSync(extraPath, 'utf8')));
  const stats = { queries: 0, unknownTables: {}, selects: 0, writes: 0 };
  let insertId = 100;
  const written = new Set();       // remember mode: normally-empty tables that have a row now
  const server = mysql.createServer();

  const colDef = (name, table = '') => ({
    catalog: 'def', schema: 'siska', table, orgTable: table, name, orgName: name,
    characterSet: 33, columnLength: 255, columnType: 253, flags: 0, decimals: 0,
  });

  function sendRows(conn, names, rows, table) {
    conn.writeColumns(names.map(n => colDef(n, table)));
    for (const r of rows) conn.writeTextRow(r);
    conn.writeEof();
  }

  function handle(conn, sqlRaw) {
    stats.queries++;
    if (sf && sf.handle(conn, sqlRaw)) return;
    if (logFile) appendFileSync(logFile, sqlRaw.replace(/\s+/g, ' ').slice(0, 400) + '\n');
    const sql = sqlRaw.replace(/\/\*[\s\S]*?\*\//g, ' ').replace(/^\s+/, '');
    const kw = (/^[A-Za-z]+/.exec(sql) || [''])[0].toUpperCase();

    if (kw === 'SELECT') return handleSelect(conn, sql);
    if (kw === 'INSERT' || kw === 'REPLACE') {
      stats.writes++;
      const tm = /^(?:INSERT|REPLACE)\s+(?:IGNORE\s+)?(?:INTO\s+)?(?:`?\w+`?\.)?`?(\w+)`?/i.exec(sql);
      if (tm && !schema[tm[1].toLowerCase()]) return unknownTable(conn, tm[1]);
      if (remember && tm) written.add(tm[1].toLowerCase());
      return writeOkPacket(conn, { affectedRows: 1, insertId: ++insertId });
    }
    if (kw === 'UPDATE') {
      stats.writes++;
      const tm = /^UPDATE\s+(?:LOW_PRIORITY\s+)?(?:IGNORE\s+)?(?:`?\w+`?\.)?`?(\w+)`?/i.exec(sql);
      if (tm && !schema[tm[1].toLowerCase()]) return unknownTable(conn, tm[1]);
      return writeOkPacket(conn, { affectedRows: 1 });
    }
    if (kw === 'DELETE') {
      stats.writes++;
      const tm = /^DELETE\s+FROM\s+(?:`?\w+`?\.)?`?(\w+)`?/i.exec(sql);
      if (tm && !schema[tm[1].toLowerCase()]) return unknownTable(conn, tm[1]);
      if (remember && tm) written.delete(tm[1].toLowerCase());
      return writeOkPacket(conn, { affectedRows: 1 });
    }
    if (kw === 'SHOW' || kw === 'DESCRIBE' || kw === 'EXPLAIN') return sendRows(conn, ['Value'], [['1']], '');
    return writeOkPacket(conn, { affectedRows: 0 });   // SET, USE, CREATE, ALTER, DROP, LOCK, BEGIN...
  }

  function unknownTable(conn, t) {
    stats.unknownTables[t] = (stats.unknownTables[t] || 0) + 1;
    conn.writeError({ code: 1146, message: `Table 'siska.${t}' doesn't exist` });
  }

  function handleSelect(conn, sql) {
    stats.selects++;
    const body = sql.replace(/;\s*$/, '');
    const fromAt = findTopLevelKeyword(body, 'FROM');
    const selectList = body.slice(6, fromAt >= 0 ? fromAt : undefined).replace(/^\s*(?:DISTINCT|ALL|SQL_CALC_FOUND_ROWS)\s+/i, '');
    const rest = fromAt >= 0 ? body.slice(fromAt + 4) : '';
    let table = null, tableName = '';
    if (fromAt >= 0) {
      const tm = /^\s*\(?\s*(?:`?(\w+)`?\.)?`?(\w+)`?/.exec(rest);
      if (tm) {
        tableName = tm[2].toLowerCase();
        if (!schema[tableName]) {
          // a subquery / derived table / unknown table
          if (/^\s*\(\s*select/i.test(rest)) tableName = ''; else return unknownTable(conn, tm[2]);
        }
        table = tableName ? schema[tableName] : null;
      }
    }
    const items = splitTopLevel(selectList).map(s => s.trim()).filter(Boolean);
    const whereAt = findTopLevelKeyword(rest, 'WHERE');
    const where = whereAt >= 0 ? rest.slice(whereAt + 5) : '';
    const limitM = /\bLIMIT\s+(\d+)(?:\s*,\s*(\d+))?/i.exec(rest);
    const limit = limitM ? (limitM[2] !== undefined ? Number(limitM[2]) : Number(limitM[1])) : Infinity;
    const isAggregate = items.length > 0 && items.every(it => /^(?:count|sum|max|min|avg)\s*\(/i.test(it) || /^\(?\s*select/i.test(it)) && !/\bGROUP\s+BY\b/i.test(rest);
    const emptyCmp = /(?:=|!=|<>|>|<|LIKE)\s*(?:'')/i.test(where) || /\blimit\s+0\b/i.test(rest);
    let nrows;
    if (!fromAt || fromAt < 0) nrows = 1;
    else if (emptyCmp) nrows = 0;
    else if (EMPTY_TABLES.has(tableName) && !(remember && written.has(tableName))) nrows = 0;
    else if (profile === 'empty' && tableName !== 'account' && tableName !== 'users') nrows = 0;
    else nrows = Math.min(2, limit);

    const names = []; const getters = [];
    for (const it of items) {
      if (/^(?:`?\w+`?\.)?\*$/.test(it)) {
        for (const c of (table || [])) { names.push(c.name); getters.push((ri) => seedValue(tableName, c, ri, profile)); }
        if (!table) { names.push('1'); getters.push(() => '1'); }
        continue;
      }
      const am = /\s+(?:AS\s+)?`?([A-Za-z_][A-Za-z0-9_]*)`?\s*$/i.exec(it);
      const simple = /^(?:`?\w+`?\.)?`?(\w+)`?$/.exec(it);
      let name = it;
      if (simple) name = simple[1];
      else if (am && /\bAS\s+`?\w+`?\s*$/i.test(it)) name = am[1];
      else if (/^(?:count|sum|max|min|avg)\s*\(/i.test(it)) name = it.replace(/\s+/g, ' ');
      names.push(name);
      const col = table && simple ? table.find(c => c.name.toLowerCase() === simple[1].toLowerCase()) : null;
      if (col) getters.push((ri) => seedValue(tableName, col, ri, profile));
      else if (/^count\s*\(/i.test(it)) getters.push(() => (emptyCmp || EMPTY_TABLES.has(tableName) || profile !== 'dummy' && !(tableName === 'account' || tableName === 'users') ? '0' : '2'));
      else if (/^(?:sum|max|min|avg)\s*\(/i.test(it)) getters.push(() => (emptyCmp || EMPTY_TABLES.has(tableName) ? null : '10'));
      else getters.push(() => '1');
    }
    if (isAggregate) nrows = 1;     // aggregates without GROUP BY always return exactly one row
    const rows = [];
    for (let ri = 0; ri < nrows; ri++) rows.push(getters.map(g => g(ri)));
    return sendRows(conn, names.length ? names : ['1'], rows.length || names.length === 0 ? rows : [], tableName);
  }

  const writeErr = (conn, code, message) => conn.writeError({ code, message });
  const sf = stateful ? makeStateful({ opts: stateful, schema, sendRows, writeOk: writeOkPacket, writeErr }) : null;

  server.on('connection', conn => {
    if (sf) sf.st.connections++;
    if (debugPackets) {
      const origWrite = conn.stream.write.bind(conn.stream);
      conn.stream.write = (chunk, ...rest) => {
        console.log('S->C wire ' + Buffer.from(chunk).length + 'B: ' + Buffer.from(chunk).subarray(0, 60).toString('hex'));
        return origWrite(chunk, ...rest);
      };
    }
    conn.serverHandshake({
      protocolVersion: 10, serverVersion: (stateful && stateful.serverVersion) || '8.0.0-fakedb', connectionId: 1, statusFlags: 2,
      // minimal capability set (no SESSION_TRACK/DEPRECATE_EOF/SSL/compression): mysqlnd is stricter than mysql2's own client
      characterSet: 33, capabilityFlags: 1 | 2 | 4 | 8 | 512 | 8192 | 32768 | 524288,
      // after the handshake's OK packet the next client command starts with sequence id 0 again
      authCallback: (params, cb) => {
        const bad = sf ? sf.auth(params) : null;
        if (bad) { sf.st.rejected.push(bad); return cb(null, bad); }
        if (params.database) conn.__db = params.database;
        cb(null); conn.sequenceId = 0;
      },
    });
    // mysql2's server side never resets the packet sequence between commands: the reply to every
    // command has to start with sequence id 1, otherwise the client (mysqlnd) drops the connection.
    // All packets of one reply are sent in a single TCP write (cork/uncork): the php-wasm socket layer used by the
    // smoke test crashes ("null function or function signature mismatch") when mysqlnd has to wait for more data.
    conn.stream.setNoDelay(true);
    const reply = fn => (...args) => {
      conn.sequenceId = 1;
      conn.stream.cork();
      try { fn(...args); } catch (e) { conn.sequenceId = 1; conn.writeError({ code: 1064, message: 'fakedb: ' + e.message }); }
      conn.stream.uncork();
      conn.sequenceId = 0;
    };
    conn.on('query', reply(sql => handle(conn, sql)));
    conn.on('stmt_prepare', reply(sql => handle(conn, sql)));   // mysql2 routes "SET ..." here
    conn.on('init_db', reply(name => (sf ? sf.initDb(conn, name) : writeOkPacket(conn))));
    conn.on('ping', reply(() => writeOkPacket(conn)));
    conn.on('quit', () => conn.stream.end());
    conn.on('error', () => {});
  });

  return new Promise(resolve => {
    server.listen(port, host, () => resolve({ port: server._server.address().port, stats, schema, state: sf ? sf.st : null, close: () => server.close() }));
  });
}

// run directly: node fakedb.mjs <schema.sql> [port]   (handy for manual experiments)
if (import.meta.url === `file://${process.argv[1]}`) {
  const db = await startFakeDb({ schemaPath: process.argv[2], port: Number(process.argv[3] || 3307) });
  console.log('fakedb listening on 127.0.0.1:' + db.port);
}
