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
include_once $path.'inc/lin2_ui.php';

// Оформление: вопрос и результат покупки в рамках с кнопками (inc/lin2_ui.php). Логика прежняя; id из адреса приводится к числу.
$sid = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$sact = isset($_GET['act']) ? (string)$_GET['act'] : '';
switch($sact){

default:
$req = mysql_query("SELECT * FROM `shop` WHERE `city` != '' and `id`='$sid'");

$avto=mysql_num_rows($req);
if($avto==0){
echo lin2_ui_note('Ошибка, такой вещи нет!');
echo lin2_ui_acts(array(array('shop.php?', 'В магазин', 'grey')));
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
echo lin2_ui_sheet('Нехватает монет!', $mag['name'], array(array('Цена', number_format($mag['cena'], 0, ',', "\xC2\xA0")), array('У вас', number_format($udata['money'], 0, ',', "\xC2\xA0"))),
array(array('shop.php?mod=info&id='.$sid, 'К вещи', 'grey'), array('shop.php?', 'В магазин')), lin2_ui_item_pic($mag['name'], $mag['tip'], true));
include($path.'inc/down.php');
exit;
}

include_once($path . 'inc/quests_promo.php');
lin2_qp_progress($log, 'shop_buy', 1);
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

echo lin2_ui_sheet('Вещь '.$mag['name'].' куплена и помещена в инвентарь!', lin2_ui_tip_name($mag['tip']),
array(array('Монет потрачено', number_format($mag['cena'], 0, ',', "\xC2\xA0")), array('Монет осталось', number_format($nmoney, 0, ',', "\xC2\xA0"))),
array(array('inventar.php?mod=item&o='.$mag['tip'], 'В инвентарь'), array('shop.php?', 'В магазин', 'grey')),
lin2_ui_item_pic($mag['name'], $mag['tip'], true), lin2_ui_grade_mark($mag['nlvl']));

break;

case 'ask':
$req = mysql_query("SELECT * FROM `shop` WHERE `city` != '' and `id`='$sid'");
if(mysql_num_rows($req)==0){
echo lin2_ui_note('Ошибка, такой вещи нет!');
echo lin2_ui_acts(array(array('shop.php?', 'В магазин', 'grey')));
break;
}
$mag = mysql_fetch_array($req);
echo lin2_ui_item_sheet($mag, array(
'price' => $mag['cena'],
'acts' => array(array('sell.php?id='.$sid, 'Да, купить'), array('shop.php?mod=info&id='.$sid, 'Нет', 'grey')),
));
echo lin2_ui_note('Вы действительно хотите купить эту вещь?');
break;
}
include($path.'inc/down.php');
?>