<?php
/**
 * Конфигурационный файл с настройками необходимыми для работы робота. 
 * Необходимо редактировать перед началом работы роботов.
 */

/**
 * ID игры
 *
 */
define('VGR_INT_GAME_ID', 1192);

/**
 * Секретный ключ
 *
 */
define('VGR_INT_SEC_KEY', 'b31a7a15d8ccdcdb78f478e109dee0cfb29cc1f9');

/**
 * Настройки mysql сервера для хранения голосов
 * (параметры базы берутся из inc/config.php или переменных окружения LIN2_DB_*, см. inc/dbconfig.php)
 */
require_once dirname(__FILE__) . '/../inc/dbconfig.php';
$lin2_db = lin2_require_config();   // нет настроек базы - сообщение «игра не установлена» и выход
if (!defined('VGR_INT_VOTES_SQL_SERVER_HOST')) define('VGR_INT_VOTES_SQL_SERVER_HOST', $lin2_db['host']);
if (!defined('VGR_INT_VOTES_SQL_SERVER_DB')) define('VGR_INT_VOTES_SQL_SERVER_DB', $lin2_db['name']);
if (!defined('VGR_INT_VOTES_SQL_SERVER_USER')) define('VGR_INT_VOTES_SQL_SERVER_USER', $lin2_db['user']);
if (!defined('VGR_INT_VOTES_SQL_SERVER_PASS')) define('VGR_INT_VOTES_SQL_SERVER_PASS', $lin2_db['pass']);



/**
 * Настройки mysql сервера игры
 *
 */
if (!defined('VGR_INT_GAME_SQL_SERVER_HOST')) define('VGR_INT_GAME_SQL_SERVER_HOST', $lin2_db['host']);
if (!defined('VGR_INT_GAME_SQL_SERVER_DB')) define('VGR_INT_GAME_SQL_SERVER_DB', $lin2_db['name']);
if (!defined('VGR_INT_GAME_SQL_SERVER_USER')) define('VGR_INT_GAME_SQL_SERVER_USER', $lin2_db['user']);
if (!defined('VGR_INT_GAME_SQL_SERVER_PASS')) define('VGR_INT_GAME_SQL_SERVER_PASS', $lin2_db['pass']);

?>
