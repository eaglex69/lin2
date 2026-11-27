<?php
define('PROTECTOR', 1);

$headmod = 'obm_fs_ad';//фикс. места

$textl='Розделка рыбы';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


switch($_GET['mod']){


default:
$res = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' && `lat_name` = 'fish'"); //проверка есть ли рыба если да то открываем таблицу
$av = mysql_num_rows($res);
$fa = mysql_fetch_array($res);
$av = $fa['kol'];
if (empty($av)){$av = 0;}
if ($av==0){
// если нет рыбок  то пишем что пусто

echo "<p><font color=grey><b>У Вас нет Рыбок.</b></font></p>";


echo "<div class=inoy><a href=\"inventar.php\">Инвентарь</a></div>";
echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";
}else{

// подтвердил //
if (isset($_GET['yes'])){ 

// забираем рыбку
$req22 = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `lat_name` = 'fish'");
$res=mysql_num_rows($req22);
$rs = mysql_fetch_array($req22);
if($res==0){
echo "<p><font color=red>У Вас нет наживки...</font></p>";
}else{
$nk=$rs['kol']-1;
mysql_query("UPDATE `res` SET `kol` = '$nk' WHERE `usr` = '$log' and `lat_name` = 'fish'");
if($nk <= 0){ //удаляем
mysql_query("DELETE FROM `res` WHERE `id` = '$rs[id]' LIMIT 1");
}
}

// топим рейт
$reqs = mysql_query("SELECT * FROM `fish_log` WHERE `usr` = '$log'");
$rss = mysql_fetch_array($reqs);
$nk=$rss['skoko']-1;
mysql_query("UPDATE `fish_log` SET `skoko` = '$nk' WHERE `usr` = '$log'");
if($nk <= 0){ //удаляем
mysql_query("DELETE FROM `fish_log` WHERE `usr` = '$log'");
}





// Coin of Luck
$r_col=rand(1,90);
if ($r_col<=10){ 
$colnew=$udata['almaz']+1;
mysql_query("UPDATE `users` SET `almaz` = '$colnew' WHERE `usr` = '$log' LIMIT 1");
$rez_col = " Выпало: <b>1 Coin of Luck </b><br/>";
}else{$rez_col = 0;}
//-----------------

//////////////////////		RES		/////////////////////////////////


$shans=rand(1,100);
if($shans<=30){

if(empty($coldrop)){$coldrop = 1;}

$req = mysql_query("SELECT * FROM `mdrop`");
$rezdrop=mysql_num_rows($req);
$drop=rand(1,$rezdrop);

//if ($udata[dostup]>4){$drop = 11;}

$req = mysql_query("SELECT * FROM `mdrop` WHERE `id` = '$drop' LIMIT 1");
$drops = mysql_fetch_array($req);


//if ($drops[lat_name] != "FestivalAdena"){ // если не фестивальки то даём ресурс
///////////////////

//--------пишем для рецептов---------
if ($drops['lat_name'] == "recipe"){
$reqshop = mysql_query("SELECT * FROM `shop` ORDER BY RAND() LIMIT 1"); // случайный с таблици
$rezshop=mysql_fetch_array($reqshop);
$name2=explode(":",$drops['name']);
$drops['name'] = $name2[0];
$drops['name']="$drops[name]: $rezshop[name]"; // рецепт : имя
$drops['lat_name']="$drops[lat_name]$rezshop[id]"; // рецепт+ид что бы не путать
$drops['give']=$rezshop['id']; // пишем ид шмота
$drops['cena']=round($rezshop['cena']/3); // считаем цену
$coldrop = 1;}
//-----------------------------------
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `lat_name` = '$drops[lat_name]'");
$res=mysql_num_rows($req);
$rs = mysql_fetch_array($req);

if (empty($drops['name'])){$drops['name']='Выпал хлам (выкинуто)'; $rez_res=0;}else{

if($res==0){
mysql_query("INSERT INTO
        `res` SET
        `usr` = '$log',
        `name` = '$drops[name]',
        `lat_name` = '$drops[lat_name]',
        `tip` = '$drops[tip]',
        `what` = '$drops[what]',
        `give` = '$drops[give]',
        `kol` = '$coldrop',
        `cena` = '$drops[cena]'");
}else{
$nk=$rs['kol']+$coldrop;
mysql_query("UPDATE `res` SET `kol` = '$nk' WHERE `usr` = '$log' and `lat_name` = '$drops[lat_name]'");
}
$rez_res = " Выпало:<b> $drops[name] х$coldrop </b><br/>";
}

}
else{$rez_res = 0;}
//--------------------------------------------------------------------------



echo "<div class=dot><u>Рыбка успешно разделана:</u><br/><br/>";

if (empty($rez_res) and empty($rez_col))
{echo "<b>Ничего не выпало...</b>";}
else
{
if (!empty($rez_res)){echo "$rez_res";}
if (!empty($rez_col)){echo "$rez_col";}
}

$av2 = $av - 1;

echo "</p><p>Осталось: $av2 шт.";
echo "</p></div>";

if ($av2>0){
echo "<div class=inoy><a href=\"?yes\">Разделать еще одну</a></div>";
}
echo "<div class=inoy><a href=\"inventar.php\">Инвентарь</a></div>";
echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";

include($path.'inc/down.php');

}
//-------------------------------------------

echo "<p><font color=grey><b>У Вас $av Рыбок.</b></font></p>";

echo "<p><font color=#D2B48C>Вы действительно хотите разделать <b><u>Fish</u></b>? При разделке могут выпасть ресурсы, фрагменты вещей для крафта, заточки или Coin of Luck.</b></font></p>";

echo "<div class=inoy><a href=\"?yes\">Да, хочу</a></div>";

echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";

}

break;



}
include($path.'inc/down.php');
?>