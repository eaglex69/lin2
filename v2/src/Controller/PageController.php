<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Отдаёт HTML-оболочку. Вся игра рисуется на <canvas> в public/assets/js,
 * данные подтягиваются через /api/* (AJAX/fetch).
 *
 * Разметка минимальная: canvas + панели. Никаких <table>-версток legacy.
 */
final class PageController
{
    public function index(Request $req): Response
    {
        $bootstrapUrl = '/api/bootstrap';
        // Если cookie недоступны (iframe), пробрасываем sid в запросе API
        $sid = Session::inUrl() ? '?s=' . urlencode(Session::id()) : '';

        $app = htmlspecialchars(Config::get('APP_NAME', 'Miniage'), ENT_QUOTES);

        $html = <<<HTML
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#12100e">
<meta name="description" content="Miniage — браузерная MMORPG: Canvas-карта, бои, магазин, чат. PHP 8.1 API.">
<title>{$app} — Lineage-подобная MMORPG</title>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div id="app" data-bootstrap="{$bootstrapUrl}{$sid}">
  <canvas id="game-canvas" width="960" height="600" aria-label="Игровая карта"></canvas>

  <header class="hud hud-top" id="hud-top">
    <div class="hud-brand"><b>miniage</b><span>ru</span></div>
    <div class="hud-bars" id="hud-bars" hidden>
      <div class="bar bar-hp"><i id="bar-hp" style="width:100%"></i><span id="bar-hp-text">HP —</span></div>
      <div class="bar bar-mp"><i id="bar-mp" style="width:100%"></i><span id="bar-mp-text">MP —</span></div>
      <div class="bar bar-exp"><i id="bar-exp" style="width:0%"></i><span id="bar-exp-text">EXP 0%</span></div>
    </div>
    <div class="hud-right">
      <span id="hud-adena" class="adena" hidden>0</span>
      <span id="hud-online" class="online">online: 0</span>
      <button id="btn-sound" class="icon-btn" title="Звук" aria-label="Звук">♫</button>
      <button id="btn-help" class="icon-btn" title="Помощь" aria-label="Помощь">?</button>
    </div>
  </header>

  <nav class="hud hud-left" id="hud-nav">
    <button data-panel="map" class="nav-btn active" title="Карта">🗺</button>
    <button data-panel="char" class="nav-btn" title="Персонаж" hidden>⚔</button>
    <button data-panel="shop" class="nav-btn" title="Магазин" hidden>🛒</button>
    <button data-panel="bag" class="nav-btn" title="Инвентарь" hidden>🎒</button>
    <button data-panel="chat" class="nav-btn" title="Чат">💬</button>
    <button data-panel="top" class="nav-btn" title="Рейтинг">🏆</button>
    <button data-panel="news" class="nav-btn" title="Новости">📜</button>
  </nav>

  <section class="hud hud-zone" id="hud-zone" hidden>
    <span id="zone-label">Giran</span>
    <span class="zone-hint">клик по монстру — бой</span>
  </section>

  <div class="panel" id="panel" hidden>
    <div class="panel-head">
      <h2 id="panel-title">Панель</h2>
      <button id="panel-close" class="icon-btn" aria-label="Закрыть">✕</button>
    </div>
    <div class="panel-body" id="panel-body"></div>
  </div>

  <div class="battle" id="battle" hidden>
    <div class="battle-head">
      <span id="battle-mob">Монстр</span>
      <span id="battle-turn">ход 1</span>
    </div>
    <div class="bar bar-mob"><i id="battle-mob-hp" style="width:100%"></i></div>
    <div class="battle-log" id="battle-log"></div>
    <div class="battle-actions">
      <button id="btn-attack" class="btn btn-primary">Атаковать</button>
      <button id="btn-flee" class="btn">Отступить</button>
    </div>
  </div>

  <div class="auth" id="auth">
    <div class="auth-card">
      <h1><b>miniage</b> ru</h1>
      <p class="auth-sub">PHP 8.1 · MySQL · AJAX · Canvas</p>
      <div class="tabs">
        <button class="tab active" data-tab="login">Вход</button>
        <button class="tab" data-tab="register">Регистрация</button>
      </div>

      <form id="form-login" class="form" autocomplete="on">
        <label>Логин<input name="nick" value="demo" maxlength="16" required></label>
        <label>Пароль<input name="pass" type="password" value="demo" required></label>
        <button class="btn btn-primary" type="submit">Войти</button>
        <p class="hint">Демо-доступ: <b>demo / demo</b></p>
      </form>

      <form id="form-register" class="form" hidden>
        <label>Логин<input name="nick" maxlength="16" placeholder="Только латиница" required></label>
        <label>Пароль<input name="pass" type="password" minlength="4" required></label>
        <label>Email<input name="email" type="email" placeholder="необязательно"></label>
        <div class="row">
          <label>Раса
            <select name="race">
              <option value="human">Человек</option>
              <option value="elf">Светлый эльф</option>
              <option value="darkelf">Тёмный эльф</option>
              <option value="orc">Орк</option>
              <option value="dwarf">Гном</option>
              <option value="kamael">Камаэль</option>
            </select>
          </label>
          <label>Класс
            <select name="class">
              <option value="fighter">Воин</option>
              <option value="mage">Маг</option>
              <option value="archer">Лучник</option>
              <option value="healer">Целитель</option>
            </select>
          </label>
        </div>
        <button class="btn btn-primary" type="submit">Создать персонажа</button>
      </form>
      <p class="auth-error" id="auth-error" role="alert"></p>
    </div>
  </div>

  <div class="toast" id="toast" hidden></div>
  <div class="loading" id="loading" hidden><i></i></div>
  <noscript><p class="noscript">Для игры нужен JavaScript: карта рисуется на canvas.</p></noscript>
</div>
<script type="module" src="/assets/js/main.js"></script>
</body>
</html>
HTML;

        return Response::html($html)->withHeaders([
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
        ]);
    }
}
