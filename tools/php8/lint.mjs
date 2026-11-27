// usage: node lint.mjs <phpVersion> [files...]      (default: every *.php in the repository)
//   phpVersion: 5.2 | 7.4 | 8.0 | 8.1 | 8.2 | 8.3 | 8.4 | 8.5     env: CONC=<parallel runs>, ROOT=<dir>
import { startPhp, REPO } from './common.mjs';
import { readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const args = process.argv.slice(2);
const ver = args.shift() || '8.3';
const root = REPO;
const conc = Number(process.env.CONC || 3);

function walk(d, out = []) {
  for (const n of readdirSync(d)) {
    if (n === '.git' || n === 'node_modules') continue;
    const p = join(d, n); const s = statSync(p);
    if (relative(root, p) === join('tools', 'php8')) continue;
    if (s.isDirectory()) walk(p, out);
    else if (/\.php$/i.test(n)) out.push(p);
  }
  return out;
}
const files = args.length ? args.map(f => join(root, f)) : walk(root).sort();

async function lintOne(file) {
  const rel = relative(root, file);
  const php = await startPhp(ver, { '/work': root });
  try {
    const r = await php.cli(['php', '-d', 'display_errors=1', '-d', 'error_reporting=-1', '-l', '/work/' + rel]);
    const out = (await r.stdoutText).trim();
    const err = (await r.stderrText).trim();
    const code = await r.exitCode;
    return { rel, code, out: (out + '\n' + err).trim() };
  } catch (e) {
    return { rel, code: -1, out: 'EXC ' + e.message };
  } finally {
    try { php.exit(); } catch {}
  }
}

let idx = 0, bad = 0, done = 0;
const results = [];
async function worker() {
  while (idx < files.length) {
    const f = files[idx++];
    const res = await lintOne(f);
    done++;
    if (res.code !== 0 || !/^No syntax errors/m.test(res.out) || /Deprecated|Warning|Fatal|Parse error/.test(res.out)) {
      bad++;
      results.push(res);
    }
  }
}
await Promise.all(Array.from({ length: conc }, worker));
results.sort((a, b) => a.rel.localeCompare(b.rel));
for (const r of results) {
  const first = r.out.split('\n').filter(l => !/^Errors parsing/.test(l)).slice(0, 3).join(' | ');
  console.log(`FAIL ${r.rel}: ${first}`);
}
console.log(`PHP ${ver}: checked ${done} files, ${bad} with problems`);
process.exit(bad ? 1 : 0);
