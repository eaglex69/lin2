<?php
define('PROTECTOR', 1);

$headmod = 'obm_fs_ad';

$textl='Открытие сундуков';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


switch($_GET['mod']){


default:

$res = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' && `name` = 'Сундук удачи'");
$av = mysql_num_rows($res);
$fa = mysql_fetch_array($res);
$av = $fa['kol'];
if (empty($av)){$av = 0;}
$resk = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' && `name` = 'Ключ'");
$avk = mysql_num_rows($resk);
$fak = mysql_fetch_array($resk);
$avk = $fak['kol'];
if (empty($avk)){$avk = 0;}
if ($av==0){

echo "<p><font color=grey><b>У Вас нет Сундуков удачи.</b></font></p>";


echo "<div class=inoy><a href=\"inventar.php\">Инвентарь</a></div>";
echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";
}else{


if (isset($_GET['yes'])){ 


if($avk>0){
$req22 = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `name` = 'Сундук удачи'");
$res=mysql_num_rows($req22);
$rs = mysql_fetch_array($req22);
if($res==0){
echo "<p><font color=red>У Вас нет сундуков удачи...</font></p>";
}else{
$avk=$avk-1;
$nk=$rs['kol']-1;
mysql_query("UPDATE `res` SET `kol` = '$avk' WHERE `usr` = '$log' and `name` = 'Ключ'");
mysql_query("UPDATE `res` SET `kol` = '$nk' WHERE `usr` = '$log' and `name` = 'Сундук удачи'");
if($nk <= 0){ //удаляем
mysql_query("DELETE FROM `res` WHERE `usr` = '$log' and `name` = 'Ключ' LIMIT 1");
mysql_query("DELETE FROM `res` WHERE `id` = '$rs[id]' LIMIT 1");
}
}
}else{echo'У вас нету ключей';include($path.'inc/down.php');exit;}










$r_col=rand(1,100);
$kol=rand(1,15);
if ($r_col<=30){ 
$colnew=$udata['almaz']+$kol;
mysql_query("UPDATE `users` SET `almaz` = '$colnew' WHERE `usr` = '$log' LIMIT 1");
$rez_col = " Выпало: <b>$kol Coin of Luck </b><br/>";
}else{$rez_col = 0;}

$r_aden=rand(1,100);
$aden=rand(5000000,95000000);
if ($r_aden<=50){ 
$moneynew=$udata['money']+$aden;
mysql_query("UPDATE `users` SET `money` = '$moneynew' WHERE `usr` = '$log' LIMIT 1");
$rez_aden = " Выпало: <b>$aden Аден </b><br/>";
}else{$rez_aden = 0;}

$r_exp=rand(1,100);
$exp=rand(50000,9500000);
if ($r_exp<=50){ 
$expnew=$udata['exp']+$exp;
mysql_query("UPDATE `users` SET `exp` = '$expnew' WHERE `usr` = '$log' LIMIT 1");
$rez_aden = " Выпало: <b>$exp Опыта </b><br/>";
}else{$rez_exp = 0;}





echo "<div class=dot><u>Cундук успешно открыть:</u><br/><br/>";

if (empty($rez_aden) and empty($rez_col) and empty($rez_exp))
{echo "<b>Ничего не выпало...</b>";}
else
{
if (!empty($rez_aden)){echo "$rez_aden";}
if (!empty($rez_col)){echo "$rez_col";}
if (!empty($rez_exp)){echo "$rez_exp";}
}

$av2 = $av - 1;

echo "</p><p>Осталось: $av2 сундуков";
echo "</p><p>Осталось: $avk ключей";
echo "</p></div>";

if ($av2>0){
echo "<div class=inoy><a href=\"?yes\">Открыть еще один</a></div>";
}
echo "<div class=inoy><a href=\"inventar.php\">Инвентарь</a></div>";
echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";

include($path.'inc/down.php');

}
//-------------------------------------------

echo "<p><font color=grey><b>У Вас $av Сундуков Удачи.</b></font></p>";
echo "<p><font color=grey><b>У Вас $avk Ключей.</b></font></p>";
echo "<p><font color=#D2B48C>Вы действительно хотите открыт <b><u>Сундук удачи</u></b>? При открытии могут выпасть Coin of Luck,адена и опыт.</b></font></p>";

echo "<div class=inoy><a href=\"?yes\">Да, хочу</a></div>";

echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";

}

break;



}
include($path.'inc/down.php');
?>