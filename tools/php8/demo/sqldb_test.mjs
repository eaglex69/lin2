// Self-test of the demo database: translation of the statements the game uses + import of the real dump (shema.sql).
//   node demo/sqldb_test.mjs
import { SqlEngine, splitSql } from './sqldb.mjs';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { REPO } from '../common.mjs';

let failed = 0, total = 0;
const ok = (name, cond, detail = '') => { total++; if (!cond) { failed++; console.log('  FAIL ' + name + (detail ? '  ::  ' + detail : '')); } };
const rows = (e, sql) => e.run(sql).rows;

const e = new SqlEngine({});
e.run("CREATE TABLE IF NOT EXISTS `t` (\n`id` int(11) NOT NULL,\n  `usr` varchar(15) NOT NULL,\n  `hp` int(11) NOT NULL,\n  `txt` text NOT NULL,\n `d` datetime NOT NULL\n) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;");
e.run('ALTER TABLE `t` ADD PRIMARY KEY (`id`);');
e.run('ALTER TABLE `t` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;');
const ins = e.run("INSERT INTO t SET usr='Tester', hp='100', txt=\"say \\\"hi\\\" it's\\nok\"");
ok('INSERT ... SET gives an insert id', ins.insertId === 1 && ins.affected === 1, JSON.stringify(ins));
ok('INSERT ... SET keeps the escapes of the string', rows(e, 'SELECT txt FROM t')[0][0] === 'say "hi" it\'s\nok');
ok('implicit defaults of NOT NULL columns (sql_mode empty)', rows(e, "SELECT d FROM t")[0][0] === '0000-00-00 00:00:00');
e.run("INSERT INTO `t` (`usr`, `hp`) VALUES ('Second', 5), ('Third', 7)");
ok('case-insensitive text comparison', rows(e, "SELECT hp FROM t WHERE usr='tester'").length === 1);
e.run("UPDATE t SET hp = hp - 30 WHERE usr='Tester' LIMIT 1");
ok('UPDATE ... LIMIT', rows(e, "SELECT hp FROM t WHERE usr='Tester'")[0][0] === 70);
e.run("UPDATE `t` SET `hp` = '1' WHERE hp > 0 ORDER BY id DESC LIMIT 1");
ok('UPDATE ... ORDER BY ... LIMIT touches one row', rows(e, 'SELECT COUNT(*) FROM t WHERE hp = 1')[0][0] === 1);
e.run("DELETE FROM t WHERE usr='Third' LIMIT 1");
ok('DELETE ... LIMIT', rows(e, 'SELECT COUNT(*) FROM t')[0][0] === 2);
ok('&& and || are AND / OR', rows(e, "SELECT usr FROM t WHERE hp > 0 && usr != 'x' || id = 99").length === 2);
ok('RAND() and ORDER BY RAND()', rows(e, 'SELECT * FROM t ORDER BY RAND() LIMIT 1').length === 1);
ok('IFNULL / IF / CONCAT', rows(e, "SELECT IFNULL(NULL, 3), IF(1 > 0, 'a', 'b'), CONCAT('x', usr) FROM t LIMIT 1")[0].join(',') === '3,a,xTester');
ok('UNIX_TIMESTAMP()', Math.abs(rows(e, 'SELECT UNIX_TIMESTAMP()')[0][0] - Date.now() / 1000) < 3);
ok('LIMIT a, b', rows(e, 'SELECT * FROM t ORDER BY id LIMIT 1, 5').length === 1);
ok('COUNT(*) / SUM on an empty set', rows(e, "SELECT COUNT(*), SUM(hp) FROM t WHERE usr = 'nobody'")[0].join(',') === '0,');
ok('SHOW TABLES', rows(e, 'SHOW TABLES').some(r => r[0] === 't'));
ok('SELECT VERSION()', /sqlite/.test(rows(e, 'SELECT VERSION()')[0][0]));
let err = null; try { e.run('SELECT * FROM nope'); } catch (x) { err = x; }
ok('unknown table -> 1146', err && err.mysql === 1146, err && err.message);
err = null; try { e.run('SELECT nope FROM t'); } catch (x) { err = x; }
ok('unknown column -> 1054', err && err.mysql === 1054, err && err.message);
e.run("REPLACE INTO t SET id=1, usr='Replaced', hp=2");
ok('REPLACE INTO ... SET', rows(e, "SELECT usr FROM t WHERE id=1")[0][0] === 'Replaced');

// the real dump
const dump = new SqlEngine({});
const stmts = splitSql(readFileSync(join(REPO, 'shema.sql'), 'utf8'));
let bad = 0, firstBad = '';
const t0 = Date.now();
for (const s of stmts) { try { dump.run(s); } catch (x) { bad++; if (!firstBad) firstBad = x.message + ' <= ' + s.slice(0, 120); } }
ok(`shema.sql: ${stmts.length} statements run without errors (${Date.now() - t0} ms)`, bad === 0, `${bad} failed, first: ${firstBad}`);
ok('shema.sql: 144 tables', dump.tableNames().length === 144, String(dump.tableNames().length));
ok('shema.sql: mobs have data', rows(dump, 'SELECT COUNT(*) FROM mobs')[0][0] > 10, String(rows(dump, 'SELECT COUNT(*) FROM mobs')[0][0]));
ok('shema.sql: AUTO_INCREMENT works after the ALTERs', (() => { dump.run("INSERT INTO account SET nick='n', pass='p'"); const r = dump.run("INSERT INTO account SET nick='m', pass='p'"); return r.insertId >= 2; })());
const extra = splitSql(readFileSync(join(REPO, 'shema_extra.sql'), 'utf8'));
let bad2 = 0; for (const s of extra) { try { dump.run(s); } catch (x) { bad2++; console.log('   extra:', x.message); } }
ok('shema_extra.sql runs', bad2 === 0 && dump.tableNames().includes('job_pvp') && dump.tableNames().includes('clan_shop'));

console.log(failed ? `FAILED: ${failed} of ${total} checks` : `OK: ${total} checks passed`);
process.exit(failed ? 1 : 0);
