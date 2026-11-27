<?php
defined('PROTECTOR') or die('Error: restricted access');
////////////////	AntiDdoss	//////////////////
include $_SERVER['DOCUMENT_ROOT'].'/inc/antiddos.php';
//////////////////////////////////////////////////
include $_SERVER['DOCUMENT_ROOT'].'/inc/gzips.php';
session_start();
include_once dirname(__FILE__).'/dbconfig.php';
$lin2_db = lin2_require_config();
$db_host = $lin2_db['host'];
$db_user = $lin2_db['user'];
$db_table = $lin2_db['name'];
$db_pass = $lin2_db['pass'];
$connect = @ mysql_pconnect($db_host, $db_user, $db_pass) or die('Нет подключения к серверу.');
@ mysql_select_db($db_table) or die('Нет соединения с БД.');
@ mysql_query("SET NAMES 'utf8'", $connect);
##################### Класс борьбы с SQL атаками ################
include($path.'inc/sql.php');
$stop_injection = new InitVars();
$stop_injection->checkVars();
##################### Класс борьбы с SQL атаками ################
?>