// Child process of smoke/run.mjs: executes the scenarios of ONE page in a fresh PHP (WebAssembly) instance.
// usage: node smoke/worker.mjs <job.json>      (progress and the result go to stdout as JSON lines)
import { readFileSync } from 'node:fs';
import { startPhp } from '../common.mjs';

const job = JSON.parse(readFileSync(process.argv[2], 'utf8'));
const MAX_OUT = 200000;

// Every request runs through this wrapper so that error display can be configured and the target page is
// executed in the global scope (the game relies on global variables).
const RUNNER = `<?php
error_reporting(-1);
ini_set('display_errors', '1');
ini_set('html_errors', '0');
ini_set('log_errors', '0');
ini_set('memory_limit', '256M');
$__target = getenv('SMOKE_TARGET');
chdir(dirname($__target));
unset($__target_dir);
require $__target;
`;

const php = await startPhp(job.php, { '/work': job.root });
if (!php.fileExists('/internal/smoke')) php.mkdir('/internal/smoke');
php.writeFile('/internal/smoke/runner.php', RUNNER);

function parseDiagnostics(text) {
  const diags = [];
  const re = /^(?:PHP )?(Warning|Notice|Deprecated|Fatal error|Parse error|Error): ([\s\S]*?) in (\/work\/[^\s]+?) on line (\d+)$/gm;
  let m;
  while ((m = re.exec(text))) diags.push({ level: m[1], message: m[2].split('\n')[0].slice(0, 300), file: m[3].replace('/work/', ''), line: Number(m[4]), full: m[2].length > 300 ? m[2].slice(0, 600) : undefined });
  // production mode: exceptions are caught by inc/php8.php and only written to error_log ("PHP Uncaught TypeError: ... in /work/f.php:12")
  const re2 = /PHP Uncaught (\w+): (.*?) in (\/work\/[^\s:]+):(\d+)\s*$/gm;
  while ((m = re2.exec(text))) diags.push({ level: 'Fatal error', message: 'Uncaught ' + m[1] + ': ' + m[2].slice(0, 300), file: m[3].replace('/work/', ''), line: Number(m[4]) });
  return diags;
}

for (const sc of job.scenarios) {
  process.stdout.write(JSON.stringify({ start: sc.name }) + '\n');
  const target = '/work/' + job.file;
  const uri = '/' + job.file + (sc.query ? '?' + sc.query : '');
  const headers = { 'user-agent': 'Mozilla/5.0 (smoke)', ...(sc.cookies ? { cookie: sc.cookies } : {}) };
  if (sc.body !== undefined) headers['content-type'] = 'application/x-www-form-urlencoded';
  let text = '', errors = '', exitCode = 0, thrown = null, t0 = Date.now();
  try {
    const r = await php.run({
      scriptPath: '/internal/smoke/runner.php',
      relativeUri: uri,
      method: sc.body !== undefined ? 'POST' : 'GET',
      headers,
      body: sc.body,
      env: { SMOKE_TARGET: target, ...job.env },
      $_SERVER: { SCRIPT_FILENAME: target, SCRIPT_NAME: '/' + job.file, PHP_SELF: '/' + job.file, DOCUMENT_ROOT: '/work', REMOTE_ADDR: '127.0.0.1', HTTP_HOST: 'localhost', SERVER_NAME: 'localhost' },
    });
    text = r.text; errors = r.errors || ''; exitCode = r.httpStatusCode;
  } catch (e) {
    thrown = String(e && e.message || e).split('\n')[0];
    const resp = e && e.response;
    if (resp) { try { text = resp.text; errors = resp.errors || ''; } catch { /* ignore */ } }
    if (!text && e && e.message) { const mm = /=== Stdout ===\n([\s\S]*?)(?:\n=== Stderr ===|$)/.exec(e.message); if (mm) text = mm[1]; }
  }
  const diags = parseDiagnostics(text + '\n' + errors);
  const fatal = diags.find(d => d.level === 'Fatal error' || d.level === 'Parse error');
  const restricted = /Error: restricted access/.test(text);
  process.stdout.write(JSON.stringify({
    done: sc.name, ms: Date.now() - t0, bytes: text.length, status: fatal ? 'fatal' : restricted ? 'restricted' : thrown ? 'crash' : 'ok',
    thrown, diags, redirect: undefined,
    head: (fatal || thrown) ? text.slice(-1500) : undefined,
    sample: sc.keepOutput ? text.slice(0, MAX_OUT) : undefined,
  }) + '\n');
}
process.exit(0);
