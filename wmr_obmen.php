<?php
define('PROTECTOR', 1);

$textl='GM-Panel WebMoney';
///////////////////////
	$path='../';			//
//////////////////////
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
///////////////



switch($_GET['mod']){
default:
//-
$data = date("d F Yг. в H:i", strtotime("+20 seconds"));
//-
$data = str_replace("January","Января",$data);
$data = str_replace("February","Февраля",$data);
$data = str_replace("March","Марта",$data);
$data = str_replace("April","Апреля",$data);
$data = str_replace("May","Мая",$data);
$data = str_replace("June","Июня",$data);
$data = str_replace("July","Июля",$data);
$data = str_replace("August","Августа",$data);
$data = str_replace("September","Сентября",$data);
$data = str_replace("October","Октября",$data);
$data = str_replace("November","Ноября",$data);
$data = str_replace("December","Декабря",$data);


// --- --- 		Обменять WMR 		 --- --- //
if (isset($_GET['yes'])){

$usr = mysql_fetch_array(mysql_query("SELECT * FROM `users` WHERE `usr`='$log' Limit 1"));


$_POST['col'] = floor((float)$_POST['col']);

if ((float)$_POST['col']<=0 or is_numeric($_POST['col'])==FALSE){
echo "<p><font color=red><b>Ошибка! Вводите цыфры больше ноля !!! </b></font></p>";
echo "<div class=silka><a href=\"wmr_obmen.php\">Назад</a></div>";
include($path.'inc/down.php');EXIT;
}



$wmr2 = $_POST['col']/10*1*100; // сколько забераем ВМР



$wmr = $usr['wmr'] - $wmr2; // выщитуем

if ($wmr<0){echo "<p><font color=red><b>Ошибка! Мало WMR !!! </b></font></p>";
echo "<div class=silka><a href=\"wmr_obmen.php\">Назад</a></div>";
include($path.'inc/down.php');EXIT;
}

$almaznew = $usr['almaz'] + $_POST['col']; // плюсуем колы

mysql_query ("UPDATE users SET almaz='$almaznew', wmr='$wmr'  WHERE usr='$log' LIMIT 1"); // пишем данные игрока с новой суммой
/* 
		//----------------------------------------------------------------------------
			//	---	//	---	//	---	//	//	 	ЗАДАНИЯ		//	//	---	//	---	//	---	//
				if ($udata[lvl]>11){
				$reqev = mysql_query("SELECT * FROM job_col WHERE `usr` = '$log' Limit 1");
				////////////////////////////
				$jm = mysql_fetch_array($reqev);
				if(mysql_num_rows($jm)>=1){ // если уже участвует  то дописываем
				if ($jm[kill]!=='off' and $jm[kill]<5000){ // если уже получил вознагр то табла не созд
				$kill=$jm[kill]+$_POST[col]; if ($kill>5000){$kill=5000;}
				mysql_query("UPDATE job_col SET `kill` = '$kill' WHERE `usr` = '$log' Limit 1");}} // дописываем +1 к количеству
				else{ // если нет игрока в рейтинге то создаем ему
				mysql_query("INSERT INTO job_col SET `usr` = '$log', `kill`='$_POST[col]', `data`='no'"); // создаем таблицу юзеру
				}}
		//-----------------------------------------------------------------------------
 */

echo '<p><font color=#99CC66><b> Готово </b></font></p><hr/>';


}



/////////////////////////
$usr = mysql_fetch_array(mysql_query("SELECT * FROM `users` WHERE `usr`='$log' Limit 1"));
////////////////////////




$usr['wmr'] = $usr['wmr'] / 100;
echo "<p><font color=#CC6633><b>У Вас ".number_format($usr['wmr'], 2, ',', "`")." WMR ! <br/> </b>";
////<a href=\"/wm/\"> » WMR за WebMoney</a></font></p>";

echo "<p><font color=green><b>Курс обмена: <br/> * 1000 Coin of Luck = 100 WMR</b></font></p>";


$col = $usr['wmr']*10;
$col = floor($col);

echo "<form action=\"wmr_obmen.php?yes\" method=\"POST\">";

echo "<div class=menu><b><font color=grey>Купить Coin of Luck:<br/>
+ </b></font><input type=\"text\" name=\"col\" size=\"3\" value=\"$col\" maxlength=\"9\"/> ";

echo "<input type='submit' name='ok' value='CoL' /><hr/></div>\n";


echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";














break;

}

echo "<br/><div class=inoy><a href=\"/pers.php\">Назад</a></div></div>";

include($path.'inc/down.php');
?>