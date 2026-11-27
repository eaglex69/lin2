'use strict';
/**
 * db.js — слой данных (аналог inc/db.php + inc/mysql.php + shema.sql)
 *
 * В PHP проект ходил в MySQL напрямую через mysql_query() в каждом файле.
 * Здесь — единый модуль-хранилище. По умолчанию работает ДЕМО-режим:
 * данные в памяти, засеяны в духе shema.sql (таблицы users, account, mobs,
 * shop, lvl, news, chat, clan, inventar, sklad, bank, online...).
 *
 * Подключение настоящей MySQL: см. README.md (раздел "Настоящая MySQL").
 * Схема БД не менялась — shema.sql в корне репозитория подходит как есть.
 */

const crypto = require('crypto');

/** md5 — как в PHP enter.php: $pas = md5($pas) */
function md5(s) {
  return crypto.createHash('md5').update(String(s), 'utf8').digest('hex');
}

// ---------------------------------------------------------------------------
// Таблица lvl (из shema.sql): сколько суммарного exp нужно для уровня.
// Первые значения — дословно из дампа, дальше — формула продолжения кривой.
// ---------------------------------------------------------------------------
const LVL_EXP = [67, 297, 805, 1716, 3154, 5112, 7700, 11000, 15200, 20500,
  27200, 35600, 46000, 59000, 75000, 95000, 120000, 150000, 190000, 240000,
  300000, 380000, 480000, 600000, 750000, 950000, 1200000, 1500000, 1900000,
  2400000, 3000000, 3800000, 4800000, 6000000, 7500000, 9500000, 12000000,
  15000000, 19000000, 24000000, 30000000, 38000000, 48000000, 60000000,
  75000000, 95000000, 120000000, 150000000, 190000000, 240000000, 300000000,
  380000000, 480000000, 600000000, 750000000, 950000000, 1200000000];

function expForLevel(lvl) {
  if (lvl < LVL_EXP.length) return LVL_EXP[lvl];
  return LVL_EXP[LVL_EXP.length - 1] * Math.pow(1.25, lvl - LVL_EXP.length + 1);
}

/** Процент опыта до следующего уровня — порт inc/opit.php */
function expPercent(user) {
  const cur = expForLevel(user.lvl);
  const prev = user.lvl > 0 ? expForLevel(user.lvl - 1) : 0;
  if (cur === prev) return 100;
  const p = Math.round(((user.exp - prev) / (cur - prev)) * 100 * 100) / 100;
  return Math.min(100, Math.max(0, p));
}

// ---------------------------------------------------------------------------
// Сид данных (демо). Поля повторяют shema.sql: users, account, mobs, shop...
// ---------------------------------------------------------------------------
let seq = 1000;
const nextId = () => ++seq;

const db = {
  // account: id, nick, pass(md5), email, ip, ref, lvisit, active(->users.id), sekret
  accounts: [
    { id: 1, nick: 'demo', pass: md5('demo'), email: 'demo@miniage.local', ip: '127.0.0.1', ref: 0, lvisit: 0, active: 1, sekret: '' },
    { id: 2, nick: 'admin', pass: md5('admin'), email: 'admin@miniage.local', ip: '127.0.0.1', ref: 0, lvisit: 0, active: 2, sekret: '' },
  ],

  // users: id, usr, pol, storona, klas, lvl, exp, money, gold, almaz, hp/hpall,
  //        mp/mpall, patt, matt, pdef, mdef, skill, city, clan, wins, loses,
  //        karma, slava, status, st, dat_reg, account, lvisit, ip_br
  users: [
    { id: 1, usr: 'Demo', pol: 'm', storona: 'human', klas: 'fighert', lvl: 10, exp: 18000,
      money: 30000000, gold: 100, almaz: 50, hp: 420, hpall: 520, mp: 180, mpall: 220,
      patt: 95, matt: 40, pdef: 60, mdef: 45, skill: 12, city: 'Giran Castle Town',
      clan: '', wins: 7, loses: 2, arenawins: 1, arenaloses: 0, karma: 0, slava: 0,
      status: 'Новичок Miniage', st: 0, dat_reg: '27.11.2026', account: 1, lvisit: '', ip_br: '',
      zerna: 0, votecoin: 0, dostup: 0, res: 0, post: 0, ups: 0, predatel: 0, ref: '', site: '', time: 0, time_bon: 0, zamujem: '', ali: '', tot_o: 0, lord: '', ps: '', pass: '', ig: 0, wmr: 0, sp: 0, citys: '' },
    { id: 2, usr: 'Admin', pol: 'm', storona: 'human', klas: 'wizard', lvl: 52, exp: 400000000,
      money: 999999999, gold: 9999, almaz: 9999, hp: 5200, hpall: 5200, mp: 3200, mpall: 3200,
      patt: 800, matt: 1200, pdef: 700, mdef: 900, skill: 99, city: 'Aden Castle Town',
      clan: 'Miniage', wins: 999, loses: 0, arenawins: 99, arenaloses: 0, karma: 0, slava: 999,
      status: 'Администратор', st: 3, dat_reg: '01.01.2020', account: 2, lvisit: '', ip_br: '',
      zerna: 0, votecoin: 0, dostup: 5, res: 0, post: 0, ups: 0, predatel: 0, ref: '', site: '', time: 0, time_bon: 0, zamujem: '', ali: '', tot_o: 0, lord: '', ps: '', pass: '', ig: 0, wmr: 0, sp: 0, citys: '' },
    { id: 3, usr: 'Talean', pol: 'm', storona: 'elf', klas: 'fighert', lvl: 76, exp: 900000000,
      money: 500000000, gold: 5000, almaz: 5000, hp: 9800, hpall: 9800, mp: 2400, mpall: 2400,
      patt: 1500, matt: 700, pdef: 1200, mdef: 1000, skill: 80, city: 'Elven Village',
      clan: 'Miniage', wins: 1500, loses: 10, arenawins: 200, arenaloses: 5, karma: 0, slava: 500,
      status: 'Легенда', st: 3, dat_reg: '15.03.2021', account: 2, lvisit: '', ip_br: '',
      zerna: 0, votecoin: 0, dostup: 1, res: 0, post: 0, ups: 0, predatel: 0, ref: '', site: '', time: 0, time_bon: 0, zamujem: '', ali: '', tot_o: 0, lord: '', ps: '', pass: '', ig: 0, wmr: 0, sp: 0, citys: '' },
    { id: 4, usr: 'Kira', pol: 'w', storona: 'darkelf', klas: 'wizard', lvl: 34, exp: 8000000,
      money: 80000000, gold: 800, almaz: 300, hp: 1800, hpall: 1800, mp: 2600, mpall: 2600,
      patt: 320, matt: 520, pdef: 260, mdef: 380, skill: 40, city: 'Dark Elven Village',
      clan: '', wins: 120, loses: 30, arenawins: 10, arenaloses: 8, karma: 0, slava: 120,
      status: 'Охотница', st: 1, dat_reg: '02.06.2024', account: 2, lvisit: '', ip_br: '',
      zerna: 0, votecoin: 0, dostup: 0, res: 0, post: 0, ups: 0, predatel: 0, ref: '', site: '', time: 0, time_bon: 0, zamujem: '', ali: '', tot_o: 0, lord: '', ps: '', pass: '', ig: 0, wmr: 0, sp: 0, citys: '' },
  ],

  // mobs: id, name, lvl, money, exp, hp/hpall, patt, matt, pdef, mdef, okra, status
  mobs: [
    { id: 1, name: 'Ratman Spy', lvl: 5, money: 450, exp: 320, hp: 180, hpall: 180, patt: 28, matt: 22, pdef: 15, mdef: 12, okra: 'Evil Hunting Grounds', status: 'on' },
    { id: 2, name: 'Androscorpio Hunter', lvl: 8, money: 720, exp: 540, hp: 260, hpall: 260, patt: 38, matt: 30, pdef: 22, mdef: 18, okra: 'Evil Hunting Grounds', status: 'on' },
    { id: 3, name: 'Skeleton Warrior', lvl: 10, money: 950, exp: 780, hp: 340, hpall: 340, patt: 48, matt: 35, pdef: 30, mdef: 25, okra: 'Evil Hunting Grounds', status: 'on' },
    { id: 4, name: 'Orc Shaman', lvl: 12, money: 1200, exp: 980, hp: 420, hpall: 420, patt: 55, matt: 70, pdef: 35, mdef: 45, okra: 'The Ant Nest', status: 'on' },
    { id: 5, name: 'Ant Soldier', lvl: 14, money: 1500, exp: 1250, hp: 520, hpall: 520, patt: 65, matt: 50, pdef: 45, mdef: 40, okra: 'The Ant Nest', status: 'on' },
    { id: 6, name: 'Dark Elf Scout', lvl: 16, money: 1900, exp: 1550, hp: 640, hpall: 640, patt: 78, matt: 85, pdef: 55, mdef: 60, okra: 'The Ant Nest', status: 'on' },
    { id: 7, name: 'Golem Guardian', lvl: 20, money: 2600, exp: 2100, hp: 900, hpall: 900, patt: 105, matt: 80, pdef: 85, mdef: 70, okra: 'Silent Valley', status: 'on' },
    { id: 8, name: 'Wraith Lord', lvl: 24, money: 3400, exp: 2800, hp: 1150, hpall: 1150, patt: 125, matt: 150, pdef: 95, mdef: 110, okra: 'Silent Valley', status: 'on' },
    { id: 9, name: 'Death Knight', lvl: 28, money: 4600, exp: 3800, hp: 1500, hpall: 1500, patt: 150, matt: 130, pdef: 120, mdef: 110, okra: 'Silent Valley', status: 'on' },
    { id: 10, name: 'Dragon Hatchling', lvl: 32, money: 6200, exp: 5200, hp: 1950, hpall: 1950, patt: 180, matt: 200, pdef: 140, mdef: 150, okra: 'Dragon Valley', status: 'on' },
    { id: 11, name: 'Ancient Wyvern', lvl: 38, money: 8500, exp: 7200, hp: 2600, hpall: 2600, patt: 220, matt: 240, pdef: 175, mdef: 190, okra: 'Dragon Valley', status: 'on' },
    { id: 12, name: 'Antharas Minion', lvl: 45, money: 12000, exp: 10500, hp: 3600, hpall: 3600, patt: 280, matt: 300, pdef: 220, mdef: 240, okra: 'Dragon Valley', status: 'on' },
  ],

  // shop: id, city, tip, tip2, name, cena, patt, matt, pdef, mdef, nlvl
  shop: [
    { id: 1, city: 'Giran Castle Town', tip: 'weapon', tip2: 'ydar', name: 'Steel Sword', cena: 250000, patt: 45, matt: 10, pdef: 0, mdef: 0, nlvl: 1 },
    { id: 2, city: 'Giran Castle Town', tip: 'weapon', tip2: 'ydar', name: 'Knight Blade', cena: 1200000, patt: 95, matt: 25, pdef: 0, mdef: 0, nlvl: 10 },
    { id: 3, city: 'Giran Castle Town', tip: 'weapon', tip2: 'ps', name: 'Apprentice Staff', cena: 300000, patt: 15, matt: 60, pdef: 0, mdef: 0, nlvl: 1 },
    { id: 4, city: 'Giran Castle Town', tip: 'weapon', tip2: 'ps', name: 'Mage Staff', cena: 1500000, patt: 30, matt: 130, pdef: 0, mdef: 0, nlvl: 10 },
    { id: 5, city: 'Giran Castle Town', tip: 'body', tip2: '', name: 'Leather Armor', cena: 180000, patt: 0, matt: 0, pdef: 25, mdef: 20, nlvl: 1 },
    { id: 6, city: 'Giran Castle Town', tip: 'body', tip2: '', name: 'Steel Plate', cena: 900000, patt: 0, matt: 0, pdef: 60, mdef: 50, nlvl: 10 },
    { id: 7, city: 'Giran Castle Town', tip: 'golova', tip2: '', name: 'Iron Helmet', cena: 80000, patt: 0, matt: 0, pdef: 12, mdef: 10, nlvl: 1 },
    { id: 8, city: 'Giran Castle Town', tip: 'shit', tip2: '', name: 'Tower Shield', cena: 220000, patt: 0, matt: 0, pdef: 35, mdef: 30, nlvl: 5 },
    { id: 9, city: 'Aden Castle Town', tip: 'weapon', tip2: 'ydar', name: 'Dragon Hunter Axe', cena: 48800000, patt: 342, matt: 132, pdef: 0, mdef: 0, nlvl: 76 },
    { id: 10, city: 'Aden Castle Town', tip: 'weapon', tip2: 'ydar', name: 'Yablonskis Hammer', cena: 48800000, patt: 251, matt: 121, pdef: 0, mdef: 0, nlvl: 76 },
    { id: 11, city: 'Aden Castle Town', tip: 'body', tip2: '', name: 'Dragon Armor', cena: 35000000, patt: 0, matt: 0, pdef: 220, mdef: 200, nlvl: 76 },
    { id: 12, city: 'Aden Castle Town', tip: 'golova', tip2: '', name: 'Dragon Helm', cena: 12000000, patt: 0, matt: 0, pdef: 80, mdef: 75, nlvl: 76 },
  ],

  // inventar: id, usr, name, tip, kol, cena, patt, matt, pdef, mdef, nlvl, status
  inventar: [
    { id: 1, usr: 'Demo', name: 'Steel Sword', tip: 'weapon', kol: 1, cena: 250000, patt: 45, matt: 10, pdef: 0, mdef: 0, nlvl: 1, status: 'on' },
    { id: 2, usr: 'Demo', name: 'Leather Armor', tip: 'body', kol: 1, cena: 180000, patt: 0, matt: 0, pdef: 25, mdef: 20, nlvl: 1, status: 'on' },
    { id: 3, usr: 'Demo', name: 'Iron Helmet', tip: 'golova', kol: 1, cena: 80000, patt: 0, matt: 0, pdef: 12, mdef: 10, nlvl: 1, status: 'off' },
    { id: 4, usr: 'Demo', name: 'Health Potion', tip: 'elixir', kol: 5, cena: 1000, patt: 0, matt: 0, pdef: 0, mdef: 0, nlvl: 0, status: 'off' },
  ],

  sklad: [
    { id: 1, usr: 'Demo', name: 'Soulshot x100', kol: 3, cena: 5000 },
  ],

  bank: [
    { usr: 'Demo', money: 5000000 },
  ],

  news: [
    { id: 1, text: 'Добро пожаловать в Miniage! Сервер переведён на Node.js — все страницы *.php теперь отдаёт Node. Логин demo / demo, дальше — в бой!', time: '27.11.2026', avtor: 'Admin' },
    { id: 2, text: 'Открыта охота в окрестностях: Evil Hunting Grounds, The Ant Nest, Silent Valley, Dragon Valley. Удачи, герои!', time: '26.11.2026', avtor: 'Admin' },
  ],

  chat: [
    { id: 1, usr: 'Admin', text: 'Добро пожаловать в Miniage!', time: Date.now() - 3600e3, color: 'ff4040' },
    { id: 2, usr: 'Talean', text: 'Всем привет! Кто в Dragon Valley?', time: Date.now() - 1800e3, color: '' },
    { id: 3, usr: 'Kira', text: 'Я качаюсь в Silent Valley, заходите в пати', time: Date.now() - 600e3, color: '' },
  ],

  clan: [
    { id: 1, name: 'Miniage', lider: 'Admin', zam: 'Talean', point: 1250, lvl: 5, money: 5000000, emblema: '', logo: '' },
    { id: 2, name: 'DarkLegion', lider: 'Kira', zam: '', point: 640, lvl: 3, money: 1200000, emblema: '', logo: '' },
  ],

  online: [], // {usr, laikas}
  mesto: [],  // {usr, place, mesto, page, city}
  tmp: [],    // бои: {usr, mob, ltime, mobhp}
  smert: [],  // смерти: {usr, hp, mp, life}
  msg: [],    // личные сообщения: {id, from, to, text, time, read}
  forum_topics: [
    { id: 1, name: 'Правила игры', avtor: 'Admin', time: '01.01.2026', kol: 42 },
    { id: 2, name: 'Гайд по прокачке для новичков', avtor: 'Talean', time: '10.02.2026', kol: 137 },
  ],
  blog: [
    { id: 1, usr: 'Admin', text: 'Первый пост в блоге Miniage!', time: '27.11.2026' },
  ],
  gallery: [],
  option_game: { id: 1, igra: 'on', chat: 'on', reg: 'on' },
  rekord: { online: '3|27.11.2026|12:00:00', new_reg: '2|27.11.2026|12:00:00' },
  new_usr: [],
  regenerator: [{ usr: 'Demo', last: Date.now() / 1000 }, { usr: 'Admin', last: Date.now() / 1000 }],
  block: [],
  ban: [],
  gifts: [],
  job_mob: [],
  event: [],
  arena_wait: [],
  tchat: [],
  adm_chat: [],
  vhod: [],
};

// ---------------------------------------------------------------------------
// Сохранение демо-БД на диск (аналог персистентности MySQL).
// Без этого новый персонаж/сообщения/покупки исчезали при перезапуске сервера.
// Файл: node/data/db.json (переопределяется через MINIAGE_DB).
// ---------------------------------------------------------------------------
const fs = require('fs');
const path = require('path');
const DATA_DIR = path.join(__dirname, '..', 'data');
const DB_FILE = process.env.MINIAGE_DB || path.join(DATA_DIR, 'db.json');
let saveTimer = null;

function loadDb() {
  try {
    if (!fs.existsSync(DB_FILE)) return false;
    const raw = JSON.parse(fs.readFileSync(DB_FILE, 'utf8'));
    // Сид задаёт структуру; из файла берём только известные ключи и массивы
    let restored = 0;
    for (const k of Object.keys(db)) {
      if (raw[k] === undefined) continue;
      if (Array.isArray(db[k]) && Array.isArray(raw[k])) { db[k] = raw[k]; restored++; }
      else if (db[k] && typeof db[k] === 'object' && raw[k] && typeof raw[k] === 'object') { Object.assign(db[k], raw[k]); restored++; }
    }
    if (raw.__seq) seq = Math.max(seq, raw.__seq);
    return restored > 0;
  } catch (e) {
    console.warn('Не удалось прочитать', DB_FILE, '- используется сид:', e.message);
    return false;
  }
}

function saveDb() {
  try {
    fs.mkdirSync(DATA_DIR, { recursive: true });
    const tmp = DB_FILE + '.tmp';
    fs.writeFileSync(tmp, JSON.stringify(Object.assign({}, db, { __seq: seq })), 'utf8');
    fs.renameSync(tmp, DB_FILE); // атомарная замена, чтобы файл не побился
  } catch (e) { console.warn('Не удалось сохранить БД:', e.message); }
}

/** Отложенная запись: не чаще 1 раза в 400 мс (как отложенный flush). */
function save() {
  if (saveTimer) return;
  saveTimer = setTimeout(() => { saveTimer = null; saveDb(); }, 400);
}
process.on('exit', () => { if (saveTimer) { clearTimeout(saveTimer); saveDb(); } });
process.on('SIGINT', () => { saveDb(); process.exit(0); });
process.on('SIGTERM', () => { saveDb(); process.exit(0); });

const dbRestored = loadDb();

// ---------------------------------------------------------------------------
// Запросы (аналоги mysql_query в PHP)
// ---------------------------------------------------------------------------
const Q = {
  accountByNick(nick) { return db.accounts.find(a => a.nick === nick) || null; },
  accountById(id) { return db.accounts.find(a => a.id === Number(id)) || null; },
  userById(id) { return db.users.find(u => u.id === Number(id)) || null; },
  userByName(usr) { return db.users.find(u => u.usr === usr) || null; },
  usersByAccount(accId) { return db.users.filter(u => u.account === Number(accId)); },
  mobById(id) { return db.mobs.find(m => m.id === Number(id)) || null; },
  mobsByOkra(okra) { return db.mobs.filter(m => m.okra === okra && m.status === 'on'); },
  okras() { return [...new Set(db.mobs.map(m => m.okra))]; },
  shopByTip(tip) { return db.shop.filter(s => s.tip === tip); },
  shopById(id) { return db.shop.find(s => s.id === Number(id)) || null; },
  inventarByUsr(usr) { return db.inventar.filter(i => i.usr === usr); },
  clanByLider(lider) { return db.clan.find(c => c.lider === lider) || null; },
  clanByName(name) { return db.clan.find(c => c.name === name) || null; },
  mestoByUsr(usr) { return db.mesto.find(m => m.usr === usr) || null; },
  battleByUsr(usr) { return db.tmp.find(t => t.usr === usr) || null; },
  regenByUsr(usr) { return db.regenerator.find(r => r.usr === usr) || null; },
  onlineCount() {
    const t = Date.now() / 1000 - 150;
    return db.online.filter(o => o.laikas > t).length;
  },
};

function saveUser(u) {
  const i = db.users.findIndex(x => x.id === u.id);
  if (i >= 0) db.users[i] = u; else db.users.push(u);
  save();
  return u;
}

function createAccount(nick, pass, email) {
  const acc = { id: nextId(), nick, pass: md5(pass), email: email || '', ip: '', ref: 0, lvisit: 0, active: 0, sekret: '' };
  db.accounts.push(acc);
  save();
  return acc;
}

function createUser(accId, data) {
  const u = Object.assign({
    id: nextId(), usr: '', pol: 'm', storona: 'human', klas: 'fighert', lvl: 1, exp: 0,
    money: 30000000, gold: 0, almaz: 0, hp: 100, hpall: 100, mp: 50, mpall: 50,
    patt: 20, matt: 10, pdef: 10, mdef: 8, skill: 3, city: 'Giran Castle Town',
    clan: '', wins: 0, loses: 0, arenawins: 0, arenaloses: 0, karma: 0, slava: 0,
    status: '', st: 0, dat_reg: new Date().toLocaleDateString('ru-RU'), account: accId,
    lvisit: '', ip_br: '', zerna: 0, votecoin: 0, dostup: 0, res: 0, post: 0, ups: 0,
    predatel: 0, ref: '', site: '', time: 0, time_bon: 0, zamujem: '', ali: '', tot_o: 0,
    lord: '', ps: '', pass: '', ig: 0, wmr: 0, sp: 0, citys: '',
  }, data);
  db.users.push(u);
  if (!db.regenerator.find(r => r.usr === u.usr)) db.regenerator.push({ usr: u.usr, last: Date.now() / 1000 });
  if (!db.mesto.find(m => m.usr === u.usr)) db.mesto.push({ usr: u.usr, place: 'gorod', mesto: Q.okras()[0] || 'Evil Hunting Grounds', page: 0, city: u.city });
  save();
  return u;
}

module.exports = { db, Q, md5, expForLevel, expPercent, saveUser, createAccount, createUser, nextId, save, saveDb, DB_FILE, dbRestored };
