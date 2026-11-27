<?
define('PROTECTOR', 1);

$headmod = 'pitomec';//фикс. места

$textl='Питомник';
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

switch($_GET[mod]){

default:
echo"<center><img src=\"pic/roz/pitkaaa.jpg\" alt=\"\"></center>";
echo "<font color=#f4d06e> Питомник: </font><br/><br/>";
$req = mysql_query("SELECT `id` FROM `pit` WHERE `usr` != '$log' and `lord`='$log' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==1){
echo"<div class=inoy><a href=\"pitomec.php?mod=back\">Забрать питомцев</a></div>";
}
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log'");
$avto=mysql_num_rows($req);
if($avto<3){
echo"<div class=inoy><a href=\"pitomec.php?mod=new\">Завести питомца</a></div>";
}
if($avto>=1){

$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto>0){

$pit = mysql_fetch_array($req);

echo"<br/><div class=inoy><a href=\"pitomec.php?mod=korm\">Покормить <b>$pit[name]</b></a>";
echo"<a href=\"pitomec.php?mod=train\">Тренировать <b>$pit[name]</b>  &nbsp;&nbsp;<font color=#ffffff>($pit[skill])</font></a>";
echo"<a href=\"/pit.php?mod=mypit\">Информация <b>$pit[name]</b> </a>";
echo"<a href=\"pitomec.php?mod=health\">Лечить <b>$pit[name]</b>  &nbsp;&nbsp;<font color=#ffffff>($pit[hp]/$pit[hpall])</font></a></div>";
}
echo"<br/><div class=inoy><a href=\"pitomec.php?mod=kletka\">Клетка</a></div>";
}
break;
case 'kletka':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"<p>У вас нет питомцев!</p>";
include($path.'inc/down.php');exit;
}
if($_GET[act]=='on'){
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==1){
echo"<p>Сначала оставте активного питомца!</p>";
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='off' and id='$_GET[id]'");
$avto=mysql_num_rows($req);

if($avto==0){
echo"<p>У вас нет такого питомца!</p>";
include($path.'inc/down.php');exit;
}

$pit = mysql_fetch_array($req);

$pitsec=time()+$pit[sec];
mysql_query("UPDATE `pit` SET `status` = 'on',`lasteda` = '$pitsec' WHERE `usr` = '$log' and `status`='off' and id='$_GET[id]'");
echo"<p>Вы забрали $pit[name] с питомника!</p>";

}elseif($_GET[act]=='off'){
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on' and id='$_GET[id]'");
$avto=mysql_num_rows($req);

if($avto==0){
echo"<p>У вас нет такого питомца!</p>";
include($path.'inc/down.php');exit;
}

$pit = mysql_fetch_array($req);

$pitsec=$pit[lasteda]-time();
mysql_query("UPDATE `pit` SET `status` = 'off',`sec` = '$pitsec' WHERE `usr` = '$log' and `status`='on' and id='$_GET[id]'");
echo"<p>Вы оставили $pit[name] в питомнике!</p>";
}else{
While($pit = mysql_fetch_array($req))
{
echo"<div class=msg><p><b>$pit[name]</b>";

if($pit[status]=="off"){echo"&nbsp;<span style=color:grey;font-size:11px;>[в питомнике] </span></p>";
 echo '<div class=silka><div class=dot><table style="width:100%" cellspacing="0" cellpadding="0"><tr><td style="vertical-align:top;width:19%;text-align			:center;">
<a class="top_menu_link" href="/pitomec.php?mod=kletka&amp;act=on&amp;id='.$pit[id].'\">Забрать</a></td><td style="vertical-align:top;width:19%;text-align			:center;">
<a class="top_menu_link" href="/pitomec.php?mod=send&amp;id='.$pit[id].'">Передать</a></td></tr></table>
</div></div></div>';
//<a href=\"pitomec.php?mod=kletka&amp;act=on&amp;id=$pit[id]\">Забрать</a>|<a href=\"pitomec.php?mod=send&amp;id=$pit[id]\">Передать</a></div>";
 }
if($pit[status]=="on"){echo"&nbsp;<span style=color:orange;font-size:11px;>[с вами] </span></p>";
echo "<div class=dot><div class=silka> <a href=\"pitomec.php?mod=kletka&amp;act=off&amp;id=$pit[id]\">Оставить</a></div></div></div>";}

if($pit[status]=="die"){
if($pit[dies]<=0){$pit[dies]=1;}
if($pit[lvl]<=0){$pit[lvl]=1;}
$cena=round($pit[dies]*$pit[hpall]);
echo"&nbsp;<span style=color:red;font-size:11px;> [Мёртв] </span></p>";
echo  "<div class=dot><div class=silka><a href=\"pitomec.php?mod=revival&amp;id=$pit[id]\">Воскресить <b>$pit[name]</b> &nbsp;<u>[$cena аден]</u></a></div></div></div>";}
}
}
echo"<br/><div class=inoy><a href=\"pitomec.php?\">Назад</a></div>";
break;
case'new':

echo "<p><font color=grey>Покупка питомца:</font></p>";

if(empty($_GET[id])){
$b = mysql_query("SELECT * FROM pitomec WHERE id != ''");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto>=1){
$i=1;
While($best = mysql_fetch_array($b))
{
echo"$i. <a href=\"pitomec.php?mod=info&amp;id=$best[id]\">$best[name]</a> - ".number_format($best[cena], 0, ',', " ")." Аден
 [<a href=\"pitomec.php?mod=new&amp;id=$best[id]\">купить</a>]<br/>";
$i++;
}
}else{
echo"Нет питомцев на продаже<br/>";
}
}else{
if(empty($_POST[name]) or empty($_POST[pol])){
echo '<form action="pitomec.php?mod=new&amp;id='.$_GET[id].'" method="post">';
echo"Имя питомца:<br/>
<input class='input' type=\"text\" size=\"10\" name=\"name\" maxlength=\"20\"/><br/>";
echo "Пол:(<small>Ни на что не влияет!</small>)<br/>
<select name=\"pol\"><option value=\"m\">Мужской</option>
<option value=\"w\">Женский</option></select><br/>";
echo '<input class="button" type="submit" value="Приручить" /></form>';
}else{
$b = mysql_query("SELECT * FROM pitomec WHERE id != '' and id='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto==0){
echo"Такого питомца не существует!";
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log'");
$avto=mysql_num_rows($req);
if($avto==3){
echo"У вас уже есть 3 питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($b);
if($udata[money]<$pit[cena]){
echo"Нехватает монет!";
include($path.'inc/down.php');exit;
}
if($udata[lvl]<$pit[lvl]){
echo"У вас маленький уровень для этого питомца!";
include($path.'inc/down.php');exit;
}
if($pit[klas]!='not' and $pit[klas]!=$udata[klas]){
echo"Этот питомец не для вашего класса!";
include($path.'inc/down.php');exit;
}

$time=time()+3600;

mysql_query("INSERT INTO
        `pit` SET
        `usr` = '$log',
        `name` = '$_POST[name]',
        `lord` = '$log',
        `status` = 'off',
        `rasa` = '$pit[rasa]',
        `pol` = '$_POST[pol]',
        `lasteda` = '$time',
        `sec` = '3600',
        `lvl` = '0',
        `skill` = '2',
        `hp` = '$pit[hp]',
        `hpall` = '$pit[hpall]',
        `exp` = '0',
        `sila` = '$pit[sila]',
        `prot` = '$pit[prot]'");

$udata[money]=$udata[money]-$pit[cena];

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

echo'Вы приручили питомца и дали ему кличку '.$_POST['name'].'<br/>';
}
}
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
case 'revival':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='die' and id='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"У вас нет такого питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($req);

if($pit[dies]<=0){$pit[dies]=1;}
if($pit[lvl]<=0){$pit[lvl]=1;}
$cena=round($pit[dies]*$pit[hpall]);

if($udata[money]<$cena){
echo"Нехватает монет!";
include($path.'inc/down.php');exit;
}
$udata[money]=$udata[money]-$cena;

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");
$time=time()+3600;

mysql_query("UPDATE `pit` SET `hp` = '$pit[hpall]',`lasteda` = '$time',`sec` = '3600',`status`='off' WHERE `usr` = '$log' and id='$_GET[id]'");

echo"Вы воскресили $pit[name]! Это обошлось вам в $cena монет!Еды хватит на час!<br/>";
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
case 'health':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"У вас нет такого питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($req);


if($pit[hp]>=$pit[hpall]){
echo"Ваш питомец здоров!";
include($path.'inc/down.php');exit;
}
if($pit[lvl]<=0){$pit[lvl]=1;}

$cena=round(2*$pit[lvl]);
if(empty($_POST[kol])){
echo '<form action="pitomec.php?mod=health" method="post">';
echo"Цена за ед. здоровья $cena монет<br/>";
echo"Восстановить:<br/>
<input class='input' type=\"text\" size=\"10\" name=\"kol\" maxlength=\"10\"/><br/>";
echo '<input class="button" type="submit" value="Лечить" /></form>';
}else{
if($_POST[kol]<=0){
$_POST[kol]=1;
}
if(($pit[hp]+$_POST[kol])>$pit[hpall]){
$_POST[kol]=$_POST[kol]-(($pit[hp]+$_POST[kol])-$pit[hpall]);
}
$cena=round($_POST[kol]*$cena);

if($udata[money]<$cena){
echo"Нехватает монет!";
include($path.'inc/down.php');exit;
}

$udata[money]=$udata[money]-$cena;
$pit[hp]=$pit[hp]+$_POST[kol];

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");
mysql_query("UPDATE `pit` SET `hp` = '$pit[hp]' WHERE `usr` = '$log' and `status`='on'");

echo"Вы восстановили $_POST[kol] ед. здоровья у $pit[name]!<br/>
- $cena монет!<br/>";
}
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
case 'korm':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"У вас нет такого питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($req);

$time=time();
if((($pit[lasteda]-$time)/3600)>12){
echo"Ваш питомец сыт!";
include($path.'inc/down.php');exit;
}
if($pit[lvl]<=0){$pit[lvl]=1;}

$cena=round(10*$pit[lvl]);

if(empty($_POST[kol])){
echo '<form action="pitomec.php?mod=korm" method="post">';
echo"Цена за час $cena монет<br/>";
echo"Количество часов:(<small>мин. 1, макс. 12</small>)<br/>
<input class='input' type=\"text\" size=\"2\" name=\"kol\" maxlength=\"2\"/><br/>";
echo '<input class="button" type="submit" value="Кормить" /></form>';
}else{
if($_POST[kol]<=0 or $_POST[kol]>12){
echo"Неправильное число!";
include($path.'inc/down.php');exit;
}
if((($pit[lasteda]+(3600*$_POST[kol]))-time())/3600>12){
echo"Нельзя перекармливать питомца!";
include($path.'inc/down.php');exit;
}
$cena=round($_POST[kol]*$cena);

if($udata[money]<$cena){
echo"Нехватает монет!";
include($path.'inc/down.php');exit;
}

$udata[money]=$udata[money]-$cena;

$time=$pit[lasteda]+(3600*$_POST[kol]);
$d=3600*$_POST[kol];
$pit[sec]=$pit[sec]+$d;
mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");
mysql_query("UPDATE `pit` SET `lasteda` = '$time',sec='$pit[sec]' WHERE `usr` = '$log' and `status`='on'");

echo"Вы покормили $pit[name] на $_POST[kol] час(ов)!<br/>
- $cena монет!<br/>";
}
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
case 'train':
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"У вас нет питомца!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($req);

if($pit[lvl]<=0){$pit[lvl]=1;}

$cena=round($pit[lvl]*50);

if(empty($_GET[what])){
echo"<b>Свободных очков: $pit[skill]</b><br/>";
if($pit[skill]>0){echo"Цена одного очка: <b>$cena</b><br/>";}
echo"Жизни: $pit[hp]/$pit[hpall]";
if($pit[skill]>0){echo' [<a href="pitomec.php?mod=train&amp;what=hp">+50</a>]';}echo'<br/>';
echo"Сила: $pit[sila]";
if($pit[skill]>0){echo' [<a href="pitomec.php?mod=train&amp;what=sila">+10</a>]';}echo'<br/>';
echo"Физ. Защита: $pit[prot]";
if($pit[skill]>0){echo' [<a href="pitomec.php?mod=train&amp;what=prot">+5</a>]';}echo'<br/>';
}else{
if($udata[money]<$cena){
echo"Нехватает монет!";
include($path.'inc/down.php');exit;
}
$udata[money]=$udata[money]-$cena;

switch($_GET[what]){
default:
echo'Ошибка! Доступ закрыт!<br/>';
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
case 'sila':
if($pit[skill]>0){
$nskill=$pit[skill]-1;
$nparm=$pit[sila]+10;
mysql_query("UPDATE pit SET skill = '$nskill',sila='$nparm' WHERE `usr` = '$log' and `status`='on'");

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

echo'Параметр повышен успешно!<br/>';
}else{
echo'Нет свободных очков опыта!<br/>';
}
break;

case 'lovk':
if($pit[skill]>0){
$nskill=$pit[skill]-1;
$nparm=$pit[lovk]+5;
mysql_query("UPDATE pit SET skill = '$nskill',lovk='$nparm' WHERE `usr` = '$log' and `status`='on'");

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

echo'Параметр повышен успешно!<br/>';
}else{
echo'Нет свободных очков опыта!<br/>';
}
break;

case 'prot':
if($pit[skill]>0){
$nskill=$pit[skill]-1;
$nparm=$pit[prot]+5;
mysql_query("UPDATE pit SET skill = '$nskill',prot='$nparm' WHERE `usr` = '$log' and `status`='on'");

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

echo'Параметр повышен успешно!<br/>';
}else{
echo'Нет свободных очков опыта!<br/>';
}
break;
case 'krit':
if($pit[skill]>0){
$nskill=$pit[skill]-1;
$nparm=$pit[krit]+1;
mysql_query("UPDATE pit SET skill = '$nskill',krit='$nparm' WHERE `usr` = '$log' and `status`='on'");

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

echo'Параметр повышен успешно!<br/>';
}else{
echo'Нет свободных очков опыта!<br/>';
}
break;
case 'ukrit':
if($pit[skill]>0){
$nskill=$pit[skill]-1;
$nparm=$pit[ukrit]+1;
mysql_query("UPDATE pit SET skill = '$nskill',ukrit='$nparm' WHERE `usr` = '$log' and `status`='on'");

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

echo'Параметр повышен успешно!<br/>';
}else{
echo'Нет свободных очков опыта!<br/>';
}
break;
case 'antikrit':
if($pit[skill]>0){
$nskill=$pit[skill]-1;
$nparm=$pit[antikrit]+1;
mysql_query("UPDATE pit SET skill = '$nskill',antikrit='$nparm' WHERE `usr` = '$log' and `status`='on'");

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

echo'Параметр повышен успешно!<br/>';
}else{
echo'Нет свободных очков опыта!<br/>';
}
break;
case 'hp':
if($pit[skill]>0){
$nskill=$pit[skill]-1;
$nparm=$pit[hpall]+50;
mysql_query("UPDATE pit SET skill = '$nskill',hpall='$nparm' WHERE `usr` = '$log' and `status`='on'");

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

echo'Параметр повышен успешно!<br/>';
}else{
echo'Нет свободных очков опыта!<br/>';
}
break;
}
}
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
case 'info':
$b = mysql_query("SELECT * FROM pitomec WHERE id != '' and id='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($b);
if($avto==0){
echo"Такого питомца не существует!";
include($path.'inc/down.php');exit;
}
$pit = mysql_fetch_array($b);
echo"<b>$pit[name]</b><br/>";
echo"<img src='pic/pit/$pit[rasa].gif'/><br/>";
echo"Раса: ";
if($pit[rasa]=="wolf"){echo"Волк<br/>";}
if($pit[rasa]=="dog"){echo"Собака<br/>";}
echo"Жизни: $pit[hp]/$pit[hpall]<br/>";

echo"<br/><b>Физические параметры</b><br/>";
echo"Урон: $pit[sila]<br/>";
echo"Защита: $pit[prot]<br/>";
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
case 'send':
if(empty($_GET[id])){
echo'Такого питомца нет!<br/>';
}else{
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` = '$log' and `id`='$_GET[id]' and `status`='off' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==1){
$pit = mysql_fetch_array($req);
if($pit['lord']!="$log"){
echo'Чужих питомцев нельзя передавать!<br/>';
include($path.'inc/down.php');exit;
}
}else{
echo'Такого питомца нет!<br/>';
include($path.'inc/down.php');exit;
}
if(empty($_POST[usr])){
echo "<form action=\"pitomec.php?mod=send&amp;id=$_GET[id]\" method=\"post\">";
echo '<b>Кому:</b><br/>';
echo '<input name="usr"/><br/>';
echo '<input type="submit" value="Продолжить"/></form><br/>';
}else{
$req = mysql_query("SELECT `id` FROM `users` WHERE `usr` = '".mysql_real_escape_string($_POST[usr])."' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==0){
echo'Такого игрока нет!<br/>';
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT `id` FROM `pit` WHERE `usr` = '".mysql_real_escape_string($_POST[usr])."' LIMIT 3");
$avto=mysql_num_rows($req);
if($avto==3){
echo'У игрока максимум питомцев!<br/>';
include($path.'inc/down.php');exit;
}

$req = mysql_query("SELECT `id` FROM `pit_item` WHERE `usr` = '$log' and `pit`='$_GET[id]' and `image`='yes' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==1){
echo'На питомце недолжно быть надетых вещей!<br/>';
include($path.'inc/down.php');exit;
}

mysql_query("UPDATE
        `pit` SET
        `usr` = '".mysql_real_escape_string($_POST[usr])."' WHERE `usr` = '$log' and `id`='$_GET[id]'");
        
         mysql_query("DELETE FROM `pit` WHERE `usr` = '$log' and `id`='$_GET[id]'");
         
         echo'Ваш питомец передан!<br/>';
         
         $time = date("H:i d.m.y");
$text = 'Игрок '.$log.' передал вам питомца '.$pit['name'];
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Зверовод', `user_to` = '".mysql_real_escape_string($_POST[usr])."', `time` = '$time', `read` = 1, `mail_msg` = '$text'");

}
}
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
case 'back':
if(empty($_GET[id])){
$req = mysql_query("SELECT `id`,`name` FROM `pit` WHERE `usr` != '$log' and `lord`='$log' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==1){
While($pitt = mysql_fetch_array($req))
{
echo'<b>'.$pitt['name'].'</b> <a href="pitomec.php?mod=back&amp;id='.$pitt['id'].'">Забрать</a><br/>';
}
}else{
echo'Нет отданых питомцев!<br/>';
}
}else{
if(empty($_GET[id])){
echo'Такого питомца нет!<br/>';
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT * FROM `pit` WHERE `usr` != '$log' and `lord`='$log' and `id`='$_GET[id]' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==0){
echo'Такого питомца нет!<br/>';
include($path.'inc/down.php');exit;
}

$pit = mysql_fetch_array($req);

$req = mysql_query("SELECT * FROM `pit_item` WHERE `usr` = '$pit[usr]' and `pit`='$_GET[id]' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto>0){

While($mag = mysql_fetch_array($req))
{

$pit['umin']=$pit['umin']-$mag['umin'];
$pit['umax']=$pit['umax']-$mag['umax'];

$pit['pgolova']=$pit['pgolova']-$mag['pgolova'];
$pit['pbody']=$pit['pbody']-$mag['pbody'];
$pit['pnogi']=$pit['pnogi']-$mag['pnogi'];

$pit['hpall']=$pit['hpall']-$mag['hp'];
$pit['krit']=$pit['krit']-$mag['krit'];
$pit['ukrit']=$pit['ukrit']-$mag['ukrit'];
$pit['antikrit']=$pit['antikrit']-$mag['antikrit'];
$pit['sila']=$pit['sila']-$mag['sila'];
$pit['lovk']=$pit['lovk']-$mag['lovk'];

}
mysql_query("UPDATE `pit` SET
         `sila` = '$pit[sila]',
         `prot` = '$pit[lovk]'
          WHERE `lord`='$log' and `id`='$_GET[id]' LIMIT 1");

mysql_query("UPDATE pit_item SET image = 'not',`pit`='0' WHERE `usr` = '$pit[usr]' and `pit`='$_GET[id]' and `image`='yes'");
}

mysql_query("UPDATE
        `pit` SET
        `usr` = '$log',
        `name` = '$pit[name]',
        `lord` = '$pit[lord]',
        `status` = 'off',
        `rasa` = '$pit[rasa]',
        `pol` = '$pit[pol]',
        `lasteda` = '$pit[lasteda]',
        `sec` = '$pit[sec]',
        `lvl` = '$pit[lvl]',
        `skill` = '$pit[skill]',
        `hp` = '$pit[hp]',
        `hpall` = '$pit[hpall]',
        `exp` = '$pit[exp]',
        `sila` = '$pit[sila]',
        `prot` = '$pit[prot]' WHERE `id`='$_GET[id]'");
        
         mysql_query("DELETE FROM `pit `id`='$_GET[id]'");
        
          $time = date("H:i d.m.y");
$text = 'Игрок '.$log.' забрал своего питомца '.$pit['name'];
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Зверовод', `user_to` = '$pit[usr]', `time` = '$time', `read` = 1, `mail_msg` = '$text'");

echo'Вы забрали '.$pit['name'].' у '.$pit['usr'].'<br/>';

}
echo"<a href=\"pitomec.php?\">Назад</a>";
break;
}
include($path.'inc/down.php');
?>