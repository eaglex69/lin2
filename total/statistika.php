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
$vsego_m=mysql_result(mysql_query("SELECT COUNT(id) FROM `tot_istor`"),0);
$vsego_s=mysql_result(mysql_query("SELECT COUNT(id) FROM `tot_stavki`"),0);
$vsego_v=mysql_result(mysql_query("SELECT COUNT(id) FROM `tot_stavki` WHERE `idu`='$udata[id]'"),0);
$vsego_ug=mysql_result(mysql_query("SELECT COUNT(*) FROM `tot_stavki` WHERE `idu`='$udata[id]' and `kom1`=`win1`"),0);
echo"Всего завершеных матчей : <b>$vsego_m</b><br>";
echo"Всего ставок : <b>$vsego_s</b><br>";
if($vsego_v){
echo"Ваших ставок : <b>$vsego_v</b><br>";
if($vsego_ug){
echo"|<small>Из них угадали <b>$vsego_ug</b></small><br>";}
else
{echo"|<small>Неугадали ни одного<br></small>";}
}
else
{echo"Ваши ставки отсутствуют!<br>";}
}
else{ echo"<b>Извините , наш тотализатор временно не роботает .<br> Зайдите пожалуйста позже!</b><br><br>"; }
echo"<a href='index.php'>В тотализатор</a>";

include($path.'inc/down.php');
?>