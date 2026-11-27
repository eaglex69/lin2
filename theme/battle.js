/*
 * Miniage — animated battle screen.
 *
 * Works on the markup printed by inc/lin2_ui.php (.lx-bt, .lx-side with data-hp / data-max, .lx-card, .lx-atk ...).
 * Click on the round attack button (or a skill) -> the sword swings at once, the request runs in the background, then:
 *   - a gold "-damage" floats over the portrait card, the enemy HP bar shrinks and leaves a yellow trail,
 *   - the enemy answers: red "-damage" over the player panel, the player HP bar shrinks,
 *   - the log, the skill list and the header bars are replaced by the fresh ones from the server answer,
 *   - the attack button lights up again ("ВАШ ХОД").
 * When the fight is over (win / defeat / flee) the last blow is played and the result page is shown.
 * Other pages that contain battle panels (pk, arena, castle) get the same bar animation on every page change.
 * Without ajax.js (or without JS) the buttons are plain links and the server logic is exactly the same.
 */
(function () {
  'use strict';
  var W = window, D = document;
  var LX = W.LX;
  if (!LX || !LX.ready || !W.requestAnimationFrame) { return; }

  var running = false;

  function $(s, c) { return (c || D).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || D).querySelectorAll(s)); }
  function int(v) { v = parseInt(v, 10); return isNaN(v) ? 0 : v; }
  function fmt(n) { return String(Math.max(0, Math.round(n))).replace(/\B(?=(\d{3})+(?!\d))/g, '\u00a0'); }
  function sleep(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }
  function pct(hp, max) { return max > 0 ? Math.max(0, Math.min(100, hp * 100 / max)) : 0; }

  /** battle numbers of a document (the live page or a parsed answer); null when it has no battle panels */
  function read(ctx) {
    var bt = $('.lx-bt', ctx);
    if (!bt) { return null; }
    var foe = $('.lx-foe', bt), me = $('.lx-me', bt);
    function side(el) { return el ? { hp: int(el.getAttribute('data-hp')), max: int(el.getAttribute('data-max')) } : { hp: 0, max: 1 }; }
    return { id: bt.getAttribute('data-foe') || '', foe: side(foe), me: side(me) };
  }
  /* last known numbers: a turn based fight (pk / arena / castle) has pages without panels between the rounds */
  var last = null;
  function remember(cur) { if (cur) { cur.t = Date.now(); last = cur; } return cur; }
  LX.snapshot = function () { return remember(read(D)); };
  remember(read(D));

  /* ---------- small effects ---------- */
  function noTransition(el, fn) {
    el.style.webkitTransition = 'none';
    el.style.transition = 'none';
    fn();
    void el.offsetWidth;
    el.style.webkitTransition = '';
    el.style.transition = '';
  }
  function pulse(el, cls, ms) {
    if (!el) { return; }
    el.classList.remove(cls);
    void el.offsetWidth;
    el.classList.add(cls);
    setTimeout(function () { el.classList.remove(cls); }, ms);
  }
  function floatText(parent, text, cls) {
    if (!parent) { return; }
    var d = D.createElement('div');
    d.className = 'lx-fl ' + cls;
    d.textContent = text;
    parent.appendChild(d);
    setTimeout(function () { if (d.parentNode) { d.parentNode.removeChild(d); } }, 1750);
  }
  function tween(el, from, to, ms) {
    if (!el) { return; }
    var t0 = null;
    function step(t) {
      if (t0 === null) { t0 = t; }
      var k = Math.min(1, (t - t0) / ms), e = 1 - Math.pow(1 - k, 3);
      el.textContent = fmt(from + (to - from) * e);
      if (k < 1) { requestAnimationFrame(step); }
    }
    requestAnimationFrame(step);
  }

  /** move a panel to new numbers: the fill goes first, the yellow trail follows later (see the CSS transitions) */
  function setSide(side, hp, max, quick) {
    if (!side) { return; }
    var fill = $('.fill', side), trail = $('.trail', side), num = $('.lx-hpnum', side);
    var from = int(side.getAttribute('data-hp'));
    var to = pct(hp, max), cur = parseFloat(fill.style.width);
    if (isNaN(cur)) { cur = 100; }
    if (quick) {
      noTransition(fill, function () { fill.style.width = to + '%'; });
      noTransition(trail, function () { trail.style.width = to + '%'; });
      if (num) { num.textContent = fmt(hp); }
    } else {
      if (to > cur) { noTransition(trail, function () { trail.style.width = to + '%'; }); }   // healed: no lagging trail
      fill.style.width = to + '%';
      trail.style.width = to + '%';
      tween(num, from, hp, 600);
    }
    side.setAttribute('data-hp', hp);
    side.setAttribute('data-max', max);
  }

  /** replace the parts of the page that change every round (log, skills, header bars, stat numbers) */
  function patch(doc) {
    $$('[data-lx-live]', doc).forEach(function (n) {
      var cur = $('[data-lx-live="' + n.getAttribute('data-lx-live') + '"]');
      if (cur) { cur.innerHTML = n.innerHTML; }
    });
    ['.lx-foe', '.lx-me'].forEach(function (sel) {
      var a = $(sel), b = $(sel, doc);
      if (!a || !b) { return; }
      var sa = $('.lx-stats', a), sb = $('.lx-stats', b);
      if (sa && sb) { sa.innerHTML = sb.innerHTML; }
      var la = $('.lx-ava b', a), lb = $('.lx-ava b', b);
      if (la && lb) { la.textContent = lb.textContent; }
    });
  }

  function setTurn(text, foe) {
    var t = $('#lx-turn');
    if (!t) { return; }
    t.textContent = text;
    if (foe) { t.classList.add('foe'); } else { t.classList.remove('foe'); }
  }

  /* ---------- one round ---------- */
  function swing() {
    var card = $('.lx-card');
    if (!card) { return; }
    pulse($('.lx-slash', card), 'go', 450);
  }

  function round(before, after, doc, kind) {
    var foe = $('.lx-foe'), me = $('.lx-me'), card = $('.lx-card'), atk = $('#lx-atk');
    var dmg = before.foe.hp - after.foe.hp;
    var hurt = before.me.hp - after.me.hp;
    var p = Promise.resolve();
    if (card) { card.classList.add('busy'); }
    if (dmg > 0 || kind === 'hit') {
      pulse($('.lx-flash', card), 'go', 500);
      pulse(card, 'lx-hit', 420);
      floatText(card, dmg > 0 ? '-' + fmt(dmg) : 'Промах', dmg > 0 ? 'card atk' : 'card miss');
    } else if (dmg < 0) {
      floatText(card, '+' + fmt(-dmg), 'card heal');
    }
    setSide(foe, after.foe.hp, after.foe.max);
    p = p.then(function () { return sleep(dmg !== 0 || kind === 'hit' ? 950 : 150); });
    p = p.then(function () {
      if (hurt > 0) {
        setTurn('ХОД ВРАГА', true);
        pulse(me, 'lx-hit', 420);
        floatText(me, '-' + fmt(hurt), 'me');
        setSide(me, after.me.hp, after.me.max);
        return sleep(900);
      }
      if (hurt < 0) {
        floatText(me, '+' + fmt(-hurt), 'me heal');
        setSide(me, after.me.hp, after.me.max);
        return sleep(700);
      }
      setSide(me, after.me.hp, after.me.max, true);
    });
    return p.then(function () {
      patch(doc);
      setTurn('ВАШ ХОД', false);
      if (card) { card.classList.remove('busy'); }
      if (atk) { atk.classList.remove('off'); }
      remember(read(D));
    });
  }

  /** the fight is over: play the last blow, then show the result page */
  function ending(before, res) {
    var end = $('.lx-end', res.doc);
    var kind = end ? end.getAttribute('data-res') : '';
    var foe = $('.lx-foe'), me = $('.lx-me'), card = $('.lx-card');
    if (kind === 'win') {
      pulse($('.lx-flash', card), 'go', 500);
      pulse(card, 'lx-hit', 420);
      floatText(card, '-' + fmt(before.foe.hp), 'card atk');
      setSide(foe, 0, before.foe.max);
      return sleep(1000).then(function () {
        if (card) { card.classList.add('dead'); }
        setTurn('ПОБЕДА', false);
        return sleep(950);
      });
    }
    if (kind === 'lose') {
      pulse(me, 'lx-hit', 420);
      floatText(me, '-' + fmt(before.me.hp), 'me');
      setSide(me, 0, before.me.max);
      setTurn('ПОРАЖЕНИЕ', true);
      return sleep(1100);
    }
    return Promise.resolve();
  }

  function act(a) {
    if (running) { return; }
    var kind = a.getAttribute('data-lx-act') || 'hit';
    var before = read(D);
    if (!before) { LX.go(a.href); return; }
    running = true;
    var atk = $('#lx-atk');
    if (atk) { atk.classList.add('off'); }
    if (kind === 'hit') { swing(); }
    var t0 = Date.now();
    LX.fetchPage(a.href).then(function (res) {
      var after = read(res.doc);
      var pause = Math.max(0, 380 - (Date.now() - t0));
      return sleep(pause).then(function () {
        if (after && after.id === before.id) { return round(before, after, res.doc, kind); }
        return ending(before, res).then(function () {
          LX.render(res.html, res.url, { doc: res.doc, push: 'replace' });
        });
      });
    }).catch(function (err) {
      if (err && err.name === 'AbortError') { return; }
      if (atk) { atk.classList.remove('off'); }
      LX.busy(false);
      location.assign(a.href);              // let the browser do it the old way
    }).then(function () { running = false; });
  }

  D.addEventListener('click', function (ev) {
    if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) { return; }
    var t = ev.target;
    while (t && t !== D && !(t.tagName === 'A' && t.hasAttribute('data-lx-act'))) { t = t.parentNode; }
    if (!t || t === D || !$('.lx-bt')) { return; }
    ev.preventDefault();
    ev.stopPropagation();
    act(t);
  }, true);

  /* ---------- other pages with battle panels (pk, arena, castle): animate the change on every page swap ---------- */
  D.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!ev.defaultPrevented || !f || !f.closest || !f.closest('.lx-duel')) { return; }   // ajax.js has taken the form
    var atk = $('#lx-atk');
    if (atk) {
      atk.classList.add('off');
      setTimeout(function () { atk.classList.remove('off'); }, 9000);
    }
    swing();
  });

  D.addEventListener('lx:render', function (ev) {
    var cur = read(D);
    var prev = (ev.detail && ev.detail.prev) || (last && Date.now() - last.t < 90000 ? last : null);
    remember(cur);
    if (!cur || !prev || prev.id !== cur.id) { return; }
    var foe = $('.lx-foe'), me = $('.lx-me'), card = $('.lx-card');
    var dmg = prev.foe.hp - cur.foe.hp, hurt = prev.me.hp - cur.me.hp;
    // the new page already holds the new numbers: rewind the bars, then let them run
    setSide(foe, prev.foe.hp, cur.foe.max, true);
    setSide(me, prev.me.hp, cur.me.max, true);
    W.requestAnimationFrame(function () {
      W.requestAnimationFrame(function () {
        if (dmg !== 0) {
          floatText(card, dmg > 0 ? '-' + fmt(dmg) : '+' + fmt(-dmg), dmg > 0 ? 'card atk' : 'card heal');
          if (dmg > 0) { pulse(card, 'lx-hit', 420); pulse($('.lx-flash', card), 'go', 500); }
        }
        setSide(foe, cur.foe.hp, cur.foe.max);
        setTimeout(function () {
          if (hurt !== 0) {
            floatText(me, hurt > 0 ? '-' + fmt(hurt) : '+' + fmt(-hurt), hurt > 0 ? 'me' : 'me heal');
            if (hurt > 0) { pulse(me, 'lx-hit', 420); }
          }
          setSide(me, cur.me.hp, cur.me.max);
        }, dmg !== 0 ? 900 : 0);
      });
    });
  });
})();
