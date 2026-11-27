<?php
define('PROTECTOR', 1);

$headmod = 'paty';//фикс. места

$textl='Пати';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

$nick=$_GET['nick'];
$pt=$_GET['pt'];

if($udata['lvl']<21){				
echo "<p>Пати будет доступно с 21 уровня!</p>";
echo "<a href=\"/\">Назад</a><br/>";
include($path.'inc/down.php');
}


switch($_GET['mod']){

default:


// пати покинуть ///////////////////////////////////////////////////////
if ($pt=='kill')
{

$pr = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' or `usr2` = '$log'"); //проверка есть ли игрок в пати
$av=mysql_num_rows($pr);
if ($av>=1){

		$p = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' && `act` = 'no' or `act` = 'yes'");
		$avto=mysql_num_rows($p);
if ($avto>=1){$res = mysql_query("UPDATE `paty` SET `act` = 'kill',`usr` = '***'  WHERE usr = '$log'");}

		$p2 = mysql_query("SELECT * FROM `paty` WHERE `usr2` = '$log' && `act2` = 'no' or `act2` = 'yes'");
		$avto2=mysql_num_rows($p2);
if ($avto2>=1){$res = mysql_query("UPDATE `paty` SET `act2` = 'kill',`usr2` = '***'  WHERE usr2 = '$log'");}


						if ($res == 'true')
					{
					echo '<p>Вы покинули пати!</p>'; // удачно
					}
					else
					{
					echo " Ошибка. Вы не состоите в пати и Вам нет приглашений.";  // неудачно =)
					}
}else{echo "Ошибка. Вы не состоите в пати и Вам нет приглашений.";}
include($path.'inc/down.php');
exit;
}
////////////////////////////////////////////////////////////////////////

// пати согласен ///////////////////////////////////////////////////////
if ($pt=='yes')
{
$pr = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' or `usr2` = '$log'"); //проверка есть ли игрок в пати
$av=mysql_num_rows($pr);
if ($av>=1){

		$p = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log'");
		$avto=mysql_num_rows($p);
if ($avto>=1){$res = mysql_query("UPDATE `paty` SET `act` = 'yes'  WHERE usr = '$log'");}

		$p2 = mysql_query("SELECT * FROM `paty` WHERE `usr2` = '$log'");
		$avto2=mysql_num_rows($p2);
if ($avto2>=1){$res = mysql_query("UPDATE `paty` SET `act2` = 'yes'  WHERE usr2 = '$log'");}


						if ($res == 'true')
					{
					echo '<p>Вы Приняли пати!</p>'; // удачно
					}
					else
					{
					echo " Неудачо. После 5 попыток сообщите Администрации сайта.";  // неудачно =)
					}
}else{echo "Ошибка. Вы не состоите в пати и Вам нет приглашений.";}
include($path.'inc/down.php');
exit;
}
////////////////////////////////////////////////////////////////////////

      $_POST['nick']=$nick;

if(empty($_POST['nick'])){
echo '<form action="paty.php?" method="post">';
echo"Ник:<br/>
<input class='input' type=\"text\" size=\"15\" value=\"$nick\" name=\"nick\"/><br/>";




echo '<input class="button" type="submit" value="Пригласить." /></form>';
}else{

$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$nick'");
$avto=mysql_num_rows($req);
$usr = mysql_fetch_array($req);
if($avto==0){
echo'Ошибка, такого игрока нет!<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
if($usr['lvl']<21){
echo'Игрок не достиг 21 уровня для игры в пати!<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
$raznica=$udata['lvl']-$usr['lvl'];
$raznica1=$usr['lvl']-$udata['lvl'];
if($raznica>90 or $raznica1>90)
{
echo'Разница в уровне не должна превышать 90 лвл<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}


if($usr['dostup']>3)
{
echo'Администрацию запрещено приглашать в пати<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
if($udata['dostup']>3)
{
echo'Администрации запрещено приглашать в пати<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}

		///////////// запрет на пати /////////////
$reqi = mysql_query("SELECT * FROM `options` where `usr`='$nick' and `paty` = 'no' LIMIT 1");
$avto=mysql_num_rows($reqi);
if($avto==1){
echo'Игрок закрыл предложения на пати!<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
	//////////////////////////////////////////////////

$req = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' and `usr2` = '$nick'");
$avto=mysql_num_rows($req);
if($avto>=1){
echo'Вы уже отправили игроку предложение!<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$nick' and `act` != 'kill'");
$avto=mysql_num_rows($req);
if($avto>=1){
echo'Игрок уже в пати!<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `paty` WHERE `usr2` = '$nick' and `act2` != 'kill'");
$avto=mysql_num_rows($req);
if($avto>=1){
echo'Игрок уже в пати!<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
if($log==$nick){
echo'Себе пати кидать нельзя!<br/>';
echo"<br/><a href=\"paty.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}


$res = mysql_query("INSERT INTO
        `paty` SET 
        `usr` = '$log', 
        `act` = 'yes', 
		`time` = '$time', 
        `usr2` = '$nick', 
        `act2` = 'no', 
        `time2` = '$time'");

						if ($res == 'true')
					{
					echo 'Игроку  '.$nik.' отослано предложение пати!'; // удачно
					}
					else
					{
					echo " Неудачо. После 5 попыток сообщите Администрации сайта.";  // неудачно =)
					}
					echo"<br/><br/><a href=\"search.php?&nick=$nick&go=go\">Назад</a>";
}				
					


}
include($path.'inc/down.php');
?>