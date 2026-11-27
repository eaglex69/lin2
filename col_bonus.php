<?
define('PROTECTOR', 1);

$headmod = 'col_bonus';//фикс. места

$textl='NPC магазин';

$path='';

include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
/*
if ($log != 'Ultimate'){
echo'Закрыто на доработки! Скоро будет работать.<br/>';
echo"<br/><a href=\"gorod.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}*/

$col=$_GET[col];
$alm=$_POST[alm];

switch($_GET[mod]){

default:
echo "<b><font color=#f4d06e>Добро пожаловать в NPC Магазин!</font></b><br/><hr>";
echo "<b><font color=#f4d06e> У Вас <font color=#ffffcc><u>$udata[almaz]</u><font color=#f4d06e> Coin of Luck</font> !</b><br/>";
echo "</div><div class=fon><div class=inoy>";
echo"<a href=\"col_bonus.php?mod=res\"><font color=#ff4040>» Ресеты</font></a> у вас $udata[res] ресетов";
echo"<a href=\"col_bonus.php?mod=vip_akk\">» V.I.P. Акаунты</a>";
echo"<a href=\"shoppit.php?mod\">» Магазин вещей для питомца</a>"; 
echo"<a href=\"anti_pk.php\">» Анти-ПК акаунт</a>";
echo"<a href=\"col_bonus.php?mod=color_akk\">» Цветной ник</a>";
echo"<a href=\"obm_fs_ad.php?\">» Обмен Festival Aden на CoL</a>";
echo"<a href=\"col_bonus.php?mod=0\">» Заточка оружия</a>"; 
echo"<a href=\"vip_scroll.php?mod=0\">» Заточка вещей</a>";
echo"<a href=\"col_bonus.php?mod=dop\">» Дополнительные возможности</a>"; 
echo '<hr>';
echo"</div>";

echo "</div></div><div class=menu>";
break;

/*
/////// VIP акаунты /////
case "im_sh";
echo "<p><b><span style=color:#40AE7E;>Именная вещь:</span></b></p><hr/>";
echo "<span style=color:#6FAACF;>
У Вас есть возможность заказать именную вещь. 
Цена данной услуги <b>700 CoL</b> или <b>400 WMR</b> (любой способ). 
При заказе меняеться название вещи и по желанию её изображение.
</span><br/>
<br/>
<span style=color:#B0813B;>
» Для заказа именной вещи нужно писать в <a href=\"/tiket.php?\"><span style=color:grey;>»Поддержку«</span></a>.<br/>
» В пункте <b>»Тема сообщения«</b> указать <u><b>Именная вещь</b></u>. <br/>
» В пункте <u><b>Введите сообщение</b></u> указать старое и новое название вещи (адрес новой картинки размером 32 х 32 px). 
Для точности указать её параметры и тип.<hr/> 
<span style=color:#597071;>
<b>Например:</b><br/>
<b>Blade of Serenity</b> Оружие (тут параметры) переименовать на <b>Moe Krutoe Nazvanie</b>
</span><hr/>
» Название не должно превышать 11 символов включая пробел. <br/><br/>

<span style=color:#F52D00;>
» Перед заказом убедитесь что у Вас есть данная сума на счету. <br/>
» Заказуйте именные вещи и проще в случае кражи доказать/найти вашу вещь <br/>
» <b>В А Ж Н О ! </b> Переименовать вещь можно только один раз.<br/><br/>
</span>";

echo "<div class=inoy><a href=\"/tiket.php?\"> --> Оформить заказ </a>";
echo "<a href=\"/col_bonus.php?\"> <-- NPC Магазин </a></div>";
break;

*/
/////// COLOR акаунты /////
case "color_akk";


if ($_GET[d]==del && $_GET[y]!==yes){// удаление после подтверждения
echo "<p><font color=#f4d06e>Вы уверены что хотите удалить цветной аккаунт?</font><br/>
<a href=\"col_bonus.php?mod=color_akk&d=del&y=yes\"><font color=#ff4040>Да</font></a> |
<a href=\"col_bonus.php?mod=color_akk\"><font color=#f4d06e>Нет</font></a>
</p><hr/>";
}


if ($_GET[d]==del && $_GET[y]==yes){// удаление после подтверждения
$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
$resvip = mysql_query("DELETE FROM `color_akk` WHERE `usr` = '$log' LIMIT 1");
					if ($resvip == 'true')
					{
					echo "<p><font color=#ff4040>Цветной аккаунт аннулирован!</font></p><hr/>";
					}
					}
			}		






if (!empty($_POST[vip])) // если есть данные то пишем их
{

$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){echo'<font color=#ff4040><p>У Вас уже есть Цветной ник!</p></font>';}else{


		if ($_POST[vip] == '00FFFF' or $_POST[vip] == 'FF83FA')	{$cena = 300;}else {$cena = 300;}
		
$nalmaz = $udata[almaz]-$cena;		

if($nalmaz<0){
echo'<font color=#ff4040><p>Недостаточно Coin of Luck!</p></font>';
}else{


$times=time()+2592000;
mysql_query("INSERT INTO
        `color_akk` SET
        `usr` = '$log',
        `color` = '$_POST[vip]',
        `time` = '$times'");

mysql_query("UPDATE `users` SET
         `almaz` = '$nalmaz'
          WHERE usr = '$log'");
		
		
echo'<font color=#f4d06e><b><p>Цветной ник активирован!</p></b></font>';

}
}
}

echo "<p><font color=#f4d06e>Добро пожаловать в мою хижину, Герой! 
<font color=#ffffcc>У тебя есть возможность выделятся среди других игроков, заказав себе цветной ник за Coin of Luck...</font></p>";

$req22 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$log' LIMIT 1");
$avto=mysql_num_rows($req22);
if($avto==1){
$vip = mysql_fetch_array($req22);
$del = "<a href=\"col_bonus.php?mod=color_akk&d=del\"><font color=#ff4040>[x]</font></a>";
echo "<hr/><p> <font color=#f4d06e>Молодец! <b>Теперь твой ник такого цвета - <font color=#$vip[color]>$log</font>!</b>$del</p>";
$vip[time]=$vip[time]-time();

if($vip[time]<60)
{
if ($vip[time]<=0){$vip[time]=0;}
echo "Осталось: $vip[time] сек.";}

if ($vip[time]>60 && $vip[time]<86400){$vip[time]=round($vip[time]/3600);
echo "Осталось: $vip[time] мин.";}

if ($vip[time]>=86400){$vip[time]=round($vip[time]/86400);
echo "Осталось: $vip[time] дн.";}
echo "</font><hr/>";
}else{

echo "<hr/><b><font color=#f4d06e>Список доступных цветов: (Ник/код цвета)</font><br/>";
echo "<font color=#ffa500>$log - FFA500</font><br/>";
echo "<font color=#FF4040>$log - FF4040</font><br/>";
echo "<font color=#00FF90>$log - 00FF90</font><br/>";
echo "<font color=#00ffff>$log - 00FFFF</font> (VIP цвет)<br/>";
echo "<font color=#ff83fa>$log - FF83FA</font> (VIP цвет)<br/>";

echo '<form action="col_bonus.php?mod=color_akk" method="post">';
echo "<hr/><font color=#f4d06e>Цвет / Стоимость:</b></font><br/>
<select name=\"vip\">
<option value=\"FFA500\">FFA500 / 300 CoL</option>
<option value=\"FF4040\">FF4040 / 300 CoL </option>
<option value=\"00FF90\">00FF90 / 300 CoL</option>
<option value=\"00FFFF\">00FFFF / 300 CoL</option>
<option value=\"FF83FA\">FF83FA / 300 CoL</option>
</select>";
echo '<br/><input class="button" type="submit" value="Заказать" /></form>';

echo "<hr/>";
echo "<font color=#ffffcc> Цветные ники покупаются на срок в 30 дней.</font>";
echo "<hr/>";}

echo"<div class=inoy><a href=\"col_bonus.php?\">Назад</a></div>";
break;






/////// VIP акаунты /////
case "vip_akk";

if ($_GET[d]==del && $_GET[y]!==yes){// удаление после подтверждения
echo "<p><font color=#f4d06e>Вы уверены что хотите удалить VIP акаунт?</font><br/>
<a href=\"col_bonus.php?mod=vip_akk&d=del&y=yes\"><font color=#ff4040>Да</font></a> |
<a href=\"col_bonus.php?mod=vip_akk\"><font color=#f4d06e>Нет</font></a>
</p><hr/>";
}


if ($_GET[d]==del && $_GET[y]==yes){// удаление после подтверждения
$req222 = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
$resvip = mysql_query("DELETE FROM `vip` WHERE `usr` = '$log' LIMIT 1");
					if ($resvip == 'true')
					{
					echo "<p><font color=#ff4040>VIP акаунт аннулирован!</font></p><hr/>";
					}
					}
			}		

if (!empty($_POST[vip])) // если есть данные то пишем их
{


$req222 = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
echo'<font color=#ff4040><p>У Вас уже есть VIP Акаунт!</p></font>';
 echo"<br/><a href=\"col_bonus.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}




		if ($_POST[vip] == 2)	{$cena = 10; $yesakk = 'yesgame';}
		if ($_POST[vip] == 5)	{$cena = 50; $yesakk = 'yesgame';}
		if ($_POST[vip] == 10)	{$cena = 100; $yesakk = 'yesgame';}
		if ($_POST[vip] == 25)	{$cena = 200; $yesakk = 'yesgame';}
		if ($_POST[vip] == 50)	{$cena = 300; $yesakk = 'yesgame';}
              		
				//мини защита желательно переписать
		if ($yesakk !== 'yesgame'){
echo'<font color=red><p>Ошибка! Вернитесь на главную...</p></font>';
 echo"<br/><a href=\"col_bonus.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}

		
		
$nalmaz = $udata[almaz]-$cena;		

if($nalmaz<0){
echo'<font color=red><p>Недостаточно Coin of Luck!</p></font>';
}else{


$times=time()+259200;
mysql_query("INSERT INTO
        `vip` SET
        `usr` = '$log',
        `tip` = '$_POST[vip]',
        `time` = '$times'");

mysql_query("UPDATE `users` SET
         `almaz` = '$nalmaz'
          WHERE usr = '$log'");
		
		
echo'<font color=#f4d06e><b><p>VIP Акаунт активирован!</p></b></font>';

}
}


echo "<p><font color=#f4d06e>Добро пожаловать в мою хижину, Герой! 
Я много странствовал и в итоге узнал несколько важных вещей, которые помогают получать больше опыта с убитых монстров. Что бы доказать право на знание этого секрета, принеси мне указанное количество Coin of Luck...</font></p>";

$req22 = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1");
$avto=mysql_num_rows($req22);
if($avto==1){
$vip = mysql_fetch_array($req22);
$del = "<a href=\"col_bonus.php?mod=vip_akk&d=del\"><font color=#ff4040>[x]</font></a>";
echo'<hr/><p> <font color=#f4d06e> Поздравляю! <b>Твой VIP Акаунт x'.$vip[tip].'!</b> '.$del.'</p>';
$vip[time]=$vip[time]-time();

if($vip[time]<60)
{
if ($vip[time]<=0){$vip[time]=0;}
echo "Осталось: $vip[time] сек.";}

if ($vip[time]>60 && $vip[time]<86400){$vip[time]=round($vip[time]/3600);
echo "Осталось: $vip[time] мин.";}

if ($vip[time]>=86400){$vip[time]=round($vip[time]/86400);
echo "Осталось: $vip[time] дн.";}
echo "</font><hr/>";
}else{

echo '<form action="col_bonus.php?mod=vip_akk" method="post">';
echo "<hr/><font color=#f4d06e>Знание / Стоимость:</font><br/>
<select name=\"vip\">
<option value=\"2\">Опыт х2 / 10 CoL</option>
<option value=\"5\">Опыт х5 /  50 CoL</option>
<option value=\"10\">Опыт х10 / 100 CoL </option>
<option value=\"25\">Опыт х25 / 200 CoL</option>
<option value=\"50\">Опыт х50 / 300 CoL</option>
</select>";
echo '<br/><input class="button" type="submit" value="Заказать" /></form>';

echo "<hr/>";
echo "<font color=#ffffcc> VIP Акаунты покупаются на срок в 3 дня.</font>";
echo "<hr/>";}

echo"<div class=inoy><a href=\"col_bonus.php?\">Назад</a></div>";
break;




//  ////     ////// обменик Колов на опыт /////////
case "2";
if (empty($_GET[yes])) // если не подтвердил
{
echo "Обмен Coin of Luck на Опыт.<br/> За 1 CoL я дам тебе 200`000 EXP.<br/>";
echo "<br/>Обменять:<br/>";
echo"<a href=\"col_bonus.php?mod=2&yes=yes&col=1\">1 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=2&yes=yes&col=2\">2 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=2&yes=yes&col=5\">5 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=2&yes=yes&col=10\">10 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=2&yes=yes&col=20\">20 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=2&yes=yes&col=50\">50 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=2&yes=yes&col=100\">100 Coin of Luck </a> <br/>";

echo"<br/><a href=\"col_bonus.php?\">Назад</a>";

}else // подтвердил
{

if ($udata[almaz]<$col or $col<=0){
echo "Недостаточно Coin of Luck";
echo "<div class=silka><a href=\"col_bonus.php?\">» Назад</a></div>";}else{

$nalm=$udata[almaz]-$col;// минус колы
$mon=200000*$col; // щитаем сколько плюсуем
$exp=$udata[exp]+$mon; // плюсуем адену
	mysql_query("UPDATE users SET almaz='$nalm',exp='$exp' WHERE usr = '$log'"); // записуем результат


echo "Вы обменяли $col CoL на $mon EXP.<br/>";
echo"<div class=silka><a href=\"col_bonus.php?\">» Назад</a></div>";
}
}

break;


//  ////     ////// обменик Колов на адену /////////
case "1";
if (empty($_GET[yes])) // если не подтвердил
{
echo "Обмен Coin of Luck на Адену.<br/> За 1 CoL я дам тебе 150`000 Аден.<br/>";
echo "<br/>Обменять:<br/>";
echo"<a href=\"col_bonus.php?mod=1&yes=yes&col=1\">1 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=1&yes=yes&col=2\">2 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=1&yes=yes&col=5\">5 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=1&yes=yes&col=10\">10 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=1&yes=yes&col=20\">20 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=1&yes=yes&col=50\">50 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=1&yes=yes&col=100\">100 Coin of Luck </a> <br/>";

echo"<br/><a href=\"col_bonus.php?\">Назад</a>";

}else // подтвердил
{

if ($udata[almaz]<$col or $col<=0){
echo "Недостаточно Coin of Luck";
echo "<div class=silka><a href=\"col_bonus.php?\">» Назад</a></div>";}else{

$nalm=$udata[almaz]-$col;// минус колы
$mon=150000*$col; // щитаем сколько плюсуем
$money=$udata[money]+$mon; // плюсуем адену
	mysql_query("UPDATE users SET almaz='$nalm',money='$money' WHERE usr = '$log'"); // записуем результат


echo "Вы обменяли $col CoL на $mon Аден.<br/>";
echo"<div class=silka><a href=\"col_bonus.php?\">» Назад</a></div>";
}
}

break;


//  ////     ////// обменик Колов на Gold Bar /////////
case "col_bar";
if (empty($_GET[yes])) // если не подтвердил
{
echo "Обмен Coin of Luck на Адену.<br/> За 1 CoL я дам тебе 200`000 Gold Bar.<br/>";
echo "<br/>Обменять:<br/>";
echo"<a href=\"col_bonus.php?mod=col_bar&yes=yes&col=1\">1 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=col_bar&yes=yes&col=2\">2 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=col_bar&yes=yes&col=5\">5 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=col_bar&yes=yes&col=10\">10 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=col_bar&yes=yes&col=20\">20 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=col_bar&yes=yes&col=50\">50 Coin of Luck </a> <br/>";
echo"<a href=\"col_bonus.php?mod=col_bar&yes=yes&col=100\">100 Coin of Luck </a> <br/>";

echo"<br/><a href=\"col_bonus.php?\">Назад</a>";

}else // подтвердил
{

if ($udata[almaz]<$col or $col<=0){
echo "Недостаточно Coin of Luck";
echo "<div class=silka><a href=\"col_bonus.php?\">» Назад</a></div>";}else{

$nalm=$udata[almaz]-$col;// минус колы
$mon=200000*$col; // щитаем сколько плюсуем
$gold=$udata[gold]+$mon; // плюсуем адену
	mysql_query("UPDATE users SET almaz='$nalm',gold='$gold' WHERE usr = '$log'"); // записуем результат


echo "Вы обменяли $col CoL на $mon Gold Bar.<br/>";
echo"<div class=silka><a href=\"col_bonus.php?\">» Назад</a></div>";
}
}

break;

//////////// заточка оружия ////////////////////////
case '0':
echo "</div></div></div><div class=menu>";
echo"<font color=#f4d06e><b>Заточка оружия!<font color=#ffffcc> 1 Coin of Luck дает +4 к одному из параметров.</b><br/>";
//echo"1 Coin of Luck заточки даёт +2 к P.Att и M.Att или +4 k одному из параметров атаки!<br/>";
echo"<u> У Вас $udata[almaz] Coin of Luck. </u></font><hr/>";

echo "<form action=\"col_bonus.php?mod=scl_weapon\" method=\"POST\">";

echo " <label><input type='radio' name='result' value='patt' /></label>\n Точить  P.Att +4 (Физ)<hr/>";
echo " <label><input type='radio' name='result' value='matt' /></label>\n Точить  M.Att +4 (Маг)<hr/>";
//echo " <label><input type='radio' name='result' value='all' /></label>\n Точить P.Att и M.Att (+2 k P.Att и M.Att) <hr/>";


echo "<div class=menu><b><font color=#f4d06e>Введите количество:</font></b><br/>
<input type=\"text\" name=\"alm\" size=\"5\" maxlength=\"9\"/> ";

echo "<input type='submit' name='ok' value='Точить' /><hr/></div>\n";

echo"<br/><a href=\"col_bonus.php?\">Назад</a>";
break;
//***--------------------------------------------------------------------------------------
case 'scl_weapon':
if($alm<='0'){echo'На 0 Coin of Luck точить нельзя!';echo"<br/><a href=\"col_bonus.php?\">Назад</a>";include($path.'inc/down.php');exit;}
if($udata[almaz]-$alm<0){echo'Недостаточно Coin of Luck!';echo"<br/><a href=\"col_bonus.php?\">Назад</a>";include($path.'inc/down.php');exit;}
$almaznew=$udata[almaz]-$alm;

if (empty($_POST[result])){
echo "Вы не выбрали тип заточки!";include($path.'inc/down.php');exit;}


$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto=='0'){
echo'<p><b><font color=#f4d06e>Точится можно только с одетым оружием!</font></b></p>';
echo"<div class=silka><a href=\"col_bonus.php?\">Назад</a></div>";
include($path.'inc/down.php');
exit;
}



//////////////////////////////////////////////////////////////////////	
if ($_POST[result]=="patt"){ // если точить только физ атаку
$it = mysql_fetch_array($req);
$toch=$alm*4;
$u = explode("|",$mag[give]);
$npatt = $toch + $udata[patt];
$nmatt = $udata[matt];
$w=explode("*",$it[name]);
if(empty($w[1])){
$nam="$w[0]*$alm";
}else{
$kol=$w[1]+$alm;
$nam="$w[0]*$kol";}

$patt = $toch + $it[patt];
$matt = $it[matt];}
///////////////////////////////////////////////////////////////////////////
if ($_POST[result]=="matt"){ // если точить только маг атаку
$it = mysql_fetch_array($req);
$toch=$alm*4;
$u = explode("|",$mag[give]);
$npatt =  $udata[patt];
$nmatt = $toch + $udata[matt];
$w=explode("*",$it[name]);
if(empty($w[1])){
$nam="$w[0]*$alm";
}else{
$kol=$w[1]+$alm;
$nam="$w[0]*$kol";}

$patt = $it[patt];
$matt = $toch + $it[matt];}
/////////////////////////////////////////////////////////////////////////
if ($_POST[result]=="all"){ // если точить физ и маг атаку
$toch=$alm*2;
$u = explode("|",$mag[give]);
$npatt=$toch+$udata[patt];
$nmatt=$toch+$udata[matt];
$w=explode("*",$it[name]);
if(empty($w[1])){
$nam="$w[0]*$alm";
}else{
$kol=$w[1]+$alm;
$nam="$w[0]*$kol";}

$patt=$toch+$it[patt];
$matt=$toch+$it[matt];}
/////////////////////////////////////////////////////////////////////////

mysql_query("UPDATE `users` SET 
`patt` = '$npatt',
`almaz` = '$almaznew',
`matt` = '$nmatt'  
WHERE usr = '$log'");
mysql_query("UPDATE `item` SET
      `patt` = '$patt',
      `matt` = '$matt',
      `name` = '$nam'
       WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
       
$msg="<b>Оружие заточено!</b><br/>Новый урон: P.Att - $patt(+$toch) / M.Att - $matt(+$toch)<br/>";

echo "$msg";
echo"<br/><a href=\"col_bonus.php?\">Назад</a>";
break;




///////////////////////////////////////
case'4';

$req1 = mysql_query("SELECT * FROM `baf` WHERE `usr` = '$log' LIMIT 1");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры под бафом!</br>';
include($path.'inc/down.php');exit;
}
$req1 = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image` = 'yes'");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры с одетыми вещами!</br>';
include($path.'inc/down.php');exit;
}
echo'<b>Сброс параметров</b><br/>';
echo'<b>ПЕРЕД СБРОСОМ ПАРАМЕТРОВ СНИМИТЕ ВСЕ ВЕЩИ</b><br/>';
echo'Стоимость услуги <b>25</b> CoL или <b>250кк</b> Аден<br/>';
echo'<a href="col_bonus.php?mod=4col">Сбросить за <b>25 CoL</b></a><br/>';
echo'<a href="col_bonus.php?mod=4aden">Сбросить за <b>2 500 000 000 Аден</b></a><br/>';
break;

case'4col';

echo'<b>Сброс параметров</b><br/>';
$req1 = mysql_query("SELECT * FROM `baf` WHERE `usr` = '$log' LIMIT 1");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры под бафом!</br>';
include($path.'inc/down.php');exit;
}
if($udata[almaz]<25){
echo'У Вас недостаточно СоL';
include($path.'inc/down.php');
exit;
}
$req1 = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image` = 'yes'");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры с одетыми вещами!</br>';
include($path.'inc/down.php');exit;
}
if($udata[storona]=="human"){$all=50;}
if($udata[storona]=="gnom"){$all=100;}
if($udata[storona]=="elf"){$all=60;}
if($udata[storona]=="darkelf"){$all=75;}
if($udata[storona]=="ork"){$all=100;}
if($udata[storona]=="kamael"){$all=100;}
if($udata[klas]=="wizard"){
$hplvl=(100*$udata[lvl]);
$mp = 100 + $all + $hplvl; $hp = 100 + $all+$hplvl;
$patt = 23; 
$matt = 56;
$pdef = 63;
$mdef = 52;
}
if($udata[klas]=="fighert"){
$hplvl=100*$udata[lvl];
$mp=80 + $all + $hplvl; $hp=120 + $all + $hplvl;
$patt = 55;
$matt = 21;
$pdef = 68;
$mdef = 49;
}
mysql_query("UPDATE `users` SET `almaz` = `almaz` - 25 WHERE `usr` = '$log' ");
mysql_query("UPDATE `users` SET `hp`='$hp',`hpall`=$hp,`mp`='$mp',`mpall`='$mp',`patt` = '$patt', `matt` = '$matt', `pdef` = '$pdef', `mdef` = '$mdef' WHERE `usr` = '$log' "); 
$skill = ($udata[lvl]*3)+($udata[res]*603);
mysql_query("UPDATE `users` SET `skill` = '$skill' WHERE `usr` = '$log' ");
echo'Очки параметров успешно сброшены!';
break;

case'4aden';

$req1 = mysql_query("SELECT * FROM `baf` WHERE `usr` = '$log' LIMIT 1");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры под бафом!</br>';
include($path.'inc/down.php');exit;
}
echo'<b>Сброс параметров</b><br/>';
if($udata[money]<2500000000){
echo'У Вас недостаточно аден';
include($path.'inc/down.php');
exit;
}
$req1 = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image` = 'yes'");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры с одетыми вещами!</br>';
include($path.'inc/down.php');exit;
}
if($udata[storona]=="human"){$all=50;}
if($udata[storona]=="gnom"){$all=100;}
if($udata[storona]=="elf"){$all=60;}
if($udata[storona]=="darkelf"){$all=75;}
if($udata[storona]=="ork"){$all=100;}
if($udata[storona]=="kamael"){$all=100;}
if($udata[klas]=="wizard"){
$hplvl=100*$udata[lvl];
$mp = 100 + $all + $hplvl; $hp = 100 + $all+$hplvl;
$patt = 23; 
$matt = 56;
$pdef = 63;
$mdef = 52;
}
if($udata[klas]=="fighert"){
$hplvl=100*$udata[lvl];
$mp=80 + $all + $hplvl; $hp=120 + $all + $hplvl;
$patt = 55;
$matt = 21;
$pdef = 68;
$mdef = 49;
}
mysql_query("UPDATE `users` SET `money` = `money` - 2500000000 WHERE `usr` = '$log' ");
mysql_query("UPDATE `users` SET `hp`='$hp',`hpall`=$hp,`mp`='$mp',`mpall`='$mp',`patt` = '$patt', `matt` = '$matt', `pdef` = '$pdef', `mdef` = '$mdef' WHERE `usr` = '$log' "); 
$skill = ($udata[lvl]*3)+($udata[res]*603);
mysql_query("UPDATE `users` SET `skill` ='$skill' WHERE `usr` = '$log' ");
echo'Очки параметров успешно сброшены!';
break;

case'4vote';

echo'<b>Сброс параметров</b><br/>';
$req1 = mysql_query("SELECT * FROM `baf` WHERE `usr` = '$log' LIMIT 1");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры под бафом!</br>';
include($path.'inc/down.php');exit;
}
if($udata[votecoin]<5){
echo'У Вас недостаточно VoteCoin';
include($path.'inc/down.php');
exit;
}
$req1 = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image` = 'yes'");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя cбрасывать параметры с одетыми вещами!</br>';
include($path.'inc/down.php');exit;
}
if($udata[storona]=="human"){$all=50;}
if($udata[storona]=="gnom"){$all=100;}
if($udata[storona]=="elf"){$all=60;}
if($udata[storona]=="darkelf"){$all=75;}
if($udata[storona]=="ork"){$all=100;}
if($udata[storona]=="kamael"){$all=100;}
if($udata[klas]=="wizard"){
$hplvl=100*$udata[lvl];
$mp = 100 + $all + $hplvl; $hp = 100 + $all+$hplvl;
$patt = 23; 
$matt = 56;
$pdef = 63;
$mdef = 52;
}
if($udata[klas]=="fighert"){

$hplvl=100*$udata[lvl];
$mp=80 + $all + $hplvl; $hp=120 + $all + $hplvl;
$patt = 55;
$matt = 21;
$pdef = 68;
$mdef = 49;
}
mysql_query("UPDATE `users` SET `votecoin` = `votecoin` - 5 WHERE `usr` = '$log' ");
mysql_query("UPDATE `users` SET `hp`='$hp',`hpall`=$hp,`mp`='$mp',`mpall`='$mp',`patt` = '$patt', `matt` = '$matt', `pdef` = '$pdef', `mdef` = '$mdef' WHERE `usr` = '$log' "); 
$skill = ($udata[lvl]*3)+($udata[res]*603);
mysql_query("UPDATE `users` SET `skill` ='$skill' WHERE `usr` = '$log' ");
echo'Очки параметров успешно сброшены!';
break;


case'res';
echo'<b>Ресет</b><br/>';
if($udata[lvl]<201){
echo'Вы не достигли 201 уровня!';
include($path.'inc/down.php');
exit;
}
$req1 = mysql_query("SELECT * FROM `baf` WHERE `usr` = '$log' LIMIT 1");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя делать ресет под бафом!</br>';
include($path.'inc/down.php');exit;
}
if($udata[almaz]==0){
echo'У Вас недостаточно CoL';
include($path.'inc/down.php');
exit;
}
$req1 = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image` = 'yes'");
$avto1=mysql_num_rows($req1);
if($avto1>0){
echo'Нельзя делать ресет с одетыми вещами!</br>';
include($path.'inc/down.php');exit;
}
if($udata[storona]=="human"){$all=50;}
if($udata[storona]=="gnom"){$all=100;}
if($udata[storona]=="elf"){$all=60;}
if($udata[storona]=="darkelf"){$all=75;}
if($udata[storona]=="ork"){$all=100;}
if($udata[storona]=="kamael"){$all=100;}
if($udata[klas]=="wizard"){
$mp = 100 + $all; $hp = 100 + $all;
}
if($udata[klas]=="fighert"){
$mp=80 + $all ; $hp=120 + $all;
}
//тестинг автоматической смены титула
$titul=Дворянин;
if ($udata[res]=="2"){
mysql_query("UPDATE `users` SET `titul` = '$titul' WHERE `usr` = '$log' ");
mysql_query("INSERT INTO
        `item` SET
        `usr` = '$log',
        `tip` = 'golova',
        `ruka` = 'ps',
        `name` = 'Mini Empire Cirklet',
        `cena` = '500000',
        `patt` = '3000000',
        `matt` = '3000000',
        `pdef` = '3000000',
        `mdef` = '3000000',
        `soul` = '0',
        `spirit` = '0',
        `image` = 'not'");
}
//конец
//Тестинг выдачи Героя
$titul1=Герой;
if ($udata[res]=="9"){
mysql_query("UPDATE `users` SET `titul` = '$titul1' WHERE `usr` = '$log' ");
mysql_query("INSERT INTO
        `item` SET
        `usr` = '$log',
        `tip` = 'golova',
        `ruka` = 'ps',
        `name` = 'Empire Cirklet',
        `cena` = '500000',
        `patt` = '10000000',
        `matt` = '10000000',
        `pdef` = '10000000',
        `mdef` = '10000000',
        `soul` = '0',
        `spirit` = '0',
        `image` = 'not'");
}
//////конец
mysql_query("UPDATE `users` SET `almaz` = `almaz` -0,`res` = `res` + 1 WHERE `usr` = '$log' ");
mysql_query("UPDATE `users` SET `hp`='$hp',`hpall`=$hp,`mp`='$mp',`mpall`='$mp',`exp`='0',`lvl`='0',`skill`='3' WHERE `usr` = '$log' "); 
echo'Ресет успешно произведен!';
break;

case 'dop';
echo"<b>Дополнительные услуги</b><br/><br/>";
echo"1) Заказ личного аватара/Фотографии 700 Coin of Luck , <br/>";
echo"2) У Вас есть возможность заказать именную вещь. 
Цена данной услуги 2000 CoL</b>. 
При заказе меняеться название вещи и по желанию её изображение.
</span><br/>
<br/>
<span style=color=#f4d06e;>
» Для заказа именной вещи нужно писать в <a href=\"/tiket.php?\"><span style=color:#6fcd72;>»Поддержку«</span></a>.<br/>
» В пункте <b>»Тема сообщения«</b> указать <u><b>Именная вещь</b></u>. <br/>
» В пункте <u><b>Введите сообщение</b></u> указать старое и новое название вещи (адрес новой картинки размером 32 х 32 px). 
Для точности указать её параметры и тип.<hr/> 
<span style=color=orange;>
<b>Например:</b><br/>
<b>Blade of Serenity</b> Оружие (тут параметры) переименовать на <b>Moe Nazvanie</b>
</span><hr/>
» Название не должно превышать 11 символов включая пробел.<br/>";
echo"3) Смена рассы = 500 Coin of Luck.<br/>";
echo"4) Смена Ника = 1500 Coin of Luck.<br/>";
echo"5) Смена класса персонажа = 500 Coin of Luck<br/><br>";

echo"<b>Фотография/аватар должна быть размером не больше чем 128x210</b><br/><br/>";

echo '<font color=#f4d06e>Для заказа услуги писать в <b>«Центр Поддержки»</b>. При заказе смены рассы, ника или класса персонажа указывать что и на что менять!<br/><br/>
Для смены аватара также писать в <b>ЦП</b> для получения дальнейших указаний по передачи желаемого аватара\Фотографии.<br/></font>
';
//-------------------------
echo"<br/><br/><font color=\"#f4d06e\">*Coin of Luck будут списаны с вашего лицевого счета. Поэтому для выполнения заказа, у Вас должна быть необходимая сумма CoL.</font><br/>";
echo"<br/><a href=\"col_bonus.php?\">Назад</a>";

break;
////////////////////////////

}
include($path.'inc/down.php');
?>