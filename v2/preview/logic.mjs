/**
 * Прогон логического харнесса в php-wasm.
 *
 *   cd v2/preview && node logic.mjs
 *
 * Исходники PHP и SQL-схемы передаются в виртуальную ФС /tmp/v2,
 * БД — SQLite в памяти (файл /tmp/test.sqlite).
 */
import { PhpNode } from 'php-wasm/PhpNode.mjs';
import sqlite from 'php-wasm-sqlite';
import fs from 'node:fs';
import path from 'node:path';

const php = new PhpNode({ version: '8.1', sharedLibs: [sqlite] });
php.addEventListener('output', (e) => { process.stdout.write(String(e.detail[0] ?? '')); });

const root = path.dirname(path.dirname(new URL(import.meta.url).pathname));
const files = {};
(function walk(d, prefix) {
  for (const f of fs.readdirSync(d)) {
    const p = path.join(d, f);
    if (fs.statSync(p).isDirectory()) {
      if (f !== 'node_modules' && f !== 'var') walk(p, prefix + f + '/');
    } else if (/\.(php|sql)$/.test(f)) {
      files[prefix + f] = fs.readFileSync(p, 'utf8');
    }
  }
})(root, '');

const setup = Object.entries(files).map(([k, v]) =>
  `$f[${JSON.stringify(k)}] = base64_decode("${Buffer.from(v, 'utf8').toString('base64')}");`
).join('\n');

const test = fs.readFileSync(path.join(root, 'preview', 'logic-test.php'), 'utf8');

await php.run(`<?php
$f = [];
${setup}
foreach ($f as $name => $content) {
    $p = '/tmp/v2/' . $name;
    if (!is_dir(dirname($p))) { @mkdir(dirname($p), 0777, true); }
    file_put_contents($p, $content);
}
${test.replace(/^<\?php\s*\/\*\*[\s\S]*?\*\/\s*/, '')}
`);
