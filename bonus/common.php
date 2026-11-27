<?php
/**
 * Общие функции используемые роботами. 
 * Не менять.
 *
 */
define('VGR_INT_MESSAGE_INFO', 'info');
define('VGR_INT_MESSAGE_WARN', 'warn');
define('VGR_INT_MESSAGE_ERROR', 'error');

// PHP 8: mysql_*() functions on top of mysqli
require_once dirname(__FILE__) . '/../inc/mysql_compat.php';

//Коды ошибок
define('VGR_CORE_XML_API_ERROR_GENERAL', -2);
define('VGR_CORE_XML_API_ERROR_SUCCESS', -1);
define('VGR_CORE_XML_API_ERROR_PARAM_NOT_PASSED', 2);
define('VGR_CORE_XML_API_ERROR_SIGN_CHECK', 3);
define('VGR_CORE_XML_API_ERROR_DOS_ATTACK', 4);


define('VGR_CORE_INT_BOT_TYPE_VOTES', 1);
define('VGR_CORE_INT_BOT_TYPE_BONUS', 2);

define('VGR_CORE_INT_VOTE_STATUS_BONUS_RECEIVED', 2);


//Соединение с БД голосований
static $vgrInt_SQLLink;

/**
 * Выводит форматированное сообщение с помощью echo
 * Используется для журналирования работы роботов
 *
 * @param string $tag Метка сообщения
 * @param int $level Уровень ошибки (info, warn, error)
 * @param string $message Собщение
 */
function vgrInt_printMessage($tag, $level, $message)
{
	$date = time();
	$date = date("d.m.y H:i:s", $date);
	echo $date;
	echo " ";
	echo $tag;
	echo " ";
	echo $level;
	echo ": ";
	echo $message;
	echo "\r\n";
}


/**
 * Закачивает содержимое файла с удаленного сервера по URL.
 *
 * @param string $url URL удаленного сервера.
 * @param int $maxlength Максимальная длина данных в закачке. Если null -- закачиваются все данные.
 * @param int $timeout Таймаут на закачку. Если 0 -- бесконечный таймаут.
 * @param bool $disableWarnings Показывать или нет предупреждения.
 * 
 * @return string Данные закачки.
 */
function vgrInt_getFileContentByURL($url, $maxlength = null, $timeout = 0, $disableWarnings = false)
{
	$url = trim($url);
	if($timeout > 0)
	{
		ini_set('default_socket_timeout', $timeout);
	}
	if($disableWarnings)
	{
		error_reporting(E_ERROR);
	}
	$context = null;
	$ret = null;
	if($maxlength == null)
	{
		$ret = file_get_contents($url, False, $context);
	}
	else
	{
		$ret = file_get_contents($url, False, $context, 0, $maxlength);
	}
	if($ret !== FALSE)
	{
		if($ret != null)
		{
			$ret = trim($ret);
		}
	}
	return $ret;
}


/**
 * Открывает соединение с БД для хранения голосов
 *
 */
function vgrInt_openVotesSQLConnection()
{
	$dbHost = VGR_INT_VOTES_SQL_SERVER_HOST;
	$dbUser = VGR_INT_VOTES_SQL_SERVER_USER;
	$dbPass = VGR_INT_VOTES_SQL_SERVER_PASS;
	$dbName = VGR_INT_VOTES_SQL_SERVER_DB;
	global $vgrInt_SQLLink;
	$vgrInt_SQLLink = mysql_connect($dbHost, $dbUser, $dbPass, true);
	if($vgrInt_SQLLink)
	{
		mysql_select_db($dbName, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
	}
	else
	{
		$errorMessage = "vgrInt_openVotesSQLConnection failed. host: $dbHost, user: $dbUser";
		throw new Exception($errorMessage);
	}
}


/**
 * Закрывает соединение с БД игры
 *
 */
function vgrInt_closeVotesSQLConnection()
{
	global $vgrInt_SQLLink;
	if($vgrInt_SQLLink != null)
	{
		mysql_close($vgrInt_SQLLink);
	}
}


/**
 * Открывает соединение с БД игры
 *
 */
function vgrInt_openGameSQLConnection()
{
	$dbHost = VGR_INT_GAME_SQL_SERVER_HOST;
	$dbUser = VGR_INT_GAME_SQL_SERVER_USER;
	$dbPass = VGR_INT_GAME_SQL_SERVER_PASS;
	$dbName = VGR_INT_GAME_SQL_SERVER_DB;
	if(mysql_connect($dbHost, $dbUser, $dbPass))
	{
		mysql_select_db($dbName) or vgrInt_throwMySQLError();
	}
	else
	{
		$errorMessage = "vgrInt_openGameSQLConnection failed. host: $dbHost, user: $dbUser";
		throw new Exception($errorMessage);
	}
}

/**
 * Закрывает соединение с БД игры
 *
 */
function vgrInt_closeGameSQLConnection()
{
	mysql_close();
}


/**
 * Создает SQL таблицы (если они еще не были созданы) БД голосов
 *
 */
function vgrInt_createSQLTables()
{
	global $vgrInt_SQLLink;
	$query =
	"
CREATE TABLE IF NOT EXISTS vigre_votes (
  id int(11) NOT NULL default '0',
  `date` datetime default NULL,
  charname varchar(255) default NULL,
  ip varchar(255) default NULL,
  `status` int(11) default NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

	mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
	
	
	$query = "ALTER TABLE `vigre_votes` ADD `sms` BOOL NULL DEFAULT NULL;";
	mysql_query($query, $vgrInt_SQLLink);

	$query = "
CREATE TABLE IF NOT EXISTS vigre_votes_bot (
  running tinyint(1) default NULL,
  last_run datetime default NULL,
  `type` int(11) NOT NULL default '0',
  PRIMARY KEY  (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

	mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();

	$query = "CREATE TABLE IF NOT EXISTS vigre_bonus (
  charname varchar(255) NOT NULL default '',
  bonus_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (charname)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

	mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();

}

/**
 * Генерит исключение с mysql ошибкой.
 * Использовать ТОЛЬКО при работе с БД голосований
 *
 */
function vgrInt_throwMySQLError()
{
	global $vgrInt_SQLLink;
	throw new Exception(mysql_error($vgrInt_SQLLink));
}

?>