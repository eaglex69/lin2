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

 error_reporting(0);

$total=mysql_result(mysql_query("SELECT on_total FROM `tot_adminka` WHERE `id`='1'"),0);


if($total==1){
echo"<div class='menu_razd'>Топ 15</div>";
$q=mysql_query("SELECT * FROM `users` ORDER BY tot_o DESC LIMIT 15");



while($arr=mysql_fetch_array($q))
{

if($arr['tot_o']){
echo " <a href='/search.php?nick=$arr[usr]&go=go'>$arr[usr]</a> Очков: $arr[tot_o]<br/>";}


}




### Низ скрипта ###

echo '<br><a href=index.php>В тотализатор</a>';

}
if($total_on==1){ echo"<b>Извините , наш тотализатор временно не роботает .<br> Зайдите пожалуйста позже!</b><br><br>"; }
include($path.'inc/down.php');
?>