<?php
define('PROTECTOR', 1);

$headmod = 'users';//фикс. места

$textl='Весь мир';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
///////////////
$taim = 150;
$date = time();
$timeout = $date - $taim;
////////////////

$sort=$_GET['sort'];

echo "</div><div class=fon>";

echo "<a href=\"users.php\">Весь мир</a> | <b>Новички</b><br/>";


//---выщитует страницы----------
if (!is_numeric($_GET['page']) || $_GET['page'] < 0 || $_GET['page'] == "0") 
{
$_GET['page'] = 0;
}
$next = $_GET['page'] + 1;
$back = $_GET['page'] - 1;
$num = $_GET['page'] * 10;
if($_GET['page'] == "0")
{$i = 1;}
else{$i = ($_GET['page']*10)+1;}

$viso = mysql_num_rows(mysql_query("SELECT id FROM new_usr"));
$puslap = floor($viso/10);

//-------------------------------

echo "<br/><br/>";
$req = mysql_query("SELECT * FROM `new_usr` ORDER BY id DESC LIMIT $num,10");
$avto=mysql_num_rows($req);
if($avto>0){ $i = $num+1;

While($usr = mysql_fetch_array($req))
{


if ($usr['dostup']==4 or $usr['dostup']==5) {$color='<font color=lime>';}
if ($usr['dostup']==3 or $usr['dostup']==2) {$color='<font color=blue>';}
if ($usr['dostup']==1 or $usr['dostup']==0) {$color='';}
if ($usr['dostup']<2){
$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$usr[usr]' LIMIT 1"); 
$avto=mysql_num_rows($req222);
if($avto==1){
$cl = mysql_fetch_array($req222);
$color = "<font color=#$cl[color]>";}else{
$color = "";}
}

$q = mysql_query("SELECT lvl FROM `users` WHERE `usr`='$usr[usr]' LIMIT 1");
$us = mysql_fetch_array($q);

echo "<div class=inoy>";
echo "<a href=\"search.php?nick=$usr[usr]&amp;go=go\"><font color=#EFF5FB>$i)</font> $pic $color $usr[usr] </font><font color=#ffffff></font> <font color=grey>  [".number_format($us['lvl'], 0, ',', " ")." lvl]</font></a>";
echo "</div>";
$i = $i+1;

}
}
//-*-----
echo "<br/>";
echo ' <div style="text-align			:center;">';
if ($_GET['page'] > 0)
{
echo "<a href=\"users_new.php?page=$back&sort=$sort\">Назад</a>";
}
elseif ($_GET['page'] == 0)
{
echo "Назад";
}
echo" | ";
if($_GET['page'] < $puslap || $_GET['page'] == "" || $_GET['page'] == 0)
{echo "<a href=\"users_new.php?page=$next&sort=$sort\">Далее</a>";}
else
{echo "Далее";}
echo "</div>";


include($path.'inc/down.php');
?>
