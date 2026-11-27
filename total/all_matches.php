<?php
define('PROTECTOR', 1);

$headmod = 'Тотализатор';
$textl='total';
$path='../';
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');




$total=mysql_result(mysql_query("SELECT on_total FROM `tot_adminka` WHERE `id`='1'"),0);




if($total==1){
switch($_GET['mod']){



#### Главная скрипта! ####




default:

echo"<div class='menu_razd'>История всех матчев</div>";




$k_post=mysql_result(mysql_query("SELECT count(*) FROM `tot_istor`"),0);
echo "<table class='post'>";
if ($k_post==0)
{
	echo "<b>Матчи еще не проходили :-(</b><br>\n";
}

$q5=mysql_query("SELECT * FROM `tot_istor` ORDER BY id DESC LIMIT 0, 10");
while($arr=mysql_fetch_array($q5)){
$stavl=mysql_result(mysql_query("SELECT count(idu) FROM `tot_stavki` WHERE `idm`='".$arr['idm']."'"),0);
$win=mysql_result(mysql_query("SELECT count(*) FROM `tot_stavki` WHERE `kom1`='".$arr['kom1']."' and `kom2`='".$arr['kom2']."' and `idm`='".$arr['idm']."'"),0);


echo"<div class='post'><u>$arr[kom1name]</u><b>  $arr[kom1]</b> - <b>$arr[kom2]  </b><u>$arr[kom2name]</u> [<b>$arr[date]</b>]<br>";


echo"<a href='?mod=stavil&idm=$arr[idm]'>Ставили</a> : <b>$stavl</b><br>";
echo"<a href='?mod=win_users&idm=$arr[idm]'>Угадавшие</a> : <b>$win</b><br><hr>";





}


echo"</table>";
echo"<br><a href='index.php'>Назад</a>";
break;


########## Ставил ###
case"stavil":
$_GET['idm'] = intval($_GET['idm']);
$q2=mysql_query("SELECT * FROM `tot_stavki` WHERE `idm`='".$_GET['idm']."'");

if(mysql_num_rows($q2) == 0)
{
echo "Ставок на матч нет.";
}

while($arr2=mysql_fetch_array($q2)){
$ar2=mysql_fetch_array(mysql_query("SELECT `usr` FROM `users` WHERE `id`='".$arr2['idu']."'"));
echo "<a href='<a href='/search.php?nick=$arr[usr]&go=go'>$ar2[usr]</a><br/>";
}
echo"<br><a href='index.php'>Назад</a><br>";
echo"<a href='index.php'>В тотализатор</a>";
break;
#####################

###### Выигравшые ####
case"win_users":
$_GET['idm'] = intval($_GET['idm']);
$q=mysql_query("SELECT * FROM `tot_stavki` WHERE `kom1`=`win1` and `kom2`=`win2` and `idm`='".$_GET['idm']."'");
if(mysql_num_rows($q) == 0)
{echo "Угадавшых ставок нет.";}
while($arr=mysql_fetch_array($q)){
$ar=mysql_fetch_array(mysql_query("SELECT `usr` FROM `users` WHERE `id`='".$arr['idu']."'"));
echo "<br><a href='/$arr[idu]'>$ar[usr]</a><br/>";
}
echo"<br><a href='all_matches.php'>Назад</a><br>";
echo"<a href='index.php'>В тотализатор</a>";
break;
#################







### Низ скрипта ###
}
}
if($total_on==1){ echo"<b>Извините , наш тотализатор временно не роботает .<br> Зайдите пожалуйста позже!</b><br><br>"; }


include($path.'inc/down.php');
?>
