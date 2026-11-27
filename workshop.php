<?
define('PROTECTOR', 1);

$headmod = 'workshop';//фикс. места

$textl='Мастерская';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


//if ($udata[dostup]<4){echo' <p><font color=red>Доступ в мастерскую закрыт</font></p>';include($path.'inc/down.php');exit;}


// доступ по профам
if ($udata[prof]!='Artisan' && $udata[prof]!='Warsmith'  && $udata[prof]!='Maestro'){echo' <p><font color=red>Доступ в мастерскую закрыт</font></p>';include($path.'inc/down.php');exit;}
// приравнюем номер профы
if ($udata[prof]=='Artisan'){$prof = 1;}	if ($udata[prof]=='Warsmith'){$prof = 2;}	if ($udata[prof]=='Maestro'){$prof = 3;}
//-----------------

//-----------------Убираем рецепт с книгу-----------------------------------------------------------------
if (isset($_GET[del])){

$av47 = mysql_num_rows(mysql_query("SELECT * FROM `kraft` WHERE `usr` = '$log' and `id`='$_GET[del]' LIMIT 1"));
	
	if($av47==1){

	mysql_query("DELETE FROM `kraft` WHERE `id`='$_GET[del]' and `usr`='$log' LIMIT 1"); // удаляем со списка


echo "<p><font color=#999900> Удалено </font></p><hr/>";

}}
//-------------------------------------------------------------------------------------------------------------
switch($_GET[mod]){

default:

//-----------------Добавляем рецепт в книгу-----------------------------------------------------------------
if ($_GET[mod]==go){

$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `id`='$_GET[go]' LIMIT 1");
$recept = mysql_fetch_array($req); // открыли рецепт

$reqsh = mysql_query("SELECT * FROM `shop` WHERE `id`='$recept[give]' LIMIT 1");
$shmot = mysql_fetch_array($reqsh); // открыли шмот

// выщитуем шмот с 1 по 40 уровень 1 профа, 41-72 эт 2-я профа и с 73 третья
if ($shmot[nlvl] < 40){$profa = 1;}
if ($shmot[nlvl] >= 40 and $shmot[nlvl] < 72){$profa = 2;}
if ($shmot[nlvl] >= 72){$profa = 3;}
//--------


		$avto=mysql_num_rows($req);
		if($avto==0){echo'<font color=red><p> Ошибка! Доступ закрыт.</p></font><hr/>';}else{

		$req2 = mysql_query("SELECT * FROM `kraft` WHERE `usr` = '$log' and `lat_name`='$recept[lat_name]' LIMIT 1");
		$avto2=mysql_num_rows($req2);
		if($avto2>0){echo'<font color=red><p> Такой рецепт уже есть! </p></font>';}else{

// пишем ресы для крафта
$droprand=rand(8,60); // ресурсы на рандом
if ($droprand==60) {$droprand=59;}
if ($droprand==11 or $droprand==12) {$droprand=13;}
$reqshop = mysql_query("SELECT * FROM `mdrop` WHERE `id` = '$droprand' LIMIT 1"); // случайный с таблици
$rezresurs=mysql_fetch_array($reqshop);
$skoko=rand(14,26); if ($shmot[nlvl]<=0){$shmot[nlvl] = 7;} $skoko=$skoko*$shmot[nlvl];
$reit=rand(4,15);
$patt=$reit*$shmot[patt];
$pdef=$reit*$shmot[pdef];
$matt=$reit*$shmot[matt];
$mdef=$reit*$shmot[mdef];
// ---------------------		
		
mysql_query("INSERT INTO
        `kraft` SET
        `usr` = '$log',
        `idd` = '$recept[give]',
        `profa` = '$profa',
        `lvl` = '$shmot[nlvl]',
        `name` = '$shmot[name]',
        `res` = '$rezresurs[lat_name]|$skoko',
        `patt` = '$patt',
        `pdef` = '$pdef',
        `matt` = '$matt',
        `mdef` = '$mdef',
        `lat_name` = '$recept[lat_name]'");

// если такой рецепт один удаляем иначе минусуем
if ($recept[kol]>1){ // более одного минусуем
$nkol=$recept[kol]-1;
mysql_query("UPDATE `res` SET `kol` = '$nkol' WHERE `usr` = '$log' and `id`='$_GET[go]' LIMIT 1");     
}else{ //иначе удаляем
mysql_query("DELETE FROM `res` WHERE `usr` = '$log' and `id`='$_GET[go]' LIMIT 1");

}

echo "<p><font color=#999900> Рецепт записан в книгу </font></p><hr/>";

}}}
//----------------------------------------------------------------------------------------------------------


echo "<p><font color=grey> &#160 Книга крафта</font></p><hr/>";


$req = mysql_query("SELECT * FROM `kraft` WHERE `usr`='$udata[usr]' ORDER BY lvl");
$avto=mysql_num_rows($req);
if ($avto>0){

While($mag = mysql_fetch_array($req))
{
$mag[name] = htmlspecialchars(stripslashes(addslashes($mag[name])));

		$reqkr = mysql_query("SELECT * FROM `kraft` WHERE `usr` = '$log' and `id`='$_GET[go]' LIMIT 1");
		$kr = mysql_fetch_array($reqkr);

if ($kr[lvl]<=$udata[lvl]){echo"<div class=inoy><a href=\"?mod=kraft&go=$mag[id]\"><img src=\"shmot/$mag[name].png\"  height=30 width=30/> $mag[name] </a></div> ";}
else {echo"<div class=dot><a href=\"?mod=kraft&go=$mag[id]\"><img src=\"shmot/$mag[name].png\"  height=30 width=30/> $mag[name] </a></div> ";}

}
}else{echo "<p><font color=orange> &#160 Нет рецептов </font></p><hr/>";}

break;

//------------------крафт-----------------

case 'kraft';

echo '<font color=#9999CC> <p><b>Вы собираетесь создать:</b></p></font>';

		$reqkr = mysql_query("SELECT * FROM `kraft` WHERE `usr` = '$log' and `id`='$_GET[go]' LIMIT 1");
		$avtokr=mysql_num_rows($reqkr);
		$kr = mysql_fetch_array($reqkr);
		
if($avtokr==0){
echo'<font color=red><p> Доступ закрыт! </p></font>';
include($path.'inc/down.php');exit;}


$req = mysql_query("SELECT * FROM `shop` WHERE `id`='$kr[idd]'");
$mag = mysql_fetch_array($req);

$avto=mysql_num_rows($req);

if($avto=="0"){
echo'<font color=red><p> Нет такой вещи! </p></font>';
include($path.'inc/down.php');exit;}


switch($mag[klas]){
case 'not':$klas='Все';break;
case 'wizard':$klas='Маг';break;
case 'fighert':$klas='Воин';break;}

switch($mag[tip]){
case 'weapon':$tip='Оружие';break;
case 'body':$tip='Доспехи';break;
case 'golova':$tip='Шлем';break;
case 'nogi':$tip='Сапоги';break;
case 'shit':$tip='Щит';break;
case 'poyas':$tip='Украшение';break;
case 'plash':$tip='Плащ';break;
case 'ruki':$tip='Рукавицы';break;
case 'kolco':$tip='Кольцо';break;
case 'amulet':$tip='Амулет';break;
}



//--------------------------------------------

$u=explode('*',$mag[name]);
echo"<img src=\"shmot/$u[0].png\" alt=\"pic\"/> <b>$mag[name]</b><br/>
Тип: $tip<hr/>
<b>Характеристика</b><br/>";
if ($tip=="Оружие"){

echo "
Физ. атака: $kr[patt]<br/>
Маг. атака: $kr[matt]<br/><br/>
SoulShot: x$mag[soul]<br/>
SpiritShot: x$mag[spirit]<br/><br/>";}else{

echo "
Физ. защ.: $kr[pdef]<br/>
Маг. защ.: $kr[mdef]<br/>";}

$res=explode('|',$kr[res]);
// приравнюем номер профы
//if ($udata[prof]=='Artisan'){$prof = 1;}	if ($udata[prof]=='Warsmith'){$prof = 2;}	if ($udata[prof]=='Maestro'){$prof = 3;}
if ($kr[profa]==1){$pr = 'Artisan';}	if ($kr[profa]==2){$pr = 'Warsmith';}	if ($kr[profa]==3){$pr = 'Maestro';}
//-----------------
echo "<hr/>
<b><u>Нужно</b></u>		<br/>
Русорсов:				$res[0] x $res[1]<br/>
Уровень: 				$kr[lvl]<br/>
Професия:				$pr<br/>
";

if ($prof>=$kr[profa] and $udata[lvl]>=$kr[lvl]){echo"<div class=inoy><a href=\"?mod=kraft_yes&go=$kr[id]\"> Создать </a></div> ";}
echo"<div class=inoy><a href=\"?del=$kr[id]\"><font color=red> Удалить </font></a></div> ";
echo' <div class=inoy> <a href="workshop.php?">Назад в книгу</a> </div>';

break;

//-------------после подтверждения создаём вещь---------------------
case 'kraft_yes';

	$reqkr = mysql_query("SELECT * FROM `kraft` WHERE `usr` = '$log' and `id`='$_GET[go]' LIMIT 1"); 
$avtokr=mysql_num_rows($reqkr);			$kr = mysql_fetch_array($reqkr);
		
if($avtokr==0){echo'<font color=red><p> Доступ закрыт! </p></font>';include($path.'inc/down.php');exit;} // проверка чей рецепт


	$req = mysql_query("SELECT * FROM `shop` WHERE `id`='$kr[idd]' LIMIT 1");
$mag = mysql_fetch_array($req);				$avto=mysql_num_rows($req);

if($avto=="0"){echo'<font color=red><p> Нет такой вещи! </p></font>';echo' <div class=inoy> <a href="workshop.php?">Назад в книгу</a> </div>';include($path.'inc/down.php');exit;} // если шмота нет

$r=explode("|",$kr[res]);
$lat_name=$r[0];
$kol=$r[1];

$req15 = mysql_query("SELECT * FROM `res` WHERE `usr`='$log' and `lat_name`='$lat_name' and `kol`>='$kol' LIMIT 1");
$avto15=mysql_num_rows($req15);

if($avto15==0){echo'<font color=red><p> Недостаточно ресурсов! Нужно <b>'.$lat_name.' x '.$kol.'</b>.</p></font>';echo' <div class=inoy> <a href="workshop.php?">Назад в книгу</a> </div>';include($path.'inc/down.php');exit;} // проверка на ресурсы

$reqre = mysql_query("SELECT * FROM `res` WHERE `usr`='$log' and `lat_name`='$lat_name' LIMIT 1");
$re = mysql_fetch_array($reqre);
if(($re[kol]-$kol)<=0){
mysql_query("DELETE FROM `res` WHERE `usr`='$log' and `lat_name`='$lat_name' LIMIT 1");//чистим логи
}else{
$ros=$re[kol]-$kol;
mysql_query("UPDATE `res` SET `kol` = '$ros' WHERE `usr`='$log' and `lat_name`='$lat_name' LIMIT 1");
}


$mag[patt]=$kr[patt];
$mag[matt]=$kr[matt];
$mag[pdef]=$kr[pdef];
$mag[mdef]=$kr[mdef];
$mag[cena]=round($mag[cena]/2);
//-----------------------------------------

//------------------------------------------
$procent=rand(1,100);
if($procent>55){
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
mysql_query("DELETE FROM `kraft` WHERE `usr` = '$log' and `id`='$_GET[go]' LIMIT 1");
echo "<font color=#996666><p>Вещь <b>$mag[name]</b> созданна и помещенна в инвентарь!</p></font>";echo' <div class=inoy> <a href="workshop.php?">Назад в книгу</a> </div>';}
else{echo'Вещь сломана!<br/>Крафт не удался<br/>'; echo' <div class=inoy> <a href="workshop.php?">Назад в книгу</a> </div>';}
	

	
break;

}
include($path.'inc/down.php');
?>
