<?
define('PROTECTOR', 1);

$headmod = 'shoppit';//фикс. места

$textl='Магазин';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
place_okr();
place_zamok();
place_tower();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

switch($_GET[mod]){

default:

echo "<font color=#f4d06e><b><p>Вещи для питомцев</p></b></font><hr/>";
if(empty($_GET[o])){
echo"<div class=inoy><a href=\"shoppit.php?o=weapon\">Отдел оружия</a>";
echo"<a href=\"shoppit.php?o=golova\">Отдел шлемов</a>";
echo"<a href=\"shoppit.php?o=body\">Отдел доспехов</a>";
echo"<a href=\"shoppit.php?o=nogi\">Отдел сапог</a></div>";
}else{
if($_GET[o]!=weapon && $_GET[o]!=golova && $_GET[o]!=body && $_GET[o]!=nogi){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `pit_shop` WHERE `city` != '' and `tip`='$_GET[o]'");
////////////////////////////
$avto=mysql_num_rows($req);

While($mag = mysql_fetch_array($req))
{
echo"<a href=\"shoppit.php?mod=info&amp;id=$mag[id]\">$mag[name]</a> ($mag[cena] монет) [<a href=\"shoppit.php?act=ask&amp;mod=sell&amp;id=$mag[id]\">купить</a>]<br/>";
}
echo"<a href=\"shoppit.php?\">Назад</a>";
}
break;
case 'info':
$req = mysql_query("SELECT * FROM `pit_shop` WHERE `city` != '' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
switch($mag[tip]){
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
switch($mag[rasa]){
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
echo"<a href=\"shoppit.php?\">Назад</a>";
break;
case 'sell':
if($_GET[act]==ask){
echo'Вы действительно хотите купить эту вещь?<br/>';
echo"<a href=\"shoppit.php?mod=sell&amp;id=$_GET[id]\">Да</a><br/>";
echo"<a href=\"shoppit.php?\">Нет</a>";
}else{
$req = mysql_query("SELECT * FROM `pit_shop` WHERE `city` != '' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка, такой вещи нет!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

$req=mysql_query("SELECT * FROM domination WHERE id = '1'");
$dom = mysql_fetch_assoc($req);
if($dom['white']>$dom['black']){
$liders='white';
}elseif($dom['black']>$dom['white']){
$liders='black';
}else{
$liders='not';
}
if($udata['storona']==$liders){
$mag[cena]=round($mag[cena]-(($mag[cena]/100)*15));
}

$nmoney=$udata[money]-$mag[cena];

if($nmoney<0){
echo'Нехватает монет!';
include($path.'inc/down.php');
exit;
}

mysql_query("INSERT INTO
        `pit_item` SET
        `usr` = '$log',
        `tip` = '$mag[tip]',
        `name` = '$mag[name]',
        `cena` = '$mag[cena]',
        `sila` = '$mag[sila]',
        `prot` = '$mag[prot]',
        `hp` = '$mag[hp]',
        `rasa` = '$mag[rasa]',
        `nlvl` = '$mag[nlvl]',
        `image` = 'not'");

$req = mysql_query("SELECT * FROM `zamok` WHERE `city` = '$udata[city]' LIMIT 1");
////////////////////////////
$city = mysql_fetch_array($req);
if($city[clan]!='not' and $udata[clan]!=$city[clan]){
$req = mysql_query("SELECT * FROM `clan` WHERE `lider`='$city[clan]'");
////////////////////////////
$clan = mysql_fetch_array($req);
$clan[money]=$clan[money]+round(($mag[cena]/100)*2);

mysql_query("UPDATE `clan` SET `money` = '$clan[money]' WHERE `lider` = '$city[clan]'");
}

mysql_query("UPDATE users SET money = '$nmoney' WHERE usr = '$log'");

echo"
Вещь $mag[name] куплена!<br/>
Монет потрачено: $mag[cena]<br/>
Монет осталось: $nmoney<br/>
";
echo"<a href=\"shoppit.php?\">Назад</a>";
}
break;
}
include($path.'inc/down.php');
?>