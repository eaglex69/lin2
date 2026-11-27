/**
 * main.js — связывает Canvas, AJAX API и панели.
 *
 * Поток: bootstrap -> (нет входа: экран входа) -> выбор зоны -> клик по
 * монстру -> бой (ходы через /api/battle/attack) -> награды, уровни.
 * Cookie недоступны (iframe) — sid автоматически уходит в URL запросов.
 */
import { api, setSession, ApiError } from './api.js';
import { WorldCanvas } from './canvas.js';
import {
  Panel, toast, loading, esc, fmt,
  renderCharacter, renderShop, renderInventory, renderChat, chatLine, renderRating, renderNews,
} from './ui.js';

const state = {
  boot: null,
  char: null,
  account: null,
  zone: null,
  zones: [],
  mobs: [],
  battle: null,
  chatChannel: 'global',
  chatLastId: 0,
  shopKind: 'all',
  inventory: [],
  equipped: [],
  busy: false,
  sound: false,
};

const $ = (sel) => document.querySelector(sel);
const el = {
  auth: $('#auth'),
  authError: $('#auth-error'),
  hudBars: $('#hud-bars'),
  adena: $('#hud-adena'),
  online: $('#hud-online'),
  zone: $('#hud-zone'),
  zoneLabel: $('#zone-label'),
  battle: $('#battle'),
  battleMob: $('#battle-mob'),
  battleTurn: $('#battle-turn'),
  battleMobHp: $('#battle-mob-hp'),
  battleLog: $('#battle-log'),
  toast: $('#toast'),
};

let world = null;
let panel = null;

// --------------------------------------------------------------------- звук
let audioCtx = null;
function beep(freq = 440, dur = 0.08, type = 'sine', gain = 0.05) {
  if (!state.sound) return;
  try {
    audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
    const osc = audioCtx.createOscillator();
    const g = audioCtx.createGain();
    osc.type = type;
    osc.frequency.value = freq;
    g.gain.value = gain;
    osc.connect(g).connect(audioCtx.destination);
    osc.start();
    g.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + dur);
    osc.stop(audioCtx.currentTime + dur);
  } catch { /* звук не критичен */ }
}

// ------------------------------------------------------------------ утилиты
function errText(e) {
  if (e instanceof ApiError) return e.message;
  return (e && e.message) || 'Неизвестная ошибка';
}

function handleError(e) {
  if (e instanceof ApiError && e.status === 401) {
    toast('Нужно войти в игру', 'warn');
    showAuth();
    return;
  }
  toast(errText(e), 'error');
}

// ---------------------------------------------------------------- начальный
async function boot() {
  loading(true);
  try {
    const data = await api.bootstrap();
    setSession(data);
    state.boot = data;
    state.account = data.account;
    state.char = data.character;
    state.zones = data.zones || [];
    el.online.textContent = `online: ${data.online || 0}`;

    if (state.char) {
      await enterGame();
    } else {
      showAuth();
    }
  } catch (e) {
    toast('Не удалось загрузить игру: ' + errText(e), 'error');
    showAuth();
  } finally {
    loading(false);
  }
}

function showAuth() {
  el.auth.hidden = false;
  el.hudBars.hidden = true;
  el.adena.hidden = true;
  el.zone.hidden = true;
  document.querySelectorAll('[data-panel="char"],[data-panel="shop"],[data-panel="bag"]').forEach((b) => { b.hidden = true; });
  if (panel) panel.close();
}

async function enterGame() {
  el.auth.hidden = true;
  el.hudBars.hidden = false;
  el.adena.hidden = false;
  el.zone.hidden = false;
  document.querySelectorAll('[data-panel="char"],[data-panel="shop"],[data-panel="bag"]').forEach((b) => { b.hidden = false; });

  updateHud(state.char);
  world.setHero(state.char);

  const city = state.char.city && state.zones.find((z) => z.key === state.char.city) ? state.char.city : 'Giran';
  await loadZone(city);

  // восстановить бой, если он был. БАГ: открывался только последний бой, а
  // остальные (до 3 активных) оставались «невидимыми» и вечно занимали лимит.
  // Теперь в HUD показываем, если боёв несколько, и подсказываем, как их закрыть.
  try {
    const cur = await api.battleCurrent();
    const list = cur.battles && cur.battles.length ? cur.battles : (cur.battle ? [cur.battle] : []);
    if (list.length) openBattle(list[list.length - 1]);
    if (list.length > 1) {
      toast(`Незавершённых боёв: ${list.length}. Откройте карту и завершите их.`, 'warn');
    }
  } catch { /* не критично */ }

  const urlPanel = new URLSearchParams(location.search).get('panel');
  if (urlPanel) openPanel(urlPanel);
}

// ---------------------------------------------------------------------- HUD
function updateHud(char) {
  if (!char) return;
  state.char = char;
  const set = (id, pct, text) => {
    const bar = document.getElementById(id);
    const label = document.getElementById(id + '-text');
    if (bar) bar.style.width = Math.max(0, Math.min(100, pct)) + '%';
    if (label) label.textContent = text;
  };
  const pct = (v, max) => (max > 0 ? (v / max) * 100 : 0);
  set('bar-hp', pct(char.hp, char.hp_max), `HP ${fmt(char.hp)}/${fmt(char.hp_max)}`);
  set('bar-mp', pct(char.mp, char.mp_max), `MP ${fmt(char.mp)}/${fmt(char.mp_max)}`);
  set('bar-exp', char.exp_percent, `EXP ${char.exp_percent}% · ${char.level} ур.`);
  el.adena.textContent = fmt(char.adena) + ' адены';
  if (world) world.setHero(char);
}

// --------------------------------------------------------------------- зоны
async function loadZone(zoneKey) {
  loading(true);
  try {
    const data = await api.zone(zoneKey);
    state.zone = data.zone;
    state.mobs = data.mobs || [];
    updateHud(data.character);
    world.setZone(data.zone);
    world.setMobs(state.mobs);
    // state.char может быть null (не вошли) — раньше это давало TypeError
    el.zoneLabel.textContent = `${data.zone.label} · ур. ${state.char ? state.char.level : '—'}`;
    el.zone.hidden = false;
    panel?.close();
    if (panel && panel.current === 'map') openPanel('map');
    localStorage.setItem('miniage.zone', zoneKey);
  } catch (e) {
    // зона закрыта по уровню — показываем карту выбора
    if (e instanceof ApiError && e.code === 'level_locked') {
      toast(e.message, 'warn');
      openPanel('map');
    } else {
      handleError(e);
    }
  } finally {
    loading(false);
  }
}

// --------------------------------------------------------------------- бой
async function startBattle(mob) {
  if (state.busy) return;
  state.busy = true;
  loading(true);
  try {
    const data = await api.battleStart(mob.id);
    updateHud(data.character);
    openBattle(data.battle);
    beep(320, 0.08, 'square');
  } catch (e) {
    handleError(e);
  } finally {
    state.busy = false;
    loading(false);
  }
}

function openBattle(battle) {
  if (!battle) return;
  state.battle = battle;
  el.battle.hidden = false;
  el.battleMob.textContent = `${battle.mob ? battle.mob.name : 'Монстр'} [${battle.mob ? battle.mob.level : '?'}]`;
  el.battleTurn.textContent = `ход ${battle.turn}`;
  const pct = battle.mob ? (battle.mob_hp / Math.max(1, battle.mob.hp)) * 100 : 100;
  el.battleMobHp.style.width = Math.max(0, Math.min(100, pct)) + '%';
  el.battleLog.innerHTML = battle.log.map(logLine).join('');
  el.battleLog.scrollTop = el.battleLog.scrollHeight;

  document.getElementById('btn-attack').disabled = battle.state !== 'active';
  document.getElementById('btn-flee').disabled = battle.state !== 'active';

  if (battle.state !== 'active') {
    setTimeout(() => {
      if (state.battle && state.battle.id === battle.id && battle.state !== 'active') closeBattle();
    }, 1800);
  }
}

function logLine(entry) {
  if (entry.t === 'player') {
    return `<div class="log-line log-player">Вы нанесли <b>${entry.dmg}</b> урона${entry.crit ? ' <span class="crit">КРИТ!</span>' : ''}</div>`;
  }
  if (entry.t === 'mob') {
    return `<div class="log-line log-mob">Монстр ударил на <b>${entry.dmg}</b></div>`;
  }
  return `<div class="log-line log-system">${esc(entry.text || '')}</div>`;
}

async function attack() {
  if (!state.battle || state.busy) return;
  state.busy = true;
  try {
    const data = await api.battleAttack(state.battle.id);
    const prevLogLen = state.battle.log.length;
    const battle = data.battle;
    updateHud(data.character);

    // эффекты для новой части лога
    const heroPos = { x: world.view.w / 2, y: world.view.h - 96 };
    const mob = state.mobs.find((m) => m.id === battle.mob.id);
    for (const entry of battle.log.slice(prevLogLen)) {
      if (entry.t === 'player') {
        if (mob) {
          world.burst(mob.x, mob.y, '#ffd166', entry.crit ? 26 : 14);
          world.floatText(mob.x, mob.y - 28, `-${entry.dmg}`, entry.crit ? '#ff6b6b' : '#ffe9a8', entry.crit ? 24 : 18);
        }
        beep(entry.crit ? 720 : 520, 0.06, 'square');
      } else if (entry.t === 'mob') {
        world.floatText(heroPos.x, heroPos.y - 40, `-${entry.dmg}`, '#ff8787');
        world.burst(heroPos.x, heroPos.y, '#ff6b6b', 10);
        beep(180, 0.09, 'sawtooth');
        document.getElementById('game-canvas').classList.add('hit');
        setTimeout(() => document.getElementById('game-canvas').classList.remove('hit'), 180);
      }
    }

    state.battle = battle;
    openBattle(battle);

    if (battle.state === 'won') {
      const r = battle.rewards || {};
      toast(`Победа! +${fmt(r.exp)} EXP, +${fmt(r.adena)} адены${r.levelup ? ` · Новый уровень ${r.level}!` : ''}`, 'success');
      beep(880, 0.18, 'sine', 0.06);
      if (r.levelup) setTimeout(() => beep(1180, 0.22, 'sine', 0.06), 160);
      // обновляем сетку зоны (респавн)
      setTimeout(() => state.zone && loadZone(state.zone.key), 2200);
    } else if (battle.state === 'lost') {
      toast('Вы погибли. Отдохните в таверне.', 'error');
      beep(140, 0.4, 'sawtooth', 0.05);
    }
  } catch (e) {
    handleError(e);
  } finally {
    state.busy = false;
  }
}

async function flee() {
  if (!state.battle || state.busy) return;
  state.busy = true;
  try {
    await api.battleFlee(state.battle.id);
    closeBattle();
    toast('Вы отступили', 'info');
  } catch (e) {
    handleError(e);
  } finally {
    state.busy = false;
  }
}

function closeBattle() {
  el.battle.hidden = true;
  state.battle = null;
}

// ------------------------------------------------------------------ панели
async function openPanel(name) {
  if (!panel) return;
  if (name === 'map') return renderMapPanel();
  if (!state.char && name !== 'chat' && name !== 'top' && name !== 'news') {
    toast('Сначала войдите в игру', 'warn');
    return showAuth();
  }
  loading(true);
  try {
    switch (name) {
      case 'char': {
        const inv = await api.inventory();
        state.inventory = inv.items || [];
        state.equipped = state.inventory.filter((i) => i.equipped);
        updateHud(inv.character);
        panel.open('char', 'Персонаж', renderCharacter(inv.character || state.char, state.equipped));
        bindCharActions();
        break;
      }
      case 'shop': {
        const data = await api.shop(state.shopKind);
        panel.open('shop', 'Магазин', renderShop(data.items || [], data.character || state.char));
        bindShop();
        break;
      }
      case 'bag': {
        const data = await api.inventory();
        state.inventory = data.items || [];
        updateHud(data.character);
        panel.open('bag', 'Инвентарь', renderInventory(state.inventory, data.character || state.char));
        bindBag();
        break;
      }
      case 'chat': {
        const data = await api.chat(state.chatChannel, 0);
        const msgs = data.messages || [];
        state.chatLastId = msgs.length ? msgs[msgs.length - 1].id : 0;
        panel.open('chat', 'Чат', renderChat(msgs, state.chatChannel));
        bindChat();
        break;
      }
      case 'top': {
        const data = await api.rating();
        panel.open('top', 'Рейтинг', renderRating(data.rating || []));
        break;
      }
      case 'news': {
        const data = await api.news();
        panel.open('news', 'Новости', renderNews(data.news || []));
        break;
      }
      default:
        renderMapPanel();
    }
  } catch (e) {
    handleError(e);
  } finally {
    loading(false);
  }
}

function renderMapPanel() {
  if (!panel) return;
  const zones = state.zones || [];
  const html = `
    <p class="muted">Выберите локацию. Монстры появляются на карте — кликните по ним, чтобы начать бой.</p>
    <ul class="zones">
      ${zones.map((z) => `
        <li class="zone ${z.locked ? 'locked' : ''} ${state.zone && state.zone.key === z.key ? 'current' : ''}" data-zone="${esc(z.key)}">
          <span class="zone-dot" style="background:${esc(z.color)}"></span>
          <div class="zone-main">
            <b>${esc(z.label)}</b>
            <small>${z.mobs} монстров · с ${z.min_level} ур.${z.locked ? ' · закрыто' : ''}</small>
          </div>
        </li>`).join('')}
    </ul>
    <div class="map-legend">
      <span>🖱 клик по монстру</span><span>⚔ бой пошаговый</span><span>💬 чат — кнопка слева</span>
    </div>`;
  panel.open('map', state.zone ? state.zone.label : 'Карта', html);
  panel.body.querySelectorAll('[data-zone]').forEach((li) => {
    li.addEventListener('click', () => {
      if (li.classList.contains('locked')) return toast('Зона закрыта по уровню', 'warn');
      loadZone(li.dataset.zone);
    });
  });
}

function bindCharActions() {
  const btn = panel.body.querySelector('[data-action="heal"]');
  if (btn) {
    btn.addEventListener('click', async () => {
      try {
        loading(true);
        const data = await api.heal();
        updateHud(data.character);
        toast(data.cost ? `Реанимация за ${fmt(data.cost)} адены` : 'Полностью восстановлен', 'success');
        openPanel('char');
      } catch (e) { handleError(e); } finally { loading(false); }
    });
  }
}

function bindShop() {
  panel.body.querySelectorAll('[data-kind]').forEach((t) => {
    t.addEventListener('click', () => {
      state.shopKind = t.dataset.kind;
      openPanel('shop');
    });
  });
  panel.body.querySelectorAll('[data-buy]').forEach((b) => {
    b.addEventListener('click', async () => {
      try {
        loading(true);
        const data = await api.buy(b.dataset.buy, 1);
        updateHud(data.character);
        toast(`Куплено: ${data.item.name} за ${fmt(data.paid)}`, 'success');
        beep(640, 0.07, 'triangle');
        // обновляем витрину: цена/наличие могли измениться, важно для UX
        openPanel('shop');
      } catch (e) { handleError(e); } finally { loading(false); }
    });
  });
}

function bindBag() {
  panel.body.querySelectorAll('[data-equip]').forEach((b) => {
    b.addEventListener('click', async () => {
      try {
        loading(true);
        const data = await api.equip(Number(b.dataset.equip), b.dataset.on === '1');
        updateHud(data.character);
        toast(b.dataset.on === '1' ? 'Надето' : 'Снято', 'info');
        openPanel('bag');
      } catch (e) { handleError(e); } finally { loading(false); }
    });
  });
  panel.body.querySelectorAll('[data-use]').forEach((b) => {
    b.addEventListener('click', async () => {
      try {
        loading(true);
        const data = await api.useItem(Number(b.dataset.use));
        updateHud(data.char);
        toast('Использовано', 'success');
        openPanel('bag');
      } catch (e) { handleError(e); } finally { loading(false); }
    });
  });
  panel.body.querySelectorAll('[data-sell]').forEach((b) => {
    b.addEventListener('click', async () => {
      try {
        loading(true);
        const data = await api.sell(Number(b.dataset.sell), 1);
        updateHud(data.character);
        toast(`Продано за ${fmt(data.refund)} адены`, 'info');
        openPanel('bag');
      } catch (e) { handleError(e); } finally { loading(false); }
    });
  });
}

function bindChat() {
  const log = document.getElementById('chat-log');
  if (log) log.scrollTop = log.scrollHeight;

  panel.body.querySelectorAll('[data-channel]').forEach((t) => {
    t.addEventListener('click', () => {
      state.chatChannel = t.dataset.channel;
      state.chatLastId = 0;
      openPanel('chat');
    });
  });

  const form = document.getElementById('chat-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const input = form.querySelector('input[name="text"]');
      const text = input.value.trim();
      if (!text) return;
      try {
        const data = await api.chatSend(text, state.chatChannel);
        input.value = '';
        addChatLine(data.message);
      } catch (e) { handleError(e); }
    });
  }
}

function addChatLine(m) {
  const log = document.getElementById('chat-log');
  if (!log || !m || !m.id || m.id <= state.chatLastId) return;
  state.chatLastId = m.id;
  log.insertAdjacentHTML('beforeend', chatLine(m));
  log.scrollTop = log.scrollHeight;
}

// ---- поллинг чата, когда открыта панель чата
setInterval(async () => {
  if (!panel || panel.current !== 'chat' || !state.char) return;
  try {
    const data = await api.chat(state.chatChannel, state.chatLastId);
    for (const m of data.messages || []) addChatLine(m);
  } catch { /* тихо */ }
}, 3000);

// ---- восстановление HP/MP
// Источник истины — сервер (GameService::applyRegen): он начисляет реген по
// времени последнего визита. Клиент только подтягивает актуальные цифры,
// иначе HUD показывал одно, а бой считал по другим значениям.
setInterval(async () => {
  if (!state.char || state.char.hp <= 0 || state.busy || state.battle) return;
  if (state.char.hp >= state.char.hp_max && state.char.mp >= state.char.mp_max) return;
  try {
    const data = await api.bootstrap();
    if (data.character) {
      state.char = data.character;
      updateHud(state.char);
    }
  } catch { /* тихо: сеть может моргнуть */ }
}, 10000);

// ------------------------------------------------------------------- события
function bindAuth() {
  document.querySelectorAll('.tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');
      const isLogin = tab.dataset.tab === 'login';
      document.getElementById('form-login').hidden = !isLogin;
      document.getElementById('form-register').hidden = isLogin;
      el.authError.textContent = '';
    });
  });

  document.getElementById('form-login').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = e.target;
    el.authError.textContent = '';
    loading(true);
    try {
      const data = await api.login(f.nick.value.trim(), f.pass.value);
      setSession(data);
      toast('Добро пожаловать!', 'success');
      await reloadSession();
    } catch (err) {
      el.authError.textContent = errText(err);
      beep(160, 0.12, 'sawtooth');
    } finally {
      loading(false);
    }
  });

  document.getElementById('form-register').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = e.target;
    el.authError.textContent = '';
    loading(true);
    try {
      const data = await api.register({
        nick: f.nick.value.trim(),
        pass: f.pass.value,
        email: f.email.value.trim(),
        race: f.race.value,
        class: f.class.value,
        gender: 'm',
      });
      setSession(data);
      toast('Аккаунт создан!', 'success');
      await reloadSession();
    } catch (err) {
      el.authError.textContent = errText(err);
    } finally {
      loading(false);
    }
  });
}

async function reloadSession() {
  const data = await api.bootstrap();
  setSession(data);
  state.account = data.account;
  state.char = data.character;
  state.zones = data.zones || [];
  el.online.textContent = `online: ${data.online || 0}`;
  if (state.char) {
    await enterGame();
  } else {
    showAuth();
  }
}

function bindNav() {
  document.querySelectorAll('.nav-btn').forEach((b) => {
    b.addEventListener('click', () => {
      if (b.classList.contains('active') && panel && !panel.root.hidden && panel.current === b.dataset.panel) {
        panel.close();
        return;
      }
      openPanel(b.dataset.panel);
    });
  });

  document.getElementById('btn-help').addEventListener('click', () => {
    panel.open('help', 'Как играть', `
      <ol class="help">
        <li>Выберите зону слева (🗺) — откроется карта с монстрами.</li>
        <li>Кликните по монстру на canvas — начнётся пошаговый бой.</li>
        <li>Кнопка «Атаковать» внизу — удар; «Отступить» — выйти из боя.</li>
        <li>Магазин (🛒) — оружие, броня, зелья. Инвентарь (🎒) — надеть/использовать.</li>
        <li>Чат (💬) — общий канал. Рейтинг (🏆) — топ игроков.</li>
      </ol>
      <p class="muted">Sid сессии: <code>${esc((state.boot && state.boot.session && state.boot.session.id) || '')}</code></p>`);
  });

  document.getElementById('btn-sound').addEventListener('click', (e) => {
    state.sound = !state.sound;
    e.currentTarget.classList.toggle('on', state.sound);
    toast(state.sound ? 'Звук включён' : 'Звук выключен', 'info');
    if (state.sound) beep(600, 0.08, 'sine');
  });

  document.getElementById('btn-attack').addEventListener('click', attack);
  document.getElementById('btn-flee').addEventListener('click', flee);
}

// -------------------------------------------------------------- инициализация
function init() {
  const canvas = document.getElementById('game-canvas');
  world = new WorldCanvas(canvas);
  world.onMobClick = (mob) => startBattle(mob);
  panel = new Panel();

  bindAuth();
  bindNav();
  boot();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
