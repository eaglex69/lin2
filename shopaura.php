<?php
define('PROTECTOR', 1);

$headmod = 'shopaura';//фикс. места

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


switch($_GET['mod']){

default:


$req = mysql_query("SELECT * FROM `shop_aura` WHERE `city` != ''");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){

echo'В наличии:<br/><br/>';
echo'<hr/>';

While($mag = mysql_fetch_array($req))
{
echo"<div class=silka><a href=\"shopaura.php?mod=info&amp;id=$mag[id]\">$mag[name] <br/> <font color=#ffffff>Цена: $mag[cena] Аден</font></a></div><hr/>";
}
}else{
echo'<br/>Нет аур на продаже!<br/>';
}
echo "<br/><div class=silka><a href=\"gorod.php?\">В город </a></div>";
break;
case 'info':
$req = mysql_query("SELECT * FROM `shop_aura` WHERE `city` != '' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

switch($mag['klas']){
case 'not':
$klas='Все';
break;
case 'wizard':
$klas='Маг';
break;
case 'fighert':
$klas='Воин';
break;
}

echo"<br/><b>$mag[name]</b><br/><br/>

Урон: $mag[umin]/$mag[umax]<br/>
Физ. защита: $mag[protect]<br/><br/>

Жизнь: $mag[hp]<br/>
Мана: $mag[mp]<br/><br/>

Крит: $mag[krit]%<br/>
Крит-урон: $mag[ukrit]%<br/>
Анти-крит: $mag[antikrit]%<br/><br/>

<b>Требования:</b><br/>
Класс: $klas<br/>
Сила: $mag[nsila]<br/>
Ловкость: $mag[nlovk]<br/>
Интелект: $mag[num]<br/>
Уровень: $mag[nlvl]<br/><br/>

<b>Время действия: $mag[actime] сек.</b><br/>
";
echo"<br/><a href=\"shopaura.php?mod=sell&amp;id=$mag[id]\">Kупить</a><br/><a href=\"shopaura.php?\">Назад</a>";
break;

case 'sell':
$req = mysql_query("SELECT * FROM `shop_aura` WHERE `city` != '' and `id`='$_GET[id]'");

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
$mag['cena']=round($mag['cena']-(($mag['cena']/100)*15));
}

$nmoney=$udata['money']-$mag['cena'];

if($nmoney<0){
echo'Нехватает монет!';
include($path.'inc/down.php');
exit;
}
$req1 = mysql_query("SELECT * FROM `item_aura` WHERE `usr` = '$log' and `lat_name` = '$mag[lat_name]'");
$res = mysql_fetch_array($req1);
$avto1=mysql_num_rows($req1);
if($avto1==0){
mysql_query("INSERT INTO
        `item_aura` SET
        `usr` = '$log',
        `name` = '$mag[name]',
        `lat_name` = '$mag[lat_name]',
        `cena` = '$mag[cena]',
        `umin` = '$mag[umin]',
        `umax` = '$mag[umax]',
        `protect` = '$mag[protect]',
        `hp` = '$mag[hp]',
        `mp` = '$mag[mp]',
        `krit` = '$mag[krit]',
        `ukrit` = '$mag[ukrit]',
        `antikrit` = '$mag[antikrit]',
        `klas` = '$mag[klas]',
        `nsila` = '$mag[nsila]',
        `num` = '$mag[num]',
        `nlovk` = '$mag[nlovk]',
        `nlvl` = '$mag[nlvl]',
        `actime` = '$mag[actime]',
        `kol` = '1'");
        
$req = mysql_query("SELECT * FROM `zamok` WHERE `city` = '$udata[city]' LIMIT 1");
////////////////////////////
$city = mysql_fetch_array($req);
if($city['clan']!='not' and $udata['clan']!=$city['clan']){
$req = mysql_query("SELECT * FROM `clan` WHERE `lider`='$city[clan]'");
////////////////////////////
$clan = mysql_fetch_array($req);
$clan['money']=$clan['money']+round(($mag['cena']/100)*2);

mysql_query("UPDATE `clan` SET `money` = '$clan[money]' WHERE `lider` = '$city[clan]'");
}

}else{
$nk=$res['kol']+1;
mysql_query("UPDATE `item_aura` SET `kol` = '$nk' WHERE `usr` = '$log' and `lat_name` = '$mag[lat_name]'");
}
mysql_query("UPDATE users SET money = '$nmoney' WHERE usr = '$log'");

echo"
<b>Аура $mag[name] куплена!</b><br/><br/>
Аден потрачено: $mag[cena]<br/>
Аден осталось: $nmoney<br/>
";
echo"<br/><a href=\"shopaura.php?\">Назад</a>";
break;
}
include($path.'inc/down.php');
?>