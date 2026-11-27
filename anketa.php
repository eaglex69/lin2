<?
define('PROTECTOR', 1);

$headmod = 'anketa';//фикс. места

$textl='Анкета';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
if(empty($_POST[nick])){
$nick=$_GET[nick];
}else{
$nick=$_POST[nick];}
$nick = htmlspecialchars(stripslashes($nick));

switch($_GET[go]) {

default:

echo "<form action=\"anketa.php?go=go\" method=\"post\">Введите ник:<br/>";
echo "<input name=\"nick\" maxlength=\"10\" title=\"nick\" emptyok=\"true\"/><br/>";
echo "<input type=\"submit\" value=\"Найти\" /></form>";

break;

case 'go':

if($nick=="admin"){echo '<center><br>Ник Админа <b>Лунь</b>!<br></center>';include($path.'inc/down.php');exit;}

$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$nick'");
////////////////////////////
$result = mysql_query("SELECT * FROM `anketa` WHERE `nik` = '$nick'");
$myrow = mysql_fetch_array($result);

$result2 = mysql_query("SELECT * FROM `anketa` WHERE `nik` = '$nick'");
$result22=mysql_num_rows($result2);
	if($result22=="0"){
echo'<b>У этого игрока не заполнена анкета!</b>';
echo"<div class=silka><b><a href=\"?\">Назад</a></b>";
include($path.'inc/down.php');exit;
	}
///////////////////////////

$avto=mysql_num_rows($req);
if($avto=="0"){
echo'Нет такого игрока!';
include($path.'inc/down.php');exit;
}

$usdata = mysql_fetch_array($req);

	if($myrow[nik]==$log)
	{
	echo' <b><center>Моя Анкета</b></center>';
	}
	
echo'<br><div class="dot">Ник: <b>'.$myrow[nik].'</b> | ';

echo'Имя: <b>'.$myrow[name].'</b><hr>';
echo"Пол: ";
if($usdata[pol]=="m"){echo"<b>Парень</b> | ";}else{echo"<b>Девушка</b> | ";}


$datess = str_replace("01","января",$myrow[mm]);
$datess = str_replace("02","февраля",$datess);
$datess = str_replace("03","марта",$datess);
$datess = str_replace("04","апреля",$datess);
$datess = str_replace("05","мая",$datess);
$datess = str_replace("06","июня",$datess);
$datess = str_replace("07","июля",$datess);
$datess = str_replace("08","августа",$datess);
$datess = str_replace("09","сентября",$datess);
$datess = str_replace("10","октября",$datess);
$datess = str_replace("11","ноября",$datess);
$datess = str_replace("12","декабря",$datess);


if ($myrow[gg]=="0"){$myrow[gg] = ".....";}else {$myrow[gg] = "$myrow[gg]г.";}
if ($myrow[mm]=="0"){$myrow[mm] = "..";}
if ($myrow[dd]=="0"){$myrow[dd] = "..";}
echo" Дата рождения: <b>$myrow[dd] $datess $myrow[gg]</b><hr/>";
echo" Город: <b>$myrow[gorod]</b> | ";
echo" ICQ: <b>$myrow[isq]</b><hr>";
echo" Телефон: <b>$myrow[tel]</b> | ";
echo" Оператор: <b>$myrow[operator]</b><hr>";
echo" О себе: <b>$myrow[osebe]</b><br/></div>";

	if($nick==$log)
	{
	echo"<div class=silka><b><a href=\"anketa.php?go=1\">Изменить</a>";
	}


echo"<a href=\"search.php?go=go&amp;nick=$nick\">Назад</a></div>";

break;

case '1';
////////////////////////////
$result = mysql_query("SELECT * FROM `anketa` WHERE `nik` = '$log'");
$myrow = mysql_fetch_array($result);
///////////////////////////

echo "<b>Изменить анкету:<br/></b><br>";

echo "<form action=\"?go=addank\" method=\"POST\">";
echo "
Имя (max 25): <br/>
<input type=\"text\" name=\"name\" value=\"$myrow[name]\" maxlength=\"25\"/> <br/>


Дата рожд.: <br/>
";


// отображаем дни
echo"<select name=\"dd\" value=\"$myrow[dd]\">";
if(!empty($myrow[dd]))
{echo "<option value=\"$myrow[dd]\"> $myrow[dd] </option>";}
$dd=1;
if ($dd<=31){
while ($dd<=31){
echo "<option value=\"$dd\"> $dd </option>";
$dd++;}} echo "</select> "; 


// отображаем месяцы
echo"<select name=\"mm\" value=\"$myrow[mm]\">";
if(!empty($myrow[mm]))
{echo "<option value=\"$myrow[mm]\"> $myrow[mm] </option>";}
$mm=1;
if ($mm<=12){
while ($mm<=12){
echo "<option value=\"$mm\"> $mm </option>";
$mm++;}} echo "</select> "; 

// отображаем год
echo"<select name=\"gg\" value=\"$myrow[gg]\">";
if(!empty($myrow[gg]))
{echo "<option value=\"$myrow[gg]\"> $myrow[gg] </option>";}
$gg=1940;
if ($gg<=2012){
while ($gg<=2012){
echo "<option value=\"$gg\"> $gg </option>";
$gg++;}} echo "</select>"; 





echo "<br/>
Город:  (max 50) <br/>
<input type=\"text\" name=\"gorod\" value=\"$myrow[gorod]\" maxlength=\"50\"/> <br/>
ICQ:  (max 9) <br/>
<input type=\"text\" name=\"isq\" value=\"$myrow[isq]\" maxlength=\"9\"/> <br/>
Модель телефона: <br/> (max 15) <br/>
<input type=\"text\" name=\"tel\" value=\"$myrow[tel]\" maxlength=\"15\"/> <br/>
Оператор: (max 15) <br/>
<input type=\"text\" name=\"operator\" value=\"$myrow[operator]\" maxlength=\"15\"/> <br/>
О себе: <br/> 
<input type=\"text\" name=\"osebe\" value=\"$myrow[osebe]\" maxlength=\"1000\"/> <br/>
<input type=\"submit\" value=\"Обновить\" class=\"ibutton\"></div>";

break;

case 'addank':


	if(time() >= $_SESSION['antispam']){
		if(!empty($_POST['name']) 
		&& !empty($_POST['gg']) 
		&& !empty($_POST['gorod']) 
		&& !empty($_POST['isq']) 
		&& !empty($_POST['tel']) 
		&& !empty($_POST['operator'])){	
				if(strlen($_POST['text']) <= 1699){
$name = htmlspecialchars(addslashes($_POST['name']));
$text = htmlspecialchars(addslashes($_POST['text']));

$dd = $_POST[dd];
$mm = $_POST[mm];
//$mm = $_POST[mm];


mysql_query("UPDATE `anketa` SET
        `name` = '$_POST[name]',
        `dd` = '$_POST[dd]',
        `mm` = '$_POST[mm]',
        `gg` = '$_POST[gg]',
        `gorod` = '$_POST[gorod]',
        `isq` = '$_POST[isq]',
        `tel` = '$_POST[tel]',
        `operator` = '$_POST[operator]',
        `osebe` = '$_POST[osebe]' WHERE `nik`='$log' LIMIT 1");




						$_SESSION['antispam'] = time() + 5;
					echo'Анкета успешно изменена!';
				}else{
					echo'Слишком длинное сообщение! Не больше 1000 символов!';
				}
		}else{
			echo'Вы не заполнили поля!';
		}
	}else{
		echo'Антиспам! Разрешено писать раз в 5 сек.!';
	}
		echo"<br/><br/><a href=\"anketa.php?go=1\">Назад</a>";

	break;
//--------------Новая анкета--------------------
case '4';

	//-----Создание анкеты---------
		$ank = mysql_query("SELECT * FROM `anketa` WHERE `nik` = '$log'");
		$ank=mysql_num_rows($ank);

		
		if($ank > 0)
		{
		echo "Анкета уже создана<br/>";
		echo"<br/>» <b><a href=\"anketa.php?go=1\">Изменить</a></b><br/>";
		include"inc/down.php";exit;
		}

	//------------------------------


////////////////////////////
$result = mysql_query("SELECT * FROM `anketa` WHERE `nik` = '$log'");
$myrow = mysql_fetch_array($result);
///////////////////////////

echo "Создать анкету<br/><br/>";

echo "<form action=\"?go=add\" method=\"POST\">";
echo "
Имя (max 25): <br/>
<input type=\"text\" name=\"name\" value=\"$myrow[name]\" maxlength=\"25\"/> <br/>


Дата рожд.: <br/>";

// отображаем дни
echo"<select name=\"dd\" value=\"$myrow[dd]\">";
$dd=1;
if ($dd<=31){
while ($dd<=31){
echo "<option value=\"$dd\"> $dd </option>";
$dd++;}} echo "</select> "; 


// отображаем месяцы
echo"<select name=\"mm\" value=\"$myrow[mm]\">";
$mm=1;
if ($mm<=12){
while ($mm<=12){
echo "<option value=\"$mm\"> $mm </option>";
$mm++;}} echo "</select> "; 

// отображаем год
echo"<select name=\"gg\" value=\"$myrow[gg]\">";
$gg=1940;
if ($gg<=2012){
while ($gg<=2012){
echo "<option value=\"$gg\"> $gg </option>";
$gg++;}} echo "</select>"; 


echo "
<br/>
Город:  (max 50) <br/>
<input type=\"text\" name=\"gorod\" value=\"$myrow[gorod]\" maxlength=\"50\"/> <br/>
ICQ:  (max 9) <br/>
<input type=\"text\" name=\"isq\" value=\"$myrow[isq]\" maxlength=\"9\"/> <br/>
Модель телефона: <br/> (max 15) <br/>
<input type=\"text\" name=\"tel\" value=\"$myrow[tel]\" maxlength=\"15\"/> <br/>
Оператор: (max 15) <br/>
<input type=\"text\" name=\"operator\" value=\"$myrow[operator]\" maxlength=\"15\"/> <br/>
О себе: <br/> 
<input type=\"text\" name=\"osebe\" value=\"$myrow[osebe]\" maxlength=\"1000\"/> <br/>
<input type=\"submit\" value=\"Создать\" class=\"ibutton\"></div>";
break;


case 'add':

	//-----Создание анкеты---------
		$ank = mysql_query("SELECT * FROM `anketa` WHERE `nik` = '$log'");
		$ank=mysql_num_rows($ank);

		
		if($ank > 0)
		{
		echo "Анкета уже создана<br/>";
		echo"<br/>» <b><a href=\"anketa.php?go=1\">Изменить</a></b><br/>";
		include"inc/down.php";exit;
		}

	//------------------------------


	if(time() >= $_SESSION['antispam']){
		if(!empty($_POST['name']) 
		&& !empty($_POST['gg']) 
		&& !empty($_POST['gorod']) 
		&& !empty($_POST['isq']) 
		&& !empty($_POST['tel']) 
		&& !empty($_POST['operator'])){	
				if(strlen($_POST['text']) <= 1699){
$name = htmlspecialchars(addslashes($_POST['name']));
$text = htmlspecialchars(addslashes($_POST['text']));

$dd = $_POST[dd];
$mm = $_POST[mm];
//$mm = $_POST[mm];


mysql_query("INSERT INTO `anketa` SET
        `nik` = '$log',
        `name` = '$_POST[name]',
        `dd` = '$_POST[dd]',
        `mm` = '$_POST[mm]',
        `gg` = '$_POST[gg]',
        `gorod` = '$_POST[gorod]',
        `isq` = '$_POST[isq]',
        `tel` = '$_POST[tel]',
        `operator` = '$_POST[operator]',
        `osebe` = '$_POST[osebe]'");




						$_SESSION['antispam'] = time() + 5;
					echo'Анкета успешно создана!';
				}else{
					echo'Слишком длинное сообщение! Не больше 1000 символов!';
				}
		}else{
			echo'Вы не заполнили поля!';
		}
	}else{
		echo'Антиспам! Разрешено писать раз в 5 сек.!';
	}
		echo"<br/><br/><a href=\"anketa.php?go=1\">Назад</a>";

	break;


//----------------------------------------------
}
include($path.'inc/down.php');
?>
