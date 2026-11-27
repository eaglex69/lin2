<?
define('PROTECTOR', 1);

$headmod = 'gm_panel';//фикс. места

$textl='GM-Panel';
///////////////////////
	$path='../';			//
//////////////////////
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
///////////////
$taim = 150;
$date = time();
$timeout = $date - $taim;
////////////////
if ($udata[dostup]>3){

switch($_GET[mod]){

default:
echo "<p><b><font color=grey>Редакторы вещей</font></b></p>";
echo ".::<a href=\"redshmot.php?mod=shop\">Простые вещи</a><br/>";
echo ".::<a href=\"redshmot.php?mod=shop_vip\">ViP вещи</a><br/>";
echo ".::<a href=\"redshmot.php?mod=shop_gold\">Gold вещи</a><br/>";
echo ".::<a href=\"redshmot.php?mod=shop_lux\">Люкс вещи</a><br/>";
echo ".::<a href=\"redshmot.php?mod=shop_ny\">Новогодние вещи</a><br/>";
echo ".::<a href=\"redshmot.php?mod=shop_chat\">Чат вещи</a><br/>";
break;

case 'shop_chat':
$req = mysql_query("SELECT * FROM `shop_chat`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name]</b> [<a href=\"redshmot.php?mod=shop_chat_del&amp;id=$mag[id]\">удалить</a>] [<a href=\"redshmot.php?mod=shop_chat_red&amp;id=$mag[id]\">изм</a>]<br/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop_chat_del':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_chat` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}
mysql_query("DELETE FROM `shop_chat` WHERE `id`='$_GET[id]'");
echo'Вещь удалена!<br/>';
echo"<a href=\"redshmot.php?\">Назад</a>";
break;


case 'shop_chat_red':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_chat` WHERE `id`='$_GET[id]' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такой вещи не существует.";include($path.'inc/down.php');exit;}
$item = mysql_fetch_array($req);
if(empty($_POST[name])){
echo '<form action="redshmot.php?mod=shop_chat_red&amp;id='.$_GET[id].'" method="post">';
echo"Имя<br/>
<input class='input' type=\"text\" value=\"$item[name]\" size=\"10\" name=\"name\"/><br/>";
echo"Цена<br/>
<input class='input' type=\"text\" value=\"$item[cena]\" size=\"10\" name=\"cena\"/><br/>";
echo"Город<br/>
<input class='input' type=\"text\" value=\"$item[city]\" size=\"10\" name=\"cena\"/><br/>";
echo "Тип:<br/>
<select name=\"tip\">
<option value=\"weapon\">Оружие</option>
<option value=\"golova\">Шлем</option>
<option value=\"body\">Доспехи</option>
<option value=\"ruki\">Рукавицы</option>
<option value=\"nogi\">Сапоги</option>
<option value=\"pants\">Штаны</option>
<option value=\"plash\">Штаны</option>
<option value=\"poyas\">Серёжка</option>
<option value=\"shit\">Щит</option>
<option value=\"amulet\">Амулет</option>
<option value=\"kolco\">Кольцо</option>
</select><br/>";
echo"P.Att<br/>
<input class='input' type=\"text\" value=\"$item[patt]\" size=\"10\" name=\"umin\"/><br/>";
echo"M.Att<br/>
<input class='input' type=\"text\" value=\"$item[matt]\" size=\"10\" name=\"umax\"/><br/>";
echo"P.Def<br/>
<input class='input' type=\"text\" value=\"$item[pdef]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"M.Def<br/>
<input class='input' type=\"text\" value=\"$item[mdef]\" size=\"10\" name=\"pbody\"/><br/>";
echo"SoulShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[soul]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"SpiritShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[spirit]\" size=\"10\" name=\"pbody\"/><br/>";
echo"Требуемый уровень<br/>
<input class='input' type=\"text\" value=\"$item[nlvl]\" size=\"10\" name=\"nlvl\"/><br/>";
echo "Тип оруж.:<br/>
<select name=\"tip2\">
<option value=\"ps\">Пусто</option>
<option value=\"ydar\">Ударное</option>
<option value=\"luk\">Луки</option>
<option value=\"kin\">Кинжалы</option>
<option value=\"sdv\">Сдвоенное</option>
<option value=\"kas\">Кастеты</option>
<option value=\"kniga\">Книги</option>
<option value=\"koppik\">Копья/Пики</option>
<option value=\"me4\">Мечи</option>
<option value=\"rap\">Рапиры</option>
<option value=\"arbalet\">Арбалеты</option>
</select><br/>";
echo '<input class="button" type="submit" value="Изменить" /></form>';
}else{
mysql_query("UPDATE `shop_chat` SET
        `name` = '$_POST[name]',
        `cena` = '$_POST[cena]',
        `city` = '$_POST[city]',
        `tip` = '$_POST[tip]',
        `tip2` = '$_POST[tip2]',
        `pdef` = '$_POST[pdef]',
        `mdef` = '$_POST[mdef]',
        `patt` = '$_POST[patt]',
        `matt` = '$_POST[matt]',
        `soul` = '$_POST[soul]',
        `spirit` = '$_POST[spirit]',
        `nlvl` = '$_POST[nlvl]' WHERE id='$_GET[id]' LIMIT 1");
echo'Вещь '.$_POST[name].' изменена!<br/>';
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop_ny':
$req = mysql_query("SELECT * FROM `shop_ny`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name]</b> [<a href=\"redshmot.php?mod=shop_ny_del&amp;id=$mag[id]\">удалить</a>] [<a href=\"redshmot.php?mod=shop_ny_red&amp;id=$mag[id]\">изм</a>]<br/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop_ny_del':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_ny` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}
mysql_query("DELETE FROM `shop_ny` WHERE `id`='$_GET[id]'");
echo'Вещь удалена!<br/>';
echo"<a href=\"redshmot.php?\">Назад</a>";
break;


case 'shop_ny_red':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_ny` WHERE `id`='$_GET[id]' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такой вещи не существует.";include($path.'inc/down.php');exit;}
$item = mysql_fetch_array($req);
if(empty($_POST[name])){
echo '<form action="redshmot.php?mod=shop_ny_red&amp;id='.$_GET[id].'" method="post">';
echo"Имя<br/>
<input class='input' type=\"text\" value=\"$item[name]\" size=\"10\" name=\"name\"/><br/>";
echo"Цена<br/>
<input class='input' type=\"text\" value=\"$item[cena]\" size=\"10\" name=\"cena\"/><br/>";
echo"Город<br/>
<input class='input' type=\"text\" value=\"$item[city]\" size=\"10\" name=\"cena\"/><br/>";
echo "Тип:<br/>
<select name=\"tip\">
<option value=\"weapon\">Оружие</option>
<option value=\"golova\">Шлем</option>
<option value=\"body\">Доспехи</option>
<option value=\"ruki\">Рукавицы</option>
<option value=\"nogi\">Сапоги</option>
<option value=\"pants\">Штаны</option>
<option value=\"plash\">Штаны</option>
<option value=\"poyas\">Серёжка</option>
<option value=\"shit\">Щит</option>
<option value=\"amulet\">Амулет</option>
<option value=\"kolco\">Кольцо</option>
</select><br/>";
echo"P.Att<br/>
<input class='input' type=\"text\" value=\"$item[patt]\" size=\"10\" name=\"umin\"/><br/>";
echo"M.Att<br/>
<input class='input' type=\"text\" value=\"$item[matt]\" size=\"10\" name=\"umax\"/><br/>";
echo"P.Def<br/>
<input class='input' type=\"text\" value=\"$item[pdef]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"M.Def<br/>
<input class='input' type=\"text\" value=\"$item[mdef]\" size=\"10\" name=\"pbody\"/><br/>";
echo"SoulShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[soul]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"SpiritShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[spirit]\" size=\"10\" name=\"pbody\"/><br/>";
echo"Требуемый уровень<br/>
<input class='input' type=\"text\" value=\"$item[nlvl]\" size=\"10\" name=\"nlvl\"/><br/>";
echo "Тип оруж.:<br/>
<select name=\"tip2\">
<option value=\"ps\">Пусто</option>
<option value=\"ydar\">Ударное</option>
<option value=\"luk\">Луки</option>
<option value=\"kin\">Кинжалы</option>
<option value=\"sdv\">Сдвоенное</option>
<option value=\"kas\">Кастеты</option>
<option value=\"kniga\">Книги</option>
<option value=\"koppik\">Копья/Пики</option>
<option value=\"me4\">Мечи</option>
<option value=\"rap\">Рапиры</option>
<option value=\"arbalet\">Арбалеты</option>
</select><br/>";
echo '<input class="button" type="submit" value="Изменить" /></form>';
}else{
mysql_query("UPDATE `shop_ny` SET
        `name` = '$_POST[name]',
        `cena` = '$_POST[cena]',
        `city` = '$_POST[city]',
        `tip` = '$_POST[tip]',
        `tip2` = '$_POST[tip2]',
        `pdef` = '$_POST[pdef]',
        `mdef` = '$_POST[mdef]',
        `patt` = '$_POST[patt]',
        `matt` = '$_POST[matt]',
        `soul` = '$_POST[soul]',
        `spirit` = '$_POST[spirit]',
        `nlvl` = '$_POST[nlvl]' WHERE id='$_GET[id]' LIMIT 1");
echo'Вещь '.$_POST[name].' изменена!<br/>';
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;


case 'shop_gold':
$req = mysql_query("SELECT * FROM `shop_gold`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name]</b> [<a href=\"redshmot.php?mod=shop_gold_del&amp;id=$mag[id]\">удалить</a>] [<a href=\"redshmot.php?mod=shop_gold_red&amp;id=$mag[id]\">изм</a>]<br/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop_gold_del':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_gold` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}
mysql_query("DELETE FROM `shop_gold` WHERE `id`='$_GET[id]'");
echo'Вещь удалена!<br/>';
echo"<a href=\"redshmot.php?\">Назад</a>";
break;


case 'shop_gold_red':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_gold` WHERE `id`='$_GET[id]' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такой вещи не существует.";include($path.'inc/down.php');exit;}
$item = mysql_fetch_array($req);
if(empty($_POST[name])){
echo '<form action="redshmot.php?mod=shop_gold_red&amp;id='.$_GET[id].'" method="post">';
echo"Имя<br/>
<input class='input' type=\"text\" value=\"$item[name]\" size=\"10\" name=\"name\"/><br/>";
echo"Цена<br/>
<input class='input' type=\"text\" value=\"$item[cena]\" size=\"10\" name=\"cena\"/><br/>";
echo"Город<br/>
<input class='input' type=\"text\" value=\"$item[city]\" size=\"10\" name=\"cena\"/><br/>";
echo "Тип:<br/>
<select name=\"tip\">
<option value=\"weapon\">Оружие</option>
<option value=\"golova\">Шлем</option>
<option value=\"body\">Доспехи</option>
<option value=\"ruki\">Рукавицы</option>
<option value=\"nogi\">Сапоги</option>
<option value=\"pants\">Штаны</option>
<option value=\"plash\">Штаны</option>
<option value=\"poyas\">Серёжка</option>
<option value=\"shit\">Щит</option>
<option value=\"amulet\">Амулет</option>
<option value=\"kolco\">Кольцо</option>
</select><br/>";
echo"P.Att<br/>
<input class='input' type=\"text\" value=\"$item[patt]\" size=\"10\" name=\"umin\"/><br/>";
echo"M.Att<br/>
<input class='input' type=\"text\" value=\"$item[matt]\" size=\"10\" name=\"umax\"/><br/>";
echo"P.Def<br/>
<input class='input' type=\"text\" value=\"$item[pdef]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"M.Def<br/>
<input class='input' type=\"text\" value=\"$item[mdef]\" size=\"10\" name=\"pbody\"/><br/>";
echo"SoulShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[soul]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"SpiritShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[spirit]\" size=\"10\" name=\"pbody\"/><br/>";
echo"Требуемый уровень<br/>
<input class='input' type=\"text\" value=\"$item[nlvl]\" size=\"10\" name=\"nlvl\"/><br/>";
echo "Тип оруж.:<br/>
<select name=\"tip2\">
<option value=\"ps\">Пусто</option>
<option value=\"ydar\">Ударное</option>
<option value=\"luk\">Луки</option>
<option value=\"kin\">Кинжалы</option>
<option value=\"sdv\">Сдвоенное</option>
<option value=\"kas\">Кастеты</option>
<option value=\"kniga\">Книги</option>
<option value=\"koppik\">Копья/Пики</option>
<option value=\"me4\">Мечи</option>
<option value=\"rap\">Рапиры</option>
<option value=\"arbalet\">Арбалеты</option>
</select><br/>";
echo '<input class="button" type="submit" value="Изменить" /></form>';
}else{
mysql_query("UPDATE `shop_gold` SET
        `name` = '$_POST[name]',
        `cena` = '$_POST[cena]',
        `city` = '$_POST[city]',
        `tip` = '$_POST[tip]',
        `tip2` = '$_POST[tip2]',
        `pdef` = '$_POST[pdef]',
        `mdef` = '$_POST[mdef]',
        `patt` = '$_POST[patt]',
        `matt` = '$_POST[matt]',
        `soul` = '$_POST[soul]',
        `spirit` = '$_POST[spirit]',
        `nlvl` = '$_POST[nlvl]' WHERE id='$_GET[id]' LIMIT 1");
echo'Вещь '.$_POST[name].' изменена!<br/>';
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;


case 'shop_lux':
$req = mysql_query("SELECT * FROM `shop_lux`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name]</b> [<a href=\"redshmot.php?mod=shop_lux_del&amp;id=$mag[id]\">удалить</a>] [<a href=\"redshmot.php?mod=shop_lux_red&amp;id=$mag[id]\">изм</a>]<br/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop_lux_del':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_lux` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}
mysql_query("DELETE FROM `shop_lux` WHERE `id`='$_GET[id]'");
echo'Вещь удалена!<br/>';
echo"<a href=\"redshmot.php?\">Назад</a>";
break;


case 'shop_lux_red':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_lux` WHERE `id`='$_GET[id]' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такой вещи не существует.";include($path.'inc/down.php');exit;}
$item = mysql_fetch_array($req);
if(empty($_POST[name])){
echo '<form action="redshmot.php?mod=shop_lux_red&amp;id='.$_GET[id].'" method="post">';
echo"Имя<br/>
<input class='input' type=\"text\" value=\"$item[name]\" size=\"10\" name=\"name\"/><br/>";
echo"Цена<br/>
<input class='input' type=\"text\" value=\"$item[cena]\" size=\"10\" name=\"cena\"/><br/>";
echo"Город<br/>
<input class='input' type=\"text\" value=\"$item[city]\" size=\"10\" name=\"cena\"/><br/>";
echo "Тип:<br/>
<select name=\"tip\">
<option value=\"weapon\">Оружие</option>
<option value=\"golova\">Шлем</option>
<option value=\"body\">Доспехи</option>
<option value=\"ruki\">Рукавицы</option>
<option value=\"nogi\">Сапоги</option>
<option value=\"pants\">Штаны</option>
<option value=\"plash\">Штаны</option>
<option value=\"poyas\">Серёжка</option>
<option value=\"shit\">Щит</option>
<option value=\"amulet\">Амулет</option>
<option value=\"kolco\">Кольцо</option>
</select><br/>";
echo"P.Att<br/>
<input class='input' type=\"text\" value=\"$item[patt]\" size=\"10\" name=\"umin\"/><br/>";
echo"M.Att<br/>
<input class='input' type=\"text\" value=\"$item[matt]\" size=\"10\" name=\"umax\"/><br/>";
echo"P.Def<br/>
<input class='input' type=\"text\" value=\"$item[pdef]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"M.Def<br/>
<input class='input' type=\"text\" value=\"$item[mdef]\" size=\"10\" name=\"pbody\"/><br/>";
echo"SoulShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[soul]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"SpiritShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[spirit]\" size=\"10\" name=\"pbody\"/><br/>";
echo"Требуемый уровень<br/>
<input class='input' type=\"text\" value=\"$item[nlvl]\" size=\"10\" name=\"nlvl\"/><br/>";
echo "Тип оруж.:<br/>
<select name=\"tip2\">
<option value=\"ps\">Пусто</option>
<option value=\"ydar\">Ударное</option>
<option value=\"luk\">Луки</option>
<option value=\"kin\">Кинжалы</option>
<option value=\"sdv\">Сдвоенное</option>
<option value=\"kas\">Кастеты</option>
<option value=\"kniga\">Книги</option>
<option value=\"koppik\">Копья/Пики</option>
<option value=\"me4\">Мечи</option>
<option value=\"rap\">Рапиры</option>
<option value=\"arbalet\">Арбалеты</option>
</select><br/>";
echo '<input class="button" type="submit" value="Изменить" /></form>';
}else{
mysql_query("UPDATE `shop_lux` SET
        `name` = '$_POST[name]',
        `cena` = '$_POST[cena]',
        `city` = '$_POST[city]',
        `tip` = '$_POST[tip]',
        `tip2` = '$_POST[tip2]',
        `pdef` = '$_POST[pdef]',
        `mdef` = '$_POST[mdef]',
        `patt` = '$_POST[patt]',
        `matt` = '$_POST[matt]',
        `soul` = '$_POST[soul]',
        `spirit` = '$_POST[spirit]',
        `nlvl` = '$_POST[nlvl]' WHERE id='$_GET[id]' LIMIT 1");
echo'Вещь '.$_POST[name].' изменена!<br/>';
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop_vip':
$req = mysql_query("SELECT * FROM `shop_vip`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name]</b> [<a href=\"redshmot.php?mod=shop_vip_del&amp;id=$mag[id]\">удалить</a>] [<a href=\"redshmot.php?mod=shop_vip_red&amp;id=$mag[id]\">изм</a>]<br/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop_vip_del':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_vip` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}
mysql_query("DELETE FROM `shop_vip` WHERE `id`='$_GET[id]'");
echo'Вещь удалена!<br/>';
echo"<a href=\"redshmot.php?\">Назад</a>";
break;


case 'shop_vip_red':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop_vip` WHERE `id`='$_GET[id]' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такой вещи не существует.";include($path.'inc/down.php');exit;}
$item = mysql_fetch_array($req);
if(empty($_POST[name])){
echo '<form action="redshmot.php?mod=shop_vip_red&amp;id='.$_GET[id].'" method="post">';
echo"Имя<br/>
<input class='input' type=\"text\" value=\"$item[name]\" size=\"10\" name=\"name\"/><br/>";
echo"Цена<br/>
<input class='input' type=\"text\" value=\"$item[cena]\" size=\"10\" name=\"cena\"/><br/>";
echo"Город<br/>
<input class='input' type=\"text\" value=\"$item[city]\" size=\"10\" name=\"cena\"/><br/>";
echo "Тип:<br/>
<select name=\"tip\">
<option value=\"weapon\">Оружие</option>
<option value=\"golova\">Шлем</option>
<option value=\"body\">Доспехи</option>
<option value=\"ruki\">Рукавицы</option>
<option value=\"nogi\">Сапоги</option>
<option value=\"pants\">Штаны</option>
<option value=\"plash\">Штаны</option>
<option value=\"poyas\">Серёжка</option>
<option value=\"shit\">Щит</option>
<option value=\"amulet\">Амулет</option>
<option value=\"kolco\">Кольцо</option>
</select><br/>";
echo"P.Att<br/>
<input class='input' type=\"text\" value=\"$item[patt]\" size=\"10\" name=\"umin\"/><br/>";
echo"M.Att<br/>
<input class='input' type=\"text\" value=\"$item[matt]\" size=\"10\" name=\"umax\"/><br/>";
echo"P.Def<br/>
<input class='input' type=\"text\" value=\"$item[pdef]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"M.Def<br/>
<input class='input' type=\"text\" value=\"$item[mdef]\" size=\"10\" name=\"pbody\"/><br/>";
echo"SoulShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[soul]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"SpiritShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[spirit]\" size=\"10\" name=\"pbody\"/><br/>";
echo"Требуемый уровень<br/>
<input class='input' type=\"text\" value=\"$item[nlvl]\" size=\"10\" name=\"nlvl\"/><br/>";
echo "Тип оруж.:<br/>
<select name=\"tip2\">
<option value=\"ps\">Пусто</option>
<option value=\"ydar\">Ударное</option>
<option value=\"luk\">Луки</option>
<option value=\"kin\">Кинжалы</option>
<option value=\"sdv\">Сдвоенное</option>
<option value=\"kas\">Кастеты</option>
<option value=\"kniga\">Книги</option>
<option value=\"koppik\">Копья/Пики</option>
<option value=\"me4\">Мечи</option>
<option value=\"rap\">Рапиры</option>
<option value=\"arbalet\">Арбалеты</option>
</select><br/>";
echo '<input class="button" type="submit" value="Изменить" /></form>';
}else{
mysql_query("UPDATE `shop_vip` SET
        `name` = '$_POST[name]',
        `cena` = '$_POST[cena]',
        `city` = '$_POST[city]',
        `tip` = '$_POST[tip]',
        `tip2` = '$_POST[tip2]',
        `pdef` = '$_POST[pdef]',
        `mdef` = '$_POST[mdef]',
        `patt` = '$_POST[patt]',
        `matt` = '$_POST[matt]',
        `soul` = '$_POST[soul]',
        `spirit` = '$_POST[spirit]',
        `nlvl` = '$_POST[nlvl]' WHERE id='$_GET[id]' LIMIT 1");
echo'Вещь '.$_POST[name].' изменена!<br/>';
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop':
$req = mysql_query("SELECT * FROM `shop`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name]</b> [<a href=\"redshmot.php?mod=shop_del&amp;id=$mag[id]\">удалить</a>] [<a href=\"redshmot.php?mod=shop_red&amp;id=$mag[id]\">изм</a>]<br/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;

case 'shop_del':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}
mysql_query("DELETE FROM `shop` WHERE `id`='$_GET[id]'");
echo'Вещь удалена!<br/>';
echo"<a href=\"redshmot.php?\">Назад</a>";
break;


case 'shop_red':
if(empty($_GET[id])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `shop` WHERE `id`='$_GET[id]' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такой вещи не существует.";include($path.'inc/down.php');exit;}
$item = mysql_fetch_array($req);
if(empty($_POST[name])){
echo '<form action="redshmot.php?mod=shop_red&amp;id='.$_GET[id].'" method="post">';
echo"Имя<br/>
<input class='input' type=\"text\" value=\"$item[name]\" size=\"10\" name=\"name\"/><br/>";
echo"Цена<br/>
<input class='input' type=\"text\" value=\"$item[cena]\" size=\"10\" name=\"cena\"/><br/>";
echo"Город<br/>
<input class='input' type=\"text\" value=\"$item[city]\" size=\"10\" name=\"cena\"/><br/>";
echo "Тип:<br/>
<select name=\"tip\">
<option value=\"weapon\">Оружие</option>
<option value=\"golova\">Шлем</option>
<option value=\"body\">Доспехи</option>
<option value=\"ruki\">Рукавицы</option>
<option value=\"nogi\">Сапоги</option>
<option value=\"pants\">Штаны</option>
<option value=\"plash\">Штаны</option>
<option value=\"poyas\">Серёжка</option>
<option value=\"shit\">Щит</option>
<option value=\"amulet\">Амулет</option>
<option value=\"kolco\">Кольцо</option>
</select><br/>";
echo"P.Att<br/>
<input class='input' type=\"text\" value=\"$item[patt]\" size=\"10\" name=\"umin\"/><br/>";
echo"M.Att<br/>
<input class='input' type=\"text\" value=\"$item[matt]\" size=\"10\" name=\"umax\"/><br/>";
echo"P.Def<br/>
<input class='input' type=\"text\" value=\"$item[pdef]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"M.Def<br/>
<input class='input' type=\"text\" value=\"$item[mdef]\" size=\"10\" name=\"pbody\"/><br/>";
echo"SoulShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[soul]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"SpiritShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[spirit]\" size=\"10\" name=\"pbody\"/><br/>";
echo"Требуемый уровень<br/>
<input class='input' type=\"text\" value=\"$item[nlvl]\" size=\"10\" name=\"nlvl\"/><br/>";
echo "Тип оруж.:<br/>
<select name=\"tip2\">
<option value=\"ps\">Пусто</option>
<option value=\"ydar\">Ударное</option>
<option value=\"luk\">Луки</option>
<option value=\"kin\">Кинжалы</option>
<option value=\"sdv\">Сдвоенное</option>
<option value=\"kas\">Кастеты</option>
<option value=\"kniga\">Книги</option>
<option value=\"koppik\">Копья/Пики</option>
<option value=\"me4\">Мечи</option>
<option value=\"rap\">Рапиры</option>
<option value=\"arbalet\">Арбалеты</option>
</select><br/>";
echo '<input class="button" type="submit" value="Изменить" /></form>';
}else{
mysql_query("UPDATE `shop` SET
        `name` = '$_POST[name]',
        `cena` = '$_POST[cena]',
        `city` = '$_POST[city]',
        `tip` = '$_POST[tip]',
        `tip2` = '$_POST[tip2]',
        `pdef` = '$_POST[pdef]',
        `mdef` = '$_POST[mdef]',
        `patt` = '$_POST[patt]',
        `matt` = '$_POST[matt]',
        `soul` = '$_POST[soul]',
        `spirit` = '$_POST[spirit]',
        `nlvl` = '$_POST[nlvl]' WHERE id='$_GET[id]' LIMIT 1");
echo'Вещь '.$_POST[name].' изменена!<br/>';
}
echo"<a href=\"redshmot.php?\">Назад</a>";
break;
}

}else{

echo "<b><font color=red><p><center>Доступ закрыт!</center></p></font></b>";
}
include($path.'inc/down.php');
?>
