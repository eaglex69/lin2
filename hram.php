<?php
define('PROTECTOR', 1);

$headmod = 'msg';//фикс. места

$textl='Свадебный храм';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
switch($_GET[mod]){

default:


echo "
<table><tr>
<td><img src='/pic/roz/wedding.png' alt='*' /></td><td>

В храме проводятся свадьбы между игроками, которые по-настоящему сильно любят друг друга и готовы навечно связать свои узы любви
</td>
</tr>
</table>
";



echo'<div class="m_bg"></div>';
if($udata[zamujem] == NULL){
echo "<a href=\"hram.php?mod=yes\">Подать заявку</a><br/>";}
else{
echo "<a href=\"hram.php?mod=delbrak\">Разорвать брак с  $udata[zamujem]</a>";
}
echo "<br />";
echo "Стоимость заявки - 100 CoL<br>";
echo "Стоимость расторжения - бесплатно!<br>";
break;

case 'delbrak':
mysql_query("DELETE FROM `ya_zamujem` WHERE usr='$udata[zamujem]'");//чистим логи
mysql_query("UPDATE `users` SET `zamujem` = '' WHERE `usr` = '$udata[zamujem]'");
mysql_query("UPDATE `users` SET `zamujem` = '' WHERE `usr` = '$log'");
mysql_query("DELETE FROM `ya_zamujem` WHERE usr='$log'");//чистим логи
mysql_query("DELETE FROM `zamuj` WHERE usr='$log'");//чистим логи

echo "Брак успешно разорван<br>";


break;

case 'yes':
if($udata[pol]=="w"){echo"Заявку может подавать только парень<br/>";
include($path.'inc/down.php');
exit;
}


if(empty($_POST[name]) or empty($_POST[text])){
echo "<form action=\"hram.php?mod=yes\" method=\"post\">";
echo"Напишите ник жены:<br/>";
echo"<input class='input' type=\"text\" size=\"20\" name=\"name\" maxlength=\"10\"/><br/>";
echo"Текст к заявке:<br/>";
echo"<input class='input' type=\"text\" size=\"20\" name=\"text\" maxlength=\"100\"/><br/>";
echo '<input class="button" type="submit" value="Продолжить" /></form>';
}else{
$name=htmlspecialchars($_POST[name]);
$text=htmlspecialchars($_POST[text]);

$req = mysql_query("SELECT * FROM `ya_zamujem` WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto==1){
echo'Вы уже состоите в браке!';
echo'<br />';
echo "<a href=\"hram.php?\"> Вернуться</a><br>";
include($path.'inc/down.php');
exit;
}


$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$name'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto=="0"){
echo'Нет такого игрока!';
echo'<br />';
echo "<a href=\"hram.php?\"> Вернуться</a><br>";
include($path.'inc/down.php');
exit;
}


$pk = mysql_fetch_assoc($req);

$reqtmp = mysql_query("SELECT * FROM `ya_zamujem` WHERE `usr` = '$pk[usr]' LIMIT 1");
if (mysql_num_rows($reqtmp)>=1){
echo'Персонаж которому вы посылаете заявку уже в браке!';
echo'<br />';
echo "<a href=\"hram.php?\"> Вернуться</a><br>";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$name'");
$pk = mysql_fetch_assoc($req);
if($pk[pol] == "m"){
echo'Геям вход запрещен!';
echo'<br />';
echo "<a href=\"hram.php?\"> Вернуться</a><br>";
include($path.'inc/down.php');
exit;
}



$reqtmp = mysql_query("SELECT * FROM `zamuj` WHERE `usr` = '$pk[usr]' LIMIT 1");
if (mysql_num_rows($reqtmp)>=1){
echo'У этого персонажа уже есть активная заявка!';
include($path.'inc/down.php');
exit;
}


if($name==$log){
echo'Cебе предлагать нельзя<br/>';
echo'<br />';
echo "<a href=\"hram.php?\"> Вернуться</a><br>";
include($path.'inc/down.php');
exit;
}


if($udata['almaz']<100){
echo'<font color=red>У вас не хватает CoL!</font>';
echo'<br />';
echo "<a href=\"hram.php?\"> Вернуться</a><br>";
include($path.'inc/down.php');
exit;
}



$almaz=$udata[almaz]-100;//almaz

mysql_query("UPDATE `users` SET `almaz` = '$almaz' WHERE `usr` = '$log'");


mysql_query("INSERT INTO `zamuj` SET `usr` = '$name',`text` = '$text', `clan` = '$log'");
echo"Заявка для $name отослана!<br/>";
}
echo'<br />';
echo "<a href=\"hram.php?\"> Вернуться</a><br>";




}
include($path.'inc/down.php');
?>