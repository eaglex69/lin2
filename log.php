<?php
session_start();
// PHP 8: mysql_*() functions on top of mysqli
include_once dirname(__FILE__) . '/inc/mysql_compat.php';
// Подключаемся к БД
include_once dirname(__FILE__) . '/inc/dbconfig.php';
$lin2_db = lin2_require_config();
$dbhost = $lin2_db['host'];
$dbname = $lin2_db['name'];
$dbuser = $lin2_db['user'];
$dbpass = $lin2_db['pass'];
$lin=mysql_connect($dbhost,$dbuser,$dbpass) or die("Не могу подключиться к серверу БД");
mysql_select_db($dbname,$lin) or die("Не могу подключиться к БД");
mysql_query("SET NAMES utf8");
mysql_query("set character_set_client='utf8'");
mysql_query("set character_set_results='utf8'");
mysql_query("set collation_connection='utf8'");
// Делаем проверку на существование акаунта
$password = md5($password);
$g=mysql_query("SELECT * FROM `account` WHERE `account`='$account' AND `password`='$password' LIMIT 1");
if(mysql_num_rows($g)==1){

if ($saver=="ok"){
SetCookie("account","$account",time()+3600*24*1000);
SetCookie("password","$password",time()+3600*24*1000);
SetCookie("login","");
}else{
SetCookie("account","$account");
SetCookie("password","$password");
SetCookie("login","");
}

header("Location: zals?");
}else{

header("Location: index?error");
}