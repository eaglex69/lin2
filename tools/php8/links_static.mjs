// Static link audit: reads the PHP source (no PHP, no database, no node_modules needed) and checks that every link target
// written in it - href="...", action="...", src="...php|js", header("Location: ...") - is a file (or a directory with an index
// file) of the site. It finds the links that run.mjs --links cannot reach because the page branch that prints them is never
// rendered with dummy data (admin screens, rare states).
//
//   node links_static.mjs [--legacy]          ROOT=<dir> audits another copy of the site (e.g. `git archive <sha>`)
//
// A target WITHOUT an extension that is not a directory (<a href="ok1231">, header("Location: ../enter")) is reported as a "pretty
// URL": it works only if the web server rewrites it, and a checkout carries no server configuration (.htaccess / nginx.conf).
// Exit code 1 when a link in a live file points nowhere and is not on the known-gaps list below.
import { readdirSync, readFileSync, statSync, existsSync } from 'node:fs';
import { dirname, join, normalize, relative, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const REPO = process.env.ROOT ? resolve(process.env.ROOT) : resolve(HERE, '..', '..');
const SHOW_LEGACY = process.argv.includes('--legacy');

// pages that the game links to but that are not in the repository (the original upload lacks them); nothing can be fixed by editing a link
const KNOWN_MISSING = {
  'rydnik.php': 'mine ("Рудник")', 'towers.php': 'towers', 'to_towers.php': 'towers', 'asshome.php': "assassins' home", 'istok.php': 'spring',
  'library.php': 'library', 'aura.php': 'auras', 'clan_shopjuvilir.php': "clan jeweller's shop",
};
// legacy / dead files (see PHP8_MIGRATION.md): their links do not matter
const LEGACY = new Set(['gk.php', 'uslugi_exp.php', 'teleport.php', 'delete_usr.php', 'coin_sms.php', 'sab.php', 'log.php', 'pnews.php', 'chat2.php', 'forum/down.php', 'inc/ini.php', 'mailadmin.php']);
// user content / binaries that are not part of the repository on purpose
const USER_CONTENT = /^(pic|foto|shmot|files|apps|smiles)\//;

const files = [];
(function walk(dir) {
  for (const name of readdirSync(dir).sort()) {
    const p = join(dir, name);
    if (statSync(p).isDirectory()) { if (!['.git', 'node_modules', 'tools'].includes(name)) walk(p); }
    else if (name.endsWith('.php')) files.push(p);
  }
})(REPO);

const ATTR = /\b(href|action|src)\s*=\s*\\?(["'])([^"'\\]*)\\?\2/gi;
const LOC = /Location\s*:\s*([^"'\\;\r\n]*)/gi;
const INTERP = /\{\$[^}]*\}|\$\w+(?:\[[^\]]*\]|->\w+)*/g;

function isFileOrIndexDir(p) {
  if (!existsSync(p)) return false;
  const st = statSync(p);
  return st.isFile() || existsSync(join(p, 'index.php')) || existsSync(join(p, 'index.html'));
}

const found = new Map();     // key -> { kind, target, resolved, legacy, uses: [file:line] }
let nChecked = 0;
for (const file of files) {
  const rel = relative(REPO, file).split(sep).join('/');
  const legacy = LEGACY.has(rel);
  const baseDir = rel.startsWith('inc/') ? REPO : dirname(file);       // inc/*.php is included by pages of the root
  readFileSync(file).toString('latin1').replace(/\r/g, '').split('\n').forEach((line, i) => {
    if (/^\s*(\/\/|#|\*|\/\*)/.test(line)) return;
    const cands = [];
    for (const m of line.matchAll(ATTR)) cands.push([m[1].toLowerCase(), m[3]]);
    for (const m of line.matchAll(LOC)) cands.push(['Location', m[1]]);
    for (let [kind, url] of cands) {
      url = url.trim().replace(/&amp;/g, '&');
      if (!url || url[0] === '#' || /^(?:[a-z][a-z0-9+.-]*:|\/\/)/i.test(url)) continue;     // external, javascript:, mailto: ...
      const path = url.split(/[?#]/)[0];
      if (!path || path[0] === '$' || path[0] === '{' || path.includes('<?') || /^\.\s*\$/.test(path)) continue;   // "?x=1" = the same page; a variable (or '.$x.' of a PHP concatenation) = not checkable
      if (kind === 'src' && !/\.(php|js)(\?|$)/i.test(url) && !/\.(php|js)$/i.test(path)) continue;   // images
      const abs = path[0] === '/' ? normalize(join(REPO, path)) : normalize(join(baseDir, path));
      if (abs !== REPO && !abs.startsWith(REPO + sep)) continue;                              // leaves the site
      const relTarget = relative(REPO, abs).split(sep).join('/');
      if (USER_CONTENT.test(relTarget)) continue;
      nChecked++;
      const interpolated = INTERP.test(path); INTERP.lastIndex = 0;
      const last = path.split('/').pop();
      let ok, why;
      if (interpolated) {
        const dirPart = dirname(abs), pattern = path.split('/').pop().replace(INTERP, '\u0000');
        if (!last.includes('.')) { ok = false; why = 'pretty URL (a variable in a name without extension)'; }
        else {
          const re = new RegExp('^' + pattern.split('\u0000').map(s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('.+') + '$');
          ok = existsSync(dirPart) && readdirSync(dirPart).some(n => re.test(n));
          why = 'no file matches';
        }
      } else if (!last.includes('.') && last !== '' && !path.endsWith('/')) {
        ok = isFileOrIndexDir(abs); why = 'pretty URL (no extension, not a directory)';
      } else {
        ok = isFileOrIndexDir(abs); why = 'no such file';
      }
      if (ok) continue;
      const key = kind + ' ' + relTarget.replace(INTERP, '$') + ' ' + legacy;
      const e = found.get(key) || { kind, target: relTarget, why, legacy, uses: [] };
      e.uses.push(`${rel}:${i + 1}`);
      found.set(key, e);
    }
  });
}

const live = [...found.values()].filter(e => !e.legacy);
const bad = live.filter(e => !(e.target in KNOWN_MISSING));
const known = live.filter(e => e.target in KNOWN_MISSING);
const fmt = e => `  ${String(e.uses.length).padStart(3)}x  ${e.kind.padEnd(8)} ${e.target.padEnd(30)} ${e.why}\n         e.g. ${e.uses[0]}${e.uses.length > 1 ? `  (+${e.uses.length - 1} more)` : ''}`;
console.log(`static link audit of ${files.length} PHP files in ${REPO}: ${nChecked} link targets checked\n`);
console.log(`=== links to files that do not exist (${bad.length}) ===\n` + (bad.length ? bad.map(fmt).join('\n') : '  none'));
console.log(`\n=== known gaps: pages that are not in the repository (${known.length}) ===\n` + (known.length ? known.map(e => fmt(e) + `   [${KNOWN_MISSING[e.target]}]`).join('\n') : '  none'));
const leg = [...found.values()].filter(e => e.legacy);
if (SHOW_LEGACY) console.log(`\n=== legacy / dead files (${leg.length}) ===\n` + (leg.length ? leg.map(fmt).join('\n') : '  none'));
else if (leg.length) console.log(`\n(${leg.length} more in legacy / dead files, see --legacy)`);
process.exit(bad.length ? 1 : 0);
