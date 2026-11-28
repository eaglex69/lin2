/*
 * Miniage — page changes without reloads (AJAX).
 *
 * Progressive enhancement: without this file (or in a browser without fetch) every link and form works the old way.
 * Same-origin links and forms are fetched in the background, the new page replaces the old one inside <body>
 * (title, history, scripts and the loading bar are handled here). Anything unusual (downloads, target=_blank,
 * other sites, modifier keys, data-lx="off") is left to the browser.
 *
 * Public API:  LX.go(url[, {method, body, push}])   LX.fetchPage(url[, init])   LX.render(html, url[, {push}])
 * Events:      document 'lx:render'  (after every swap; detail = {url, doc, prev})
 */
(function () {
  'use strict';
  var W = window, D = document;
  if (W.LX && W.LX.ready) { return; }
  if (!W.fetch || !W.DOMParser || !W.URL || !W.history || !history.pushState || !W.Promise) { return; }

  var LX = W.LX = W.LX || {};
  LX.ready = true;
  var root = D.documentElement;
  var running = null;          // AbortController of the request in flight
  var token = 0;               // id of the latest navigation, older answers are dropped
  var lastPath = location.pathname + location.search;
  var refreshTimer = 0;
  var loaderEl = null, loaderTimer = 0, toastEl = null, toastTimer = 0;
  var lastSubmitter = null;

  var FILE_RE = /\.(zip|rar|7z|apk|exe|pdf|mp3|mp4|ogg|jpe?g|png|gif|webp|svg|txt|csv|xml|js|css)(\?|#|$)/i;

  function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || D).querySelectorAll(sel)); }

  /* ---------- loader and messages ---------- */
  function ensureUi() {
    if (!loaderEl) {
      loaderEl = D.createElement('div');
      loaderEl.id = 'lx-loader';
      loaderEl.setAttribute('data-lx-keep', '1');
      loaderEl.innerHTML = '<i></i>';
      D.body.appendChild(loaderEl);
    }
  }
  function busy(on) {
    clearTimeout(loaderTimer);
    if (on) {
      loaderTimer = setTimeout(function () {
        ensureUi();
        root.classList.add('lx-busy');
        var bar = loaderEl.firstChild;
        bar.style.width = '12%';
        setTimeout(function () { bar.style.width = '68%'; }, 30);
      }, 120);
    } else if (loaderEl && root.classList.contains('lx-busy')) {
      loaderEl.firstChild.style.width = '100%';
      setTimeout(function () {
        root.classList.remove('lx-busy');
        loaderEl.firstChild.style.width = '0';
      }, 180);
    } else {
      root.classList.remove('lx-busy');
    }
  }
  function toast(text) {
    if (!toastEl) {
      toastEl = D.createElement('div');
      toastEl.id = 'lx-toast';
      toastEl.setAttribute('data-lx-keep', '1');
      D.body.appendChild(toastEl);
    }
    toastEl.textContent = text;
    toastEl.style.display = 'block';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.style.display = 'none'; }, 4500);
  }

  /* ---------- helpers ---------- */
  function sameOrigin(u) {
    return (u.protocol === 'http:' || u.protocol === 'https:') && u.host === location.host;
  }
  function decode(buf, contentType) {
    var m = /charset\s*=\s*["']?([\w-]+)/i.exec(contentType || '');
    var label = m ? m[1] : 'utf-8';
    try { return new TextDecoder(label).decode(buf); }
    catch (e) { return new TextDecoder('utf-8').decode(buf); }
  }
  function isHtml(ct) { return /(^|\/|\+)(x?html)\b/i.test(ct || ''); }

  /** fetch + decode a page; resolves {html, url, status, doc} or rejects */
  function fetchPage(url, init) {
    init = init || {};
    var ctl = ('AbortController' in W) ? new AbortController() : null;
    if (ctl && init.keepOthers !== true) {
      if (running) { try { running.abort(); } catch (e) {} }
      running = ctl;
    }
    var opts = {
      method: init.method || 'GET',
      body: init.body,
      credentials: 'same-origin',
      cache: 'no-store',
      redirect: 'follow',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-LX': '1' }
    };
    if (ctl) { opts.signal = ctl.signal; }
    return fetch(url, opts).then(function (resp) {
      var ct = resp.headers.get('content-type') || '';
      var disp = resp.headers.get('content-disposition') || '';
      if (!isHtml(ct) || /attachment/i.test(disp)) {
        var err = new Error('not-html');
        err.resp = resp;
        throw err;
      }
      return resp.arrayBuffer().then(function (buf) {
        var html = decode(buf, ct);
        if (running === ctl) { running = null; }
        return {
          html: html,
          url: resp.url || url,
          status: resp.status,
          doc: new DOMParser().parseFromString(html, 'text/html')
        };
      });
    });
  }

  /* ---------- swapping the page ---------- */
  function cloneScript(old) {
    var s = D.createElement('script');
    for (var i = 0; i < old.attributes.length; i++) { s.setAttribute(old.attributes[i].name, old.attributes[i].value); }
    s.text = old.text;
    return s;
  }
  function stripVersion(h) { return (h || '').replace(/[?#].*$/, ''); }

  function syncHead(doc) {
    var have = {};
    qsa('link[rel~="stylesheet"]').forEach(function (l) { have[stripVersion(l.getAttribute('href'))] = l; });
    qsa('link[rel~="stylesheet"]', doc).forEach(function (l) {
      var h = l.getAttribute('href');
      var key = stripVersion(h);
      if (!have[key]) { D.head.appendChild(D.importNode(l, true)); }
      else if (have[key].getAttribute('href') !== h) { have[key].setAttribute('href', h); }   // new ?v=
    });
    if (doc.title) { D.title = doc.title; }
  }

  function render(html, url, opts) {
    opts = opts || {};
    var doc = opts.doc || new DOMParser().parseFromString(html, 'text/html');
    if (!doc.getElementById('lx-app')) {
      // not one of our pages (server error page, plain text ...): show it as it is
      history.pushState({ lx: 1 }, '', url);
      D.open(); D.write(html); D.close();
      return;
    }
    var prev = LX.snapshot ? LX.snapshot() : null;
    syncHead(doc);

    // remember the scroll position of the page we are leaving
    try { history.replaceState({ lx: 1, y: W.pageYOffset }, '', location.href); } catch (e) {}

    var keep = qsa('body>[data-lx-keep]');
    var kids = Array.prototype.slice.call(D.body.childNodes).filter(function (n) { return keep.indexOf(n) < 0; });
    kids.forEach(function (n) { D.body.removeChild(n); });
    var frag = D.createDocumentFragment();
    Array.prototype.slice.call(doc.body.childNodes).forEach(function (n) { frag.appendChild(D.importNode(n, true)); });
    // scripts of the new page must be re-created to run
    var todo = [];
    qsa('script', frag).forEach(function (s) {
      var src = s.getAttribute('src');
      if (src) {
        var u;
        try { u = new URL(src, url); } catch (e) { u = null; }
        // counters of other sites use document.write, they cannot run after the load: skip them
        if (!u || !sameOrigin(u) || qsa('script[src]').some(function (x) { return stripVersion(x.getAttribute('src')) === stripVersion(src); })) {
          s.parentNode.removeChild(s);
          return;
        }
      }
      todo.push(s);
    });
    D.body.insertBefore(frag, D.body.firstChild);
    todo.forEach(function (s) {
      var n = cloneScript(s);
      try { s.parentNode.replaceChild(n, s); } catch (e) { /* script removed with its parent */ }
    });

    // the page is built: loose text goes into blocks (theme/blocks.js) BEFORE the scroll position is restored, the height changes
    if (LX.blocks) { try { LX.blocks(); } catch (e) { /* the page works without blocks */ } }

    // history (opening the page we are already on replaces the entry instead of stacking a duplicate)
    if (opts.push === 'replace' || (opts.push !== false && url === location.href)) { history.replaceState({ lx: 1, y: 0 }, '', url); }
    else if (opts.push !== false) { history.pushState({ lx: 1, y: 0 }, '', url); }
    lastPath = location.pathname + location.search;

    // scroll
    if (opts.push === false && opts.scrollY != null) { W.scrollTo(0, opts.scrollY); }
    else if (location.hash && D.getElementById(location.hash.slice(1))) { D.getElementById(location.hash.slice(1)).scrollIntoView(); }
    else if (opts.keepScroll !== true) { W.scrollTo(0, 0); }

    hideBrokenImages();
    scheduleRefresh(doc, url);
    var ev;
    try { ev = new CustomEvent('lx:render', { detail: { url: url, doc: doc, prev: prev } }); }
    catch (e) { ev = D.createEvent('CustomEvent'); ev.initCustomEvent('lx:render', false, false, { url: url, doc: doc, prev: prev }); }
    D.dispatchEvent(ev);
  }

  function scheduleRefresh(doc, base) {
    clearTimeout(refreshTimer);
    var m = doc.querySelector('meta[http-equiv="refresh" i]');
    if (!m) { return; }
    var mm = /^\s*(\d+)\s*(?:[;,]\s*(?:url\s*=\s*)?['"]?([^'"]*)['"]?)?\s*$/i.exec(m.getAttribute('content') || '');
    if (!mm) { return; }
    var my = token;
    var target = mm[2] ? new URL(mm[2], base).href : location.href;
    refreshTimer = setTimeout(function () { if (my === token) { go(target, { push: mm[2] ? true : 'replace' }); } }, Math.max(0, +mm[1]) * 1000);
  }

  /* ---------- navigation ---------- */
  function go(url, opts) {
    opts = opts || {};
    var my = ++token;
    clearTimeout(refreshTimer);
    busy(true);
    var method = (opts.method || 'GET').toUpperCase();
    return fetchPage(url, { method: method, body: opts.body }).then(function (res) {
      if (my !== token) { return; }
      busy(false);
      if (method === 'GET' && !res.doc.getElementById('lx-app')) { location.assign(res.url); return; }   // 404 page, plain text ...
      render(res.html, res.url, { doc: res.doc, push: opts.push, scrollY: opts.scrollY, keepScroll: opts.keepScroll });
    }).catch(function (err) {
      if (err && err.name === 'AbortError') { return; }
      if (my !== token) { return; }
      busy(false);
      if (err && err.message === 'not-html' && err.resp) {
        // a file or something that is not a page
        if (method === 'GET') { location.assign(url); return; }
        err.resp.blob().then(function (b) {
          var a = D.createElement('a');
          a.href = URL.createObjectURL(b);
          a.download = '';
          D.body.appendChild(a); a.click(); D.body.removeChild(a);
        });
        return;
      }
      if (method === 'GET') { location.assign(url); }
      else { toast('Нет связи с сервером. Повторите действие.'); }
    });
  }

  function linkOk(a, ev) {
    if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) { return null; }
    if (a.hasAttribute('download') || (a.target && a.target !== '_self') || a.getAttribute('data-lx') === 'off') { return null; }
    if (/\bexternal\b/.test(a.getAttribute('rel') || '')) { return null; }
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel|sms|data|blob):/i.test(href)) { return null; }
    var u;
    try { u = new URL(a.href, location.href); } catch (e) { return null; }
    if (!sameOrigin(u) || FILE_RE.test(u.pathname)) { return null; }
    if (u.pathname + u.search === location.pathname + location.search && u.hash) { return null; }   // jump inside the page
    return u.href;
  }

  D.addEventListener('click', function (ev) {
    var t = ev.target;
    while (t && t !== D && !(t.tagName === 'A' && t.hasAttribute('href'))) { t = t.parentNode; }
    if (!t || t === D) {
      // remember the pressed submit button (older browsers have no event.submitter)
      var b = ev.target && ev.target.closest ? ev.target.closest('button,input[type=submit],input[type=image]') : null;
      lastSubmitter = b || null;
      return;
    }
    var url = linkOk(t, ev);
    if (!url) { return; }
    ev.preventDefault();
    go(url);
  });

  D.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (ev.defaultPrevented || !f || f.tagName !== 'FORM') { return; }
    var submitter = ev.submitter || lastSubmitter;
    lastSubmitter = null;
    if (submitter && submitter.form !== f) { submitter = null; }
    if ((f.target && f.target !== '_self') || f.getAttribute('data-lx') === 'off') { return; }
    var method = ((submitter && submitter.getAttribute('formmethod')) || f.getAttribute('method') || 'GET').toUpperCase();
    if (method !== 'GET' && method !== 'POST') { return; }
    var action = (submitter && submitter.getAttribute('formaction')) || f.getAttribute('action') || location.href;
    var u;
    try { u = new URL(action, location.href); } catch (e) { return; }
    if (!sameOrigin(u)) { return; }
    var fd;
    try { fd = new FormData(f); } catch (e) { return; }
    if (submitter && submitter.name && !fd.has(submitter.name)) { fd.append(submitter.name, submitter.value); }
    ev.preventDefault();
    if (method === 'GET') {
      var p = new URLSearchParams();
      fd.forEach(function (v, k) { p.append(k, typeof v === 'string' ? v : (v && v.name) || ''); });
      u.search = p.toString();
      go(u.href);
      return;
    }
    var enc = ((submitter && submitter.getAttribute('formenctype')) || f.getAttribute('enctype') || '').toLowerCase();
    var body;
    if (enc === 'multipart/form-data') { body = fd; }
    else {
      body = new URLSearchParams();
      fd.forEach(function (v, k) { body.append(k, typeof v === 'string' ? v : (v && v.name) || ''); });
    }
    go(u.href, { method: 'POST', body: body });
  });

  W.addEventListener('popstate', function (ev) {
    var path = location.pathname + location.search;
    if (path === lastPath && !(ev.state && ev.state.lx)) { return; }   // only the #hash changed
    var y = ev.state && ev.state.y != null ? ev.state.y : 0;
    go(location.href, { push: false, scrollY: y });
  });

  /* ---------- broken pictures (the old art may be missing on a server): hide them instead of a broken icon ---------- */
  function hideBrokenImages() {
    qsa('img').forEach(function (i) {
      if (i.getAttribute('src') && i.complete && i.naturalWidth === 0) { i.classList.add('lx-noimg'); }
    });
  }
  D.addEventListener('error', function (ev) {
    var t = ev.target;
    if (t && t.tagName === 'IMG') { t.classList.add('lx-noimg'); }
  }, true);

  if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }
  try { history.replaceState({ lx: 1, y: 0 }, '', location.href); } catch (e) {}
  if (D.readyState === 'loading') { D.addEventListener('DOMContentLoaded', function () { hideBrokenImages(); scheduleRefresh(D, location.href); }); }
  else { hideBrokenImages(); scheduleRefresh(D, location.href); }
  W.addEventListener('load', hideBrokenImages);

  LX.go = go;
  LX.fetchPage = fetchPage;
  LX.render = render;
  LX.toast = toast;
  LX.busy = busy;
})();
