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

echo "<b>Весь мир</b> | <a href=\"users_new.php\">Новички</a><br/><br/>";

echo "Сортировать: ";
if (empty($sort)){$sort="lvl"; $so="DESC";}
if ($sort=='usr'){$ss="lvl"; $what="ур"; $so="ASC"; echo "Имя | ";}else{ echo"<a href=\"users.php?sort=usr\">Имя</a> | ";}
if ($sort=='exp'){$ss="exp"; $what="EXP"; $so="DESC"; echo "Опыт | ";}else{ echo"<a href=\"users.php?sort=exp\">Опыт</a> | ";}
if ($sort=='lvl'){$ss="lvl"; $what="ур"; $so="DESC"; echo "LvL | ";}else{ echo"<a href=\"users.php?sort=lvl\">LvL</a> | ";}
if ($sort=='id') {$ss="lvl"; $what="ур"; $so="ASC"; echo "ID";}else{ echo"<a href=\"users.php?sort=id\">ID</a>";}

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

$viso = mysql_num_rows(mysql_query("SELECT id FROM users"));
$puslap = floor($viso/10);

//-------------------------------

echo "<br/><br/>";
$req = mysql_query("SELECT * FROM `users` WHERE `id`!='' ORDER BY $sort $so LIMIT $num,10");
$avto=mysql_num_rows($req);
if($avto>0){
While($usr = mysql_fetch_array($req))
{


if ($usr['dostup']==5) {$color='<font color=lime>';}
if ($usr['dostup']==4) {$color='<font color=lime>';}
if ($usr['dostup']==3 or $usr['dostup']==2) {$color='<font color=#547ef4>';}
if ($usr['dostup']==1 or $usr['dostup']==0) {$color='';}
if ($usr['dostup']<2){
$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$usr[usr]' LIMIT 1"); 
$avto=mysql_num_rows($req222);
if($avto==1){
$cl = mysql_fetch_array($req222);
$color = "<font color=#$cl[color]>";}else{
$color = "";}
}

echo "<br>";
echo "<a href=\"search.php?nick=$usr[usr]&amp;go=go\">$pic $color $usr[usr] </font><font color=#ffffcc> • </font> <font color=#ffffcc>  ".number_format($usr[$ss], 0, ',', " ")." $what</font></a>";
echo "</br>";

}
}
//-*-----
echo "<br/>";
echo ' <div style="text-align			:center;">';
if ($_GET['page'] > 0)
{
echo "<a href=\"users.php?page=$back&sort=$sort\">Назад</a>";
}
elseif ($_GET['page'] == 0)
{
echo "Назад";
}
echo" | ";
if($_GET['page'] < $puslap || $_GET['page'] == "" || $_GET['page'] == 0)
{echo "<a href=\"users.php?page=$next&sort=$sort\">Далее</a>";}
else
{echo "Далее";}
echo "</div>";


include($path.'inc/down.php');
?>
