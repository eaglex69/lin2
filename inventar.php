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




//------------выкунуть вещ-----------------------
if(!empty($_GET['off'])){

$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image`='not' and `id`='$_GET[off]'");
$avto=mysql_num_rows($req);
if($avto==1){
$item = mysql_fetch_array($req);
if (!isset($_GET['yes'])){

echo"<div class=msg><p><span style=color:#f4d06e;>Выкинуть вещь <b>$item[name]</b>?</p></span>";

echo "<div class=inoy><a href=\"inventar.php?mod=item&o=$_GET[o]&off=$item[id]&yes\"><span style=color:#ffffcc;>Да</span></a>";
echo "<a href=\"inventar.php?mod=item&o=$_GET[o]\"><span style=color:#ff4040;>Нет</span></a></div></div>";
}
if (isset($_GET['yes'])){

$time = date("H:i d.m.y");
$text = " [$time] Игрок <a href=\"/search.php?nick=$log&amp;go=go\">$log</a> выкинул вещь <b>$item[name]</b> ";
mysql_query("INSERT INTO `logi` (`id` ,`tip` ,`text` )VALUES (NULL , 'offvesh', '$text')");

mysql_query("DELETE FROM `item` WHERE usr='$log' and `image`='not' and `id`='$_GET[off]'");
    echo"<div class=msg><p><span style=color:#f4d06e;>Вещь <b>$item[name]</b> выкинута!</p></span></div>";
}}}

//---------
//------------ выкинуть ресурс	-----------------
if (!empty($_GET['ofr'])){
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `id`='$_GET[ofr]' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==1){
$res = mysql_fetch_array($req);

if (!isset($_GET['yes'])){

echo"<div class=msg><p><span style=color:#f4d06e;>Выкинуть <b>$res[name]</b>?</p></span>";

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
echo "<div class=inoy><a href=\"inventar.php?mod=res&tip=res&ofr=$res[id]&yes&page=$page\"><span style=color:#ffffcc;>Да</span></a>";
echo "<a href=\"inventar.php?mod=res&tip=res&page=$page\"><span style=color:#ff4040;>Нет</span></a></div></div>";
}


if (isset($_GET['yes'])){
$time = date("H:i d.m.y");
$text = " [$time] Игрок <a href=\"/search.php?nick=$log&amp;go=go\">$log</a> выкинул ресурс <b>$res[name]</b> ";
mysql_query("INSERT INTO `logi` (`id` ,`tip` ,`text` )VALUES (NULL , 'offres', '$text')");

mysql_query("DELETE FROM `res` WHERE usr='$log' and `id`='$_GET[ofr]'");

echo"<div class=msg><p><span style=color:#f4d06e;>Ресурс <b>$res[name]</b> выкинут!</p></span></div>";
}}}






switch($_GET['mod']){

default:
//////считаем вещи/////////
$weapon = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='weapon' and `image`='not'"),0);
$golova = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='golova' and `image`='not'"),0);
$shit = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='shit' and `image`='not'"),0);
$poyas = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='poyas' and `image`='not'"),0);
$rpoyas = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='rpoyas' and `image`='not'"),0);
$plash = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='plash' and `image`='not'"),0);
$ruki = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='ruki' and `image`='not'"),0);
$nogi = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='nogi' and `image`='not'"),0);
$body = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='body' and `image`='not'"),0);
$pants = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='pants' and `image`='not'"),0);

$amulet = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='amulet' and `image`='not'"),0);
$kolco = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='kolco' and `image`='not'"),0);
$rkolco = mysql_result(mysql_query("SELECT COUNT(*) FROM item WHERE usr='$log' and tip='rkolco' and `image`='not'"),0);
$pits = mysql_result(mysql_query("SELECT COUNT(*) FROM pit_item WHERE usr='$log' and `image`='not'"),0);

$shems = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log' and tip='rec'"),0);

$res = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log'"),0);
$souls = mysql_result(mysql_query("SELECT COUNT(*) FROM soulshots WHERE usr='$log'"),0);



echo "<p> <font color=#f4d06e>  Инвентарь: &#160 </b></font>";
if($udata['lvl']>0 and $udata['lvl']<20) echo'Для использования вам доступны вещи NG грейда';
if($udata['lvl']>19 and $udata['lvl']<40) echo'Для использования вам доступны вещи D грейда';
if($udata['lvl']>39 and $udata['lvl']<52) echo'Для использования вам доступны вещи С грейда';
if($udata['lvl']>51 and $udata['lvl']<62) echo'Для использования вам доступны вещи B грейда';
if($udata['lvl']>61 and $udata['lvl']<76) echo'Для использования вам доступны вещи A грейда';
if($udata['lvl']>75 and $udata['lvl']<91) echo'Для использования вам доступны вещи S грейда';
if($udata['lvl']>90 and $udata['lvl']<111) echo'Для использования вам доступны вещи R грейда';
if($udata['lvl']>110) echo'Для использования вам доступны вещи R111 грейда </p> </div><hr/>';
echo "<br/>";







echo "<img src=\"shmot/inventar/66.png\"/><a href=\"pers.php?act=shmot\">Снять вещи</a><br/>";
echo "<br/>";

echo"<img src=\"shmot/inventar/3.png\" alt='шлем'/><a href=\"inventar.php?mod=item&amp;o=golova\">Шлемы</a> <font color=#ffffcc>($golova)</font><br/>";
echo"<img src=\"shmot/inventar/2.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=weapon\">Оружие</a> <font color=#ffffcc>($weapon)</font><br/>";
echo"<img src=\"shmot/inventar/7.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=shit\">Щиты</a> <font color=#ffffcc>	($shit)</font><br/>";
echo"<img src=\"shmot/inventar/5.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=ruki\">Рукавицы</a> <font color=#ffffcc>($ruki)</font><br/>";
echo"<img src=\"shmot/inventar/4.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=body\">Доспехи</a> <font color=#ffffcc>($body)</font><br/>";
echo"<img src=\"shmot/inventar/18.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=pants\">Штаны</a> <font color=#ffffcc>($pants)</font><br/>";
echo"<img src=\"shmot/inventar/6.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=nogi\">Сапоги</a> <font color=#ffffcc>($nogi)</font><br/>";

echo "<hr>";
echo"<img src=\"shmot/inventar/17.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=plash\">Плащи</a> <font color=#ffffcc>($plash)</font><br/>";
echo"<img src=\"shmot/inventar/8.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=amulet\">Амулеты</a> <font color=#ffffcc>($amulet)</font><br/>";
echo"<img src=\"shmot/inventar/9.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=poyas\">Левые серьги</a> <font color=#ffffcc>($poyas)</font><br/>";
echo"<img src=\"shmot/inventar/9.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=rpoyas\">Правые серьги</a> <font color=#ffffcc>($rpoyas)</font><br/>";
echo"<img src=\"shmot/inventar/10.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=kolco\">Левые кольца</a> <font color=#ffffcc>($kolco)</font><br/>";
echo"<img src=\"shmot/inventar/10.png\" alt='оружие'/><a href=\"inventar.php?mod=item&amp;o=rkolco\">Правые кольца</a> <font color=#ffffcc>($rkolco)</font><br/>";


echo "<hr>";
echo"<img src=\"shmot/inventar/13.png\" alt='оружие'/><a href=\"inventar.php?mod=soulshots\">Soulshots</a> <font color=#ffffcc>($souls)</font><br/>";
echo"<img src=\"shmot/inventar/16.png\" alt='оружие'/><a href=\"inventar.php?mod=res\">Ресурсы</a> <font color=#ffffcc>($res)</font><br/>";
echo"<img src=\"shmot/inventar/12.png\" alt='оружие'/><a href=\"inventar.php?mod=shems\">Рецепты</a> <font color=#ffffcc>($shems)</font><br/>";
echo"<img src=\"shmot/inventar/15.png\" alt='оружие'/><a href=\"inventar.php?mod=pits\">Вещи для питомцев</a> <font color=#ffffcc>($pits)</font><br/>";




break;


case 'in':

$req = mysql_query("SELECT * FROM `item` WHERE `id`='$_GET[id]'");
$mag = mysql_fetch_array($req);
$avto=mysql_num_rows($req);

if($avto=="0"){
echo'Нет такой вещи!';
include($path.'inc/down.php');exit;
}


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
case 'pants':
$tip='Штаны';
break;
case 'plash':
$tip='Плащ';
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
echo "<p><font color=#f4d06e>Вещь  игрока $mag[usr]!</font></b></p> ";
$u=explode('*',$mag['name']);
echo"<br/><img src=\"shmot/$u[0].png\" alt=\"pic\"/> <b>$mag[name]</b><br/>
Тип: $tip ";

if ($mag['ruka']=='ydar'){echo"Ударное";}
if ($mag['ruka']=='luk'){echo"Лук";}
if ($mag['ruka']=='kin'){echo"Кинжал";}
if ($mag['ruka']=='sdv'){echo"Сдвоенное";}
if ($mag['ruka']=='kas'){echo"Кастет";}
if ($mag['ruka']=='kniga'){echo"Книга";}
if ($mag['ruka']=='koppik'){echo"Копьё/Пика";}
if ($mag['ruka']=='me4'){echo"Меч";}
if ($mag['ruka']=='rap'){echo"Рапира";}


echo "<hr/>Характеристика</b><br/>";
if ($tip=="Оружие"){
echo "
Физ. атака: $mag[patt]<br/>
Маг. атака: $mag[matt]<br/><br/>

SoulShot: x$mag[soul]<br/>
SpiritShot: x$mag[spirit]<br/><br/>";}else{

echo "
Физ. защ.: $mag[pdef]<br/>
Маг. защ.: $mag[mdef]<br/><br/>";}

break;



case 'shems':
$req = mysql_query("SELECT * FROM res WHERE usr='$log' and tip='rec'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){

echo "<p><font color=#f4d06e>Рецепты</font></p><hr/>";

While($mag = mysql_fetch_array($req))
{
if($mag['unknow']=='0'){$mag['name']='Скрыт';}
$u=explode(': ',$mag['name']);

echo"<div class=inoy> <a href=\"inventar.php?mod=info&amp;act=res&amp;id=$mag[id]\"><img src=\"shmot/$u[1].png\" alt=\"pic\"/> $mag[name]  <font color=#ffffcc>($mag[kol] штук)</font></a></div>";
}
}else{
echo"<b>Нет рецептов</b><br/>";
}
echo"<br/><div class=silka><a href=\"inventar.php?\">Назад</a></div>";
break;
case 'item':

echo "<p> <font color=#f4d06e><b> &#160 Инвентарь: &#160 </b></font> </p> </div><hr/>";

if($_GET['o']!='shit' && $_GET['o']!='golova' && $_GET['o']!='body' && $_GET['o']!='rpoyas' && $_GET['o']!='poyas' && $_GET['o']!='plash' && $_GET['o']!='pants' && $_GET['o']!='ruki' && $_GET['o']!='nogi' && $_GET['o']!='amulet' && $_GET['o']!='kolco' && $_GET['o']!='rkolco' && $_GET['o']!='weapon'){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$_GET[o]' and `image`='not'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
$u=explode('*',$mag['name']);

$cena=round($mag['cena']/4);


echo"<img src=\"shmot/$u[0].png\"  align='left' width='32' height='32' alt='' style='margin-right:10px;border:1px solid #383838'/> <a href=\"inventar.php?mod=info&amp;id=$mag[id]\">$mag[name]</a> <br/>
 [<a href=\"dress.php?tip=$_GET[o]&amp;id=$mag[id]\">Одеть</a>] [<a href=\"inventar.php?mod=item&o=$_GET[o]&off=$mag[id]\">Выкинуть</a>]<hr/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}

echo"<br/><a href=\"inventar.php?\"> Назад</a>";

break;
case 'pits':

echo "<p><font color=grey>Вещи питов</font></p><hr/>";
$weapon = mysql_result(mysql_query("SELECT COUNT(*) FROM pit_item WHERE usr='$log' and tip='weapon' and `image`='not'"),0);
$golova = mysql_result(mysql_query("SELECT COUNT(*) FROM pit_item WHERE usr='$log' and tip='golova' and `image`='not'"),0);
$body = mysql_result(mysql_query("SELECT COUNT(*) FROM pit_item WHERE usr='$log' and tip='body' and `image`='not'"),0);
$nogi = mysql_result(mysql_query("SELECT COUNT(*) FROM pit_item WHERE usr='$log' and tip='nogi' and `image`='not'"),0);
if(empty($_GET['o'])){
echo "<div class=inoy>";
echo"<a href=\"inventar.php?mod=pits&amp;o=weapon\">Оружие <font color=#ffffcc>($weapon)</font></a>";
echo"<a href=\"inventar.php?mod=pits&amp;o=golova\">Шлемы <font color=#ffffcc>($golova)</font></a>";
echo"<a href=\"inventar.php?mod=pits&amp;o=body\">Доспехи <font color=#ffffcc>($body)</font></a>";
echo"<a href=\"inventar.php?mod=pits&amp;o=nogi\">Сапоги <font color=#ffffcc>($nogi)</font></a></div>";
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
echo"<b>$mag[name]</b>
 [<a href=\"inventar.php?mod=infopit&amp;id=$mag[id]\">инфо</a>]<br/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}

}
echo"<a href=\"inventar.php?\">Назад</a>";
break;
case 'infopit':
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
echo"<a href=\"inventar.php?\">Назад</a>";
break;

case'soulshots':

echo "<p>Soulhots</p></div><hr/>";

if($_GET['page']<=0){
		$page = 1;
		}else{
		$page = intval($_GET['page']);
		}
		$from = ($page-1)*10;
$req = mysql_query("SELECT * FROM `soulshots` WHERE `usr` = '$log'");
$avto=mysql_num_rows($req);
            $pages1=$avto/10;
            $pages=round($avto/10);
            if($pages1>$pages){
            $pages=$pages+1;
            }
$wepa = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$magw = mysql_fetch_array($wepa);
$req = mysql_query("SELECT * FROM `soulshots` WHERE `usr` = '$log' and `nlvl` ='$magw[nlvl]' ");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
if ($udata['klas']=="wizard") {$k=2; $name='Spiritshot';}
if ($udata['klas']=="fighert") {$k=1; $name='Soulshot';}
if($mag['nlvl']==0) $gr='NG';
if($mag['nlvl']==20) $gr='D';
if($mag['nlvl']==40) $gr='C';
if($mag['nlvl']==52) $gr='B';
if($mag['nlvl']==62) $gr='A';
if($mag['nlvl']==76) $gr='S';
if($mag['nlvl']==91) $gr='R';
if($mag['nlvl']==111) $gr='R111';
echo"<img src=\"pic/skr/$k/$mag[nlvl].jpeg\" align='left' width='32' height='32' alt='' style='margin-right:10px;border:1px solid #383838'/>$name $gr</a>
 ($mag[kol] штук)";
echo " Для оружия $mag[nlvl] ур.<br/>";
echo " На вас Оружие $magw[nlvl] ур.<br/>";


if($mag['status']==0){
echo" [<a href=\"inventar.php?mod=shot_off\"><font color=#ff4040>Выключить</font></a>]<br/>";
}else{
echo" [<a href=\"inventar.php?mod=shot_on\"><font color=#6fcd72>Включить</font></a>]<br/>";
}
}
echo "<p><center>";
echo "<hr><p>Стр: "; navig2($page, '?mod=res&tip=res&', $pages);
echo "</p></center>";
}else{
echo"<b>Нет Soulshots</b><br/>";
}
break;

case 'shot_off':
$req = mysql_query("SELECT id,name FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo'На вас нету оружия!!!';
}else{
$wepa = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$magw = mysql_fetch_array($wepa);
mysql_query("UPDATE `soulshots` SET `status` = '1' WHERE `usr` = '$log' and `nlvl` = '$magw[nlvl]'");
echo"Изменения сохранены!<br/>";
echo"<a href=\"inventar.php?mod=soulshots\">Продолжить</a>"; 
}
break;

case 'shot_on':
$req = mysql_query("SELECT id,name FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo'На вас нету оружия!!!';
}else{
$wepa = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$magw = mysql_fetch_array($wepa);
mysql_query("UPDATE `soulshots` SET `status` = '1' WHERE `usr` = '$log' and `nlvl` != '$magw[nlvl]'");
mysql_query("UPDATE `soulshots` SET `status` = '0' WHERE `usr` = '$log' and `nlvl` = '$magw[nlvl]'");
echo"Изменения сохранены!<br/>";
echo"<a href=\"inventar.php?mod=soulshots\">Продолжить</a>"; 
}
break;


case 'res':
if(empty($_GET['tip'])){
///
$res = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log' and tip='res'"),0);
$elexir = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log' and tip='elexir'"),0);
$scroll = mysql_result(mysql_query("SELECT COUNT(*) FROM res WHERE usr='$log' and tip='scroll'"),0);
///
echo "<p> <font color=#f4d06e> &#160 Ресурсы &#160 </font> </p> <hr/>";
echo "<div class=inoy>";

echo"<a href=\"inventar.php?mod=res&amp;tip=res\">Ресурсы <font color=#ffffcc>($res)</font></a> ";
echo"<a href=\"inventar.php?mod=res&amp;tip=elexirs\">Элексиры <font color=#ffffcc>($elexir)</font></a> ";
echo"<a href=\"inventar.php?mod=res&amp;tip=scroll\">Свитки <font color=#ffffcc>($scroll)</font></a> </div>";
}elseif($_GET['tip']=='res'){

echo "<p> 	 Ресурсы	</p></div><hr/>";



//---выщитует страницы----------
   if($_GET['page']<=0){
		$page = 1;
		}else{
		$page = intval($_GET['page']);
		}
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
echo"<img src=\"pic/skr/$mag[name].gif\"  align='left' width='32' height='32' alt='' style='margin-right:10px;border:1px solid #383838'/>  <a href=\"inventar.php?mod=info&amp;act=res&amp;id=$mag[id]\">$mag[name]</a>
 ($mag[kol] штук)<br/> [<a href=\"inventar.php?mod=res&tip=res&ofr=$mag[id]&page=$page\">Выкинуть всё</a>] <hr/>";
}

echo "<p><center>";

echo "</div><hr><p>Стр: "; navig2($page, '?mod=res&tip=res&', $pages);



echo "</p></center>";


}else{
echo"<b>Нет ресурсов</b><br/>";
}
}elseif($_GET['tip']=='elexirs'){
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `tip`='elexir'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){

echo "<form action=\"elexirs.php?\" method=\"POST\">";

While($mag = mysql_fetch_array($req))
{
echo" <img src=\"pic/skr/$mag[name].gif\" alt=\"*\"/>";
echo " <label><input type='radio' name='result' value='$mag[id]' /></label>\n";
echo "<a href=\"inventar.php?mod=info&amp;act=elexir&amp;id=$mag[id]\">$mag[name]</a>";
echo "<br/> &#160   ($mag[kol] штук) <hr/>";

}
echo "<div class=menu><b><font color=grey>Введите количество:</font></b><br/>
<input type=\"text\" name=\"col\" size=\"5\" maxlength=\"9\"/> ";

echo "<input type='submit' name='ok' value='Использовать' /><hr/></div>\n";

}else{
echo"<b>Нет элексиров</b><br/>";
}
}elseif($_GET['tip']=='scroll'){
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `tip`='scroll'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<a href=\"inventar.php?mod=info&amp;act=scroll&amp;id=$mag[id]\">$mag[name]</a>
 ($mag[kol] штук) [<a href=\"scroll.php?id=$mag[id]\">использовать</a>]<br/>";
}
}else{
echo"<b>Нет свитков</b><br/>";
}
}else{
echo"<b>Ошибка, доступ закрыт!</b><br/>";
}
echo"<br/><a href=\"inventar.php?\">Назад</a>";
break;
case 'info':
if($_GET['act']=='res' or $_GET['act']=='elexir' or $_GET['act']=='scroll'){
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
switch($mag['tip']){
case 'res':
$tip='Ресурс';
break;
case 'rec':
$tip='Рецепт';
break;
case 'scroll':
$tip='Свиток';
break;
case 'elexir':
$tip='Элексир';
break;
}
echo "<p><font color=#f4d06e> Инф. ресурса </font></p><hr/>";
echo"<b> $mag[name]</b><br/>
Тип: $tip<br/>
Количество: $mag[kol]<br/>
";


//если фестиваль адена обменять
if ($mag['lat_name'] == "fish"){echo "<div class=inoy><a href=\"fish_r.php\">Разделать</a></div>";}
if ($mag['name'] == "Сундук удачи"){echo "<div class=inoy><a href=\"open_s.php\">Открыть</a></div>";}
//если фестиваль адена обменять
if ($mag['lat_name'] == "FestivalAdena"){echo "<div class=inoy><a href=\"obm_fs_ad.php\">Обменять</a></div>";}
//////////////////////////////
//если гном крафтер выучить
if ($mag['tip']=='rec'){
if ($udata['prof']=='Artisan' or $udata['prof']=='Warsmith'  or $udata['prof']=='Maestro'){ // доступ по профам
echo "<div class=inoy><a href=\"workshop.php?mod=go&go=$mag[id]\">Записать в книгу</a></div>";
}}

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
$req = mysql_query("SELECT * FROM `item` WHERE `id`='$_GET[id]'");
}else{
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `id`='$_GET[id]'");}

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
$u=explode('*',$mag['name']);
echo"<br/><img src=\"shmot/$u[0].png\" alt=\"pic\"/> <b>$mag[name]</b><br/>
Тип: $tip<hr/>

<b>Характеристика</b><br/>";
echo"<br/>";
echo "Грейд:";
if($mag['nlvl']>0 and $mag['nlvl']<20) echo' NG (0 уровень) ';
if($mag['nlvl']>19 and $mag['nlvl']<40) echo' D (20 уровень)';
if($mag['nlvl']>39 and $mag['nlvl']<52) echo' С (40 уровень)';
if($mag['nlvl']>51 and $mag['nlvl']<62) echo' B (52 уровень)';
if($mag['nlvl']>61 and $mag['nlvl']<76) echo' A (62 уровень)';
if($mag['nlvl']>75 and $mag['nlvl']<91) echo' S (76 уровень)';
if($mag['nlvl']>90 and $mag['nlvl']<111) echo' R (91 уровень)';
if($mag['nlvl']>110) echo' R111 (111 уровень)';
echo"<br/>";
echo"<br/>";
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

echo"<div class=silka><a href=\"inventar.php?\">Назад</a></div>";
break;
case 'aura':
$req = mysql_query("SELECT * FROM `item_aura` WHERE `usr` = '$log'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<a href=\"inventar.php?mod=info&amp;act=aura&amp;id=$mag[id]\">$mag[name]</a>
 ($mag[kol] штук) [<a href=\"aura.php?id=$mag[id]\">вкл</a>]<br/>";
}
}else{
echo"<b>Нет аур</b><br/>";
}
break;
}
include($path.'inc/down.php');
?>