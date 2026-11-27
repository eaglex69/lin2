// A tiny fake MySQL server for smoke-testing the PHP code without a real database.
//  * speaks the MySQL wire protocol (via the server side of mysql2), accepts any credentials
//  * knows every table/column from shema.sql and answers SELECTs with plausible dummy rows
//  * INSERT/UPDATE/DELETE/SET/... simply succeed; queries on unknown tables fail with ER_NO_SUCH_TABLE
//  * a WHERE clause that compares something with an empty string ('') returns an empty set
//    (that is how "guest / not found" situations are simulated)
// It is NOT a SQL engine: it is only meant to let PHP pages run far enough to expose runtime errors.
import mysql from 'mysql2';
import { readFileSync, appendFileSync } from 'node:fs';

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

export function startFakeDb({ schemaPath, port = 0, logFile = null, host = '127.0.0.1', debugPackets = false, profile = 'dummy' }) {
  const schema = parseSchema(readFileSync(schemaPath, 'utf8'));
  const stats = { queries: 0, unknownTables: {}, selects: 0, writes: 0 };
  let insertId = 100;
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
    if (logFile) appendFileSync(logFile, sqlRaw.replace(/\s+/g, ' ').slice(0, 400) + '\n');
    const sql = sqlRaw.replace(/\/\*[\s\S]*?\*\//g, ' ').replace(/^\s+/, '');
    const kw = (/^[A-Za-z]+/.exec(sql) || [''])[0].toUpperCase();

    if (kw === 'SELECT') return handleSelect(conn, sql);
    if (kw === 'INSERT' || kw === 'REPLACE') {
      stats.writes++;
      const tm = /^(?:INSERT|REPLACE)\s+(?:IGNORE\s+)?(?:INTO\s+)?(?:`?\w+`?\.)?`?(\w+)`?/i.exec(sql);
      if (tm && !schema[tm[1].toLowerCase()]) return unknownTable(conn, tm[1]);
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
    else if (EMPTY_TABLES.has(tableName)) nrows = 0;
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

  server.on('connection', conn => {
    if (debugPackets) {
      const origWrite = conn.stream.write.bind(conn.stream);
      conn.stream.write = (chunk, ...rest) => {
        console.log('S->C wire ' + Buffer.from(chunk).length + 'B: ' + Buffer.from(chunk).subarray(0, 60).toString('hex'));
        return origWrite(chunk, ...rest);
      };
    }
    conn.serverHandshake({
      protocolVersion: 10, serverVersion: '8.0.0-fakedb', connectionId: 1, statusFlags: 2,
      // minimal capability set (no SESSION_TRACK/DEPRECATE_EOF/SSL/compression): mysqlnd is stricter than mysql2's own client
      characterSet: 33, capabilityFlags: 1 | 2 | 4 | 8 | 512 | 8192 | 32768 | 524288,
      // after the handshake's OK packet the next client command starts with sequence id 0 again
      authCallback: (params, cb) => { cb(null); conn.sequenceId = 0; },
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
    conn.on('init_db', reply(() => writeOkPacket(conn)));
    conn.on('ping', reply(() => writeOkPacket(conn)));
    conn.on('quit', () => conn.stream.end());
    conn.on('error', () => {});
  });

  return new Promise(resolve => {
    server.listen(port, host, () => resolve({ port: server._server.address().port, stats, schema, close: () => server.close() }));
  });
}

// run directly: node fakedb.mjs <schema.sql> [port]   (handy for manual experiments)
if (import.meta.url === `file://${process.argv[1]}`) {
  const db = await startFakeDb({ schemaPath: process.argv[2], port: Number(process.argv[3] || 3307) });
  console.log('fakedb listening on 127.0.0.1:' + db.port);
}
