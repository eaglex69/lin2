<?php
define('PROTECTOR', 1);

$headmod = 'Тотализатор';
$textl='total';
$path='../';
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');



///

if ($udata['dostup'] >= 4){


if(isset ($_GET['del'])){
$id_del = $_GET['del'];
header("Location:admin.php?mod=del_ok");
echo mysql_query("DELETE FROM `tot_match` WHERE `id` = '$id_del'");
echo mysql_query("DELETE FROM `tot_stavki` WHERE `idm` = '$id_del'");
}


switch($_GET['mod']){


#### Выигравшые юзеры ####
case "v":
if(empty($_POST['submit'])){
$_GET['idm'] = intval($_GET['idm']);
$q=mysql_query("SELECT * FROM `tot_match` WHERE `id`='".$_GET['idm']."';");
$arr=mysql_fetch_array($q);

echo "<form action='?mod=v&idm=$_GET[idm]' method=post>$arr[kom1] :<input type='hidden' value='$arr[kom1]' name='kom1name'></input><input value='$arr[kom2]' type='hidden' name='kom2name'></input><input size='3' name='s1'><br/>$arr[kom2] :<input size='3' name='s2'><br/><input type='submit' name='submit'>";
}else{
mysql_query("UPDATE `tot_stavki`  SET `win1`='".$_POST['s1']."' , `win2`='".$_POST['s2']."' WHERE `idm`='".$_GET['idm']."'");
mysql_query("INSERT INTO `tot_istor`(`idm`,`kom1name`,`kom2name`,`date`,`kom1`,`kom2`)values('".$_GET['idm']."','".$_POST['kom1name']."','".$_POST['kom2name']."','".date("d.m.y")."','".$_POST['s1']."','".$_POST['s2']."')");

$q=mysql_query("SELECT * FROM `tot_stavki` WHERE `kom1`='".$_POST['s1']."' and `kom2`='".$_POST['s2']."' and `idm`='".$_GET['idm']."';");

if(mysql_num_rows($q) == 0)
{
	echo "Угадавших счет нет.";	}

while($arr=mysql_fetch_array($q)){

mysql_query("UPDATE `users` SET `tot_o` = tot_o + 1 WHERE `id`='".$arr['idu']."' LIMIT 1");

$ar=mysql_fetch_array(mysql_query("SELECT `user` FROM `users` WHERE `id`='".$arr['idu']."';"));
echo "<br><a href='/$arr[idu]'>$ar[user]</a><br/>";
}
mysql_query("UPDATE `tot_match` SET `s1`='1' WHERE `id`='$_GET[idm]'");

$all=mysql_num_rows($q);
echo "<br/>Выигравших юзеров - $all<br/>";
}
echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;


##############



################

#### Удаление команды ####
case "del_ok":

if(empty($_POST['submit'])){
	echo "<big><font color='red'><b>Удаленно успешно!</b></font></big><br>";
echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
}

break;
############


#### Добавление новой команды ####
case "add_ok":

if(empty($_POST['submit'])){
	echo "<big><font color='red'><b>Добавленно успешно!</b></font></big><br>";
echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
}

break;
######## Добавление команд ####################
case "add":
if(empty($_POST['submit'])){
$d=date('Y.m.d H:i');
echo "<form action='?mod=add' method=post>Комманда 1:<br/>
<input type='text' name='kom1'><br/> Комманда 2:<br/>
<input name='kom2'>
<br/> Дата матча:<br/><input name='date' value='$d'><br/>
Описание матча:<br/>
<input type='text' name='opisanie'><br/>

<input type='submit' value='Подача ставок' name='submit'>";
}else{
$_POST['date'] = $_POST['date'];
$date=str_replace('.','',$_POST['date']);
$date=str_replace(':','',$date);
$date=str_replace(' ','',$date);
$time2= $_POST['date'];
$time=time();
$date2=date("d.m.y");
mysql_query("INSERT INTO `tot_match` SET `kom1`='".$_POST['kom1']."', `kom2`='".$_POST['kom2']."', `date`='".$date."', `s1`='nu' ,`time2`='$time2', `date2`='".$date2."' , `time`='".$time."' , `opisanie`='".$_POST['opisanie']."'");
header("Location: admin.php?mod=add_ok");

echo "<br>";
}
echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;
#############

case"base":
echo"<a href='?mod=base_ist'>Очистить таблицу истории</a><br>";
echo"<a href='?mod=base_match'>Очистить таблицу матчей</a><br>";
echo"<a href='?mod=base_stav'>Очистить таблицу ставок</a><br>";
echo"<a href='?mod=base_all'>Очистить все таблицы тотализатора</a><br><br>";
break;


case"base_ist":
echo"<b><big>Таблица `tot_istor` успешно очишенна!</big></b><br><br>";
mysql_query("TRUNCATE TABLE `tot_istor`");

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;

case"base_match":
echo"<b><big>Таблица `tot_match` успешно очишенна!</big></b><br><br>";
mysql_query("TRUNCATE TABLE `tot_match`");

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;

case"base_stav":
echo"<b><big>Таблица `tot_stavki` успешно очишенна!</big></b><br><br>";
mysql_query("TRUNCATE TABLE `tot_stavki`");

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;

case"base_all":
echo"<b><big>Таблицы `tot_stavki`,`tot_istor`,`tot_match` успешно очишенны!</big></b><br><br>";
mysql_query("TRUNCATE TABLE `tot_stavki`");
mysql_query("TRUNCATE TABLE `tot_istor`");
mysql_query("TRUNCATE TABLE `tot_match`");

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;




######## Оповешение ####################
case "opov":

$zagolov=mysql_result(mysql_query("SELECT (opis) FROM `tot_adminka` WHERE `id`='1'"),0);
$mes=mysql_result(mysql_query("SELECT (message) FROM `tot_adminka` WHERE `id`='1'"),0);
echo"<form action='?mod=opov_ok' method='post'>";
echo"<div class='menu_razd'>Управление оповешением</div>";




echo"<small><b>Сообшение оповешения :</b></small><br>";
echo"<textarea name='zag_mes'  type='text' maxlenhgt='500'>$mes</textarea><br>";

echo"<small><b>Строка оповешения :</b></small><br>";
echo"
<select name='zag_me'><option value=1>ДА<option value=0>НЕТ</select>";

echo"<input name='nick_adm' type='hidden' value='$user[user]'></input>";

echo"<br><input value='Сохроняем' type='submit'></input>";

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;

case "opov_ok":


$messa = $_POST['zag_mes'];
$time = date("H:i | d.m.y");
$on = $_POST['zag_me'];
$nick_adm = $_POST['nick_adm'] ;


echo"<big><b><font color='red'>Успешно добавлено!</font></big></b><br>";

mysql_query("UPDATE `tot_adminka`
SET `admin_nick` = '".$nick_adm."' , `time`='".$time."',`on`='".$on."',`message`='".$messa."',`opis`='Новости' WHERE `id`='1'");

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;
#############

### Настройки тотала ###

case "ochki":

echo"<form action='?mod=ochki_1' method='post'>";
echo"<div class='menu_razd'>Управление очками</div>";


echo"<small><b>Введите ник :</b></small><br>";
echo"
<input type='name' name='user' maxlength='16'></input>";



echo"<br><input value='Дальше' type='submit'></input>";

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;
##
case "ochki_1":
$nick=mysql_result(mysql_query("SELECT (usr) FROM `users` WHERE `usr`='".$_POST['user']."'"),0);
$ochk=mysql_result(mysql_query("SELECT (tot_o) FROM `users` WHERE `usr`='".$_POST['user']."'"),0);

echo"<form action='?mod=ochki_ok' method='post'>";
echo"<input type='hidden' value='".$nick."' name='user'></input><br>";
echo 'Ник :'.$nick.'<BR>';
echo"<input value='".$ochk."' maxlength='6' name='ochk'></input><br>";
echo"<input value='Изменить' type='submit'><br>";

echo"</form>";
echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;
##
case "ochki_ok":
$och = trim ($_POST['ochk']);

echo"<big><b><font color='red'>Успешно изминено!</font></big></b><br>";

mysql_query("UPDATE `users` SET `tot_o`='".$och."' WHERE `usr`='".$_POST['user']."'");

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;
#############

case"system":

echo"<form action='?mod=setting_ok' method='post'>";
echo"Приостановить временно роботу тотализатора :<br>";
echo"<select name='on_total'><option value='1'>Нет<option value='0'>Да</select><br>";

echo"<input value='Сохранить' type='submit'></input>";
echo"</form>";


echo '<br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;

####
case "setting_ok":


echo"<big><b><font color='red'>Успешно изминено!</font></big></b><br>";

mysql_query("UPDATE `tot_adminka` SET `on_total`='".$_POST['on_total']."' WHERE `id`='1'");

echo '<br><br><a href=admin.php>В админку</a>';
echo "<br /><a href='index.php'>В тотализатор</a>";
break;
#############













### Стандартная страница
default:
$q=mysql_query("SELECT * FROM `tot_match` WHERE `s1`='nu';");
if(mysql_num_rows($q) == 0){
echo "<br>";
echo "<big><font color='red'><b>Матчей нет.</b></font></big>";
echo "<br>";
}
while($arr=mysql_fetch_array($q)){



echo "<br/><b>Команды : <a href='?mod=v&idm=$arr[id]'>$arr[kom1] - $arr[kom2]</a></b><br>";
echo "[<a href='?del=".$arr['id']."'>x</a>]<br>";
}
echo "<br>
<b>Функции админки :</b>
<br /><a href='?mod=add'>Добавить матч</a>
<br /><a href='?mod=system'>Настройки системы </a>
<br /><a href='?mod=ochki'>Управление очками юзеров </a>
<br /><a href='?mod=opov'>Управление оповешениями</a>
<br /><a href='?mod=base'>Управление базой</a><br>
<br /><a href='index.php'>В тотализатор</a>";
echo "<br>";
break;}
###########


#####  Если права не соответствуют админу ####
}else{header("Location: ../enter?");}
include($path.'inc/down.php');
?>