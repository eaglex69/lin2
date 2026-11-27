<?php
define('PROTECTOR', 1);

$headmod = 'elexirs';//фикс. места

$textl='Инвентарь';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


$_GET['id'] = $_POST['result'];

$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'<p>Ошибка, такой вещи нет!</p>';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
if($mag['tip']!='elexir'){
echo'<p>Ошибка, доступ закрыт!</p>';
include($path.'inc/down.php');
exit;
}

if((float)$_POST['col']<0){
echo'<p>Вводите положительное значение!</p>';
include($path.'inc/down.php');
exit;
}

if (empty($_POST['result'])){
echo'<p>Не выбран элексир!</p>';
include($path.'inc/down.php');
exit;}


if (empty($_POST['col'])){$_POST['col'] = 1;}
$mag['kol'] = $mag['kol'] - $_POST['col'];

if($mag['kol']<0){
echo'<p>Недостаточно элексиров!</p>';
include($path.'inc/down.php');
exit;
}

$rez = $mag['give']*$_POST['col'];


switch($mag['what']){
case 'hp':
$newp=$mag['give']*$_POST['col']+$udata['hp'];
if($newp>$udata['hpall']){$newp=$udata['hpall'];}

mysql_query("UPDATE `users` SET `hp` = '$newp' WHERE usr = '$log'");
if($mag['kol']==0){
mysql_query("DELETE FROM `res` WHERE `usr` = '$log' and `id`='$_GET[id]'");
}else{
mysql_query("UPDATE res SET kol = '$mag[kol]' WHERE `usr` = '$log' and `id`='$_GET[id]'");
}
echo"<p>Вы использовали $mag[name]! <b>+$rez</b> HP.</p>";
break;
case 'mp':
$newp=$mag['give']*$_POST['col']+$udata['mp'];
if($newp>$udata['mpall']){$newp=$udata['mpall'];}

mysql_query("UPDATE `users` SET `mp` = '$newp' WHERE usr = '$log'");
if($mag['kol']==0){
mysql_query("DELETE FROM `res` WHERE `usr` = '$log' and `id`='$_GET[id]'");
}else{
mysql_query("UPDATE res SET kol = '$mag[kol]' WHERE `usr` = '$log' and `id`='$_GET[id]'");
}
echo"<p>Вы использовали $mag[name]! <b>+$rez</b> MP.</p>";
break;
}

if (empty($_GET['btl'])) {echo"<a href=\"inventar.php?\">Назад</a>";}else{echo"<a href=\"battle.php?\">Вернутся в бой!</a>";}

include($path.'inc/down.php');
?>