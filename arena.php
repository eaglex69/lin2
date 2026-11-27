<?
define('PROTECTOR', 1);

$headmod = 'arena';//фикс. места

$textl='Арена';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

switch($_GET[mod]){

default:
echo"<center><img src=\"pic/roz/пвпарена.png\" alt=\"\"></center>";
if($udata[lvl]<20){
$textl='Битва';
echo "<b>Арена будет доступна с 20 уровня!</b>";
echo"<div class=inoy><a href=\"gorod.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}


log_msg_ar($tlog='battle');  // лог действий Арены

echo "<br/><center><font color=#f4d06e><b> Арена боя</b></font><hr/></div><div class=fon>";
$req = mysql_query("SELECT * FROM `mesto` WHERE `place` = 'arena' and `usr` != '$log'");
////////////////////////////
if(mysql_num_rows($req)>=1)
{

While($arena = mysql_fetch_array($req))
{
$requsr = mysql_query("SELECT * FROM `users` WHERE `usr` = '$arena[usr]'");
$usr = mysql_fetch_assoc($requsr);


$asd = mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeou' and usr = '$arena[usr]'");
$i=1;
While($visi = mysql_fetch_array($asd))
{
if ($usr[hp]<0){echo "<div class=menu> $arena[usr] <font color=grey> убит </font><br/></div>";}else{
echo"<div class=inoy><a href=\"okar.php?k=ar&amp;id=$usr[id]\"> $arena[usr] <font color=#FFFFFF>[$usr[hp]/$usr[hpall]]</font></a></div> ";
}}}}else{echo "<div class=menu>Кроме Вас на Арене никого нет.";}

echo "</div>";
break;





}
include($path.'inc/down.php');
?>