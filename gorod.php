<?php
define('PROTECTOR', 1);

$headmod = 'gorod';//фикс. места

$textl='Город';
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









// город: плитки вместо ссылок-иконок (оформление: theme/fantasy.css, раздел 10; разметка: inc/lin2_ui.php)
include_once $path.'inc/lin2_ui.php';

if ($udata['karma']>10) // если большая карма
{
echo '<div class="lx-view"><img src="/pic/roz/karma.jpg" alt="" onerror="this.parentNode.style.display=\'none\'"/></div>';
echo '<div class="lx-hint"><b>Стой..стой..стой...</b> У тебя слишком большая карма, я не могу пропустить тебя в город. Иди и отмойся в окрестностях на монстрах.</div>';

}else{

/////загружаем город
$req = mysql_query("SELECT * FROM `zamok` WHERE `city` = '$udata[city]' LIMIT 1");

////////////////////////////
$city = array('clan'=>'not');
$avto=mysql_num_rows($req);
if($avto==1){
$city = mysql_fetch_array($req);
if($city['clan']!='not'){
$req = mysql_query("SELECT * FROM `clan` WHERE `lider`='$city[clan]'");
//////////////////
$clan = mysql_fetch_array($req);
$clan_note = '<div class="lx-hint"><b>Город под контролем клана:</b> <a href="clan.php?id='.(int)$clan['id'].'">'.lin2_ui_h($clan['name']).'</a></div>';
}
}
//////////////
$b = mysql_query("SELECT * FROM bazar WHERE `city`='$udata[city]'");
////////////////////////////
$cbaz=mysql_num_rows($b);
$req6546566 = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$city[clan]'");
$wh = mysql_fetch_array($req6546566);
if(!empty($wh['emblema'])){
$pic = "<img src=\"/pic/clan/$wh[emblema]\" alt=\"cl\"/>";}
////////]!='shop_z////------------------------------------	ГОРОД	------------------------------------

if(!isset($_GET['pl']) or $_GET['pl']!='shop_zone'){
/*include($path.'inc/include.php');*/

echo lin2_ui_place('Город', $udata['city']);
if (isset($clan_note)) {echo $clan_note;}

// картинка города
$town_pic = array(
'Северный полюс'=>'/pic/roz/gorod.jpg', 'Hunter Village'=>'/pic/town/дварвен.png', 'Dark Elven Village'=>'/pic/town/даркелф.png',
'Oren Town'=>'/pic/town/глудио.png', 'Orc Village'=>'/pic/town/орк.png', 'Elven Village'=>'/pic/town/елвен.png',
'Farm Zone'=>'/pic/roz/gorod.jpg', 'Rune Township'=>'/pic/town/рунее.png', 'Kamael Village'=>'/pic/town/даркелф.png',
'Talking Island Village'=>'/pic/town/хуман.png', 'Godard Town'=>'/pic/town/годарт.png', 'Gludio Village'=>'/pic/town/глудин.png',
'Giran Castle Town'=>'/pic/town/гиран.png', 'Dwarven Village'=>'/pic/town/гном.png', 'Dion Town'=>'/pic/town/дион.png',
'Shtutgart Town'=>'/pic/town/даркелф.png', 'Aden Castle Town'=>'/pic/town/аден.png', 'Casino Royal'=>'/pic/roz/gorod.jpg',
);
if (isset($town_pic[$udata['city']])) {
echo '<div class="lx-view"><img src="'.lin2_ui_h($town_pic[$udata['city']]).'" alt="" onerror="this.parentNode.style.display=\'none\'"/></div>';
}

$gallery =mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery`"), 0);
$blog =mysql_result(mysql_query("SELECT COUNT(*) FROM `blog`"), 0); 

///////////////////////////////////////////////////////////////////////////////////////////////
/////////// Сколько на арене
$timeou = time() - 150;
$arer = 0;
$reqav = mysql_query("SELECT * FROM `mesto` WHERE `place` = 'arena'");
////////////////////////////
if(mysql_num_rows($reqav)>=1)
{
While($arena = mysql_fetch_array($reqav))
{

$asd2 = mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeou' and usr = '$arena[usr]'");
$are = mysql_num_rows($asd2);
$arer = $arer + $are;
}}
////////////////////////////////////////////////////////////////////////////////////////////////

include_once($path . 'inc/quests_promo.php');
$lx_ready_q = !empty($log) ? lin2_qp_ready_quest_count($log, $udata['lvl']) : 0;
$tiles = array(
    array('job.php?', 'quest', 'Квест', $lx_ready_q > 0 ? array('badge' => $lx_ready_q) : array()),
);
if($udata['klas']=="wizard"){$tiles[] = array('akademmag.php?', 'akadem', 'Академия');}
if($udata['klas']=="fighert"){$tiles[] = array('akademwar.php?', 'akadem', 'Академия');}
$tiles[] = array('home.php?', 'clan', 'Клан');
$tiles[] = array('sklad.php?', 'sklad', 'Склад');
$tiles[] = array('hram.php?', 'zags', 'Загс');
$tiles[] = array('gallery.php?', 'foto', 'Фото');
$tiles[] = array('bank.php?', 'bank', 'Банк');
$tiles[] = array('portal.php?', 'portal', 'Телепорт');
$tiles[] = array('arena.php?', 'pvp', 'ПвП', array('cls'=>'hot', 'badge'=>$arer));   // красная бирка = игроки на арене сейчас
$tiles[] = array('pitomec.php?', 'pet', 'Питомец');
$tiles[] = array('baf_vip.php?', 'baf', 'ВИП Баф', array('cls'=>'vip'));
echo lin2_ui_tiles($tiles);

$d=date('YmdHi');
$q=mysql_query("SELECT * FROM `tot_match` WHERE `s1`='nu' and `date` > $d");
$as=mysql_num_rows($q);
if (empty($as)){$as=0;}
//echo''.$pic.'<a href="/total/index.php?">  Тотализатор</a>('.$as.')<br/><br/>';

//----------------------------------------------------------------------//
echo lin2_ui_sub('Куда пойти');
echo lin2_ui_tiles(array(
array('gorod.php?pl=shop_zone', 'g-scales', 'Торговая площадь'),
array('promo.php?', 'g-gift', 'Акции и бонусы'),
array('lotomy.php?', 'g-casino', 'Казино'),
array('to_zamok.php?', 'g-city', 'Замок'),
array('to_world.php?', 'g-world', 'Окрестности'),
array('fish.php?', 'g-fish', 'Озеро'),
));
}else{

echo lin2_ui_place('Торговая площадь города', $udata['city']);

$cbazvip = mysql_num_rows(mysql_query("SELECT * FROM `bazar_vip` WHERE city = '$udata[city]'"));

$cbazwmr = mysql_num_rows(mysql_query("SELECT * FROM `bazar_wmr` WHERE `usr`!='$log'"));
$cbazwmrvi = mysql_num_rows(mysql_query("SELECT * FROM `bazar_wmr` WHERE `usr`='$log'"));
$count_res = mysql_result(mysql_query("SELECT COUNT(*) FROM `sell_res` WHERE `city` = '".$udata['city']."' AND `pers_id` != '".$active."'"),0);

echo lin2_ui_tiles(array(
array('shop.php?', 'g-bag', 'Магазин шмота', array('cls'=>'hot')),
array('shop_lux.php?', 'g-diamond', 'Люкс Магазин'),
array('shop_vip.php?', 'g-crown', 'VIP Shop', array('cls'=>'vip')),
array('shop_gold.php?', 'g-bars', 'Gold Bar'),
array('shopsoul.php?', 'g-crystal', 'SoulShots'),
array('bazar_vip.php?', 'g-scales', 'Базар Шмота', array('badge'=>$cbazvip)),
array('col_bonus.php?', 'g-coin', 'NPC'),
));

echo lin2_ui_go(array(array('gorod.php?', 'Центр города'), array('to_zamok.php?', 'Замок')));

}

////////////////////////
}
include($path.'inc/down.php');
?>
