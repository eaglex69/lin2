<?php
declare(strict_types=1);

/**
 * Miniage v2 — установщик для хостинга.
 *
 * Что делает:
 *   1. проверяет окружение (PHP 8.1+, расширения, права на запись);
 *   2. принимает параметры базы — MySQL (по умолчанию) или SQLite;
 *   3. проверяет соединение, создаёт базу при необходимости;
 *   4. применяет схему через тот же Migrator, что и игра (идемпотентно);
 *   5. пишет .env;
 *   6. после установки блокируется от повторного запуска.
 *
 * Раскладка: работает и вложенная (v2/public/install.php), и плоская
 * (все файлы в public_html).
 *
 * После установки этот файл нужно удалить с сервера.
 *
 * Функции объявлены под function_exists(), а вся страница собирается в
 * miniage_install_main(): файл можно подключать повторно в одном процессе
 * (так его проверяет тестовый харнесс), и он не падает на redeclare.
 */

defined('MINIAGE_INSTALL_VERSION') || define('MINIAGE_INSTALL_VERSION', '2.0');

if (!function_exists('miniage_install_root')) {
    /** Корень приложения: каталог с src/, db/, var/. */
    function miniage_install_root(): string
    {
        $here = __DIR__;
        if (is_dir($here . '/src') && is_dir($here . '/db')) {
            return $here;                       // плоская раскладка
        }
        if (is_dir(dirname($here) . '/src')) {
            return dirname($here);              // вложенная: public/ внутри v2/
        }
        return $here;
    }
}

if (!function_exists('miniage_install_h')) {
    function miniage_install_h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('miniage_install_write_env')) {
    /** @param array<string,string> $env */
    function miniage_install_write_env(string $file, array $env): bool
    {
        $lines = [
            '# Miniage v2 — конфигурация, создана установщиком ' . date('Y-m-d H:i:s'),
            '# Не выкладывайте этот файл в открытый доступ.',
        ];
        foreach ($env as $k => $v) {
            // берём в кавычки, если есть пробелы, решётка или кавычки
            $needsQuote = $v !== '' && preg_match('/[\s#"\']/', $v) === 1;
            $lines[] = $k . '=' . ($needsQuote ? '"' . str_replace('"', '\"', $v) . '"' : $v);
        }
        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, implode("\n", $lines) . "\n") === false) {
            return false;
        }
        @chmod($tmp, 0640);
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }
}

if (!function_exists('miniage_install_test_mysql')) {
    /** Пробное подключение к MySQL. Возвращает [ok, сообщение]. */
    function miniage_install_test_mysql(
        string $host,
        string $port,
        string $name,
        string $user,
        string $pass,
        string $prefix
    ): array {
        $dsnBase = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
        try {
            $pdo = new PDO($dsnBase, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            return [false, 'Не удалось подключиться к MySQL: ' . $e->getMessage()];
        }

        // базы может не быть — пробуем создать; на shared-хостинге это обычно
        // запрещено, тогда просим создать её в панели (ошибку не считаем фатальной)
        $safeName = str_replace('`', '', $name);
        try {
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $safeName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (PDOException $e) {
            error_log('[miniage/install] CREATE DATABASE: ' . $e->getMessage());
        }

        try {
            $pdo->exec('USE `' . $safeName . '`');
            $ver = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        } catch (PDOException $e) {
            return [false, 'База «' . $name . '» недоступна: ' . $e->getMessage()
                . '. Создайте её в панели хостинга и повторите.'];
        }

        if (version_compare($ver, '5.7.0', '<')) {
            return [false, 'MySQL ' . $ver . ' слишком старый: нужен 5.7+ (лучше 8.0).'];
        }
        // проверка прав на запись: пробная таблица в этой базе
        $probe = '`' . $prefix . 'install_probe`';
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$probe} (id INT PRIMARY KEY) ENGINE=InnoDB");
            $pdo->exec("DROP TABLE {$probe}");
        } catch (PDOException $e) {
            return [false, 'Нет прав на создание таблиц в базе «' . $name . '»: ' . $e->getMessage()];
        }
        return [true, 'MySQL ' . $ver . ', база «' . $name . '» доступна, права на запись есть.'];
    }
}

if (!function_exists('miniage_install_test_sqlite')) {
    /** Пробное подключение к SQLite. Возвращает [ok, сообщение]. */
    function miniage_install_test_sqlite(string $path): array
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return [false, 'Не удалось создать каталог ' . $dir];
        }
        if (!is_writable($dir)) {
            return [false, 'Каталог ' . $dir . ' недоступен для записи (chmod 775).'];
        }
        try {
            $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('CREATE TABLE IF NOT EXISTS __probe (id INTEGER PRIMARY KEY)');
            $pdo->exec('DROP TABLE __probe');
        } catch (PDOException $e) {
            return [false, 'SQLite недоступен: ' . $e->getMessage()];
        }
        return [true, 'SQLite: файл ' . $path . ' доступен для записи.'];
    }
}

if (!function_exists('miniage_install_main')) {
    function miniage_install_main(): void
    {
        $root = miniage_install_root();
        $envFile = $root . '/.env';
        $lockFile = $root . '/var/.installed';
        $installed = is_file($lockFile);

        // ------------------------------------------------------------ проверки
        $checks = [];
        $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
        $checks[] = ['PHP ' . PHP_VERSION, $phpOk, $phpOk ? 'ок' : 'нужен PHP 8.1 или новее'];

        foreach (['pdo' => true, 'json' => true, 'mbstring' => false, 'openssl' => false] as $ext => $required) {
            $ok = extension_loaded($ext);
            $checks[] = [
                'Расширение ' . $ext,
                $ok || !$required,
                $ok ? 'ок' : ($required ? 'обязательно' : 'не критично (есть резервный код)'),
            ];
        }
        $hasMysql = extension_loaded('pdo_mysql');
        $hasSqlite = extension_loaded('pdo_sqlite');
        $checks[] = [
            'Драйвер pdo_mysql',
            $hasMysql || $hasSqlite,
            $hasMysql ? 'ок' : ($hasSqlite ? 'нет (доступен только SQLite)' : 'нужен pdo_mysql или pdo_sqlite'),
        ];
        $checks[] = ['Драйвер pdo_sqlite', true, $hasSqlite ? 'ок' : 'нет (не обязателен)'];

        $varDir = $root . '/var';
        $varOk = (is_dir($varDir) && is_writable($varDir)) || @mkdir($varDir, 0775, true);
        $checks[] = ['Каталог var/ для записи', (bool) $varOk, $varOk ? 'ок' : 'chmod 775 ' . $varDir];
        $srcOk = is_dir($root . '/src') && is_dir($root . '/db');
        $checks[] = ['Файлы игры на месте', $srcOk, $srcOk ? 'ок' : 'нет src/ или db/ — распакуйте архив целиком'];
        $checks[] = [
            'Повторная установка',
            !$installed,
            $installed
                ? 'игра уже установлена — удалите var/.installed, чтобы переустановить'
                : 'ок',
        ];

        $fatal = false;
        foreach ($checks as $c) {
            if ($c[1] !== true) {
                $fatal = true;
            }
        }

        // ---------------------------------------------------------- обработка
        $errors = [];
        $done = false;
        $log = [];

        $in = [
            'db_driver' => (string) ($_POST['db_driver'] ?? 'mysql'),
            'db_host'   => trim((string) ($_POST['db_host'] ?? 'localhost')),
            'db_port'   => trim((string) ($_POST['db_port'] ?? '3306')),
            'db_name'   => trim((string) ($_POST['db_name'] ?? 'miniage')),
            'db_user'   => trim((string) ($_POST['db_user'] ?? '')),
            'db_pass'   => (string) ($_POST['db_pass'] ?? ''),
            'db_prefix' => trim((string) ($_POST['db_prefix'] ?? '')),
            'seed_demo' => ($_POST['seed_demo'] ?? '1') === '1',
            'demo_pass' => (string) ($_POST['demo_pass'] ?? ''),
            'app_url'   => trim((string) ($_POST['app_url'] ?? '')),
            'timezone'  => trim((string) ($_POST['timezone'] ?? 'Europe/Moscow')),
        ];

        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        if ($isPost && !$installed) {
            // --- валидация
            if (!in_array($in['db_driver'], ['mysql', 'sqlite'], true)) {
                $errors[] = 'Неизвестный тип базы.';
            }
            if ($in['db_driver'] === 'mysql') {
                if ($in['db_host'] === '' || $in['db_name'] === '' || $in['db_user'] === '') {
                    $errors[] = 'Для MySQL нужны хост, имя базы и пользователь.';
                }
                if (preg_match('/^\d+$/', $in['db_port']) !== 1) {
                    $errors[] = 'Порт должен быть числом.';
                }
                if ($in['db_name'] !== '' && preg_match('/^[A-Za-z0-9_]+$/', $in['db_name']) !== 1) {
                    $errors[] = 'Имя базы: только латиница, цифры и подчёркивание.';
                }
            }
            if ($in['db_prefix'] !== '' && preg_match('/^[A-Za-z0-9_]{1,12}$/', $in['db_prefix']) !== 1) {
                $errors[] = 'Префикс таблиц: до 12 символов, латиница/цифры/подчёркивание.';
            }
            if (preg_match('#^[A-Za-z][A-Za-z0-9_./+-]*$#', $in['timezone']) !== 1) {
                $errors[] = 'Некорректная таймзона.';
            }
            if ($in['demo_pass'] !== '' && strlen($in['demo_pass']) < 8) {
                $errors[] = 'Пароль демо-аккаунта: минимум 8 символов (или оставьте поле пустым).';
            }
            if ($in['seed_demo'] && $in['demo_pass'] === '') {
                $in['demo_pass'] = 'demo';   // витрина: вход demo/demo, как в README
            }

            $sqlitePath = $root . '/var/miniage.sqlite';
            if ($errors === []) {
                // --- соединение
                if ($in['db_driver'] === 'mysql') {
                    [$ok, $msg] = miniage_install_test_mysql(
                        $in['db_host'], $in['db_port'], $in['db_name'], $in['db_user'], $in['db_pass'], $in['db_prefix']
                    );
                } else {
                    [$ok, $msg] = miniage_install_test_sqlite($sqlitePath);
                }
                $log[] = ($ok ? '✔ ' : '✖ ') . $msg;
                if (!$ok) {
                    $errors[] = $msg;
                }
            }

            if ($errors === []) {
                // --- .env
                $env = [
                    'APP_NAME'        => 'Miniage',
                    'APP_ENV'         => 'production',
                    'APP_URL'         => $in['app_url'],
                    'DB_DRIVER'       => $in['db_driver'],
                    'DB_HOST'         => $in['db_host'],
                    'DB_PORT'         => $in['db_port'],
                    'DB_NAME'         => $in['db_name'],
                    'DB_USER'         => $in['db_user'],
                    'DB_PASS'         => $in['db_pass'],
                    'DB_SQLITE_PATH'  => 'var/miniage.sqlite',
                    'DB_PREFIX'       => $in['db_prefix'],
                    'SESSION_NAME'    => 'miniage_sid',
                    'SESSION_TTL'     => '86400',
                    'SEED_DEMO'       => $in['seed_demo'] ? '1' : '0',
                    'SEED_DEMO_PASS'  => $in['demo_pass'],
                    'SEED_ADMIN_PASS' => '',
                    'TIMEZONE'        => $in['timezone'],
                    'TRUSTED_PROXIES' => '',
                ];
                if (!miniage_install_write_env($envFile, $env)) {
                    $errors[] = 'Не удалось записать .env в ' . $root . ' (нужны права на запись в каталог).';
                } else {
                    $log[] = '✔ Конфигурация записана в .env';
                }
            }

            if ($errors === []) {
                // --- миграция теми же классами, что у игры
                try {
                    defined('MINIAGE_ROOT') || define('MINIAGE_ROOT', $root);
                    if (!isset($GLOBALS['__miniage_install_autoload'])) {
                        $GLOBALS['__miniage_install_autoload'] = true;
                        spl_autoload_register(static function (string $class) use ($root): void {
                            $prefix = 'App\\';
                            if (!str_starts_with($class, $prefix)) {
                                return;
                            }
                            $file = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                            if (is_file($file)) {
                                require $file;
                            }
                        });
                    }
                    // путь к SQLite в .env относительный — Config раскрывает его
                    // от корня приложения (см. Config::load)
                    if (class_exists('App\\Core\\Config')) {
                        \App\Core\Config::reload();
                        \App\Core\Config::load($root);
                    }
                    \App\Core\Database::reset();
                    \App\Core\Database::boot();
                    \App\Core\Migrator::migrate();
                    $mobs = (int) \App\Core\Database::value(
                        'SELECT COUNT(*) FROM ' . \App\Core\Database::table('mobs')
                    );
                    $log[] = '✔ Схема применена, мобов в базе: ' . $mobs;

                    @file_put_contents($lockFile, date('c') . "\n");
                    $done = true;
                    $log[] = '✔ Установка завершена';
                } catch (Throwable $e) {
                    $log[] = '✖ Миграция: ' . $e->getMessage();
                    $errors[] = 'Не удалось применить схему: ' . $e->getMessage();
                }
            }
        }

        $hostName = (string) ($_SERVER['HTTP_HOST'] ?? 'ваш-сайт');
        $baseUrl = $in['app_url'] !== '' ? rtrim($in['app_url'], '/') : (
            ((($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off') ? 'https://' : 'http://') . $hostName
        );
        $h = 'miniage_install_h';
        ?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Установка Miniage v2</title>
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  body { margin: 0; padding: 28px 16px; background: #14120f; color: #e8e2d6;
         font: 15px/1.55 system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
  .wrap { max-width: 860px; margin: 0 auto; }
  h1 { font-size: 26px; margin: 0 0 4px; letter-spacing: .3px; }
  h1 b { color: #d8b26a; }
  .sub { color: #9c9484; margin: 0 0 22px; font-size: 14px; }
  .card { background: #1c1915; border: 1px solid #2d2822; border-radius: 10px;
          padding: 18px 20px; margin: 0 0 16px; }
  h2 { font-size: 16px; margin: 0 0 12px; color: #d8b26a; font-weight: 600; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  td { padding: 5px 6px; border-bottom: 1px solid #262119; vertical-align: top; }
  tr:last-child td { border-bottom: 0; }
  .ok { color: #7bd389; }
  .bad { color: #ff8787; }
  .muted { color: #948c7c; }
  .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px 16px; }
  label { display: block; font-size: 13px; color: #b3aa98; margin-bottom: 4px; }
  input, select { width: 100%; padding: 8px 10px; background: #12100e; color: #e8e2d6;
                  border: 1px solid #3a332b; border-radius: 6px; font: inherit; }
  input:focus, select:focus { outline: 2px solid #6d5a34; border-color: #6d5a34; }
  .hint { font-size: 12px; color: #8f8778; margin-top: 3px; }
  .btn { display: inline-block; margin-top: 18px; padding: 11px 22px; border: 0; border-radius: 7px;
         background: #d8b26a; color: #1a1712; font: inherit; font-weight: 600; cursor: pointer;
         text-decoration: none; }
  .btn:hover { background: #e8c67f; }
  .btn[disabled] { background: #4a4238; color: #8c8478; cursor: not-allowed; }
  ul.log { list-style: none; padding: 0; margin: 0; font-size: 14px; }
  ul.log li { padding: 4px 0; border-bottom: 1px solid #262119; }
  ul.log li:last-child { border-bottom: 0; }
  .err { background: #2a1a1a; border: 1px solid #533; color: #ffb3b3; border-radius: 6px;
         padding: 10px 12px; margin: 0 0 14px; font-size: 14px; }
  code { background: #262119; padding: 1px 5px; border-radius: 4px; font-size: 13px; }
  .row-tabs { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
  .tab { padding: 7px 14px; border-radius: 6px; border: 1px solid #3a332b; background: #12100e;
         color: #b3aa98; cursor: pointer; font: inherit; }
  .tab.active { background: #d8b26a; color: #1a1712; border-color: #d8b26a; font-weight: 600; }
  .hidden { display: none !important; }
</style>
</head>
<body>
<div class="wrap">
  <h1><b>miniage</b> ru — установка</h1>
  <p class="sub">PHP 8.1+ · MySQL или SQLite · AJAX · Canvas · установщик <?= $h(MINIAGE_INSTALL_VERSION) ?></p>

<?php if ($installed && !$done): ?>
  <div class="card">
    <h2>Игра уже установлена</h2>
    <p>Найдена отметка <code>var/.installed</code>. Повторная установка выключена,
       чтобы случайно не перезаписать рабочую базу.</p>
    <p class="muted">Переустановка: удалите <code>var/.installed</code> и обновите
       страницу. <b>Внимание:</b> данные не удаляются, но схема и демо-данные
       применятся заново.</p>
    <p><a class="btn" href="index.php">Открыть игру →</a></p>
  </div>

<?php elseif ($done): ?>
  <div class="card">
    <h2>Готово</h2>
    <ul class="log"><?php foreach ($log as $line): ?><li><?= $h($line) ?></li><?php endforeach; ?></ul>
  </div>
  <div class="card">
    <h2>Что дальше</h2>
    <ol>
      <li><b>Удалите <code>install.php</code></b> с сервера — он больше не нужен.</li>
      <?php if ($in['seed_demo']): ?>
        <li>Демо-вход: <b>demo / <?= $h($in['demo_pass']) ?></b>.
            Для боевого сервера создайте свой аккаунт и выключите демо
            (в <code>.env</code>: <code>SEED_DEMO=0</code>, затем удалите
            демо-аккаунт из базы).</li>
      <?php else: ?>
        <li>Демо-аккаунты выключены — зарегистрируйтесь на странице входа.</li>
      <?php endif; ?>
      <li>Убедитесь, что <code>.env</code> и <code>var/</code> закрыты от прямого
          доступа из браузера (это делает <code>.htaccess</code> из архива).</li>
      <li>Проверка живости: <code><?= $h($baseUrl) ?>/api/health</code></li>
    </ol>
    <p><a class="btn" href="index.php">Открыть игру →</a></p>
  </div>

<?php else: ?>
  <div class="card">
    <h2>1. Проверка сервера</h2>
    <table>
      <?php foreach ($checks as [$name, $ok, $note]): ?>
        <tr>
          <td><?= $h($name) ?></td>
          <td class="<?= $ok ? 'ok' : 'bad' ?>" style="width:70px"><?= $ok ? '✔' : '✖' ?></td>
          <td class="muted"><?= $h($note) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <?php if ($fatal): ?>
    <div class="card"><p class="bad">Сервер не готов к установке: исправьте пункты
      с ✖ и обновите страницу.</p></div>
  <?php else: ?>
  <form method="post" autocomplete="off">
    <div class="card">
      <h2>2. База данных</h2>
      <?php foreach ($errors as $e): ?><div class="err"><?= $h($e) ?></div><?php endforeach; ?>

      <div class="row-tabs">
        <button type="button" class="tab <?= $in['db_driver'] === 'mysql' ? 'active' : '' ?>" data-tab="mysql">MySQL</button>
        <button type="button" class="tab <?= $in['db_driver'] === 'sqlite' ? 'active' : '' ?>" data-tab="sqlite">SQLite (без MySQL)</button>
      </div>

      <input type="hidden" name="db_driver" id="db_driver" value="<?= $h($in['db_driver']) ?>">

      <div id="pane-mysql" class="<?= $in['db_driver'] === 'mysql' ? '' : 'hidden' ?>">
        <div class="grid">
          <div>
            <label for="db_host">Хост</label>
            <input id="db_host" name="db_host" value="<?= $h($in['db_host']) ?>" placeholder="localhost">
            <div class="hint">На shared-хостинге обычно <code>localhost</code></div>
          </div>
          <div>
            <label for="db_port">Порт</label>
            <input id="db_port" name="db_port" value="<?= $h($in['db_port']) ?>" inputmode="numeric">
          </div>
          <div>
            <label for="db_name">Имя базы</label>
            <input id="db_name" name="db_name" value="<?= $h($in['db_name']) ?>">
          </div>
          <div>
            <label for="db_user">Пользователь</label>
            <input id="db_user" name="db_user" value="<?= $h($in['db_user']) ?>">
          </div>
          <div>
            <label for="db_pass">Пароль</label>
            <input id="db_pass" name="db_pass" type="password" value="<?= $h($in['db_pass']) ?>">
          </div>
          <div>
            <label for="db_prefix">Префикс таблиц</label>
            <input id="db_prefix" name="db_prefix" value="<?= $h($in['db_prefix']) ?>" placeholder="необязательно">
            <div class="hint">Если в базе уже есть другие таблицы</div>
          </div>
        </div>
      </div>

      <div id="pane-sqlite" class="<?= $in['db_driver'] === 'sqlite' ? '' : 'hidden' ?>">
        <p class="muted" style="margin:0">База будет создана в <code>var/miniage.sqlite</code>.
          Подходит, если MySQL нет — но MySQL быстрее и надёжнее при нескольких игроках.</p>
      </div>
    </div>

    <div class="card">
      <h2>3. Игра</h2>
      <div class="grid">
        <div>
          <label for="app_url">Адрес игры</label>
          <input id="app_url" name="app_url" value="<?= $h($baseUrl) ?>">
          <div class="hint">Можно оставить как есть</div>
        </div>
        <div>
          <label for="timezone">Часовой пояс</label>
          <input id="timezone" name="timezone" value="<?= $h($in['timezone']) ?>">
          <div class="hint">Например, <code>Europe/Moscow</code> или <code>UTC</code></div>
        </div>
        <div>
          <label for="demo_pass">Пароль демо-аккаунта</label>
          <input id="demo_pass" name="demo_pass" value="<?= $h($in['demo_pass']) ?>" placeholder="demo">
          <div class="hint">Логин <code>demo</code>. Минимум 8 символов, если задаёте</div>
        </div>
        <div>
          <label for="seed_demo">Демо-аккаунты</label>
          <select id="seed_demo" name="seed_demo">
            <option value="1" <?= $in['seed_demo'] ? 'selected' : '' ?>>Создать demo (для пробы)</option>
            <option value="0" <?= !$in['seed_demo'] ? 'selected' : '' ?>>Не создавать (для боевого сервера)</option>
          </select>
        </div>
      </div>
    </div>

    <button class="btn" type="submit">Установить →</button>
    <p class="hint" style="margin-top:10px">Установщик создаст таблицы и демо-данные в
      указанной базе. Существующие таблицы с теми же именами не удаляются,
      схема только дополняется.</p>
  </form>
  <?php endif; ?>
<?php endif; ?>
</div>
<script>
  // переключение MySQL / SQLite
  document.querySelectorAll('.tab[data-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var t = btn.dataset.tab;
      var hidden = document.getElementById('db_driver');
      if (hidden) hidden.value = t;
      document.querySelectorAll('.tab[data-tab]').forEach(function (b) {
        b.classList.toggle('active', b === btn);
      });
      var pm = document.getElementById('pane-mysql');
      var ps = document.getElementById('pane-sqlite');
      if (pm) pm.classList.toggle('hidden', t !== 'mysql');
      if (ps) ps.classList.toggle('hidden', t !== 'sqlite');
    });
  });
</script>
</body>
</html>
        <?php
    }
}

miniage_install_main();
