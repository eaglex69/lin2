# tools/php8 - инструменты проверки порта на PHP 8

Это **вспомогательные** скрипты, игре они не нужны (на боевой сервер их можно не выкладывать).
Системного PHP в среде разработки не было, поэтому всё запускается на PHP, собранном в WebAssembly
(пакет `@php-wasm/node`: PHP 5.2, 7.4, 8.0 - 8.5), нужен только Node.js >= 20.

```sh
cd tools/php8
npm install            # php-wasm и mysql2 (для поддельного MySQL-сервера)
```

Корень проверяемого проекта - два каталога выше (или переменная `ROOT=/путь`).

## Синтаксис

```sh
node lint.mjs 8.3                  # php -l для всех *.php; версии: 5.2 7.4 8.0 8.1 8.2 8.3 8.4 8.5
CONC=3 node lint.mjs 8.0 inc/db.php battle.php    # только указанные файлы
```

## Статический аудит (скрипты PHP, запускаются через `runphp.mjs`)

Проект монтируется в `/work`, `tools/php8/scripts` - в `/tools`.

```sh
node runphp.mjs 8.3 /tools/audit.php /work              # конструкции, несовместимые с PHP 8 (короткие теги, ereg, {} и т. д.)
node runphp.mjs 8.3 /tools/fix.php /work [--dry]        # автоправки: <? -> <?php, 'ключи' в кавычки (идемпотентно, по токенам)
node runphp.mjs 8.3 /tools/mysql_calls.php /work        # какие mysql_*() вызываются и сколько раз
node runphp.mjs 8.3 /tools/mysql_entries.php /work      # входные файлы, до которых не дошёл слой mysql_* (inc/mysql_compat.php)
node runphp.mjs 8.3 /tools/globals_flow.php /work       # переменные, которые раньше создавал register_globals
node runphp.mjs 8.3 /tools/divisions.php /work          # деления / и % с непостоянным делителем (DivisionByZeroError)
node runphp.mjs 8.3 /tools/arg_counts.php /work         # вызовы функций с недостаточным числом аргументов (ArgumentCountError)
node runphp.mjs 8.3 /tools/dynamic_props.php /work      # свойства классов, создаваемые «на лету» (устарело в 8.2)
node runphp.mjs 8.3 /tools/concat_prec.php /work        # смесь "." и бинарных "+"/"-" без скобок (приоритет изменился в PHP 8)
node runphp.mjs 8.3 /tools/loose_zero.php /work         # $row['col'] == 0 для текстовых столбцов (0 == 'abc' в PHP 8 ложь)
node runphp.mjs 8.3 /tools/pre_db_code.php /work        # входные файлы, исполняющие код до inc/db.php (inc/php8.php там не действует)
node runphp.mjs 8.3 /tools/fix_case_semicolon.php /work [--dry]   # "case X;" -> "case X:" (устарело в PHP 8.5)
node runphp.mjs 8.3 /tools/fix_req_compare.php /work [--dry]      # $_POST['x'] <= 0 -> (float)$_POST['x'] <= 0 (в PHP 8 'abc' <= 0 ложь, в PHP 5 было истина)
node runphp.mjs 8.3 /tools/req_arith.php /work [--all]            # значения из $_GET/$_POST в арифметике и round()/floor() без приведения к числу (TypeError на 'abc' и '')
```

Результат в файл: `OUT=report.txt node runphp.mjs ...`.

## Дымовой тест (`smoke/`)

`smoke/fakedb.mjs` - поддельный MySQL-сервер (протокол MySQL, таблицы берутся из `shema.sql`, `SELECT` возвращает
правдоподобные строки), `smoke/run.mjs` - прогоняет **каждую страницу** игры на PHP 8 как гость и как игрок, с
GET-параметрами и ветками `switch`, найденными в исходнике, и с POST-телом.

```sh
node smoke/run.mjs                              # PHP 8.3, режим отладки (E_ALL), данные «dummy»
node smoke/run.mjs --php 8.5 --only '^battle\.php$'
node smoke/run.mjs --profile zero               # все числа и строки из БД равны 0 (деление на ноль)
node smoke/run.mjs --profile empty              # SELECT ничего не находит
node smoke/run.mjs --garbage abc                # во всех параметрах GET/POST вместо 1 подставляется abc (пусто: --garbage '')
node smoke/run.mjs --prod                       # как на боевом сервере: без LIN2_DEBUG
node smoke/run.mjs --no-dirs --prod             # без каталогов pic/smiles, pic/clan, foto (их нет в репозитории; по умолчанию тест создаёт заглушки)
CONC=2 node smoke/run.mjs                       # число параллельных процессов (по умолчанию 2)
node smoke/bots.mjs 8.3                         # cron-роботы bonus/ (votes_bot.php, bonus_bot.php) против поддельной БД
```

Итог - `smoke/out/report.txt` (и `report.json`): фатальные ошибки (`TypeError`, `ValueError`, вызовы
несуществующих функций...), предупреждения без шума «undefined variable/key», устаревшие вызовы, обращения к
таблицам, которых нет в `shema.sql`.

## Ссылки: статический аудит, проверка в дымовом тесте и «клик»

Ссылку на несуществующий файл не замечают ни `php -l`, ни дымовой тест без `--links`: страница, на которой она стоит, выполняется
без ошибок. Так была пропущена ссылка «атаковать» на моба: `<a href="ok1231">` - «красивый» адрес старого хостинга
(правило перезаписи в `.htaccess`, которого в репозитории нет); на новом сервере это `404 Not Found`. Три инструмента ловят такое:

```sh
node links_static.mjs                           # без PHP и без БД: все href / action / src="*.php|js" / header("Location: ...") из исходников
node links_static.mjs --legacy                  # то же, плюс ссылки мёртвых файлов (forum/down.php, log.php...)
ROOT=/путь/к/распакованному/архиву node links_static.mjs
node smoke/run.mjs --prod --links --no-dirs     # то же для того, что страницы реально напечатали: <a>, <form>, <script>, <link>, Location (около 29 тысяч ссылок)
node smoke/click.mjs                            # «клик» как в браузере: список мобов -> ссылка «атаковать» -> редирект -> экран боя
node smoke/click.mjs --all --prod               # нажать каждую ссылку на моба из списка
node smoke/click.mjs --page 'okrestnosti.php?mod=info&id=10'   # карточка моба и её кнопка «Атаковать»
node smoke/click.mjs --then '^battle\.php\?mod=ataka&udar=at' --then '^battle\.php\?mod=ataka&udar=at'   # атаковать моба, затем ударить дважды
node smoke/click.mjs --show                     # показать ссылки страницы, на которую попали
```

* `links_static.mjs` считает ошибкой адрес без расширения, который не является каталогом (`ok1231`, `../enter`, `/123`): такие адреса
  работают только если веб-сервер их переписывает, а в репозитории нет настроек сервера. Исключение - список «известных
  пробелов» в начале файла: страницы, которых нет в репозитории (`rydnik.php`, `towers.php`, `library.php`...). Код выхода 1, если
  найдено что-то сверх этого списка.
* `run.mjs --links` проверяет, что файл есть, для каждой ссылки, форм-экшена и редиректа каждой страницы. Ожидаемый остаток на
  чистой выкладке: `<script src="theme/fon.gif">` в подкаталогах (`gm/`, `total/`, `forum/`, `drink/`) - старый тег, который грузит
  картинку как скрипт, безвреден - и пять страниц из `index_site.php`, которых нет в репозитории.
* `click.mjs` ведёт себя как браузер: хранит cookie и сессию PHP, идёт по редиректам, а путь, которого нет среди файлов, получает
  «404 Not Found» - как от веб-сервера без правил перезаписи. Поддельная БД работает в режиме `remember`: после `INSERT` в `tmp`
  («игрок в бою») `battle.php` видит бой, после `DELETE` - нет (в общем прогоне этого режима нет: сервер там один на все страницы).
  Пример: `ROOT=<старая копия> node smoke/click.mjs` показывает `GET /ok10  404 NOT FOUND`, текущий код - `GET /ok.php?id=10  302 -> battle.php?  200`.
  Без `--prod` сообщения PHP пишутся в журнал ошибок, а не в страницу (иначе вывод раньше `header()` ломал бы сами редиректы), и выводятся
  после каждого шага.

## Установщик (`install.php`)

```sh
node smoke/install.mjs 8.0 8.3 8.5              # install.php целиком: форма, ошибки, установка, повторный запуск, сбой и повтор, режим «подключить базу»...
node smoke/install.mjs 8.3 --verbose            # со списком всех проверок
node smoke/sqlsplit.mjs 8.3                     # разбор shema.sql на запросы: трудные случаи, настоящие дампы, 4000 случайных входов
DUMP_QUERIES=/tmp/q.json node smoke/install.mjs # сохранить все запросы установки (например, чтобы прогнать их через парсер MySQL из sqlglot)
```

Для этих тестов `smoke/fakedb.mjs` запускается в режиме `stateful`: сервер помнит базы и таблицы (`CREATE` / `DROP` / `SHOW TABLES` /
`CREATE DATABASE` / `USE`), отвечает 1045 на неверного пользователя и 1049 на неизвестную базу и, как MySQL, отвергает неизвестные
колонки (1054), неверное число значений в `INSERT` (1136) и обращения к несуществующим таблицам (1146). Все запросы записываются в
`state.queries`. Это не MySQL: типы, ключи и режимы SQL не проверяются.

Чтобы убедиться, что тест не «пустой», внесите в копию репозитория поломку (опечатка в имени колонки, отключённая проверка CSRF) и запустите
тест с `ROOT=/путь/к/копии` - он должен упасть.

Это **не функциональный тест**: поддельная БД не хранит данные (кроме режима `remember` у `click.mjs`), поэтому проверяется только то, что
код выполняется на PHP 8 без исключений. Полный прогон занимает от 3 до 7 минут (зависит от версии PHP и `CONC`).

## Игра целиком в браузере (`demo/`) и проверки оформления (`ui/`)

Дымовой тест с поддельной БД не показывает, как игра выглядит и как работают бои. Для этого есть **демо-сервер**: он запускает сайт
из репозитория на PHP 8 (WebAssembly) и хранит данные в SQLite в памяти, но «говорит» с PHP по протоколу MySQL, так что код игры
не меняется. Это инструмент разработки (данные пропадают при перезапуске, нет HTTPS и `.htaccess`), хостингом он быть не может.

```sh
cd tools/php8
node --disable-warning=ExperimentalWarning demo/server.mjs --port 8080            # http://localhost:8080/ ; нужен Node.js >= 22.5 (node:sqlite)
node --disable-warning=ExperimentalWarning demo/server.mjs --port 8081 --php 8.0  # другая версия PHP
node demo/sqldb_test.mjs                                                          # 23 проверки перевода SQL MySQL -> SQLite
```

Данные берутся из `shema.sql` (7941 монстр, магазины, охотничьи угодья), плюс три готовых персонажа (пароль `tester1`): `Tester` -
воин 24 уровня (в браузере вы входите под ним без пароля), `Mage01` - маг 22 уровня (соперник для `pk.php`), `Admin1` - администратор.
`/_demo/reset` возвращает данные к исходным, `/_demo/login/<ник>` входит под другим персонажем, `/_demo/sql?q=...` выполняет запрос
(удобно подготовить сцену для теста). Файлы `.php`, `.css`, `.js` читаются с диска на каждый запрос: правка видна после обновления
страницы. Не открывайте `gm/` и `total/` под обычным игроком: игра сама банит за это (как и раньше), `/_demo/reset` снимает бан.

Проверки в настоящем браузере (Chromium без окна из пакета `@sparticuz/chromium`, внешняя сеть для страниц закрыта):

```sh
cd tools/php8/ui && npm install                       # puppeteer-core и Chromium (~80 МБ)
node ajax_test.mjs                                    # переходы без перезагрузки: ссылки, формы, Назад / Вперёд, скрипты, ... (23 проверки)
node battle_test.mjs [каталог-для-скриншотов]         # бой с монстром: без JavaScript и с ним, удар, ответ, победа, поражение (18)
node duel_test.mjs [каталог-для-скриншотов]           # pk.php, combat.php, fight.php и перенос полос между страницами (7)
node shop_test.mjs                                    # город (плитки), магазины, покупка, инвентарь, 16 страниц на 360 px (78 проверок)
node seed.mjs                                         # наполнить сумку Tester'а вещами, ресурсами, питомцами (41 запрос) - для скриншотов
AS=Tester W=390 node probe.mjs /gorod.php "document.title"   # выполнить выражение JS на странице и напечатать результат (JSON)
node shots.mjs /tmp/shots --w 540 / /pers.php         # скриншоты страниц (--as Admin1, --guest, --maxh 4000)
node overflow.mjs 360 / /pers.php /okrestnosti.php    # нет ли горизонтальной прокрутки на экране телефона (AS=Admin1 для админских страниц)
BASE=http://127.0.0.1:8081 node ajax_test.mjs         # те же проверки против сервера на другом порту / версии PHP
```

Каждый тест сам делает `/_demo/reset` и готовит нужные строки в базе (`shop_test.mjs` ещё и наполняет сумку, как `seed.mjs`). После
`battle_test.mjs` и `duel_test.mjs` Tester остаётся «убитым» (страницы показывают только «Вы убиты!»): перед скриншотами сделайте
`/_demo/reset` и `node seed.mjs`. Код выхода 1, если проверка не прошла или в консоли браузера
появилась ошибка JavaScript. Описание того, что проверяется, - в `THEME.md`.

Картинки-значки темы (`theme/fantasy/g-*.svg`, `corner-steel.svg`) рисует скрипт, а не человек:

```sh
python3 tools/php8/art/make_glyphs.py            # перерисовать в theme/fantasy/ (или укажите каталог: ... make_glyphs.py /tmp/glyphs)
```

Нужен только Python 3 (без сторонних пакетов). Список значков и их рисунки - в самом скрипте; имя `g-<имя>` используется в плитках
(`lin2_ui_tiles()` в `inc/lin2_ui.php`).
