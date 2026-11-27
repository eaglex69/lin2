<?php
define('PROTECTOR', 1);
// PHP 8: error level / timezone / mysql_*() before the code below (this entry runs code before inc/db.php)
include_once dirname(__FILE__).'/inc/php8.php';

$headmod = 'okrestnosti';//фикс. места


if($_GET['mod']=='ataka'){
$header=TRUE;


}else{
$textl='Битва';
}

include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
$timer=time()+300;

$ataka=$_GET['ataka'];
$udar=$_GET['udar'];


////////////////////////
$req = mysql_query("SELECT * FROM `tmp` WHERE `usr` = '$log' LIMIT 1");
$av = mysql_num_rows($req);
if ($av==0){
mysql_query("INSERT INTO log SET usr='$log',text='Вы не находитесь в бою!',kto='system',place='battle',timer='$timer'");
header ('Location: okrestnosti.php?',false);exit;}


$m = mysql_fetch_array($req);
////////////////////////////
$req = mysql_query("SELECT * FROM `mobs` WHERE `id` = '$m[mob]' LIMIT 1");
$mob = mysql_fetch_array($req);

////////////////////////////////////////////////////////////////////////////////
$goldx = 1;
if ($udata['lvl']<=0){$udata['lvl'] = 1;}
$in_reit = mysql_query("SELECT reit FROM option_game WHERE id = '1' LIMIT 1");
$reit =mysql_fetch_array ($in_reit);
if ($reit['reit']=='off'){
$upper=1;
}else{
$upper=2;
}
if($udata['res']==0) $koef=1;
if($udata['res']==1) $koef=1.5;
if($udata['res']>1) $koef=$udata['res'];
$mob['exp']=($mob['exp']*$udata['lvl']*$upper)/$koef;

if ($udata['money'] >= 100000000000){$mob['money'] = 0;}

///////////////////////////////////////////////////////////////////////////////
///////////////////////// 	Вип акаунты
$reqvip = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1"); // проверяем есть ли вип акаунт у игрока
$avto=mysql_num_rows($reqvip);
if($avto==1){
$vip = mysql_fetch_array($reqvip);
$goldx = $vip['tip']/$koef;
$mob['exp']=$mob['exp']*$vip['tip'];
$mob['money']=($mob['money']+($mob['money']*$vip['tip']))/$koef; // делаем рейты аден
}
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////

$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status` = 'on'");
$avto=mysql_num_rows($req);
if($avto==1){
$pit = mysql_fetch_array($req);
}
function timer($log){

$time=time();
mysql_query("UPDATE `tmp` SET `ltime` = '$time' WHERE `usr` = '$log'");
}
///////////////////////
function lose($log,$pas,$m,$pit,$ass,$a,$mob,$udata){

$time=time();
$losetime=$m['ltime']+600;

if($udata['hp']<=0 or $time>$losetime){

$nloses=$udata['loses']+1;
if($mob['hp']<=0){
mysql_query("UPDATE `mobs` SET `hp` = '0',`status`='off',`oponent`='$time' WHERE `id` = '$m[mob]'");
}else{
mysql_query("UPDATE `mobs` SET `hp` = '$mob[hp]',`status`='on',`oponent`='not' WHERE `id` = '$m[mob]'");
}




mysql_query("UPDATE users SET loses = '$nloses',hp='0' WHERE usr = '$log'");//пишем данные в плэера

mysql_query("UPDATE regenerator SET last = '$time' WHERE usr = '$log'");//сбиваем регенерацию

mysql_query("DELETE FROM `tmp` WHERE usr='$log'");//чистим логи

mysql_query("INSERT INTO log SET usr='$log',text='Вы проиграли бой с $mob[name]!',kto='system',place='battle',timer='$timer'"); // пишем что вы проиграли

//питомци
if($pit['status']=='on'){
if($pit['hp']<=0){
mysql_query("UPDATE pit SET status = 'die' WHERE `usr` = '$log'");
}
}
echo"<font color=#990033><center><br/><b> Вы проиграли бой! </b></font><br/>";
echo '<hr>';
echo"<br/><div class=inoy><a href=\"res.php?\">Храм жизни...</a></center></div>";
include($path.'inc/down.php');
exit;
}
}
function win($log,$pas,$m,$mob,$ass,$pit,$a,$udata,$usr,$p){

$time=time();


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////
$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
$mestouser = mysql_fetch_assoc($req);
////////////////////////////

if($mob['status']=='battle' or $mob['status']=='off'){

mysql_query("DELETE FROM `tmp` WHERE usr='$log' LIMIT 1");//выходим из боя
mysql_query("INSERT INTO log SET usr='$log',text='Монстр $mob[name] уже убит!',kto='system',place='battle',timer='$timer'");

echo"<font color=#baa252><center><br/><b>Монстр уже убит!</b></font><br/>";
echo '<div style="border-bottom		:#666 solid  1px; padding		: 1% 1% 1% 1%; width:55%;"></div>';
echo"<br/><div class=inoy><a href=\"/okrestnosti.php?\">Окрестности</a></center></div>";
include($path.'inc/down.php');
exit;

header ('Location: okrestnosti.php?',false);exit;
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


if($mob['hp']<=0){

$nwins=$udata['wins']+1;
//питы
if($pit['status']=='on'){
if($pit['hp']<=0){
mysql_query("UPDATE pit SET status = 'die' WHERE `usr` = '$log'  and `status` = 'on'");
}else{
$pexp1=round($mob['exp']/30);
$pexp=$pit['exp']+$pexp1;
mysql_query("UPDATE pit SET exp='$pexp' WHERE `usr` = '$log'  and `status` = 'on'");
}
}
//-----------------------------------
///////////////СПОЙЛИНГ ВСЁ РАНДОМ///////////////

$reqsp = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log' and `tip` = 'spoil' and `activ` != 'no'  ORDER BY `id` DESC"); // спойлинг всех ресурсов на рандом
$ressp=mysql_num_rows($reqsp);
$spoil = mysql_fetch_array($reqsp);
if($ressp>0){
$n = $spoil['lvl']-1;
$col=explode("|",$spoil['uron']);
$coldrop=$col[$n];//+сила



$shans=rand(1,100);
if($shans<=45){

$req = mysql_query("SELECT * FROM `mdrop` WHERE `id` >= '0' and `id` < '61' ");
$rezdrop=mysql_num_rows($req);
$drop=rand(1,$rezdrop);



$req = mysql_query("SELECT * FROM `mdrop` WHERE `id` = '$drop' LIMIT 1");
$drops = mysql_fetch_array($req);


if ($drops['lat_name'] != "FestivalAdena"){ // если не фестивальки то даём ресурс
///////////////////

//--------пишем для рецептов---------

if ($drops['lat_name'] == "recipe"){
$reqshop = mysql_query("SELECT * FROM `shop` WHERE `nlvl` > '62' ORDER BY RAND() LIMIT 1"); // случайный из таблици
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
if (empty($drops['name'])){$drops['name']='Хлам (выкинут)';}
$spoilall = " Cпойл: $drops[name] х$coldrop <br/>";}
}
}

/////////////////////////////////////////////////////////////////////////////
////////// СПОЙЛИНГ /////////
$reqsp = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log' and `tip` = 'spoilall' and `activ` != 'no'  ORDER BY `id` DESC"); // спойлинг даёт больше ресурсов при выпадении
$ressp=mysql_num_rows($reqsp);
$spoil = mysql_fetch_array($reqsp);
if($ressp>0){
$n = $spoil['lvl']-1;
$col=explode("|",$spoil['uron']);
$coldropsp=$col[$n];//+сила
}
//--------
$shans=rand(1,100);
if($shans<=$mob['shans']){


$drop=explode("/",$mob['drop']);
$cdr=count($drop)-1;
$iddr=rand(0,$cdr);


$req = mysql_query("SELECT * FROM `mdrop` WHERE `id` = '$drop[$iddr]' LIMIT 1");
$drop = mysql_fetch_array($req);

if(empty($coldropsp)){$coldrop = 1;}else{$coldrop = $coldropsp;}
if ($drop['lat_name'] == "FestivalAdena"){$coldrop = 1;}
if ($drop['lat_name'] == "Ключ"){$coldrop = 1;}
if ($drop['lat_name'] == "Сундук удачи"){$coldrop = 1;}
//--------пишем для рецептов---------
if ($drop['lat_name'] == "recipe"){
$reqshop = mysql_query("SELECT * FROM `shop` ORDER BY RAND() LIMIT 1"); // случайный с таблици
$rezshop=mysql_fetch_array($reqshop);
$name2=explode(":",$drop['name']);
$drop['name'] = $name2[0];
$drop['name']="$drop[name]: $rezshop[name]"; // рецепт : имя
$drop['lat_name']="$drop[lat_name]$rezshop[id]"; // рецептид что бы не путать
$drop['give']=$rezshop['id']; // пишем ид шмота
$drop['cena']=round($rezshop['cena']/3); // считаем цену
$coldrop = 1;}
//-----------------------------------

///////////////////
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `lat_name` = '$drop[lat_name]'");
$res=mysql_num_rows($req);
$rs = mysql_fetch_array($req);
if($res==0){
mysql_query("INSERT INTO
        `res` SET
        `usr` = '$log',
        `name` = '$drop[name]',
        `lat_name` = '$drop[lat_name]',
        `tip` = '$drop[tip]',
        `what` = '$drop[what]',
        `give` = '$drop[give]',
        `kol` = '$coldrop',
        `cena` = '$drop[cena]'");
}else{
$nk=$rs['kol']+$coldrop;
mysql_query("UPDATE `res` SET `kol` = '$nk' WHERE `usr` = '$log' and `lat_name` = '$drop[lat_name]'");
}
//}
}
if ($mob['sm']==0){$mob['sm']=100;} // сделано так, потому, что создано половина мобов с рандом 0. Потом переписан бал рандом, мобов лень переписывать хД (iNoY.GM)
$smshans=rand(1,100);
if($smshans<=$mob['sm']){
$nmoney = $mob['money'];
}


$shans=rand(1,100);
mysql_query("UPDATE `mobs` SET `hp` = '0',`status`='off',`oponent`='$time' WHERE `id` = '$m[mob]'");

$p = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' and `act` = 'yes' and `act2` = 'yes' or `usr2` = '$log' and `act2` = 'yes' and `act` = 'yes' Limit 1"); //проверяем состоит ли игрок в пати
$avt=mysql_num_rows($p);
if($avt >= 1){$pat=2; $paty="yes";}else{$pat=1; $paty="no";} // если в пати то делим напополам, иначе на 1 и оставляем то что надо (полный)

$nexp=$udata['exp']+$mob['exp']/$pat;
if ($udata['karma']>0){$nkar=$udata['karma']-1;}else{$nkar=0;}


// щитаем голды что бы разное было для интереса
$gd = rand(1,1000);
$gold = $mob['hpall']*$mob['lvl']/200+$gd;
$ngold = $udata['gold']+$gold;
/////////////////////////////////////////////////

if(!empty($nmoney)){
$nmoney=$udata['money']+$nmoney/$pat;
$spwin=$mob['exp']/10;
$sp=$spwin/$pat+$udata['sp'];
mysql_query("UPDATE users SET karma = '$nkar',wins = '$nwins',exp='$nexp',sp='$sp',money='$nmoney',gold='$ngold' WHERE usr = '$log'");//пишем данные в плэера
}else{
$mon=$nmoney; // вписал для пати
$spwin=$mob['exp']/10;
$sp=$spwin/$pat+$udata['sp'];

mysql_query("UPDATE users SET karma = '$nkar',wins = '$nwins',sp='$sp',exp='$nexp' WHERE usr = '$log'");
}
// пати если есть ///////////////////////////////////////////////////////////////
if ($paty=="yes"){

// глава пати
		$pgl = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' and `act` = 'yes'");
		$avt=mysql_num_rows($pgl);


		if($avt >= 1)
		{
		$pgl = mysql_fetch_array($pgl);
		$u = mysql_query("SELECT * FROM `users` WHERE `usr` = '$pgl[usr2]'");
		$usr = mysql_fetch_array($u);		}

// принявший пати

		$p = mysql_query("SELECT * FROM `paty` WHERE `usr2` = '$log' and `act2` = 'yes'");
		$avt=mysql_num_rows($p);


		if($avt >= 1)
		{
		$p = mysql_fetch_array($p);
		$u = mysql_query("SELECT * FROM `users` WHERE `usr` = '$p[usr]'");
		$usr = mysql_fetch_array($u);		}
//////////

$nik=$usr['usr'];
$ptexp=$usr['exp']+$mob['exp']/$pat;
$pspwin=$mob['exp']/10;
$psp=$pspwin/$pat+$usr['sp'];
$pmon=$usr['money']+$mob['money']/$pat;
	mysql_query("UPDATE users SET exp='$ptexp',sp='$psp',money='$pmon' WHERE usr = '$nik'");} // в пати



// если в клане то опыт клану
	if(!empty($udata['clan']))
{
$reqclan = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($reqclan);


$clanexp=$clan['exp']+$mob['exp']/50;
mysql_query("UPDATE clan SET
exp = '$clanexp' WHERE lider = '$udata[clan]' LIMIT 1");
}


//-----------------------------------------------------------------------------------
//////			 делаем от багоюзства			 /////////
$date = date("d.m.Y");
$times = date("H:i");

$datans = date("H:i-w");
if ($datans == '00:00-0' or $datans == '00:00-0' or $datans == '00:01-0'){ /* в 3 часа дня воскресенье  когда время 15,00 ничего не делает*/}else{ /*иначе*/

// записуем в рейтинги недели для мобов
$reqev = mysql_query("SELECT * FROM `eve_mob_w` WHERE `ids` = '$udata[id]' and `usr` = '$log'");
////////////////////////////
$eve = mysql_fetch_array($reqev);
if(mysql_num_rows($reqev)>=1) // если уже участвует в рейтинге то дописываем
{
$skoko=$eve['skoko']+1;
mysql_query("UPDATE eve_mob_w SET skoko='$skoko' WHERE ids='$udata[id]' and usr='$log'");} // дописываем +1 к количеству

else{ // если нет игрока в рейтинге то создаем ему
mysql_query("INSERT INTO eve_mob_w SET usr='$log',ids='$udata[id]',skoko='1'"); // создаем таблицу юзеру
}
}
$date = date("d.m.Y");
$times = date("H:i");
$data1 = date("H:i");
if ($data1 == '00:25' or $data1 == '00:24' or $data == '00:01'){ /* в 3 часа дня воскресенье  когда время 15,00 ничего не делает*/}else{ /*иначе*/

// записуем в рейтинги недели для мобов
$reqev = mysql_query("SELECT * FROM `eve_mob` WHERE `ids` = '$udata[id]' and `usr` = '$log'");
////////////////////////////
$eve = mysql_fetch_array($reqev);
if(mysql_num_rows($reqev)>=1) // если уже участвует в рейтинге то дописываем
{
$skoko=$eve['skoko']+1;
mysql_query("UPDATE eve_mob SET skoko='$skoko' WHERE ids='$udata[id]' and usr='$log'");} // дописываем +1 к количеству

else{ // если нет игрока в рейтинге то создаем ему
mysql_query("INSERT INTO eve_mob SET usr='$log',ids='$udata[id]',skoko='1'"); // создаем таблицу юзеру
}
}

//-----------------------------------------------------------------------------------


//	---	//	---	//	---	//	//	 	ЗАДАНИЯ		//	//	---	//	---	//	---	//
if ($udata['lvl']>76){
$reqev = mysql_query("SELECT * FROM job_mob WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
$jm = mysql_fetch_array($reqev);
if(mysql_num_rows($reqev)>=1){ // если уже участвует  то дописываем
if ($jm['kill']!=='off'){ // если уже получил вознагр то табла не созд
$kill=$jm['kill']+1; if ($kill>20000){$kill=20000;}
mysql_query("UPDATE job_mob SET `kill` = '$kill' WHERE `usr` = '$log' Limit 1");}} // дописываем +1 к количеству
else{ // если нет игрока в рейтинге то создаем ему
mysql_query("INSERT INTO job_mob SET `usr` = '$log', `kill`='1', `data`='no'"); // создаем таблицу юзеру
}}
//-----------------------------------------------------------------------------









///-----------------------------------------------------------------------------------
// таблица килов для обмена
$reqev = mysql_query("SELECT * FROM `kill_mob` WHERE `id` = '$udata[id]' and `usr` = '$log'");
////////////////////////////
$eve = mysql_fetch_array($reqev);
if(($eve)>=1) // если уже участвует в рейтинге то дописываем
{
$skoko= $eve['skoko']+1;

mysql_query("UPDATE `kill_mob` SET `skoko`='$skoko' WHERE `id`='$udata[id]' and `usr`='$log'");} // дописываем +1 к количеству
else{ // если нет игрока в рейтинге то создаем ему
mysql_query("INSERT INTO kill_mob SET `usr` = '$log', `id`='$udata[id]', skoko='1'"); // создаем таблицу юзеру
}
//-----------------------------------------------------------------------------------




mysql_query("DELETE FROM `tmp` WHERE usr='$log'");//чистим логи

mysql_query("UPDATE regenerator SET last = '$time' WHERE usr = '$log'");//сбиваем регенерацию

mysql_query("INSERT INTO log SET usr='$log',text='Вы победили в бою с $mob[name]!',kto='system',place='battle',timer='$timer'"); // пишем что вы выиграли

$req = mysql_query("SELECT * FROM `userquests` WHERE `quest` = '$mob[quest]' and `usr`='$log' and `status`='start'");
$avto=mysql_num_rows($req);
if($avto==1){
//проверка на килл
$req = mysql_query("SELECT * FROM `user_kill` WHERE `quest` = '$mob[quest]' and `city`='$udata[city]' and `usr`='$log'");
$avto=mysql_num_rows($req);
if($avto==1){
$u_k = mysql_fetch_array($req);
$u_k['killing']=$u_k['killing']-1;
if($u_k['killing']<=0){
mysql_query("DELETE FROM `user_kill` WHERE `quest` = '$mob[quest]' and `usr`='$log'");//чистим логи
}else{
mysql_query("UPDATE `user_kill` SET `killing` = '$u_k[killing]'  WHERE `quest` = '$mob[quest]' and `usr`='$log'");
}
}
}

echo"<font color=#6FCD72><center><b> <img src=\"pic/winl.png\" alt=\"\"/> ВЫ ПОБЕДИЛИ МОНСТРА ! </b></font> ";
echo '<hr>';
if(!empty($drop['name'])){
echo"<b>Выпало: $drop[name] x$coldrop</b><br/>";
}
echo "$spoilall";
if(!empty($shems['name'])){

if($shems['unknow']=='0'){$shems['name']='Скрыт';}

echo"Схема: $shems[name]<br/>";
}
$sp=$mob['exp']/10/$pat;
$mob['exp']=$mob['exp']/$pat;
$mob['money']=$mob['money']/$pat;
if(!empty($nmoney)){
echo'<font color=#ffffcc><b><i>Выпало:</b></i> <b> <font color=#f4d06e> '.number_format($mob['money'], 0, ',', "`").'</b> аден, ';
}
echo'получено<b> <font color=#f4d06e>'.number_format($mob['exp'], 0, ',', "`").'</b> опыта, ';
echo'<b> <font color=#f4d06e>'.number_format($sp, 0, ',', "`").'</b> SP ';
if(!empty($gold)){
echo'и <b> <font color=#f4d06e>'.number_format($gold, 0, ',', "`").'</b> Gold Bar ';
}

if(!empty($pexp)){
echo"<br/><font color=#f4d06e>".number_format($pexp1, 0, ',', "`")." опыта получил $pit[name]<br/></font>";
}
echo '</div><div style="text-align:center;">';
$igla=rand(1,100);
//if ($log=='KraToS'){$igla = 100;}
$ig_rand_skoko=rand(3,7);
if ($igla >= 40){
$ignew = $udata['ig']+$ig_rand_skoko;
mysql_query("UPDATE users SET ig = '$ignew' WHERE usr = '$log'");
$reqev = mysql_query("SELECT * FROM `eve_ny` WHERE `ids` = '$udata[id]' and `usr` = '$log'");
$eve = mysql_fetch_array($reqev);
if(mysql_num_rows($reqev)>=1) 
{
$skoko=$eve['skoko']+1;
mysql_query("UPDATE eve_ny SET skoko='$skoko' WHERE ids='$udata[id]' and usr='$log'");} 
else{ 
mysql_query("INSERT INTO eve_ny SET usr='$log',ids='$udata[id]',skoko='1'"); 
}
}

$col_rand=rand(1,10000);
//if ($log=='KraToS'){$col_rand = 1000;}
$col_rand_skoko=rand(1,10);
if ($col_rand >= 8500){
$col_new = $udata['almaz']+$col_rand_skoko;
mysql_query("UPDATE users SET almaz = '$col_new' WHERE usr = '$log'");
echo '<div class=dot><font color=#6FCD72>Выпало '.$col_rand_skoko.' Coin of Luck</font></div>';
}
echo"<div class=news><a href=\"okrestnosti.php?\"><img src=\"pic/skils/drop.png\" height=25 width=25></a></div></div></center>";


include($path.'inc/down.php');
exit;
}
}
function pit($log,$pas,$pit,$udata){
if($pit['status']=='on'){
if($pit['hp']<=0){
mysql_query("UPDATE pit SET status = 'die' WHERE `usr` = '$log'");
}
}
}

function mein($log,$pas,$ass,$a,$m,$pit,$mob,$udata,$udar,$usr,$p){
timer($log);
lose($log,$pas,$m,$pit,$ass,$a,$mob,$udata);
win($log,$pas,$m,$mob,$ass,$pit,$a,$udata,$usr,$p);
pit($log,$pas,$pit,$udata);
if($pit['status']=='on'){
echo"<b>$pit[name]:</b> ($pit[hp]/$pit[hpall])<br/>";
}
if ($mob['hp']>=0){
$hprez=lin2_div($mob['hp'],$mob['hpall'])*100;
if ($hprez>100) {$hprez=100;};
} else {$mob['hp']=0; $hprez=0;}
echo "<b><img src=\"pic/mob.png\" alt=\"pic\"/> $mob[name] -<font color=#ffffcc> &#160 [<font color=#ff4040>HP<font color=#ffffcc>:</b></font><font color=#ffffcc>    ".number_format($mob['hp'], 0, ',', "`")."<b>/</b>".number_format($mob['hpall'], 0, ',', "`")."] </font>";

echo "</div>";

//-----пк в бою----------

///////////////игроки
echo "<br/>";
echo'<hr/><small>';
$odin=false;
$timeout=time()-150;//оффлайн

$req = mysql_query("SELECT * FROM online WHERE laikas > '$timeout' and usr!='$log'");

////////////////////////////
While($who = mysql_fetch_array($req))
{
$reqs = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$who[usr]' LIMIT 1");
$mesto = mysql_fetch_array($reqs);//место

$reqs = mysql_query("SELECT id,city,storona,hp,lvl FROM `users` WHERE `usr` = '$who[usr]' LIMIT 1");
$cit = mysql_fetch_array($reqs);//город

if ($cit['lvl']>=40){
$pk = " <a href=\"ok.php?k=pk&amp;id=$cit[id]\"><font color=$color>[PK]</a></font></font> |";}else {$pk = " |";}



if($mestouser['mesto']==$mesto['mesto'] && $cit['city']==$udata['city'] && $mesto['page']==$mestouser['page']  && $mesto['place']==$mestouser['place']){

if($cit['storona']==$udata['storona']){$color=="green";} else{($color="red");

}
idlog($idlog=$who['usr']);
echo "$pk";

$odin=true;
}
}
if($odin==false){
//echo' &#160 <small>Никого нет рядом!</small>';
}else{
echo '<hr/>';}
echo "</small>";
/////////////////
//-----------------------
echo" <div class=inoy><a href=\"battle.php?mod=ataka&udar=at\"> <img src=\"pic/skils/att.jpg\" height=25 width=25> Атаковать</a></div>";

$reqs = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log'");

$reqi = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$item = mysql_fetch_array($reqi);
//$req = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log' and `ruka` = '$item[ruka]'");
$req = mysql_query("SELECT * FROM `mag` WHERE
`activ` != 'no' && `usr` = '$log' && `tip` = 'hp' or
`activ` != 'no' && `usr` = '$log' && `tip` = 'hp' or
`activ` != 'no' && `usr` = '$log' && `tip` = 'hpot' or
`activ` != 'no' && `usr` = '$log' && `tip` = 'atack' && `ruka` = '' or
`activ` != 'no' && `ruka` = '$item[ruka]' && `usr` = '$log' &&
`tip` != 'bafzash' && `tip` != 'bafzat'  && `tip` != 'bafkrit'  && `tip` != 'resp'  && `tip` != 'bafat'
 && `tip` != 'bafhp'  && `tip` != 'bafmp'  && `tip` != 'spoil'   && `tip` != 'spoilall'");


if(mysql_num_rows($req)>=1)
{

echo" &#160<font color=#af9878><i> Скилы: </i><div class=inoy><br>";
echo '<table style="width:100%" cellspacing="0" cellpadding="0"><tr>';
$i = 0;
While($mag = mysql_fetch_array($req))
{
$mag['lvl']=$mag['lvl']-1;
$magmp=explode("|",$mag['mp']);
$mag['mp']=$magmp[$mag['lvl']];//мп

$maghp=explode("|",$mag['hp']);
$mag['hp']=$maghp[$mag['lvl']];//хп

$maghp=explode("|",$mag['plushp']);
$mag['plushp']=$maghp[$mag['lvl']];//+хп

$maghp=explode("|",$mag['uron']);
$mag['uron']=$maghp[$mag['lvl']];//+урон

if ($mag['uron']>=1){$mag['uron'] = "+$mag[uron] DM";}		else	{$mag['uron'] = "";}
if ($mag['mp']>=1)  {$mag['mp'] = " / -$mag[mp] MP";}			else	{$mag['mp'] = "";}
if ($mag['hp']>=1)  {$mag['hp'] = " / -$mag[hp] HP";}			else	{$mag['hp'] = "";}
if ($mag['plushp']>=1) {$mag['plushp'] = "+$mag[plushp] HP";}	else	{$mag['plushp'] = "";}
if ($mag['plusmp']>=1) {$mag['plusmp'] = "+$mag[plusmp] MP";}	else	{$mag['plusmp'] = "";}
if ($i==5){echo "</tr><tr>";}if ($i==10){echo "</tr><tr>";}if ($i==15){echo "</tr><tr>";}if ($i==20){echo "</tr><tr>";}


// откат скила
$time=time();
if ($mag['time']>$time and $mag['time']!==0)
{
$trez = $mag['time'] - $time;

echo"</div><img src=\"pic/skils/$mag[name].jpg\" height=20 width=20> <font color=#af9878> $mag[name]</font> <font color=#ffffcc> [Откат  <font color=#af9878><b>$trez</b></font> сек.]</font><div class=silka> ";

//echo "<td style=text-align:center; class=silka> <img src=\"pic/skils/$mag[name].jpg\" height=30 width=30 style='margin-right:10px;border:3px solid red;'> </td>";

}else{
//----------------
echo"<a href=\"battle.php?mod=ataka&udar=mag?$mag[id]\"><img src=\"pic/skils/$mag[name].jpg\" height=20 width=20> $mag[name] <font color=#ffffcc>[$mag[uron]$mag[plushp]$mag[mp]$mag[hp]$mag[plusmp]]</font></a> ";


//echo "<td style=text-align:center; class=silka> <a href=\"battle.php?mod=ataka&udar=mag?$mag[id]\"><img src=\"pic/skils/$mag[name].jpg\" height=30 width=30 style='margin-right:10px;border:2px solid #636261'></a> </td>";


}$i++;

}
echo '</div></tr></table>';
}

/*
echo"</div><div class=silka><br/>
<a href=\"battle.php?mod=ataka&udar=no\">Покинуть бой</a> </div>";
*/
echo '</div><br/><table style="width:100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:top;width:19%;text-align			:center;">
<a class="top_menu_link" href="/battle.php" title="Ждать">Ждать</a></td><td style="vertical-align:top;width:19%;text-align			:center;">
<a class="top_menu_link" href="/battle.php?mod=ataka&udar=no" title="Покинуть бой">Покинуть бой</a></td></tr></table>
<br>
</div></div>';


///////-----------
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `tip`='elexir'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){

echo "<form action=\"elexirs.php?btl=y\" method=\"POST\"><hr/>";

While($mag = mysql_fetch_array($req))
{
echo " <label><input type='radio' name='result' value='$mag[id]' /></label>\n";
echo " <a href=\"inventar.php?mod=info&amp;act=elexir&amp;id=$mag[id]\">$mag[name]</a>";
echo " <font color=grey>   ($mag[kol] штук) </font><br/>";

}
echo "<div class=menu><b><font color=grey>Кол-во:</font></b><br/>
<input type=\"text\" name=\"col\" size=\"5\" maxlength=\"9\"/> ";
echo "<input type='submit' name='ok' value=' Go ' /><hr/></div>\n";
}
//------------

echo "</div>";




log_msg($tlog='battle');
}
//////////
function ataka($log,$pas,$ass,$a,$m,$pit,$mob,$timer,$udata,$udar,$usr){
//защита юзера рандом
$_POST['block']=rand(1,3);

//атака юзера рандом
 if ($udar=='at') {$udar=rand(1,5);}
///////////////////////////////////////////////////////////////////////////////

// если в клане то увеличуем параметры игрока
	
$reqcla = mysql_query("SELECT patt,matt,pdef,mdef FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($reqcla);
$reqwep = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$mag = mysql_fetch_array($reqwep);

$reqshot = mysql_query("SELECT * FROM `soulshots` WHERE `usr` = '$log' and `nlvl` = '$mag[nlvl]' ");
$shot=mysql_fetch_array($reqshot);
if($shot['kol']>0){
if($shot['kol'] > 1){mysql_query("UPDATE `soulshots` SET `kol` = `kol`-1 WHERE `usr` = '$log' and `status` = '0' ");}else
{mysql_query(" DELETE FROM `soulshots` WHERE `soulshots`.`id` = $shot[id] ");}



$udata['patt']=($udata['patt']+($udata['patt']*($clan['patt']/100)))*2;
$udata['matt']=round(($udata['matt']+($udata['matt']*($clan['matt']/100)))*1.5);
$udata['pdef']=$udata['pdef']+($udata['pdef']*($clan['pdef']/100));
$udata['mdef']=$udata['mdef']+($udata['mdef']*($clan['mdef']/100));
}else{
$udata['patt']=$udata['patt']+($udata['patt']*($clan['patt']/100));
$udata['matt']=$udata['matt']+($udata['matt']*($clan['matt']/100));
$udata['pdef']=$udata['pdef']+($udata['pdef']*($clan['pdef']/100));
$udata['mdef']=$udata['mdef']+($udata['mdef']*($clan['mdef']/100));
}

if ($mob['hp']<=0){
mysql_query("DELETE FROM `tmp` WHERE usr='$log' LIMIT 1");//выходим из боя
mysql_query("INSERT INTO log SET usr='$log',text='Монстр $mob[name] уже убит!',kto='system',place='battle',timer='$timer'");
header ('Location: okrestnosti.php?',false);exit;
}



if($udar=='no'){
mysql_query("DELETE FROM `tmp` WHERE usr='$log' LIMIT 1"); // выходим из боя
mysql_query("INSERT INTO log SET usr='$log',text='Вы покинули бой!',kto='system',place='battle',timer='$timer'");
header ('Location: okrestnosti.php?',false);exit;
}
///////////////////////////////////////////////////////////////////////////////

////AI
$mudar=rand(1,3);
$mblock=rand(1,3);
////AI PITA
$pudar=rand(1,3);
$pblock=rand(1,3);
/////////////
////AI ASSA
//$audar=rand(1,3);
///$ablock=rand(1,3);
/////////////
$ud = explode("?",$udar);
if($ud[0]=='mag'){
$req = mysql_query("SELECT * FROM `mag` WHERE `id` = '$ud[1]' and `usr` = '$log' LIMIT 1");
if (mysql_num_rows($req)==0){
mysql_query("INSERT INTO log SET usr='$log',text='Нет такого умения',kto='system',place='battle',timer='$timer'");
header ("Location: battle.php?",false);exit;
}
$reqi = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$item = mysql_fetch_array($reqi);
$reqin = mysql_query("SELECT * FROM `mag` WHERE `id` = '$ud[1]' and `usr` = '$log' and `ruka` = '$item[ruka]' or `id` = '$ud[1]' and `usr` = '$log' and `ruka` = ''");
if (mysql_num_rows($reqin)==0){
mysql_query("INSERT INTO log SET usr='$log',text='Оружие не подходит умению',kto='system',place='battle',timer='$timer'");
header ("Location: battle.php?",false);exit;
}


$mag = mysql_fetch_array($req);

$mag['lvl']=$mag['lvl']-1;
$magmp=explode("|",$mag['mp']);
$mag['mp']=$magmp[$mag['lvl']];//мп

$maghp=explode("|",$mag['hp']);
$mag['hp']=$maghp[$mag['lvl']];//хп

$maghp=explode("|",$mag['plushp']);
$mag['plushp']=$maghp[$mag['lvl']];//+хп

$maghp=explode("|",$mag['uron']);
$mag['uron']=$maghp[$mag['lvl']];//+урон

if ($mag['hp']>=$udata['hp']){
mysql_query("INSERT INTO log SET usr='$log',text='Нехватает здоровья',kto='system',place='battle',timer='$timer'");
header ("Location: battle.php?",false);exit;
}

if ($mag['mp']>$udata['mp']){
mysql_query("INSERT INTO log SET usr='$log',text='Нехватает маны',kto='system',place='battle',timer='$timer'");
header ("Location: battle.php?",false);exit;
}

if ($mag['tip']=='hp' && $mag['plushp']+$udata['hp']>$udata['hpall']+100){
mysql_query("INSERT INTO log SET usr='$log',text='У Вас достаточно здоровья',kto='system',place='battle',timer='$timer'");
header ("Location: battle.php?",false);exit;
}


// откат скила защита
$time=time();
if ($mag['time']>$time and $mag['time']!==0){
mysql_query("INSERT INTO log SET usr='$log',text='Умение еще не готово',kto='system',place='battle',timer='$timer'");
header ("Location: battle.php?",false);exit;
}else{ // если скил откатился то его использует и пишет время в таблицу
$timenew=time()+10;

mysql_query("UPDATE `mag` SET `time` = '$timenew' WHERE `id` = '$ud[1]'");
}
//----------------


$bonus=rand(1,25);


if ($udata['klas']=="wizard") {$uron = $udata['matt'];} // если маг, то маг атака работает
if ($udata['klas']=="fighert") {$uron = $udata['patt'];} // если воин, то физ атака работает



//if (empty($bonus) or $bonus == 0){$bonus = 1;} // если бонус равен 0 или нет значения то от равен 1

//$uron=$uron*$bonus;
//----------

if($udata['klas']=='wizard')	{ // если маг скил умножает атаку на 0,001 и плюсует её

	$uron2 = $mag['uron'] * 0.00467 * $uron; // выщитуем маг урон
	$uron = $uron2 + $uron + $bonus;
								}else	{ // иначе когда воин просто скил плюс к урону

			$uron = $mag['uron'] + $uron + $bonus;
										}

$uron="".number_format($uron, 0, ',', "")."";


if ($udata['klas']=="wizard") {$mobdef = $mob['mdef'];} // защита маг
if ($udata['klas']=="fighert") {$mobdef = $mob['pdef'];} // защита физ

$uron=$uron-$mobdef;

if($uron<=0){$uron=round(0,1);}
$newmob=$mob['hp']-$uron;


if ($udata['hp']>=$udata['hpall']){}
else{if ($mag['tip']=="hpot"){$mag['plushp'] = round($uron*($mag['uron']/300));}}

// проверка
//if($udata[dostup]>4){$mag[plushp] = 500;}

$newmp=$udata['mp']-$mag['mp'];
$newhp=$udata['hp']-$mag['hp']+$mag['plushp'];


if($newhp>$udata['hpall']){$newhp = $udata['hpall'];}

$udata['hp'] = $newhp; // иначе не работает плюс
mysql_query("UPDATE `users` SET `mp` = '$newmp',`hp` = '$newhp' WHERE `usr` = '$log'");

if ($mag['plushp']>0){$hp111="/ +$mag[plushp] HP ";}

mysql_query("INSERT INTO log SET usr='$log',text='$smskrit $mag[name] $kuda -$uron $hp111',kto='user',place='battle',timer='$timer'");

}else{
if($udar==1){$bonus=1.05;$kuda='в голову';}
if($udar==2){$bonus=1.03;$kuda='по туловищу';}
if($udar==3){$bonus=1.00;$kuda='по ногам';}
if($udar==4){$bonus=1.01;$kuda='по рукам';}
if($udar==5){$bonus=1.02;$kuda='в плечо';}


if ($udata['klas']=="wizard") {$uron = $udata['matt'];} // если маг, то маг атака работает
if ($udata['klas']=="fighert") {$uron = $udata['patt'];} // если воин, то физ атака работает

if ($udata['klas']=="wizard") {$mobdef = $mob['mdef'];} // защита маг
if ($udata['klas']=="fighert") {$mobdef = $mob['pdef'];} // защита физ

if($uron >= $mobdef) {

$uron=round($uron*$bonus);
$uron=$uron-$mobdef;

if($uron<=0){$uron=round(0,1);}
$newmob=$mob['hp']-$uron;

$msg=rand(0,2);
if($msg=="0"){$sms="Ты бьёшь $mob[name] $kuda! -$uron";}
if($msg=="1"){$sms="Ты атакуешь $mob[name] $kuda! -$uron";}
if($msg=="2"){$sms="Ты бьёшь $mob[name] $kuda! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='user',place='battle',timer='$timer'");

}else{
$newmob=$mob['hp'];

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] пригнулся от твоего удара!";}
if($msg=="1"){$sms="$mob[name] блокировал твой удар!";}
if($msg=="2"){$sms="$mob[name] увернулся от твоего удара!";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='user',place='battle',timer='$timer'");
}
}

//$newmob = $mob[hp]-$uron;
mysql_query("UPDATE `mobs` SET `hp` = '$newmob' WHERE `id` = '$mob[id]'");

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


// ришем если юзер с тыка бьёт то он не получает урона
if ($newmob > 0){

///////////////////
if($mudar==1){$bonus=1.25;$kuda='в голову';}
if($mudar==2){$bonus=1;$kuda='по туловищу';}
if($mudar==3){$bonus=0.70;$kuda='по ногам';}


if ($udata['klas']=="wizard") {$uron = $mob['matt'];} // если маг, то маг атака работает
if ($udata['klas']=="fighert") {$uron = $mob['patt'];} // если воин, то физ атака работает

if ($udata['klas']=="wizard") {$mobdef = $udata['mdef'];} // защита маг
if ($udata['klas']=="fighert") {$mobdef = $udata['pdef'];} // защита физ

//////////////////////////////////////////////////////
if (empty($bonus)){$bomus = 1;}
///////////////////////////////////////////////////
$uron=round($uron*$bonus);

if ($uron>$mobdef){ // если урон выше защиты проводи расчёт атаки

$ner = rand(0,10);
$uron=round($uron-$mobdef+$ner);
if($uron<=0){$uron=rand(0,10);}
$newhp=$udata['hp']-$uron+$mag['plushp'];


mysql_query("UPDATE `users` SET `hp` = '$newhp' WHERE `usr` = '$log'");

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] сильно бьёт $kuda! -$uron";}
if($msg=="1"){$sms="$mob[name] атакует $kuda! -$uron";}
if($msg=="2"){$sms="$mob[name] метко бьёт $kuda! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");
}else{

$uron=rand(0,10);
$newhp=$udata['hp']-$uron;
mysql_query("UPDATE `users` SET `hp` = '$newhp' WHERE `usr` = '$log'");

$msg=rand(0,2);
if($msg=="0"){$sms="Сработала защита!  -$uron";}
if($msg=="1"){$sms="Блокировка удара!  -$uron";}
if($msg=="2"){$sms="Защита!  -$uron";}
mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");
}}
///////////////////////
//////////////////////////
if($pit['status']=='on'){
///////////////////////////
////////////////////////////


if($pudar==1){$bonus=1.25;$kuda='в голову';}
if($pudar==2){$bonus=1;$kuda='по туловищу';}
if($pudar==3){$bonus=0.70;$kuda='по ногам';}
/////////////
if($pudar != $mblock and $pit['sila'] < $mobdef){


$uron=rand(1,25)+$pit['sila'];
$uron=$uron-$mobdef;

$uron=round($uron*$bonus);
if($uron<=0){$uron=round(0,1);}
$newmob=$newmob-$uron;

$msg=rand(0,2);
if($msg=="0"){$sms="$pit[name] бьёт $mob[name] $kuda! -$uron";}
if($msg=="1"){$sms="$pit[name] атакует $mob[name] $kuda! -$uron";}
if($msg=="2"){$sms="$pit[name] бьёт $mob[name] $kuda! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='user',place='battle',timer='$timer'");

}else{
$newmob=$newmob;

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] пригнулся от удара $pit[name]!";}
if($msg=="1"){$sms="$mob[name] блокировал удар $pit[name]!";}
if($msg=="2"){$sms="$mob[name] увернулся от удара $pit[name]!";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='user',place='battle',timer='$timer'");
}
//////////////////
///////////////////
if($mudar==1){$bonus=1.25;$kuda='в голову';}
if($mudar==2){$bonus=1;$kuda='по туловищу';}
if($mudar==3){$bonus=0.70;$kuda='по ногам';}

if($pblock!=$mudar){
////////////////////////////////////////////////////////
//////////////////////////////////////////////////////
switch($mudar){
case '1':
$protect=round(($pit['prot']+$pit['pgolova'])/3);
break;
case '2':
$protect=round(($pit['prot']+$pit['pbody'])/3);
break;
case '3':
$protect=round(($pit['prot']+$pit['pnogi'])/3);
break;
}
//////////////////////////////////////////////////////
/////////////////////////////////////////////////////
//////////////////////////////////////////////////////
$uron=rand($mob['umin'],$mob['umax']);

$krit=rand(1,100);
if($krit<=$mob['krit']){

$makrit=rand(1,100);
if($makrit<=$pit['antikrit']){

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] наносит крит-удар $kuda, но $pit[name] блокирует!";}
if($msg=="1"){$sms="$mob[name] бьёть крит-удар $kuda, но $pit[name] пригнулся!";}
if($msg=="2"){$sms="$mob[name] наносит крит-удар $kuda, но $pit[name] отпрыгивает!";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");

}else{
$mno=round(($uron/100)*$mob['ukrit']);
$uron=$uron+$mno;
$uron=$uron-$protect;
$uron=round($uron*$bonus);
if($uron<=0){$uron=round(0,1);}
$newhp=$pit['hp']-$uron;

mysql_query("UPDATE `pit` SET `hp` = '$newhp' WHERE `usr` = '$log'");

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] наносит крит-удар $kuda $pit[name]! -$uron";}
if($msg=="1"){$sms="$mob[name] бьёт крит-удар $kuda $pit[name]! -$uron";}
if($msg=="2"){$sms="$mob[name] наносит крит-удар $kuda $pit[name]! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");
}
}else{
$uron=$uron-$protect;
$uron=round($uron*$bonus);
if($uron<=0){$uron=round(0,1);}
$newhp=$pit['hp']-$uron;

mysql_query("UPDATE `pit` SET `hp` = '$newhp' WHERE `usr` = '$log'");

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] сильно бьёт $kuda $pit[name]! -$uron";}
if($msg=="1"){$sms="$mob[name] атакует $kuda $pit[name]! -$uron";}
if($msg=="2"){$sms="$mob[name] метко бьёт $kuda $pit[name]! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");
}
}else{
$msg=rand(0,2);
if($msg=="0"){$sms="$pit[name] пригнулся от удара!";}
if($msg=="1"){$sms="$pit[name] блокировал удар!";}
if($msg=="2"){$sms="$pit[name] увернулся от удара!";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");
}
}
if($ass['status']=='active'){
#********************************#
if($ass['lovk']<$mob['lovk']){
$ps_l=rand(1,100);
$pshans_lovk=round(($mob['lovk']-$ass['lovk'])*5);
}
#********************************#

if($audar==1){$bonus=1.25;$kuda='в голову';}
if($audar==2){$bonus=1;$kuda='по туловищу';}
if($audar==3){$bonus=0.70;$kuda='по ногам';}
/////////////
if($audar!=$mblock and ($ass['lovk']>=$mob['lovk'] or $ps_l>$pshans_lovk)){

if($audar==1){$mprotect='pgolova';}
if($audar==2){$mprotect='pbody';}
if($audar==3){$mprotect='pnogi';}

$uron=rand($ass['umin'],$ass['umax']);

$krit=rand(1,100);
if($krit<=$ass['krit']){

$makrit=rand(1,100);
if($makrit<=$mob['antikrit']){

$msg=rand(0,2);
if($msg=="0"){$sms="$ass[name] наносит крит-удар $kuda, но $mob[name] блокирует!";}
if($msg=="1"){$sms="$ass[name] бьёт крит-удар $kuda, но $mob[name] пригнулся!";}
if($msg=="2"){$sms="$ass[name] наносит крит-удар $kuda, но $mob[name] отпрыгивает!";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='user',place='battle',timer='$timer'");
}else{
$mno=round(($uron/100)*$ass['ukrit']);
$uron=$uron+$mno;
$uron=$uron-$mob[$mprotect];
$uron=round($uron*$bonus);
if($uron<=0){$uron=round(0,1);}
$newmob=$newmob-$uron;

$msg=rand(0,2);
if($msg=="0"){$sms="$ass[name] наносит крит-удар $kuda $mob[name]! -$uron";}
if($msg=="1"){$sms="$ass[name] бьёт крит-удар $kuda $mob[name]! -$uron";}
if($msg=="2"){$sms="$ass[name] наносит крит-удар $kuda $mob[name]! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='user',place='battle',timer='$timer'");
}
}else{
$uron=$uron-$mob[$mprotect];
$uron=round($uron*$bonus);
if($uron<=0){$uron=round(0,1);}
$newmob=$newmob-$uron;

$msg=rand(0,2);
if($msg=="0"){$sms="$ass[name] бьёт $mob[name] $kuda! -$uron";}
if($msg=="1"){$sms="$ass[name] атакует $mob[name] $kuda! -$uron";}
if($msg=="2"){$sms="$ass[name] бьёт $mob[name] $kuda! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='user',place='battle',timer='$timer'");
}
}else{
$newmob=$newmob;

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] пригнулся от удара $ass[name]!";}
if($msg=="1"){$sms="$mob[name] блокировал удар $ass[name]!";}
if($msg=="2"){$sms="$mob[name] увернулся от удара $ass[name]!";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='user',place='battle',timer='$timer'");
}
//////////////////
///////////////////
if($mudar==1){$bonus=1.25;$kuda='в голову';}
if($mudar==2){$bonus=1;$kuda='по туловищу';}
if($mudar==3){$bonus=0.70;$kuda='по ногам';}

if($ablock!=$mudar){
////////////////////////////////////////////////////////
//////////////////////////////////////////////////////
switch($mudar){
case '1':
$protect=$ass['pgolova'];
break;
case '2':
$protect=$ass['pbody'];
break;
case '3':
$protect=$ass['pnogi'];
break;
}
//////////////////////////////////////////////////////
/////////////////////////////////////////////////////
//////////////////////////////////////////////////////
$uron=rand($mob['umin'],$mob['umax']);

$krit=rand(1,100);
if($krit<=$mob['krit']){

$makrit=rand(1,100);
if($makrit<=$ass['antikrit']){

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] наносит крит-удар $kuda, но $ass[name] блокирует!";}
if($msg=="1"){$sms="$mob[name] бьёть крит-удар $kuda, но $ass[name] пригнулся!";}
if($msg=="2"){$sms="$mob[name] наносит крит-удар $kuda, но $ass[name] отпрыгивает!";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");

}
$mno=round(($uron/100)*$mob['ukrit']);
$uron=$uron+$mno;
$uron=$uron-$protect;
$uron=round($uron*$bonus);
if($uron<=0){$uron=round(0,1);}
$newhp=$ass['hp']-$uron;

mysql_query("UPDATE `asasins` SET `hp` = '$newhp' WHERE `id`='$a[asasin]'");

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] наносит крит-удар $kuda $ass[name]! -$uron";}
if($msg=="1"){$sms="$mob[name] бьёт крит-удар $kuda $ass[name]! -$uron";}
if($msg=="2"){$sms="$mob[name] наносит крит-удар $kuda $ass[name]! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");
}
}else{
$uron=$uron-$protect;
$uron=round($uron*$bonus);
if($uron<=0){$uron=round(0,1);}
$newhp=$ass['hp']-$uron;

mysql_query("UPDATE `asasins` SET `hp` = '$newhp' WHERE `id`='$a[asasin]'");

$msg=rand(0,2);
if($msg=="0"){$sms="$mob[name] сильно бьёт $kuda $ass[name]! -$uron";}
if($msg=="1"){$sms="$mob[name] атакует $kuda $ass[name]! -$uron";}
if($msg=="2"){$sms="$mob[name] метко бьёт $kuda $ass[name]! -$uron";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");
}
}else{
$msg=rand(0,2);
if($msg=="0"){$sms="$ass[name] пригнулся от удара!";}
if($msg=="1"){$sms="$ass[name] блокировал удар!";}
if($msg=="2"){$sms="$ass[name] увернулся от удара!";}

mysql_query("INSERT INTO log SET usr='$log',text='$sms',kto='enemy',place='battle',timer='$timer'");

}
mysql_query("UPDATE `mobs` SET `hp` = '$newmob' WHERE `id` = '$m[mob]'");
/////////////////////////////////////////////////
header ("Location: battle.php?",false);exit; //в бой




}
/////страница!!начало
if($_GET['mod']=='ataka'){
ataka($log,$pas,$ass,$a,$m,$pit,$mob,$timer,$udata,$udar,$usr);
}else{
mein($log,$pas,$ass,$a,$m,$pit,$mob,$udata,$udar,$usr,$p);
}

include($path.'inc/down.php');
?>
