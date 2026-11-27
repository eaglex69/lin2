<?php
define('PROTECTOR', 1);

$headmod = 'dress';//фикс. места

$textl='Инвентарь';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `id`='$_GET[id]' and `image`='not'");
$avto=mysql_num_rows($req);
$req1 = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `image`='yes'");
$avto1=mysql_num_rows($req1);
if($avto1>=1){
echo'<div class=dot>Сначала снимите то что на вас!</div>';
echo "<div class=inoy> <a href=\"/inventar.php?mod=item&o=$_GET[tip]\">Назад</a> </div>";
include($path.'inc/down.php');
exit;
}

if($avto==0){
echo'<div class=msg>Ошибка, такой вещи нет!</div>';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

if($mag['nlvl'] > $udata['lvl']){
echo'<p>Вещь не подходит по уровню! Иди в окрестности и подними свой level.</p>';
include($path.'inc/down.php');
exit;
}

$npatt=$mag['patt']+$udata['patt'];
$nmatt=$mag['matt']+$udata['matt'];

$npdef=$mag['pdef']+$udata['pdef'];
$nmdef=$mag['mdef']+$udata['mdef'];


mysql_query("UPDATE `users` SET
         `pdef` = '$npdef',
         `mdef` = '$nmdef',
         `patt` = '$npatt',
         `matt` = '$nmatt'
          WHERE usr = '$log'");

mysql_query("UPDATE item SET image = 'yes' WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `id`='$_GET[id]'");

echo"<div class=dot>Вы одели $mag[name]!</div>";
echo"<a href=\"inventar.php?mod=item&o=$_GET[tip]\">Назад</a>";

include($path.'inc/down.php');
?>