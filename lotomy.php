<?
define('PROTECTOR', 1);
$headmod = 'zayavka';//фикс. места
$textl='Предложения замуж';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

$loto=rand(1,7);
$coin=rand(1,4);
$gold=rand(100,10000);
$aden=rand(50000,40000000);
$drevaden=rand(50,300);
$time=date("H:i");


if($udata[lvl]<"20"){echo"Чтобы зайти в игорный дом  нужно иметь 20 или выше уровень.<br/>"; include_once("inc/down.php"); exit;}

switch($_GET[go]){

default:
echo"<center><img src=\"pic/roz/casino.png\" alt=\"\"></center>";
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e>- Добро пожаловать в Казино: Miniage ! </font></font></p></i></b>";
echo"<i><font color=#f4d06e>- Вы можете крутить колесо фортуны </font></font></p></i>";
echo"<i><font color=#f4d06e>- Стоимость прокрутки колеса один раз составляет <font color=#ffffcc> 1 <img src=\"pic/лааак.png\" height=15 width=15> Coin Of Luck </font></font></p></i>";
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=koleso\">Войти</a><br/>";

break;

case 'koleso':
echo"<center><img src=\"pic/roz/колесо.png\" alt=\"\"></center>";
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e> Колесо Фортуны ! </font></font></p></i></b>";
echo"<i><font color=#f4d06e>- На колесе 7 секторов,2 выигранных и 5 проигрышных,</font></font></p></i>";
echo"<i><font color=#f4d06e>- Соответственно шансы 30/70 </font></font></p></i>";
echo"<i><font color=#f4d06e>- Вам может выпасть от <font color=#ffffcc> 1 до 4 <img src=\"pic/лааак.png\" height=15 width=15> Coin of luck </font></font></p></i>";
echo"<i><font color=#f4d06e>- Или от <font color=#ffffcc> 50,000 до 40,000,000 <img src=\"pic/adena.png\" height=15 width=15> Аден </font></font></p></i>";
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=krutit\">Прокрутить</a><br/>";
echo"<a href=\"lotomy.php?\">В казино</a><br/>";
break;

case 'krutit':
if($loto==1){
if($udata[almaz]<"1"){echo"Чтоб крутить колесо нужно иметь минимум 1 монету удачи или выше.<br/>"; include_once("inc/down.php"); exit;}
$udata[almaz]=$udata[almaz]-1;
mysql_query("UPDATE `users` SET `almaz`='$udata[almaz]' WHERE `usr`='$log'");
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e> Выпал сектор номер <font color=#ff4040> 1 ! </font></font></p></i></b>";
echo"<i><font color=#f4d06e>- Вы ничего не выиграли, этот сектор пустой ! </font></font></p></i>";


echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=krutit\">Прокрутить еще</a><br/>";
echo"<a href=\"lotomy.php?go=koleso\">К Колесу</a><br/>";  
}

if($loto==2){
if($udata[almaz]<"1"){echo"Чтобы крутить колесо нужно иметь минимум 1 монету удачи или выше.<br/>"; include_once("inc/down.php"); exit;}

$udata[almaz]=$udata[almaz]-1;
mysql_query("UPDATE `users` SET `udata[almaz]`='$udata[almaz]' WHERE `usr`='$log'");
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e> Выпал сектор номер <font color=#ff4040> 2 ! </font></font></p><i></b>";
echo"<i><font color=#f4d06e>- Вы выиграли, этот сектор с выигрышными монетами удачи <font color=#ffffcc><img src=\"pic/лааак.png\" height=15 width=15> Coin Of Luck ! </font></font></p></i>";
echo"<i><font color=#f4d06e>- Вы выиграли <font color=#ffffcc> $coin <img src=\"pic/лааак.png\" height=15 width=15> Coin Of Luck. Поздравляем ! </font></font></p></i>"; 


echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=koleso\">Забрать</a><br/>";
echo"<a href=\"lotomy.php?go=krutit\">Прокрутить </a><br/>"; 
$udata[almaz]=round($udata[almaz]+$coin);
mysql_query("UPDATE `users` SET `almaz`='$udata[almaz]' WHERE `usr`='$log'");
}

if($loto==3){
if($udata[almaz]<"1"){echo"Чтобы крутить колесо нужно иметь минимум 1 монету удачи или выше.<br/>"; include_once("inc/down.php"); exit;}
$udata[almaz]=$udata[almaz]-1;
mysql_query("UPDATE `users` SET `almaz`='$udata[almaz]' WHERE `usr`='$log'");
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e> Выпал сектор номер <font color=#ff4040> 1 ! </font></font></p></i></b>";
echo"<i><font color=#f4d06e>- Вы ничего не выиграли, этот сектор пустой ! </font></font></p></i>";


echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=krutit\">Прокрутить еще</a><br/>";
echo"<a href=\"lotomy.php?go=koleso\">К Колесу</a><br/>"; 
}

if($loto==4){
if($udata[almaz]<"1"){echo"Чтобы крутить колесо нужно иметь минимум 1 монету удачи или выше.<br/>"; include_once("inc/down.php"); exit;}
$udata[almaz]=$udata[almaz]-1;
mysql_query("UPDATE `users` SET `almaz`='$udata[almaz]' WHERE `usr`='$log'");
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e> Выпал сектор номер <font color=#ff4040> 1 ! </font></font></p></i></b>";
echo"<i><font color=#f4d06e>- Вы ничего не выиграли, этот сектор пустой ! </font></font></p></i>";


echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=krutit\">Прокрутить еще</a><br/>";
echo"<a href=\"lotomy.php?go=koleso\">К Колесу</a><br/>"; 
}

if($loto==5){
if($udata[almaz]<"1"){echo"Чтобы крутить колесо нужно иметь минимум 1 монету удачи или выше.<br/>"; include_once("inc/down.php"); exit;}
$udata[almaz]=$udata[almaz]-1;
mysql_query("UPDATE `users` SET `almaz`='$udata[almaz]' WHERE `usr`='$log'");
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e> Выпал сектор номер <font color=#ff4040> 1 ! </font></font></p></i></b>";
echo"<i><font color=#f4d06e>- Вы ничего не выиграли, этот сектор пустой ! </font></font></p></i>";


echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=krutit\">Прокрутить еще</a><br/>";
echo"<a href=\"lotomy.php?go=koleso\">К Колесу</a><br/>"; 
}

if($loto==6){
if($udata[almaz]<"1"){echo"Чтобы крутить колесо нужно иметь минимум 1 монету удачи или выше.<br/>"; include_once("inc/down.php"); exit;}

$udata[almaz]=round($udata[almaz]-1);
mysql_query("UPDATE `users` SET `almaz`='$udata[almaz]' WHERE `usr`='$log'");
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e> Выпал сектор номер <font color=#ff4040> 6 ! </font></font></p></b>";
echo"<i><font color=#f4d06e>- Вы выиграли, этот сектор имеет <font color=#ffffcc><img src=\"pic/adena.png\" height=15 width=15> Адены ! </font></font></p></i>";
echo"<i><font color=#f4d06e>- Вы выиграли <font color=#ffffcc> $aden <img src=\"pic/adena.png\" height=15 width=15> Адена. Поздравляем ! </font></font></p></i>"; 


echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=koleso\">Забрать</a><br/>";
echo"<a href=\"lotomy.php?go=krutit\">Прокрутить </a><br/>"; 
$udata[money]=round($udata[money]+$aden);
mysql_query("UPDATE `users` SET `money`='$udata[money]' WHERE `usr`='$log'");
}
if($loto==7){
if($udata[almaz]<"1"){echo"Чтобы крутить колесо нужно иметь минимум 1 монету удачи или выше.<br/>"; include_once("inc/down.php"); exit;}

$udata[almaz]=round($udata[almaz]-1);
mysql_query("UPDATE `users` SET `almaz`='$udata[almaz]' WHERE `usr`='$log'");
echo"<b><i><img src=\"pic/roz/casino2.png\" alt=\"\"><font color=#f4d06e> Выпал сектор номер <font color=#ff4040> 7 ! </font></font></p></b>";
echo"<i><font color=#f4d06e>- Вы ничего не выиграли, этот сектор пустой ! </font></font></p></i>";


echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"<a href=\"lotomy.php?go=krutit\">Прокрутить еще</a><br/>";
echo"<a href=\"lotomy.php?go=koleso\">К Колесу</a><br/>"; 
}

break;
}

include($path.'inc/down.php');
?>