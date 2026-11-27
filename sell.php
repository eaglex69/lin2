<?php
define('PROTECTOR', 1);

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

switch($_GET['act']){

default:
$req = mysql_query("SELECT * FROM `shop` WHERE `city` != '' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка, такой вещи нет!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
///////////////
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
$mag['cena']=round($mag['cena']-(($mag['cena']/100)*15));
}
/////////////
$nmoney=$udata['money']-$mag['cena'];

if($nmoney<0){
echo'Нехватает монет!';
include($path.'inc/down.php');
exit;
}

mysql_query("INSERT INTO
        `item` SET
        `usr` = '$log',
        `tip` = '$mag[tip]',
        `ruka` = '$mag[tip2]',
        `name` = '$mag[name]',
        `cena` = '$mag[cena]',
        `patt` = '$mag[patt]',
        `matt` = '$mag[matt]',
        `pdef` = '$mag[pdef]',
        `mdef` = '$mag[mdef]',
        `time` = '$mag[time]',
        `soul` = '$mag[soul]',
        `spirit` = '$mag[spirit]',
        `nlvl` = '$mag[nlvl]',
        `image` = 'not'");

//////////////////////////////////////////////////////////////////////////////////////////////     
$req = mysql_query("SELECT * FROM `zamok` WHERE `city` = '$udata[city]' LIMIT 1");////////////
//////////////////////////////////////////////////////////////////////////////////////////////
$city = mysql_fetch_array($req);//////////////////////////////////////////////////////////////
if($city['clan']!='not' and $udata['clan']!=$city['clan']){/////////////////////////////////////////
$req = mysql_query("SELECT * FROM `clan` WHERE `lider`='$city[clan]'");///////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////
$clan = mysql_fetch_array($req);//////////////////////////////////////////////////////////////
$clan['money']=round($clan['money']+(($mag['cena']/100)*2));////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////

mysql_query("UPDATE `clan` SET `money` = '$clan[money]' WHERE `lider` = '$city[clan]'");
}

mysql_query("UPDATE users SET money = '$nmoney' WHERE usr = '$log'");

echo"
Вещь $mag[name] куплена и помещена в инвентарь!<br/>
Монет потрачено: $mag[cena]<br/>
Монет осталось: $nmoney<br/>
";
echo"<a href=\"shop.php?\">Назад</a>";

break;

case 'ask':
echo'Вы действительно хотите купить эту вещь?<br/>';
echo"<a href=\"sell.php?id=$_GET[id]\">Да</a> | ";
echo"<a href=\"shop.php?\">Нет</a>";
break;
}
include($path.'inc/down.php');
?>