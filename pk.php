<?php
define('PROTECTOR', 1);
// PHP 8: error level / timezone / mysql_*() before the code below (this entry runs code before inc/db.php)
include_once dirname(__FILE__).'/inc/php8.php';

if($_GET['mod']=='ataka' or $_GET['mod']=='mag'){
$header=TRUE;
}else{
$textl='Битва';
}

$headmod = 'pk';//фикс. места

include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
////////////////////////



$req = mysql_query("SELECT * FROM `pk` WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Вы ненаходитесь в бою!";include($path.'inc/down.php');exit;}
$my = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `pk` WHERE `usr` = '$my[enemy]' LIMIT 1");
$pk = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$my[enemy]' LIMIT 1");
$he = mysql_fetch_array($req);
function timer(){
global $log;
$time=time();
mysql_query("UPDATE `pk` SET `ltime` = '$time' WHERE `usr` = '$log'");
}
//////////////////////
function lose(){
global $log,$pas,$my,$pk,$he,$udata;

$time=time();
$losetime=$my['ltime']+150;

if(empty($pk['ltime'])){$pk['ltime']=9290842337;}
if($udata['hp']<=0 or $time>$losetime or $my['pk']=='lose'){
$nloses=$udata['loses']+1;
if($he['hp']<=0 or ($pk['ltime']+150)<$time){
@ mysql_query("UPDATE pk SET pk = 'lose' WHERE usr = '$he[usr]'");
}else{
@ mysql_query("UPDATE pk SET pk = 'win' WHERE usr = '$he[usr]'");
}
mysql_query("UPDATE users SET loses = '$nloses',hp='0' WHERE usr = '$log'");//пишем данные в плэера
mysql_query("UPDATE regenerator SET last = '$time' WHERE usr = '$log'");//сбиваем регенерацию
mysql_query("DELETE FROM `pk` WHERE usr='$log'");//чистим логи
mysql_query("DELETE FROM `pk_log` WHERE usr='$log'");//чистим логи

$reqm = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
$mm = mysql_fetch_array($reqm);
if($mm['city']==2){
$time=time()+1200;

mysql_query("INSERT INTO
        `out` SET
        `usr` = '$log',
        `timeout` = '$time'");
        
        mysql_query("UPDATE `mesto` SET `place` = 'gorod',`city` = '0',`x` = '0',`y` = '0' WHERE `usr` = '$log'");     

}

echo"Вы проиграли бой!<br/>";
echo"<a href=\"pers.php?\">Продолжить</a>";
include($path.'inc/down.php');
exit;
}
}
function win(){
global $log,$pas,$my,$pk,$he,$udata;

$time=time();
if(empty($pk['ltime'])){$pk['ltime']=0;}
if($he['hp']<=0 or ($pk['ltime']+150)<$time or $my['pk']=='win' or empty($pk['usr'])){


$exp=round($he['hpall']/5);
$nexp=round($udata['exp']+$exp);
if($udata['lvl']==0){$udata['lvl']=1;}
$money=round(($he['money']/10));
$nmoney=round($udata['money']+$money);
$he['money']=$he['money']-$money;

if($he['hp']<=0){
@ mysql_query("UPDATE pk SET pk = 'lose' WHERE usr = '$pk[log]'");
}else{
@ mysql_query("UPDATE pk SET pk = 'win' WHERE usr = '$pk[log]'");
}

mysql_query("UPDATE `users` SET `money` = '$he[money]' WHERE `usr` = '$my[enemy]'");
mysql_query("UPDATE users SET exp='$nexp',money='$nmoney' WHERE usr = '$log'");//пишем данные в плэера
mysql_query("DELETE FROM `pk` WHERE usr='$log'");//чистим логи
mysql_query("DELETE FROM `pk_log` WHERE usr='$log'");//чистим логи

$reqm = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
$mm = mysql_fetch_array($reqm);

echo"Вы победили в бою с $he[usr]!<br/>";
echo"+$exp опыта<br/>";
if($money>0){
echo"+$money монет<br/>";}
if($mm['city']==1){
echo"<a href=\"okrestnosti.php?\">Продолжить</a>";}
elseif($mm['city']==2){
echo"<a href=\"zamok.php?\">Продолжить</a>";
}
include($path.'inc/down.php');
exit;
}
}
function goboj(){
global $log,$pas,$my,$pk,$he,$udata;

$timer=time()+300;
$rand=rand(1000,9999);

if($my['xod']=='wait' and $pk['xod']=='not'){
echo'Противник не сделал хода!<br/>';
echo"<a href=\"pk.php?r=$rand\">Обновить</a>";
include($path.'inc/down.php');exit;
}elseif($my['xod']=='wait' and $pk['xod']=='wait'){
////хз///////
if($my['udar']==1){$bonus=1.25;$kuda='в голову';}
if($my['udar']==2){$bonus=1;$kuda='в грудь';}
if($my['udar']==3){$bonus=0.75;$kuda='по ногам';}

if($pk['udar']==1){$bonus=1.25;$kuda='в голову';}
if($pk['udar']==2){$bonus=1;$kuda='в грудь';}
if($pk['udar']==3){$bonus=0.75;$kuda='по ногам';}
///////////////////
if($my['tip']=='mag'){
$req = mysql_query("SELECT * FROM `mag` WHERE `id` = '$my[mag]' and `usr` = '$log' LIMIT 1");
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

$uron=$mag['uron'];

$uron=$uron-$he['prot'];
if($uron<=0){$uron=round(0,1);}
$newhp=$he['hp']-$uron;
mysql_query("UPDATE `users` SET `hp` = '$newhp' WHERE `usr` = '$pk[usr]'");

$newmp=$udata['mp']-$mag['mp'];
$newhp=$udata['hp']-$mag['hp']+$mag['plushp'];
mysql_query("UPDATE `users` SET `mp` = '$newmp',`hp` = '$newhp' WHERE `usr` = '$log'");

mysql_query("INSERT INTO log SET usr='$log',text='Ты используешь $mag[name] $pk[usr]! -$uron',kto='user',place='pk',timer='$timer'");
mysql_query("INSERT INTO log SET usr='$pk[usr]',text='$log использует $mag[name]! -$uron',kto='enemy',place='pk',timer='$timer'");
}else{
if($my['udar']==$pk['block'] and $pk['tip']=='ataka'){
mysql_query("INSERT INTO log SET usr='$log',text='Ты бьёшь $kuda $pk[usr], но он блокировал!',kto='user',place='pk',timer='$timer'");
mysql_query("INSERT INTO log SET usr='$pk[usr]',text='$log бьёт тебя $kuda, но ты блокируешь!',kto='enemy',place='pk',timer='$timer'");
}else{

$uron=round($udata['umin'],$udata['umax'])+$udata['sila'];
$uron=round($uron*$bonus);
//////////////////////////////////////////////////////
switch($my['udar']){
case '1':
$heprotect=round(($he['prot']+$he['pgolova'])/3);
break;
case '2':
$heprotect=round(($he['prot']+$he['pbody'])/3);
break;
case '3':
$heprotect=round(($he['prot']+$he['pnogi'])/3);
break;
}

$uron=$uron-$heprotect;
if($uron<=0){$uron=round(0,1);}
$newhp=$he['hp']-$uron;
mysql_query("UPDATE `users` SET `hp` = '$newhp' WHERE `usr` = '$pk[usr]'");

mysql_query("INSERT INTO log SET usr='$log',text='Ты бьёшь $pk[usr] $kuda! -$uron',kto='user',place='pk',timer='$timer'");
mysql_query("INSERT INTO log SET usr='$pk[usr]',text='$log бьёт тебя $kuda! -$uron',kto='enemy',place='pk',timer='$timer'");

}
}
if($pk['tip']=='mag'){
$req = mysql_query("SELECT * FROM `mag` WHERE `id` = '$pk[mag]' and `usr` = '$pk[usr]' LIMIT 1");
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

$uron=$mag['uron'];

$uron=$uron-$udata['prot'];
if($uron<=0){$uron=round(0,1);}
$newhp=$udata['hp']-$uron;
mysql_query("UPDATE `users` SET `hp` = '$newhp' WHERE `usr` = '$log'");
$newmp=$he['mp']-$mag['mp'];
$newhp=$he['hp']-$mag['hp']+$mag['plushp'];
mysql_query("UPDATE `users` SET `mp` = '$newmp',`hp` = '$newhp' WHERE `usr` = '$pk[usr]'");

mysql_query("INSERT INTO log SET usr='$pk[usr]',text='Ты используешь $mag[name] $log! -$uron',kto='user',place='pk',timer='$timer'");
mysql_query("INSERT INTO log SET usr='$log',text='$pk[usr] использует $mag[name]! -$uron',kto='enemy',place='pk',timer='$timer'");

}else{
if($pk['udar']==$my['block'] and $my['tip']=='ataka'){
mysql_query("INSERT INTO log SET usr='$pk[usr]',text='Ты бьёшь $kuda $log, но он блокировал!',kto='user',place='pk',timer='$timer'");
mysql_query("INSERT INTO log SET usr='$log',text='$pk[usr] бьёт тебя $kuda, но ты блокируешь!',kto='enemy',place='pk',timer='$timer'");
}else{

$uron=round($he['umin'],$he['umax'])+$he['sila'];
$uron=round($uron*$bonus);
////////////////////////////////////////////////////////
switch($pk['udar']){
case '1':
$protect=round(($udata['prot']+$udata['pgolova'])/3);
break;
case '2':
$protect=round(($udata['prot']+$udata['pbody'])/3);
break;
case '3':
$protect=round(($udata['prot']+$pro['pnogi'])/3);
break;
}

$uron=$uron-$protect;
if($uron<=0){$uron=round(0,1);}
$newhp=$udata['hp']-$uron;

mysql_query("UPDATE `users` SET `hp` = '$newhp' WHERE `usr` = '$log'");

mysql_query("INSERT INTO log SET usr='$pk[usr]',text='Ты бьёшь $log $kuda! -$uron',kto='user',place='pk',timer='$timer'");
mysql_query("INSERT INTO log SET usr='$log',text='$pk[usr] бьёт тебя $kuda! -$uron',kto='enemy',place='pk',timer='$timer'");
}}
////////////////////конец хода
$time=time();
mysql_query("UPDATE `pk` SET `xod` = 'not',`ltime`='$time' WHERE `usr` = '$log'");
mysql_query("UPDATE `pk` SET `xod` = 'not',`ltime`='$time' WHERE `usr` = '$pk[usr]'");
echo'Ход окончен!<br/>';
echo"<a href=\"pk.php?r=$rand\">Продолжить</a>";
include($path.'inc/down.php');exit;
///////////////////////////////
}
}
function mein(){
global $log,$pas,$my,$pk,$he,$udata;
timer();
lose();
win();
goboj();
include_once dirname(__FILE__).'/inc/lin2_ui.php';
echo lin2_ui_duel(array('action'=>'pk.php?mod=ataka','key'=>'pk'.(int)$he['id'],'foe'=>$he,'me'=>$udata));

$ma = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log' LIMIT 1");
echo '<div class="lx-skills">';
if(mysql_num_rows($ma)>=1)
{
echo '<h4>Скилы:</h4>';
While($mag = mysql_fetch_array($ma))
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

echo '<a class="lx-skill" href="pk.php?mod=mag&amp;id='.(int)$mag['id'].'">'.lin2_ui_h($mag['name']).'<em>('.lin2_ui_h($mag['uron'].','.$mag['mp'].','.$mag['hp'].','.$mag['plushp']).')</em></a>';
}
}else{
echo'<h4>Нет умений</h4>';
}
echo '</div>';
log_msg($tlog='pk');
}
//////////
function ataka(){
global $log,$pas,$my,$pk,$udata,$_POST;

if($my['xod']=='wait'){
header ("Location: pk.php?");exit;
}
if(empty($_POST['udar']) or empty($_POST['block'])){
timer();
header ("Location: pk.php?");exit;
}

$time=time();

mysql_query("UPDATE `pk` SET `xod` = 'wait',`tip` = 'ataka',`ltime`='$time',`block`='$_POST[block]',`udar`='$_POST[udar]' WHERE `usr` = '$log'");

header ("Location: pk.php?");exit;
}
/////////////////
function mag(){
global $log,$pas,$my,$pk,$udata;

$timer=time()+300;

if($my['xod']=='wait'){
header ("Location: pk.php?");exit;
}
$req = mysql_query("SELECT * FROM `mag` WHERE `id` = '$_GET[id]' and `usr` = '$log' LIMIT 1");

if (mysql_num_rows($req)==0){
mysql_query("INSERT INTO log SET usr='$log',text='Нет такого умения!',kto='system',place='pk',timer='$timer'");

header ("Location: pk.php?");exit; 
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
mysql_query("INSERT INTO log SET usr='$log',text='Нехватает здоровья!',kto='system',place='pk',timer='$timer'");

header ("Location: pk.php?");exit; 
}

if ($mag['mp']>$udata['mp']){
mysql_query("INSERT INTO log SET usr='$log',text='Нехватает маны!',kto='system',place='pk',timer='$timer'");

header ("Location: pk.php?");exit; 
}

$time=time();

mysql_query("UPDATE `pk` SET 
        `xod` = 'wait',
        `tip` = 'mag',
        `ltime`='$time',
        `mag`='$_GET[id]' WHERE `usr` = '$log'");
        
header ("Location: pk.php?");exit; 
}
/////страница!!начало
if($_GET['mod']=='ataka'){
ataka();
}elseif($_GET['mod']=='mag' and $_GET['id']>'0'){
mag();
}else{
mein();
}
include($path.'inc/down.php');

?>
