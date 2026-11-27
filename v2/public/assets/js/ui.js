/**
 * ui.js — панели интерфейса: персонаж, магазин, инвентарь, чат, рейтинг, новости.
 * Всё рендерится в DOM (панель справа), игровое поле остаётся canvas.
 */

export function esc(s) {
  return String(s ?? '').replace(/[&<>"'`=\/]/g, (c) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '`': '&#96;',
    '=': '&#61;',
    '/': '&#47;',
  }[c]));
}

export function fmt(n) {
  return Number(n || 0).toLocaleString('ru-RU');
}

export function toast(text, kind = 'info') {
  const el = document.getElementById('toast');
  if (!el) return;
  el.textContent = text;
  el.className = `toast toast-${kind}`;
  el.hidden = false;
  clearTimeout(el._t);
  el._t = setTimeout(() => { el.hidden = true; }, 2600);
}

export function loading(on) {
  const el = document.getElementById('loading');
  if (el) el.hidden = !on;
}

export class Panel {
  constructor() {
    this.root = document.getElementById('panel');
    this.title = document.getElementById('panel-title');
    this.body = document.getElementById('panel-body');
    this.current = null;
    document.getElementById('panel-close').addEventListener('click', () => this.close());
  }

  open(name, title, html) {
    this.current = name;
    this.title.textContent = title;
    this.body.innerHTML = html;
    this.root.hidden = false;
    document.querySelectorAll('.nav-btn').forEach((b) => {
      b.classList.toggle('active', b.dataset.panel === name);
    });
    return this.body;
  }

  close() {
    this.root.hidden = true;
    this.current = null;
    document.querySelectorAll('.nav-btn').forEach((b) => {
      b.classList.toggle('active', b.dataset.panel === 'map');
    });
  }
}

/** Панель персонажа с экипировкой. */
export function renderCharacter(char, equipped = []) {
  if (!char) return '<p class="muted">Нет персонажа.</p>';
  const slots = ['weapon', 'armor', 'helmet', 'boots', 'ring'];
  const bySlot = {};
  for (const it of equipped) bySlot[it.slot] = it;

  const row = (label, value) => `<div class="stat"><span>${label}</span><b>${value}</b></div>`;

  return `
  <div class="char-card">
    <div class="char-name">${esc(char.name)} <small>${esc(char.race_label)} · ${esc(char.class_label)}</small></div>
    <div class="char-level">Уровень <b>${char.level}</b> · EXP ${char.exp_percent}%</div>
    ${row('HP', `${fmt(char.hp)} / ${fmt(char.hp_max)}`)}
    ${row('MP', `${fmt(char.mp)} / ${fmt(char.mp_max)}`)}
    ${row('Физ. атака', fmt(char.p_atk))}
    ${row('Мат. атака', fmt(char.m_atk))}
    ${row('Физ. защита', fmt(char.p_def))}
    ${row('Мат. защита', fmt(char.m_def))}
    ${row('Крит', char.crit + '%')}
    ${row('Адена', fmt(char.adena))}
    ${row('Побед / поражений', `${char.wins} / ${char.losses}`)}
  </div>
  <h3>Экипировка</h3>
  <ul class="slots">
    ${slots.map((s) => `<li><span>${slotLabel(s)}</span><b>${bySlot[s] ? esc(bySlot[s].name) : '—'}</b></li>`).join('')}
  </ul>
  <button class="btn btn-primary" data-action="heal">Отдых в таверне (лечение)</button>`;
}

function slotLabel(slot) {
  return ({ weapon: 'Оружие', armor: 'Доспех', helmet: 'Шлем', boots: 'Обувь', ring: 'Кольцо' })[slot] || slot;
}

/** Каталог магазина. */
export function renderShop(items, char) {
  const kinds = [['all', 'Все'], ['weapon', 'Оружие'], ['armor', 'Броня'], ['potion', 'Зелья'], ['material', 'Ресурсы']];
  return `
  <div class="shop-head">
    <div class="tabs tabs-sm">${kinds.map(([k, l]) => `<button class="tab ${k === 'all' ? 'active' : ''}" data-kind="${k}">${l}</button>`).join('')}</div>
    <div class="adena-badge">${fmt(char ? char.adena : 0)} адены</div>
  </div>
  <ul class="items">
    ${items.map((it) => `
      <li class="item" data-code="${esc(it.code)}">
        <div class="item-icon icon-${esc(it.icon)}"></div>
        <div class="item-main">
          <div class="item-name">${esc(it.name)}</div>
          <div class="item-meta">${it.level_req} ур. ${statLine(it)}</div>
        </div>
        <div class="item-buy">
          <span class="price">${fmt(it.price)}</span>
          <button class="btn btn-sm" data-buy="${esc(it.code)}">Купить</button>
        </div>
      </li>`).join('')}
  </ul>`;
}

export function statLine(it) {
  const parts = [];
  if (it.p_atk) parts.push(`P.Atk +${it.p_atk}`);
  if (it.m_atk) parts.push(`M.Atk +${it.m_atk}`);
  if (it.p_def) parts.push(`P.Def +${it.p_def}`);
  if (it.m_def) parts.push(`M.Def +${it.m_def}`);
  if (it.hp_bonus) parts.push(`HP +${it.hp_bonus}`);
  if (it.mp_bonus) parts.push(`MP +${it.mp_bonus}`);
  return parts.join(' · ') || (it.kind === 'material' ? 'материал' : '—');
}

/** Инвентарь с экипировкой/использованием/продажей. */
export function renderInventory(items, char) {
  if (!items.length) return '<p class="muted">Инвентарь пуст. Загляните в магазин.</p>';
  return `
  <div class="shop-head"><div class="adena-badge">${fmt(char ? char.adena : 0)} адены</div></div>
  <ul class="items">
    ${items.map((it) => `
      <li class="item ${it.equipped ? 'is-equipped' : ''}">
        <div class="item-icon icon-${esc(it.icon)}"></div>
        <div class="item-main">
          <div class="item-name">${esc(it.name)} ${it.qty > 1 ? `<span class="qty">×${it.qty}</span>` : ''}</div>
          <div class="item-meta">${statLine(it)}</div>
        </div>
        <div class="item-actions">
          ${it.slot !== 'none' ? `<button class="btn btn-sm" data-equip="${it.inv_id}" data-on="${it.equipped ? '0' : '1'}">${it.equipped ? 'Снять' : 'Надеть'}</button>` : ''}
          ${it.kind === 'potion' ? `<button class="btn btn-sm" data-use="${it.inv_id}">Исп.</button>` : ''}
          <button class="btn btn-sm btn-ghost" data-sell="${it.inv_id}">Продать</button>
        </div>
      </li>`).join('')}
  </ul>`;
}

/** Чат. */
export function renderChat(messages, channel = 'global') {
  const channels = [['global', 'Общий'], ['trade', 'Торговля'], ['clan', 'Клан'], ['system', 'Система']];
  return `
  <div class="chat">
    <div class="tabs tabs-sm">${channels.map(([c, l]) => `<button class="tab ${c === channel ? 'active' : ''}" data-channel="${c}">${l}</button>`).join('')}</div>
    <div class="chat-log" id="chat-log">
      ${messages.map(chatLine).join('')}
    </div>
    <form class="chat-form" id="chat-form">
      <input name="text" maxlength="240" placeholder="Сообщение…" autocomplete="off">
      <button class="btn btn-sm btn-primary" type="submit">→</button>
    </form>
  </div>`;
}

export function chatLine(m) {
  const cls = m.author === 'Система' ? 'msg-system' : '';
  return `<div class="chat-line ${cls}"><span class="time">${esc(m.time)}</span> <b>${esc(m.author)}</b>: ${esc(m.text)}</div>`;
}

/** Рейтинг. */
export function renderRating(rows) {
  return `
  <table class="table">
    <thead><tr><th>#</th><th>Персонаж</th><th>Ур.</th><th>Победы</th></tr></thead>
    <tbody>${rows.map((r, i) => `
      <tr><td>${i + 1}</td><td>${esc(r.name)}<small> ${esc(r.race)} · ${esc(r.class)}</small></td><td>${r.level}</td><td>${r.wins}</td></tr>`).join('')}
    </tbody>
  </table>`;
}

/** Новости. */
export function renderNews(list) {
  return list.map((n) => `
    <article class="news">
      <h3>${esc(n.title)}</h3>
      <div class="news-meta">${esc(n.date)} · ${esc(n.author)}</div>
      <p>${esc(n.body)}</p>
    </article>`).join('');
}
