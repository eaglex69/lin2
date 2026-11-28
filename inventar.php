<?php
define('PROTECTOR', 1);

$headmod = 'inventar';//фикс. места

$textl='Инвентарь';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
include_once $path.'inc/lin2_ui.php';




//------------выкунуть вещ-----------------------
if(!empty($_GET['off'])){

$off = (int)$_GET['off'];
$o_back = preg_replace('/[^a-z]/', '', isset($_GET['o']) ? (string)$_GET['o'] : '');
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image`='not' and `id`='$off'");
$avto=mysql_num_rows($req);
if($avto==1){
$item = mysql_fetch_array($req);
if (!isset($_GET['yes'])){

echo lin2_ui_confirm('Выкинуть вещь '.$item['name'].'?', 'inventar.php?mod=item&o='.$o_back.'&off='.(int)$item['id'].'&yes', 'inventar.php?mod=item&o='.$o_back);
}
if (isset($_GET['yes'])){

$time = date("H:i d.m.y");
$text = " [$time] Игрок <a href=\"/search.php?nick=$log&amp;go=go\">$log</a> выкинул вещь <b>$item[name]</b> ";
mysql_query("INSERT INTO `logi` (`id` ,`tip` ,`text` )VALUES (NULL , 'offvesh', '$text')");

mysql_query("DELETE FROM `item` WHERE usr='$log' and `image`='not' and `id`='$off'");
    echo lin2_ui_note('Вещь <b>'.lin2_ui_h($item['name']).'</b> выкинута!');
}}}

//---------
//------------ выкинуть ресурс	-----------------
if (!empty($_GET['ofr'])){
$ofr = (int)$_GET['ofr'];
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `id`='$ofr' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==1){
$res = mysql_fetch_array($req);

if (!isset($_GET['yes'])){

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
echo lin2_ui_confirm('Выкинуть '.$res['name'].'?', 'inventar.php?mod=res&tip=res&ofr='.(int)$res['id'].'&yes&page='.$page, 'inventar.php?mod=res&tip=res&page='.$page);
}


if (isset($_GET['yes'])){
$time = date("H:i d.m.y");
$text = " [$time] Игрок <a href=\"/search.php?nick=$log&amp;go=go\">$log</a> выкинул ресурс <b>$res[name]</b> ";
mysql_query("INSERT INTO `logi` (`id` ,`tip` ,`text` )VALUES (NULL , 'offres', '$text')");

mysql_query("DELETE FROM `res` WHERE usr='$log' and `id`='$ofr'");

echo lin2_ui_note('Ресурс <b>'.lin2_ui_h($res['name']).'</b> выкинут!');
}}}






if (!isset($_GET['mod'])) {$_GET['mod'] = '';}
switch($_GET['mod']){

default:
//////считаем вещи/////////
$cnt = array('weapon'=>0, 'golova'=>0, 'body'=>0, 'pants'=>0, 'nogi'=>0, 'ruki'=>0, 'shit'=>0, 'plash'=>0, 'amulet'=>0,
    'poyas'=>0, 'rpoyas'=>0, 'kolco'=>0, 'rkolco'=>0);
$rq = mysql_query("SELECT `tip`, COUNT(*) AS n FROM item WHERE usr='$log' and `image`='not' GROUP BY `tip`");
while ($rw = mysql_fetch_array($rq)) {
if (isset($cnt[$rw['tip']])) {$cnt[$rw['tip']] = (int)$rw['n'];}
}
$pits = mysql_result(mysql_query("SELECT COUNT(*) FROM pit_item WHERE usr='$log' and `image`='not'"),0);

$shems = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log' and tip='rec'"),0);

$res = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log'"),0);
$souls = mysql_result(mysql_query("SELECT COUNT(*) FROM soulshots WHERE usr='$log'"),0);

// грейд, вещи которого доступны игроку по уровню
$lv = (int)$udata['lvl'];
$gr_user = ($lv > 110) ? 'R111' : lin2_ui_grade($lv);
echo lin2_ui_note('<b>Инвентарь.</b> Для использования вам доступны вещи '.$gr_user.' грейда.');

// плитки по группам: экипировка, плащи и украшения, расходное. Красная бирка = сколько вещей в сумке.
$groups = array(
'Экипировка' => array('weapon'=>'g-weapon', 'golova'=>'g-golova', 'body'=>'g-body', 'pants'=>'g-pants', 'nogi'=>'g-nogi', 'ruki'=>'g-ruki', 'shit'=>'g-shit'),
'Плащи и украшения' => array('plash'=>'g-plash', 'amulet'=>'g-amulet', 'poyas'=>'g-earring', 'rpoyas'=>'g-earring', 'kolco'=>'g-ring', 'rkolco'=>'g-ring'),
);
foreach ($groups as $title => $list) {
$tiles = array();
$sum = 0;
foreach ($list as $t => $g) {
$sum += $cnt[$t];
$tiles[] = array('inventar.php?mod=item&o='.$t, $g, lin2_ui_tip_name($t, true), array('badge'=>$cnt[$t], 'cls'=>$cnt[$t] ? '' : 'zero'));
}
echo lin2_ui_sub($title, $sum.' в сумке');
echo lin2_ui_tiles($tiles, 'sm');
}
echo lin2_ui_btn('pers.php?act=shmot', 'Снять вещи', 'red', 'full');

echo lin2_ui_sub('Расходное и прочее');
echo lin2_ui_tiles(array(
array('inventar.php?mod=soulshots', 'g-crystal', 'Soulshots', array('badge'=>$souls, 'cls'=>$souls ? '' : 'zero')),
array('inventar.php?mod=res', 'g-ore', 'Ресурсы', array('badge'=>$res, 'cls'=>$res ? '' : 'zero')),
array('inventar.php?mod=shems', 'g-scroll', 'Рецепты', array('badge'=>$shems, 'cls'=>$shems ? '' : 'zero')),
array('inventar.php?mod=pits', 'g-paw', 'Вещи питомцев', array('badge'=>$pits, 'cls'=>$pits ? '' : 'zero')),
), 'sm');

break;


case 'in':

$req = mysql_query("SELECT * FROM `item` WHERE `id`='".(int)(isset($_GET['id']) ? $_GET['id'] : 0)."'");
$mag = mysql_fetch_array($req);
$avto=mysql_num_rows($req);

if($avto=="0"){
echo lin2_ui_note('Нет такой вещи!');
include($path.'inc/down.php');exit;
}

echo lin2_ui_sub('Вещь игрока '.$mag['usr']);
echo lin2_ui_item_sheet($mag);

break;



case 'shems':
$req = mysql_query("SELECT * FROM res WHERE usr='$log' and tip='rec'");
////////////////////////////
$avto=mysql_num_rows($req);
echo lin2_ui_sub('Рецепты', $avto.' шт.');
if($avto>=1){

While($mag = mysql_fetch_array($req))
{
if(isset($mag['unknow']) && $mag['unknow']=='0'){$mag['name']='Скрыт';}
$u=explode(': ',$mag['name']);

echo lin2_ui_thing_row($mag['name'], 'inventar.php?mod=info&act=res&id='.(int)$mag['id'], 'g-scroll', isset($u[1]) ? '/shmot/'.rawurlencode($u[1]).'.png' : '', (int)$mag['kol'].' шт.');
}
}else{
echo lin2_ui_empty('Нет рецептов');
}
echo lin2_ui_btn('inventar.php?', 'Назад', 'grey', 'full');
break;
case 'item':

$o = (isset($_GET['o']) && is_string($_GET['o'])) ? $_GET['o'] : '';
if(!in_array($o, array('shit','golova','body','rpoyas','poyas','plash','pants','ruki','nogi','amulet','kolco','rkolco','weapon'), true)){
echo lin2_ui_note('Ошибка!');
include($path.'inc/down.php');
exit;
}
// лучшие вещи сверху: по грейду, внутри грейда по виду и имени; у каждого грейда свой заголовок
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$o' and `image`='not' ORDER BY `nlvl` DESC, `ruka`, `name`, `id`");
////////////////////////////
$avto=mysql_num_rows($req);
echo lin2_ui_sub(lin2_ui_tip_name($o, true), $avto.' шт.');
if($avto>=1){
$items = array();
$per_grade = array();
While($mag = mysql_fetch_array($req))
{
$items[] = $mag;
$g = lin2_ui_grade($mag['nlvl']);
$per_grade[$g] = isset($per_grade[$g]) ? $per_grade[$g] + 1 : 1;
}
$last = '';
foreach ($items as $mag) {
$g = lin2_ui_grade($mag['nlvl']);
if ($g !== $last && count($per_grade) > 1) {
echo '<div class="lx-grp">'.lin2_ui_grade_mark($mag['nlvl']).'<b>'.$g.' грейд</b><em>'.$per_grade[$g].' шт.</em></div>';
}
$last = $g;

echo lin2_ui_item_row($mag, 'inventar.php?mod=info&id='.(int)$mag['id'], array(
array('dress.php?tip='.$o.'&id='.(int)$mag['id'], 'Одеть'),
array('inventar.php?mod=item&o='.$o.'&off='.(int)$mag['id'], 'Выкинуть', 'red'),
));
}
}else{
echo lin2_ui_empty('Нет вещей');
}

echo lin2_ui_btn('inventar.php?', 'Назад', 'grey', 'full');

break;
case 'pits':

$pit_names = array('weapon'=>'Оружие', 'golova'=>'Шлемы', 'body'=>'Доспехи', 'nogi'=>'Сапоги');
$pit_glyph = array('weapon'=>'g-weapon', 'golova'=>'g-golova', 'body'=>'g-body', 'nogi'=>'g-nogi');
$pc = array('weapon'=>0, 'golova'=>0, 'body'=>0, 'nogi'=>0);
$rq = mysql_query("SELECT `tip`, COUNT(*) AS n FROM pit_item WHERE usr='$log' and `image`='not' GROUP BY `tip`");
while ($rw = mysql_fetch_array($rq)) {
if (isset($pc[$rw['tip']])) {$pc[$rw['tip']] = (int)$rw['n'];}
}
$po = (isset($_GET['o']) && is_string($_GET['o'])) ? $_GET['o'] : '';
if($po === ''){
echo lin2_ui_sub('Вещи питомцев');
$tiles = array();
foreach ($pit_names as $t => $title) {
$tiles[] = array('inventar.php?mod=pits&o='.$t, $pit_glyph[$t], $title, array('badge'=>$pc[$t], 'cls'=>$pc[$t] ? '' : 'zero'));
}
echo lin2_ui_tiles($tiles, 'sm');
echo lin2_ui_btn('inventar.php?', 'Назад', 'grey', 'full');
}else{
if(!isset($pit_names[$po])){
echo lin2_ui_note('Ошибка!');
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `pit_item` WHERE `usr` = '$log' and `tip`='$po' and `image`='not'");
////////////////////////////
$avto=mysql_num_rows($req);
echo lin2_ui_sub($pit_names[$po], $avto.' шт.');
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
$pi = 'inventar.php?mod=infopit&id='.(int)$mag['id'];
echo lin2_ui_thing_row($mag['name'], $pi, $pit_glyph[$po], '', 'Урон '.(int)$mag['sila'].' · Защита '.(int)$mag['prot'].' · Жизнь '.(int)$mag['hp'], array(array($pi, 'Инфо')));
}
}else{
echo lin2_ui_empty('Нет вещей');
}

echo lin2_ui_btn('inventar.php?mod=pits', 'Назад', 'grey', 'full');
}
break;
case 'infopit':
$req = mysql_query("SELECT * FROM `pit_item` WHERE `usr` = '$log' and `id`='".(int)(isset($_GET['id']) ? $_GET['id'] : 0)."'");

$avto=mysql_num_rows($req);
if($avto==0){
echo lin2_ui_note('Ошибка!');
include($path.'inc/down.php');
exit;
}

$mag = mysql_fetch_array($req);
$pit_tip = array('weapon'=>'Оружие', 'body'=>'Доспехи', 'golova'=>'Шлем', 'nogi'=>'Сапоги');
$pit_rasa = array('not'=>'Все', 'wolf'=>'Волк', 'dog'=>'Собака');
$tip = isset($pit_tip[$mag['tip']]) ? $pit_tip[$mag['tip']] : '';
$rasa = isset($pit_rasa[$mag['rasa']]) ? $pit_rasa[$mag['rasa']] : '';
echo lin2_ui_sheet($mag['name'], $tip, array(
array('Урон', $mag['sila']), array('Защита', $mag['prot']), array('Жизнь', $mag['hp']),
array('Раса', $rasa), array('Уровень', $mag['nlvl']),
), array(array('inventar.php?mod=pits&o='.$mag['tip'], 'Назад', 'grey')), '<span class="lx-ic big g-paw"></span>');
break;

case'soulshots':

echo lin2_ui_sub('Soulshots');

$wepa = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$magw = mysql_fetch_array($wepa);
$wnl = $magw ? (int)$magw['nlvl'] : 0;
$req = mysql_query("SELECT * FROM `soulshots` WHERE `usr` = '$log' and `nlvl` ='$wnl' ");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
$k=1; $name='Soulshot';
if ($udata['klas']=="wizard") {$k=2; $name='Spiritshot';}
if ($udata['klas']=="fighert") {$k=1; $name='Soulshot';}
$gr = ($mag['nlvl']==111) ? 'R111' : lin2_ui_grade($mag['nlvl']);
$toggle = ($mag['status']==0) ? array('inventar.php?mod=shot_off', 'Выключить', 'red') : array('inventar.php?mod=shot_on', 'Включить');
echo lin2_ui_thing_row($name.' '.$gr, '', 'g-crystal', '/pic/skr/'.$k.'/'.(int)$mag['nlvl'].'.jpeg',
(int)$mag['kol'].' шт. · для оружия '.(int)$mag['nlvl'].' ур. · на вас оружие '.$wnl.' ур.', array($toggle));
}
}else{
echo lin2_ui_empty('Нет Soulshots');
}
echo lin2_ui_btn('inventar.php?', 'Назад', 'grey', 'full');
break;

case 'shot_off':
$req = mysql_query("SELECT id,name FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo lin2_ui_note('На вас нету оружия!!!');
}else{
$wepa = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$magw = mysql_fetch_array($wepa);
mysql_query("UPDATE `soulshots` SET `status` = '1' WHERE `usr` = '$log' and `nlvl` = '$magw[nlvl]'");
echo lin2_ui_note('Изменения сохранены!');
echo lin2_ui_go(array(array('inventar.php?mod=soulshots', 'Продолжить')));
}
break;

case 'shot_on':
$req = mysql_query("SELECT id,name FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo lin2_ui_note('На вас нету оружия!!!');
}else{
$wepa = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$magw = mysql_fetch_array($wepa);
mysql_query("UPDATE `soulshots` SET `status` = '1' WHERE `usr` = '$log' and `nlvl` != '$magw[nlvl]'");
mysql_query("UPDATE `soulshots` SET `status` = '0' WHERE `usr` = '$log' and `nlvl` = '$magw[nlvl]'");
echo lin2_ui_note('Изменения сохранены!');
echo lin2_ui_go(array(array('inventar.php?mod=soulshots', 'Продолжить')));
}
break;


case 'res':
$tipr = (isset($_GET['tip']) && is_string($_GET['tip'])) ? $_GET['tip'] : '';
$back = 'inventar.php?mod=res';
if($tipr === ''){
///
$res = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log' and tip='res'"),0);
$elexir = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log' and tip='elexir'"),0);
$scroll = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log' and tip='scroll'"),0);
///
echo lin2_ui_sub('Ресурсы');
echo lin2_ui_tiles(array(
array('inventar.php?mod=res&tip=res', 'g-ore', 'Ресурсы', array('badge'=>$res, 'cls'=>$res ? '' : 'zero')),
array('inventar.php?mod=res&tip=elexirs', 'g-potion', 'Элексиры', array('badge'=>$elexir, 'cls'=>$elexir ? '' : 'zero')),
array('inventar.php?mod=res&tip=scroll', 'g-scroll', 'Свитки', array('badge'=>$scroll, 'cls'=>$scroll ? '' : 'zero')),
));
$back = 'inventar.php?';
}elseif($tipr=='res'){

echo lin2_ui_sub('Ресурсы');



//---выщитует страницы----------
   $page = (isset($_GET['page']) && intval($_GET['page']) > 0) ? intval($_GET['page']) : 1;
		$from = ($page-1)*10;

		        $avto = mysql_num_rows(mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `tip`='res'"));
            $pages1=$avto/10;
            $pages=round($avto/10);
            if($pages1>$pages){
            $pages=$pages+1;
            }

//-------------------------------


$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `tip`='res' LIMIT $from,10");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo lin2_ui_thing_row($mag['name'], 'inventar.php?mod=info&act=res&id='.(int)$mag['id'], 'g-ore', '/pic/skr/'.rawurlencode($mag['name']).'.gif',
(int)$mag['kol'].' шт.', array(array('inventar.php?mod=res&tip=res&ofr='.(int)$mag['id'].'&page='.$page, 'Выкинуть всё', 'red')));
}

echo lin2_ui_pager($page, $pages, 'inventar.php?mod=res&tip=res&page=');


}else{
echo lin2_ui_empty('Нет ресурсов');
}
}elseif($tipr=='elexirs'){
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `tip`='elexir'");
////////////////////////////
$avto=mysql_num_rows($req);
echo lin2_ui_sub('Элексиры');
if($avto>=1){

echo "<form action=\"elexirs.php?\" method=\"POST\">";

While($mag = mysql_fetch_array($req))
{
echo lin2_ui_thing_row($mag['name'], 'inventar.php?mod=info&act=elexir&id='.(int)$mag['id'], 'g-potion', '/pic/skr/'.rawurlencode($mag['name']).'.gif',
(int)$mag['kol'].' шт.', array(), '<label class="lx-rd"><input type="radio" name="result" value="'.(int)$mag['id'].'" /></label>');

}
echo "<div class=\"lx-sheet\"><b>Введите количество:</b><br/>
<input type=\"text\" name=\"col\" size=\"5\" maxlength=\"9\"/> 
<input type='submit' name='ok' value='Использовать' /></div></form>\n";

}else{
echo lin2_ui_empty('Нет элексиров');
}
}elseif($tipr=='scroll'){
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `tip`='scroll'");
////////////////////////////
$avto=mysql_num_rows($req);
echo lin2_ui_sub('Свитки');
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo lin2_ui_thing_row($mag['name'], 'inventar.php?mod=info&act=scroll&id='.(int)$mag['id'], 'g-scroll', '', (int)$mag['kol'].' шт.',
array(array('scroll.php?id='.(int)$mag['id'], 'Использовать')));
}
}else{
echo lin2_ui_empty('Нет свитков');
}
}else{
echo lin2_ui_note('<b>Ошибка, доступ закрыт!</b>');
$back = 'inventar.php?';
}
echo lin2_ui_btn($back, 'Назад', 'grey', 'full');
break;
case 'info':
$back = 'inventar.php?';
$gid = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
if(isset($_GET['act']) and ($_GET['act']=='res' or $_GET['act']=='elexir' or $_GET['act']=='scroll')){
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `id`='$gid'");
$avto=mysql_num_rows($req);
if($avto==0){
echo lin2_ui_note('Ошибка!');
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
$tip_names = array('res'=>'Ресурс', 'rec'=>'Рецепт', 'scroll'=>'Свиток', 'elexir'=>'Элексир');
$tip = isset($tip_names[$mag['tip']]) ? $tip_names[$mag['tip']] : '';
$acts = array();

//если фестиваль адена обменять
if ($mag['lat_name'] == "fish"){$acts[] = array('fish_r.php', 'Разделать');}
if ($mag['name'] == "Сундук удачи"){$acts[] = array('open_s.php', 'Открыть');}
//если фестиваль адена обменять
if ($mag['lat_name'] == "FestivalAdena"){$acts[] = array('obm_fs_ad.php', 'Обменять');}
//////////////////////////////
//если гном крафтер выучить
if ($mag['tip']=='rec'){
if ($udata['prof']=='Artisan' or $udata['prof']=='Warsmith'  or $udata['prof']=='Maestro'){ // доступ по профам
$acts[] = array('workshop.php?mod=go&go='.(int)$mag['id'], 'Записать в книгу');
}}
$glyph = ($mag['tip']=='scroll' || $mag['tip']=='rec') ? 'g-scroll' : ($mag['tip']=='elexir' ? 'g-potion' : 'g-ore');
echo lin2_ui_sheet($mag['name'], $tip, array(array('Количество', $mag['kol'])), $acts,
'<span class="lx-ic big '.$glyph.'"><img src="/pic/skr/'.lin2_ui_h(rawurlencode($mag['name'])).'.gif" alt=""/></span>');

}elseif($_GET['act']=='shems'){
$req = mysql_query("SELECT * FROM `shems` WHERE `usr` = '$log' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

if($mag['unknow']=='0'){$mag['name']='Скрыт';}

echo'Схема: <b>'.$mag['name'].'</b><br/>';
echo'Количество '.$mag['kol'].'<br/>';
echo'Цена создания: '.$mag['scena'].'<br/>';
echo'Ресурсы: <br/>';
$res=explode("|",$mag['nead_res']);
$kres=count($res);
$i=0;
$b=1;
while($b<=$kres){
$r=explode(",",$res[$i]);
$lat_name=$r[0];
$kol=$r[1];

$req = mysql_query("SELECT name FROM `mdrop` WHERE `lat_name`='$lat_name' LIMIT 1");
$reso = mysql_fetch_array($req);
echo"$reso[name] - $kol штук<br/>";
$i++;
$b++;
}
if($mag['unknow']=='1'){

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
case 'shit':
$tip='Щит';
break;
case 'poyas':
$tip='Левая серьга';
break;
case 'rpoyas':
$tip='Правая серьга';
break;
case 'plash':
$tip='Плащ';
break;
case 'pants':
$tip='Штаны';
break;
case 'ruki':
$tip='Рукавицы';
break;
case 'kolco':
$tip='Левое кольцо';
break;
case 'rkolco':
$tip='Правое кольцо';
break;
case 'amulet':
$tip='Амулет';
break;
}

$u=explode('*',$mag['name']);
echo"<br/><img src=\"shmot/$u[0].png\" alt=\"pic\"/> <b>$mag[name]</b><br/>";

echo"Тип: $tip<br/>
<b>Характеристика</b><hr/>";
echo "Грейд:";
if(!empty($mag['nlvl'])) echo' NG ';
if($mag['nlvl']>19 and $mag['nlvl']<40) echo' D ';
if($mag['nlvl']>39 and $mag['nlvl']<52) echo' С ';
if($mag['nlvl']>51 and $mag['nlvl']<62) echo' B ';
if($mag['nlvl']>61 and $mag['nlvl']<76) echo' A ';
if($mag['nlvl']>75 and $mag['nlvl']<91) echo' S ';
if($mag['nlvl']>90 and $mag['nlvl']<111) echo' R ';
if($mag['nlvl']>110) echo' R111 ';
if ($tip=="Оружие"){
echo "
Физ. атака: $mag[patt]<br/>
Маг. атака: $mag[matt]<br/><br/>

SoulShot: x$mag[soul]<br/>
SpiritShot: x$mag[spirit]<br/><br/>";}else{

echo "
Физ. защ.: $mag[pdef]<br/>
Маг. защ.: $mag[mdef]<br/><br/>";}
}


}elseif($_GET['act']=='aura'){
$req = mysql_query("SELECT * FROM `item_aura` WHERE `usr` = '$log' and `id`='$_GET[id]'");

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

echo"<b>$mag[name]</b><br/>
Урон: $mag[umin]/$mag[umax]<br/>
Физ. защита: $mag[protect]<br/>
Жизнь: $mag[hp]<br/>
Мана: $mag[mp]<br/>
Крит: $mag[krit]%<br/>
Крит-урон: $mag[ukrit]%<br/>
Анти-крит: $mag[antikrit]%<br/>
<b>Требования:</b><br/>
Класс: $klas<br/>
Сила: $mag[nsila]<br/>
Ловкость: $mag[nlovk]<br/>
Интелект: $mag[num]<br/>
Уровень: $mag[nlvl]<br/>
Время действия: $mag[actime] сек.<br/>
";
}
else{
if ($udata['dostup']>=4){
$req = mysql_query("SELECT * FROM `item` WHERE `id`='$gid'");
}else{
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `id`='$gid'");}

$avto=mysql_num_rows($req);
if($avto==0){
echo lin2_ui_note('Ошибка!');
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
$acts = array();
if ($mag['usr']==$log && $mag['image']=='not') { // вещь в сумке: сразу одеть или выкинуть, назад - в её отдел
$acts[] = array('dress.php?tip='.$mag['tip'].'&id='.(int)$mag['id'], 'Одеть');
$acts[] = array('inventar.php?mod=item&o='.$mag['tip'].'&off='.(int)$mag['id'], 'Выкинуть', 'red');
$back = 'inventar.php?mod=item&o='.$mag['tip'];
}
echo lin2_ui_item_sheet($mag, array('acts' => $acts));
}

echo lin2_ui_btn($back, 'Назад', 'grey', 'full');
break;
case 'aura':
$req = mysql_query("SELECT * FROM `item_aura` WHERE `usr` = '$log'");
////////////////////////////
$avto=mysql_num_rows($req);
echo lin2_ui_sub('Ауры', $avto.' шт.');
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo lin2_ui_thing_row($mag['name'], 'inventar.php?mod=info&act=aura&id='.(int)$mag['id'], 'g-crystal', '', (int)$mag['kol'].' шт.',
array(array('aura.php?id='.(int)$mag['id'], 'Включить')));
}
}else{
echo lin2_ui_empty('Нет аур');
}
break;
}
include($path.'inc/down.php');
?>