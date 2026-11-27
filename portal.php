<?
define('PROTECTOR', 1);

$headmod = 'portal';//фикс. места

$textl='Портал';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
place_okr();
place_zamok();
place_tower();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

switch($_GET[mod]){

default:
echo"<center><img src=\"pic/roz/tp.png\" alt=\"\"></center>";
echo'<font color=#f4d06e><i>Страж: <br /><font color=#ffffcc> -Приветствую тебя, странствующий. В городе можно отдохнуть от походов, пополнить припасы, купить доспехи. За воротами находятся окрестности города. Будь осторожен, там обитают опасные монстры.</i><br /><br />';
echo"<b><p>Города:</p></b><hr/>";
$req = mysql_query("SELECT * FROM `gorod` WHERE `name` != '$udata[city]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<div class=inoy> <a href=\"portal.php?mod=go&amp;id=$mag[id]&amp;tip=city\"><img src=\"pic/gorodaaa.png\" alt=\"pic\"/> &#160 $mag[name]  <font color=#ffffcc> - ".number_format($mag[cena], 0, ',', "`")." Aden </font></a> </div>";
}
}else{
echo"Нет активных порталов<br/>";
}

echo"<b><p>Окрестности:</p></b><hr/>";
$req = mysql_query("SELECT * FROM `okra` WHERE `gorod` = '$udata[city]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<div class=inoy> <a href=\"portal.php?mod=okr&amp;id=$mag[id]&amp;tip=city\"><img src=\"pic/okraaa.png\" alt=\"pic\"/> &#160 $mag[name]  <font color=#ffffcc> - ".number_format($mag[cena], 0, ',', "`")." Aden </font></a> </div>";
}
}else{
echo"Нет активных порталов<br/>";
}


break;

case 'go':
$req = mysql_query("SELECT * FROM `gorod` WHERE `name` != '$udata[city]' and id='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
$mag = mysql_fetch_array($req);
if($udata[money]<$mag[cena]){
echo'<p><center>Нехватает денег!</center></p>';
}elseif($udata[lvl]<$mag[lvl]){
echo'<p><center>Маленький уровень!</center></p>';
}else{
switch($_GET[tip]){
default:
echo'<p><center>Ошибка!Несуществует такого портала!<p></center>';
include($path.'inc/down.php');exit;
break;
case 'city':
$udata[money]=$udata[money]-$mag[cena];
mysql_query("UPDATE `users` SET `money` = '$udata[money]',`city`='$mag[name]' WHERE `usr` = '$log'");
// ЗАПИСАТЬ ЮЗЕРУ КООРДИНАТЫ ГОРОДА	
$req22 = mysql_query("SELECT * FROM `okra` WHERE `gorod` = '$mag[name]' Limit 1");
$gorod = mysql_fetch_array($req22);

mysql_query("UPDATE `mesto` SET `mesto` = '$gorod[name]',`page`='0' WHERE `usr` = '$log'");
// ////////////////////////////////
echo"<hr/><div class=head><br/> Вы прибыли в $mag[name]! <br/><br/></div><p><hr/></p>";
echo"<div class=silka><a href=\"gorod.php?\"><center>Войти в Город</center></a></div>";

include($path.'inc/down.php');exit;
break;
}
}}else{

echo"<br/><hr/><div class=inf><font color=#7F0000><center> Нет такого портала! </center></font></div><hr/>";

//echo"<b>Нет такого портала!</b><br/>";
}
echo"<div class=silka><a href=\"portal.php?\"><center>Назад</center></a></div>";

break;


////////////////////////////////////////

case 'okr':
$req = mysql_query("SELECT * FROM `okra` WHERE `gorod` = '$udata[city]' and id='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
$mag = mysql_fetch_array($req);
if($udata[money]<$mag[cena]){
echo'<p><center>Нехватает денег!</center></p>';
}elseif($udata[lvl]<$mag[lvl]){
echo'<p><center>Мал уровень!</center></p>';
}else{
switch($_GET[tip]){
default:
echo'<p><center>Нет такого портала!</center></p>';
include($path.'inc/down.php');exit;
break;
case 'city':
$udata[money]=$udata[money]-$mag[cena];
mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");
// ЗАПИСАТЬ ЮЗЕРУ КООРДИНАТЫ ГОРОДА	http://l3n.ru/to_world.php?
mysql_query("UPDATE `mesto` SET `mesto` = '$mag[name]',`page`='0' WHERE `usr` = '$log'");
// ////////////////////////////////

echo"<hr/><div class=head><br/> Вы  в окре $mag[name]! <br/><br/></div><p><hr/></p>";
echo"<div class=silka><a href=\"to_world.php?\"><center>Далее</center></a></div>";

//echo"Вы прибыли в $mag[name]!<br/>";
//echo"<a href=\"to_world.php?\">Далее</a>";
include($path.'inc/down.php');exit;
break;
}
}}else{
echo'<p><center>Нет такого портала!</center></p>';
}
echo"<div class=silka><a href=\"portal.php?\"><center>Назад</center></a></div>";
break;




}
include($path.'inc/down.php');
?>