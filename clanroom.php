<?php
define('PROTECTOR', 1);

$headmod = 'clanroom';//фикс. места

$textl='Комната клана';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

if(empty($udata['clan'])){echo"Вы не в клане!"; include($path.'inc/down.php'); exit;}

$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$avto=mysql_num_rows($req);
if($avto==1){
$clan = mysql_fetch_array($req);
}else{
echo"Отсутствие такого клана! Обратитесь к администрации игры за помощью!";include($path.'inc/down.php'); exit;
}

switch($_GET['go']) {

default:
$ref=rand(1000,9999);

// загружаем эмблему клана
if(!empty($clan['emblema'])){
$embl = "<img src=\"pic/clan/$clan[emblema]\" alt=\"clan\"/> ";}
echo"<center><img src=\"pic/roz/clan.jpg\" alt=\"\"></center>";
echo "<center><font color=#f4d06e><i>Клан:  <b>$clan[name] </b></i></font></center><hr>";

if(empty($clan['logo'])){
$logo = "<img src='pic/pusto.png' align='left' width='50' height='50' alt='' style='margin-right:10px;border:1px solid #383838'>";
}else{
$logo = "<img src='pic/clan_logo/$clan[logo]' align='left' width='50' height='50' alt='' style='margin-right:10px;border:1px solid #383838'>";
}


// уровень и опыт клана
include($path.'inc/clan_lvl.php');
include($path.'inc/clan_lvl_2.php');
include($path.'inc/clan_opit.php');


echo "$logo";
echo "$embl <font color=#f4d06e><b>$clan[name]</b></font><br/> 
<font color=#f4d06e>Опыт:</b><font color=#ffffcc> ($expcl%)<font color=#af9878> ".number_format($clan['exp'], 0, ',', "`")."<small> / </small>".number_format($clan_new_exp, 0, ',', "`")." </font><br>
<font color=#f4d06e>Уровень: <font color=#ffffcc>$clan[lvl] $pic</font><br/><br/>";
// полоса jgsnf
echo '<div style="border:1px solid #7F3300;background:1px #282828;height:3px;width:100%;"><div style="background-color:#FF6A00;height:3px;width:'.$expcl.'%;"></div><span style="position:relative;top:-99%;left:1%;"> </font></span></div></small>';


echo "<hr>";
// админка клана
if($udata['clan']==$log or $clan['zam']==$log)
{
echo"<div class=inoy><a href=\"home.php?go=desk\">Управление кланом</a>
<a href=\"clanroom.php?go=desk\">Вывесить объявление</a>
<a href=\"clanroom.php?go=site\">Вывесить сайт клана</a>";
if($clan['set_col']==0){
echo"<a href=\"clanroom.php?go=set_col\"><font color=#ff4040>Запретить</font> взять CoL</a>";
}else{
echo"<a href=\"clanroom.php?go=set_col\"><font color=#6fcd72>Разрешить </font>взять CoL</a>";
}
if($clan['set_sklad']==0){
echo"<a href=\"clanroom.php?go=set_sklad\"><font color=#ff4040>Закрыть склад</font></a>";
}else{
echo"<a href=\"clanroom.php?go=set_sklad\"><font color=#6fcd72>Открыть склад</font></a>";
}
echo "</div><hr/>";
}
//----конец админки-------

if(!empty($clan['desk'])){
echo '<center><div class=dot>';
echo"<p><font color=#007F46>$clan[desk]</font></p>";}
echo '</center>';

$skilall=$clan['dd']+$clan['zash']+$clan['krit']+$clan['ukrit']+$clan['lovk']+$clan['point'];
$skilyes=$clan['dd']+$clan['zash']+$clan['krit']+$clan['ukrit']+$clan['lovk'];

if(!empty($clan['site'])){echo"</div><div class=silka><a href=\"$clan[site]\"><font color=grey>$clan[site]</font><font color=silver> - сайт клана</font></a></div><div class=inoy>";}

echo"<center><font color=#f4d06e>Капитал клана:  <font color=#ffffcc>".number_format($clan['col'], 0, ',', " ")." CoL</b></font></center>";
//echo"<center><font color=#f4d06e>Капитал клана:  <font color=#ffffcc>".number_format($clan[aden], 0, ',', " ")." Аден</b></font></center><hr/>";
/*echo"<center>Репутация:  <font color=#FF9B00>".number_format($clan[rep], 0, ',', " ")." Очков</b></font></center><hr/>";*/
echo "<div class=inoy><a href=\"clanroom.php?go=skil\">Умения клана <font color=#ffffcc><b>($skilall | $skilyes | $clan[point])</b></font></a>
<a href=\"clanroom.php?go=bankadd_col\">Положить CoL</a>
<a href=\"clanroom.php?go=my_col\">Взять CoL</a>
<a href=\"clanroom.php?go=logs\">Лог операций с капиталом и складом</a>
<a href=\"clanroom.php?go=sklad\">Cклад клана</a>
<a href=\"clanroom.php?go=who\">Список клана</a></div></div>";

if (empty($_GET['start'])) $start = 0;
else $start = (int) $_GET['start'];

$req = mysql_query("SELECT * FROM `clanchat` where `clan`='$udata[clan]' ORDER BY id DESC LIMIT $start,5");
$avto=mysql_num_rows($req);

if($avto==0){echo"Чат пуст";}else{echo "<hr/>";
while($chat = mysql_fetch_array($req))
{
echo"<a href=\"search.php?go=go&amp;nick=$chat[usr]\">$chat[usr] <font color=#af9878>| </a> <font color=#af9878> $chat[time] <br/><font color=#ffffcc> $chat[msg]</font><hr/>";
}
}

echo "<form action=\"clanroom.php?go=add\" method=\"post\"><br/>
<input name=\"msg\" maxlength=\"200\"/><br/>";
echo '<div align="left"><input class="button_medium3" class="button" type="submit" value=""/></form>';

echo '<br/><div style="border-bottom		:#666 solid  1px; padding		: 1% 1% 1% 1%; width:55%;">';
if ($start > 0) {echo '<a href="clanroom.php?start='.($start - 5).'">Назад</a> |';}else {echo "Назад |";}
if ($avto >= $start) {echo ' <a href="clanroom.php?start='.($start + 5).'">Далее</a>';}else{echo "Далее";}
echo "</div>";

echo"<br/><div class=silka><a href=\"clanroom.php?ref=$ref\">Обновить</a></div>"; 


break;
/////////////////////////////////////////////////////////////////////////////////////////////
/// sklad////////////////////////////////////////////////////////////////////////////////////
case 'sklad':
if($clan['set_sklad']==1){echo"Доступ закрыт!";}else{
$req = mysql_query("SELECT * FROM `sklad_clan` WHERE `clan` = '$clan[name]'");
////////////////////////////
$avto=mysql_num_rows($req);
if(empty($_GET['id'])){
if($avto>=1){
While($mag = mysql_fetch_array($req))
{$u = explode("*",$mag['name']);

echo"<div class=inoy><a href=\"?go=sklad&amp;id=$mag[id]\"><img src=\"shmot/$u[0].png\" alt=\"pic\"/>  $mag[name]</a></div>";
}
}else{
echo"<b><div class=dot>Нет вещей на складе</b></div><br/>";

}
}else{
$req = mysql_query("SELECT * FROM `sklad_clan` WHERE `clan` = '$clan[name]' and `id`='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto==1){
$item = mysql_fetch_array($req);

mysql_query("INSERT INTO
        `item` SET
        `usr` = '$log',
        `tip` = '$item[tip]',
        `ruka` = '$item[ruka]',
        `name` = '$item[name]',
        `cena` = '$item[cena]',
        `patt` = '$item[patt]',
        `matt` = '$item[matt]',
        `pdef` = '$item[pdef]',
        `mdef` = '$item[mdef]',
        `soul` = '$item[soul]',
        `spirit` = '$item[spirit]',
        `time` = '$item[time]',
        `nlvl` = '$item[nlvl]',
        `image` = 'not'");
        
        mysql_query("INSERT INTO clanlog SET clan='$udata[clan]',msg='$log забрал(а) $item[name] со склада.'");
        
        mysql_query("DELETE FROM `sklad_clan` WHERE clan='$clan[name]' and `id`='$_GET[id]'");
        
echo"Вещь $item[name] забрана со склада!<br/>";

}else{
echo"<b>Нет такой вещи</b><br/>";
}
}
echo"<br/><div class=silka><a href=\"?\">Назад</a></div>";
echo"<div class=silka><a href=\"?go=send\">Положить вещи</a></div>";

}
break;
/////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////ложим шмот////////////
/////////////////////////////////////////////////////////////////////////////////////////////
case 'send':
if($clan['set_sklad']==1){echo"Доступ закрыт!";}else{
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image`='not'");
////////////////////////////
$avto=mysql_num_rows($req);
if(empty($_GET['id'])){
if($avto>=1){
While($mag = mysql_fetch_array($req))
{$u = explode("*",$mag['name']);
echo "<div class=inoy>";
echo"<a href=\"?go=send&amp;id=$mag[id]\"><img src=\"shmot/$u[0].png\" alt=\"pic\"/> $mag[name]</a>";
echo "</div>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
}else{
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image`='not' and `id`='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto==1){
$item = mysql_fetch_array($req);

mysql_query("INSERT INTO
        `sklad_clan` SET
        `tip` = '$item[tip]',
        `ruka` = '$item[ruka]',
        `name` = '$item[name]',
        `cena` = '$item[cena]',
        `patt` = '$item[patt]',
        `matt` = '$item[matt]',
        `pdef` = '$item[pdef]',
        `mdef` = '$item[mdef]',
        `soul` = '$item[soul]',
        `spirit` = '$item[spirit]',
        `time` = '$item[time]',
        `nlvl` = '$item[nlvl]',
		`clan` = '$clan[name]'");
        
        mysql_query("INSERT INTO clanlog SET clan='$udata[clan]',msg='$log положил(а) $item[name] на склад.'");
        
        mysql_query("DELETE FROM `item` WHERE usr='$log' and `image`='not' and `id`='$_GET[id]'");
        
echo"Вещь $item[name] положена на склад клана!<br/>";

}else{
echo"<b>Нет такой вещи</b><br/>";
}
}
echo"<br/><div class=silka><a href=\"?\">Назад</a></div>";
}
break;
/// скилы
case 'skil':

echo "<div class=menu>";
$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);

echo "<b>Умения клана:</b><br/><br/>";
echo "<hr/> <font color=grey>P.Att:  +$clan[patt]%";
echo '<br/><div style="background:3px #999999;height:5px;width:125px;"><div style="background-color:#007F0E;height:5px;width:'.$clan['patt'].'%;"></div></div><hr/>';
echo "M.Att: +$clan[matt]%";
echo '<br/><div style="background:3px #999999;height:5px;width:125px;"><div style="background-color:#007F0E;height:5px;width:'.$clan['matt'].'%;"></div></div><hr/>';
echo "P.Def: +$clan[pdef]%";
echo '<br/><div style="background:3px #999999;height:5px;width:125px;"><div style="background-color:#007F0E;height:5px;width:'.$clan['pdef'].'%;"></div></div><hr/>';
echo "M.Def: +$clan[mdef]%";
echo '<br/><div style="background:3px #999999;height:5px;width:125px;"><div style="background-color:#007F0E;height:5px;width:'.$clan['mdef'].'%;"></div></div><hr/>';

echo "<br/>";
echo "<hr/><font color=lime>* Каждый параметр увеличует в бою параметры героя в процентном соотношении.<hr/>";

echo "</font><br/><a href=\"clanroom.php?\">Назад</a></div>";
break;
///////////////



case 'site':

if($udata['clan']!==$log and $clan['zam']!==$log){ echo"Вы не имеете право размещать сайт!"; include($path.'inc/down.php'); exit;}
if(empty($_POST['site'])){
echo "<form action=\"clanroom.php?log=".$log."&amp;pas=".$pas."&amp;go=site\" method=\"post\">Адрес:<br/>";
echo "<input name=\"site\" maxlength=\"100\" value=\"http://\" title=\"name\"/><br/>";
echo '<input type="submit" value="Ok" /></form>';
}else{
mysql_query("UPDATE `clan` SET `site` = '$_POST[site]' WHERE `lider` = '$udata[clan]'");

echo"Клан сайт установлен!";
echo"<br/><a href=\"clanroom.php?\">Продолжить</a>"; 
}
break;

case 'desk':

if($udata['clan']!==$log and $clan['zam']!==$log){ echo"Вы не имеете право размещать объявления!"; include($path.'inc/down.php'); exit;}
if(empty($_POST['text'])){
echo "<form action=\"clanroom.php?log=".$log."&amp;pas=".$pas."&amp;go=desk\" method=\"post\">Текст обьявления:<br/>";
echo "<input name=\"text\" maxlength=\"200\" title=\"name\"/><br/>";
echo '<input type="submit" value="Ok" /></form>';
}else{
mysql_query("UPDATE `clan` SET `desk` = '$_POST[text]' WHERE `lider` = '$udata[clan]'");

echo"Обьявление размещено!";
echo"<br/><a href=\"clanroom.php?\">Продолжить</a>"; 
}
break;

case 'add':

$msg=$_POST['msg'];

if ($msg == "" || strlen($msg) > 2500 || strlen($msg) < 3) { echo"Сообщение длинное или короткое!<br/> &#187 5 - 2`500 символов.<br/>"; }else{
include_once($path . 'inc/antimat.php');
$msg=stripslashes($msg);
$msg=lin2_chat_filter($msg);
$msg=htmlspecialchars($msg);
$msg=str_replace("\\","",$msg);
$msg=str_replace("/","",$msg);
$msg=str_replace("|","",$msg);
$msg=str_replace("$","",$msg);
$msg=str_replace("&","",$msg);
$msg=str_replace("<","",$msg);
$msg=str_replace(">","",$msg);
$msg=str_replace("#","",$msg);
$log=str_replace("#","",$log);
/////////////////
$times222 = date("H:i:s");
$date222 = date("d.m.Y");
mysql_query("INSERT INTO clanchat SET usr='$log',clan='$udata[clan]',msg='$msg',time='$times222 / $date222'");
include_once($path . 'inc/quests_promo.php');
lin2_qp_progress($log, 'chat_msg', 1);

echo"Сообщение добавлено!<br/>";}
echo"<br/><a href=\"clanroom.php?\">Продолжить</a>"; 
break;


// добавляем колы
case 'bankadd_col':
if(empty($_POST['kol'])){
echo "<form action=\"clanroom.php?log=".$log."&amp;pas=".$pas."&amp;go=bankadd_col\" method=\"post\">Сумма:<br/>";
echo "<input name=\"kol\" maxlength=\"20\" title=\"name\"/><br/>";
echo '<input type="submit" value="Ok" /></form>';
}else{
if((float)$_POST['kol']<=0){echo'Введена слишком маленькая сумма!';include($path.'inc/down.php'); exit;}
if(preg_match('/[^0-9]/',$_POST['kol'])){echo"Вводите число, а не буквы!"; include($path.'inc/down.php'); exit;}
if($_POST['kol']>$udata['almaz']){echo"У вас нет такой суммы!"; include($path.'inc/down.php'); exit;}

mysql_query("INSERT INTO clanlog SET clan='$udata[clan]',msg='$log положил $_POST[kol] CoL.'");

$clan['col']=$clan['col']+$_POST['kol'];
$udata['almaz']=$udata['almaz']-$_POST['kol'];

mysql_query("UPDATE `clan` SET `col` = '$clan[col]' WHERE `lider` = '$udata[clan]'");
mysql_query("UPDATE `users` SET `almaz` = '$udata[almaz]' WHERE `usr` = '$log'");

echo"Вы положили $_POST[kol] CoL!";
echo"<br/><br/><a href=\"clanroom.php?\">Продолжить</a>"; 
}
break;




// берём колы
case 'my_col':
if($clan['set_col']==1 and $udata['clan']!=$log){echo"Доступ закрыт!";}else{
if(empty($_POST['kol'])){
echo "<form action=\"clanroom.php?log=".$log."&amp;pas=".$pas."&amp;go=my_col\" method=\"post\">Сумма:<br/>";
echo "<input name=\"kol\" maxlength=\"20\" title=\"name\"/><br/>";
echo '<input type="submit" value="Ok" /></form>';
}else{
if((float)$_POST['kol']<=0){echo'Введена слишком маленькая сумма!';include($path.'inc/down.php'); exit;}
if(preg_match('/[^0-9]/',$_POST['kol'])){echo"Вводите число, а не буквы!"; include($path.'inc/down.php'); exit;}
if($_POST['kol']>$clan['col']){echo"У клана нет такой суммы!"; include($path.'inc/down.php'); exit;}

mysql_query("INSERT INTO clanlog SET clan='$udata[clan]',msg='$log забрал $_POST[kol] CoL.'");

$clan['col']=$clan['col']-$_POST['kol'];
$udata['almaz']=$udata['almaz']+$_POST['kol'];

mysql_query("UPDATE `clan` SET `col` = '$clan[col]' WHERE `lider` = '$udata[clan]'");
mysql_query("UPDATE `users` SET `almaz` = '$udata[almaz]' WHERE `usr` = '$log'");

echo"Вы забрали $_POST[kol] CoL!";
}}
echo"<br/><a href=\"clanroom.php?\">Продолжить</a>"; 
break;



case 'logs':
echo'<b>Лог операций с деньгами и складом:</b><br/><br>';
if (empty($_GET['start'])) $start = 0;
else $start = (int) $_GET['start'];

$req = mysql_query("SELECT * FROM `clanlog` where `clan`='$udata[clan]' ORDER BY id DESC LIMIT $start,15");
$avto=mysql_num_rows($req);

if($avto==0){echo"<br/>Лог пуст";}else{
while($chat = mysql_fetch_array($req))
{
echo"<font color=#267F00> $chat[msg] </font><hr/>";
}
}

if ($start != 0) {echo '<a href="clanroom.php?start='.($start - 15).'&amp;log='.$log.'&amp;pas='.$pas.'&amp;go=logs">Назад</a> ';}

if ($avto > $start+15) {echo ' <a href="clanroom.php?start='.($start + 15).'&amp;log='.$log.'&amp;pas='.$pas.'&amp;go=logs">Далее</a>';}

echo"<br/><div class=silka><a href=\"clanroom.php?\">Комната клана</a></div>"; 

break;



////////////////////// выход с клана /////////////////////
case 'clan':
if (empty($_GET['cl'])){
$nmo=$udata['lvl']*150000;

echo "Вы уверены что хотите покинуть клан?<br/>
Это будет стоить ".number_format($nmo, 0, ',', "`")." Аден.";
echo "<p><a href=\"clanroom.php?cl=c&amp;go=clan\">Да </a> | ";
echo "<a href=\"pers.php?\">Нет </a></p>";

}else{
if ($log==$udata['clan']){
echo "Вы Глава клана и не можете покинуть его!<br/>";
}else{
$nmoney=$udata['money']-$udata['lvl']*150000;
if($nmoney>0){

mysql_query("UPDATE users SET clan = '',money='$nmoney' WHERE `usr` = '$log'");
echo'Вы покинули свой клан!<br/>';
}else{
echo'Недостаточно Аден!<br/>';
}
}}
echo"<a href=\"pers.php?\">Назад</a>";
break;
///////////////////////////////////////////////////////////


case 'who':

$req = mysql_query("SELECT * FROM `clan` WHERE `lider` = '$udata[clan]'");
////////////////////////////
$cl = mysql_fetch_array($req);
echo"Клан \"<b>$cl[name]</b>\":<br/><br/>";

$laikas = 150;
$dabar = time();
$timeout = $dabar - $laikas;
$asd = mysql_num_rows(mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeout' AND usr='$cl[lider]'"));
if($asd==0 && $cl['dostup']<3){$stat47 = ' <font color=red> Off</font> ';}else{$stat47 = ' <font color=lime> On </font> ';}

echo "<div class=inoy> <a href=\"search.php?nick=$cl[lider]&amp;go=go\"><font color=#ffffff>1. </font><font color=green>$cl[lider]</font> <font color=grey> [Лидер]</font></b> $stat47 </a></div>";
$req = mysql_query("SELECT clan,usr FROM `users` where `clan`='$udata[clan]' and `usr`!='$udata[clan]' ORDER BY lvl DESC");
////////////////////////////
$avto=mysql_num_rows($req);
$i=2;
if($avto>=1){
echo "<div class=inoy>";
While($clan = mysql_fetch_array($req))
{

$laikas = 150;
$dabar = time();
$timeout = $dabar - $laikas;
$asd = mysql_num_rows(mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeout' AND usr='$clan[usr]'"));
if($asd==0 && $cl['dostup']<3){$stat47 = ' <font color=red> Off</font> ';}else{$stat47 = ' <font color=lime> On </font> ';}


echo" <a href=\"search.php?nick=$clan[usr]&amp;go=go\"><font color=#ffffff>$i.</font> $clan[usr] $stat47</a>";
$i++;
}
echo "</div>";
$avto=$avto+1;
echo"<br/><b><p>Всего соклановцев: $avto</p></b><br/>";
}else{
echo"<b><p>Нет соклановцев</p></b><br/>";
}
break;

case 'set_col':
if($udata['clan']!==$log and $clan['zam']!==$log){ echo"Вы не имеете права тут находится!"; include($path.'inc/down.php'); exit;}
if($clan['set_col']==0){
$on=1;
}else{
$on=0;
}
mysql_query("UPDATE `clan` SET `set_col` = '$on' WHERE `lider` = '$udata[clan]'");
echo"Изменения сохранены!<br/>";
echo"<a href=\"clanroom.php?\">Продолжить</a>"; 
break;

case 'set_sklad':
if($udata['clan']!==$log and $clan['zam']!==$log){
echo"Вы не имеете права тут находится!"; include($path.'inc/down.php'); 
exit;
}
if($clan['set_sklad']==0){
$on=1;
}else{
$on=0;
}
mysql_query("UPDATE `clan` SET `set_sklad` = '$on' WHERE `lider` = '$udata[clan]'");
echo"Изменения сохранены!<br/>";
echo"<a href=\"clanroom.php?\">Продолжить</a>"; 
break;

}

include($path.'inc/down.php');
?>