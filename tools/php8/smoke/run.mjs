// Smoke test: runs every page of the game on PHP 8 (WebAssembly) against a fake MySQL server (smoke/fakedb.mjs).
//
//   node smoke/run.mjs [--php 8.3] [--only <regex on file name>] [--max <scenarios per file>] [--prod] [--out smoke/out]
//
// The repository is copied to a temp directory first (pages write files), the fake DB is started in this process and one
// worker process (smoke/worker.mjs) per page runs the scenarios. A page is called as a guest and as a logged-in
// player, with the GET parameters / switch() case labels found in its source, and with a POST body built from the
// $_POST keys it reads. Results: smoke/out/report.json and a summary on stdout.
//
// This is NOT a functional test (the fake DB returns dummy rows) - it finds fatal errors (undefined functions,
// TypeError, ValueError, DivisionByZeroError, ...) and PHP 8 warnings on realistic code paths.
import { startFakeDb } from './fakedb.mjs';
import { REPO, HERE } from '../common.mjs';
import { spawn } from 'node:child_process';
import { cpSync, mkdirSync, readdirSync, readFileSync, writeFileSync, rmSync, existsSync, statSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { tmpdir } from 'node:os';

const args = process.argv.slice(2);
const opt = (name, def) => { const i = args.indexOf('--' + name); return i >= 0 ? (args[i + 1] && !args[i + 1].startsWith('--') ? args[i + 1] : true) : def; };
const PHP = String(opt('php', '8.3'));
const ONLY = opt('only', null) ? new RegExp(String(opt('only'))) : null;
const MAX = Number(opt('max', 30));
const PROD = !!opt('prod', false);          // production mode: no LIN2_DEBUG (error_reporting without warnings, exception handler on)
const OUT = String(opt('out', join(HERE, 'smoke', 'out')));
const CONC = Number(process.env.CONC || 2);
const WORKER_TIMEOUT_MS = Number(process.env.WORKER_TIMEOUT || 120000);
const PROFILE = String(opt('profile', 'dummy'));       // fake DB data: dummy | zero (all values 0) | empty (no rows)
const GARBAGE = opt('garbage', null);                    // replace the placeholder value 1 of GET/POST params, e.g. --garbage abc  (use --garbage "" for empty)
const NO_DIRS = !!opt('no-dirs', false);                 // do not create the user-content directories (pic/smiles, pic/clan, foto...): a checkout without images
const COOKIES = 'nick=Tester; password=abc';

mkdirSync(OUT, { recursive: true });
const TMP = join(tmpdir(), 'lin2_smoke_' + process.pid);
cpSync(REPO, TMP, { recursive: true, filter: p => !/[\\/](\.git|node_modules)([\\/]|$)/.test(p) && !/[\\/]tools[\\/]php8([\\/]|$)/.test(p) });

// directories with user content that are not part of the repository but exist on the real server
for (const [dir, names] of [['pic/smiles', ['1.gif', '2.gif', '3.gif']], ['pic/clan', ['a.gif', 'b.gif', 'c.gif']], ['pic/clan_logo', ['a.gif', 'b.gif', 'c.gif']], ['foto', ['a.jpg', 'b.jpg', 'c.jpg']]]) {
  if (!NO_DIRS && !existsSync(join(TMP, dir))) { mkdirSync(join(TMP, dir), { recursive: true }); for (const n of names) writeFileSync(join(TMP, dir, n), 'GIF89a'); }
}

const db = await startFakeDb({ schemaPath: join(REPO, 'shema.sql'), logFile: join(OUT, 'queries.log'), profile: PROFILE });
writeFileSync(join(OUT, 'queries.log'), '');

// ---------------------------------------------------------------------------------------------------------------
// pages and scenarios
// ---------------------------------------------------------------------------------------------------------------
const DIRS = ['', 'forum', 'gm', 'total', 'drink', 'kcaptcha', 'bonus'];
const SKIP = new Set(['php.php', 'gzip_pr.php', 'install.php']);   // phpinfo; the installer has its own test (smoke/install.mjs)
const files = [];
for (const d of DIRS) {
  const dir = join(TMP, d);
  if (!existsSync(dir)) continue;
  for (const f of readdirSync(dir).sort()) {
    if (!f.endsWith('.php') || SKIP.has(f)) continue;
    const rel = d ? d + '/' + f : f;
    if (statSync(join(dir, f)).isFile() && (!ONLY || ONLY.test(rel))) files.push(rel);
  }
}

function scenariosFor(rel) {
  const src = readFileSync(join(TMP, rel)).toString('latin1');
  const getKeys = new Set([...src.matchAll(/\$_GET\[\s*['"]?(\w+)['"]?\s*\]/g)].map(m => m[1]));
  const postKeys = new Set([...src.matchAll(/\$_POST\[\s*['"]?(\w+)['"]?\s*\]/g)].map(m => m[1]));
  const switches = new Map();      // key -> labels
  for (const m of src.matchAll(/switch\s*\(\s*\$_GET\[\s*['"]?(\w+)['"]?\s*\]\s*\)\s*\{([\s\S]*?)(?=\bswitch\s*\(|$)/g)) {
    const labels = switches.get(m[1]) || new Set();
    for (const c of m[2].matchAll(/case\s+(?:'([^']*)'|"([^"]*)"|(\d+))\s*:/g)) labels.add(c[1] ?? c[2] ?? c[3]);
    switches.set(m[1], labels);
  }
  const compared = new Map();      // key -> values from `$_GET['k'] == 'v'`
  for (const m of src.matchAll(/\$_GET\[\s*['"]?(\w+)['"]?\s*\]\s*={2,3}\s*(?:'([^']*)'|"([^"]*)"|(\d+))/g)) {
    const s = compared.get(m[1]) || new Set(); s.add(m[2] ?? m[3] ?? m[4]); compared.set(m[1], s);
  }
  const others = k => [...getKeys].filter(x => x !== k && !switches.has(x)).slice(0, 8).map(x => x + '=1').join('&');
  const enc = s => encodeURIComponent(s);
  const sc = [{ name: 'guest', query: '' }, { name: 'player', query: '', cookies: COOKIES }];
  const extra = others('');
  if (extra) sc.push({ name: 'player+params', query: extra, cookies: COOKIES });
  const seen = new Set(['', extra]);
  const add = (name, q, body) => { const key = q + '|' + (body ?? ''); if (seen.has(key) || sc.length >= MAX) return; seen.add(key); sc.push({ name, query: q, body, cookies: COOKIES }); };
  for (const [k, labels] of switches) for (const l of [...labels].slice(0, 14)) { const o = others(k); add(`${k}=${l}`, `${k}=${enc(l)}` + (o ? '&' + o : '')); }
  for (const [k, vals] of compared) for (const v of [...vals].slice(0, 4)) { const o = others(k); add(`${k}=${v}`, `${k}=${enc(v)}` + (o ? '&' + o : '')); }
  for (const k of getKeys) if (!switches.has(k) && !compared.has(k)) add(`?${k}`, `${k}=1`);
  if (postKeys.size) {
    const body = [...postKeys].slice(0, 20).map(k => `${encodeURIComponent(k)}=1`).join('&');
    add('POST', extra, body);
    for (const [k, labels] of switches) for (const l of [...labels].slice(0, 4)) add(`POST ${k}=${l}`, `${k}=${enc(l)}` + (extra ? '&' + extra : ''), body);
  }
  if (GARBAGE !== null) {
    const g = GARBAGE === true ? '' : String(GARBAGE);
    const sub = q => q === undefined ? q : q.replace(/=1(?=&|$)/g, '=' + encodeURIComponent(g));
    for (const x of sc) { x.query = sub(x.query); x.body = sub(x.body); }
  }
  return sc;
}

// ---------------------------------------------------------------------------------------------------------------
// run workers
// ---------------------------------------------------------------------------------------------------------------
const env = { LIN2_DB_HOST: '127.0.0.1:' + db.port, LIN2_DB_USER: 'smoke', LIN2_DB_PASS: 'smoke', LIN2_DB_NAME: 'siska' };
if (!PROD) env.LIN2_DEBUG = '1';
const results = [];
let next = 0, running = 0, finished = 0;

function runWorker(file) {
  return new Promise(resolve => {
    const scenarios = scenariosFor(file);
    const jobPath = join(OUT, 'job_' + file.replace(/[\\/]/g, '__') + '.json');
    writeFileSync(jobPath, JSON.stringify({ php: PHP, root: TMP, file, env, scenarios }));
    const child = spawn('node', [join(HERE, 'smoke', 'worker.mjs'), jobPath], { cwd: HERE, stdio: ['ignore', 'pipe', 'pipe'] });
    const res = { file, scenarios: [], crashed: null };
    let buf = '', lastStart = null, timer = setTimeout(() => { res.crashed = 'timeout (hang) in scenario ' + lastStart; child.kill('SIGKILL'); }, WORKER_TIMEOUT_MS);
    let stderr = '';
    child.stdout.on('data', d => {
      buf += d; let i;
      while ((i = buf.indexOf('\n')) >= 0) {
        const line = buf.slice(0, i); buf = buf.slice(i + 1);
        try { const o = JSON.parse(line); if (o.start) lastStart = o.start; else if (o.done) res.scenarios.push(o); } catch { /* ignore garbage */ }
      }
    });
    child.stderr.on('data', d => { stderr += d; if (stderr.length > 5000) stderr = stderr.slice(-5000); });
    child.on('close', code => {
      clearTimeout(timer);
      if (!res.crashed && res.scenarios.length < scenarios.length) res.crashed = `worker exited (code ${code}) after ${res.scenarios.length}/${scenarios.length} scenarios; last: ${lastStart}; ${stderr.trim().split('\n').slice(-3).join(' | ').slice(0, 400)}`;
      res.planned = scenarios.length;
      resolve(res);
    });
  });
}

await new Promise(resolve => {
  const pump = () => {
    while (running < CONC && next < files.length) {
      const f = files[next++]; running++;
      runWorker(f).then(r => { results.push(r); running--; finished++; process.stderr.write(`\r[${finished}/${files.length}] ${f}                         `); pump(); });
    }
    if (running === 0 && next >= files.length) resolve();
  };
  pump();
});
process.stderr.write('\n');

// ---------------------------------------------------------------------------------------------------------------
// report
// ---------------------------------------------------------------------------------------------------------------
const NOISE = /^(Undefined variable|Undefined array key|Undefined property|Trying to access array offset|Attempt to read property)/;
const fatals = new Map(), crashes = [], warns = new Map(), deprecs = new Map(), restricted = [];
let nScen = 0;
for (const r of results) {
  if (r.crashed) crashes.push({ file: r.file, why: r.crashed });
  if (r.scenarios.length && r.scenarios.every(s => s.status === 'restricted')) restricted.push(r.file);
  for (const s of r.scenarios) {
    nScen++;
    for (const d of s.diags) {
      const key = `${d.file}:${d.line}  ${d.message}`;
      const bucket = d.level === 'Fatal error' || d.level === 'Parse error' ? fatals : d.level === 'Deprecated' ? deprecs : (d.level === 'Warning' && !NOISE.test(d.message)) || d.level === 'Notice' ? warns : null;
      if (!bucket) continue;
      const e = bucket.get(key) || { n: 0, pages: new Set(), example: r.file + ' [' + s.done + ']' };
      e.n++; e.pages.add(r.file); bucket.set(key, e);
    }
  }
}
const fmt = (m, limit = 60) => [...m].sort((a, b) => b[1].n - a[1].n).slice(0, limit).map(([k, v]) => `  ${String(v.n).padStart(4)}x  ${k}\n          e.g. ${v.example}${v.pages.size > 1 ? `  (+${v.pages.size - 1} more pages)` : ''}`).join('\n');
const lines = [];
lines.push(`PHP ${PHP}${PROD ? ' (production mode)' : ' (debug mode)'}, data profile: ${PROFILE}${GARBAGE !== null ? ', params=' + JSON.stringify(GARBAGE === true ? '' : GARBAGE) : ''}: ${files.length} pages, ${nScen} requests, fake DB handled ${db.stats.queries} queries`);
lines.push(`pages that die with "restricted access" (include-only files): ${restricted.length}`);
lines.push(`\n=== FATAL ERRORS (${fatals.size} distinct) ===\n` + fmt(fatals, 200));
lines.push(`\n=== worker crashes / hangs (${crashes.length}) ===\n` + crashes.map(c => `  ${c.file}: ${c.why}`).join('\n'));
lines.push(`\n=== other warnings, without "undefined variable/key" noise (${warns.size} distinct) ===\n` + fmt(warns, 60));
lines.push(`\n=== deprecations (${deprecs.size} distinct) ===\n` + fmt(deprecs, 30));
lines.push(`\n=== queries on tables that are not in shema.sql ===\n  ` + (JSON.stringify(db.stats.unknownTables)));
const text = lines.join('\n');
writeFileSync(join(OUT, 'report.txt'), text + '\n');
writeFileSync(join(OUT, 'report.json'), JSON.stringify({ php: PHP, prod: PROD, results, stats: db.stats }, null, 1));
console.log(text);
db.close();
rmSync(TMP, { recursive: true, force: true });
process.exit(0);
