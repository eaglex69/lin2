// Link checker shared by smoke/worker.mjs (run.mjs --links) and smoke/click.mjs.
// Every <a href>, <form action>, <script src>, <link href>, <iframe src>, meta refresh and the Location header of a
// response must point to something that exists in the checkout. Images are not checked: the pic/ and foto/ directories
// hold user content that is not in the repository.
import { existsSync, statSync } from 'node:fs';
import { join, posix } from 'node:path';

// directories with user content (pictures) or binary downloads that are not part of the repository
export const NOT_IN_REPO = /^\/(pic|foto|shmot|files|apps)\//;

export function linkTargets(html) {
  const out = [];
  for (const m of html.matchAll(/<(a|form|script|link|iframe|frame|area)\b([^>]*)>/gi)) {
    const tag = m[1].toLowerCase();
    const attr = (tag === 'a' || tag === 'link' || tag === 'area') ? 'href' : tag === 'form' ? 'action' : 'src';
    const am = new RegExp('\\s' + attr + '\\s*=\\s*(?:"([^"]*)"|\'([^\']*)\'|([^\\s>]+))', 'i').exec(m[2]);
    if (am) out.push([tag, (am[1] ?? am[2] ?? am[3]).trim()]);
  }
  for (const m of html.matchAll(/<meta[^>]+http-equiv=["']?refresh["']?[^>]*content=["']?\d+\s*;\s*url=([^"'>\s]+)/gi)) out.push(['refresh', m[1]]);
  return out;
}
export function resolveLink(pagePath, raw) {
  let t = raw.replace(/&amp;/g, '&');
  if (!t || t[0] === '#' || /^(?:[a-z][a-z0-9+.-]*:|\/\/)/i.test(t)) return null;      // external, javascript:, mailto: ...
  t = t.split('#')[0].split('?')[0];
  if (t === '') return null;                                                          // "?x=1": the same page
  const abs = t[0] === '/' ? posix.normalize(t) : posix.normalize(posix.join(posix.dirname(pagePath), t));
  return abs.startsWith('/..') ? null : abs;
}
export function linkExists(root, abs) {
  try {
    const st = statSync(join(root, abs));
    if (st.isFile()) return true;
    if (st.isDirectory()) return existsSync(join(root, abs, 'index.php')) || existsSync(join(root, abs, 'index.html'));
  } catch { /* missing */ }
  return false;
}
export function findBadLinks(root, file, html, location) {
  const page = '/' + file;
  const all = linkTargets(html);
  if (location) all.push(['Location', location]);
  const bad = [];
  let checked = 0;
  for (const [kind, raw] of all) {
    const abs = resolveLink(page, raw);
    if (abs === null || NOT_IN_REPO.test(abs)) continue;
    checked++;
    if (!linkExists(root, abs)) bad.push({ kind, target: raw.slice(0, 120), resolved: abs });
  }
  return { checked, bad };
}
