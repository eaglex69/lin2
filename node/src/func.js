'use strict';
/**
 * func.js — порт inc/func.php + inc/core.php + inc/lvl.php + inc/opit.php
 *           + inc/regeneration.php (игровая математика)
 */
const { expForLevel, expPercent, saveUser, Q } = require('./db');

// inc/core.php: sec2day()
function sec2day(sec) {
  sec = Number(sec) || 0;
  const days = Math.floor(sec / 86400);
  const hours = Math.floor((sec / 3600) - days * 24);
  const minuts = Math.floor((sec - hours * 3600 - days * 86400) / 60);
  const seconds = sec - (minuts * 60 + hours * 3600 + days * 86400);
  return (days ? days + ' дн. ' : '') + (hours ? hours + ' ч. ' : '') +
    (minuts ? minuts + ' мин. ' : '') + seconds + ' сек.';
}

// inc/core.php: checkin() — чистка текста чата/сообщений
function checkin(str) {
  if (!str) return '';
  str = String(str).replace(/(\.|\?|!|\(|\)){3,}/g, '$1$1$1');
  str = str.replace(/ {2,}/g, ' ').replace(/(\n)+(\n)/gi, '\n\n');
  return str.trim();
}

// inc/core.php: display_pagin()
function pagin(myPage, total, script, display = 10) {
  const lastPage = Math.max(1, Math.ceil(total / display));
  if (!myPage || myPage < 1 || myPage > lastPage) myPage = 1;
  let out = '<div class="navigation">';
  if (myPage > 1) out += `<a href="${script}${myPage - 1}">Назад</a> `;
  if (myPage < lastPage) out += `<a href="${script}${myPage + 1}">Вперёд</a>`;
  return out + '</div>';
}

// inc/func.php: str2gradient()
function str2gradient(text, from = '', to = '', mode = 'hex') {
  if (!text) return '';
  let f = [0, 0, 255], t = [255, 0, 0];
  if (mode === 'hex' && from) f = [0, 2, 4].map(i => parseInt(from.substr(i, 2), 16));
  if (mode === 'hex' && to) t = [0, 2, 4].map(i => parseInt(to.substr(i, 2), 16));
  const levels = text.length;
  let out = '';
  for (let i = 1; i <= levels; i++) {
    const rgb = f.map((fv, ii) => {
      const tmp = Math.floor((fv - t[ii]) / levels);
      let v = fv - tmp * i;
      if (v > 255) v = 255;
      return v.toString(16).toUpperCase().padStart(2, '0');
    });
    out += `<font color="#${rgb.join('')}">${text[i - 1]}</font>`;
  }
  return out + '\n';
}

// inc/regeneration.php — регенерация HP/MP: +1/сек вне боя
function regeneration(user) {
  if (!user) return;
  const reg = Q.regenByUsr(user.usr);
  if (!reg) return;
  const now = Math.floor(Date.now() / 1000);
  const k = Math.floor(now - reg.last);
  if (k >= 1 && !Q.battleByUsr(user.usr)) {
    user.hp = Math.min(user.hpall, user.hp + k);
    user.mp = Math.min(user.mpall, user.mp + k);
    reg.last = now;
    saveUser(user);
  }
}

// inc/lvl.php — проверка повышения уровня (может сработать несколько раз)
function checkLevel(user) {
  let leveled = false;
  while (user.exp >= expForLevel(user.lvl)) {
    user.lvl += 1;
    user.skill += 3;
    const bonus = user.lvl * 100;
    user.money += bonus;
    user.hpall += user.lvl;
    user.mpall += user.lvl;
    user.hp = user.hpall;
    user.mp = user.mpall;
    if (user.klas === 'wizard') {
      user.patt += user.lvl * 0.03; user.matt += user.lvl * 0.08;
      user.pdef += user.lvl * 0.03; user.mdef += user.lvl * 0.08;
    } else {
      user.patt += user.lvl * 0.03; user.matt += user.lvl * 0.02;
      user.pdef += user.lvl * 0.09; user.mdef += user.lvl * 0.02;
    }
    user.patt = Math.round(user.patt); user.matt = Math.round(user.matt);
    user.pdef = Math.round(user.pdef); user.mdef = Math.round(user.mdef);
    leveled = true;
  }
  if (leveled) saveUser(user);
  return leveled;
}

// inc/func.php: going() — если игрок в бою, гоним в battle.php
function inBattle(user) {
  return user ? !!Q.battleByUsr(user.usr) : false;
}

// Форматирование чисел как number_format() в PHP
function num(n) { return Number(n || 0).toLocaleString('ru-RU'); }

// inc/core.php: formatsize()
function formatsize(fileSize) {
  if (fileSize >= 1048576000) return (fileSize / 1073741824).toFixed(2) + ' Gb';
  if (fileSize >= 1024000) return (fileSize / 1048576).toFixed(2) + ' Mb';
  if (fileSize >= 1000) return (fileSize / 1024).toFixed(2) + ' Kb';
  return Math.round(fileSize) + ' byte';
}

module.exports = { sec2day, checkin, pagin, str2gradient, regeneration, checkLevel, inBattle, num, formatsize, expPercent };
