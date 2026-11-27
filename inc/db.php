<?php
defined('PROTECTOR') or die('Error: restricted access');

// PHP 8: error level, timezone, mysql_*() on top of mysqli, exception handler (see inc/php8.php)
include_once dirname(__FILE__).'/php8.php';


$starttime = microtime(1);
# Analyzer of variables
// --------------- Функция правильного вывода веса файла -------------------//
function formatsize($file_size) {
	if ($file_size >= 1048576000) {
		$file_size = round(($file_size / 1073741824), 2) . " Gb";
	} elseif ($file_size >= 1024000) {
		$file_size = round(($file_size / 1048576), 2) . " Mb";
	} elseif ($file_size >= 1000) {
		$file_size = round(($file_size / 1024), 2) . " Kb";
	} else {
		$file_size = round($file_size) . " byte";
	} 
	return $file_size;
} 
function variables() {
	if ($_GET) {
		$cont = array();
		foreach ($_GET as $var_name => $var_value) $cont[] = "<b>".$var_name."</b>=".$var_value;
		$content = "<br><br><font color=\"blue\"><b>GET</b></font> - ".implode(", ", $cont);
	}
	if ($_POST) {
		$cont = array();
		foreach ($_POST as $var_name => $var_value) {
			$var_value = is_array($var_value) ? fields_save($var_value) : $var_value;
			$var_value = str_replace(array("[", "]"), array("[", "]"), htmlspecialchars($var_value));
			$cont[] = "<b>".$var_name."</b>=".$var_value."";
		}
		$content .= "<br><br><font color=\"blue\"><b>POST</b></font> - ".implode(", ", $cont);
	}
	if ($_COOKIE) {
		$cont = array();
		foreach ($_COOKIE as $var_name => $var_value) $cont[] = "<b>".$var_name."</b>=".$var_value;
		$content .= "<br><br><font color=\"blue\"><b>COOKIE</b></font> - ".implode(", ", $cont);
	}
	if ($_FILES) {
		$cont = array();
		foreach ($_FILES as $var_name => $var_value) $cont[] = "<b>".$var_name."</b>=".$var_value;
		$content .= "<br><br><font color=\"blue\"><b>FILES</b></font> - ".implode(", ", $cont);
	}
	if ($_SESSION) {
		$cont = array();
		foreach ($_SESSION as $var_name => $var_value) $cont[] = "<b>".$var_name."</b>=".$var_value;
		$content .= "<br><br><font color=\"blue\"><b>SESSION</b></font> - ".implode(", ", $cont);
	}
	return $content;
}

function fields_save($field) {
	if ($field) {
		$fields = stripslashes(htmlspecialchars(trim(implode("|", $field)), ENT_QUOTES));
		return $fields;
	}
}

// PHP 8: enter.php / avt.php start the session before including this file (notice on a repeated start)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// entries that do not include inc/path.php (avt.php, blog.php, enter.php, news.php ...) have no $path
if (!isset($path)) $path = '';

// Параметры подключения к базе: inc/config.php (его создаёт install.php) и/или переменные окружения LIN2_DB_*
// (см. inc/dbconfig.php). Пока ничего не задано, вместо игры выводится страница «игра не установлена».
include_once dirname(__FILE__).'/dbconfig.php';
$lin2_db = lin2_require_config();
$db_host = $lin2_db['host'];
$db_user = $lin2_db['user'];
$db_table = $lin2_db['name'];
$db_pass = $lin2_db['pass'];
$connect = @ mysql_connect($db_host, $db_user, $db_pass) or die('Проводятся технические работы. Зайдите через день с ув.Саша');
@ mysql_select_db($db_table) or die('Нет соединения с БД.');
@ mysql_query("SET NAMES 'utf8'", $connect);
// the schema and the INSERTs were written for MySQL 5.5 (empty sql_mode); MySQL 5.7/8 are strict by default
@ mysql_query("SET SESSION sql_mode=''", $connect);
include ($path.'inc/mysql.php');
$mysql = new mysql;

	 include($path.'inc/AntiHack.class.php');
     $lq = new AntiHack;

       if (isset($_GET))$_GET = $lq->filter($_GET, 'get');
       if (isset($_POST))$_POST = $lq->filter($_POST, 'post');
       if (isset($_FILES))$_FILES = $lq->filter($_FILES, 'files');
       if (isset($_COOKIE))$_COOKIE = $lq->filter($_COOKIE, 'cookie');
       if (isset($_SERVER))$_SERVER = $lq->filter($_SERVER, 'server');
       if (isset($_REQUEST))$_REQUEST = $lq->filter($_REQUEST, 'request');
     unset($lq);
	 
     if (isset($_GET['_1118538408(0)'])){
	 echo'Этот способ взлома больше не работает. (с) Unknown';
}
	 



?>