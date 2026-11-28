/*
 * Miniage — blocks.
 *
 * The old pages print loose text, links, pictures and forms straight onto the page. This script puts every such group into a
 * block (div.lx-blk, look: theme/fantasy.css), so that no information stands on the bare background. Runs of loose nodes are split at
 * <hr> and at a blank line (two <br>); forms, tables, lists and paragraphs get a block of their own; wrappers the old pages leave open
 * (<font>, <center>, plain <div>) are looked through. Elements that already are surfaces (.msg .dot .menu .inoy .info ... and everything
 * called lx-*) are left alone, blocks that turn out to be empty (their picture is missing) are hidden.
 *
 * Progressive enhancement: without this file the page is the same, just without the extra blocks. Nothing is removed or rewritten,
 * nodes are only moved into a wrapper, so ids, forms and scripts of the page keep working.
 *
 * Runs on load and after every page change of theme/ajax.js (event 'lx:render').  API:  LX.blocks([root])
 */
(function () {
  'use strict';
  var W = window, D = document;
  if (!D.querySelector || !D.createElement || !Array.prototype.forEach) { return; }
  var LX = W.LX = W.LX || {};
  if (LX.blocks) { return; }

  var INLINE = { A: 1, B: 1, I: 1, U: 1, S: 1, EM: 1, STRONG: 1, SMALL: 1, SUB: 1, SUP: 1, SPAN: 1, FONT: 1, IMG: 1, INPUT: 1, SELECT: 1,
    TEXTAREA: 1, BUTTON: 1, LABEL: 1, CODE: 1, BIG: 1, STRIKE: 1, TT: 1, ABBR: 1, Q: 1, MARK: 1, CITE: 1, NOBR: 1, WBR: 1, KBD: 1,
    DEL: 1, INS: 1, VAR: 1, SAMP: 1, DFN: 1, BDO: 1 };
  var NEUTRAL = { SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, LINK: 1, META: 1, TEMPLATE: 1, BASE: 1 };
  var OWN_BLOCK = { FORM: 1, TABLE: 1, UL: 1, OL: 1, DL: 1, P: 1, PRE: 1, BLOCKQUOTE: 1, FIELDSET: 1, H1: 1, H2: 1, H3: 1, H4: 1, H5: 1, H6: 1 };
  var LISTS = /(^|\s)(inoy|pic|silka)(\s|$)/;                          // lists of keys: every <a> is a key
  var SURFACE = /(^|\s)(msg|dot|inoy|pic|silka|info|event|news|forum|inf|pkboy|lich|head|down|down2|adm|admin|foot|navigation|zagolovok|paty|logtext|chattext|chat2|lx-[A-Za-z0-9_-]+)(\s|$)/;
  var BLOCK_INSIDE = 'div,table,form,hr,p,ul,ol,dl,h1,h2,h3,h4,h5,h6,pre,blockquote,center,fieldset';
  var VOIDABLE = '.lx-blk,.menu,.inoy,.pic,.silka,.msg,.dot,.event,center';

  function cls(n) { return typeof n.className === 'string' ? n.className : ''; }
  function slice(l) { return Array.prototype.slice.call(l); }

  function hasText(n) { return (n.textContent || '').replace(/[\s\u00a0]+/g, '') !== ''; }
  function hasControl(n) {
    if (n.nodeType !== 1) { return false; }
    if (/^(INPUT|SELECT|TEXTAREA|BUTTON)$/.test(n.tagName)) { return n.type !== 'hidden'; }
    return !!(n.querySelector && n.querySelector('input:not([type=hidden]),select,textarea,button'));
  }
  function hasPic(n) {
    if (n.nodeType !== 1) { return false; }
    if (n.tagName === 'IMG') { return !/(^|\s)lx-noimg(\s|$)/.test(cls(n)); }
    return !!(n.querySelector && n.querySelector('img:not(.lx-noimg)'));
  }
  /* is there anything to look at in this node? */
  function meaningful(n) {
    if (n.nodeType === 3) { return /\S/.test(n.nodeValue.replace(/\u00a0/g, ' ')); }
    if (n.nodeType !== 1 || n.tagName === 'BR' || NEUTRAL[n.tagName]) { return false; }
    return hasText(n) || hasControl(n) || hasPic(n);
  }
  function isSurface(n) {
    return SURFACE.test(cls(n)) || (n.id && n.id.indexOf('lx-') === 0) || n.hasAttribute('data-lx-live') || n.hasAttribute('data-lx-keep');
  }
  function isBr(n) { return n.nodeType === 1 && n.tagName === 'BR'; }
  function isBlank(n) { return n.nodeType === 8 || (n.nodeType === 3 && !/\S/.test(n.nodeValue.replace(/\u00a0/g, ' '))); }

  /* wrap a run of loose nodes into one block (leading / trailing line breaks are hidden, not moved) */
  function flush(run) {
    var a = 0, b = run.length - 1, i;
    while (a <= b && (isBr(run[a]) || isBlank(run[a]))) { if (isBr(run[a])) { run[a].className = 'lx-hb'; } a++; }
    while (b >= a && (isBr(run[b]) || isBlank(run[b]))) { if (isBr(run[b])) { run[b].className = 'lx-hb'; } b--; }
    if (a > b) { return; }
    var part = run.slice(a, b + 1), any = false;
    for (i = 0; i < part.length; i++) { if (meaningful(part[i])) { any = true; break; } }
    if (!any) { return; }
    var box = D.createElement('div');
    box.className = 'lx-blk';
    part[0].parentNode.insertBefore(box, part[0]);
    for (i = 0; i < part.length; i++) { box.appendChild(part[i]); }
  }

  function wrapOne(el) {
    var box = D.createElement('div');
    box.className = 'lx-blk' + (el.tagName === 'TABLE' ? ' tb' : '');
    el.parentNode.insertBefore(box, el);
    box.appendChild(el);
  }

  /* go through the children of one container */
  function walk(box, listMode, depth) {
    if (depth > 12) { return; }
    var kids = slice(box.childNodes), run = [], brs = 0, i, n, t;
    for (i = 0; i < kids.length; i++) {
      n = kids[i];
      if (n.nodeType === 3 || n.nodeType === 8) { if (!isBlank(n)) { brs = 0; } run.push(n); continue; }
      if (n.nodeType !== 1) { continue; }
      t = n.tagName;
      if (NEUTRAL[t]) { flush(run); run = []; brs = 0; continue; }
      if (t === 'BR') {
        brs++;
        if (brs >= 2 && run.length) {            // a blank line ends the block
          flush(run); run = [];
          n.className = 'lx-hb';
        } else if (brs >= 2) {
          n.className = 'lx-hb';
        } else { run.push(n); }
        continue;
      }
      if (!isBlank(n)) { brs = 0; }
      if (t === 'HR') { flush(run); run = []; n.className = (cls(n) + ' lx-sep').replace(/^\s+/, ''); continue; }
      if (t === 'A' && listMode) { flush(run); run = []; continue; }           // a key of a link list
      if (INLINE[t]) {
        if (n.querySelector && n.querySelector(BLOCK_INSIDE) && !isSurface(n)) { flush(run); run = []; walk(n, listMode, depth + 1); }
        else { run.push(n); }
        continue;
      }
      // block level
      flush(run); run = [];
      var c = cls(n);
      if (isSurface(n)) {
        if (LISTS.test(c) && !/lx-/.test(c)) { walk(n, true, depth + 1); }       // text between the keys of a link list
        continue;
      }
      if (t === 'DIV' && !n.id) { walk(n, listMode, depth + 1); continue; }                // a wrapper (plain or with a class of its own): looked through
      if (t === 'CENTER') { walk(n, listMode, depth + 1); continue; }
      if (OWN_BLOCK[t]) { if (meaningful(n)) { wrapOne(n); } }
    }
    flush(run);
  }

  /* hide a plate that has nothing to show (its picture is missing) */
  function voidScan(root) {
    slice((root || D).querySelectorAll(VOIDABLE)).forEach(function (el) {
      var empty = !meaningful(el);
      var has = /(^|\s)lx-void(\s|$)/.test(cls(el));
      if (empty && !has) { el.className = cls(el) + ' lx-void'; }
      else if (!empty && has) { el.className = cls(el).replace(/(^|\s)lx-void(?=\s|$)/g, ''); }
    });
  }

  function run(root) {
    var cell = root || D.querySelector('td.centertd');
    if (!cell) { return; }
    try { walk(cell, false, 0); voidScan(cell); } catch (e) { if (W.console && console.error) { console.error('lx blocks:', e); } }
  }

  LX.blocks = run;

  if (D.readyState === 'loading') { D.addEventListener('DOMContentLoaded', function () { run(); }); } else { run(); }
  W.addEventListener('load', function () { var c = D.querySelector('td.centertd'); if (c) { voidScan(c); } });
  D.addEventListener('lx:render', function () { run(); setTimeout(function () { var c = D.querySelector('td.centertd'); if (c) { voidScan(c); } }, 350); });
  D.addEventListener('error', function (ev) {                        // a picture that is missing may empty its plate
    if (ev.target && ev.target.tagName === 'IMG') {
      setTimeout(function () { var c = D.querySelector('td.centertd'); if (c) { voidScan(c); } }, 30);
    }
  }, true);
})();
