<?php
define('PROTECTOR', 1);

$textl='Задания';
///////////////////////
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
///////////////



echo"<center><img src=\"pic/roz/нор.png\" alt=\"\"></center>";
echo "<div class=msg><p><span style=color:#f4d06e;>Задания:</b></span></p></div>";


//--------------------------------------------------------------------------//--------------------------------------------------------------------------
echo "<p><hr/></p>";
//--------------------------------------------------------------------------//--------------------------------------------------------------------------

//-------------мобы в сумме 2000 доступно с 76 уровня-------------------
// награда +5 кол -/- + опыт от уровня
if ($udata['lvl']>76){

//---выполненно--
if (isset($_GET['jbm'])){
$jm = mysql_fetch_array(mysql_query("SELECT * FROM job_mob WHERE `usr` = '$log' LIMIT 1"));
if($jm['kill']>=20000){

$newexp = $udata['exp']+5000000000;
$newalmaz = $udata['almaz']+1200;

mysql_query("UPDATE users SET `exp` = '$newexp', `almaz` = '$newalmaz'  WHERE `usr` = '$log' Limit 1"); // вознаграждение

$date = date("d.m.Y");
mysql_query("UPDATE job_mob SET `kill` = 'off',`data` = '$date' WHERE `usr` = '$log' Limit 1"); // получил вознаграждение

}}
//---------------




$jm = mysql_fetch_array(mysql_query("SELECT * FROM `job_mob` WHERE `usr` = '$log' LIMIT 1"));
if(empty($jm['kill'])){$mobkill=0;}else{$mobkill=$jm['kill'];}

$moball=20000; // при смене менять и в файле battle.php
$mobpr = floor($mobkill / $moball*100);
		echo "<font color=#f4d06e> Убить 20`000 мобов: </b></font><br/>";
if ($mobkill=='off' and $mobkill!==0){echo "<div style=color:#6fcd72;padding:1% 1% 1% 1%;>ВЫПОЛНЕНО</div>";}else{echo '	<span style="top:9px;left:2%;color:#f4d06e;"> Процесс:</b><font color=#ffffcc>    '.$mobkill.' / '.$moball.'</span><div style="border:1px solid #339900;font-size: 9px;background:1px #3a3b43;height:10px;width:160px;"><div style="background-color:#62bb46;height:10px;width:'.$mobpr.'%;"></div></div>';
echo "<div style=color:#f4d06e;padding:1% 1% 1% 1%;>+1200 CoL -/- +5 kkk EXP</div>";	
	if ($mobkill>=$moball)	{echo "<div class=\"silka\"> <b><a href=\"/job.php?jbm\">Завершить</a></b></div>";}
		}
		echo "</div>";


}else{echo "<font color=#f4d06e> Убить 20`000 мобов: </b></font><br/>Доступно с 76 уровня</div>";}

//--------------------------------------------------------------------------//--------------------------------------------------------------------------
echo "<p><hr/></p>";
//--------------------------------------------------------------------------//--------------------------------------------------------------------------





//-------------рыбалка в сумме 1000 доступно с 50 уровня-------------------
// награда +5 кол -/- + опыт от уровня
if ($udata['lvl']>49){

//---выполненно--
if (isset($_GET['jbf'])){
$jm = mysql_fetch_array(mysql_query("SELECT * FROM job_fish WHERE `usr` = '$log' LIMIT 1"));
if($jm['kill']>=10000){

$newexp = $udata['exp']+1000000000;
$newalmaz = $udata['almaz']+450;

mysql_query("UPDATE users SET `exp` = '$newexp', `almaz` = '$newalmaz'  WHERE `usr` = '$log' Limit 1"); // вознаграждение

$date = date("d.m.Y");
mysql_query("UPDATE job_fish SET `kill` = 'off',`data` = '$date' WHERE `usr` = '$log' Limit 1"); // получил вознаграждение

}}
//---------------




$jm = mysql_fetch_array(mysql_query("SELECT * FROM `job_fish` WHERE `usr` = '$log' LIMIT 1"));
if(empty($jm['kill'])){$mobkill=0;}else{$mobkill=$jm['kill'];}

$moball=10000; // при смене менять и в файле fish.php
$mobpr = floor($mobkill / $moball*100);
		echo "<font color=#f4d06e> Словить 10`000 рыб: </b></font><br/>";
if ($mobkill=='off' and $mobkill!==0){echo "<div style=color:#6fcd72;padding:1% 1% 1% 1%;>ВЫПОЛНЕНО</div>";}else{echo '	<span style="top:9px;left:2%;color:#f4d06e;"> Процесс:</b><font color=#ffffcc>    '.$mobkill.' / '.$moball.'</span></font><div style="border:1px solid #339900;font-size: 9px;background:1px #3a3b43;height:10px;width:160px;"><div style="background-color:#62bb46;height:10px;width:'.$mobpr.'%;"></div></div>';
echo "<div style=color:#f4d06e;padding:1% 1% 1% 1%;>+450 CoL -/- +1 kkk EXP</div>";	
	if ($mobkill>=$moball)	{echo "<div class=\"silka\"> <b><a href=\"/job.php?jbf\">Завершить</a></b></div>";}
		}
		echo "</div>";


}else{echo "<font color=#f4d06e> Словить 10`000 рыб: </b></font><br/>Доступно с 50 уровня</div>";}
//--------------------------------------------------------------------------


//--------------------------------------------------------------------------//--------------------------------------------------------------------------
echo "<p><hr/></p>";
//--------------------------------------------------------------------------//--------------------------------------------------------------------------





//-------------Мастер PvP в сумме 100 доступно с 50 уровня-------------------
// награда +5 кол -/- + опыт от уровня
if ($udata['lvl']>49){

//---выполненно--
if (isset($_GET['jpvp'])){
$jm = mysql_fetch_array(mysql_query("SELECT * FROM job_pvp WHERE `usr` = '$log' LIMIT 1"));
if($jm['kill']>=100){

$newexp = $udata['exp']+2000000000;
$newalmaz = $udata['almaz']+75;

mysql_query("UPDATE users SET `exp` = '$newexp', `almaz` = '$newalmaz'  WHERE `usr` = '$log' Limit 1"); // вознаграждение

$date = date("d.m.Y");
mysql_query("UPDATE job_pvp SET `kill` = 'off',`data` = '$date' WHERE `usr` = '$log' Limit 1"); // получил вознаграждение

}}
//---------------




$jm = mysql_fetch_array(mysql_query("SELECT * FROM `job_pvp` WHERE `usr` = '$log' LIMIT 1"));
if(empty($jm['kill'])){$mobkill=0;}else{$mobkill=$jm['kill'];}

$moball=100; // при смене менять и в файле okar.php
$mobpr = floor($mobkill / $moball*100);
		echo "<font color=#f4d06e> PvP 100 килов при нападении: </b></font><br/>";
if ($mobkill=='off' and $mobkill!==0){echo "<div style=color:#6fcd72;padding:1% 1% 1% 1%;>ВЫПОЛНЕНО</div>";}else{echo '	<span style="top:9px;left:2%;color:#f4d06e;"> Процесс:</b><font color=#ffffcc>    '.$mobkill.' / '.$moball.'</span><div style="border:1px solid #339900;font-size: 9px;background:1px #3a3b43;height:10px;width:160px;"><div style="background-color:#62bb46;height:10px;width:'.$mobpr.'%;"></div></div>';
echo "<div style=color:#f4d06e;padding:1% 1% 1% 1%;>+75 CoL -/- +2 kkk EXP</div>";	
	if ($mobkill>=$moball)	{echo "<div class=\"silka\"> <b><a href=\"/job.php?jpvp\">Завершить</a></b></div>";}
		}
		echo "</div>";


}else{echo "<font color=#f4d06e><b> PvP 100 килов при нападении: </b></font><br/>Доступно с 50 уровня</div>";}
//--------------------------------------------------------------------------

//--------------------------------------------------------------------------//--------------------------------------------------------------------------
echo "<p><hr/></p>";
//--------------------------------------------------------------------------//--------------------------------------------------------------------------





include($path.'inc/down.php');
?>