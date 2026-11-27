'use strict';
/**
 * tools/generate.js — генератор карты переноса PHP -> Node.js.
 * Сканирует корневые *.php и inc/*.php, пишет node/MIGRATION.md:
 * какой файл куда переехал и что реализовано.
 * Запуск: node tools/generate.js  (или npm run generate)
 */
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', '..');
const OUT = path.join(__dirname, '..', 'MIGRATION.md');

// Ручная карта для ядра (остальные — stubPage в routes.js)
const CORE = {
  'index.php': 'routes.js:index() + layout.js:pageGuest()',
  'enter.php': 'auth.js:login() (md5, cookie 3ч)',
  'reg.php': 'routes.js:reg()',
  'office.php': 'routes.js:office()',
  'pers.php': 'routes.js:pers()',
  'gorod.php': 'routes.js:gorod()',
  'okrestnosti.php': 'routes.js:okrestnosti()',
  'battle.php': 'routes.js:battle()',
  'fight.php': 'routes.js:fight() -> battle()',
  'shop.php': 'routes.js:shop()',
  'inventar.php': 'routes.js:inventar()',
  'sklad.php': 'routes.js:sklad()',
  'bank.php': 'routes.js:bank()',
  'chat.php': 'routes.js:chat()',
  'msg.php': 'routes.js:msg()',
  'news.php': 'routes.js:news()',
  'news_index.php': 'routes.js:news()',
  'rating.php': 'routes.js:rating()',
  'search.php': 'routes.js:search()',
  'online.php': 'routes.js:online()',
  'clan.php': 'routes.js:clan()',
  'home.php': 'routes.js:home()',
  'res.php': 'routes.js:res()',
  'portal.php': 'routes.js:portal()',
  'teleport.php': 'routes.js:portal()',
  'to_gorod.php': 'routes.js:portal()',
  'to_world.php': 'routes.js:portal()',
  'to_zamok.php': 'routes.js:portal()',
  'job.php': 'routes.js:job()',
  'faq.php': 'routes.js:faq()',
  'rules.php': 'routes.js:rules()',
  'main.php': 'routes.js:main()',
  'auto.php': 'routes.js:auto()',
};

const INC = {
  'inc/db.php': 'src/db.js (слой данных, shema.sql)',
  'inc/mysql.php': 'src/db.js (запросы Q.*)',
  'inc/auth.php': 'src/auth.js:auth()',
  'inc/func.php': 'src/func.js',
  'inc/core.php': 'src/func.js (sec2day, checkin, pagin)',
  'inc/head.php': 'src/layout.js:head()',
  'inc/zag.php': 'src/layout.js:zag() (HP/MP/EXP-бары)',
  'inc/down.php': 'src/layout.js:down()',
  'inc/end.php': 'src/layout.js:down()',
  'inc/endmain.php': 'src/layout.js:down()',
  'inc/opit.php': 'src/func.js:expPercent()',
  'inc/lvl.php': 'src/func.js:checkLevel()',
  'inc/regeneration.php': 'src/func.js:regeneration()',
  'inc/online.php': 'src/auth.js (online.laikas)',
  'inc/path.php': 'server.js (ROOT)',
};

function listPhp(dir) {
  return fs.readdirSync(dir).filter(f => f.endsWith('.php')).sort();
}

const rootPhp = listPhp(ROOT);
const incPhp = listPhp(path.join(ROOT, 'inc')).map(f => 'inc/' + f);
const subPhp = [];
for (const d of ['forum', 'gm', 'bonus', 'drink']) {
  const p = path.join(ROOT, d);
  if (fs.existsSync(p)) listPhp(p).forEach(f => subPhp.push(d + '/' + f));
}

let md = `# Карта переноса PHP -> Node.js (Miniage)\n\n`;
md += `Сгенерировано: ${new Date().toISOString()}\n\n`;
md += `Всего PHP-файлов: ${rootPhp.length + incPhp.length + subPhp.length} (корень: ${rootPhp.length}, inc/: ${incPhp.length}, подпапки: ${subPhp.length})\n\n`;
md += `## Ядро (реализовано полностью)\n\n| PHP | Node.js |\n|---|---|\n`;
for (const [k, v] of Object.entries(CORE)) md += `| ${k} | ${v} |\n`;
md += `\n## inc/ (библиотеки)\n\n| PHP | Node.js |\n|---|---|\n`;
for (const f of incPhp) md += `| ${f} | ${INC[f] || 'src/func.js / stub'} |\n`;
md += `\n## Остальные корневые *.php (stubPage в routes.js)\n\n`;
md += `Эти страницы отдаются через routes.js:stubPage() — Node.js отвечает на тот же URL,\n`;
md += `показывает карту переноса и навигацию. Логика оригинала сохранена в PHP-файле.\n\n`;
md += `| PHP | Node.js |\n|---|---|\n`;
for (const f of rootPhp) if (!CORE[f]) md += `| ${f} | routes.js:stubPage() |\n`;
md += `\n## Подпапки (forum/, gm/, bonus/, drink/)\n\n| PHP | Node.js |\n|---|---|\n`;
for (const f of subPhp) md += `| ${f} | routes.js:stubPage() |\n`;
md += `\n## БД\n\n\`shema.sql\` (144 таблицы) — без изменений, см. README.md.\n`;

fs.writeFileSync(OUT, md, 'utf8');
console.log(`MIGRATION.md записан: ${OUT} (${rootPhp.length + incPhp.length + subPhp.length} файлов)`);
