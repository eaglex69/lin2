<?php
define('PROTECTOR', 1);

$headmod = 'shopelexirs';//фикс. места

$textl='Магазин';
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


switch($_GET['mod']){

default:

$req = mysql_query("SELECT * FROM `shop_ny`");
////////////////////////////
$avto=mysql_num_rows($req);
echo "<div class=menu><b><font color=grey>Выберите  вещь:</font></b><br/></div>";
echo "<small><font color=#007F46>У вас $udata[ig] снежинок</font></small>";
echo "<hr/>";
echo "<form action=\"shop_ny.php?mod=sell\" method=\"POST\">";

While($mag = mysql_fetch_array($req))
{
echo" <img src=\"shmot/$mag[name].jpg\" alt=\"*\"/>";
echo " <label><input type='radio' name='result' value='$mag[id]' /></label>\n";

echo "$mag[name] [<a href=\"shop_ny.php?mod=info&amp;id=$mag[id]\">Инфо</a>]";
echo "<br/> &#160   Цена:  $mag[cena] Снежинок<hr/>";
}



echo "<br/><input type='submit' name='ok' value='Купить' /><hr/></div>\n";

echo"<br/><a href=\"shopelexirs.php?\">Назад</a>";
break;
case 'info':
$req = mysql_query("SELECT * FROM `shop_ny` WHERE `city` != '' and `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

echo"<b>$mag[name]</b><br/>
Уровень $mag[nlvl]<br/>
Физ.атака: $mag[patt]<br/>
Маг.атака: $mag[matt]<br/>
Физ.защита: $mag[pdef]<br/>
Маг.защита: $mag[mdef]<br/>
<br/>";
echo"<a href=\"shop_ny.php?\">Назад</a>";
break;

case 'sell':

if (empty($_POST['result'])){
echo "Вы не выбрали вещь!";include($path.'inc/down.php');exit;}
$_GET['id']=$_POST['result'];
$req = mysql_query("SELECT * FROM `shop_ny` WHERE `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка, такой вещи нет!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
$nmoney = $udata['ig']-$mag['cena'];
if($nmoney<0){
echo'Нехватает Иголок!';
include($path.'inc/down.php');
exit;
}
mysql_query("INSERT INTO
        `item` SET
        `usr` = '$log',
        `tip` = '$mag[tip]',
        `ruka` = '$mag[tip2]',
        `name` = '$mag[name]',
        `cena` = '$mag[cena]',
        `patt` = '$mag[patt]',
        `matt` = '$mag[matt]',
        `pdef` = '$mag[pdef]',
        `mdef` = '$mag[mdef]',
        `time` = '$mag[time]',
        `soul` = '$mag[soul]',
        `spirit` = '$mag[spirit]',
        `nlvl` = '$mag[nlvl]',
        `image` = 'not'");
mysql_query("UPDATE users SET ig = '$nmoney' WHERE usr = '$log'");
echo"
Вещь $mag[name] куплена!<br/>
Иголок потрачено: $mag[cena]<br/>
Иголок осталось: $nmoney<br/>
";
break;
}
include($path.'inc/down.php');
?>