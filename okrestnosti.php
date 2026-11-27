<?
define('PROTECTOR', 1);

$headmod = 'okrestnosti';//фикс. места

include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
place_city();
place_zamok();
place_tower();
include($path.'inc/core.php');



// анти откат //
$anti_otk=rand(741,942431);

if(isset($anti_otk)){ $_SESSION['anti_otk']=$anti_otk; }

mysql_query("UPDATE `mesto` SET `anti_otkat`='$anti_otk' WHERE `usr` = '$udata[usr]'"); // пишем новые данные



//----------------
$time=time();
//----------------
$lr=$time-60*5; // 5 минут боя
mysql_query("DELETE FROM `tmp` WHERE  `ltime` < '$lr'");

// корегируем мобов с минусовым значением на ХП 
$r = mysql_query("SELECT * FROM `mobs` WHERE `hp` < '0' and `status` = 'on'");
while ($mob = mysql_fetch_array($r)){

// проверка моба в бою он или нет
$r2boy = mysql_num_rows(mysql_query("SELECT * FROM `tmp` WHERE `mob` = '$mob[id]'"));
if (empty($r2boy)){ // моба нет в логах боя, корегируем его
mysql_query("UPDATE `mobs` SET `status`='off',`hp`='0', `oponent`='$time' WHERE `id` = '$mob[id]'");
}
}


/////////место
$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
$mestouser = mysql_fetch_array($req);
/////////////////////////////////////////
$req = mysql_query("SELECT * FROM `okra` WHERE `name` = '$mestouser[mesto]'");
$world = mysql_fetch_array($req);
//////////////////////////////////////////

include($path.'inc/head.php');
include($path.'inc/zag.php');
////////////////////////
$random=rand(1000,9999);
////////////////

switch($_GET[mod]){

default:

if ($mestouser[page] == "" || $mestouser[page] < 0 || $mestouser[page] == "0") {$mestouser[page] = 0;}

if ($_GET[mod]==go){ // вперёд
$viso = mysql_num_rows(mysql_query("SELECT * FROM `mobs` WHERE `okra` = '$mestouser[mesto]'"));
$puslap = floor($viso/10);

if($mestouser[page] < $puslap || $mestouser[page] == "" || $mestouser[page] == 0){ $mestouser[page] = $mestouser[page] + 1;}
mysql_query ("UPDATE mesto SET  page = '$mestouser[page]' WHERE usr='$log' LIMIT 1"); // пишем данные

//$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1"); // открываем снова
//$mestouser = mysql_fetch_array($req);

}
if ($_GET[mod]==back){ // назад
if ($mestouser[page]>0){$mestouser[page] = $mestouser[page] - 1;}
mysql_query ("UPDATE mesto SET  page = '$mestouser[page]' WHERE usr='$log' LIMIT 1"); // пишем данные


//$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1"); // открываем снова
//$mestouser = mysql_fetch_array($req);
}

//////////////////////////////
log_msg_pk($tlog='battle'); // лог действий пк





if ($mestouser[page]>0){$xl = "x$mestouser[page]";}
echo"<div class=msg><div align='center'>Окрестность <b>$world[name]</b> $xl</div>";
echo"<div align='center'><b><a href=\"okrestnosti.php?&amp;r=$random\">Ждать</a></b></div></div>";


/*
//---------------	 Лог боя победа		----------------------------


$req=mysql_query("SELECT * FROM log_boy_win WHERE usr = '$log' LIMIT 1");
$avto = mysql_num_rows($req);
if($avto>=1){
While($ilok = mysql_fetch_assoc($req))
{
echo "<hr/><small>";
echo'<div class="msg">';
echo "$ilok[text]";
echo'</small></div>';
}
}
mysql_query("DELETE FROM log_boy_win WHERE usr = '$log'");


//---------------------------------------------------------------------------------
*/

//////////////////////////////////////////
if ($mestouser[page] == "" || $mestouser[page] < 0 || $mestouser[page] == "0") {$mestouser[page] = 0;}
$num = $mestouser[page] * 10;

$req = mysql_query("SELECT * FROM `mobs` WHERE `okra` = '$mestouser[mesto]' ORDER BY lvl,name Limit $num,10");
$avto=mysql_num_rows($req);
if($avto>=1){
echo'<hr/><br/><div class=silka>';

While($mob = mysql_fetch_array($req))
{
if($mob['tip']=='boss'){
$color='#ff4040';
$color2='#cc9966';
}else{
$color='#f4d06e';
$color2='#cc9966';
}
if($mob[status]==on)
{
//ok$mob[id]
//ok.php?id=$mob[id]<img src=\"pic/mob.png\" alt=\"pic\"/>
echo '<table style="width:100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:inherit;width:100%;">';
echo " <a href=\"ok$mob[id]\"><font color='$color'><img src=\"pic/mob.png\" alt=\"pic\"/><font color='$color'>$mob[name] <font color=#ffffcc> [$mob[lvl]] (".number_format($mob[hp], 0, ',', "`")."<b>/</b>".number_format($mob[hpall], 0, ',', "`")." <font color=#ffffcc>hp)</font></font></a>";
echo ' </td> <td style="vertical-align:inherit;width:24px;font-size:22px;">';
echo " <a href=\"okrestnosti.php?mod=info&amp;id=$mob[id]\"><small>[i]</a></td></tr></table>";
}
else if($mob[status]==battle){


$r = mysql_query("SELECT * FROM `tmp` WHERE `city` = '$udata[city]' AND `usr`='$mob[oponent]' LIMIT 1");
$m = mysql_fetch_array($r);
$d = time();
if(($d>=($m['ltime']+600) and $mob[hp]<=0) or $d>=($m['ltime']+600)){
mysql_query("UPDATE `mobs` SET `status`='on',`hp`='$mob[hpall]', `oponent`='not' WHERE `id` = '$mob[id]'");
$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$mob[oponent]' LIMIT 1");
$mu = mysql_fetch_array($req);

$nloses=$mu[loses]+1;
mysql_query("UPDATE users SET loses = '$nloses',hp='0' WHERE usr = '$mob[oponent]'");//пишем данные в плэера
mysql_query("DELETE FROM `tmp` WHERE usr='$mob[oponent]'");
mysql_query("DELETE FROM `log` WHERE usr='$mob[oponent]'");

echo '<table style="width:100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:inherit;width:100%;">';
echo " <a href=\"ok$mob[id]\"><font color='$color'><img src=\"pic/mob.png\" alt=\"pic\"/>$mob[name] (".number_format($mob[hp], 0, ',', "`")." <font color=red>HP</font>/$mob[lvl] lvl)</font></a>";
echo ' </td> <td style="vertical-align:inherit;width:24px;font-size:22px;">';
echo " <a href=\"okrestnosti.php?mod=info&amp;id=$mob[id]\">[i]</a></td></tr></table>";
}
}
elseif($mob[status]==off){
$d = time();
if($d>=($mob[oponent]+$mob[time])){

mysql_query("UPDATE `mobs` SET `status`='on',`hp`='$mob[hpall]', `oponent`='not' WHERE `id` = '$mob[id]'");

echo '<table style="width:100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:inherit;width:100%;">';
echo " <a href=\"ok$mob[id]\"><font color='$color'><img src=\"pic/mob.png\" alt=\"pic\"/><font color='$color'>$mob[name] <font color=#ffffcc> [$mob[lvl]] (".number_format($mob[hp], 0, ',', "`")."<b>/</b>".number_format($mob[hpall], 0, ',', "`")." <font color=#ffffcc>hp)</font></font></a>";
echo ' </td> <td style="vertical-align:inherit;width:24px;font-size:22px;">';
echo " <a href=\"okrestnosti.php?mod=info&amp;id=$mob[id]\"><small>[i]</a></td></tr></table>";


}else{
echo '<table style="width:100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:inherit;width:100%;">';
echo"<font color='$color2'>$mob[name] (убит/$mob[lvl] lvl)</font>";
echo ' </td> <td style="vertical-align:inherit;width:24px;font-size:22px;">';
echo " <a href=\"okrestnosti.php?mod=info&amp;id=$mob[id]\">[i]</a></td></tr></table>";
}
}
}
echo "</div>";
}
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


$reqvip = mysql_query("SELECT * FROM `anti_pk` WHERE `usr` = '$who[usr]' LIMIT 1");
$avto=($reqvip);

$reqvip2 = mysql_query("SELECT * FROM `anti_pk` WHERE `usr` = '$udata[usr]' LIMIT 1");
$avto2=($reqvip2);


if ($udata[lvl]>=100 and $avto !==1 and $cit[lvl]>=100 and $avto2 !==1){

$pk = " <a href=\"ok.php?k=pk&amp;id=$cit[id]\"><font color=$color>[PK]</a></font></font>";

}else {$pk = "";}





if($mestouser[mesto]==$mesto[mesto] && $cit[city]==$udata[city] && $mesto[page]==$mestouser[page]  && $mesto[place]==$mestouser[place]){

if($cit[storona]==$udata[storona]){$color=="green";} else{($color="red");

}
idlog($idlog=$who[usr]); 
echo "$pk |";

$odin=true;
}
}
if($odin==false){
//echo' &#160 <small>Никого нет рядом!</small>';
}else{
echo '<hr/>';}
echo "</small>";
/////////////////
//echo"<hr/><b>Направление:</b><br/>";



$viso = mysql_num_rows(mysql_query("SELECT * FROM `mobs` WHERE `okra` = '$mestouser[mesto]'"));
$puslap = floor($viso/10);



echo "<div class=inoy>";
if($mestouser[page] < $puslap || $mestouser[page] == "" || $mestouser[page] == 0){
echo"<a href=\"okrestnosti.php?mod=go\"> Далее </a>";}
if ($mestouser[page]>0) {echo"<a href=\"okrestnosti.php?mod=back\"> Вернуться </a>";}
echo "</div>";

break;











case 'info':
$reqm = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
$mm = mysql_fetch_array($reqm);
if(empty($mm[page])){$mm[page]=7;}
$id=$_GET[id];
$req = mysql_query("SELECT * FROM `mobs` WHERE `id`='$id' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такого монстра не существует.".mysql_num_rows($req);include($path.'inc/down.php');exit;}
$mob = mysql_fetch_array($req);

if ($mob[name]=="0" or $mob[name]=='') {echo "";}else {echo"<br/><b>$mob[name]</b><br/><br/>";}

if ($mob[lvl]=="" or $mob[lvl]=='') {echo "";}else {echo"Уровень: $mob[lvl]<br/>";}
if ($mob[hp]=="" or $mob[hp]=='0') {echo "";}else {echo"Жизни: ".number_format($mob[hp], 0, ',', "`")."<hr/>";}
if ($mob[patt]=="") {echo "";}else {echo"P.Att: ".number_format($mob[patt], 0, ',', "`")." <br/>";}
if ($mob[matt]=="") {echo "";}else {echo"M.Att: ".number_format($mob[matt], 0, ',', "`")." <br/>";}

if ($mob[pdef]=="") {echo "";}else {echo"P.Def: ".number_format($mob[pdef], 0, ',', "`")." <br/>";}
if ($mob[mdef]=="") {echo "";}else {echo"M.Def: ".number_format($mob[mdef], 0, ',', "`")." <hr/>";}




if ($mob[sm]==0){$mob[sm]=100;}
if ($mob[exp]=="0" or $mob[exp]=='') {echo "";}else {echo"EXP: +".number_format($mob[exp], 0, ',', "`")."<br/>";}
if ($mob[money]=="0" or $mob[money]=='') {echo "";}else {echo"Адена: +".number_format($mob[money], 0, ',', "`")." <small>  ($mob[msh]%)</small><br/><br/>";}


echo "<b>Дроп:</b><br/>";

if ($mob[drop]=="") {echo "Не выпадает<br/><br/>";}else{
$res=explode("/",$mob[drop]);
$sha=explode(".",$mob[drop]);
$kres=count($res);
$i=0;
$b=1;
while($b<=$kres){
$r=explode("/",$res[$i]);
$lat_name=$r[0];
$kol=$r[1];

$req = mysql_query("SELECT * FROM `mdrop` WHERE `id`='$lat_name' LIMIT 1");
$reso = mysql_fetch_array($req);
echo"$reso[name] <br/>";
$i++;
$b++;
}
echo "- - - - -<br/>Шанс дропа: $mob[shans] %<br/><br/>";
}

echo"<a href=\"ok$mob[id]\">Атаковать</a>";
echo"<hr/><a href=\"okrestnosti.php?\">Назад</a>";
break;

break;
case 'portal':
echo'<div class="event">';
$req = mysql_query("SELECT * FROM world WHERE `city` = '$udata[city]' and `y` = '$mestouser[y]' and `x`='$mestouser[x]' and `portal`='1' LIMIT 1");
$do=mysql_num_rows($req);
if($do==1){
if($_GET[act]==city){

// открытие и запись координат города 
$cit = mysql_query("SELECT * FROM citys WHERE `city` = '$udata[city]' LIMIT 1");
$city=mysql_fetch_array($cit);
//----------------------

mysql_query("UPDATE `mesto` SET `place` = 'gorod',`city` = '0',`x` = '$city[x]',`y` = '$city[y]' WHERE `usr` = '$log'");     
echo"Вы прибыли в город.<br/>";
echo"<a href=\"gorod.php?\">Далее</a>";
echo'</div>';
include($path.'inc/down.php');exit;
}elseif($_GET[act]==loc){
$req = mysql_query("SELECT * FROM world WHERE `city` = '$udata[city]' and `id` = '$_GET[id]' and `portal`='1' LIMIT 1");
$do=mysql_num_rows($req);
if($do==1){
$citys = mysql_fetch_array($req);

mysql_query("UPDATE `mesto` SET `x` = '$citys[x]',`y` = '$citys[y]' WHERE `usr` = '$log'");   
  
echo"Вы прибыли в локацию $citys[name].<br/>";
echo"<a href=\"okrestnosti.php?\">Далее</a>";
echo'</div>';
include($path.'inc/down.php');exit;
}else{
echo"В этой локации нет портала!<br/>";
}
}
echo"</div></div><div class=fon><div class=inoy>";
echo "<p><font color=grey> » <b> &#160 Портал &#160 </b> « </font></p><hr/>";
echo"<a href=\"okrestnosti.php?mod=portal&amp;act=city\">В город</a>";

echo"<b><p> &#160 ОкРеСнОстИ</b></p>";
$req = mysql_query("SELECT * FROM world WHERE `city` = '$udata[city]' and `portal`='1'");
//////////////////////////////
While($mob = mysql_fetch_array($req)){

echo"<a href=\"okrestnosti.php?mod=portal&amp;act=loc&amp;id=$mob[id]\">$mob[name]</a>";

}
echo "</center>";
echo"<br/><div class=silka><a href=\"okrestnosti.php?\">Назад</a></div>";
}else{
echo"В этой локации нет портала!<br/>";
}
echo'</div></div>';
break;
}
include($path.'inc/down.php');
?>
