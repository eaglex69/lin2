<?php
/**
 * Загрузчик слоя совместимости с PHP 8.
 *
 * Игра написана под PHP 5.x и старый php.ini (см. php.ini.legacy). Этот файл подключается первой
 * строкой inc/db.php и воспроизводит те настройки старого окружения, которые теперь нельзя
 * положить в php.ini «по умолчанию»:
 *
 *  1. error_reporting. Старый php.ini работал с error_reporting = 1 (только фатальные ошибки),
 *     поэтому «Undefined variable/index» и подобные уведомления никогда не попадали в страницы.
 *     В PHP 8 они стали предупреждениями (E_WARNING), а не-фатальный вывод в начале страницы ломает
 *     header()/cookie и вёрстку. Пока не включена отладка, уровень ограничивается фатальными ошибками.
 *  2. Часовой пояс. В php.ini стоял date.timezone = Europe/Moscow; без него PHP 8 берёт UTC.
 *  3. Функции mysql_*() (удалены в PHP 7) - см. inc/mysql_compat.php.
 *  4. Необработанные исключения PHP 8 (TypeError, ValueError, DivisionByZeroError...). В PHP 5 такие
 *     ситуации были предупреждениями; теперь страница оборвалась бы стеком вызовов с путями к файлам.
 *     Вместо этого ошибка пишется в error_log, а игроку выводится короткое сообщение.
 *
 * Отладка: переменная окружения LIN2_DEBUG=1 (или define('LIN2_DEBUG', 1) до подключения db.php)
 * включает E_ALL и показывает исключения как есть.
 */
if (defined('LIN2_PHP8')) {
    return;
}
define('LIN2_PHP8', 1);

function lin2_php8_boot()
{
    if (defined('LIN2_DEBUG')) {
        $debug = (bool) LIN2_DEBUG;
    } else {
        $env = getenv('LIN2_DEBUG');
        $debug = ($env !== false && $env !== '' && $env !== '0');
    }

    if ($debug) {
        error_reporting(E_ALL);
    } else {
        error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
    }

    if (!ini_get('date.timezone')) {
        date_default_timezone_set('Europe/Moscow');
    }

    require_once dirname(__FILE__) . '/mysql_compat.php';

    if (!$debug) {
        set_exception_handler('lin2_php8_exception_handler');
    }
}

/**
 * Деление «как в PHP 5»: деление на ноль не бросает DivisionByZeroError (PHP 8), а даёт 0
 * (PHP 5 выдавал предупреждение и false). Используется там, где делитель берётся из БД и может быть 0.
 */
function lin2_div($a, $b)
{
    // PHP 8: арифметика над '' и нечисловыми строками бросает TypeError (PHP 5 считал их нулём) - приводим явно
    $a = is_numeric($a) ? $a + 0 : (float) $a;
    $b = is_numeric($b) ? $b + 0 : (float) $b;
    return ($b == 0) ? 0 : $a / $b;
}

/**
 * readdir() для результата opendir(): PHP 8 бросает TypeError, если каталог не открылся (opendir() вернул false),
 * в PHP 5 это было предупреждение и цикл просто не выполнялся (например, нет каталога pic/smiles).
 */
function lin2_readdir($handle)
{
    return is_resource($handle) ? readdir($handle) : false;
}

function lin2_closedir($handle)
{
    if (is_resource($handle)) {
        closedir($handle);
    }
}

function lin2_php8_exception_handler($e)
{
    error_log('PHP Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo '<div class="menu">Произошла ошибка. Попробуйте обновить страницу чуть позже.</div>';
}

lin2_php8_boot();
