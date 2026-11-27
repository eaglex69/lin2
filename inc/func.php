<?php
defined('PROTECTOR') or die('Error: restricted access');

function str2gradient($text,$from='', $to='', $mode="hex")
 {
 if($mode=="hex")
 {
 $to = hexdec($to[0].$to[1]).",".hexdec($to[2].$to[3]).",".hexdec($to[4].$to[5]);
 $from = hexdec($from[0].$from[1]).",".hexdec($from[2].$from[3]).",".hexdec($from[4].$from[5]);
 }

 if( empty($text) )
 return '';
 else
 $levels=strlen($text);

 if (empty($from))
 $from = array(0,0,255);
 else
 $from = explode(",", $from);

 if (empty($to))

 $to = array(255,0,0);
 else
 $to = explode(",", $to);

 $output = "";

 for ($i=1;$i<=$levels;$i++)
 {
 for ($ii=0;$ii<4;$ii++)
 {

 $tmp[$ii] = $from[$ii] - $to[$ii];
 $tmp[$ii] = floor($tmp[$ii] / $levels);
 $rgb[$ii] = $from[$ii] -($tmp[$ii] * $i);

 if ($rgb[$ii] > 255) $rgb[$ii] = 255;

 $rgb[$ii] = dechex($rgb[$ii]);
 $rgb[$ii] = strtoupper($rgb[$ii]);

 if (strlen($rgb[$ii]) < 2) $rgb[$ii] = "0$rgb[$ii]";
 }
 $output .= "<font color=\"#".$rgb[0].$rgb[1].$rgb[2]."\">" . $text[$i -1 ] . "</font>";
 }
 return $output."\n";
 }


//////////БОИ
function going(){
global $in_battle,$inpk,$inar,$inzasada;
///////////////////////////////БОЙ/////////////////////
if ($in_battle=='1'){
header ('Location: battle.php?');exit;
}
/////////////////////////////////////////////////////////////////
elseif ($inpk=='1'){
header ('Location: pk.php?');exit;
}
///////////////////////////////////////////////////////////////////
elseif ($inar=='1'){
header ('Location: combat.php?');exit;
}
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////// 		Event MOB 		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function domin_eve_mob(){
global $udata;
$date = date("d.m.Y");
$times = date("H:i");


//$timeevent = mysql_fetch_array(mysql_query("SELECT * FROM `time`")); // откроем таблицу времени
$dabar = time();

$datans = date("H:i");

if ($datans == '00:25'){ // в 3 часа дня воскресенье



$avto=mysql_num_rows(mysql_query("SELECT * FROM `eve_mob`"));
if($avto > 0){


if (empty($i)){$i=1;}
while ($i <= 3){

$req = mysql_query("SELECT * FROM `eve_mob` ORDER BY `skoko` DESC LIMIT 1");
$mag = mysql_fetch_array($req);


if ($i==1) {$mesto="1"; $col=45;}
if ($i==2) {$mesto="2"; $col=30;}
if ($i==3) {$mesto="3"; $col=20;}
if ($i==3){$br="<hr/><br/><br/>";}

//перед тем, что бы писать первого, чистим таблицу


if (empty($mag['usr'])){$mag['usr']="Пусто";}
$msg = ".:: <b>$mag[usr]</b> победитель эвента <font color=#8B475D>\"Убийца монстров\"</font> занявший $mesto место от $date . Награда $col Coin of Luck $br ";
mysql_query("INSERT INTO event SET data='$date',text='$msg'"); // пишем что победитель и место
mysql_query("DELETE FROM `eve_mob` WHERE `usr` = '$mag[usr]'");//удаляем первого

$time = date("H:i d.m.y");
$msg = ".:: <b>$mag[usr]</b> победитель эвента <font color=#8B475D>\"Убийца монстров\"</font> занявший $mesto место от $date . Награда $col Coin of Luck! ";
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '$mag[usr]', `time` = '$time', `read` = 1, `mail_msg` = '$msg'"); // отправляем сообщение


$requs = mysql_query("SELECT * FROM `users` WHERE `usr`='$mag[usr]'");
$usr = mysql_fetch_array($requs);
$col=$usr['almaz']+$col; // плюсуем награду к тому что есть

mysql_query("UPDATE `users` SET `almaz` = '$col' WHERE `usr` = '$mag[usr]'");// даем награды за приз места

$i++;}

mysql_query("DELETE FROM `eve_mob`");//чистим логи всех участников
}

$newtm = time()+604800;
mysql_query("UPDATE `time` SET `eve_mob` = '$newtm'"); //  записуем следущее время запуска
}}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////// 		Event PK 		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function domin_eve_pk(){
global $udata;
$date = date("d.m.Y");
$times = date("H:i");


//$timeevent = mysql_fetch_array(mysql_query("SELECT * FROM `time`")); // откроем таблицу времени
$dabar = time();


$datans = date("H:i");

if ($datans == '00:25'){ // в 3 часа дня воскресенье



$avto=mysql_num_rows(mysql_query("SELECT * FROM `eve_pk`"));
if($avto > 0){


if (empty($i)){$i=1;}

while ($i <= 3){

$req = mysql_query("SELECT * FROM `eve_pk` ORDER BY `skoko` DESC LIMIT 1");
$mag = mysql_fetch_array($req);


if ($i==1) {$mesto="1"; $col=40;}
if ($i==2) {$mesto="2"; $col=25;}
if ($i==3) {$mesto="3"; $col=15;}
if ($i==3){$br="<hr/><br/><br/>";}

if (empty($mag['usr'])){$mag['usr']="Пусто";}
$msg = ".:: <b> $mag[usr]</b> победитель эвента <font color=#B22222>\"Убийца в PK\"</font> занявший $mesto место от $date . Награда $col Coin of Luck $br  ";
mysql_query("INSERT INTO event SET data='$date',text='$msg'"); // пишем что победитель и место
mysql_query("DELETE FROM `eve_pk` WHERE `usr` = '$mag[usr]'");//удаляем первого


$time = date("H:i d.m.y");
$msg = ".:: <b> $mag[usr]</b> победитель эвента <font color=#B22222>\"Убийца в PK\"</font> занявший $mesto место от $date . Награда $col Coin of Luck! ";
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '$mag[usr]', `time` = '$time', `read` = 1, `mail_msg` = '$msg'"); // отправляем сообщение

$requs = mysql_query("SELECT * FROM `users` WHERE `usr`='$mag[usr]'");
$usr = mysql_fetch_array($requs);
$col=$usr['almaz']+$col; // плюсуем награду к тому что есть

mysql_query("UPDATE `users` SET `almaz` = '$col' WHERE `usr` = '$mag[usr]'");// даем награды за приз места

$i++;}

mysql_query("DELETE FROM `eve_pk`");//чистим логи всех участников

}
$newtm = time()+604800;
mysql_query("UPDATE `time` SET `eve_pk` = '$newtm'"); //  записуем следущее время запуска

}}

function domin_eve_mob_w(){
global $udata;
$date = date("d.m.Y");
$times = date("H:i");
$dabar = time();
$datans = date("H:i-w");
if ($datans == '00:00-0'){ // в 3 часа дня воскресенье
$avto=mysql_num_rows(mysql_query("SELECT * FROM `eve_mob_w`"));
if($avto> 0){
if (empty($i)){$i=1;}
while ($i <= 3){
$req = mysql_query("SELECT * FROM `eve_mob_w` ORDER BY `skoko` DESC LIMIT 1");
$mag= mysql_fetch_array($req);
if ($i==1) {$mesto="1"; $col=180;}
if ($i==2) {$mesto="2"; $col=120;}
if ($i==3) {$mesto="3"; $col=80;}
if ($i==3){$br="<hr/><br/><br/>";}
if (empty($mag['usr'])){$mag['usr']="Пусто";}
$msg = ".:: <b>$magmob[usr]</b> победитель недельного эвента <font color=#8B475D>\"Убийца монстров\"</font> занявший $mesto место от $date . Награда $col Coin of Luck $br ";
mysql_query("INSERT INTO event_w SET data='$date',text='$msg'"); // пишем что победитель и место
mysql_query("DELETE FROM `eve_mob` WHERE `usr` = '$mag[usr]'");//удаляем первого
$time = date("H:i d.m.y");
$msg = ".:: <b>$mag[usr]</b> победитель недельного эвента <font color=#8B475D>\"Убийца монстров\"</font> занявший $mesto место от $date . Награда $col Coin of Luck! ";
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '$mag[usr]', `time` = '$time', `read` = 1, `mail_msg` = '$msg'"); // отправляем сообщение
$requ = mysql_query("SELECT * FROM `users` WHERE `usr`='$mag[usr]'");
$usr = mysql_fetch_array($requ);
$colmob=$usr['almaz']+$col; // плюсуем награду к тому что есть
mysql_query("UPDATE `users` SET `almaz` = '$colmob' WHERE `usrmob` = '$mag[usr]'");// даем награды за приз места
$i++;}
mysql_query("DELETE FROM `eve_mob_w`");//чистим логи всех участников
}
$newtm = time()+604800;
mysql_query("UPDATE `time` SET `eve_mob_w` = '$newtm'"); // записуем следущее время запуска
}}


function domin_eve_pk_w(){
global $udata;
$date = date("d.m.Y");
$times = date("H:i");
$dabar = time();
$datans = date("H:i-w");
if ($datans == '00:00-0'){ // в 3 часа дня воскресенье
$avto=mysql_num_rows(mysql_query("SELECT * FROM `eve_pk_w`"));
if($avto > 0){
if (empty($i)){$i=1;}
while ($i <= 3){
$req = mysql_query("SELECT * FROM `eve_pk_w` ORDER BY `skoko` DESC LIMIT 1");
$mag = mysql_fetch_array($req);
if ($i==1) {$mesto="1"; $col=160;}
if ($i==2) {$mesto="2"; $col=100;}
if ($i==3) {$mesto="3"; $col=60;}
if ($i==3){$br="<hr/><br/><br/>";}
if (empty($mag['usr'])){$mag['usr']="Пусто";}
$msg = ".:: <b>$mag[usr]</b> победитель эвента <font color=#8B475D>\"Убийца РК\"</font> занявший $mesto место от $date . Награда $col Coin of Luck $br ";
mysql_query("INSERT INTO event_w SET data='$date',text='$msg'"); // пишем что победитель и место
mysql_query("DELETE FROM `eve_pk_w` WHERE `usr` = '$mag[usr]'");//удаляем первого
$time = date("H:i d.m.y");
$msg = ".:: <b>$mag[usr]</b> победитель эвента <font color=#8B475D>\"Убийца РК\"</font> занявший $mesto место от $date . Награда $col Coin of Luck! ";
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '$mag[usr]', `time` = '$time', `read` = 1, `mail_msg` = '$msg'"); // отправляем сообщение
$requs = mysql_query("SELECT * FROM `users` WHERE `usr`='$mag[usr]'");
$usr = mysql_fetch_array($requs);
$col=$usr['almaz']+$col; // плюсуем награду к тому что есть
mysql_query("UPDATE `users` SET `almaz` = '$col' WHERE `usr` = '$mag[usr]'");// даем награды за приз места
$i++;}
mysql_query("DELETE FROM `eve_pk_w`");//чистим логи всех участников
}
$newtm = time()+604800;
mysql_query("UPDATE `time` SET `eve_pk_w` = '$newtm'"); // записуем следущее время запуска
}}

function domin_eve_fish_w(){
global $udata;
$date = date("d.m.Y");
$times = date("H:i");
$dabar = time();
$datans = date("H:i-w");
if ($datans == '00:00-0'){ // в 3 часа дня воскресенье
$avto=mysql_num_rows(mysql_query("SELECT * FROM `fish_log_w`"));
if($avto > 0){
if (empty($i)){$i=1;}
while ($i <= 3){
$req = mysql_query("SELECT * FROM `fish_log_w` ORDER BY `skoko` DESC LIMIT 1");
$mag = mysql_fetch_array($req);
if ($i==1) {$mesto="1"; $col=160;}
if ($i==2) {$mesto="2"; $col=100;}
if ($i==3) {$mesto="3"; $col=60;}
if ($i==3){$br="<hr/><br/><br/>";}
if (empty($mag['usr'])){$mag['usr']="Пусто";}
$msg = ".:: <b>$mag[usr]</b> победитель эвента <font color=#8B475D>\"Недельный рыбак\"</font> занявший $mesto место от $date . Награда $col Coin of Luck $br ";
mysql_query("INSERT INTO event_w SET data='$date',text='$msg'"); // пишем что победитель и место
mysql_query("DELETE FROM `fish_log_w` WHERE `usr` = '$mag[usr]'");//удаляем первого
$time = date("H:i d.m.y");
$msg = ".:: <b>$mag[usr]</b> победитель эвента <font color=#8B475D>\"Недельный рыбак\"</font> занявший $mesto место от $date . Награда $col Coin of Luck! ";
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '$mag[usr]', `time` = '$time', `read` = 1, `mail_msg` = '$msg'"); // отправляем сообщение
$requs = mysql_query("SELECT * FROM `users` WHERE `usr`='$mag[usr]'");
$usr = mysql_fetch_array($requs);
$col=$usr['almaz']+$col; // плюсуем награду к тому что есть
mysql_query("UPDATE `users` SET `almaz` = '$col' WHERE `usr` = '$mag[usr]'");// даем награды за приз места
$i++;}
mysql_query("DELETE FROM `fish_log_w`");//чистим логи всех участников
}
$newtm = time()+604800;
mysql_query("UPDATE `time` SET `fish_log_w` = '$newtm'"); // записуем следущее время запуска
}}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////// 		Event FISH 		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function domin_eve_fish(){
global $udata;
$date = date("d.m.Y");
$times = date("H:i");


//$timeevent = mysql_fetch_array(mysql_query("SELECT * FROM `time`")); // откроем таблицу времени
$dabar = time();


$datans = date("H:i");

if ($datans == '00:25'){ // в 3 часа дня воскресенье



$avto=mysql_num_rows(mysql_query("SELECT * FROM `fish_log`"));
if($avto > 0){


if (empty($i)){$i=1;}

while ($i <= 3){

$req = mysql_query("SELECT * FROM `fish_log` ORDER BY `skoko` DESC LIMIT 1");
$mag = mysql_fetch_array($req);


if ($i==1) {$mesto="1"; $col=40;}
if ($i==2) {$mesto="2"; $col=25;}
if ($i==3) {$mesto="3"; $col=15;}
if ($i==3){$br="<hr/><br/><br/>";}

if (empty($mag['usr'])){$mag['usr']="Пусто";}
$msg = ".:: <b> $mag[usr]</b> победитель эвента <font color=#527F3F>\"Рыбак\"</font> занявший $mesto место от $date . Награда $col Coin of Luck $br  ";
mysql_query("INSERT INTO event SET data='$date',text='$msg'"); // пишем что победитель и место
mysql_query("DELETE FROM `fish_log` WHERE `usr` = '$mag[usr]'");//удаляем первого


$time = date("H:i d.m.y");
$msg = ".:: <b> $mag[usr]</b> победитель эвента <font color=#527F3F>\"Рыбак\"</font> занявший $mesto место от $date . Награда $col Coin of Luck! ";
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '$mag[usr]', `time` = '$time', `read` = 1, `mail_msg` = '$msg'"); // отправляем сообщение

$requs = mysql_query("SELECT * FROM `users` WHERE `usr`='$mag[usr]'");
$usr = mysql_fetch_array($requs);
$col=$usr['almaz']+$col; // плюсуем награду к тому что есть

mysql_query("UPDATE `users` SET `almaz` = '$col' WHERE `usr` = '$mag[usr]'");// даем награды за приз места

$i++;}

mysql_query("DELETE FROM `fish_log`");//чистим логи всех участников

}
$newtm = time()+604800;
mysql_query("UPDATE `time` SET `eve_fish` = '$newtm'"); //  записуем следущее время запуска

}}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


function auto_clean(){
global $log;

$req = mysql_query("SELECT * FROM `log` WHERE `usr` = '$log' LIMIT 20");
$avto = mysql_num_rows($req);
if($avto>='20'){
$lim=round($avto/2);
mysql_query("DELETE FROM `log` WHERE `usr` = '$log' LIMIT $lim");
}
}
///////////////////////////////////
function del_log($lpl){
global $log;
mysql_query("DELETE FROM `log` WHERE `usr` = '$log' and `place` = '$lpl'");
}
/////// лог для окресностей
function log_msg_okr($tlog){
global $log;

$req=mysql_query("SELECT * FROM log WHERE usr = '$log' and place='$tlog' ORDER BY id DESC LIMIT 1");
$avto = mysql_num_rows($req);
if($avto>=1){
echo'<div class="logtext">';
While($ilok = mysql_fetch_assoc($req))
{
if($ilok['kto']=='system'){$color='grey';}
if($ilok['kto']=='user'){$color='green';}
if($ilok['kto']=='enemy'){$color='red';}
echo '<small><font color="'.$color.'">'.$ilok['text'].'</font></small><br/>';
}
echo'</div>';
}
mysql_query("DELETE FROM log WHERE usr = '$log' and place='$tlog'");
}
//////////////////////////////////////////////////////

/////// лог для PK
function log_msg_pk($tlog){
global $log;

$req=mysql_query("SELECT * FROM logpk WHERE usr = '$log' and place='$tlog' and new >= '1' ORDER BY id DESC");
$avto = mysql_num_rows($req);
if($avto>=1){
echo'<hr>';
While($ilok = mysql_fetch_assoc($req))
{
if($ilok['kto']=='system'){$color='grey';}
if($ilok['kto']=='user'){$color='green';}
if($ilok['kto']=='enemy'){$color='red';}
echo '<small><font color="'.$color.'">'.$ilok['text'].'</font></small><br/>';
}
echo'<hr>';
}
mysql_query("DELETE FROM logpk WHERE usr = '$log' and place='$tlog' and new >= '1'");

}
//////////////////////////////////////////////////////
// лог для АРЕНЫ
function log_msg_ar($tlog){
global $log;

$req=mysql_query("SELECT * FROM logar WHERE usr = '$log' and place='$tlog' and new >= '1' ORDER BY id DESC");
$avto = mysql_num_rows($req);
if($avto>=1){
echo'<hr>';
While($ilok = mysql_fetch_assoc($req))
{
if($ilok['kto']=='system'){$color='grey';}
if($ilok['kto']=='user'){$color='green';}
if($ilok['kto']=='enemy'){$color='red';}
echo '<small><font color="'.$color.'">'.$ilok['text'].'</font></small><br/>';
}
echo'<hr>';
}
mysql_query("DELETE FROM logar WHERE usr = '$log' and place='$tlog' and new >= '1'");

}
//////////////////////////////////////////////////////


function log_msg($tlog){
global $log;

$req=mysql_query("SELECT * FROM log WHERE usr = '$log' and place='$tlog' ORDER BY id DESC LIMIT 6");
$avto = mysql_num_rows($req);
if($avto=1){
echo'<div class="logtext" data-lx-live="log">';
While($ilok = mysql_fetch_assoc($req))
{
if($ilok['kto']=='system'){$color='grey';}
if($ilok['kto']=='user'){$color='green';}
if($ilok['kto']=='enemy'){$color='red';}
echo '<small><font color="'.$color.'">'.$ilok['text'].'</font></small><br/>';
}
echo'</div>';
}
}


function idlog_online($idlog){

$reqi = mysql_query("SELECT lvl,storona,dostup,clan,usr FROM `users` WHERE `usr` = '$idlog' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($reqi);
if($avto>=1){

$ref = mysql_fetch_assoc($reqi);

//картинка клана 
if(!empty($ref['clan'])){
$req = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$ref[clan]'");
$wh = mysql_fetch_array($req);

if(!empty($wh['emblema'])){
$pic = "<img src=\"pic/clan/$wh[emblema]\" alt=\"clan\"/>";}}


if ($ref['dostup']==5){$color="<font color=lime>";}
if ($ref['dostup']==4){$color="<font color=lime>";}
if ($ref['dostup']>=2 && $ref['dostup']<4){$color="<font color=#547ef4>";}
if ($ref['dostup']>=2 && $ref['dostup']<4){$color="<font color=#547ef4>";}
if ($ref['dostup']<2){
$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$ref[usr]' LIMIT 1"); 
$avto=mysql_num_rows($req222);
if($avto==1){
$cl = mysql_fetch_array($req222);
$color = "<font color=#$cl[color]>";}else{
$cl = "";}
}


echo '<br><a href="search.php?nick='.$idlog.'&amp;go=go"><img src="pic/12-12-12.png" alt="pic" />  '.$color.' '.$idlog.'<font color=#ffffcc>, </font> <font color=#ffffcc>'.$ref['lvl'].' уровень</font></a> </font></br>';
}
}


function idlog_2($idlog){

$reqi = mysql_query("SELECT lvl,storona,dostup,karma,clan FROM `users` WHERE `usr` = '$idlog' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($reqi);
if($avto>=1){

$ref = mysql_fetch_assoc($reqi);

//картинка клана 
if(!empty($ref['clan'])){
$req = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$ref[clan]'");
$wh = mysql_fetch_array($req);

if(!empty($wh['emblema'])){
$pic = "<img src=\"pic/clan/$wh[emblema]\" alt=\"clan\"/>";}}


if ($ref['dostup']==5){$color="<font color=lime>";}
if ($ref['dostup']==4){$color="<font color=lime>";}
if ($ref['dostup']>=2 && $ref['dostup']<4){$color="<font color=#547ef4>";}
if ($ref['dostup']<2){
$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$ref[usr]' LIMIT 1"); 
$avto=mysql_num_rows($req222);
if($avto==1){
$cl = mysql_fetch_array($req222);
$color = "<font color=#$cl[color]>";}else{
$cl = "";}
}
if ($ref['karma']>=1){$color="<font color=#D80000>";}
echo ' '.$color.'<b>'.$idlog.'</b>  </font> <font color=grey>['.$ref['lvl'].']</font> '.$pic.' ';
}
}





function idlog($idlog){

$reqi = mysql_query("SELECT lvl,storona,dostup,karma,clan FROM `users` WHERE `usr` = '$idlog' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($reqi);
if($avto>=1){

$ref = mysql_fetch_assoc($reqi);

//картинка клана 
if(!empty($ref['clan'])){
$req = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$ref[clan]'");
$wh = mysql_fetch_array($req);

if(!empty($wh['emblema'])){
$pic = "<img src=\"pic/clan/$wh[emblema]\" alt=\"clan\"/>";}}

if ($ref['dostup']==5){$color="<font color=lime>";}
if ($ref['dostup']==4){$color="<font color=lime>";}
if ($ref['dostup']>=2 && $ref['dostup']<4){$color="<font color=#547ef4>";}
if ($ref['dostup']<2){
$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$ref[usr]' LIMIT 1"); 
$avto=mysql_num_rows($req222);
if($avto==1){
$cl = mysql_fetch_array($req222);
$color = "<font color=#$cl[color]>";}else{
$cl = "";}
}
if ($ref['karma']>=1){$color="<font color=#D80000>";}
echo ' '.$pic.'<a href="search.php?nick='.$idlog.'&amp;go=go"> '.$color.''.$idlog.'</font></a> ';
}
}


///РЕФЕРАЛЫ
function ref(){
global $log,$udata;

if(!empty($udata['ref'])){

$avtoref = mysql_num_rows(mysql_query("SELECT * FROM `ref` WHERE `ref` = '$udata[ref]'  and `usr` = '$log'  and `tip` = '500' LIMIT 1"));

//-- +50col при 150 уровне --// 
if($udata['lvl']>=150 and $avtoref==0){

$req = mysql_query("SELECT `almaz` FROM `users` WHERE `usr` = '$udata[ref]' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($req);

if ($avto == 1) {
    $refer = mysql_fetch_assoc($req);
    
    $refer['almaz']=$refer['almaz']+50; // 50 col
    
    mysql_query("UPDATE users SET almaz = '$refer[almaz]' WHERE usr = '$udata[ref]' LIMIT 1");
	//---создаём таблицу вознагрождения--//	
	$data = date("y/m/d H:i:s", strtotime("+20 seconds"));
	mysql_query("INSERT INTO `ref` SET `usr` = '$log',`ref` = '$udata[ref]',`tip` = '500',`data` = '$data'");
	}}

//-------------------------------	---------------- 	-------------------
//-- +5 коп при 10 уровне --// 
$avtoref = mysql_num_rows(mysql_query("SELECT * FROM `ref` WHERE `ref` = '$udata[ref]'  and `usr` = '$log'  and `tip` = '5' LIMIT 1"));

if($udata['lvl']>=3 and $avtoref==0){

$req = mysql_query("SELECT `wmr` FROM `users` WHERE `usr` = '$udata[ref]' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($req);

if ($avto == 1) {
    $refer = mysql_fetch_assoc($req);
    
    $refer['wmr']=$refer['wmr']+5; // 500 коп = 5 руб
    
    mysql_query("UPDATE users SET wmr = '$refer[wmr]' WHERE usr = '$udata[ref]' LIMIT 1");
	//---создаём таблицу вознагрождения--//	
	$data = date("y/m/d H:i:s", strtotime("+20 seconds"));
	mysql_query("INSERT INTO `ref` SET `usr` = '$log',`ref` = '$udata[ref]',`tip` = '5',`data` = '$data'");
	
}}
//--------------------------------

}}
/////////////////////




function clanwarn(){
global $log,$udata;
$time=time();
///////
$req = mysql_query("SELECT * FROM `clanwar` WHERE `timeout`<'$time' and `clan`='$udata[clan]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
mysql_query("DELETE FROM `clanwar` WHERE `usr` = '$log'");
}
}
//////////////////////


//////////////////////Color akk/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function color_akk(){
global $log,$udata;
$time=time()+5;
///////
$req = mysql_query("SELECT * FROM `color_akk` WHERE `time`<='$time' Limit 1");
$avto=mysql_num_rows($req);

if($avto==1){
   $col = mysql_fetch_array($req);
      
mysql_query("DELETE FROM `color_akk` WHERE `id` = '$col[id]' LIMIT 1");
}
}
////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////БАФЫ/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function baf(){
global $log,$udata;
$time=time()+5;
///////
$req = mysql_query("SELECT * FROM `baf` WHERE `time`<='$time' and `usr`='$log' LIMIT 1");
$avto=mysql_num_rows($req);

if($avto>=1){

while ($mag = mysql_fetch_array($req)){



$patt=$udata['patt']-$mag['bafat'];
$matt=$udata['matt']-$mag['bafat'];
$pdef=$udata['pdef']-$mag['pmdef'];
$mdef=$udata['mdef']-$mag['pmdef'];


mysql_query("UPDATE `users` SET
         `patt` = '$patt',
         `matt` = '$matt',
         `pdef` = '$pdef',
         `mdef` = '$mdef' WHERE usr = '$log'");
          
          
mysql_query("DELETE FROM `baf` WHERE `usr` = '$log'");

}}}
////////////////////////



///////КОРМИМ ПИТОВ
function pit_eda(){
global $log;

$time=time();
$req = mysql_query("SELECT `dies`,`lasteda`,`name` FROM `pit` WHERE `usr` = '$log' and `status`='on' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto>='1'){
$pit = mysql_fetch_assoc($req);
if($pit['lasteda']<=$time){
$pit['dies']=$pit['dies']+1;
mysql_query("UPDATE `pit` SET `hp` = '0',`dies`='$pit[dies]',`status`='die' WHERE `usr` = '$log' and `status`='on' LIMIT 1");
///пишем письмо
$time = date("H:i d.m.y");
$text = 'Ваш питомец <b>'.$pit['name'].'</b> погиб от голода! Вы можете его воскресить в питомнике!';
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Зверовод', `user_to` = '$log', `time` = '$time', `read` = 1, `mail_msg` = '$text'");
}
}
}
////////////////

//навигация
function place_okr(){
global $log;

$req = mysql_query("SELECT `city` FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
$mestouser = mysql_fetch_assoc($req);
if($mestouser['city']=='1'){
header ('Location: okrestnosti.php?');exit;
}
}

//навигация
function place_zamok(){
global $log;

$req = mysql_query("SELECT `city` FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
$mestouser = mysql_fetch_assoc($req);
if($mestouser['city']=='2'){
header ('Location: zamok.php?');exit;
}
}

//навигация
function place_tower(){
global $log;

$req = mysql_query("SELECT `city` FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
$mestouser = mysql_fetch_assoc($req);
if($mestouser['city']=='3'){
header ('Location: towers.php?');exit;
}
}

//навигация
function place_city(){
global $log;

$req = mysql_query("SELECT `city` FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
$mestouser = mysql_fetch_assoc($req);
if($mestouser['city']=='0'){
header ('Location: gorod.php?');exit;
}
}






function navig2($page, $link, $pages){
	for($i = 1; $i <= $pages; $i++){
		if($i != $page){
			echo '<a href="'.$link.'page='.$i.'">'.$i.'</a> ';
		}
		else{
			echo '<u>'.$i.'</u> '; // выводим активую страницу текстом
		}
	}
	if($pages>=1){
	echo '<br/>';
	}


}


?>