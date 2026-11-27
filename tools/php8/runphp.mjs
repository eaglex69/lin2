// usage: node runphp.mjs <phpVersion> <script.php> [args...]
//   The repository is mounted at /work, tools/php8/scripts at /tools. Examples:
//     node runphp.mjs 8.3 /tools/audit.php /work
//     OUT=report.txt node runphp.mjs 8.3 /tools/globals_flow.php /work
import { startPhp, HERE, REPO } from './common.mjs';
import { writeFileSync } from 'node:fs';
import { join } from 'node:path';

const [ver, script, ...rest] = process.argv.slice(2);
const mounts = { '/work': REPO, '/tools': join(HERE, 'scripts') };
if (process.env.EXTRA) mounts['/extra'] = process.env.EXTRA;
const php = await startPhp(ver, mounts);
const r = await php.cli(['php', '-d', 'display_errors=stderr', '-d', 'error_reporting=-1', '-d', 'memory_limit=1G', script, ...rest],
                        { cwd: process.env.CWD || '/work' });
const out = await r.stdoutText; const err = await r.stderrText; const code = await r.exitCode;
if (process.env.OUT) writeFileSync(process.env.OUT, out); else process.stdout.write(out);
if (err.trim()) process.stderr.write(err);
process.exit(code || 0);
