// Runs the cron robots of bonus/ (votes_bot.php, bonus_bot.php) once against the fake MySQL server.
// usage: node smoke/bots.mjs [php-version]        (default 8.3)
//
// The robots read their connection settings from bonus/config.php (host 'localhost', fixed DB name and password), which the
// smoke test cannot reach, so the constants are defined first: config.php's define() calls then only produce "already
// defined" warnings (filtered out below) and the robots connect to the fake server instead.
// What this proves: opening the link (mysql_connect with $new_link), mysql_select_db / mysql_query / mysql_error with an explicit
// link, CREATE TABLE and the rest of the robot's mysql_* calls run through inc/mysql_compat_impl.php without a fatal error.
// What it does not: the fake server returns two dummy rows for every SELECT (so the robots end in their "wrong number of rows"
// branch), and the HTTP request to vigre.su is not made.
import { startFakeDb } from './fakedb.mjs';
import { startPhp, REPO } from '../common.mjs';
import { join } from 'node:path';

const RUNNER = `<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');
ini_set('html_errors', '0');
$host = getenv('FAKE_HOST');
foreach (array('VOTES', 'GAME') as $k) {
    define("VGR_INT_{$k}_SQL_SERVER_HOST", $host);
    define("VGR_INT_{$k}_SQL_SERVER_USER", 'smoke');
    define("VGR_INT_{$k}_SQL_SERVER_PASS", 'smoke');
    define("VGR_INT_{$k}_SQL_SERVER_DB", 'siska');
}
chdir('/work/bonus');
try {
    include '/work/bonus/' . getenv('BOT');      // the robot starts itself at the end of its file
} catch (Throwable $e) {
    echo "\\nTHROWN ", get_class($e), ': ', $e->getMessage(), ' @', $e->getFile(), ':', $e->getLine(), "\\n";
}
`;

const version = process.argv[2] || '8.3';
const db = await startFakeDb({ schemaPath: join(REPO, 'shema.sql') });
const php = await startPhp(version, { '/work': REPO });
if (!php.fileExists('/internal/bots')) php.mkdir('/internal/bots');
php.writeFile('/internal/bots/runner.php', RUNNER);

let failed = false;
for (const bot of ['votes_bot.php', 'bonus_bot.php']) {
  let text = '';
  try {
    const r = await php.run({ scriptPath: '/internal/bots/runner.php', env: { FAKE_HOST: '127.0.0.1:' + db.port, BOT: bot } });
    text = r.text;
  } catch (e) {
    failed = true;
    text = 'CRASH ' + String(e.message).split('\n')[0] + (e.response ? '\n' + e.response.text : '');
  }
  text = text.split('\n').filter(l => !/already defined/.test(l)).join('\n').trim();
  if (/THROWN|Fatal error|Parse error|CRASH/.test(text)) failed = true;
  console.log(`===== PHP ${version}: ${bot}\n${text}\n`);
}
console.log(failed ? 'FAILED' : `OK: both robots ran to the end on PHP ${version} (${db.stats.queries} queries)`);
process.exit(failed ? 1 : 0);
