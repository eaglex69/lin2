<?
define('PROTECTOR', 1);

$headmod = 'rating';//фикс. места

$textl='Зал Славы';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
switch($_GET[mod]){

default:

echo "<font color=#f4d06e> Рейтинги игроков: </font><br/><br/>";
if ($udata[dostup]>=0){
}


echo"<div align=left>";
echo"<div align=left><hr/><a href=\"rating.php?mod=lvl\"><img src=\"pic/соп.png\" > Рейтинг самых опытных</a>";
echo"<div align=left><hr/><a href=\"rating.php?mod=matt\"><img src=\"pic/fata.png\" > Рейтинг самых сильных по M.Atk</a>";
echo"<div align=left><hr/><a href=\"rating.php?mod=patt\"><img src=\"pic/fata.png\" > Рейтинг самых сильных по P.Atk</a>";
echo"<div align=left><hr/><a href=\"rating.php?mod=mdef\"><img src=\"pic/pdeeef.png\" > Рейтинг самых сильных по M.Def</a>";
echo"<div align=left><hr/><a href=\"rating.php?mod=pdef\"><img src=\"pic/pdeeef.png\" > Рейтинг самых сильных по P.Def</a>";
echo"<div align=left><hr/><a href=\"rating.php?mod=glad\"><img src=\"pic/sspvp.png\" > Рейтинг самых сильных в PvP</a><hr/>";
echo "</div>";
echo"<br/><div class=silka><a href=\"index.php?\">Назад</a></div>";
break;

case 'adena':
if ($udata[dostup]>=4){
$b = mysql_query("SELECT usr,money FROM users WHERE money >'0' and dostup < '4' ORDER BY money DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [Адена <b>".number_format($best[money], 0, ',', "`")."</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет  игроков</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
}
break;
///////////
case 'col':
if ($udata[dostup]>=4){
$b = mysql_query("SELECT usr,almaz FROM users WHERE almaz >'0' and dostup < '4' ORDER BY almaz DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 - <font color=grey>CoL <b>".number_format($best[almaz], 0, ',', "`")."</b></font><br/>";
$i++;
}
}else{
echo"<b>Нет  игроков</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
}
break;
///////////




case 'glad':

echo "<p><font color=grey>PvP мастер</font></p><hr/>";

$b = mysql_query("SELECT usr,arenawins FROM users WHERE arenawins >'0' and dostup < '4' ORDER BY arenawins DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [PvP: <b>$best[arenawins]</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет гладиаторов</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;

case 'lvl':

echo "<p><font color=grey>Рейтинг EXP</font></p><hr/>";

$b = mysql_query("SELECT usr,lvl FROM users WHERE lvl >'0' and dostup < '4' ORDER BY lvl DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a> [<b>$best[lvl]</b> уровень]<br/>";
$i++;
}
}else{
echo"<b>Нет опытных игроков</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;
/////////////
case 'matt':

echo "<p><font color=grey>Рейтинг M.Att</font></p><hr/>";

$b = mysql_query("SELECT usr,matt FROM users WHERE matt >'0' and dostup < '4' ORDER BY matt DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [M.Att <b>$best[matt]</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет сильных игроков</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;

////////////////////////////

case 'patt':

echo "<p><font color=grey>Рейтинг P.Att</font></p><hr/>";

$b = mysql_query("SELECT usr,patt FROM users WHERE patt >'0' and dostup < '4' ORDER BY patt DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [P.Att <b>$best[patt]</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет ловких игроков</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;

case 'mdef':

echo "<p><font color=grey>Рейтинг М.Def</font></p><hr/>";

$b = mysql_query("SELECT usr,mdef FROM users WHERE mdef >'0' and dostup < '4' ORDER BY mdef DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [M.Def <b>$best[mdef]</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет умных игроков</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;

///////////
case 'pdef':

echo "<p><font color=grey>Рейтинг P.Def</font></p><hr/>";

$b = mysql_query("SELECT usr,pdef FROM users WHERE pdef >'0' and dostup < '4' ORDER BY pdef DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [p.Def <b>$best[pdef]</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет физ. защищёных игроков</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;
///////////
case 'hp':

echo "<p><font color=grey>Рейтинг жизней</font></p><hr/>";

$b = mysql_query("SELECT usr,hpall FROM users WHERE hpall >'0' and dostup < '4' ORDER BY hpall DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [Жизни <b>$best[hpall]</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет живчиков</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;
///////////
case 'mp':

echo "<p><font color=grey>Рейтинг маны</font></p><hr/>";

$b = mysql_query("SELECT usr,mpall FROM users WHERE mpall >'0' and dostup < '4' ORDER BY mpall DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [Мана <b>$best[mpall]</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет игроков с маной</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;
///////////
case 'money':

echo "<p><font color=grey>Адена</font></p><hr/>";

$b = mysql_query("SELECT usr,money FROM users WHERE money >0 and dostup < '4' ORDER BY money DESC LIMIT 0, 10");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"search.php?nick=$best[usr]&amp;go=go\">$best[usr]</a>
 [Монеты <b>$best[money]</b>]<br/>";
$i++;
}
}else{
echo"<b>Нет богачей</b><br/>";
}
echo"<a href=\"rating.php?\">Назад</a>";
break;
///////////
}

include($path.'inc/down.php');
?>