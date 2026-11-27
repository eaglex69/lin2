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
echo"<div class='menu_razd'>Все мои ставки</div>";


echo "Нет ставок\n";



$q5=mysql_query("SELECT * FROM `tot_stavki` WHERE `idu` = '".$udata['id']."' ORDER BY id DESC LIMIT 0, 10");
while($arr=mysql_fetch_array($q5)){


echo"<div class='post'><b>$arr[kom1name]</b> - <b>$arr[kom2name]</b><br>";
echo"Ставка была сделана : [<b>".$arr['date']."</b>&nbsp;|&nbsp;<b>".$arr['time']."</b>]<br>";

echo"Ваша ставка : <b>$arr[kom1]</b> | <b>$arr[kom2]</b><br>";
echo"Выигрышная ставка : <b>$arr[win1]</b> | <b>$arr[win2]</b> <br>";
if($arr['kom1'] == $arr['win1'] && $arr['kom2'] == $arr['win2'])
{
echo"<b>Ваша ставка сыграла!! <font color='lime'>:-)</font></b><br>";
}else{
echo"<b>Ваша ставка не сыграла!! <font color='red'>:-(</font></b><br>";
}
echo"<hr>";
}




echo"<a href='index.php'>В тотализатор</a>";
} }
if($total_on==1){echo"<b>Извините, наш тотализатор временно не рaботает .<br> Зайдите пожалуйста позже!</b><br><br>"; }
include($path.'inc/down.php');
?>