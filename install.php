<?php
/**
 * Установщик игры Miniage (l2war.mobi) для PHP 8.
 *
 * Откройте этот файл в браузере один раз после загрузки игры на сервер (http://ваш-сайт/install.php).
 * Установщик:
 *   1. проверяет сервер (версия PHP, расширения, права на каталоги);
 *   2. подключается к MySQL и создаёт таблицы игры из shema.sql (и shema_extra.sql);
 *   3. создаёт первого администратора (аккаунт и персонаж с правами dostup = 5);
 *   4. создаёт каталоги для загрузок и записывает параметры базы в inc/config.php.
 *
 * Файл inc/config.php (или переменные окружения LIN2_DB_*) - признак «игра установлена»: пока он есть,
 * установщик ничего не делает, переустановить поверх нельзя. После установки удалите install.php.
 *
 * Установщик самодостаточен (код игры не подключает). Синтаксис рассчитан на PHP 5.2+, чтобы на старом PHP
 * вместо ошибки разбора показалось понятное сообщение.
 */

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>Установка</title></head>'
        . '<body style="font-family:sans-serif;max-width:34em;margin:2em auto;padding:0 1em;line-height:1.5">'
        . '<h2>Нужен PHP 8</h2><p>Для игры нужен PHP 8.0 или новее, а на сервере работает PHP '
        . htmlspecialchars(PHP_VERSION) . '. Переключите версию PHP в панели хостинга и откройте эту страницу снова.</p>'
        . '</body></html>';
    exit;
}

define('LIN2I_ROOT', dirname(__FILE__));

/* ------------------------------------------------------------------------------------------------------------
 * Мелкие помощники
 * ---------------------------------------------------------------------------------------------------------- */

function lin2i_h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Значение поля формы: только строки (массивы вместо строки игнорируются). */
function lin2i_post($key)
{
    return (isset($_POST[$key]) && is_string($_POST[$key])) ? $_POST[$key] : '';
}

function lin2i_config_file()
{
    return LIN2I_ROOT . '/inc/config.php';
}

/** Базовый путь сайта: «/» или «/game/». */
function lin2i_base_url()
{
    $dir = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
    return rtrim($dir, '/') . '/';
}

/** Игра уже установлена? Возвращает текст причины или false. */
function lin2i_installed()
{
    $u = getenv('LIN2_DB_USER');
    $n = getenv('LIN2_DB_NAME');
    if ($u !== false && $u !== '' && $n !== false && $n !== '') {
        return 'Параметры базы заданы переменными окружения LIN2_DB_*.';
    }
    if (file_exists(lin2i_config_file())) {
        return 'Файл inc/config.php уже существует.';
    }
    return false;
}

/* ------------------------------------------------------------------------------------------------------------
 * Разбор SQL-дампа на отдельные запросы
 * ---------------------------------------------------------------------------------------------------------- */

/**
 * Делит текст SQL на запросы. Понимает строки ('..', "..", `..`) с экранированием, комментарии
 * (-- , #, блочные, в том числе условные вида слэш-звёздочка-!40101 ... - они выполняются сервером), смену
 * разделителя командой DELIMITER. Комментарии между запросами отбрасываются.
 */
function lin2i_sql_split($sql)
{
    $out = array();
    $n = strlen($sql);
    $i = (substr($sql, 0, 3) === "\xEF\xBB\xBF") ? 3 : 0;
    $delim = ';';
    $start = -1;

    while ($i < $n) {
        if ($start < 0) {
            // между запросами: пропускаем пробелы и комментарии
            $i += strspn($sql, " \t\r\n", $i);
            if ($i >= $n) {
                break;
            }
            $c = $sql[$i];
            $c2 = substr($sql, $i, 2);
            if ($c === '#' || ($c2 === '--' && ($i + 2 >= $n || strpos(" \t\r\n", $sql[$i + 2]) !== false))) {
                $e = strpos($sql, "\n", $i);
                $i = ($e === false) ? $n : $e + 1;
                continue;
            }
            if ($c2 === '/*' && substr($sql, $i + 2, 1) !== '!') {
                $e = strpos($sql, '*/', $i + 2);
                $i = ($e === false) ? $n : $e + 2;
                continue;
            }
            if (($c === 'D' || $c === 'd') && preg_match('/\GDELIMITER[ \t]+(\S+)[ \t]*(?:\r?\n|\z)/i', $sql, $m, 0, $i)) {
                $delim = $m[1];
                $i += strlen($m[0]);
                continue;
            }
            $start = $i;
        }

        // внутри запроса: прыгаем к ближайшему «интересному» символу
        $dl = strlen($delim);
        $i += strcspn($sql, "'\"`#-/" . $delim[0], $i);
        if ($i >= $n) {
            break;
        }
        $c = $sql[$i];

        if ($c === "'" || $c === '"' || $c === '`') {
            $i++;
            while ($i < $n) {
                $i += ($c === '`') ? strcspn($sql, '`', $i) : strcspn($sql, $c . '\\', $i);
                if ($i >= $n) {
                    break;
                }
                if ($sql[$i] === '\\' && $c !== '`') {
                    $i += 2;
                    continue;
                }
                if ($i + 1 < $n && $sql[$i + 1] === $c) {
                    $i += 2;
                    continue;
                }
                $i++;
                break;
            }
            continue;
        }

        $c2 = substr($sql, $i, 2);
        if ($c === '#' || ($c2 === '--' && ($i + 2 >= $n || strpos(" \t\r\n", $sql[$i + 2]) !== false))) {
            $e = strpos($sql, "\n", $i);
            $i = ($e === false) ? $n : $e + 1;
            continue;
        }
        if ($c2 === '/*') {
            $e = strpos($sql, '*/', $i + 2);
            $i = ($e === false) ? $n : $e + 2;
            continue;
        }
        if (substr($sql, $i, $dl) === $delim) {
            $stmt = trim(substr($sql, $start, $i - $start));
            if ($stmt !== '') {
                $out[] = $stmt;
            }
            $i += $dl;
            $start = -1;
            continue;
        }
        $i++;
    }

    if ($start >= 0) {
        $stmt = trim(substr($sql, $start));
        if ($stmt !== '') {
            $out[] = $stmt;
        }
    }
    return $out;
}

/**
 * Читает shema.sql (обязателен) и shema_extra.sql (по желанию).
 * @return array|false  statements (список array(файл, текст)), tables (имя_в_нижнем_регистре => имя), files (файл => число запросов)
 */
function lin2i_schema(&$error)
{
    $files = array('shema.sql' => true, 'shema_extra.sql' => false);
    $stmts = array();
    $info = array();
    foreach ($files as $name => $required) {
        $path = LIN2I_ROOT . '/' . $name;
        if (!is_file($path) || !is_readable($path)) {
            if ($required) {
                $error = 'Не найден файл ' . $name . ' (дамп базы из комплекта игры): загрузите его рядом с install.php.';
                return false;
            }
            continue;
        }
        $sql = file_get_contents($path);
        if ($sql === false || $sql === '') {
            $error = 'Файл ' . $name . ' пуст или не читается.';
            return false;
        }
        if (!preg_match('//u', $sql)) {
            $error = 'Файл ' . $name . ' не в кодировке UTF-8 (при загрузке по FTP используйте двоичный режим).';
            return false;
        }
        $parts = lin2i_sql_split($sql);
        $info[$name] = count($parts);
        foreach ($parts as $p) {
            $stmts[] = array($name, $p);
        }
    }
    $tables = array();
    foreach ($stmts as $s) {
        if (preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`/i', $s[1], $m)) {
            $tables[strtolower($m[1])] = $m[1];
        }
    }
    if (count($tables) < 10) {
        $error = 'В дампе найдено слишком мало таблиц (' . count($tables) . '): файл shema.sql повреждён?';
        return false;
    }
    return array('statements' => $stmts, 'tables' => $tables, 'files' => $info);
}

/**
 * Таблицы, в которых хранятся данные игроков. В поставляемом shema.sql игроков нет (нет строк account и users), но в этих
 * таблицах остались следы тестовых персонажей и реальных игроков 2013-2015 годов: их навыки (mag, 577 ников), история
 * входов с IP-адресами (vhod), голоса за игру с IP голосовавших (vigre_*), сообщения, а в block_account - запись о блокировке
 * аккаунта с номером 3 (на чистой установке заблокирован был бы третий зарегистрировавшийся игрок).
 */
function lin2i_player_tables()
{
    return array('block_account', 'color_akk', 'komentarai', 'mag', 'mesto', 'msg_r', 'new_usr', 'online', 'online_time',
        'regenerator', 'vhod', 'vigre_bonus', 'vigre_votes', 'vigre_votes_bot');
}

/**
 * Если в дампе нет самих игроков (INSERT в account или users), убирает из него INSERT в таблицы игроков: это «осиротевшие»
 * строки. Дамп с игроками (полная копия действующей игры) импортируется целиком, без изменений.
 * Ключи массива сохраняются: по ним в сообщениях об ошибках называется номер запроса.
 * @return array statements (оставшиеся), skipped (сколько запросов убрано), tables (какие таблицы)
 */
function lin2i_filter_player_data($stmts)
{
    $skipTables = array_flip(lin2i_player_tables());
    foreach ($stmts as $s) {
        if (preg_match('/^\s*(?:INSERT|REPLACE)\s+(?:IGNORE\s+)?INTO\s+`(?:account|users)`/i', $s[1])) {
            return array('statements' => $stmts, 'skipped' => 0, 'tables' => array());
        }
    }
    $skipped = 0;
    $tables = array();
    foreach ($stmts as $k => $s) {
        if (preg_match('/^\s*(?:INSERT|REPLACE)\s+(?:IGNORE\s+)?INTO\s+`([^`]+)`/i', $s[1], $m) && isset($skipTables[strtolower($m[1])])) {
            unset($stmts[$k]);
            $skipped++;
            $tables[strtolower($m[1])] = true;
        }
    }
    return array('statements' => $stmts, 'skipped' => $skipped, 'tables' => array_keys($tables));
}

/* ------------------------------------------------------------------------------------------------------------
 * MySQL
 * ---------------------------------------------------------------------------------------------------------- */

/** «хост», «хост:порт» и «хост:/путь/к/сокету» - как принимала старая функция mysql_connect(). */
function lin2i_split_host($server, &$host, &$port, &$socket)
{
    $host = $server;
    $port = null;
    $socket = null;
    if (preg_match('~^(.*):(\d+)$~', $server, $m) && substr_count($server, ':') === 1) {
        $host = $m[1];
        $port = (int) $m[2];
    } elseif (preg_match('~^(.*):(/.*)$~', $server, $m)) {
        $host = $m[1];
        $socket = $m[2];
    }
    if ($host === '') {
        $host = 'localhost';
    }
}

/**
 * Подключается к серверу MySQL (без выбора базы). При ошибке возвращает false и заполняет $error = array(код, текст).
 */
function lin2i_connect($server, $user, $pass, &$error)
{
    mysqli_report(MYSQLI_REPORT_OFF);
    lin2i_split_host($server, $host, $port, $socket);
    $link = mysqli_init();
    if (!$link) {
        $error = array(0, 'mysqli_init() не сработал');
        return false;
    }
    @mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    try {
        $ok = @mysqli_real_connect($link, $host, $user, $pass, null, $port, $socket);
    } catch (Exception $e) {
        $error = array((int) $e->getCode(), $e->getMessage());
        return false;
    }
    if (!$ok) {
        $error = array(mysqli_connect_errno(), (string) mysqli_connect_error());
        return false;
    }
    if (!@mysqli_set_charset($link, 'utf8')) {
        @mysqli_query($link, "SET NAMES 'utf8'");
    }
    // схема и запросы игры написаны под MySQL 5.5 с пустым sql_mode (в MySQL 5.7/8 режим по умолчанию строгий)
    @mysqli_query($link, "SET SESSION sql_mode=''");
    return $link;
}

function lin2i_quote_ident($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

/** Выполняет один запрос. При ошибке возвращает false и «код: текст» в $error. */
function lin2i_exec($link, $sql, &$error)
{
    try {
        $r = @mysqli_query($link, $sql);
    } catch (Exception $e) {
        $error = $e->getCode() . ': ' . $e->getMessage();
        return false;
    }
    if ($r === false) {
        $error = mysqli_errno($link) . ': ' . mysqli_error($link);
        return false;
    }
    if (is_object($r)) {
        mysqli_free_result($r);
    }
    return true;
}

/** Список таблиц текущей базы (имена в нижнем регистре => как в базе). */
function lin2i_list_tables($link)
{
    $tables = array();
    $r = @mysqli_query($link, 'SHOW TABLES');
    if ($r) {
        while ($row = mysqli_fetch_row($r)) {
            $tables[strtolower($row[0])] = $row[0];
        }
        mysqli_free_result($r);
    }
    return $tables;
}

function lin2i_connect_hint($errno, $msg, $server)
{
    switch ((int) $errno) {
        case 1045:
            $t = 'MySQL не принял логин или пароль (ошибка 1045). Проверьте имя пользователя и пароль.';
            break;
        case 1049:
            $t = 'Такой базы нет (ошибка 1049). Создайте её в панели хостинга или отметьте «Создать базу».';
            break;
        case 1044:
            $t = 'У пользователя нет прав на эту базу (ошибка 1044).';
            break;
        case 2002:
        case 2003:
        case 2005:
        case 2006:
            $t = 'Не удаётся связаться с сервером MySQL «' . $server . '» (ошибка ' . (int) $errno . '). '
                . 'Проверьте адрес: на хостингах это бывает не localhost, а, например, mysql.ваш-хостинг.ru.';
            break;
        default:
            $t = 'Ошибка MySQL ' . (int) $errno . '.';
    }
    return $t . ' [' . $msg . ']';
}

/* ------------------------------------------------------------------------------------------------------------
 * Администратор
 * ---------------------------------------------------------------------------------------------------------- */

/**
 * Создаёт аккаунт и персонажа-администратора теми же запросами, что игра при создании персонажа
 * (office.php, действие add), плюс users.dostup = 5 (доступ к adm_panel.php) и account.active = id персонажа.
 * Пароль хранится так же, как у игроков: md5 (формат входа в enter.php и inc/auth.php).
 * @return bool
 */
function lin2i_create_admin($link, $nick, $pass, $email, &$error)
{
    $e = array();
    $e['nick'] = mysqli_real_escape_string($link, $nick);
    $e['email'] = mysqli_real_escape_string($link, $email);
    $e['ip'] = mysqli_real_escape_string($link, isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '');
    $hash = md5($pass);
    $now = time();
    $n = $e['nick'];

    if (!lin2i_exec($link, "INSERT INTO `account` SET `nick` = '$n', `pass` = '$hash', `email` = '{$e['email']}', "
        . "`ip` = '{$e['ip']}', `ref` = '0', `lvisit` = '$now', `active` = '0', `sekret` = ''", $error)) {
        return false;
    }
    $accId = (int) mysqli_insert_id($link);

    // человек из деревни Talking Island, класс «воин» - значения из office.php
    $hp = 120 + 50;
    $mp = 80 + 50;
    if (!lin2i_exec($link, "INSERT INTO `users` SET `usr` = '$n', `pass` = '$hash', `email` = '{$e['email']}', `ip` = '{$e['ip']}', "
        . "`pol` = 'm', `storona` = 'human', `lvl` = '0', `money` = '10000000000', `almaz` = '0', `skill` = '3', "
        . "`hp` = '$hp', `hpall` = '$hp', `mp` = '$mp', `mpall` = '$mp', `exp` = '0', "
        . "`patt` = '55', `matt` = '21', `pdef` = '68', `mdef` = '49', `klas` = 'fighert', "
        . "`city` = 'Talking Island Village', `lvisit` = '$now', `account` = '$accId', `dostup` = '5'", $error)) {
        return false;
    }
    $uid = (int) mysqli_insert_id($link);

    $items = array(
        array('weapon', 'Club', 768, 160, 160, 0, 0, 1, 1),
        array('body', 'Cotton Robe', 2345, 0, 0, 200, 200, 0, 0),
        array('amulet', 'Necklace of Magic', 60, 0, 0, 125, 180, 0, 0),
    );
    foreach ($items as $it) {
        if (!lin2i_exec($link, "INSERT INTO `item` SET `usr` = '$n', `tip` = '{$it[0]}', `name` = '{$it[1]}', `cena` = '{$it[2]}', "
            . "`patt` = '{$it[3]}', `matt` = '{$it[4]}', `pdef` = '{$it[5]}', `mdef` = '{$it[6]}', "
            . "`soul` = '{$it[7]}', `spirit` = '{$it[8]}', `image` = 'not'", $error)) {
            return false;
        }
    }

    if (!lin2i_exec($link, "INSERT INTO `mag` SET `usr` = '$n', `name` = 'Strong blow', `lat_name` = 'siln_udar', `tip` = 'atack', "
        . "`klas` = 'fighert', `uron` = '20|35|48|61|74|87|100|133|157|240', `mp` = '7|9|10|11|12|13|14|15|16|17', "
        . "`lv` = '1|1|1|3|3|3|5|5|7|7', `hp` = '0|0|0|0|0|0|0|0|0|0', `plushp` = '0|0|0|0|0|0|0|0|0|0', "
        . "`lvlmax` = '8', `cena` = '5|10|20|38|54|80|112|180|260', `lvl` = '1'", $error)) {
        return false;
    }
    if (!lin2i_exec($link, "INSERT INTO `mesto` SET `usr` = '$n', `place` = 'main', `page` = '0', "
        . "`mesto` = 'Shadow of the Monster Tree', `city` = '0'", $error)) {
        return false;
    }
    if (!lin2i_exec($link, "INSERT INTO `regenerator` SET `usr` = '$n', `last` = '$now'", $error)) {
        return false;
    }
    if (!lin2i_exec($link, "INSERT INTO `new_usr` SET `usr` = '$n', `data` = '" . date('y/m/d') . "', `time` = '" . ($now + 86400) . "'", $error)) {
        return false;
    }
    // активный персонаж аккаунта - только что созданный
    if (!lin2i_exec($link, "UPDATE `account` SET `active` = '$uid' WHERE `id` = '$accId' LIMIT 1", $error)) {
        return false;
    }
    return true;
}

/* ------------------------------------------------------------------------------------------------------------
 * Каталоги, файл настроек
 * ---------------------------------------------------------------------------------------------------------- */

/** Создаёт каталоги, в которые игра сохраняет загруженные файлы. @return array каталог => array(создан, доступен_для_записи) */
function lin2i_prepare_dirs()
{
    $res = array();
    foreach (array('pic/gallery', 'foto', 'shmot') as $d) {
        $path = LIN2I_ROOT . '/' . $d;
        $made = false;
        if (!is_dir($path)) {
            $made = @mkdir($path, 0755, true);
        }
        $ok = is_dir($path) && is_writable($path);
        if (is_dir($path) && !$ok) {
            @chmod($path, 0777);
            clearstatcache();
            $ok = is_writable($path);
        }
        $res[$d] = array($made, $ok);
    }
    return $res;
}

function lin2i_config_text($host, $user, $pass, $name)
{
    return "<?php\n"
        . "// Создан установщиком install.php " . date('Y-m-d H:i:s') . ".\n"
        . "// Параметры подключения к MySQL. Не публикуйте этот файл (в git он игнорируется); образец - inc/config.sample.php.\n"
        . "return array(\n"
        . "    'db_host' => " . var_export($host, true) . ",\n"
        . "    'db_user' => " . var_export($user, true) . ",\n"
        . "    'db_pass' => " . var_export($pass, true) . ",\n"
        . "    'db_name' => " . var_export($name, true) . ",\n"
        . ");\n";
}

/** Записывает inc/config.php (через временный файл) и проверяет, что он читается. */
function lin2i_write_config($text, $expect, &$error)
{
    $file = lin2i_config_file();
    $tmp = $file . '.tmp' . getmypid();
    if (@file_put_contents($tmp, $text, LOCK_EX) === false) {
        $error = 'нет прав на запись в каталог inc/';
        return false;
    }
    @chmod($tmp, 0640);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        $error = 'не удалось создать файл inc/config.php';
        return false;
    }
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($file, true);
    }
    $back = @include $file;
    if (!is_array($back) || $back != $expect) {
        $error = 'файл inc/config.php записан, но читается неверно';
        return false;
    }
    return true;
}

/* ------------------------------------------------------------------------------------------------------------
 * Проверка сервера
 * ---------------------------------------------------------------------------------------------------------- */

function lin2i_chk($title, $status, $note)
{
    return array('title' => $title, 'status' => $status, 'note' => $note);
}

/** @return array список проверок: status = ok | warn | fail (fail блокирует установку) */
function lin2i_checks()
{
    $c = array();
    $php8 = version_compare(PHP_VERSION, '8.0.0', '>=');
    $c[] = lin2i_chk('PHP ' . PHP_VERSION, $php8 ? 'ok' : 'fail', $php8 ? '' : 'нужен PHP 8.0 или новее');

    $need = array(
        'mysqli' => 'работа с MySQL',
        'mbstring' => 'обработка текста в UTF-8',
        'iconv' => 'перекодировка текста',
    );
    foreach ($need as $ext => $why) {
        $ok = extension_loaded($ext);
        $c[] = lin2i_chk('Расширение ' . $ext, $ok ? 'ok' : 'fail', $ok ? '' : 'не включено (' . $why . '): включите его в панели хостинга');
    }
    $want = array(
        'gd' => 'без него не будет картинки-капчи при регистрации игроков',
        'xml' => 'нужно только роботу голосования bonus/votes_bot.php',
        'zlib' => 'нужно для сжатия страниц (zlib.output_compression в php.ini из комплекта)',
    );
    foreach ($want as $ext => $why) {
        $ok = extension_loaded($ext);
        $c[] = lin2i_chk('Расширение ' . $ext, $ok ? 'ok' : 'warn', $ok ? '' : 'не включено: ' . $why);
    }

    $sess = (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE);
    $c[] = lin2i_chk('Сессии', $sess ? 'ok' : 'fail', $sess ? '' : 'session_start() не сработал: проверьте session.save_path (каталог должен быть доступен для записи)');

    $sh = LIN2I_ROOT . '/shema.sql';
    $ok = is_file($sh) && is_readable($sh);
    $c[] = lin2i_chk('Файл shema.sql', $ok ? 'ok' : 'fail', $ok ? round(filesize($sh) / 1024) . ' КБ' : 'не найден: загрузите его рядом с install.php');

    $w = is_dir(LIN2I_ROOT . '/inc') && is_writable(LIN2I_ROOT . '/inc');
    $c[] = lin2i_chk('Запись в inc/', $w ? 'ok' : 'warn', $w ? '' : 'нет прав на запись: установщик покажет текст inc/config.php, и вы создадите файл вручную');

    $fu = ini_get('file_uploads');
    $c[] = lin2i_chk('Загрузка файлов (file_uploads)', ($fu && $fu !== 'Off' && $fu !== '0') ? 'ok' : 'warn',
        ($fu && $fu !== 'Off' && $fu !== '0') ? '' : 'выключена: не будут работать галерея и загрузка картинок в админке');

    $assets = is_dir(LIN2I_ROOT . '/pic/roz') && is_dir(LIN2I_ROOT . '/pic/main');
    $c[] = lin2i_chk('Картинки игры (pic/)', $assets ? 'ok' : 'warn',
        $assets ? '' : 'каталога pic/ с картинками (города, умения, смайлы...) в комплекте нет: скопируйте pic/ и shmot/ со старого сервера, иначе вместо картинок будут пустые места');
    return $c;
}

function lin2i_has_fail($checks)
{
    foreach ($checks as $c) {
        if ($c['status'] === 'fail') {
            return true;
        }
    }
    return false;
}

/* ------------------------------------------------------------------------------------------------------------
 * Установка
 * ---------------------------------------------------------------------------------------------------------- */

/** Проверка полей формы. @return array ошибки (пустой - всё в порядке) */
function lin2i_validate($in)
{
    $err = array();
    if ($in['db_host'] === '' || strlen($in['db_host']) > 255 || !preg_match('~^[A-Za-z0-9._:/\[\]-]+$~', $in['db_host'])) {
        $err[] = 'Сервер MySQL: допустимы буквы, цифры и символы . - _ : / (обычно localhost).';
    }
    if (!preg_match('~^[A-Za-z0-9_.$-]{1,64}$~', $in['db_name'])) {
        $err[] = 'Имя базы: до 64 символов - латинские буквы, цифры и символы _ . $ -';
    }
    if ($in['db_user'] === '' || strlen($in['db_user']) > 80 || preg_match('/[\x00-\x1f]/', $in['db_user'])) {
        $err[] = 'Укажите пользователя MySQL.';
    }
    if (strlen($in['db_pass']) > 255 || strpos($in['db_pass'], "\0") !== false) {
        $err[] = 'Пароль MySQL слишком длинный (до 255 символов).';
    }
    if ($in['db_mode'] === 'fresh') {
        if (!preg_match('~^[A-Za-z0-9]{4,10}$~', $in['admin_nick'])) {
            $err[] = 'Ник администратора: от 4 до 10 латинских букв и цифр (как у персонажей в игре).';
        } elseif (is_numeric($in['admin_nick'])) {
            // enter.php принимает числовой логин за номер аккаунта (is_numeric): войти под таким ником было бы нельзя
            $err[] = 'Ник администратора не должен состоять только из цифр и не должен выглядеть как число (например, 1e5): форма входа принимает такой логин за номер аккаунта.';
        }
        if (!preg_match('~^[A-Za-z0-9]{6,15}$~', $in['admin_pass'])) {
            $err[] = 'Пароль администратора: от 6 до 15 латинских букв и цифр (форма входа в игру принимает до 15 символов).';
        } elseif ($in['admin_pass'] !== $in['admin_pass2']) {
            $err[] = 'Пароли администратора не совпадают.';
        }
        if ($in['admin_email'] !== '' && (strlen($in['admin_email']) > 35 || !filter_var($in['admin_email'], FILTER_VALIDATE_EMAIL))) {
            $err[] = 'E-mail указан неверно (или длиннее 35 символов); поле можно оставить пустым.';
        }
    }
    return $err;
}

function lin2i_collect_input()
{
    $in = array(
        'db_host' => trim(lin2i_post('db_host')),
        'db_name' => trim(lin2i_post('db_name')),
        'db_user' => trim(lin2i_post('db_user')),
        'db_pass' => lin2i_post('db_pass'),
        'db_create' => lin2i_post('db_create') === '1',
        'db_mode' => lin2i_post('db_mode') === 'existing' ? 'existing' : 'fresh',
        'admin_nick' => trim(lin2i_post('admin_nick')),
        'admin_pass' => lin2i_post('admin_pass'),
        'admin_pass2' => lin2i_post('admin_pass2'),
        'admin_email' => trim(lin2i_post('admin_email')),
        'overwrite' => lin2i_post('overwrite') === '1',
        'overwrite_name' => trim(lin2i_post('overwrite_name')),
    );
    if ($in['db_host'] === '') {
        $in['db_host'] = 'localhost';
    }
    return $in;
}

/**
 * Выполняет установку. Результат: ok, errors, log (журнал), conflict (если в базе уже есть таблицы игры),
 * config_text (если inc/config.php пришлось создавать вручную), notes (предупреждения), admin_nick.
 */
function lin2i_run($in)
{
    $res = array('ok' => false, 'errors' => array(), 'log' => array(), 'conflict' => null,
        'config_text' => null, 'notes' => array(), 'admin_nick' => null, 'dirs' => array());
    $log = array();

    $errors = lin2i_validate($in);
    if ($errors) {
        $res['errors'] = $errors;
        return $res;
    }

    $schema = lin2i_schema($serr);
    if ($schema === false) {
        $res['errors'][] = $serr;
        return $res;
    }

    @set_time_limit(0);
    @ignore_user_abort(true);

    // --- подключение
    $link = lin2i_connect($in['db_host'], $in['db_user'], $in['db_pass'], $cerr);
    if ($link === false) {
        $res['errors'][] = lin2i_connect_hint($cerr[0], $cerr[1], $in['db_host']);
        return $res;
    }
    $log[] = 'Подключились к MySQL ' . mysqli_get_server_info($link) . ' (' . $in['db_host'] . ').';

    // --- база
    if (!@mysqli_select_db($link, $in['db_name'])) {
        $errno = mysqli_errno($link);
        $emsg = mysqli_error($link);
        if ($errno == 1049 && $in['db_create']) {
            if (!lin2i_exec($link, 'CREATE DATABASE ' . lin2i_quote_ident($in['db_name']) . ' DEFAULT CHARACTER SET utf8', $xerr)) {
                $res['errors'][] = 'Не удалось создать базу «' . $in['db_name'] . '»: ' . $xerr
                    . '. Создайте базу в панели хостинга и снимите галочку «Создать базу».';
                return $res;
            }
            if (!@mysqli_select_db($link, $in['db_name'])) {
                $res['errors'][] = lin2i_connect_hint(mysqli_errno($link), mysqli_error($link), $in['db_host']);
                return $res;
            }
            $log[] = 'База «' . $in['db_name'] . '» создана.';
        } else {
            $res['errors'][] = lin2i_connect_hint($errno, $emsg, $in['db_host']);
            return $res;
        }
    } else {
        $log[] = 'База «' . $in['db_name'] . '» найдена.';
    }

    $existing = lin2i_list_tables($link);
    $tables = $schema['tables'];
    $present = array_intersect_key($tables, $existing);

    if ($in['db_mode'] === 'existing') {
        // --- подключение к готовой базе: таблицы не трогаем
        if (!isset($existing['account']) || !isset($existing['users'])) {
            $res['errors'][] = 'В базе «' . $in['db_name'] . '» нет таблиц account и users - это не база игры. '
                . 'Выберите «Новая установка» или другую базу.';
            return $res;
        }
        $log[] = 'Режим «подключить существующую базу»: найдено таблиц игры - ' . count($present) . ' из ' . count($tables) . ', данные не менялись.';
        $missing = array_diff_key($tables, $existing);
        if ($missing) {
            $res['notes'][] = 'В базе нет таблиц: ' . implode(', ', array_slice(array_values($missing), 0, 12))
                . (count($missing) > 12 ? ' и ещё ' . (count($missing) - 12) : '')
                . '. Разделы игры, которым они нужны, будут пустыми; недостающие можно создать из shema.sql.';
        }
    } else {
        // --- новая установка
        if ($present) {
            if (!($in['overwrite'] && $in['overwrite_name'] === $in['db_name'])) {
                $accounts = null;
                if (isset($existing['account'])) {
                    $r = @mysqli_query($link, 'SELECT COUNT(*) FROM `account`');
                    if ($r) {
                        $row = mysqli_fetch_row($r);
                        $accounts = (int) $row[0];
                        mysqli_free_result($r);
                    }
                }
                $res['conflict'] = array('tables' => count($present), 'accounts' => $accounts);
                $res['log'] = $log;
                return $res;
            }
            // стираем только те таблицы, которые создаёт сам установщик
            foreach ($present as $real) {
                if (!lin2i_exec($link, 'DROP TABLE IF EXISTS ' . lin2i_quote_ident($real), $xerr)) {
                    $res['errors'][] = 'Не удалось удалить таблицу ' . $real . ': ' . $xerr;
                    $res['log'] = $log;
                    return $res;
                }
            }
            $log[] = 'Удалены существующие таблицы игры: ' . count($present) . '.';
        }

        $filtered = lin2i_filter_player_data($schema['statements']);
        if ($filtered['skipped']) {
            $log[] = 'В дампе нет аккаунтов игроков, поэтому данные игроков (тестовые персонажи, история входов с IP-адресами, голоса) не импортируются: '
                . $filtered['skipped'] . ' запросов INSERT в таблицы ' . implode(', ', $filtered['tables']) . '.';
        }
        $done = 0;
        foreach ($filtered['statements'] as $idx => $s) {
            if (!lin2i_exec($link, $s[1], $xerr)) {
                $head = preg_replace('/\s+/', ' ', substr($s[1], 0, 160));
                $res['errors'][] = 'Ошибка в запросе №' . ($idx + 1) . ' из ' . $s[0] . ': ' . $xerr . ' - ' . $head;
                if (array_intersect_key($tables, lin2i_list_tables($link))) {
                    $res['errors'][] = 'Часть таблиц уже создана. Исправьте причину и запустите установку снова: установщик предложит стереть созданные таблицы и начать заново.';
                } else {
                    $res['errors'][] = 'Ни одна таблица не создана. Проверьте, что у пользователя есть права на эту базу (создание таблиц), и запустите установку снова.';
                }
                $res['log'] = $log;
                return $res;
            }
            $done++;
        }
        // дамп меняет sql_mode командой SET SQL_MODE: возвращаем режим, в котором работает игра
        @mysqli_query($link, "SET SESSION sql_mode=''");
        $log[] = 'Выполнено запросов: ' . $done . ' из ' . count($schema['statements']) . ' ('
            . implode(', ', array_map('lin2i_file_count', array_keys($schema['files']), $schema['files'])) . ').';

        $after = lin2i_list_tables($link);
        $lost = array_diff_key($tables, $after);
        if ($lost) {
            $res['errors'][] = 'После импорта не нашлись таблицы: ' . implode(', ', array_slice(array_values($lost), 0, 12)) . '.';
            $res['log'] = $log;
            return $res;
        }
        $log[] = 'Создано таблиц: ' . count($tables) . '.';

        // --- администратор
        if (!lin2i_create_admin($link, $in['admin_nick'], $in['admin_pass'], $in['admin_email'], $aerr)) {
            $res['errors'][] = 'Не удалось создать администратора: ' . $aerr;
            $res['log'] = $log;
            return $res;
        }
        $res['admin_nick'] = $in['admin_nick'];
        $log[] = 'Создан администратор «' . $in['admin_nick'] . '» (аккаунт и персонаж, права dostup = 5).';
    }

    // --- каталоги для загрузок
    $dirs = lin2i_prepare_dirs();
    $res['dirs'] = $dirs;
    foreach ($dirs as $d => $st) {
        if ($st[1]) {
            $log[] = 'Каталог ' . $d . '/: ' . ($st[0] ? 'создан' : 'есть') . ', запись разрешена.';
        } else {
            $res['notes'][] = 'Каталог ' . $d . '/ недоступен для записи: создайте его и выставьте права 777 (иначе не будут работать загрузки картинок).';
        }
    }

    // --- настройки
    $cfg = array('db_host' => $in['db_host'], 'db_user' => $in['db_user'], 'db_pass' => $in['db_pass'], 'db_name' => $in['db_name']);
    $text = lin2i_config_text($in['db_host'], $in['db_user'], $in['db_pass'], $in['db_name']);
    if (lin2i_write_config($text, $cfg, $werr)) {
        $log[] = 'Параметры базы записаны в inc/config.php.';
    } else {
        $res['config_text'] = $text;
        $res['notes'][] = 'Не удалось записать inc/config.php (' . $werr . '). Создайте этот файл вручную с текстом ниже, пока его нет, игра не заработает.';
    }

    $res['ok'] = true;
    $res['log'] = $log;
    return $res;
}

function lin2i_file_count($name, $count)
{
    return $name . ': ' . $count;
}

/* ------------------------------------------------------------------------------------------------------------
 * Страницы
 * ---------------------------------------------------------------------------------------------------------- */

function lin2i_page($title, $body, $code = 200)
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Robots-Tag: noindex, nofollow');
        header('X-Frame-Options: DENY');
    }
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex,nofollow">'
        . '<title>' . lin2i_h($title) . '</title><style>'
        . 'body{font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f3f4f6;color:#1f2937;margin:0;padding:1em}'
        . '.box{max-width:44em;margin:0 auto;background:#fff;border-radius:10px;padding:1.2em 1.4em;box-shadow:0 1px 4px rgba(0,0,0,.12)}'
        . 'h1{font-size:1.5em;margin:.1em 0 .2em}h2{font-size:1.1em;margin:1.4em 0 .4em}'
        . '.lead{color:#4b5563;margin:0 0 1em}'
        . 'table.chk{width:100%;border-collapse:collapse;font-size:.92em}table.chk td{padding:.25em .4em;border-bottom:1px solid #eee;vertical-align:top}'
        . '.ok{color:#15803d}.warn{color:#b45309}.fail{color:#b91c1c}.st{width:1.6em;font-weight:bold}'
        . 'fieldset{border:1px solid #d1d5db;border-radius:8px;margin:1em 0;padding:.6em 1em .9em}legend{font-weight:600;padding:0 .4em}'
        . 'label{display:block;margin:.7em 0 .15em;font-weight:500}input[type=text],input[type=password],input[type=email]{width:100%;box-sizing:border-box;padding:.5em;border:1px solid #9ca3af;border-radius:6px;font:inherit}'
        . '.hint{color:#6b7280;font-size:.85em;margin:.1em 0 0}.chk-row{display:block;margin:.7em 0 0;font-weight:400}.chk-row input{margin-right:.4em}'
        . 'button,.btn{display:inline-block;background:#2563eb;color:#fff;border:0;border-radius:6px;padding:.65em 1.4em;font:inherit;font-weight:600;cursor:pointer;text-decoration:none}'
        . 'button.danger{background:#b91c1c}button:disabled{background:#9ca3af;cursor:not-allowed}'
        . '.alert{border-radius:8px;padding:.6em .9em;margin:.8em 0}.alert.e{background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d}'
        . '.alert.w{background:#fffbeb;border:1px solid #fde68a;color:#78350f}.alert.g{background:#f0fdf4;border:1px solid #bbf7d0;color:#14532d}'
        . 'pre{background:#111827;color:#e5e7eb;padding:.8em;border-radius:8px;overflow:auto;font-size:.85em;white-space:pre-wrap}'
        . 'textarea{width:100%;box-sizing:border-box;font:13px/1.4 monospace;height:11em}code{background:#f3f4f6;padding:0 .3em;border-radius:3px}'
        . '</style></head><body><div class="box">' . $body . '</div></body></html>';
}

function lin2i_checks_html($checks)
{
    $icons = array('ok' => '&#10004;', 'warn' => '&#9888;', 'fail' => '&#10006;');
    $h = '<table class="chk">';
    foreach ($checks as $c) {
        $h .= '<tr class="' . $c['status'] . '"><td class="st">' . $icons[$c['status']] . '</td><td>' . lin2i_h($c['title'])
            . ($c['note'] !== '' ? '<div class="hint">' . lin2i_h($c['note']) . '</div>' : '') . '</td></tr>';
    }
    return $h . '</table>';
}

function lin2i_csrf()
{
    if (empty($_SESSION['lin2i_csrf'])) {
        try {
            $_SESSION['lin2i_csrf'] = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $_SESSION['lin2i_csrf'] = md5(uniqid((string) mt_rand(), true));
        }
    }
    return $_SESSION['lin2i_csrf'];
}

function lin2i_csrf_ok()
{
    return isset($_SESSION['lin2i_csrf']) && is_string($_SESSION['lin2i_csrf']) && $_SESSION['lin2i_csrf'] !== ''
        && hash_equals($_SESSION['lin2i_csrf'], lin2i_post('csrf'));
}

function lin2i_form_page($vals, $errors, $checks, $conflict)
{
    $blocked = lin2i_has_fail($checks);
    $b = '<h1>Установка игры</h1><p class="lead">Miniage (l2war.mobi). Установщик проверит сервер, создаст таблицы в базе MySQL, '
        . 'заведёт администратора и сохранит настройки. Это займёт меньше минуты.</p>';

    $b .= '<h2>Проверка сервера</h2>' . lin2i_checks_html($checks);

    if ($errors) {
        $b .= '<div class="alert e"><b>Не получилось:</b><ul style="margin:.3em 0 0 1.2em;padding:0">';
        foreach ($errors as $e) {
            $b .= '<li>' . lin2i_h($e) . '</li>';
        }
        $b .= '</ul></div>';
    }

    if ($blocked) {
        $b .= '<div class="alert e">Исправьте пункты, отмеченные красным крестиком, и обновите страницу.</div>';
        lin2i_page('Установка игры', $b);
        return;
    }

    if ($conflict) {
        $b .= '<div class="alert w"><b>В базе «' . lin2i_h($vals['db_name']) . '» уже есть таблицы игры (' . (int) $conflict['tables'] . ' шт.'
            . ($conflict['accounts'] !== null ? ', аккаунтов: ' . (int) $conflict['accounts'] : '') . ').</b><br>'
            . 'Поверх них новую игру ставить нельзя. Что можно сделать:<ul style="margin:.3em 0 .3em 1.2em;padding:0">'
            . '<li>указать другую, пустую базу;</li>'
            . '<li>выбрать «Подключить существующую базу» ниже - данные останутся нетронутыми;</li>'
            . '<li>или стереть таблицы игры и поставить заново (<b>все данные в них будут потеряны</b>): введите имя базы '
            . 'в поле подтверждения, поставьте галочку и нажмите «Установить».</li></ul>'
            . '<label class="chk-row"><input type="checkbox" name="overwrite" value="1" form="f"> Да, стереть таблицы игры в этой базе</label>'
            . '<label>Имя базы для подтверждения</label><input type="text" name="overwrite_name" form="f" autocomplete="off">'
            . '<p class="hint">Пароли в целях безопасности не сохраняются между попытками - введите их ещё раз.</p></div>';
    }

    $existing = ($vals['db_mode'] === 'existing');
    $b .= '<form id="f" method="post" autocomplete="off"><input type="hidden" name="action" value="install">'
        . '<input type="hidden" name="csrf" value="' . lin2i_h(lin2i_csrf()) . '">';

    $b .= '<fieldset><legend>1. База данных MySQL</legend>'
        . '<label>Сервер</label><input type="text" name="db_host" value="' . lin2i_h($vals['db_host']) . '">'
        . '<div class="hint">Чаще всего <code>localhost</code>. Если хостинг дал другой адрес, впишите его (можно с портом: <code>localhost:3307</code>).</div>'
        . '<label>Имя базы</label><input type="text" name="db_name" value="' . lin2i_h($vals['db_name']) . '">'
        . '<label>Пользователь</label><input type="text" name="db_user" value="' . lin2i_h($vals['db_user']) . '">'
        . '<label>Пароль</label><input type="password" name="db_pass" value="" autocomplete="new-password">'
        . '<label class="chk-row"><input type="checkbox" name="db_create" value="1"' . ($vals['db_create'] ? ' checked' : '') . '> Создать базу, если её нет</label>'
        . '<div class="hint">Нужны права на создание баз. На обычном хостинге базу создают в панели управления.</div>'
        . '<label class="chk-row"><input type="radio" name="db_mode" value="fresh"' . ($existing ? '' : ' checked') . '> <b>Новая установка</b> - создать таблицы игры в пустой базе</label>'
        . '<label class="chk-row"><input type="radio" name="db_mode" value="existing"' . ($existing ? ' checked' : '') . '> <b>Подключить существующую базу игры</b> - таблицы и данные не трогаем (перенос игры на другой сервер)</label>'
        . '</fieldset>';

    $b .= '<fieldset id="admin_box"><legend>2. Администратор</legend>'
        . '<div class="hint">Создаётся аккаунт и персонаж с доступом к админ-панели (<code>adm_panel.php</code>). Под этим ником и паролем вы войдёте в игру.</div>'
        . '<label>Ник</label><input type="text" name="admin_nick" maxlength="10" value="' . lin2i_h($vals['admin_nick']) . '">'
        . '<div class="hint">4-10 латинских букв и цифр, не одни цифры.</div>'
        . '<label>Пароль</label><input type="password" name="admin_pass" maxlength="15" value="" autocomplete="new-password">'
        . '<div class="hint">6-15 латинских букв и цифр.</div>'
        . '<label>Пароль ещё раз</label><input type="password" name="admin_pass2" maxlength="15" value="" autocomplete="new-password">'
        . '<label>E-mail (необязательно)</label><input type="text" name="admin_email" maxlength="35" value="' . lin2i_h($vals['admin_email']) . '">'
        . '</fieldset>';

    $b .= '<p><button type="submit">Установить</button></p>'
        . '<p class="hint">Установка занимает несколько секунд - не закрывайте страницу после нажатия.</p></form>'
        . '<script>(function(){var r=document.getElementsByName("db_mode");function f(){var x=false;for(var i=0;i<r.length;i++){if(r[i].checked&&r[i].value==="existing"){x=true}}'
        . 'document.getElementById("admin_box").style.display=x?"none":"block"}for(var i=0;i<r.length;i++){r[i].onclick=f}f()})();</script>';

    lin2i_page('Установка игры', $b);
}

function lin2i_done_page($res, $in)
{
    $base = lin2i_base_url();
    $b = '<h1>Игра установлена &#127881;</h1>';
    $b .= '<div class="alert g">Готово. ' . ($in['db_mode'] === 'existing'
        ? 'Параметры базы сохранены, данные не менялись.'
        : 'Таблицы созданы, администратор заведён.') . '</div>';

    if ($res['config_text'] !== null) {
        $b .= '<div class="alert e"><b>Осталось одно действие.</b> Создайте файл <code>inc/config.php</code> (через FTP или файловый менеджер) '
            . 'с таким содержимым, пока его нет, игра покажет «не установлена»:</div>'
            . '<textarea readonly onclick="this.select()">' . lin2i_h($res['config_text']) . '</textarea>';
    }
    foreach ($res['notes'] as $n) {
        $b .= '<div class="alert w">' . lin2i_h($n) . '</div>';
    }
    if ($res['admin_nick'] !== null) {
        $b .= '<h2>Вход</h2><p>Откройте <a href="' . lin2i_h($base) . 'index.php"><b>игру</b></a> и войдите: ник <b>'
            . lin2i_h($res['admin_nick']) . '</b> и пароль, который вы указали. Админ-панель: <a href="' . lin2i_h($base) . 'adm_panel.php">adm_panel.php</a>.</p>';
    } else {
        $b .= '<h2>Дальше</h2><p>Откройте <a href="' . lin2i_h($base) . 'index.php"><b>игру</b></a>. Чтобы сделать существующего игрока администратором, '
            . 'выполните в базе: <code>UPDATE users SET dostup = 5 WHERE usr = \'Ник\';</code></p>';
    }
    $b .= '<h2>Безопасность</h2><p><b>Удалите install.php</b> с сервера. Пока файл inc/config.php существует, установщик ничего не делает, '
        . 'но лишнему файлу на сайте делать нечего.</p>'
        . '<form method="post"><input type="hidden" name="action" value="delete_installer"><input type="hidden" name="csrf" value="' . lin2i_h(lin2i_csrf()) . '">'
        . '<button class="danger" type="submit">Удалить install.php</button></form>';
    $b .= '<h2>Журнал установки</h2><pre>' . lin2i_h(implode("\n", $res['log'])) . '</pre>';
    lin2i_page('Игра установлена', $b);
}

function lin2i_installed_page($why, $deleted)
{
    $b = '<h1>Игра уже установлена</h1>';
    if ($deleted !== null) {
        $b .= $deleted
            ? '<div class="alert g">Файл install.php удалён.</div>'
            : '<div class="alert e">Не удалось удалить install.php (нет прав): удалите файл вручную через FTP или файловый менеджер.</div>';
    }
    $b .= '<p>' . lin2i_h($why) . ' Повторный запуск установщика отключён, чтобы никто не стёр данные игры.</p>'
        . '<p><a class="btn" href="' . lin2i_h(lin2i_base_url()) . 'index.php">Открыть игру</a></p>';
    if ($deleted === null) {
        $b .= '<p>Установщик больше не нужен - <b>удалите install.php</b>.</p>'
            . '<form method="post"><input type="hidden" name="action" value="delete_installer"><input type="hidden" name="csrf" value="' . lin2i_h(lin2i_csrf()) . '">'
            . '<button class="danger" type="submit">Удалить install.php</button></form>'
            . '<p class="hint">Чтобы установить игру заново, удалите <code>inc/config.php</code> (и уберите переменные LIN2_DB_*, если они заданы).</p>';
    }
    lin2i_page('Игра уже установлена', $b, 403);
}

/* ------------------------------------------------------------------------------------------------------------
 * Точка входа
 * ---------------------------------------------------------------------------------------------------------- */

function lin2i_main()
{
    error_reporting(E_ALL);
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
    set_exception_handler('lin2i_exception');

    @session_start();

    $action = lin2i_post('action');
    $installed = lin2i_installed();

    if ($installed !== false) {
        $deleted = null;
        if ($action === 'delete_installer' && lin2i_csrf_ok()) {
            $deleted = @unlink(__FILE__);
        }
        lin2i_installed_page($installed, $deleted);
        return;
    }

    $checks = lin2i_checks();
    $vals = array('db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_create' => false, 'db_mode' => 'fresh',
        'admin_nick' => '', 'admin_email' => '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'install') {
        if (!lin2i_csrf_ok()) {
            lin2i_form_page($vals, array('Сессия устарела или cookie отключены. Обновите страницу и попробуйте ещё раз.'), $checks, null);
            return;
        }
        if (lin2i_has_fail($checks)) {
            lin2i_form_page($vals, array(), $checks, null);
            return;
        }
        $in = lin2i_collect_input();
        foreach ($vals as $k => $v) {
            $vals[$k] = $in[$k];
        }
        $res = lin2i_run($in);
        if ($res['ok']) {
            lin2i_done_page($res, $in);
            return;
        }
        lin2i_form_page($vals, $res['errors'], $checks, $res['conflict']);
        return;
    }

    lin2i_form_page($vals, array(), $checks, null);
}

function lin2i_exception($e)
{
    error_log('LIN2 install: ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    lin2i_page('Ошибка установки', '<h1>Ошибка установки</h1><div class="alert e">' . lin2i_h(get_class($e) . ': ' . $e->getMessage())
        . '</div><p>Подробности записаны в журнал ошибок PHP. Обновите страницу и попробуйте ещё раз.</p>', 500);
}

// LIN2I_LIB - для тестов: подключить функции, не запуская установщик
if (!defined('LIN2I_LIB')) {
    lin2i_main();
}
