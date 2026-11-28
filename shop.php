<?php
define('PROTECTOR', 1);

$headmod = 'shop';//фикс. места

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

// Оформление: плитки разделов, красные вкладки «Оружие / Броня / Украшения», фишки грейда и типа, карточки товаров
// (theme/fantasy.css, раздел 10; разметка: inc/lin2_ui.php). Запросы, ссылки и параметры те же, что были.
// id, грейд и тип из адреса приводятся к числу / к списку допустимых значений.
$weapon_kinds = array('ydar'=>'Ударное', 'luk'=>'Луки', 'kin'=>'Кинжалы', 'sdv'=>'Сдвоенное', 'kas'=>'Кастеты',
    'kniga'=>'Книги', 'koppik'=>'Копья/Пики', 'me4'=>'Мечи', 'rap'=>'Рапиры');
$tip2 = (isset($_GET['tip2']) && isset($weapon_kinds[$_GET['tip2']])) ? $_GET['tip2'] : 'ydar';
$lvlus = isset($_GET['lvlus']) ? (int)$_GET['lvlus'] : 0;
$o = isset($_GET['o']) ? (string)$_GET['o'] : '';
$mod = isset($_GET['mod']) ? (string)$_GET['mod'] : '';
$money = isset($udata['money']) ? $udata['money'] : null;

/*if ($udata[dostup]<4){

echo "<hr/><p><font color=red>Приносим свои извенения. Проводятся технические работы по обновлению магазинов!</p></font><hr/></b></div></div></center>";
 include($path.'inc/down.php');exit;

}*/


switch($mod){

default:
echo lin2_ui_note('<b>Решил прикупиться?</b> Выбирай что нужно:');
echo lin2_ui_tiles(array(
array('shop.php?mod=weapon', 'g-weapon', 'Магазин оружия', array('sub'=>'Мечи, луки, кинжалы, книги, копья')),
array('shop.php?mod=b', 'g-body', 'Магазин брони', array('sub'=>'Шлемы, доспехи, штаны, сапоги, щиты')),
array('/shopjuvilir.php?', 'g-ring', 'Амулеты и кольца', array('sub'=>'Серьги, кольца, амулеты')),
), 'big');
//echo"<a href=\"shopsoul.php\">Магазин SoulShots</a>";
echo lin2_ui_go(array(array('gorod.php?pl=shop_zone', 'Торговая площадь')));
break;

case 'w':
echo lin2_ui_go(array(array('shop.php?mod=weapon', 'Отдел оружия'), array('shop.php?', 'Назад')));
break;

case 'b':
// ----- броня: плитки по видам, внутри вид -> грейд -> карточки
$armor = array('golova', 'body', 'pants', 'nogi', 'ruki', 'shit', 'plash');
$glyph = array('golova'=>'g-golova', 'body'=>'g-body', 'pants'=>'g-pants', 'nogi'=>'g-nogi', 'ruki'=>'g-ruki', 'shit'=>'g-shit',
    'plash'=>'g-plash', 'poyas'=>'g-earring', 'rpoyas'=>'g-earring');
if(empty($o)){
echo lin2_ui_shop_tabs('b');
echo lin2_ui_note('Защита — важная часть! Выбери, что хочешь надеть.');
$tiles = array();
foreach ($armor as $t) {
$tiles[] = array('shop.php?mod=b&o='.$t, $glyph[$t], lin2_ui_tip_name($t, true));
}
echo lin2_ui_tiles($tiles, 'sm');
echo lin2_ui_go(array(array('shop.php?', 'Назад')));

}else{
if(!in_array($o, array('shit','golova','body','rpoyas','poyas','plash','pants','ruki','nogi'), true)){
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

echo lin2_ui_shop_tabs(($o=='poyas' || $o=='rpoyas') ? 'jewel' : 'b');
echo lin2_ui_sub(lin2_ui_tip_name($o, true), 'грейд '.lin2_ui_grade($lvlus));
echo lin2_ui_grade_chips('shop.php?mod=b&o='.$o.'&lvlus=', $lvlus);
echo lin2_ui_goods($rows, 'shop.php?mod=info&id=', 'sell.php?act=ask&id=', $money);
echo lin2_ui_go(array(array('shop.php?mod=b', 'Назад')));

}
break;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
case 'weapon':

$req = mysql_query("SELECT * FROM `shop` WHERE `city` != '' and `tip`='weapon' and `tip2`='$tip2' and `nlvl`='$lvlus' ORDER BY cena");
////////////////////////////
$rows = array();
While($mag = mysql_fetch_array($req))
{
$rows[] = $mag;
}

echo lin2_ui_shop_tabs('weapon');
echo lin2_ui_grade_chips('shop.php?mod=weapon&tip2='.$tip2.'&lvlus=', $lvlus);
$kinds = array();
foreach ($weapon_kinds as $k => $title) {
$kinds[] = array($title, $k == $tip2 ? null : 'shop.php?mod=weapon&lvlus='.$lvlus.'&tip2='.$k);
}
echo lin2_ui_chips('Тип', $kinds);
echo lin2_ui_sub($weapon_kinds[$tip2], 'грейд '.lin2_ui_grade($lvlus));
echo lin2_ui_goods($rows, 'shop.php?mod=info&id=', 'sell.php?act=ask&id=', $money);
echo lin2_ui_go(array(array('shop.php?', 'Назад')));
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

$jewel = in_array($mag['tip'], array('poyas','rpoyas','amulet','kolco','rkolco'), true);
echo lin2_ui_shop_tabs($mag['tip']=='weapon' ? 'weapon' : ($jewel ? 'jewel' : 'b'));
// «Назад» ведёт в тот отдел и грейд, откуда пришли
if ($mag['tip']=='weapon') {
$back = 'shop.php?mod=weapon&lvlus='.(int)$mag['nlvl'].'&tip2='.urlencode((string)$mag['tip2']);
} elseif ($mag['tip']=='amulet' || $mag['tip']=='kolco' || $mag['tip']=='rkolco') {
$back = 'shopjuvilir.php?mod=mag&lvlus='.(int)$mag['nlvl'].'&o='.$mag['tip'];
} else {
$back = 'shop.php?mod=b&lvlus='.(int)$mag['nlvl'].'&o='.$mag['tip'];
}
echo lin2_ui_item_sheet($mag, array(
'price' => $mag['cena'],
'acts' => array(array('sell.php?act=ask&id='.(int)$mag['id'], 'Купить', ($money === null || (float)$money >= (float)$mag['cena']) ? '' : 'grey'), array($back, 'Назад', 'grey')),
));
break;

case 'soulshots':

if (isset($_GET['t'])){
$colnew=$udata['almaz']-1;
$co = 100; // количество наживки
if ($colnew>=0){

$req = mysql_query("SELECT * FROM `soulshots` WHERE `usr` = '$log'");
$res=mysql_num_rows($req);
$rs = mysql_fetch_array($req);
if($res==0){
mysql_query("INSERT INTO `soulshots` (`id` ,`usr` ,`kol` )
VALUES (NULL , '$log', '100');");
$nk=$rs['kol']+$co;
echo lin2_ui_note('Вы купили '.$co.' Shots. У Вас теперь '.$nk.' Shots.');
}else{
$nk=$rs['kol']+$co;
mysql_query("UPDATE `soulshots` SET `kol` = '$nk' WHERE `usr` = '$log'");
echo lin2_ui_note('Вы купили '.$co.' Shots. У Вас теперь '.$nk.' Shots.');

}
mysql_query("UPDATE `users` SET `almaz` = '$colnew' WHERE `usr` = '$log' LIMIT 1");


}else{
echo lin2_ui_note('<b>Не хватает Coin of Luck</b>');
}
}else{

/*echo "<p><font color=#057F46><b>Купить Shots:</b></font></p>";*/

echo lin2_ui_thing_row('Shots x100', '', 'g-crystal', '/pic/skr/s.jpeg', 'Цена: 1 Coin of Luck', array(array('shop.php?mod=soulshots&t', 'Купить')));

}
break;




}
include($path.'inc/down.php');
