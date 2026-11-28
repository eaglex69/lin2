<?php
define('PROTECTOR', 1);

$headmod = 'shopjuvilir';//фикс. места

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

// Оформление как у shop.php: красные вкладки магазинов, плитки, фишки грейда, карточки товаров.
// id и грейд из адреса приводятся к числу.
$lvlus = isset($_GET['lvlus']) ? (int)$_GET['lvlus'] : 0;
$o = isset($_GET['o']) ? (string)$_GET['o'] : '';
$mod = isset($_GET['mod']) ? (string)$_GET['mod'] : '';
$money = isset($udata['money']) ? $udata['money'] : null;

switch($mod){

default:
echo lin2_ui_shop_tabs('jewel');
echo lin2_ui_note('Украшения: серьги, кольца и амулеты.');
echo lin2_ui_tiles(array(
array('shopjuvilir.php?mod=mag&o=amulet', 'g-amulet', 'Амулеты'),
array('shop.php?mod=b&o=poyas', 'g-earring', 'Левые серьги'),
array('shop.php?mod=b&o=rpoyas', 'g-earring', 'Правые серьги'),
array('shopjuvilir.php?mod=mag&o=kolco', 'g-ring', 'Левые кольца'),
array('shopjuvilir.php?mod=mag&o=rkolco', 'g-ring', 'Правые кольца'),
));
echo lin2_ui_go(array(array('shop.php?', 'Назад')));
break;

case 'mag':
if($o!='amulet' && $o!='kolco' && $o!='rkolco'){
echo lin2_ui_note('Ошибка!');
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop` WHERE `city` != '' and `tip`='$o' and `nlvl`='$lvlus' ORDER BY cena");
////////////////////////////
$rows = array();
While($mag = mysql_fetch_array($req))
{
$rows[] = $mag;
}

echo lin2_ui_shop_tabs('jewel');
echo lin2_ui_sub(lin2_ui_tip_name($o, true), 'грейд '.lin2_ui_grade($lvlus));
echo lin2_ui_grade_chips('shopjuvilir.php?mod=mag&o='.$o.'&lvlus=', $lvlus, 76);   // украшения продаются до S
echo lin2_ui_goods($rows, 'shopjuvilir.php?mod=info&id=', 'sell.php?act=ask&id=', $money);
echo lin2_ui_go(array(array('shopjuvilir.php?', 'Назад')));
break;
case 'info':
$req = mysql_query("SELECT * FROM `shop` WHERE `city` != '' and `id`='".(int)(isset($_GET['id']) ? $_GET['id'] : 0)."'");

$avto=mysql_num_rows($req);
if($avto==0){
echo lin2_ui_note('Ошибка!');
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

echo lin2_ui_shop_tabs('jewel');
echo lin2_ui_item_sheet($mag, array(
'price' => $mag['cena'],
'acts' => array(
array('sell.php?act=ask&id='.(int)$mag['id'], 'Купить', ($money === null || (float)$money >= (float)$mag['cena']) ? '' : 'grey'),
array('shopjuvilir.php?mod=mag&lvlus='.(int)$mag['nlvl'].'&o='.$mag['tip'], 'Назад', 'grey'),
),
));
break;
}
include($path.'inc/down.php');
?>
