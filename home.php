<?php
define('PROTECTOR', 1);

$headmod = 'home';//фикс. места

$textl='Ратуша';
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




/*																																												
*/
//-----------------Удаляем клан-----------------------------------------------------------------
if (isset($_GET['delclan'])){

echo "<div class=msg><p>Распустить клан?</p></div>";
echo "<div class=inoy>
<a href=\"home.php?delclanyes\"><font color=#ff4040>Да</font></a>
<a href=\"home.php\"><font color=#6fcd72>Нет</font></a>
</div>";

}
if (isset($_GET['delclanyes'])){


$lider = mysql_num_rows(mysql_query("SELECT * FROM `clan` WHERE `lider` = '$log' LIMIT 1"));
	if($lider==1){

$req = mysql_query("SELECT * FROM `users` WHERE `clan` = '$log'");
	   While($cl = mysql_fetch_array($req))
	   {

	
	mysql_query ("UPDATE users SET clan='' WHERE usr='$cl[usr]' LIMIT 1"); // пишем данные игрока с новой суммой



		}
		
	mysql_query("DELETE FROM `clan` WHERE `lider`='$log' LIMIT 1"); // удаляем со списка кланов
	
	
echo "<p><font color=#f4d06e> Kлан распущен </font></p><hr/>";

}}
//-------------------------------------------------------------------------------------------------------------











$req = mysql_query("SELECT * FROM `clan` WHERE `lider` = '$log'");
////////////////////////////
$lider=mysql_num_rows($req);

$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);


switch($_GET['mod']){

default:
echo"<center><img src=\"pic/roz/clan.jpg\" alt=\"\"></center>";
echo "<p><b>Управление кланом:</b></p><div class=inoy>";


$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);

If ($clan['point']>0){$sk="<font color=#fffffc> +$clan[point]</font>";}


if($lider==0 and empty($udata['clan'])){
echo"<a href=\"home.php?mod=new\">Создать клан</a>";
}elseif($lider==0 and $clan['zam']!=$log and !empty($udata['clan'])){
echo"<a href=\"clanroom.php?\">Комната клана</a>";
}elseif($lider==1 or $clan['zam']==$log){

if($lider==1){echo"<a href=\"home.php?mod=zam\">Зам. клана</a>";
echo"<a href=\"clan_shop.php\">Магазин<font color=#ffffcc>(10 ур. за репутацию клана)</font></a>";}
echo"<a href=\"home.php?mod=toclan\">Взять в клан</a>";
echo"<a href=\"home.php?mod=logo\">Эмблема клана <font color=#ffffcc>(3 ур. 10 CoL)</font></a>";
echo"<a href=\"home.php?mod=cl_logo\">Логотип клана <font color=#ffffcc>(9 ур. 200 CoL)</font></a>";
echo"<a href=\"home.php?mod=clanlist\">Список клана</a>";
echo"<a href=\"home.php?mod=citys\">Города клана</a>";
echo"<a href=\"home.php?mod=skil\">Умения клана $sk</a>";
echo"<a href=\"home.php?delclan\"><font color=#ff4040>Распустить клан</font></a></div><br/>";
}
echo"<div class=inoy><a href=\"home.php?mod=list\">Список кланов</a>";
echo "</div>";
break;

////////////////////Заместитель клана///////////////////
case 'zam':

$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);


if($lider==1){

if (empty($clan['zam'])) {echo "<p><font color=grey>Заместитель клана не назначен</font></p><hr/>";}else{echo "<p><font color=grey>Заместитель клана <b>$clan[zam]</b></font></p><hr/>";}


if ($_GET['z']!='yes'){
echo"<div class=inoy><a href=\"home.php?mod=zam&z=yes\">Назначить</a>";
echo"<a href=\"home.php?mod=zam&z=del\">Убрать</a></div>";}

			if ($_GET['z']=='yes') // назначаем
				{
				if (empty($_POST['zam']))	{ //если кто зам не введенно то вводим
				echo "<form action=\"home.php?mod=zam&z=yes\" method=\"POST\">";
				echo "<div class=menu><b><font color=grey>Ник игрока:</font></b><br/>
				<input type=\"text\" name=\"zam\" value=\"$_GET[zam]\" size=\"10\" maxlength=\"10\"/> <br/>";
				echo "<input type='submit' name='ok' value=' Назначить ' /></font></form> ";
										}else{//иначе пишем результат
					
					//проверка
					$req = mysql_query("SELECT * FROM `users` WHERE `clan` = '$log' and `usr` = '$_POST[zam]' LIMIT 1");
					$avto=mysql_num_rows($req);
					if($avto==0){
					echo'<p><font color=#ff4040>Персонаж не состоит в Вашем клане!</font></p>';
					}else{

					
					
					
						mysql_query("UPDATE clan SET zam='$_POST[zam]' WHERE `lider` = '$log'");	

					echo"<font color=grey><p>Вы назначили заместителем клана персонажа <b>$_POST[zam]</b></p></font>";}}

				}

if ($_GET['z']=='del'){// удаляем
mysql_query("UPDATE clan SET zam='' WHERE `lider` = '$log'");
echo"<font color=#ff4040><p>У Вас теперь нет заместителя клана!</b></p></font>";
}

}else{echo "<p><font color=red>Доступ закрыт</font></p>";}

		echo"<br/><div class=silka><a href=\"home.php?\">Назад</a></div>";

break;

// умения клана и их повешение //
/// скилы
case 'skil':
echo "<div class=menu>";

//////////////


///////////////Patt урон////////////////
if (isset($_GET['patt'])){
$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);
if($clan['patt']>=100){
echo'Максимальный процент!<hr/>';}else{
if($clan['point']>0 and $clan['col']>=$clan['patt']){
$nskill=$clan['point']-1;
$nparm=$clan['patt']+1;
$ncol=$clan['col']-$clan['patt'];
mysql_query("UPDATE clan SET point = '$nskill',patt='$nparm',col='$ncol' WHERE `lider` = '$udata[clan]'");
echo'Параметр повышен успешно!<hr/>';
}else{
echo'Нет свободных очков опыта или недостаточно Coin of LucK!<hr/>';
}}}
///////////////matt урон////////////////
if (isset($_GET['matt'])){
$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);
if($clan['matt']>=100){
echo'Максимальный процент!<hr/>';}else{
if($clan['point']>0 and $clan['col']>=$clan['matt']){
$nskill=$clan['point']-1;
$nparm=$clan['matt']+1;
$ncol=$clan['col']-$clan['matt'];
mysql_query("UPDATE clan SET point = '$nskill',matt='$nparm',col='$ncol' WHERE `lider` = '$udata[clan]'");
echo'Параметр повышен успешно!<hr/>';
}else{
echo'Нет свободных очков опыта или недостаточно Coin of LucK!<hr/>';
}}}
///////////////pdef защита////////////////
if (isset($_GET['pdef'])){
$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);
if($clan['pdef']>=100){
echo'Максимальный процент!<hr/>';}else{
if($clan['point']>0 and $clan['col']>=$clan['pdef']){
$nskill=$clan['point']-1;
$nparm=$clan['pdef']+1;
$ncol=$clan['col']-$clan['pdef'];
mysql_query("UPDATE clan SET point = '$nskill',pdef='$nparm',col='$ncol' WHERE `lider` = '$udata[clan]'");
echo'Параметр повышен успешно!<hr/>';
}else{
echo'Нет свободных очков опыта или недостаточно Coin of LucK!<hr/>';
}}}
///////////////mdef////////////////
if (isset($_GET['mdef'])){
$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);
if($clan['mdef']>=100){
echo'Максимальный процент!<hr/>';}else{
if($clan['point']>0 and $clan['col']>=$clan['mdef']){
$nskill=$clan['point']-1;
$nparm=$clan['mdef']+1;
$ncol=$clan['col']-$clan['mdef'];
mysql_query("UPDATE clan SET point = '$nskill',mdef='$nparm',col='$ncol' WHERE `lider` = '$udata[clan]'");
echo'Параметр повышен успешно!<hr/>';
}else{
echo'Нет свободных очков опыта или недостаточно Coin of LucK!<hr/>';
}}}

//////////////////
$req = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($req);

If ($clan['point']>0){$sk="<font color=red> +$clan[point]</font>";}
echo "<b>Умения клана:</b> $sk<br/><br/>";

echo "<hr/> <font color=#f4d06e>P.Att:  +$clan[patt]%";
echo '<br/><div style="background:3px #999999;height:5px;width:125px;"><div style="background-color:#007F0E;height:5px;width:'.$clan['patt'].'%;"></div></div>';
if($clan['point']>0 and $clan['patt']<100){echo" <a href=\"home.php?mod=skil&patt\">+1 за $clan[patt] CoL</a>";}

echo "<hr/>M.Att: +$clan[matt]%";
echo '<br/><div style="background:3px #999999;height:5px;width:125px;"><div style="background-color:#007F0E;height:5px;width:'.$clan['matt'].'%;"></div></div>';
if($clan['point']>0 and $clan['matt']<100){echo" <a href=\"home.php?mod=skil&matt\">+1 за $clan[matt] CoL</a>";}

echo "<hr/>P.Def: +$clan[pdef]%";
echo '<br/><div style="background:3px #999999;height:5px;width:125px;"><div style="background-color:#007F0E;height:5px;width:'.$clan['pdef'].'%;"></div></div>';
if($clan['point']>0 and $clan['pdef']<100){echo" <a href=\"home.php?mod=skil&pdef\">+1 за $clan[pdef] CoL</a>";}

echo "<hr/>M.Def: +$clan[mdef]%";
echo '<br/><div style="background:3px #999999;height:5px;width:125px;"><div style="background-color:#007F0E;height:5px;width:'.$clan['mdef'].'%;"></div></div>';
if($clan['point']>0 and $clan['mdef']<100){echo" <a href=\"home.php?mod=skil&mdef\">+1 за $clan[mdef] CoL</a>";}

echo "<br/><br/>";
echo "<hr/><font color=#f4d06e> Каждый параметр увеличует в бою параметры героя в процентном соотношении.";
if($clan['point']>0){echo" <hr/><font color=green>** Coin of Luck берутся из казны клана.";}
echo "<hr/>";
echo "</font><br/><a href=\"home.php?\">Назад</a></div>";
break;




////////////////////////////////////////////////
case 'list':

echo "<font color=#f4d06e>Список кланов: </font><br/><br/>";
$req = mysql_query("SELECT * FROM `clan` ORDER BY `exp` DESC");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($clan = mysql_fetch_array($req))
{
echo"<div class=inoy><a href=\"clan.php?id=$clan[id]\"><font color=#f4d06e>$clan[name] <font color=#ffffcc>  $clan[lvl]<font color=#f4d06e> ур. </font> <font color=#ffffcc>  (".number_format($clan['exp'], 0, ',', " ")." exp)</font></a></div>";
}
}else{
echo"<b>Нет кланов</b><br/>";
}
echo"<br/><div class=silka><a href=\"home.php?\">Назад</a></div>";
break;

case 'new':
if(empty($_POST['name'])){
echo'<b>Требования:</b><br/>';
echo'Уровень: 10<br/>
Аден: 10`000`000<br/>
Coin of Luck: 400<br/>';

echo "<form action=\"home.php?mod=new\" method=\"post\">";
echo"Название клана:<br/>";
echo"<input class='input' type=\"text\" size=\"10\" name=\"name\" maxlength=\"15\"/><br/>";
echo"Описание клана:<br/>";
echo"<input class='input' type=\"text\" size=\"10\" name=\"desk\" maxlength=\"100\"/><br/>";
echo '<input class="button" type="submit" value="Создать" /></form>';
}else{
$_POST['name'] = htmlspecialchars($_POST['name']);

if($lider==1 or !empty($udata['clan'])){
echo'Вы уже находитесь в клане!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT `name` FROM `clan` WHERE `name` = '$_POST[name]' LIMIT 1");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto==1){
echo'Данное имя занято!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
$avto=mysql_num_rows($req);
if($udata['money']<10000000 or $udata['lvl']<10 or $udata['almaz']<400){
echo'Требования не выполнены!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}


mysql_query("INSERT INTO `clan` SET `lider` = '$log',`name` = '$_POST[name]', `desk` = '$_POST[desk]'");
$udata['money']=$udata['money']-10000000;
$udata['almaz']=$udata['almaz']-400;
mysql_query("UPDATE `users` SET `money` = '$udata[money]',`almaz` = '$udata[almaz]',`clan`='$log' WHERE `usr` = '$log'");
echo"Клан <b>$_POST[name]</b> успешно создан!<br/>";
}
echo"<a href=\"home.php?\">Назад</a>";
break;

case'logo':
if($lider==0 and $clan['zam']!=$log){
echo'Вы не лидер клана и не зам!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}

echo "Для изменения эмблемы нужно 3 уровень клана и 10 Coin of Luck<br/>";
if(empty($_GET['name'])){
$dira = opendir ("pic/clan");
while ($filea = lin2_readdir ($dira)) 
{if (( $filea != ".") && ($filea != ".."))
{$aa[]=$filea;}}
lin2_closedir ($dira);
if (!isset($aa)) $aa = array(); $totala = count($aa)-1;

for ($ia = 0; $ia < $totala; $ia++){

$data_namea[]=$aa[$ia];
}

foreach($data_namea as $ka=>$va)
{
$dat_screena[]="<div class=inoy><a href=\"home.php?name=$data_namea[$ka]&amp;mod=logo\"><img src=\"pic/clan/$data_namea[$ka]\" alt=\"Эмблема\"/> - выбрать</a></div>";

}


$totala = count($dat_screena);



if (empty($_GET['logs'])) $logs = 0;
else $logs = (int)$_GET['logs'];
if ($totala < $logs + 10){ $end = $totala; }
else {$end = $logs + 10; }
for ($ia = $logs; $ia < $end; $ia++){

echo"$dat_screena[$ia]";

}

echo "<div class=menu><br/>";
if ($logs != 0) {echo '<a href="home.php?logs='.($logs - 10).'&amp;mod=logo"> назад</a> ';}
if ($totala > $logs + 10) {echo ' <a href="home.php?logs='.($logs + 10).'&amp;mod=logo">вперёд</a>';}

echo"<br/>Всего: $totala эмблем<br/>";
}else{
$name = htmlspecialchars(stripslashes(addslashes($_GET['name'])));

if(!@file("pic/clan/$name")){echo"Такой эмблемы не существует."; include($path.'inc/down.php'); exit;}

$reqcl = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($reqcl);
if($clan['lvl']<3 or $clan['col']<10){
echo'Требования не выполнены! Деньги берутся с капитала клана.<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}

$col=$clan['col']-10;



mysql_query("UPDATE `clan` SET `emblema` = '$name',`col` = '$col' WHERE `lider` = '$log'");

echo"<p> Эмблема выбрана. Списано 10 Coin of Luck</p><br/>";
}
echo"<br/><div class=silka><a href=\"home.php?\">Назад</a></div>";
break;

////////////////////////////////////////////// лого клана /////////////////////////////////////////////////////////////
case'cl_logo':
if($lider==0 and $clan['zam']!=$log){
echo'Вы не лидер клана и не зам!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
echo "Для изменения логотипа нужно 9 уровень клана и 200 Coin of Luck.<hr/>";

if(empty($_GET['name'])){
$dira = opendir ("pic/clan_logo");
while ($filea = lin2_readdir ($dira)) 
{if (( $filea != ".") && ($filea != ".."))
{$aa[]=$filea;}}
lin2_closedir ($dira);
if (!isset($aa)) $aa = array(); $totala = count($aa)-1;

for ($ia = 0; $ia < $totala; $ia++){

$data_namea[]=$aa[$ia];
}

foreach($data_namea as $ka=>$va)
{
$dat_screena[]="</div><div class=inoy><a href=\"home.php?name=$data_namea[$ka]&amp;mod=cl_logo\"> <img src=\"pic/clan_logo/$data_namea[$ka]\" alt=\"lg\"/> - выбрать</a></div><div class=menu>";

}


$totala = count($dat_screena);



if (empty($_GET['logs'])) $logs = 0;
else $logs = (int)$_GET['logs'];
if ($totala < $logs + 10){ $end = $totala; }
else {$end = $logs + 10; }
for ($ia = $logs; $ia < $end; $ia++){

echo"$dat_screena[$ia]";

}


if ($logs != 0) {echo '<a href="home.php?logs='.($logs - 10).'&amp;mod=cl_logo">&#x41D;&#x430;&#x437;&#x430;&#x434;</a> ';}
if ($totala > $logs + 10) {echo ' <a href="home.php?logs='.($logs + 10).'&amp;mod=cl_logo">&#x414;&#x430;&#x43B;&#x435;&#x435;</a>';}

echo"<br/>Всего: $totala логотипов<br/>";
}else{
$name = htmlspecialchars(stripslashes(addslashes($_GET['name'])));

if(!@file("pic/clan_logo/$name")){echo"Такого логотипа не существует."; include($path.'inc/down.php'); exit;}
$reqcl = mysql_query("SELECT * FROM `clan` where `lider`='$udata[clan]'");
$clan = mysql_fetch_array($reqcl);

if($clan['lvl']<9 or $clan['col']<200){
echo'Требования не выполнены! Деньги берутся с капитала клана.<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
$col=$clan['col']-200;
mysql_query("UPDATE `clan` SET `logo` = '$name',`col` = '$col' WHERE `lider` = '$log'");

echo"Логотип выбран.<br/>
 Списано: 200 Coin of Luck </br> 
 Осталось: $col Coin of Luck<br/>";
}
echo"<br/><a href=\"home.php?\">Назад</a>";
break;
/////////////////////////////////////////////////

case 'toclan':
if($lider==0 and $clan['zam']!=$log){
echo'Вы не лидер клана и не зам!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
if(empty($_POST['name']) or empty($_POST['text'])){
echo "<form action=\"home.php?mod=toclan\" method=\"post\">";
echo"Игрок:<br/>";
echo"<input class='input' type=\"text\" size=\"10\" name=\"name\" maxlength=\"10\"/><br/>";
echo"Текст приглашения:<br/>";
echo"<input class='input' type=\"text\" size=\"10\" name=\"text\" maxlength=\"100\"/><br/>";
echo '<input class="button" type="submit" value="Отослать" /></form>';
}else{
$name=htmlspecialchars($_POST['name']);
$text=htmlspecialchars($_POST['text']);

$req = mysql_query("SELECT clan FROM `users` WHERE `usr` = '$name'");
////////////////////////////
$avto=mysql_num_rows($req);
$inv = mysql_fetch_array($req);
if($avto=="0"){
echo'Нет такого игрока!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
if($name==$log){
echo'Себе отправлять нельзя!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
if($inv['clan']==$log){
echo'Этот игрок итак в вашем клане!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
if(!empty($inv['clan'])){
echo'Этот игрок в другом клане!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}

mysql_query("INSERT INTO `invite` SET `clan` = '$udata[clan]',`usr` = '$name',`text` = '$text'");
echo"Приглашение для игрока $name отослано!<br/>";
}
echo"<a href=\"home.php?\">Назад</a>";
break;
case 'clanlist':
if($lider==0 and $clan['zam']!=$log){
echo'Вы не лидер клана и не зам!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT * FROM `clan` WHERE `lider` = '$udata[clan]'");
////////////////////////////
$cl = mysql_fetch_array($req);
echo"<b>$cl[name]</b>:<br/>";
echo "<b>1. <a href=\"search.php?nick=$cl[lider]&amp;go=go\">$cl[lider]</a> [Лидер]</b><br/>";
$req = mysql_query("SELECT clan,usr FROM `users` where `clan`='$udata[clan]' and `usr`!='$udata[clan]' ORDER BY lvl DESC");
////////////////////////////
$avto=mysql_num_rows($req);
$i=2;
if($avto>=1){
While($clan = mysql_fetch_array($req))
{
echo"$i. <a href=\"search.php?nick=$clan[usr]&amp;go=go\">$clan[usr]</a> [<a href=\"home.php?mod=outclan&amp;usr=$clan[usr]\">исключить</a>]<br/>";
$i++;
}
echo"<br/><b>Всего соклановцев: $avto</b><br/>";
}else{
echo"<b>Нет соклановцев</b><br/>";
}
echo"<a href=\"home.php?\">Назад</a>";
break;
case 'outclan':
if(empty($_GET['usr'])){
echo'Не выбран игрок!<br/>';
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$_GET[usr]' and `clan` = '$log' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($req);

if ($avto == 1) {
    $claner = mysql_fetch_array($req);
    
    mysql_query("UPDATE `users` SET `clan` = '' WHERE `usr` = '$_GET[usr]'");
$time = date("H:i d.m.y");
$text = "$log исключил вас из клана!";
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Ратуша', `user_to` = '$_GET[usr]', `time` = '$time', `read` = 1, `mail_msg` = '$text'");

echo"$_GET[usr] исключён из клана!<br/>";
    }else{
    echo'Нет такого игрока в вашем клане!';
    include($path.'inc/down.php');
    exit;
    }
    echo"<a href=\"home.php?\">Назад</a>";
break;
case 'citys':
if($lider==0 and $clan['zam']!=$log){
echo'Вы не лидер клана и не зам!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}

$req = mysql_query("SELECT * FROM `zamok` WHERE clan='$udata[clan]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){

echo "<p><font color=orange>Города клана</font></p><hr/>";

While($c = mysql_fetch_array($req))
{
echo"<b>$c[name]</b><br/> Прочность ворот: $c[vorota]  <a href=\"home.php?mod=repair&amp;id=$c[id]\"> [+] </a><hr/>";
}
}else{
echo"<b>Нет городов</b><br/>";
}
echo"<br/><dic class=silka><a href=\"home.php?\">Назад</a></div>";
break;
case 'repair':
if($lider==0 and $clan['zam']!=$log){
echo'Вы не лидер клана и не зам!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
$req = mysql_query("SELECT * FROM `zamok` WHERE clan='$udata[clan]' and `id`='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto==0){
echo'Нет таких ворот!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
$zamok = mysql_fetch_array($req);
if(empty($_POST['kol'])){
echo'Прочность сейчас: '.$zamok['vorota'].'<br/>';
echo'За 1`000 ед. прочности снимут 1 Coin of Luck!<br/>';
echo "<form action=\"home.php?mod=repair&amp;id=$_GET[id]\" method=\"post\">";
echo '<b>Заплатить CoL:(мин 10)</b><br/>';
echo '<input name="kol"/><br/>';
echo '<input type="submit" value="Увеличить"/></form>';
}else{
if((float)$_POST['kol']<10){
echo"Минимальное количество 10 Coin of Luck!";
include($path.'inc/down.php');exit;
}
$pr=round(1000*$_POST['kol']);
if($_POST['kol']>$udata['almaz']){
echo'Нехватает Coin of Luck!<br/>';
echo"<a href=\"home.php?\">Назад</a>";
include($path.'inc/down.php');exit;
}
$udata['almaz']=$udata['almaz']-$_POST['kol'];
$zamok['vorota']=$zamok['vorota']+$pr;
        mysql_query("UPDATE `users` SET `almaz` = '$udata[almaz]' WHERE `usr` = '$log'");
        mysql_query("UPDATE `zamok` SET `vorota` = '$zamok[vorota]' WHERE clan='$udata[clan]' and `id`='$_GET[id]'");
        
        echo"Прочность ворот увеличена на $pr!<br/>";
}
echo"<a href=\"home.php?\">Назад</a>";
break;
}
echo "</div></b></b></p></p></u></font></font>";
include($path.'inc/down.php');
?>