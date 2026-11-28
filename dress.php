<?php
define('PROTECTOR', 1);
$headmod = 'dress';//фикс. места
$textl='Инвентарь';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
include_once $path.'inc/lin2_ui.php';

// Оформление: сообщения в рамке и кнопки (inc/lin2_ui.php). Логика прежняя; тип вещи и id из адреса очищаются.
$tip = preg_replace('/[^a-z]/', '', isset($_GET['tip']) ? (string)$_GET['tip'] : '');
$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$back = 'inventar.php?mod=item&o='.$tip;

$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$tip' and `id`='$id' and `image`='not'");
$avto=mysql_num_rows($req);
$req1 = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$tip' and `image`='yes'");
$avto1=mysql_num_rows($req1);
if($avto1>=1){
echo lin2_ui_note('Сначала снимите то что на вас!');
echo lin2_ui_acts(array(array('pers.php?act=shmot', 'Снять вещи', 'red'), array($back, 'Назад', 'grey')));
include($path.'inc/down.php');
exit;
}
if($avto==0){
echo lin2_ui_note('Ошибка, такой вещи нет!');
echo lin2_ui_acts(array(array('inventar.php?', 'Инвентарь', 'grey')));
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
if($mag['nlvl'] > $udata['lvl']){
echo lin2_ui_note('Вещь не подходит по уровню! Иди в окрестности и подними свой level.');
echo lin2_ui_acts(array(array('to_world.php?', 'Окрестности'), array($back, 'Назад', 'grey')));
include($path.'inc/down.php');
exit;
}
$npatt=$mag['patt']+$udata['patt'];
$nmatt=$mag['matt']+$udata['matt'];
$npdef=$mag['pdef']+$udata['pdef'];
$nmdef=$mag['mdef']+$udata['mdef'];
mysql_query("UPDATE `users` SET
         `pdef` = '$npdef',
         `mdef` = '$nmdef',
         `patt` = '$npatt',
         `matt` = '$nmatt'
          WHERE usr = '$log'");
mysql_query("UPDATE item SET image = 'yes' WHERE `usr` = '$log' and `tip`='$tip' and `id`='$id'");
echo lin2_ui_sheet('Вы одели '.$mag['name'].'!', lin2_ui_tip_name($tip), lin2_ui_item_stats($mag), array(array($back, 'Назад'), array('inventar.php?', 'Инвентарь', 'grey')),
lin2_ui_item_pic($mag['name'], $tip, true), lin2_ui_grade_mark($mag['nlvl']));
include($path.'inc/down.php');
?>
