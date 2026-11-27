#!/usr/bin/env node
/**
 * build-release.mjs — собирает архив для установки на хостинг.
 *
 *   cd v2/preview && node ../release/build-release.mjs
 *
 * Что делает:
 *   • раскладывает приложение «плоско» (всё в одном каталоге) — так архив
 *     распаковывается прямо в public_html;
 *   • кладёт корневой .htaccess из release/.htaccess (закрывает .env и var/);
 *   • кладёт README.txt с инструкцией;
 *   • НЕ включает: node_modules, var/ (базу), .env, тесты, preview-мост,
 *     BUGS.md/SECURITY.md и прочие внутренние файлы;
 *   • пишет v2/var/release/miniage-v2-<версия>.zip и печатает состав.
 *
 * Требуется только Node.js (zip собирается через tar/zip из системы либо
 * встроенным zipfile-совместимым способом — используется системный `zip`).
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const V2 = path.resolve(__dirname, '..');
const VERSION = fs.existsSync(path.join(V2, 'VERSION'))
  ? fs.readFileSync(path.join(V2, 'VERSION'), 'utf8').trim()
  : '2.0';
const OUT_DIR = path.join(V2, 'var', 'release');
const STAGE = path.join(OUT_DIR, 'stage');
const NAME = `miniage-v2-${VERSION}`;
const ZIP = path.join(OUT_DIR, `${NAME}.zip`);

const log = (...a) => console.log(...a);

// --- что копируем: [откуда, куда, рекурсивно?]
const entries = [
  ['src', 'src', true],
  ['db', 'db', true],
  ['public/assets', 'assets', true],
  ['public/index.php', 'index.php', false],
  ['public/install.php', 'install.php', false],
  ['.env.example', '.env.example', false],
  ['release/.htaccess', '.htaccess', false],
  ['release/README.txt', 'README.txt', false],
];

function rmrf(p) {
  fs.rmSync(p, { recursive: true, force: true });
}

function copy(src, dst) {
  const st = fs.statSync(src);
  if (st.isDirectory()) {
    fs.mkdirSync(dst, { recursive: true });
    for (const name of fs.readdirSync(src)) {
      if (name === '.DS_Store' || name.endsWith('~')) continue;
      copy(path.join(src, name), path.join(dst, name));
    }
    return;
  }
  fs.mkdirSync(path.dirname(dst), { recursive: true });
  fs.copyFileSync(src, dst);
  // права: каталоги 755, файлы 644 — как ждёт хостинг
  fs.chmodSync(dst, 0o644);
}

// ---------------------------------------------------------------- подготовка
rmrf(STAGE);
fs.mkdirSync(STAGE, { recursive: true });
fs.mkdirSync(path.join(STAGE, 'var'), { recursive: true });

const missing = [];
for (const [from, to, rec] of entries) {
  const src = path.join(V2, from);
  if (!fs.existsSync(src)) { missing.push(from); continue; }
  if (rec) copy(src, path.join(STAGE, to));
  else {
    fs.mkdirSync(path.dirname(path.join(STAGE, to)), { recursive: true });
    fs.copyFileSync(src, path.join(STAGE, to));
    fs.chmodSync(path.join(STAGE, to), 0o644);
  }
}
if (missing.length) {
  console.error('Не найдены файлы:', missing.join(', '));
  process.exit(1);
}

// .env в архив не попадает, но пример — да; пустой var/ нужен для записи
fs.writeFileSync(path.join(STAGE, 'var', '.gitkeep'), '');
// в архиве .gitkeep не нужен
fs.unlinkSync(path.join(STAGE, 'var', '.gitkeep'));

// короткая версия отчёта о сборке
const files = [];
(function walk(d, base = '') {
  for (const name of fs.readdirSync(d).sort()) {
    const p = path.join(d, name);
    if (fs.statSync(p).isDirectory()) walk(p, `${base}${name}/`);
    else files.push(`${base}${name}`);
  }
})(STAGE);

// ---------------------------------------------------------------- упаковка
if (fs.existsSync(ZIP)) fs.unlinkSync(ZIP);
try {
  execFileSync('zip', ['-r', '-q', '-X', ZIP, '.'], { cwd: STAGE });
} catch (e) {
  console.error('Не удалось запустить zip:', e.message);
  process.exit(1);
}
const sizeKb = Math.round(fs.statSync(ZIP).size / 1024);

log(`Готово: ${path.relative(process.cwd(), ZIP)} (${sizeKb} КБ, ${files.length} файлов, версия ${VERSION})`);
log('Состав:');
for (const f of files) log('  ' + f);

// контрольная проверка: в архив не попали секреты и база
const forbidden = files.filter((f) =>
  /(^|\/)(\.env|\.git|node_modules|var\/miniage)(\/|$)/.test(f) ||
  /\.(sqlite|sqlite3|log)$/.test(f)
);
if (forbidden.length) {
  console.error('В архив попали лишние файлы:', forbidden);
  process.exit(1);
}
log('Проверка состава: .env/база/логи не попали — ок');
