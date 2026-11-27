<?
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


switch($_GET[mod]){

default:

$req = mysql_query("SELECT * FROM `shopelexirs` WHERE `city` != '' and `tip`='elexir'");
////////////////////////////
$avto=mysql_num_rows($req);
echo "<div class=menu><b><font color=grey>Выберите  элексир:</font></b><br/></div>";
echo "<small><font color=#007F46>(цены указаны за 1000 банок)</font></small>";
echo "<hr/>";
echo "<form action=\"shopelexirs.php?mod=sell\" method=\"POST\">";

While($mag = mysql_fetch_array($req))
{
echo" <img src=\"pic/skr/$mag[name].gif\" alt=\"*\"/>";
echo " <label><input type='radio' name='result' value='$mag[id]' /></label>\n";

echo "<a href=\"shopelexirs.php?mod=info&amp;id=$mag[id]\">$mag[name]</a>";
echo "<br/> &#160   Цена:  $mag[cena] Coin of Luck <hr/>";
}

echo "<div class=menu><b><font color=grey>Введите <br/> кол-во банок:</b><br/>
<input type=\"text\" name=\"col\" size=\"5\" maxlength=\"9\"/> ";

echo " х 1`000 </font><br/><input type='submit' name='ok' value='Купить' /><hr/></div>\n";

echo"<br/><a href=\"shopelexirs.php?\">Назад</a>";
break;
case 'info':
$req = mysql_query("SELECT * FROM `shopelexirs` WHERE `city` != '' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
switch($mag[what]){
case 'hp':
$tip='жизнь';
break;
case 'mp':
$tip='ману';
break;
}
echo"<b>$mag[name]</b><br/>
Востанавливает: $tip<br/>
Едениц: $mag[give]<br/>
Цена: $mag[cena]<br/><br/>
";
echo"<a href=\"shopelexirs.php?\">Назад</a>";
break;

case 'sell':

if (empty($_POST[result])){
echo "Вы не выбрали элексир!";}else{


if (empty($_POST[col]) or $_POST[col]<=0){
echo "Вы не ввели нужное количество элексиров!";}else{

$_GET[id]=$_POST['result'];
$col = $_POST[col];
$col2 = $col * 1000; // кол банок

$req = mysql_query("SELECT * FROM `shopelexirs` WHERE `city` != '' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка, такой вещи нет!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

$req=mysql_query("SELECT * FROM domination WHERE id = '1'");
$dom = mysql_fetch_assoc($req);
if($dom['white']>$dom['black']){
$liders='white';
}elseif($dom['black']>$dom['white']){
$liders='black';
}else{
$liders='not';
}
if($udata['storona']==$liders){
$mag[cena]=round($mag[cena]-(($mag[cena]/100)*15));
}

$cen = $mag[cena]*$col; 		// цена элексира
$nmoney = $udata[almaz]-$cen;	// сколько осталось

if($nmoney<0){
echo'Нехватает Coin of Luck!';
}else{
$req1 = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `lat_name` = '$mag[lat_name]'");
$res = mysql_fetch_array($req1);
$avto1=mysql_num_rows($req1);
if($avto1==0){
mysql_query("INSERT INTO
        `res` SET
        `usr` = '$log',
        `name` = '$mag[name]',
        `lat_name` = '$mag[lat_name]',
        `tip` = '$mag[tip]',
        `what` = '$mag[what]',
        `give` = '$mag[give]',
        `kol` = '$col2',
        `cena` = '$mag[cena]'");
        
$req = mysql_query("SELECT * FROM `zamok` WHERE `city` = '$udata[city]' LIMIT 1");
////////////////////////////
$city = mysql_fetch_array($req);
if($city[clan]!='not' and $udata[clan]!=$city[clan]){
$req = mysql_query("SELECT * FROM `clan` WHERE `lider`='$city[clan]'");
////////////////////////////
$clan = mysql_fetch_array($req);
$clan[money]=$clan[money]+round(($mag[cena]/100)*2);

mysql_query("UPDATE `clan` SET `money` = '$clan[money]' WHERE `lider` = '$city[clan]'");
}

}else{

$nk=$res[kol]+$col2;
mysql_query("UPDATE `res` SET `kol` = '$nk' WHERE `usr` = '$log' and `lat_name` = '$mag[lat_name]'");
}
mysql_query("UPDATE users SET votecoin = '$nmoney' WHERE usr = '$log'");

echo"
Вещь $mag[name] куплена в количестве $col2 штук!<br/>
Coin of Luck потрачено: $cen<br/>
Coin of Luck осталось: $nmoney<br/>
";
}}}
echo"<br/><a href=\"shopelexirs.php?\">Назад</a>";

break;
}
include($path.'inc/down.php');
?>