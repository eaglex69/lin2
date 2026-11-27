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

Это **не функциональный тест**: поддельная БД не хранит данные, поэтому проверяется только то, что код выполняется
на PHP 8 без исключений. Полный прогон занимает около 7 минут на 2 ядрах.
