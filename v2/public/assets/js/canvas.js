/**
 * canvas.js — отрисовка мира на <canvas>.
 *
 * Всё рисуется кодом (никаких картинок): изометрическая сетка, деревья,
 * монстры-спрайты, персонаж, плавающий урон, подсветка под курсором.
 * Работает с высокой плотностью пикселей (devicePixelRatio) и resizable.
 */

const TAU = Math.PI * 2;

const SPRITE_COLORS = {
  slime: '#7bd389',
  rat: '#a1887f',
  orc: '#c0392b',
  wolf: '#95a5a6',
  skeleton: '#ecf0f1',
  ghoul: '#8d9e8e',
  archer: '#8e44ad',
  fairy: '#5dade2',
  golem: '#795548',
  dragon: '#e67e22',
};

export class WorldCanvas {
  constructor(canvas) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.dpr = Math.max(1, Math.min(3, window.devicePixelRatio || 1));
    this.world = { w: 960, h: 600 };
    this.zone = null;
    this.mobs = [];
    this.hero = null;
    this.hover = null;
    this.hoverTile = null;
    this.floaters = [];
    this.particles = [];
    this.t = 0;
    this.camera = { x: 0, y: 0 };
    this.onMobClick = null;
    this.onTileClick = null;

    this._resize();
    window.addEventListener('resize', () => this._resize());
    canvas.addEventListener('mousemove', (e) => this._onMove(e));
    canvas.addEventListener('mouseleave', () => { this.hover = null; this.hoverTile = null; });
    canvas.addEventListener('click', (e) => this._onClick(e));
    canvas.addEventListener('touchstart', (e) => this._onTouch(e), { passive: true });

    this._loop = this._loop.bind(this);
    requestAnimationFrame(this._loop);
  }

  _resize() {
    const rect = this.canvas.getBoundingClientRect();
    const w = Math.max(320, Math.round(rect.width || 960));
    const h = Math.max(360, Math.round(rect.height || 600));
    this.canvas.width = Math.round(w * this.dpr);
    this.canvas.height = Math.round(h * this.dpr);
    this.view = { w, h };
    this.world = { w, h };
    this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
  }

  setZone(zone) {
    this.zone = zone;
    this.particles = [];
    for (let i = 0; i < 40; i++) {
      this.particles.push({
        x: Math.random() * this.view.w,
        y: Math.random() * this.view.h,
        r: 0.6 + Math.random() * 1.8,
        a: 0.15 + Math.random() * 0.35,
        vx: (Math.random() - 0.5) * 0.18,
        vy: -0.05 - Math.random() * 0.12,
      });
    }
  }

  setMobs(mobs) { this.mobs = mobs || []; }
  setHero(hero) { this.hero = hero || null; }

  /** Точка на канвасе -> мировые координаты. */
  _toWorld(e) {
    const rect = this.canvas.getBoundingClientRect();
    return {
      x: (e.clientX - rect.left) * (this.view.w / rect.width),
      y: (e.clientY - rect.top) * (this.view.h / rect.height),
    };
  }

  _pickMob(p) {
    let best = null;
    let bestD = 46;
    for (const m of this.mobs) {
      if (m.alive === false) continue;
      const d = Math.hypot(m.x - p.x, m.y - p.y);
      if (d < bestD) { bestD = d; best = m; }
    }
    return best;
  }

  _onMove(e) {
    const p = this._toWorld(e);
    this.hover = this._pickMob(p);
    this.hoverTile = { x: p.x, y: p.y };
    this.canvas.style.cursor = this.hover ? 'pointer' : 'default';
  }

  _onClick(e) {
    const p = this._toWorld(e);
    const mob = this._pickMob(p);
    if (mob && this.onMobClick) {
      this.onMobClick(mob);
      return;
    }
    if (this.onTileClick) this.onTileClick(p);
  }

  _onTouch(e) {
    const t = e.touches && e.touches[0];
    if (!t) return;
    const p = this._toWorld(t);
    const mob = this._pickMob(p);
    if (mob && this.onMobClick) this.onMobClick(mob);
  }

  /** Эффект: всплывающий текст (урон/опыт). */
  floatText(x, y, text, color = '#fff', size = 18) {
    this.floaters.push({ x, y, text, color, size, life: 1.1, max: 1.1, vy: -0.55 });
  }

  /** Вспышка-частицы при ударе. */
  burst(x, y, color, count = 14) {
    for (let i = 0; i < count; i++) {
      const a = Math.random() * TAU;
      const s = 0.8 + Math.random() * 2.6;
      this.particles.push({
        x, y, vx: Math.cos(a) * s, vy: Math.sin(a) * s,
        r: 1.5 + Math.random() * 2.5, life: 0.55, max: 0.55, color,
      });
    }
  }

  _loop(ts) {
    this.t = ts / 1000;
    this._draw();
    requestAnimationFrame(this._loop);
  }

  _draw() {
    const { ctx, view } = this;
    const w = view.w;
    const h = view.h;

    // фон зоны
    const bg = this.zone ? this.zone.color : '#1d2b1d';
    const g = ctx.createRadialGradient(w * 0.5, h * 0.42, 60, w * 0.5, h * 0.42, Math.max(w, h) * 0.78);
    g.addColorStop(0, this._lighten(bg, 22));
    g.addColorStop(1, this._darken(bg, 34));
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, w, h);

    this._drawGrid(ctx, w, h);
    this._drawDecor(ctx, w, h);
    this._drawAmbient(ctx);

    // монстры
    for (const m of this.mobs) {
      this._drawMob(ctx, m, this.hover && this.hover.id === m.id);
    }

    // герой
    if (this.hero) this._drawHero(ctx, this.hero);

    // подсветка клетки под курсором
    if (this.hoverTile && !this.hover) {
      ctx.save();
      ctx.strokeStyle = 'rgba(255,255,255,0.16)';
      ctx.lineWidth = 1;
      const gx = Math.round(this.hoverTile.x / 48) * 48;
      const gy = Math.round(this.hoverTile.y / 48) * 48;
      ctx.strokeRect(gx - 24, gy - 24, 48, 48);
      ctx.restore();
    }

    this._drawFloaters(ctx);
    this._drawParticles(ctx);
    this._drawVignette(ctx, w, h);
  }

  _drawGrid(ctx, w, h) {
    ctx.save();
    ctx.strokeStyle = 'rgba(255,255,255,0.045)';
    ctx.lineWidth = 1;
    const step = 48;
    for (let x = 0; x <= w; x += step) {
      ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, h); ctx.stroke();
    }
    for (let y = 0; y <= h; y += step) {
      ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke();
    }
    ctx.restore();
  }

  /** Деревья/камни на основе сида зоны — стабильны между кадрами. */
  _drawDecor(ctx, w, h) {
    const seedBase = this.zone ? [...this.zone.key].reduce((a, c) => a + c.charCodeAt(0), 0) : 7;
    const count = Math.floor((w * h) / 26000);
    for (let i = 0; i < count; i++) {
      const rnd = this._rnd(seedBase * 1000 + i);
      const x = rnd * w;
      const y = (this._rnd(seedBase * 2000 + i) * h * 0.92) + 24;
      const kind = this._rnd(seedBase * 3000 + i);
      if (kind > 0.55) {
        this._tree(ctx, x, y, 0.7 + rnd * 0.6);
      } else if (kind > 0.3) {
        this._rock(ctx, x, y, 0.6 + rnd * 0.7);
      } else {
        this._bush(ctx, x, y, 0.6 + rnd * 0.5);
      }
    }
  }

  _rnd(n) {
    const x = Math.sin(n * 12.9898) * 43758.5453;
    return x - Math.floor(x);
  }

  _tree(ctx, x, y, s) {
    ctx.save();
    ctx.globalAlpha = 0.85;
    ctx.fillStyle = '#2f2417';
    ctx.fillRect(x - 3 * s, y - 6 * s, 6 * s, 16 * s);
    const grd = ctx.createRadialGradient(x - 6 * s, y - 22 * s, 3, x, y - 18 * s, 24 * s);
    grd.addColorStop(0, '#3f6f3a');
    grd.addColorStop(1, '#1e3a1c');
    ctx.fillStyle = grd;
    ctx.beginPath();
    ctx.arc(x, y - 20 * s, 18 * s, 0, TAU);
    ctx.fill();
    ctx.restore();
  }

  _rock(ctx, x, y, s) {
    ctx.save();
    ctx.globalAlpha = 0.8;
    ctx.fillStyle = '#4a4a4f';
    ctx.beginPath();
    ctx.ellipse(x, y, 12 * s, 8 * s, 0, 0, TAU);
    ctx.fill();
    ctx.fillStyle = '#5f5f66';
    ctx.beginPath();
    ctx.ellipse(x - 3 * s, y - 3 * s, 6 * s, 4 * s, 0, 0, TAU);
    ctx.fill();
    ctx.restore();
  }

  _bush(ctx, x, y, s) {
    ctx.save();
    ctx.globalAlpha = 0.7;
    ctx.fillStyle = '#33592f';
    ctx.beginPath();
    ctx.arc(x, y, 9 * s, 0, TAU);
    ctx.arc(x + 8 * s, y + 2 * s, 7 * s, 0, TAU);
    ctx.arc(x - 8 * s, y + 2 * s, 7 * s, 0, TAU);
    ctx.fill();
    ctx.restore();
  }

  _drawAmbient(ctx) {
    for (const p of this.particles) {
      if (p.life !== undefined) continue; // частицы эффектов рисуются отдельно
      p.x += p.vx;
      p.y += p.vy;
      if (p.y < -10) p.y = this.view.h + 10;
      if (p.x < -10) p.x = this.view.w + 10;
      if (p.x > this.view.w + 10) p.x = -10;
      ctx.save();
      ctx.globalAlpha = p.a;
      ctx.fillStyle = '#ffe9a8';
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, TAU);
      ctx.fill();
      ctx.restore();
    }
  }

  _drawMob(ctx, m, hovered) {
    const bob = Math.sin(this.t * 2.2 + m.id) * 2.5;
    const x = m.x;
    const y = m.y + bob;
    const alive = m.alive !== false;
    const color = SPRITE_COLORS[m.sprite] || '#' + (m.color || '6ab04c');

    ctx.save();
    if (!alive) ctx.globalAlpha = 0.25;

    // тень
    ctx.fillStyle = 'rgba(0,0,0,0.32)';
    ctx.beginPath();
    ctx.ellipse(x, m.y + 16, 17, 6, 0, 0, TAU);
    ctx.fill();

    if (hovered && alive) {
      const pulse = 0.5 + Math.sin(this.t * 6) * 0.2;
      ctx.strokeStyle = `rgba(255,228,150,${pulse})`;
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.ellipse(x, m.y + 16, 24, 10, 0, 0, TAU);
      ctx.stroke();
    }

    this._sprite(ctx, m.sprite, x, y, color, m.boss);

    // имя и уровень
    ctx.font = '11px "Trebuchet MS", system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillStyle = alive ? (m.boss ? '#ff9d5c' : '#e8e2d2') : '#7b7b7b';
    ctx.fillText(`${m.name} [${m.level}]`, x, m.y - 26);
    if (!alive) {
      ctx.fillStyle = '#9a9a9a';
      // «занят» и «ждёт респавна» — разные вещи: подпись должна объяснять,
      // почему моб недоступен, иначе игрок думает, что таймер врёт
      ctx.fillText(m.busy ? 'бьётся с другим игроком' : `респавн ~${m.respawn_in}s`, x, m.y - 12);
    }
    ctx.restore();
  }

  _sprite(ctx, kind, x, y, color, boss) {
    const s = boss ? 1.35 : 1;
    ctx.save();
    ctx.translate(x, y);
    ctx.scale(s, s);

    switch (kind) {
      case 'slime':
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.ellipse(0, 4, 16, 11, 0, Math.PI, TAU);
        ctx.ellipse(0, 4, 16, 11, 0, 0, Math.PI);
        ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.55)';
        ctx.beginPath(); ctx.arc(-5, 0, 2.4, 0, TAU); ctx.arc(5, 0, 2.4, 0, TAU); ctx.fill();
        break;
      case 'rat':
      case 'wolf':
        ctx.fillStyle = color;
        ctx.beginPath(); ctx.ellipse(0, 4, 17, 9, 0, 0, TAU); ctx.fill();
        ctx.beginPath(); ctx.arc(13, -2, 7, 0, TAU); ctx.fill();
        ctx.beginPath();
        ctx.moveTo(11, -8); ctx.lineTo(14, -15); ctx.lineTo(17, -6);
        ctx.fill();
        ctx.fillStyle = '#2b2b2b';
        ctx.beginPath(); ctx.arc(16, -3, 1.6, 0, TAU); ctx.fill();
        ctx.strokeStyle = color; ctx.lineWidth = 3; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(-16, 2); ctx.quadraticCurveTo(-26, -4, -24, -12); ctx.stroke();
        break;
      case 'orc':
        ctx.fillStyle = color;
        ctx.beginPath(); ctx.ellipse(0, 1, 13, 15, 0, 0, TAU); ctx.fill();
        ctx.fillStyle = '#3d1f12';
        ctx.beginPath(); ctx.ellipse(-4, -2, 2, 3, 0, 0, TAU); ctx.ellipse(4, -2, 2, 3, 0, 0, TAU); ctx.fill();
        ctx.fillStyle = '#e7d9a0';
        ctx.beginPath(); ctx.moveTo(-3, 6); ctx.lineTo(0, 11); ctx.lineTo(3, 6); ctx.fill();
        break;
      case 'skeleton':
        ctx.strokeStyle = color; ctx.lineWidth = 3; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(0, -10); ctx.lineTo(0, 8); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(-8, -4); ctx.lineTo(8, -4); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(-6, 8); ctx.lineTo(0, 2); ctx.lineTo(6, 8); ctx.stroke();
        ctx.fillStyle = color;
        ctx.beginPath(); ctx.arc(0, -14, 5, 0, TAU); ctx.fill();
        ctx.fillStyle = '#2b2b2b';
        ctx.beginPath(); ctx.arc(-2, -14, 1.4, 0, TAU); ctx.arc(2, -14, 1.4, 0, TAU); ctx.fill();
        break;
      case 'ghoul':
        ctx.fillStyle = color;
        ctx.beginPath(); ctx.ellipse(0, 2, 12, 16, 0, 0, TAU); ctx.fill();
        ctx.fillStyle = '#c9d6c9';
        ctx.beginPath(); ctx.arc(0, -14, 6, 0, TAU); ctx.fill();
        ctx.fillStyle = '#212121';
        ctx.beginPath(); ctx.arc(-2, -14, 1.5, 0, TAU); ctx.arc(2, -14, 1.5, 0, TAU); ctx.fill();
        ctx.strokeStyle = color; ctx.lineWidth = 4; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(-10, 0); ctx.lineTo(-20, -6); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(10, 0); ctx.lineTo(20, -6); ctx.stroke();
        break;
      case 'archer':
      case 'fairy':
        ctx.fillStyle = color;
        ctx.beginPath(); ctx.ellipse(0, 2, 9, 13, 0, 0, TAU); ctx.fill();
        ctx.beginPath(); ctx.arc(0, -12, 5, 0, TAU); ctx.fill();
        if (kind === 'fairy') {
          ctx.globalAlpha = 0.55;
          ctx.beginPath(); ctx.ellipse(-9, -2, 7, 4, -0.6, 0, TAU); ctx.fill();
          ctx.beginPath(); ctx.ellipse(9, -2, 7, 4, 0.6, 0, TAU); ctx.fill();
          ctx.globalAlpha = 1;
        } else {
          ctx.strokeStyle = '#caa472'; ctx.lineWidth = 2;
          ctx.beginPath(); ctx.arc(11, -2, 9, -1.2, 1.2); ctx.stroke();
        }
        break;
      case 'golem':
        ctx.fillStyle = color;
        ctx.fillRect(-13, -12, 26, 22);
        ctx.fillRect(-20, -6, 8, 16);
        ctx.fillRect(12, -6, 8, 16);
        ctx.fillStyle = 'rgba(255,255,255,0.18)';
        ctx.fillRect(-9, -8, 6, 4);
        ctx.fillStyle = '#a7f3d0';
        ctx.beginPath(); ctx.arc(-4, -2, 2, 0, TAU); ctx.arc(4, -2, 2, 0, TAU); ctx.fill();
        break;
      case 'dragon':
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.moveTo(-16, 6); ctx.quadraticCurveTo(-6, -18, 4, -6);
        ctx.quadraticCurveTo(16, -20, 18, 2); ctx.quadraticCurveTo(6, 12, -16, 6);
        ctx.fill();
        ctx.fillStyle = '#ffd166';
        ctx.beginPath(); ctx.arc(10, -6, 1.8, 0, TAU); ctx.fill();
        ctx.strokeStyle = 'rgba(255,180,80,0.5)'; ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(-14, 4); ctx.quadraticCurveTo(-30, -4, -26, -14); ctx.stroke();
        break;
      default:
        ctx.fillStyle = color;
        ctx.beginPath(); ctx.arc(0, 0, 13, 0, TAU); ctx.fill();
    }
    ctx.restore();
  }

  _drawHero(ctx, hero) {
    const x = this.view.w / 2;
    const y = this.view.h - 96;
    const bob = Math.sin(this.t * 3) * 2;

    // мягкий свет вокруг героя
    const g = ctx.createRadialGradient(x, y, 4, x, y, 70);
    g.addColorStop(0, 'rgba(255,231,164,0.22)');
    g.addColorStop(1, 'rgba(255,231,164,0)');
    ctx.fillStyle = g;
    ctx.beginPath(); ctx.arc(x, y, 70, 0, TAU); ctx.fill();

    ctx.save();
    ctx.fillStyle = 'rgba(0,0,0,0.34)';
    ctx.beginPath(); ctx.ellipse(x, y + 20, 18, 6, 0, 0, TAU); ctx.fill();

    const color = hero.race === 'elf' ? '#d8e8ff' : hero.race === 'darkelf' ? '#b39ddb' : hero.race === 'orc' ? '#8bc34a' : '#ffd54f';
    ctx.translate(x, y + bob);

    // тело
    ctx.fillStyle = '#3c2f24';
    ctx.beginPath();
    ctx.moveTo(-9, 16); ctx.lineTo(-6, -4); ctx.lineTo(6, -4); ctx.lineTo(9, 16);
    ctx.fill();
    // плащ
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.moveTo(-7, -4); ctx.quadraticCurveTo(0, 4, 7, -4); ctx.lineTo(5, 16); ctx.lineTo(-5, 16);
    ctx.fill();
    // голова
    ctx.fillStyle = '#f0c9a2';
    ctx.beginPath(); ctx.arc(0, -10, 6.5, 0, TAU); ctx.fill();
    // волосы
    ctx.fillStyle = color;
    ctx.beginPath(); ctx.arc(0, -12, 6.5, Math.PI, TAU); ctx.fill();
    // меч
    ctx.strokeStyle = '#e8e8e8'; ctx.lineWidth = 2.4; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(10, 10); ctx.lineTo(18, -10); ctx.stroke();
    ctx.strokeStyle = '#a9772f'; ctx.lineWidth = 4;
    ctx.beginPath(); ctx.moveTo(8.5, 12); ctx.lineTo(11, 7); ctx.stroke();
    ctx.restore();

    ctx.font = '12px "Trebuchet MS", system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillStyle = 'rgba(255,255,255,0.85)';
    ctx.fillText(hero.name, x, y - 34);
  }

  _drawFloaters(ctx) {
    for (const f of this.floaters) {
      f.life -= 1 / 60;
      f.y += f.vy;
      const a = Math.max(0, f.life / f.max);
      ctx.save();
      ctx.globalAlpha = a;
      ctx.font = `bold ${f.size}px "Trebuchet MS", system-ui, sans-serif`;
      ctx.textAlign = 'center';
      ctx.lineWidth = 3;
      ctx.strokeStyle = 'rgba(0,0,0,0.65)';
      ctx.strokeText(f.text, f.x, f.y);
      ctx.fillStyle = f.color;
      ctx.fillText(f.text, f.x, f.y);
      ctx.restore();
    }
    this.floaters = this.floaters.filter((f) => f.life > 0);
  }

  _drawParticles(ctx) {
    for (const p of this.particles) {
      if (p.life === undefined) continue;
      p.life -= 1 / 60;
      p.x += p.vx;
      p.y += p.vy;
      p.vy += 0.06;
      const a = Math.max(0, p.life / p.max);
      ctx.save();
      ctx.globalAlpha = a;
      ctx.fillStyle = p.color || '#ffe9a8';
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, TAU); ctx.fill();
      ctx.restore();
    }
    this.particles = this.particles.filter((p) => p.life === undefined || p.life > 0);
  }

  _drawVignette(ctx, w, h) {
    const g = ctx.createRadialGradient(w / 2, h / 2, Math.min(w, h) * 0.35, w / 2, h / 2, Math.max(w, h) * 0.75);
    g.addColorStop(0, 'rgba(0,0,0,0)');
    g.addColorStop(1, 'rgba(0,0,0,0.55)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, w, h);
  }

  _lighten(hex, amt) {
    return this._shift(hex, amt);
  }

  _darken(hex, amt) {
    return this._shift(hex, -amt);
  }

  _shift(hex, amt) {
    const m = /^#?([a-f0-9]{6})$/i.exec(hex || '');
    if (!m) return '#1d2b1d';
    const n = parseInt(m[1], 16);
    const clamp = (v) => Math.max(0, Math.min(255, v));
    const r = clamp((n >> 16) + amt);
    const g = clamp(((n >> 8) & 0xff) + amt);
    const b = clamp((n & 0xff) + amt);
    return `rgb(${r},${g},${b})`;
  }
}

export { SPRITE_COLORS };
