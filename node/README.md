# Miniage на Node.js

Перевод WAP-MMORPG **Miniage (L2War)** с PHP на Node.js. Оригинальные PHP-файлы
остались в корне репозитория нетронутыми — Node-версия лежит в папке `node/`
и отвечает на **те же URL** (`/index.php`, `/pers.php`, `/battle.php`, …),
поэтому все ссылки внутри игры работают без изменений.

## Запуск

Требуется только Node.js ≥ 18. Зависимостей нет — `npm install` не нужен.

```bash
cd node
node server.js          # http://localhost:3000
# или: npm start
# другой порт: PORT=8080 node server.js
```

**Демо-вход:** логин `demo` / пароль `demo` (персонаж Demo, 10 ур., 30 млн аден).
Также доступны `admin` / `admin` и регистрация новых персонажей на `/reg.php`.

## Что реализовано

| PHP | Node.js |
|---|---|
| `inc/db.php`, `inc/mysql.php`, `shema.sql` | `src/db.js` — слой данных (демо в памяти, сид в духе `shema.sql`) |
| `inc/auth.php`, `enter.php` | `src/auth.js` — md5-пароли, сессия `sid`, cookie на 3 часа, активный персонаж |
| `inc/func.php`, `inc/core.php` | `src/func.js` — `sec2day`, `checkin`, пагинация, градиент |
| `inc/opit.php`, `inc/lvl.php` | `src/func.js` — `expPercent()`, `checkLevel()` (кривая опыта из таблицы `lvl`) |
| `inc/regeneration.php` | `src/func.js` — `regeneration()` (+1 HP/MP в сек вне боя) |
| `inc/head.php`, `inc/zag.php`, `inc/down.php` | `src/layout.js` — WAP XHTML, HP/MP/EXP-бары, навигация |
| `index.php`, `reg.php`, `office.php`, `pers.php` | `src/routes.js` |
| `gorod.php`, `okrestnosti.php`, `battle.php`, `fight.php` | `src/routes.js` — город, охота, бой (урон/крит/победа/поражение) |
| `shop.php`, `inventar.php`, `sklad.php`, `bank.php` | `src/routes.js` — магазин, инвентарь, склад, банк |
| `chat.php`, `msg.php`, `news.php`, `rating.php`, `search.php`, `online.php` | `src/routes.js` |
| `clan.php`, `home.php`, `res.php`, `portal.php`, `job.php`, `faq.php`, `rules.php` | `src/routes.js` |
| остальные ~180 `*.php` | `routes.js:stubPage()` — тот же URL + карта переноса |

Полная карта всех 214 PHP-файлов: **[MIGRATION.md](./MIGRATION.md)**
(перегенерируется командой `node tools/generate.js`).

## Вход и сессии

Пароли хешируются MD5, как в `enter.php`; сессия живёт в памяти сервера и
переносится cookie `sid` (HttpOnly, SameSite=Lax). Внутри iframe-превью
браузеры блокируют сторонние cookie — поэтому sid дублируется в URL (`?s=...`),
и игра работает в обоих режимах. Когда cookie доступны, адреса остаются чистыми.

Вход по неверному паролю теперь показывает явное сообщение на главной
(`index.php?error=...`), а не молча возвращает на форму.

## Данные

Демо-БД сохраняется на диск в `node/data/db.json` (переопределяется
`MINIAGE_DB=/path/to.json`), поэтому созданные персонажи, покупки, чат и почта
не пропадают после перезапуска. Файл исключён из git через `node/data/.gitignore`.

## Структура

```
node/
  server.js          — HTTP-сервер, статика (/theme/, /pic/), роутинг
  src/
    db.js            — данные: users, account, mobs, shop, lvl, news, chat, clan…
    auth.js          — авторизация (порт inc/auth.php + enter.php)
    func.js          — игровая математика (порт inc/func.php, lvl, opit, regeneration)
    layout.js        — вёрстка (порт inc/head.php, zag.php, down.php)
    routes.js        — страницы игры (все *.php)
  tools/generate.js  — генератор MIGRATION.md
  MIGRATION.md       — карта переноса PHP -> Node.js
```

## Настоящая MySQL вместо демо-данных

Схема БД не менялась — `../shema.sql` (144 таблицы) подходит как есть.
Чтобы подключить MySQL, замените реализацию в `src/db.js` на `mysql2`
(пакет ставится из npm): запросы `Q.*` уже повторяют семантику
`mysql_query()` из PHP, а поля таблиц совпадают с `shema.sql` один в один.
Параметры подключения в PHP были в `inc/db.php` (`$db_host/$db_user/$db_pass`).

## Проверка

```bash
curl http://localhost:3000/api/health   # {"ok":true,"engine":"node",...}
```
