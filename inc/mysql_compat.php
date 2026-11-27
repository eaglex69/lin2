<?php
/**
 * mysql_* -> mysqli: слой совместимости для PHP 7/8.
 *
 * Расширение ext/mysql (функции mysql_connect(), mysql_query() и т.д.) удалено из PHP 7.0.
 * Вся игра написана на этих функциях (около 4 600 вызовов), поэтому вместо переписывания
 * каждого вызова они реализованы здесь поверх mysqli с сохранением старого поведения:
 *
 *  - «ссылка по умолчанию» = последнее открытое соединение; $link_identifier необязателен;
 *  - mysql_connect() без $new_link возвращает уже открытое соединение с теми же параметрами;
 *  - ошибки SQL НЕ бросают исключений: функции возвращают false, текст ошибки - в mysql_error()
 *    (PHP 8.1+ в mysqli по умолчанию бросает mysqli_sql_exception - здесь это отключено);
 *  - mysql_fetch_*() возвращают false в конце выборки (а не null), mysql_result() работает как раньше;
 *  - вызовы с «неправильным» аргументом (например, mysql_num_rows(false) после неудачного запроса)
 *    дают предупреждение E_USER_WARNING и возвращают null/false, а не фатальный TypeError.
 *
 * Подключается из inc/php8.php (а значит и inc/db.php) и bonus/common.php. Если mysql_connect() уже
 * существует (старое расширение или другой polyfill), файл ничего не делает.
 *
 * Файл разделён на загрузчик (этот) и реализацию (mysql_compat_impl.php) намеренно: функции верхнего уровня
 * PHP объявляет ещё при компиляции файла, поэтому проверка function_exists('mysql_connect') внутри файла
 * с самими функциями всегда была бы истинной и пропускала бы весь остальной код.
 */

if (function_exists('mysql_connect')) {
    return;
}

if (!extension_loaded('mysqli')) {
    // (trigger_error(E_USER_ERROR) is deprecated since PHP 8.4)
    error_log('inc/mysql_compat.php: для работы нужно расширение PHP mysqli (см. PHP8_MIGRATION.md)');
    die('Не загружено расширение PHP mysqli. См. PHP8_MIGRATION.md');
}

// Как в старом ext/mysql: ошибки возвращаются значением, а не исключением.
mysqli_report(MYSQLI_REPORT_OFF);

if (!defined('MYSQL_ASSOC')) define('MYSQL_ASSOC', 1);
if (!defined('MYSQL_NUM')) define('MYSQL_NUM', 2);
if (!defined('MYSQL_BOTH')) define('MYSQL_BOTH', 3);
if (!defined('MYSQL_CLIENT_COMPRESS')) define('MYSQL_CLIENT_COMPRESS', 32);
if (!defined('MYSQL_CLIENT_SSL')) define('MYSQL_CLIENT_SSL', 2048);
if (!defined('MYSQL_CLIENT_INTERACTIVE')) define('MYSQL_CLIENT_INTERACTIVE', 1024);
if (!defined('MYSQL_CLIENT_IGNORE_SPACE')) define('MYSQL_CLIENT_IGNORE_SPACE', 256);

require_once dirname(__FILE__) . '/mysql_compat_impl.php';
