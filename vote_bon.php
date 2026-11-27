<?php
define('PROTECTOR', 1);

$headmod = 'vote_bon';//фикс. места

$textl='NPC магазин';

$path='';

include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');



$VoteCoin=$_GET['VoteCoin'];
$alm=$_POST['alm'];

switch($_GET['mod']){

default:
echo "<b><font color=#007F46>Добро пожаловать в VoteCoin Магазин!</font></b><br/><hr>";
echo "<b> У Вас <font color=#FF6A00><u>$udata[votecoin]</u> VoteCoin</font> !</b><br/>";
echo "</div><div class=fon><div class=inoy>";


echo"<a href=\"vote_bon.php?mod=vip_akk\">&#187; V.I.P. Аккаунты</a>";
echo"<a href=\"vote_bon.php?mod=VoteCoinor_akk\">&#187; Цветной ник</a>";
echo"<a href=\"vote_bon.php?mod=4vote\">&#187; Cбросить очки параметров  за 5 VoteCoin</a>";
echo"<a href=\"shopsoul.php\">&#187; Магазин Soul and Spirit Shots</a>";
echo"<a href=\"shopelexirs.php\">&#187; Магазин Эликсиров</a>";
echo "</div></div><div class=menu>";
break;



case "VoteCoinor_akk":


if ($_GET['d']=='del' && $_GET['y']!=='yes'){// удаление после подтверждения
echo "<p><font color=green>Вы уверены что хотите удалить цветной аккаунт?</font><br/>
<a href=\"vote_bon.php?mod=VoteCoinor_akk&d=del&y=yes\"><font color=red>Да</font></a> |
<a href=\"vote_bon.php?mod=VoteCoinor_akk\"><font color=grey>Нет</font></a>
</p><hr/>";
}


if ($_GET['d']=='del' && $_GET['y']=='yes'){// удаление после подтверждения
$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
$resvip = mysql_query("DELETE FROM `color_akk` WHERE `usr` = '$log' LIMIT 1");
					if ($resvip == 'true')
					{
					echo "<p><font color=red>Цветной аккаунт аннулирован!</font></p><hr/>";
					}
					}
			}		






if (!empty($_POST['vip'])) // если есть данные то пишем их
{

$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){echo'<font color=red><p>У Вас уже есть Цветной ник!</p></font>';}else{


		if ($_POST['vip'] == 'EE3B3B' or $_POST['vip'] == '595E21')	{$cena = 70;}else {$cena = 40;}
		
$nvotecoin = $udata['votecoin']-$cena;		

if($nvotecoin<0){
echo'<font color=red><p>Недостаточно VoteCoin!</p></font>';
}else{


$times=time()+2592000;
mysql_query("INSERT INTO
        `color_akk` SET
        `usr` = '$log',
        `color` = '$_POST[vip]',
        `time` = '$times'");

mysql_query("UPDATE `users` SET
         `votecoin` = '$nvotecoin'
          WHERE usr = '$log'");
		
		
echo'<font color=#FF6A00><b><p>Цветной ник активирован!</p></b></font>';

}
}
}

echo "<p><font color=#007F46>Добро пожаловать в мою хижину, Герой! 
У тебя есть возможность выделятся среди других игроков, заказав себе цветной ник за Coin of Luck...</font></p>";

$req22 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$log' LIMIT 1");
$avto=mysql_num_rows($req22);
if($avto==1){
$vip = mysql_fetch_array($req22);
$del = "<a href=\"vote_bon.php?mod=VoteCoinor_akk&d=del\"><font color=red>[x]</font></a>";
echo "<hr/><p> <font color=#008282>Молодец! <b>Теперь твой ник такого цвета - <font color=#$vip[color]>$log</font>!</b>$del</p>";
$vip['time']=$vip['time']-time();

if($vip['time']<60)
{
if ($vip['time']<=0){$vip['time']=0;}
echo "Осталось: $vip[time] сек.";}

if ($vip['time']>60 && $vip['time']<86400){$vip['time']=round($vip['time']/3600);
echo "Осталось: $vip[time] мин.";}

if ($vip['time']>=86400){$vip['time']=round($vip['time']/86400);
echo "Осталось: $vip[time] дн.";}
echo "</font><hr/>";
}else{

echo "<hr/><b><font color=grey>Список доступных цветов: (Ник/код цвета)</font><br/>";
echo "<font color=#A020F0>$log - A020F0</font><br/>";
echo "<font color=#FF006E>$log - FF006E</font><br/>";
echo "<font color=#00FF90>$log - 00FF90</font><br/>";
echo "<font color=#EE3B3B>$log - EE3B3B</font> (VIP цвет)<br/>";
echo "<font color=#595E21>$log - 595E21</font> (VIP цвет)<br/>";

echo '<form action="vote_bon.php?mod=VoteCoinor_akk" method="post">';
echo "<hr/><font color=grey>Цвет / Стоимость:</b></font><br/>
<select name=\"vip\">

<option value=\"A020F0\">A020F0 / 40 VoteCoin</option>
<option value=\"FF006E\">FF006E / 40 VoteCoin</option>
<option value=\"00FF90\">00FF90 / 40 VoteCoin</option>
<option value=\"EE3B3B\">EE3B3B / 70 VoteCoin</option>
<option value=\"595E21\">595E21 / 70 VoteCoin</option>

</select>";
echo '<br/><input class="button" type="submit" value="Заказать" /></form>';

echo "<hr/>";
echo "<font color=#C64F00>* Цветные ники покупаются на срок в 30 дней.</font>";
echo "<hr/>";}

echo"<div class=inoy><a href=\"vote_bon.php?\">Назад</a></div>";
break;
/////// VIP акаунты /////
case "vip_akk":

if ($_GET['d']=='del' && $_GET['y']!=='yes'){// удаление после подтверждения
echo "<p><font color=green>Вы уверены что хотите удалить VIP аккаунт?</font><br/>
<a href=\"vote_bon.php?mod=vip_akk&d=del&y=yes\"><font color=red>Да</font></a> |
<a href=\"vote_bon.php?mod=vip_akk\"><font color=grey>Нет</font></a>
</p><hr/>";
}


if ($_GET['d']=='del' && $_GET['y']=='yes'){// удаление после подтверждения
$req222 = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
$resvip = mysql_query("DELETE FROM `vip` WHERE `usr` = '$log' LIMIT 1");
					if ($resvip == 'true')
					{
					echo "<p><font color=red>VIP аккаунт аннулирован!</font></p><hr/>";
					}
					}
			}		

if (!empty($_POST['vip'])) // если есть данные то пишем их
{


$req222 = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
echo'<font color=red><p>У Вас уже есть VIP Акаунт!</p></font>';
 echo"<br/><a href=\"vote_bon.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}




		if ($_POST['vip'] == 2)	{$cena = 5; $yesakk = 'yesgame';}
		if ($_POST['vip'] == 5)	{$cena = 15; $yesakk = 'yesgame';}
		if ($_POST['vip'] == 10)	{$cena = 20; $yesakk = 'yesgame';}
		if ($_POST['vip'] == 25)	{$cena = 30; $yesakk = 'yesgame';}
		if ($_POST['vip'] == 50)	{$cena = 45; $yesakk = 'yesgame';}
		
				//мини защита желательно переписать
		if ($yesakk !== 'yesgame'){
echo'<font color=red><p>Ошибка! Вернитесь на главную...</p></font>';
 echo"<br/><a href=\"vote_bon.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}

		
		
$nvotecoin = $udata['votecoin']-$cena;		

if($nvotecoin<0){
echo'<font color=red><p>Недостаточно VoteCoin!</p></font>';
}else{


$times=time()+2592000;
mysql_query("INSERT INTO
        `vip` SET
        `usr` = '$log',
        `tip` = '$_POST[vip]',
        `time` = '$times'");

mysql_query("UPDATE `users` SET
         `votecoin` = '$nvotecoin'
          WHERE usr = '$log'");
		
		
echo'<font color=#FF6A00><b><p>VIP Акаунт активирован!</p></b></font>';

}
}


echo "<p><font color=#007F46>Добро пожаловать в мою хижину, Герой! 
Я много странствовал и в итоге узнал несколько важных вещей, которые помогают получать больше опыта с убитых монстров. Что бы доказать право на знание этого секрета, принеси мне указанное количество VoteCoin...</font></p>";

$req22 = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1");
$avto=mysql_num_rows($req22);
if($avto==1){
$vip = mysql_fetch_array($req22);
$del = "<a href=\"vote_bon.php?mod=vip_akk&d=del\"><font color=red>[x]</font></a>";
echo'<hr/><p> <font color=#008282> Поздравляю! <b>Твой VIP Акаунт x'.$vip['tip'].'!</b> '.$del.'</p>';
$vip['time']=$vip['time']-time();

if($vip['time']<60)
{
if ($vip['time']<=0){$vip['time']=0;}
echo "Осталось: $vip[time] сек.";}

if ($vip['time']>60 && $vip['time']<86400){$vip['time']=round($vip['time']/3600);
echo "Осталось: $vip[time] мин.";}

if ($vip['time']>=86400){$vip['time']=round($vip['time']/86400);
echo "Осталось: $vip[time] дн.";}
echo "</font><hr/>";
}else{

echo '<form action="vote_bon.php?mod=vip_akk" method="post">';
echo "<hr/><font color=grey>Знание / Стоимость:</font><br/>
<select name=\"vip\">
<option value=\"2\">Опыт х2 / 5 VoteCoin</option>
<option value=\"5\">Опыт х5 /15 VoteCoin</option>
<option value=\"10\">Опыт х10 / 20 VoteCoin </option>
<option value=\"25\">Опыт х25 / 30 VoteCoin</option>
<option value=\"50\">Опыт х50 / 45 VoteCoin</option>
</select>";
echo '<br/><input class="button" type="submit" value="Заказать" /></form>';

echo "<hr/>";
echo "<font color=#C64F00>* VIP Акаунты покупаются на срок в 30 дней.</font>";
echo "<hr/>";}

echo"<div class=inoy><a href=\"vote_bon.php?\">Назад</a></div>";
break;

case'4vote':
echo'<b>Сброс параметров</b><br/>';
if($udata['votecoin']<5){
echo'У Вас недостаточно VoteCoin';
include($path.'inc/down.php');
exit;
}
$req1 = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image` = 'yes' ");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры с одетыми вещами!</br>';
include($path.'inc/down.php');exit;
}


if($udata['storona']=="human"){$all=50;}
if($udata['storona']=="gnom"){$all=100;}
if($udata['storona']=="elf"){$all=60;}
if($udata['storona']=="darkelf"){$all=75;}
if($udata['storona']=="ork"){$all=100;}
if($udata['storona']=="kamael"){$all=100;}
if($udata['klas']=="wizard"){
$hplvl=100*$udata['lvl'];
$mp = 100 + $all + $hplvl; $hp = 100 + $all+$hplvl;
$patt = 23; 
$matt = 56;
$pdef = 63;
$mdef = 52;
}
if($udata['klas']=="fighert"){
$hplvl=100*$udata['lvl'];
$mp=80 + $all + $hplvl; $hp=120 + $all + $hplvl;
$patt = 55;
$matt = 21;
$pdef = 68;
$mdef = 49;
}
mysql_query("UPDATE `users` SET `money` = `votecoin` - 5 WHERE `usr` = '$log' ");
mysql_query("UPDATE `users` SET `hp`='$hp',`hpall`=$hp,`mp`='$mp',`mpall`='$mp',`patt` = '$patt', `matt` = '$matt', `pdef` = '$pdef', `mdef` = '$mdef' WHERE `usr` = '$log' "); 
$skill = ($udata['lvl']*3)+($udata['res']*303);
mysql_query("UPDATE `users` SET `skill` ='$skill' WHERE `usr` = '$log' ");
echo'Очки параметров успешно сброшены!';
break;



////////////////////////////

}
include($path.'inc/down.php');
?>