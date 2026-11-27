<?php
define('PROTECTOR', 1);

$headmod = 'pit';//фикс. места

$textl='Питомец';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
switch($_GET['mod']){
default:
if(empty($_GET['id'])){
echo"Нет такого питомца!<br/>";
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT * FROM `pit` WHERE `id` = '$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"Нет такого питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($req);

echo"<b>$pit[name]</b><br/>";
echo"<img src='pic/pit/$pit[rasa].gif'/><br/>";
echo"Раса: ";
if($pit['rasa']=="wolf"){echo"Волк<br/>";}
if($pit['rasa']=="dog"){echo"Собака<br/>";}

echo"Хозяин: <a href=\"search.php?nick=$pit[usr]&amp;go=go\"><b>$pit[usr]</b></a><br/>";
echo"Уровень: $pit[lvl]<br/>";
echo"Опыт: $pit[exp]<br/>";
echo"Жизни: $pit[hp]/$pit[hpall]<br/>";
echo"Смертей: $pit[dies]<br/>";
echo"Статус: ";
if($pit['status']=="off"){echo"<b>В питомнике</b><br/>";}
if($pit['status']=="on"){echo"<b>С хозяином</b><br/>";
}
if($pit['status']=="die"){echo"<b>Мёртв</b><br/>";}

echo"<br/><b>Физические параметры</b><br/>";
echo"Сила: $pit[sila]<br/>";
echo"Защита: $pit[prot]<br/>";

echo"<br/><b>Снаряжение</b><br/>";
$i=1;
while($i<=4){
switch($i){
case '1':
$tip='Оружие';
$gt='weapon';
break;
case '2':
$tip='Шлем';
$gt='golova';
break;
case '3':
$tip='Доспех';
$gt='body';
break;
case '4':
$tip='Сапоги';
$gt='nogi';
break;
}
echo"$tip: ";
$req = mysql_query("SELECT id,name FROM `pit_item` WHERE `usr` = '$pit[usr]' and `tip`='$gt' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"Пусто<br/>";
}else{
$mag = mysql_fetch_array($req);
echo"<b>$mag[name]</b><br/>";
}
$i++;
}

break;

case 'mypit':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"С вами нет питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($req);

echo"<b>$pit[name]</b><br/>";
echo"<img src='pic/pit/$pit[rasa].gif'/><br/>";
echo"Раса: ";
if($pit['rasa']=="wolf"){echo"Волк<br/>";}
if($pit['rasa']=="dog"){echo"Собака<br/>";}

echo"Уровень: $pit[lvl]<br/>";
echo"Опыт: $pit[exp]<br/>";
echo"Жизни: $pit[hp]/$pit[hpall]<br/>";
echo"Свободные очки: $pit[skill]<br/>";
echo"Смертей: $pit[dies]<br/>";
echo"Статус: ";
if($pit['status']=="off"){echo"<b>В питомнике</b><br/>";}
if($pit['status']=="on"){echo"<b>С вами</b><br/>";
$pit['lasteda']=$pit['lasteda']-time();
if($pit['lasteda']<60){
echo "Сытость: $pit[lasteda] секунд<br/>";
}elseif($pit['lasteda']>60 and $pit['lasteda']<3600){
$pit['lasteda']=round($pit['lasteda']/60);
echo "Сытость: $pit[lasteda] минут<br/>";
}else{
$pit['lasteda']=round($pit['lasteda']/3600);
echo "Сытость: $pit[lasteda] часов<br/>";
}
}
if($pit['status']=="die"){echo"<b>Мёртв</b><br/>";}

echo"<br/><b>Физические параметры</b><br/>";
echo"Сила: $pit[sila]<br/>";
echo"Защита: $pit[prot]<br/>";

echo"<br/><b>Снаряжение</b><br/>";
$i=1;
while($i<=4){
switch($i){
case '1':
$tip='Оружие';
$gt='weapon';
break;
case '2':
$tip='Шлем';
$gt='golova';
break;
case '3':
$tip='Доспех';
$gt='body';
break;
case '4':
$tip='Сапоги';
$gt='nogi';
break;
}
echo"$tip: ";
$req = mysql_query("SELECT id,name FROM `pit_item` WHERE `usr` = '$log' and `tip`='$gt' and `pit`='$pit[id]' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"Пусто<br/>";
}else{
$mag = mysql_fetch_array($req);
echo"<a href=\"pit.php?mod=snyat&amp;tip=$gt\"><b>$mag[name]</b></a><br/>";
}
$i++;
}
echo"<a href=\"pit.php?mod=item\">Инвентарь</a><br/>";
break;
case 'item':

echo "<p><font color=grey> Шмот питомца: </font></p>";
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"С вами нет питомца!";
include($path.'inc/down.php');exit;
}

if(empty($_GET['o'])){
echo "<div class=inoy>";
echo"<a href=\"pit.php?mod=item&amp;o=weapon\">Оружия</a>";
echo"<a href=\"pit.php?mod=item&amp;o=golova\">Шлемы</a>";
echo"<a href=\"pit.php?mod=item&amp;o=body\">Доспехи</a>";
echo"<a href=\"pit.php?mod=item&amp;o=nogi\">Сапоги</a>";
echo"</div><br/>";
echo"<div class=inoy><a href=\"pit.php?mod=mypit\">Назад</a></div>";
}else{
if($_GET['o']!='weapon' && $_GET['o']!='golova' && $_GET['o']!='body' && $_GET['o']!='nogi'){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `pit_item` WHERE `usr` = '$log' and `tip`='$_GET[o]' and `image`='not'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<a href=\"pit.php?mod=info&amp;id=$mag[id]\">$mag[name]</a> [<a href=\"pit.php?mod=dress&amp;id=$mag[id]\">одеть</a>]<br/>";
}
}else{
echo"<b>Нет вещей!</b><br/>";
}
echo"<a href=\"pit.php?mod=item\">Назад</a>";
}
break;
case 'info':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"С вами нет питомца!";
include($path.'inc/down.php');exit;
}

$req = mysql_query("SELECT * FROM `pit_item` WHERE `usr` = '$log' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
switch($mag['tip']){
case 'weapon':
$tip='Оружие';
break;
case 'body':
$tip='Доспехи';
break;
case 'golova':
$tip='Шлем';
break;
case 'nogi':
$tip='Сапоги';
break;
}
switch($mag['rasa']){
case 'not':
$rasa='Все';
break;
case 'wolf':
$rasa='Волк';
break;
case 'dog':
$rasa='Собака';
break;
}
echo"<b>$mag[name]</b><br/>
Тип: $tip<br/>
<b>Характеристика</b><br/>
Урон: $mag[sila]<br/>
Защита: $mag[prot]<br/>
Жизнь: $mag[hp]<br/>
<b>Требования:</b><br/>
Раса: $rasa<br/>
Уровень: $mag[nlvl]<br/>
";
echo"<a href=\"pit.php?mod=item\">Назад</a>";
break;
case 'dress':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"У вас нет питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($req);

$req = mysql_query("SELECT * FROM `pit_item` WHERE `usr` = '$log' and `id`='$_GET[id]' and `image`='not'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка, такой вещи нет!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

$req = mysql_query("SELECT * FROM `pit_item` WHERE `usr` = '$log' and `tip`='$mag[tip]' and `pit`='$pit[id]' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto>=1){
echo'Сначала снимите то что на питомце!';
include($path.'inc/down.php');
exit;
}


if($mag['rasa']!='not' and $mag['rasa']!=$pit['rasa']){
echo'Эта вещь не для расы вашего питомца!';
include($path.'inc/down.php');
exit;
}
if($mag['nlvl'] > $pit['lvl']){
echo'Ващь неподходит по уровню!';
include($path.'inc/down.php');
exit;
}
//$mag = mysql_fetch_array($req);


///////////////////////
$nhp=$mag['hp']+$pit['hpall'];
$nsila=$mag['sila']+$pit['sila'];
$nprot=$mag['prot']+$pit['prot'];
//////////////////////

mysql_query("UPDATE `pit` SET
         `hpall` = '$nhp',
         `sila` = '$nsila',
         `prot` = '$nprot'
          WHERE usr = '$log'");
		  
mysql_query("UPDATE pit_item SET image = 'yes',`pit`='$pit[id]' WHERE `usr` = '$log' and `tip`='$mag[tip]' and `id`='$_GET[id]'");

echo"Вы одели $mag[name] на питомца!<br/>";
echo"<a href=\"pit.php?mod=item\">Назад</a>";
break;
case 'snyat':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"С вами нет питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($req);

$req = mysql_query("SELECT * FROM `pit_item` WHERE `usr` = '$log' and `pit`='$pit[id]' and `tip`='$_GET[tip]' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка, на питомце ничего не одето!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

///////////////////////
$nhp=$pit['hpall']-$mag['hp'];
$nsila=$pit['sila']-$mag['sila'];
$nprot=$pit['prot']-$mag['prot'];
//////////////////////

mysql_query("UPDATE `pit` SET
         `hpall` = '$nhp',
         `sila` = '$nsila',
         `prot` = '$nprot'
          WHERE usr = '$log'");

mysql_query("UPDATE pit_item SET image = 'not',`pit`='0' WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `image`='yes' and `pit`='$pit[id]'");

echo"Вы сняли $mag[name]!<br/>";
echo"<a href=\"pit.php?mod=mypit\">Назад</a>";
break;
}
include($path.'inc/down.php');
?>