<?
define('PROTECTOR', 1);

$headmod = 'zamok';//фикс. места

include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
place_city();
place_okr();
place_tower();
include($path.'inc/core.php');
if($_GET[mod]==taran or $_GET[mod]==ok){
$header=TRUE;
}else{
$textl='Замок';
}
include($path.'inc/head.php');
include($path.'inc/zag.php');



$time=date("H:i:w");
if($time>"21:00:0" && $time<"23:00:0"){}else{
echo"<p> Осада доступна с 21,00 до 23,00 каждого воскресенья ! Иди готовься к бою. </p><hr/>";
echo"<a href=\"to_gorod.php?\">В город</a><br/>";
include($path.'inc/down.php');exit;

}

////////////////////////
$random=rand(1000,9999);
$timer=time()+300;
////////////////
$req = mysql_query("SELECT * FROM `zamok` WHERE `city` = '$udata[city]'");
$zamok = mysql_fetch_array($req);

switch($_GET[mod]){

default:

if($zamok[vorota]>0 and $zamok[clan]!=$udata[clan]){
echo"<div align='center'><b><a href=\"zamok.php?r=$random\">Ждать</a></b></div>";
echo'<b>Ворота (прочность: '.$zamok[vorota].')</b><br/>';
echo"<a href=\"zamok.php?mod=taran\"><input class=\"button\" type=\"submit\" value=\"Бить\" /></a><br/>";
echo"<b>Локации:</b><br/>";
echo"<a href=\"to_gorod.php?\">Город</a><br/>";
}else{
#################################
$req = mysql_query("SELECT * FROM `guards` WHERE `city` = '$udata[city]'");
//////////////////////////////
echo"<div align='center'><b><a href=\"zamok.php?r=$random\">Ждать</a></b></div>";

if($zamok[clan]==$udata[clan]){
echo'<b>Ворота (прочность: '.$zamok[vorota].')</b><br/>';
}
While($mob = mysql_fetch_array($req))
{
if($mob[status]==on){
echo"<a href=\"zamok.php?mod=ok&amp;id=$mob[id]\">$mob[name]($mob[hp]/$mob[hpall])</a> [<a href=\"zamok.php?mod=info&amp;id=$mob[id]\">?</a>]<br/>";
}
elseif($mob[status]==battle){
$r = mysql_query("SELECT * FROM `tmp_zamok` WHERE `city` = '$udata[city]' AND `usr`='$mob[oponent]' LIMIT 1");
$m = mysql_fetch_array($r);
$d = time();
if(($d>=($m['ltime']+120) and $mob[hp]<=0) or $d>=($m['ltime']+120)){
mysql_query("UPDATE `guards` SET `status`='on',`hp`='$mob[hpall]', `oponent`='not' WHERE `id` = '$mob[id]'");
$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$mob[oponent]' LIMIT 1");
$mu = mysql_fetch_array($req);

$nloses=$mu[loses]+1;
mysql_query("UPDATE users SET loses = '$nloses',hp='0' WHERE usr = '$mob[oponent]'");//пишем данные в плэера
mysql_query("DELETE FROM `tmp_zamok` WHERE usr='$mob[oponent]'");
mysql_query("DELETE FROM `log` WHERE usr='$mob[oponent]'");

echo"<a href=\"zamok.php?mod=ok&amp;id=$mob[id]\">$mob[name]($mob[hpall]/$mob[hpall])</a> [<a href=\"zamok.php?mod=info&amp;id=$mob[id]\">?</a>]<br/>";
}else{
echo"<b><a href=\"zamok.php?mod=info&amp;id=$mob[id]\">$mob[name]($mob[hp]/$mob[hpall])</a> против <a href=\"search.php?nick=$mob[oponent]&amp;go=go\">$mob[oponent]</a></b><br/>";
}
}
elseif($mob[status]==off){
$d = time();
if($d>=($mob[oponent]+$mob[time])){

mysql_query("UPDATE `guards` SET `status`='on',`hp`='$mob[hpall]', `oponent`='not' WHERE `id` = '$mob[id]'");

echo"<a href=\"zamok.php?mod=ok&amp;id=$mob[id]\">$mob[name]($mob[hpall]/$mob[hpall])</a> [<a href=\"zamok.php?mod=info&amp;id=$mob[id]\">?</a>]<br/>";
}else{
echo"<b><a href=\"zamok.php?mod=info&amp;id=$mob[id]\">$mob[name]</a></b> [<font color='red'><b>труп</b></font>]<br/>";
}
}
}


echo"<b>Локации:</b><br/>";
echo"<a href=\"to_gorod.php?\">Город</a><br/>";
$req = mysql_query("SELECT * FROM `guards` WHERE `city` = '$udata[city]' && `status` = 'on'");
$guards=mysql_num_rows($req);
if($guards==0){
echo"<a href=\"zamok.php?mod=citadel\">Цитадель</a><br/>";
}
}
log_msg($tlog='zamok');
break;

case 'info':/*
if($zamok[vorota]>0){
header ("Location: zamok.php?");exit;
}*/
$id=$_GET[id];
$req = mysql_query("SELECT * FROM `guards` WHERE `city` = '$udata[city]' and `id`='$id' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такого стражника не существует.";include($path.'inc/down.php');exit;}
$mob = mysql_fetch_array($req);

echo"<b>$mob[name]</b><br/>";
echo"Уровень: $mob[lvl]<br/>";
echo"Жизни: $mob[hp]/$mob[hpall]<br/>";
echo"P.Att: $mob[patt]<br/>";
echo"M.Att: $mob[patt]<br/>";
echo"P.Def: $mob[pdef]<br/>";
echo"M.Def: $mob[mdef]<br/>";

echo"<a href=\"zamok.php?\">Назад</a>";
break;
case 'taran':
if($zamok[vorota]<=0){
header ("Location: zamok.php?");exit;
}else{
if($udata[lvl]<5){
mysql_query("INSERT INTO log SET usr='$log',text='У вас маленький уровень!',kto='system',place='zamok',timer='$timer'");
header ("Location: zamok.php?");exit;
}



if ($udata[klas]=="wizard") {$uron = $udata[matt];} // если маг, то маг атака работает
if ($udata[klas]=="fighert") {$uron = $udata[patt];} // если воин, то физ атака работает

$uron=rand(1,25)+$uron;

$zamok[vorota]=$zamok[vorota]-$uron;
if($zamok[vorota]<0){$zamok[vorota]=0;}
mysql_query("UPDATE `zamok` SET `vorota` = '$zamok[vorota]' WHERE `city` = '$udata[city]'");

mysql_query("INSERT INTO log SET usr='$log',text='Вы наносите воротам урон $uron',kto='user',place='zamok',timer='$timer'");
header ("Location: zamok.php?");exit;
}
break;
case 'ok':
$time=time();
////////////////////////////////////////////////////////
if($zamok[vorota]>0){
header ("Location: zamok.php?");exit;
}
if ($inaw==1){
require_once'inc/head.php';
require_once'inc/zag.php';
echo'У вас бой на арене!';
include($path.'inc/down.php');exit;
}
///////////////
$id="$_GET[id]";
////////////////////////
$req = mysql_query("SELECT * FROM `guards` WHERE `city` = '$udata[city]' and `id`='$id' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){
header ("Location: zamok.php?");exit;
}
///////////////////////////// 
$mob = mysql_fetch_array($req);
if($mob[status]==battle or $mob[status]==off){
header ("Location: zamok.php?");exit;
}
mysql_query("UPDATE `guards` SET `status` = 'battle',`oponent`='$log' WHERE `city` = '$udata[city]' and `id`='$id' LIMIT 1");

mysql_query("INSERT INTO `tmp_zamok` SET `usr` = '$log',`mob` = '$id',`city` = '$udata[city]',`ltime`='$time'");
del_log($lpl='battle');  
mysql_query("INSERT INTO log SET usr='$log',text='$log против $mob[name]!Бой начался!',kto='system',place='battle',timer='$timer'");

header ("Location: fight.php?");exit; 
break;

case 'citadel':
if($zamok[vorota]>0){
echo"Ворота целы!<br/>";
echo"<a href=\"zamok.php?\">Назад</a><br/>";
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT * FROM `guards` WHERE `city` = '$udata[city]' && `status` = 'on'");
$guards=mysql_num_rows($req);
if($guards>=1){
echo"Есть живые стражники!<br/>";
echo"<a href=\"zamok.php?\">Назад</a><br/>";
include($path.'inc/down.php');exit;
}
if($_GET[act]!='zahvat'){
echo"<a href=\"zamok.php?mod=citadel&amp;act=zahvat\">Захватить город</a> ";
}else{
if($zamok[clan]==$udata[clan]){
echo"Этот город принадлежит вашему клану!<br/>";
echo"<a href=\"zamok.php?\">Назад</a><br/>";
include($path.'inc/down.php');exit;
}


if($udata[almaz]<20){
echo"Нехватает Coin of Luck! Нужно 20 шт.<br/>";
echo"<a href=\"zamok.php?\">Назад</a><br/>";
include($path.'inc/down.php');exit;
}

$ncol=$udata[almaz]-20;
mysql_query("UPDATE `users` SET `almaz` = '$ncol' WHERE `usr`='$udata[usr]'");
/////////////////////////////////////////////////////////////////////////////////////
$req = mysql_query("SELECT * FROM `zamok` WHERE `city` = '$udata[city]'");
$zavt = mysql_num_rows($req);
if ($zavt>0){
mysql_query("UPDATE `zamok` SET `clan` = '$udata[clan]' WHERE `city`='$udata[city]'");}
else{
mysql_query("INSERT INTO
        `zamok` SET
        `clan` = '$udata[clan]',
        `city` = '$udata[city]',
        `name` = '$udata[city]',
        `vorota` = '20000',
        `col` = '20'");
}
////////////////////////////////////////////////////////////////////////////////////
echo"Город $zamok[name] захвачен вашим кланом!<br/>";
echo"<a href=\"zamok.php?\">Назад</a><br/>";
}
break;

}
include($path.'inc/down.php');
?>