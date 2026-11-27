import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime, createNodeFsMountHandler } from '@php-wasm/node';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

export const HERE = dirname(fileURLToPath(import.meta.url));
// repository root = two levels above tools/php8
export const REPO = process.env.ROOT ? resolve(process.env.ROOT) : resolve(HERE, '..', '..');

let pid = 1;
// Starts a PHP (WebAssembly) instance of the given version. `mounts` maps a virtual path to a host directory.
export async function startPhp(version = '8.3', mounts = {}) {
  const php = new PHP(await loadNodeRuntime(version, { emscriptenOptions: { processId: pid++ } }));
  for (const [vpath, hpath] of Object.entries(mounts)) {
    if (!php.fileExists(vpath)) php.mkdir(vpath);
    await php.mount(vpath, createNodeFsMountHandler(hpath));
  }
  return php;
}
