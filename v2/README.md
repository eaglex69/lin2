# Miniage v2 — PHP 8.1 + MySQL + AJAX + Canvas

Полный рерайт WAP-игры Miniage. Legacy-версия (230 PHP-файлов на `mysql_*`,
WAP XHTML, серверный рендеринг) заменена современным приложением:

* **PHP 8.1+** — строгая типизация, PSR-4, `declare(strict_types=1)`, фронт-контроллер;
* **MySQL 8** через **PDO** с prepared statements; SQLite как аварийный fallback;
* **AJAX** — единый JSON API `/api/*` (`fetch`, CSRF-токены, HTTP-статусы);
* **Canvas** — карта мира, монстры и персонаж рисуются кодом в `<canvas>`, без картинок.

> Прежние `*.php` из корня репозитория удалены — они остались в истории git
> (`git log --all -- '*.php'`). Старые URL (`/gorod.php`, `/shop.php`, …)
> обрабатываются редиректами на новый интерфейс, чтобы не ломать ссылки.

---

## Быстрый старт (production: Apache/Nginx + PHP-FPM + MySQL)

```bash
# 1. Зависимостей нет — только PHP 8.1+ с расширениями pdo_mysql, json, mbstring
php -v

# 2. База данных
mysql -u root -p < v2/db/schema.mysql.sql
#    или дайте приложению создать схему самому (миграция идемпотентна)

# 3. Конфигурация
cd v2
cp .env.example .env
#   пропишите DB_HOST/DB_NAME/DB_USER/DB_PASS

# 4. Права на запись (SQLite-fallback и логи)
chmod -R 775 var/

# 5. Веб-сервер
#    Apache:  DocumentRoot = v2/public   (mod_rewrite + AllowOverride All)
#    Nginx:   root v2/public;  try_files $uri $uri/ /index.php?$query_string;

# 6. Проверка
curl http://your-host/api/health
```

Встроенный сервер для разработки:

```bash
cd v2
php -S 0.0.0.0:8080 -t public
```

Демо-доступ после первого запуска: **demo / demo** (также `admin / admin`).

### Схема БД

`v2/db/schema.mysql.sql` создаёт 11 таблиц:

| Таблица | Назначение |
|---|---|
| `accounts` | аккаунты, `password_hash()` (Argon2id/BCrypt), роли |
| `characters` | персонажи: раса, класс, уровень, HP/MP, атака/защита, адена, координаты |
| `items` | каталог предметов: оружие, броня, зелья, материалы |
| `inventory` | что у персонажа, флаг `equipped` |
| `mobs` | монстры по зонам, награды, респавн |
| `battles` | пошаговые бои и журнал ходов (JSON) |
| `chat_messages` | чат: каналы global/trade/clan/system |
| `sessions` | серверные сессии (fallback без нативных) |
| `online` | онлайн-статус |
| `level_exp` | кривая опыта до 50 уровня |
| `news` | новости |

Данные из старой `shema.sql` (144 таблицы) не переносятся автоматически —
структура переработана. При необходимости исторические данные заливаются
скриптом поверх новой схемы.

---

## Архитектура

```
v2/
  public/                 ← DocumentRoot
    index.php             фронт-контроллер: конфиг, БД, сессия, роутер
    .htaccess             rewrite + заголовки безопасности
    assets/
      css/app.css         тёмная «lineage»-тема, адаптив
      js/api.js           AJAX-клиент (fetch + CSRF + sid в URL для iframe)
      js/canvas.js        рендер мира: изометрия, спрайты, эффекты, input
      js/ui.js            панели: персонаж, магазин, инвентарь, чат, рейтинг
      js/main.js          связка состояния, боя и панелей
  src/
    Core/                 Config, Database (PDO), Migrator, Router, Request, Response, Session
    Service/              AuthService, GameService, ShopService, ChatService, ExpTable
    Controller/           ApiController (JSON), PageController (HTML-оболочка)
  db/
    schema.mysql.sql      боевая схема
    schema.sqlite.sql     та же схема для SQLite
  preview/                запуск PHP 8.1 в WASM для превью без PHP/MySQL
  var/                    SQLite-файл, кэш (не в git)
```

### Поток запроса

```
GET /                 → PageController → HTML-оболочка (canvas + панели)
GET /api/bootstrap    → данные сессии, персонаж, зоны, CSRF
POST /api/login       → AuthService::login (password_verify) → сессия + CSRF
GET /api/zones/Giran  → GameService::mobsInZone (координаты для canvas)
POST /api/battle/start→ создаёт запись в battles
POST /api/battle/attack→ ход игрока + ответ монстра + награды/уровни
POST /api/shop/buy    → транзакция: списание адены, выдача предмета
GET  /api/chat        → последние сообщения (pollling из canvas-клиента)
```

### Безопасность

* пароли — `password_hash(PASSWORD_DEFAULT)` + `password_verify`, без MD5;
* CSRF-токен на сессию, проверяется middleware на всех POST;
* все SQL-запросы — prepared statements, идентификаторы валидируются;
* XSS: `esc()` на клиенте и `htmlspecialchars` на сервере, чат очищается от тегов;
* сессии: `HttpOnly`, `SameSite=Lax`, `Secure` при HTTPS, `session_regenerate_id` при входе;
* ошибки не показываются в production, логируются через `error_log`.

### Canvas-клиент

* карта рисуется процедурно: градиент зоны, сетка, деревья/камни/кусты по сиду зоны;
* 10 типов спрайтов монстров рисуются кодом (`slime`, `wolf`, `skeleton`, `dragon`, …);
* hover-подсветка и клик по монстру → старт боя;
* всплывающий урон, частицы, вспышка при получении удара;
* HUD: HP/MP/EXP-полосы, адена, онлайн; панели — DOM поверх canvas;
* адаптив: на мобильных навигация уходит вниз, панели на всю ширину;
* `devicePixelRatio` учтён — картинка резкая на Retina.

---

## Режим превью без PHP и MySQL

В песочнице нет PHP/MySQL, поэтому добавлен `v2/preview/server.mjs`: он поднимает
**настоящий PHP 8.1.28** (сборка WebAssembly, пакет `php-wasm`) и SQLite-драйвер,
монтирует файлы проекта в виртуальную ФС и передаёт запросы в `public/index.php`.

```bash
cd v2/preview
npm install        # php-wasm + php-wasm-sqlite
npm start          # http://localhost:3000
```

Этот сервер — только для демонстрации: исполняется тот же PHP-код, что и на проде,
но вместо MySQL используется SQLite (`var/miniage.sqlite`).

---

## API (кратко)

| Метод | Маршрут | Назначение |
|---|---|---|
| GET | `/api/health` | версия PHP, драйвер БД |
| GET | `/api/bootstrap` | сессия, персонаж, зоны, CSRF |
| POST | `/api/register` | создание аккаунта + персонажа |
| POST | `/api/login` | вход (`password_verify`), новая сессия |
| POST | `/api/logout` | выход |
| POST | `/api/characters/{id}/select` | выбрать персонажа |
| GET | `/api/zones` | список зон с рекомендуемым уровнем |
| GET | `/api/zones/{key}` | монстры зоны + координаты для canvas |
| POST | `/api/battle/start` | начать бой с монстром |
| POST | `/api/battle/attack` | сделать ход |
| POST | `/api/battle/flee` | отступить |
| GET | `/api/battle/current` | незавершённый бой |
| POST | `/api/move` | сохранить позицию персонажа |
| POST | `/api/heal` | лечение в таверне |
| GET | `/api/shop?kind=` | каталог (`weapon/armor/potion/material`) |
| POST | `/api/shop/buy` | купить предмет |
| POST | `/api/shop/sell` | продать (50 % цены) |
| GET | `/api/inventory` | инвентарь |
| POST | `/api/inventory/equip` | надеть/снять |
| POST | `/api/inventory/use` | использовать зелье |
| GET/POST | `/api/chat` | чтение и отправка сообщений |
| GET | `/api/rating` | топ-50 персонажей |
| GET | `/api/news` | новости |

Формат ответа: `{"ok":true, ...}` либо `{"ok":false,"error":"…","code":"…"}`.

---

## Отличия от legacy-версии

| Было (PHP 5.x) | Стало (PHP 8.1) |
|---|---|
| `mysql_*` (удалено в PHP 7) | PDO + prepared statements |
| `md5($pass)` | `password_hash` / `password_verify` |
| 230 файлов с `include` цепочками | один фронт-контроллер + PSR-4 |
| WAP XHTML-вёрстка таблицами | Canvas + HTML5, адаптив |
| Полная перезагрузка страницы | AJAX/JSON без перезагрузок |
| `$_SESSION` без защиты | HttpOnly/SameSite + CSRF |
| Картинки для графики | процедурная отрисовка в canvas |
