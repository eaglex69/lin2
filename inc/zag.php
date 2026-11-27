<?php
defined('PROTECTOR') or die('Error: restricted access');


if ($user_id=='1'){
if(empty($header)){

// загружаем страницу автонастроек aden
    include($path.'inc/avtoopt.php');
   
//    include($path.'inc/antimat.php'); // антимат
//////////////////////////////////
																																													
////////////////////////////////////////////////////////
echo'<div class="gameBorder"><div class="fon">';

$t=time();

////////////////эвенты //////////////
domin_eve_mob();
domin_eve_pk();
domin_eve_mob_w();
domin_eve_pk_w();
domin_eve_fish_w();
domin_eve_fish();





////////////////////////////////////

mysql_query("DELETE FROM `anti_pk` WHERE `time` <= '$t'"); // Anti PK чистим прошедшие
mysql_query("DELETE FROM `vip` WHERE `time` <= '$t'"); // ВИП акаунты чистим прошедшие


//-----			ЗАДАНИЯ			------------
$date = date("d.m.Y");
mysql_query("DELETE FROM `job_mob` WHERE `data` != '$date' and `data` != 'no'"); // Задания чистим МОБЫ
mysql_query("DELETE FROM `job_fish` WHERE `data` != '$date' and `data` != 'no'"); // Задания чистим РЫБЫ
mysql_query("DELETE FROM `job_pvp` WHERE `data` != '$date' and `data` != 'no'"); // Задания чистим PvP
mysql_query("DELETE FROM `job_col` WHERE `data` != '$date' and `data` != 'no'"); // Задания чистим CoL

	
//-----------------------------------------------------------------------------------------
// если мёрв выполнить																	 //
$reqin = mysql_query("SELECT * FROM `smert` WHERE `usr`='$log' and `life`='no'");		 //
$magia = mysql_num_rows($reqin);
if ($magia!=0) 
{
$magia = mysql_fetch_array($reqin);


$res = mysql_query ("UPDATE users SET
        hp='$magia[hp]',
        mp='$magia[mp]'
		 WHERE usr='$log' LIMIT 1");


$adres = $_SERVER['SCRIPT_NAME'];




if($adres!="/baf.php" && $adres!="/adm_panel.php" && $adres!="/res.php" && $adres!="/chat.php" && $adres!="/main.php" && $adres!="/index.php" && $adres!="/pers.php" && $adres!="/online.php" && $adres!="/forum/*" && $adres!="/msg.php"){

echo'<div class="menu">';

echo"<b> Вы убиты!</b>";
echo"<br/> Выберите способ воскрешения.<br/>";

$exp = $udata['exp']*0.005;
$exp2 = $udata['exp']*0.002;

if ($udata['lvl']<=9)	{echo"<br/><a href=\"res.php?mod=11\">Воскреснуть </a> <br/> До 9 уровня Вас воскрешают без потери опыта.";}else{
	echo"<br/><a href=\"res.php?mod=1\">Воскреснуть самому. Утеря ".number_format($exp, 0, ',', ' ')." EXP </a>";
	echo"<br/><a href=\"res.php?mod=2\">Заплатить целителю 200`000 Аден.  Утеря ".number_format($exp2, 0, ',', ' ')." EXP </a>";}

	
	
echo "</div>";



include($path.'inc/down.php');
exit;}

echo "</div>";
}																						 //
//else {echo " Вы живы <hr/>";} // нет значения											 //
//-----------------------------------------------------------------------------------------
	
	
	
/////////////////////////////////////////////////////////////////////////////////////////////
// запись Смерти юзера
$reqin = mysql_query("SELECT * FROM `smert` WHERE `usr`='$log' and `life`='no'");		 //
$magia = mysql_num_rows($reqin);
if ($udata['hp']<=0 and $magia==0)
{
$res = mysql_query ("INSERT INTO `smert` SET
        `usr` = '$log',
        `hp` = '$udata[hp]',
        `mp` = '$udata[mp]',
        `life` = 'no',
        `kom`='Убил моб'");
$loses=$udata['loses'];

$ress = mysql_query ("UPDATE users SET
        loses='$loses',
        hp='$udata[hp]'
		 WHERE usr='$log' LIMIT 1");
}
////////////////////////////////////////////////////////////////////////////////////////////////
// загрузка из функции /////////
mysql_query("DELETE FROM `paty` WHERE `act` = 'kill' && `act2` = 'kill'"); // чистим пати

/////////////////////
include($path.'inc/lvl.php');


include_once $path.'inc/lin2_ui.php';
echo'<div class="head lx-head" data-lx-live="head">';

include($path.'inc/opit.php');

if ($exp>100) 
{
$lvlplus="<font color=red>+</font>";
$exp=100;
}

$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$log' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
$col = mysql_fetch_array($req222);
$nick_html = '<font color="#'.$col['color'].'"><b>'.$log.'</b></font>'; 
}else{
$nick_html = $log;}

// шапка: аватар, ник, уровень и полосы HP / MP / EXP (оформление: theme/fantasy.css)
echo lin2_ui_avatar($udata);
echo '<div class="lx-hd"><div class="lx-nick"><span class="n">'.$nick_html.'</span><span class="lv">'.$udata['lvl'].'  ур.  '.$lvlplus.'</span></div>';

$mprez=lin2_div($udata['mp'],$udata['mpall'])*100;
if ($udata['hp']>=0){
$hprez=lin2_div($udata['hp'],$udata['hpall'])*100;

if ($hprez>100) {$hprez=100;};
if ($mprez>100) {$mprez=100;};
} else {$udata['hp']=0; $hprez=0;}

// полоса жизней, маны и опыта (ширина всегда 0..100%)
echo '<div class="lx-bar lx-hp"><i style="width:'.max(0,min(100,$hprez)).'%"></i><b>HP&nbsp;&nbsp;'.$udata['hp'].'/'.$udata['hpall'].'</b></div>';
echo '<div class="lx-bar lx-mp"><i style="width:'.max(0,min(100,$mprez)).'%"></i><b>MP&nbsp;&nbsp;'.$udata['mp'].'/'.$udata['mpall'].'</b></div>';
echo '<div class="lx-bar lx-xp"><i style="width:'.max(0,min(100,$exp)).'%"></i><b>EXP&nbsp;&nbsp;'.max(0,min(100,$exp)).'%</b></div>';
echo "</div></div>";




///////////////////////////////////////////////////////////

//-------оплатить разблок-----------------
if (isset($_GET['block_del'])){
$req = mysql_query("SELECT * FROM `block` WHERE `usr` = '$log' LIMIT 1");
$avto = mysql_num_rows($req);
if ($avto == 1) {
  
  $ban = mysql_fetch_array($req);    

  if ($ban['cena']>0){
  
$colnew = $udata['almaz']-$ban['cena'];

if ($colnew >=0)
{

mysql_query ("UPDATE users SET almaz='$colnew' WHERE usr='$log' LIMIT 1"); // пишем данные игрока с новой суммой

mysql_query("DELETE FROM `block` WHERE `usr` = '$log'"); //удаляем блок

echo "<p><b><font color=green>Блок снят</font></b></p>";

}else
{
echo "<div class=dot><p><b><font color=#ff4040>Недостаточно Coin of Luck</font></b></p></div>";
}

}}
}

//------------------------------------------------
$adres = $_SERVER['SCRIPT_NAME'];
if($adres!="/adm_panel.php" && $adres!="/main.php" && $adres!="/search.php" && $adres!="/auto.php" && $adres!="/index.php" && $adres!="/pers.php" && $adres!="/online.php" && $adres!="/tiket.php"){

// если игрок заблочен
$req = mysql_query("SELECT * FROM `block` WHERE `usr` = '$log' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($req);
if ($avto == 1) {
    $ban = mysql_fetch_array($req);    
    if($ban['ban_time']<$t){
    mysql_query("DELETE FROM `block` WHERE `usr` = '$log'");
    }else{
	echo "<div class=menu>";

    echo"<font color=#ff4040>Ваш персонаж был Заблокирован!</font></b><br/>
	<font color=#f4d06e>Кем:<b></font><a href=\"search.php?nick=$ban[log]&amp;go=go\"><font color=#ffffcc> $ban[log]</a><br/>
	<font color=#f4d06e>Причина:</b></font> <font color=#ffffcc>$ban[text]!</font><font color=#f4d06e> <br/>Осталось: ";
$ban['ban_time']=$ban['ban_time']-time();
if($ban['ban_time']<60){
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>сек.";
}elseif($ban['ban_time']>60 and $ban['ban_time']<3600){
$ban['ban_time']=round($ban['ban_time']/60);
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>мин.";
}else{$ban['ban_time']=round($ban['ban_time']/3600);
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>часов";}
echo "</font></b>"; 

 if ($ban['cena']>0){
 echo "<br/><font color=#f4d06e> Цена разбл: - <font color=#ffffcc>$ban[cena] CoL</font>";
 echo'<div class=inoy> <a href="?block_del">&#187; Оплатить</a> </div>';
}

echo "<hr/><p><font color=#f4d06e>Если вы считаете, что заблокированны несправедливо, можете обратится в \"Поддержку\"!</p></font><hr/></b></div></div></center>";
 include($path.'inc/downmain.php');exit;}}




// если игрок заблочен
$ip=htmlspecialchars(stripslashes($_SERVER['REMOTE_ADDR']));
$req = mysql_query("SELECT * FROM `block_ip` WHERE `ip` = '$ip' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($req);
if ($avto == 1) {
    $ban = mysql_fetch_array($req);    
    if($ban['ban_time']<$t){
    mysql_query("DELETE FROM `block_ip` WHERE `ip` = '$ip'");
    }else{
	echo "<div class=menu>";

    echo"<font color=#f4d06e>Ваш персонаж был Заблокирован!</font></b><br/>
	<font color=#f4d06e>Кем:<b></font><a href=\"search.php?nick=$ban[log]&amp;go=go\"><font color=#ffffcc> $ban[log]</a><br/>
	<font color=#f4d06e>Причина:</b></font> <font color=#ffffcc>$ban[text]!</font> <br/><font color=#f4d06e>Осталось: ";
$ban['ban_time']=$ban['ban_time']-time();
if($ban['ban_time']<60){
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>сек.";
}elseif($ban['ban_time']>60 and $ban['ban_time']<3600){
$ban['ban_time']=round($ban['ban_time']/60);
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>мин.";
}else{$ban['ban_time']=round($ban['ban_time']/3600);
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>часов";}
echo "</font></b>"; 

 if ($ban['cena']>0){
 echo "<br/><font color=#f4d06e> Цена разбл: - <font color=#ffffcc>$ban[cena] CoL</font>";
 echo'<div class=inoy> <a href="?block_del">&#187; Оплатить</a> </div>';
}

echo "<hr/><p><font color=#f4d06e>Если вы считаете, что заблокированны несправедливо, можете обратится в \"Поддержку\"!</p></font><hr/></b></div></div></center>";
 include($path.'inc/downmain.php');exit;}}
 }





//--------	----------	 админка	---------------------


///////////////////////////////////
$wm = mysql_num_rows(mysql_query("SELECT * FROM `zakaz_col_wm`"));// новые заявки пополнения WebMoney
if ($wm!==0){$wm1="+$wm";}

if($udata['dostup']>=4){

if($udata['dostup']==5){$gmsilka = '<a href="/gm/"><font color=#ffffcc>GM '.$wm1.'</font></a> 		<font color=#af9878>| ';}

$tk7 = mysql_query("SELECT * FROM `tiket_tm` WHERE news != ''");
$avt = mysql_num_rows($tk7);

/////////////////////////////////////////////////////////////////////////////////////
// щитаем количество постов новых													//
if ($avt>=1){																		//
While($tk = mysql_fetch_array($tk7))												//
{																					//
$rez = $tk['news'] + $rez;															//
}}																					//
if ($rez>0){$rezl = "(+$rez)";}														//

echo "<div class=admin><center>";
echo' '.$gmsilka.'


   

<a href="/adm_chat.php?"><font color=#ffffcc>АЧ</a> 		<font color=#af9878>|  

<a href="/adm_panel.php?"><font color=#ffffcc>АП</a> 		<font color=#af9878>|  
<a href="/tiket_adm.php"><font color=#ffffcc>ЦП '.$rezl.'</a> 	<br/>
</b>';
echo "</center></div></div>";
}

if($udata['dostup']==2 or $udata['dostup']==3){
echo "<div class=admin><center>";

echo'	<a href="/mod_panel.php?"><font color=#ffffcc>МД-П</a> <font color=#af9878>| 
		<a href="/adm_chat.php?"><font color=#ffffcc>Адм-Чат</a> <font color=#af9878>|	
		<a href="/tiket_adm.php"><font color=#ffffcc>ЦП '.$rezl.'</a>
		';	//
																					//
echo "</center></div></div>";																//
}
if($udata['time']<3600*24*7){
if($udata['dostup']<1 or $udata['usr']=='Ultimate'){
$frazy = mysql_fetch_array(mysql_query('SELECT * FROM `text` ORDER BY RAND() LIMIT 1'));
}}							//
///////////////////////////////////////////////////////////////////////////////////////////

$reqac = mysql_query("SELECT * FROM `academy` WHERE `student` = '$log' LIMIT 1");
$ac = mysql_num_rows($reqac); 

if($ac>0){
if($udata['lvl']>72){
$reqclan = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($reqclan);
$clanrep=$clan['rep']+1;
mysql_query("UPDATE clan SET rep = '$clanrep' WHERE lider = '$udata[clan]' LIMIT 1");
mysql_query("UPDATE users SET clan = '' WHERE usr = '$log' LIMIT 1");
mysql_query("DELETE FROM academy WHERE student = '$log' LIMIT 1");
}
}   


if($udata['dostup'] > 4){
$reqac = mysql_query("SELECT * FROM `item` WHERE `time` >1");
$ac = mysql_num_rows($reqac); 
if($ac>0){
mysql_query("DELETE FROM `item` WHERE `time`  >1 ");
}}


$reqis = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
    $prov = mysql_fetch_array($reqis);    

if($prov['place']=="chat" or $prov['place']=="forum_in" or $prov['place']=="tchat" or $prov['place']=="msg"){


// если игрок забанен
$req = mysql_query("SELECT * FROM `ban` WHERE `usr` = '$log' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($req);
if ($avto == 1) {
    $ban = mysql_fetch_array($req);    
    if($ban['ban_time']<$t){
    mysql_query("DELETE FROM `ban` WHERE `usr` = '$log'");
    }else{
echo'<div class="foot"><center>';
    echo"<font color=#f4d06e>Ваш персонаж был забанен!</font></b><br/> <font color=#f4d06e>Причина:</font> <font color=#ffffcc>$ban[text]!</font> <br/><font color=#f4d06e>Осталось: ";
$ban['ban_time']=$ban['ban_time']-time();
if($ban['ban_time']<60){
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>сек.";
}elseif($ban['ban_time']>60 and $ban['ban_time']<3600){
$ban['ban_time']=round($ban['ban_time']/60);
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>мин.";
}else{$ban['ban_time']=round($ban['ban_time']/3600);
echo "<font color=#ffffcc>$ban[ban_time] <font color=#f4d06e>часов";}
echo "</b>"; 
echo "<br/><br/>Вам закрыт доступ в Почту, Чат и Форум.<br/><br/>";
 include($path.'inc/down.php');exit;}}}
//////////////////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////


if($inpk=='1' and $headmod != 'pk'){
echo'<div class="info" align="center">';
echo"<a href=\"pk.php?\"><b>В бой</b></a>";
echo'</div>';
}
if($inar=='1' and $headmod != 'combat'){
echo'<div class="info" align="center">';
echo"<a href=\"combat.php?\"><b>В бой</b></a>";
echo'</div>';
}
$q = mysql_query("SELECT COUNT(*) FROM `msg_r` WHERE `user_to` = '$log' AND `user_from`!='Система' AND `user_from`!='Рассылка от администрации' AND `read` = '1';");
$new_mail = mysql_result($q, 0);
if($new_mail > 0){
echo'<div class=silka>';
echo"<center><a href=\"/msg.php?mod=read\"><img src=\"pic/roz/смс.png\" alt=\"\"><font color=#f4d06e> Сообщения <font color=#ff4040>+$new_mail </font></b></a></div></div>";
echo"</div>";}

$qras = mysql_query("SELECT COUNT(*) FROM `msg_r` WHERE `user_to` = '$log' AND `user_from`='Рассылка от администрации' AND `read` = '1';");
$new_ras = mysql_result($qras, 0);
if($new_ras > 0){
echo'<div class=silka>';
echo"<center><a href=\"/msg.php?mod=ras\"><img src=\"pic/roz/смс.png\" alt=\"\"><font color=#f4d06e> Рассылки <font color=#ff4040>+$new_ras </font></b></a></div></div>";
echo"</div>";}

$qsys = mysql_query("SELECT COUNT(*) FROM `msg_r` WHERE `user_to` = '$log' AND `user_from`='Система' AND `read` = '1';");
$new_sys = mysql_result($qsys, 0);
if($new_sys > 0){
echo'<div class=silka>';
echo"<center><a href=\"/msg.php?mod=sys\"><img src=\"pic/roz/смс.png\" alt=\"\"><font color=#f4d06e> Системные <font color=#ff4040>+$new_sys </font></b></a></div></div>";
echo"</div>";}





////////////		Вернуться в бой 	//////////////
//-------------------------------------
$adres = $_SERVER['SCRIPT_NAME'];
if($adres!="/battle.php" and $in_battle=='1'){ 
echo'<div class=silka><div class="info" align="center"><div style="font-size: 13px;border-bottom		:#000033 solid  1px; padding		: 1% 1% 1% 1%; width:200px;">';
echo"<a href=\"/battle.php\"><font color=#f4d06e>&#187 Вы находитесь в бою &#171 </font></b></a></div></div>";
echo"</div>";}
///////////////////////////////////////////////////////////////////////////////


////////////		Мой питомец	//////////////
//-------------------------------------
$pita345 = mysql_query("SELECT id,hp,hpall,lvl,name,lasteda FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avtopit = mysql_num_rows($pita345);     
$adres = $_SERVER['SCRIPT_NAME'];
if($avtopit==1 and $adres!="/pit.php"  and $adres!="/pitomec.php"){ $pit = mysql_fetch_array($pita345);
if ($pit['hp']>=0){
$hprezpit=lin2_div($pit['hp'],$pit['hpall'])*100;

if ($hprezpit>100) {$hprezpit=100;};
} else {$pit['hp']<=0; $hprezpit=0;}

		echo "<div class=\"paty\"><div style=margin-left:7px;><b> Питомец  с <a href=\"/pit.php?mod=mypit\">$pit[name]</a> - $pit[lvl] ур.</b><br/>";

echo '<span style="top:9px;left:2%;color:#FF6633;"> <b>HP:</b>    '.$pit['hp'].' / '.$pit['hpall'].'</span>&nbsp;&nbsp;';
echo '<span style=color:orange;>';
		$pit['lasteda']=$pit['lasteda']-time();
if($pit['lasteda']<60){
echo "[$pit[lasteda] сек.]<br/>";
}elseif($pit['lasteda']>60 and $pit['lasteda']<3600){
$pit['lasteda']=round($pit['lasteda']/60);
echo "[$pit[lasteda] мин.]<br/>";
}else{
$pit['lasteda']=round($pit['lasteda']/3600);
echo "[$pit[lasteda] ч.]<br/>";
}
echo '</span>';

echo '<div style="border:1px solid #330000;font-size: 9px;background:1px #282828;height:10px;width:120px;"><div style="background-color:#990000;height:10px;width:'.$hprezpit.'%;"></div></div>';
echo "</div></div>";
}


///////////////////////////////////////////////////////////////////////////////


//--------озеро----------
$adres = $_SERVER['SCRIPT_NAME']; $avto = mysql_num_rows(mysql_query("SELECT * FROM `fish` WHERE `usr` = '$log' LIMIT 1"));
if($adres!="/fish.php" && $adres!="/adm_panel.php" && $adres!="/res.php" && $avto==1){
echo'<div class=silka align="center"><div style="font-size: 13px;border-bottom:#000033 solid  1px; padding: 1% 1% 1% 1%; width:200px;">';
echo"<a href=\"/fish.php\"><span style=color:#f4d06e;>&#187 Вернуться к рыбалке &#171 </span></a></div></div>";}
//----------------------------------



////////////		Навыки игрока показ 	//////////////
//-------------------------------------
$adres = $_SERVER['SCRIPT_NAME'];
if($adres!="/pers.php"){
  if($udata['skill']>0){echo"<div><div class=\"info\"><div class=silka><a href=\"/pers.php?\"><font color=#ffffcc>&#187 Активные навыки </font><font color=#ff4040>+".$udata['skill']."</font></a></b></div></div>";}
}
  //-------------------------------------
///////////////////////////////////////////////////////////////////////////////
		
	//-----Создание анкеты---------
		$ank = mysql_query("SELECT * FROM `anketa` WHERE `nik` = '$log'");
		$ank=mysql_num_rows($ank);
	
		if($ank == 0)
		{
		echo " <div class=\"info\"><div class=silka><a href=\"/anketa.php?&go=4\"><font color=#ffffcc>&#187 Создать анкету</font></a></div></div>";
		}

	//------------------------------//------------------------
	
	// -------- Приглашения в клан ----------------
	$req = mysql_query("SELECT * FROM `invite` WHERE `usr` = '$log'");
	$inv=mysql_num_rows($req);

	if($inv > 0)
		{
		echo " <div class=\"info\"><div class=silka><b><a href=\"index.php?mod=inv\"><font color=silver>&#187 Приглашения в клан </font><font color=grey> +$inv </font></a></div></div>";
		}
	
	//------------------------------//------------------------
// -------- Приглашения замуж----------------
$req = mysql_query("SELECT * FROM `zamuj` WHERE `usr` = '$log'");
$avto=mysql_num_rows($req);
if($avto>=1){
echo'<div class="info">';
echo"<a href=\"".$path."zayavka.php\">Предложение замуж</a>";

echo'</div>';
}
/*
$adres = $_SERVER['SCRIPT_NAME'];
if ($udata[dostup]<4 && $adres!="/chat.php"){
echo "<hr/><p><font color=red>Приносим свои извенения. Проводятся технические работы по обновлению магазинов! Но Вы можете пока пообщаться в нашем <a href=\"chat.php\"><b>чате</b> !</a></p></font><hr/></b></div></div></center>";
 include($path.'inc/end.php');exit;}
*/

$adres = $_SERVER['SCRIPT_NAME'];
if($adres!="/paty.php" && $adres!="/forum.php"){
	
		// ---- Пати ------
// Создавший (usr)

	// приглашение
		$paty = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' and `act` = 'no'");
		$avto=mysql_num_rows($paty);
	if ($avto>=1){
		$usr = mysql_fetch_array($paty);

echo "<div class=\"paty\"><font color=#f4d06e>Вам предлогает пати <a href=\"search.php?nick=$usr[usr]&amp;go=go\">$usr[usr]</a> </b><br/>";
echo "<p> <a href=\"paty.php?&pt=yes\">Согласен</a></b> | ";
echo " <a href=\"paty.php?&pt=kill\">Отказатся</a></b></p></div>";
}else{



		$pgl = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' and `act` != 'kill'");
		$avt=mysql_num_rows($pgl);
		

		if($avt >= 1)
		{
		$pgl = mysql_fetch_array($pgl);
		$u = mysql_query("SELECT * FROM `users` WHERE `usr` = '$pgl[usr2]'");
		$usr = mysql_fetch_array($u);
if ($usr['hp']>=0){
$php=lin2_div($usr['hp'],$usr['hpall'])*100;
$pmp=lin2_div($usr['mp'],$usr['mpall'])*100;

if ($hprez>100) {$hprez=100;};
if ($mprez>100) {$mprez=100;};
} else { $php=0;}

		$pgl2 = mysql_query("SELECT * FROM `paty` WHERE `usr` = '$log' and `act2` = 'yes' Limit 1");
		$avt2=mysql_num_rows($pgl2);
if ($avt2>=1){

		echo "<div class=\"paty\"></b></font> <font color=#ffffcc>Пати с <a href=\"search.php?nick=$usr[usr]&amp;go=go\"><font color=#f4d06e>$usr[usr]</a> - <font color=#f4d06e>$usr[lvl] ур.</b><br/>";
echo '</center>	<span style="top:9px;left:2%;color:#ff4040;"> HP:</b>    '.$usr['hp'].' / '.$usr['hpall'].'</span><div style="border:1px solid #990000;font-size: 9px;background:1px #282828;height:10px;width:120px;"><div style="background-color:#990000;height:10px;width:'.$php.'%;"></div></div>';
echo '			<span style="top:9px;left:2%;color:skyblue;"> MP:</b>    '.$usr['mp'].' / '.$usr['mpall'].'</span><div style="border:1px solid #3333FF;font-size: 9px;background:1px #282828;height:10px;width:120px;"><div style="background-color:#3333FF;height:10px;width:'.$pmp.'%;"></div></div>';
		echo "<div class=\"silka\"> <a href=\"paty.php?&pt=kill\">Покинуть пати</a></b></div></div>";
		}else { 
		if ($pgl['act2']=="kill"){$sm="<font color=#ff4040> Игрок отказался от участия в пати.</font>";}else{$sm="Игроку выслано приглашение.";}
echo "<div class=\"paty\"><font color=#f4d06e></b></font> <font color=#ffffcc>Пати с <a href=\"search.php?nick=$usr[usr]&amp;go=go\"><font color=#f4d06e>$usr[usr]</a> - $usr[lvl] ур.</b><br/>";
echo " $sm";
		echo "<div class=\"silka\"> <a href=\"paty.php?&pt=kill\">Покинуть пати</a></b></div></div>";
}
 }
}
 
 
 ////////////////////
 	// приглашение
		$paty2 = mysql_query("SELECT * FROM `paty` WHERE `usr2` = '$log' and `act2` = 'no'");
		$avto2=mysql_num_rows($paty2);
	if ($avto2>=1){
		$usr = mysql_fetch_array($paty2);
	
	if ($usr['usr']=='***'){
echo "<div class=\"paty\">Вам предлогали пати! </b><br/>";
echo "<font color=#ff4040>Игрок отказался от учасия.</font> <br/>";
echo " <div class=silka><a href=\"paty.php?&pt=kill\">Отказатся так же</a></b></div></div>";
}else{

echo "<div class=\"paty\">Вам предлогает пати <a href=\"search.php?nick=$usr[usr]&amp;go=go\"><font color=#ffffcc>$usr[usr]</a> </b><br/>";
echo "<p> <a href=\"paty.php?&pt=yes\"><font color=#ffffcc>Согласен</a></b> <font color=#f4d06e>| ";
echo " <a href=\"paty.php?&pt=kill\"><font color=#ffffcc>Отказатся</a></b></p></div>";}
}else{

// принявший (usr2)		
		$p = mysql_query("SELECT * FROM `paty` WHERE `usr2` = '$log' and `act2` != 'kill'");
		$avt=mysql_num_rows($p);
		

		if($avt >= 1)
		{
		$p = mysql_fetch_array($p);
		$u = mysql_query("SELECT * FROM `users` WHERE `usr` = '$p[usr]'");
		$usr = mysql_fetch_array($u);
if ($usr['hp']>=0){
$php=lin2_div($usr['hp'],$usr['hpall'])*100;
$pmp=lin2_div($usr['mp'],$usr['mpall'])*100;

if ($hprez>100) {$hprez=100;};
if ($mprez>100) {$mprez=100;};
} else { $php=0;}

		$pgl2 = mysql_query("SELECT * FROM `paty` WHERE `usr2` = '$log' and `act` = 'yes' Limit 1");
		$avt2=mysql_num_rows($pgl2);
if ($avt2>=1){

		echo "<div class=\"paty\"></b></font><font color=#ffffcc> Пати с <a href=\"search.php?nick=$usr[usr]&amp;go=go\"><font color=#f4d06e>$usr[usr]</a> - <font color=#f4d06e>$usr[lvl] ур.</b><br/>";
echo '</center>	<span style="top:9px;left:2%;color:#ff4040;"> HP:</b>    '.$usr['hp'].' / '.$usr['hpall'].'</span><div style="border:1px solid #990000;font-size: 9px;background:1px #282828;height:10px;width:120px;"><div style="background-color:#990000;height:10px;width:'.$php.'%;"></div></div>';
echo '			<span style="top:9px;left:2%;color:skyblue;"> MP:</b>    '.$usr['mp'].' / '.$usr['mpall'].'</span><div style="border:1px solid #3333FF;font-size: 9px;background:1px #282828;height:10px;width:120px;"><div style="background-color:#3333FF;height:10px;width:'.$pmp.'%;"></div></div>';
		echo "<div class=\"silka\"> <a href=\"paty.php?&pt=kill\">Покинуть пати</a></b></div></div>";
		}else { 
		if ($p['act']=="kill"){$sm="<font color=#ff4040> Игрок отказался от участия в пати.</font>";}else{echo "Игроку выслано приглашение.";}
echo "<div class=\"paty\"></font> <font color=#ffffcc>Пати с <a href=\"search.php?nick=$usr[usr]&amp;go=go\"><font color=#f4d06e>$usr[usr]</a> - $usr[lvl] ур.</b><br/>";
echo " $sm";
		echo "<div class=\"silka\"> <b><a href=\"paty.php?&pt=kill\">Покинуть пати</a></b></div></div>";
}
 }

}
}
////////////////////////////////////////////////////////////////
color_akk();
baf();
////////////////////////////////////////////////////************************************************

$req = mysql_query("SELECT * FROM `baf` WHERE `usr` = '$log'");
$avto = mysql_num_rows($req);
if($avto>=1){


While($aur = mysql_fetch_array($req))
{

if ($aur['tip']=='bafkrit'){$tip="Критов";}
if ($aur['tip']=='bafat'){$tip="Атаки";}
if ($aur['tip']=='bafzash'){$tip="Защиты";}
if ($aur['tip']=='bafzat'){$tip="Защиты и Атаки";}

if ($aur['tip']=='5kad'){$tip="  <img src=\"pic/baff.png\" >	  ";}
if ($aur['tip']=='40kad'){$tip=" <img src=\"pic/baff.png\" > ";}
if ($aur['tip']=='300'){$tip=" <img src=\"pic/baff.png\" > ";}
if ($aur['tip']=='4001'){$tip=" <img src=\"pic/baff.png\" > ";}
if ($aur['tip']=='4002'){$tip=" <img src=\"pic/baff.png\" > ";}
if ($aur['tip']=='4003'){$tip=" <img src=\"pic/baff.png\" > ";}

if ($aur['tip']=='200kad'){$tip=" <img src=\"pic/baff.png\" > ";}
if ($aur['tip']=='200'){$tip=" <img src=\"pic/baff.png\" > ";}


echo'<div class="info">';
echo"  $tip  </b>";

$aur['time']=$aur['time']-time();
if($aur['time']<60){
if ($aur['time']<=0){$aur['time']=0;}
echo "  $aur[time] сек.";
}else{
$aur['time']=round($aur['time']/60);
echo "  $aur[time] мин.";
}
echo' </div>';
}
}
	
//-------------------лог бафов--------------------------
$req = mysql_query("SELECT * FROM `baf_log` WHERE `usr` = '$log'");
$avto = mysql_num_rows($req);
if($avto>=1){
While($baf = mysql_fetch_array($req))
{
echo "<div class=dot>$baf[text]</div>";

mysql_query("DELETE FROM `baf_log` WHERE usr='$log'");//чистим логи

}}
///////*************************************************



echo'<div class="menu"></b></a>';



// онлайн считает
include($path.'inc/avto_online.php');
//интервал между загрузкой страниц
include($path.'inc/interval.php');




////////////		открываем информацию о данных игрока снова 	//////////////
//-------------------------------------
  $req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$log' and `id` = '$udata[id]' LIMIT 1");
  $udata = mysql_fetch_assoc($req);
//-------------------------------------
///////////////////////////////////////////////////////////////////////////////




}}
else
{
echo "<div class=gameBorder><div class=menu><br/><b>Ошибка!Вы не авторизованы!</b><br/><br/><div class=silka><a href='index.php'>Авторизуйтесь</a></div></div>";include($path.'inc/end.php');exit;
}
?>