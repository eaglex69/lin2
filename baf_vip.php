<?
define('PROTECTOR', 1);

$headmod = 'baf';//фикс. места

$textl='Баф зона';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
///////////////
$taim = 150;
$date = time();
$timeout = $date - $taim;
////////////////

//---------------------
$nick=$_GET[nick];
$id=$_GET[id];
//---------------------
/*
if ($udata[dostup]<4){
echo'Подождите! Делаем новые бафы!!!<br/>';
echo"<br/><a href=\"pers.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
*/
echo"<center><img src=\"pic/roz/tp.jpg\" alt=\"\"></center>";
echo "</div> <div class = fon><p><font color=f4d06e>Вип Бафы</font></p><hr/>";

switch($_GET[mod]){

default:

if (!empty($nick)) {$_POST[nick] = $nick;}  // если значение ник не пустое то пост ник равен нику

if(empty($_POST[nick])){
echo '<form action="baf_vip.php?" method="post">';
echo"Ник:<br/>
<input class='input' type=\"text\" size=\"15\" value=\"$nick\" name=\"nick\"/><br/>";




echo '<input class="button" type="submit" value="Редакт." /></form><br/>';

echo"<div class=silka>		<a href=\"baf_vip.php?&nick=$log\">Бафнуть себя</a>	</div>";

}else{

if (!empty($_POST[nick])) {$nick = $_POST[nick];} // если значение пост ник не пустое то знаение ник равно ему

$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$nick'");
$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка, такого игрока нет!<br/>';
echo"<br/><a href=\"baf_vip.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}

echo"<div class=silka>

		<a href=\"baf_vip.php?mod=add&tip=200&nick=$nick\">Баф +200% Def & Att <font color=silver>( - 1kk Аден / 30 мин ) </font></a>
		<a href=\"baf_vip.php?mod=add&tip=5kad&nick=$nick\">Баф +2k Att & Def <font color=silver>( - 5kk аден / 30 мин ) </font></a>

		<a href=\"baf_vip.php?mod=add&tip=40kad&nick=$nick\">Баф +10k Def & Att <font color=silver>( - 40 CoL / 3 часа ) </font></a>
		<a href=\"baf_vip.php?mod=add&tip=300&nick=$nick\">Баф +300% Def & Att <font color=silver>( - 20 CoL / 30 мин ) </font></a>

		<a href=\"baf_vip.php?mod=add&tip=4001&nick=$nick\">Баф +400% Def & Att <font color=silver>( - 50 CoL / 30 мин ) </font></a>
		<a href=\"baf_vip.php?mod=add&tip=4002&nick=$nick\">Баф +400% Def & Att <font color=silver>( - 150 CoL / 2 часа ) </font></a>
		<a href=\"baf_vip.php?mod=add&tip=4003&nick=$nick\">Баф +400% Def & Att <font color=silver>( - 250 CoL / 5 часов ) </font></a>

		<a href=\"baf_vip.php?mod=add&tip=200kad&nick=$nick\"><font color=orange>Баф +60k Def & Att </font><font color=silver>( - 300 CoL / 24 часа ) </font></a>
		
</div>";
}
break;

case 'add':


$reqi = mysql_query("SELECT * FROM `users` WHERE `usr`='$nick' LIMIT 1");
$usr = mysql_fetch_array($reqi);

if ($_GET[tip]=="5kad" or $_GET[tip]=="40kad" or $_GET[tip]=="300" or $_GET[tip]=="4001" or $_GET[tip]=="4002" or $_GET[tip]=="4003" or $_GET[tip]=="200kad" or $_GET[tip]=="200"){


if ($_GET[tip]=="5kad")	{$att = 2000; $def = 2000; $alm = 0; $money = 5000000; $times = 1800; $sms="Att & Def  +2`000 / Адена -5`000`000 / 30 мин";} 

if ($_GET[tip]=="40kad")	{$att = 10000; $def = 10000; $alm = 40; $money = 0; $times = 10800; $sms="Att & Def  +10`000 / CoL -40 / 3 часа";} // 

if ($_GET[tip]=="200")	{$att = round(($usr[patt]+$usr[matt])/2*2); $def = round(($usr[pdef]+$usr[mdef])/2*2); $alm = 0; $money = 1000000; $times = 1800; $sms="Att & Def  +200% / -1kk Аден / 30 мин";} // 
if ($_GET[tip]=="300")	{$att = round(($usr[patt]+$usr[matt])/2*3); $def = round(($usr[pdef]+$usr[mdef])/2*3); $alm = 20; $money = 0; $times = 1800; $sms="Att & Def  +300% / CoL -20 / 30 мин";} // 
if ($_GET[tip]=="4001")	{$att = round(($usr[patt]+$usr[matt])/2*4); $def = round(($usr[pdef]+$usr[mdef])/2*4); $alm = 50; $money = 0; $times = 1800; $sms="Att & Def  +400% / CoL -50 / 30 мин";} // 
if ($_GET[tip]=="4002")	{$att = round(($usr[patt]+$usr[matt])/2*4); $def = round(($usr[pdef]+$usr[mdef])/2*4); $alm = 150; $money = 0; $times = 7200; $sms="Att & Def  +400% / CoL -150 / 2 часа";} // 
if ($_GET[tip]=="4003")	{$att = round(($usr[patt]+$usr[matt])/2*4); $def = round(($usr[pdef]+$usr[mdef])/2*4); $alm = 250; $money = 0; $times = 18000; $sms="Att & Def  +400% / CoL -250 / 5 часов";} // 
	
if ($_GET[tip]=="200kad")	{$att = 60000; $def = 60000; $alm = 300; $money = 0; $times = 86400; $sms="Att & Def  +60`000 / CoL -300 / 24 часа";} // 


$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$nick'");
$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка, такого игрока нет!';
 echo"<br/><div class=silka><a href=\"baf_vip.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

if ($magiya[tip]=="bafhp" && $mag[hp]>=$mag[hpall]){echo "У игрока полно жизней!<br/>";}else{


$req1 = mysql_query("SELECT * FROM `baf` WHERE `usr` = '$nick' LIMIT 1");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя активировать больше одного бафа!</br>';
 echo"<br/><a href=\"baf.php?&nick=$nick\">Назад</a>";
include($path.'inc/down.php');exit;
}


$reqi = mysql_query("SELECT * FROM `users` WHERE `usr`='$nick' LIMIT 1");
$usr = mysql_fetch_array($reqi);

if($udata[almaz]<$alm){echo'<p>Недостаточно Coin of Luck!</p></br>';
 echo"<hr/><a href=\"baf_vip.php?&nick=$nick\">Назад</a>";
include($path.'inc/down.php');exit;}

if($udata[money]<$money){echo'<p>Недостаточно Аден!</p></br>';
 echo"<hr/><a href=\"baf_vip.php?&nick=$nick\">Назад</a>";
include($path.'inc/down.php');exit;}


$time=time()+$times; // бафы на время
if ($time==0){}else{
mysql_query("INSERT INTO
        `baf` SET
        `usr` = '$nick',
        `tip` = '$_GET[tip]',
        `bafat` = '$att',
        `pmdef` = '$def',
        `time` = '$time'");}

$patt=$att+$usr[patt];
$matt=$att+$usr[matt];
$pdef=$def+$usr[pdef];
$mdef=$def+$usr[mdef];



$rezult = mysql_query("UPDATE `users` SET
         `patt` = '$patt',
         `matt` = '$matt',
         `pdef` = '$pdef',
         `mdef` = '$mdef'
          WHERE usr = '$nick'");

	
	$nmon = $udata[money] - $money;
	$nalm = $udata[almaz] - $alm;
		  
mysql_query("UPDATE `users` SET `money`='$nmon',`almaz`='$nalm' WHERE usr = '$log'");
		  

// пишем юзеру сообщение кто его бафнул
if ($nick!=$log){
$time = date("H:i d.m.y");
$text = "
Вам дал  <a href=\"search.php?nick=$log&amp;go=go\">$log</a> БАФ $sms .
";
mysql_query("INSERT INTO `baf_log` SET `usr` = '$nick', `text` = '$text'");
}
		  
echo"Вы использовали БАФ <b> $sms </b>  на $times секунд!<br/> $u[1]";
}}else{echo "<p><font color=red>Ошибка! Такого типа нет бафов $_GET[tip]</font></p>";}

 echo"<br/><div class=silka><a href=\"baf_vip.php?&nick=$nick\">Назад</a>";
break;

}
include($path.'inc/down.php');
?>