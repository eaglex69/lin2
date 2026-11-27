<?php
define('PROTECTOR', 1);

$headmod = 'shop_chat';//фикс. места

$textl='Чат Магазин';
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

$tip2=$_GET['tip2'];
$lvlus=abs(intval($_GET['lvlus']));
$o=isset($_GET['o'])?$_GET['o']:'';



$case=htmlspecialchars(trim($_GET['mod']));
switch($case){

default:
echo"</div></div> <div class=menu> <font color=grey>Решил прикупиться? <br/>Выбирай что нужно: </font><br/><br/></div>";
echo "<div class=inoy>";
echo"<a href=\"shop_chat.php?mod=weapon\">Магазин оружия</a>";
echo"<a href=\"shop_chat.php?mod=b\">Магазин брони</a>";
echo "</div>";
break;

case 'w':
echo"<a href=\"shop_chat.php?mod=weapon\">Отдел оружия</a><br/>";
echo"<a href=\"shop_chat.php?\">Назад</a>";
break;

case 'b':
if(empty($_GET['o'])){
echo"<font color=grey> Хах...Защита важная часть!!! </font><br/><br/>";

echo "<div class=inoy>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=grocery\"> Украшения</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=shit\"> Щиты</a>"; /* fgkhjfdgdkj */
echo"<a href=\"shop_chat.php?mod=b&amp;o=golova\"> Шлемы</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=body\"> Доспехи</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=amulet\"> Амулеты</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=kolco\">Левые кольца</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=rkolco\">Правые кольца</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=poyas\">Левые серьги</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=rpoyas\">Правые серьги</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=plash\"> Отдел плащей</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=pants\"> Отдел штанов</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=ruki\"> Рукавицы</a>";
echo"<a href=\"shop_chat.php?mod=b&amp;o=nogi\"> Сапоги</a>";


echo"<br/><a href=\"shop_chat.php?\">Назад</a>";
echo "</div>";

}else{
if($_GET['o']!='grocery' && $_GET['o']!='shit' && $_GET['o']!='golova' && $_GET['o']!='body' && $_GET['o']!='poyas' && $_GET['o']!='rpoyas' && $_GET['o']!='kolco' && $_GET['o']!='rkolco' && $_GET['o']!='amulet' && $_GET['o']!='plash' && $_GET['o']!='ruki' && $_GET['o']!='nogi' && $_GET['o']!='pants'){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$req =  mysql_query("SELECT * FROM `shop_chat` WHERE `city` != '' and `tip`='".$_GET['o']."' and `nlvl`='".$lvlus."' ORDER BY cena");
////////////////////////////

echo"<b>Классы:</b> | ";
if ($lvlus==0){echo"NG | ";}else{ echo"<a href=\"shop_chat.php?mod=b&lvlus=0&o=$_GET[o]\">NG</a> | ";}
if ($lvlus==20){echo"D | ";}else{ echo"<a href=\"shop_chat.php?mod=b&lvlus=20&o=$_GET[o]\">D</a> | ";}
if ($lvlus==40){echo"C | ";}else{ echo"<a href=\"shop_chat.php?mod=b&lvlus=40&o=$_GET[o]\">C</a> | ";}
if ($lvlus==52){echo"B | ";}else{ echo"<a href=\"shop_chat.php?mod=b&lvlus=52&o=$_GET[o]\">B</a> | ";}
if ($lvlus==62){echo"A | ";}else{ echo"<a href=\"shop_chat.php?mod=b&lvlus=62&o=$_GET[o]\">A</a> | ";}
if ($lvlus==76){echo"S | ";}else{ echo"<a href=\"shop_chat.php?mod=b&lvlus=76&o=$_GET[o]\">S</a> | ";}
if ($lvlus==91){echo"R | ";}else{ echo"<a href=\"shop_chat.php?mod=b&lvlus=91&o=$_GET[o]\"> R </a> |";}
if ($lvlus==111){echo"R111<br/>";}else{ echo"<a href=\"shop_chat.php?mod=b&lvlus=111&o=$_GET[o]\"> R111 </a> <br/>";}

if (empty($lvlus)) {$lvlus = '0';}

if($lvlus<20){$st="NG";}
elseif($lvlus>=20 and $lvlus<40){$st="D";}
elseif($lvlus>=40 and $lvlus<52){$st="C";}
elseif($lvlus>=52 and $lvlus<62){$st="B";}
elseif($lvlus>=62 and $lvlus<76){$st="A";}
elseif($lvlus>=76 and $lvlus<91){$st="S";}
elseif($lvlus>=91 and $lvlus<111){$st="R";}
elseif($lvlus>=111){$st="R111";}

if ($o=='grocery'){$tp="Украшение";}
if ($o=='golova'){$tp="Шлем";}
if ($o=='body'){$tp="Доспех";}
if ($o=='plash'){$tp="Плащ";}
if ($o=='ruki'){$tp="Рукавицы";}
if ($o=='pants'){$tp="Штаны";}
if ($o=='nogi'){$tp="Сапоги";}
if ($o=='shit'){$tp="Щит";}
if ($o=='amulet'){$tp="Амулеты";}
if ($o=='poyas'){$tp="Левая серьга";}
if ($o=='poyas'){$tp="Правая серьга";}
if ($o=='kolco'){$tp="Левое кольцо";}
if ($o=='rkolco'){$tp="Правое кольцо";}
echo "<hr/>";

///////////////////////////
$avto= mysql_num_rows($req);

While($mag =  mysql_fetch_assoc($req))
{
$mag['name'] = htmlspecialchars(stripslashes(addslashes($mag['name'])));

echo" <img src=\"/shmot/$mag[name].png\" alt=\"pic\"/>
 <a href=\"shop_chat.php?mod=info&amp;id=$mag[id]\">$mag[name]</a><br/> Цена: ".number_format($mag['cena'])." <hr/>";
}

echo"<br/><a href=\"shop_chat.php?mod=b\">Назад</a>";

}
break;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
case 'weapon':


if($lvlus<20){$st="NG";}
elseif($lvlus>=20 and $lvlus<40){$st="D";}
elseif($lvlus>=40 and $lvlus<52){$st="C";}
elseif($lvlus>=52 and $lvlus<62){$st="B";}
elseif($lvlus>=62 and $lvlus<76){$st="A";}
elseif($lvlus>=76 and $lvlus<91){$st="S";}
elseif($lvlus>=91 and $lvlus<111){$st="R";}
elseif($lvlus>=111){$st="R111";}


if ($tip2=='ydar'){$tp="Ударное";}
if ($tip2=='luk'){$tp="Луки";}
if ($tip2=='kin'){$tp="Кинжалы";}
if ($tip2=='sdv'){$tp="Сдвоенное";}
if ($tip2=='kas'){$tp="Кастеты";}
if ($tip2=='kniga'){$tp="Книги";}
if ($tip2=='koppik'){$tp="Копья/Пики";}
if ($tip2=='me4'){$tp="Луки";}
if ($tip2=='rap'){$tp="Мечи";}


echo"<b>Классы:</b> | ";
if ($lvlus==0){echo"NG | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=0&tip2=$tip2\">NG</a> | ";}
if ($lvlus==20){echo"D | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=20&tip2=$tip2\">D</a> | ";}
if ($lvlus==40){echo"C | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=40&tip2=$tip2\">C</a> | ";}
if ($lvlus==52){echo"B | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=52&tip2=$tip2\">B</a> | ";}
if ($lvlus==62){echo"A | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=62&tip2=$tip2\">A</a> | ";}
if ($lvlus==76){echo"S | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=76&tip2=$tip2\">S</a> | ";}
if ($lvlus==91){echo"R | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=91&tip2=$tip2\">R</a> |";}
if ($lvlus==111){echo"R111<br/>";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=111&tip2=$tip2\">R111</a><br/>";}


if (empty($tip2)) {$tip2='ydar';}

echo"<b>Тип:</b> | ";
if ($tip2=='ydar'){echo"Ударное | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=ydar\">Ударное</a> | ";}
if ($tip2=='luk'){echo"Луки | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=luk\">Луки</a> | ";}
if ($tip2=='kin'){echo"Кинжалы | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=kin\">Кинжалы</a> | ";}
if ($tip2=='sdv'){echo"Сдвоенное | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=sdv\">Сдвоенное</a> | ";}
if ($tip2=='kas'){echo"Кастеты | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=kas\">Кастеты</a> | ";}
if ($tip2=='kniga'){echo"Книги | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=kniga\">Книги</a> | ";}
if ($tip2=='koppik'){echo"Копья/Пики | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=koppik\">Копья/Пики</a> | ";}
if ($tip2=='me4'){echo"Мечи | ";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=me4\">Мечи</a> | ";}
if ($tip2=='rap'){echo"Рапиры | <br/><br/>";}else{ echo"<a href=\"shop_chat.php?mod=weapon&lvlus=$lvlus&tip2=rap\">Рапиры</a> | <br/><br/>";}



echo "<hr/>";
$req =  mysql_query("SELECT * FROM `shop_chat` WHERE `city` != '' and `tip`='weapon' and `tip2`='$tip2' and `nlvl`='$lvlus' ORDER BY cena");
////////////////////////////
$avto=  mysql_num_rows($req);

While($mag =  mysql_fetch_assoc($req))
{
//*********************


echo"	<img src=\"/shmot/$mag[name].png\" alt=\"pic\"/>
		<a href=\"shop_chat.php?mod=info&amp;id=$mag[id]\">$mag[name]</a><br/>
		Цена: ".number_format($mag['cena'])." Постов "; 
		//[<a href=\"sell_chat.php?act=ask&amp;id=$mag[id]\">купить</a>]<br/>";
echo "<hr/>";
}
echo"<br/><a href=\"shop_chat.php?\">Назад</a>";
break;
case 'info':
$req =  mysql_query("SELECT * FROM `shop_chat` WHERE `city` != '' and `id`='".$_GET['id']."'");

$avto= mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag =  mysql_fetch_assoc($req);
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
case 'pants':
$tip='Штаны';
break;
case 'shit':
$tip='Щит';
break;
case 'amulet':
$tip='Амулет'; //серьга
break;
case 'poyas':
$tip='Левая серьга'; //серьга
break;
case 'rpoyas':
$tip='Правая серьга'; //серьга
break;
case 'kolco':
$tip='Левое кольцо'; //серьга
break;
case 'rkolco':
$tip='Правое кольцо'; //серьга
break;
case 'plash':
$tip='Плащ';
break;
case 'ruki':
$tip='Рукавицы';
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


if($mag['nlvl']<20){$st="NG";}
elseif($mag['nlvl']>=20 and $mag['nlvl']<40){$st="D";}
elseif($mag['nlvl']>=40 and $mag['nlvl']<52){$st="C";}
elseif($mag['nlvl']>=52 and $mag['nlvl']<62){$st="B";}
elseif($mag['nlvl']>=62 and $mag['nlvl']<76){$st="A";}
elseif($mag['nlvl']>=76 and $mag['nlvl']<91){$st="S";}
elseif($mag['nlvl']>=91 and $mag['nlvl']<111){$st="R";}
elseif($mag['nlvl']>=111){$st="R111";}



echo"<b></div></div></div><div class=menu><img src=\"/shmot/$mag[name].png\" alt=\"pic\"/> $mag[name]</b><br/>
Тип: $tip<br/>
Цена: ".number_format($mag['cena'], 0, ',', "'")." Постов <br/><br/>";

if ($tip=="Оружие"){
echo "
Физ. атака: $mag[patt]<br/>
Маг. атака: $mag[matt]<br/><br/>

SoulShot: x$mag[soul]<br/>
SpiritShot: x$mag[spirit]<br/><br/>";}else{

echo "
Физ. защ.: $mag[pdef]<br/>
Маг. защ.: $mag[mdef]<br/><br/>";}


echo "
Грейд: $st<br/><br/></div><div class=inoy>
";
echo "<a href=\"sell_chat.php?act=ask&amp;id=$mag[id]\">Купить</a>";
echo"<a href=\"shop_chat.php?\">Назад</a></div>";
break;
}
include($path.'inc/down.php');
?>