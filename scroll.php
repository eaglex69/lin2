<?php
define('PROTECTOR', 1);

$headmod = 'scroll';//фикс. места

$textl='Инвентарь';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'<div class=dot><p>Ошибка, такого свитка нет!</div></p>';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);
if($mag['tip']!='scroll'){
echo'<div class=dot><p>Ошибка, доступ закрыт!</div></p>';
include($path.'inc/down.php');
exit;
}
switch($mag['what']){
case 'scroll_weapon':
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto=='0'){
echo'<div class=dot><p>Ошибка, сначала возьмите оружие</div></p>!';
include($path.'inc/down.php');
exit;
}
$it = mysql_fetch_array($req);

$u = explode("|",$mag['give']);
$npatt=$u[0]+$udata['patt'];
$nmatt=$u[1]+$udata['matt'];
$w=explode("*",$it['name']);
if(empty($w[1])){
$nam="$w[0]*1";
}else{
$kol=$w[1]+1;
$nam="$w[0]*$kol";}

if(empty($w[1])){$stavka=100;}
if($w[1]==1){$stavka=99;}
if($w[1]==2){$stavka=98;}
if($w[1]==3){$stavka=97;}
if($w[1]==4){$stavka=96;}
if($w[1]==5){$stavka=95;}
if($w[1]==6){$stavka=70;}
if($w[1]==7){$stavka=65;}
if($w[1]==8){$stavka=60;}
if($w[1]==9){$stavka=55;}
if($w[1]>=10){$stavka=50;}

$priz=rand(1,100);

if($priz<=$stavka){

$num=$u[0]+$it['patt'];
$nux=$u[1]+$it['matt'];

mysql_query("UPDATE `users` SET `patt` = '$npatt',`matt` = '$nmatt' WHERE usr = '$log'");
mysql_query("UPDATE `item` SET
      `patt` = '$num',
      `matt` = '$nux',
      `name` = '$nam'
       WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
       
$msg="	Оружие заточено!<br/>
		Новый урон:
		P.Att: $num<br/>
		M.Att: $nux<br/>";
}else{
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$it = mysql_fetch_array($req);

$npatt=$udata['patt']-$it['patt'];
$nmatt=$udata['matt']-$it['matt'];

$npdef=$udata['pdef']-$it['pdef'];
$nmdef=$udata['mdef']-$it['mdef'];

mysql_query("UPDATE `users` SET
         `patt` = '$npatt',
         `matt` = '$nmatt',
         `pdef` = '$npdef',
         `mdef` = '$nmdef'
          WHERE usr = '$log'");

mysql_query("DELETE FROM `item` WHERE `usr` = '$log' and `tip`='weapon' and `image`='yes'");
$text = "[$time] <a href=\"/search.php?nick=$log&amp;go=go\">$log</a> Сломал $it[name]. ";
mysql_query("INSERT INTO `l2warmobi`.`logi` (`id` ,`tip` ,`text`)VALUES (NULL , 'slomal', '$text')");
$msg="Оружие не выдержало очередного заряда магии из свитка!<br/><b>Оружие $it[name] сломано!</b><br/>";
}
//////////////
if($mag['kol']==1){
mysql_query("DELETE FROM `res` WHERE `usr` = '$log' and `id`='$_GET[id]'");
}else{
$mag['kol']--;
mysql_query("UPDATE res SET kol = '$mag[kol]' WHERE `usr` = '$log' and `id`='$_GET[id]'");
}
break;
/////////////////////
case 'scroll_armor':
if(empty($_GET['tip'])){
echo'<b>Заколдовать:</b><br/>';
echo"<a href=\"scroll.php?tip=golova&amp;id=$_GET[id]\">Шлем</a><br/>";
echo"<a href=\"scroll.php?tip=shit&amp;id=$_GET[id]\">Щит</a><br/>";
echo"<a href=\"scroll.php?tip=ruki&amp;id=$_GET[id]\">Рукавицы</a><br/>";
echo"<a href=\"scroll.php?tip=body&amp;id=$_GET[id]\">Доспехи</a><br/>";
echo"<a href=\"scroll.php?tip=nogi&amp;id=$_GET[id]\">Сапоги</a><br/>- - -<br/>";

//echo"<a href=\"scroll.php?tip=plash&amp;id=$_GET[id]\">Плащ</a><br/>";
//echo"<a href=\"scroll.php?tip=poyas&amp;id=$_GET[id]\">Пояс</a><br/>";
}elseif(isset($_GET['tip']) and empty($_GET['what'])){
echo"<p>Выберите что повысить:</p><hr/>";
echo"<div class=inoy><a href=\"scroll.php?tip=$_GET[tip]&amp;what=pdef&amp;id=$_GET[id]\">P.Def</a>";
echo"<a href=\"scroll.php?tip=$_GET[tip]&amp;what=mdef&amp;id=$_GET[id]\">M.Def</a></div>";
}else{
if($_GET['tip']!='golova' and $_GET['tip']!='body' and $_GET['tip']!='nogi' and $_GET['tip']!='shit' and $_GET['tip']!='plash' and $_GET['tip']!='ruki' and $_GET['tip']!='poyas'){
echo'Ошибка, доступ закрыт!';
include($path.'inc/down.php');
exit;
}
if($_GET['what']!='pdef' and $_GET['what']!='mdef'){
echo'Ошибка, доступ закрыт!';
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `image`='yes' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto=='0'){
echo'Ошибка, сначала наденьте вещь!';
include($path.'inc/down.php');
exit;
}
$it = mysql_fetch_array($req);

$nprotect=$mag['give']+$udata[$_GET['what']];

$w=explode("*",$it['name']);
if(empty($w[1])){
$nam="$w[0]*1";
}else{
$kol=$w[1]+1;
$nam="$w[0]*$kol";}

if(empty($w[1])){$stavka=100;}
if($w[1]==1){$stavka=95;}
if($w[1]==2){$stavka=90;}
if($w[1]==3){$stavka=85;}
if($w[1]==4){$stavka=80;}
if($w[1]==5){$stavka=75;}
if($w[1]==6){$stavka=70;}
if($w[1]==7){$stavka=65;}
if($w[1]==8){$stavka=60;}
if($w[1]==9){$stavka=55;}
if($w[1]>=10){$stavka=50;}

$priz=rand(1,100);

if($priz<=$stavka){

$num=$mag['give']+$it[$_GET['what']];

mysql_query("UPDATE `users` SET `$_GET[what]` = '$nprotect' WHERE usr = '$log'");
mysql_query("UPDATE `item` SET
      `$_GET[what]` = '$num',
      `name` = '$nam'
       WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `image`='yes'");
       
$msg="Защита увеличена!<br/>
Новая защита: $num<br/>";
}else{
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `image`='yes'");
$it = mysql_fetch_array($req);

$npatt=$udata['patt']-$it['patt'];
$nmatt=$udata['matt']-$it['matt'];

$npdef=$udata['pdef']-$it['pdef'];
$nmdef=$udata['mdef']-$it['mdef'];

mysql_query("UPDATE `users` SET
         `patt` = '$npatt',
         `matt` = '$nmatt',
         `pdef` = '$npdef',
         `mdef` = '$nmdef'
          WHERE usr = '$log'");

mysql_query("DELETE FROM `item` WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `image`='yes'");

$text = "[$time] <a href=\"/search.php?nick=$log&amp;go=go\">$log</a> Сломал $it[name]. ";
mysql_query("INSERT INTO `l2warmobi`.`logi` (`id` ,`tip` ,`text`)VALUES (NULL , 'slomal', '$text')");

$msg="<p>Вещь не выдержала очередного заряда магии из свитка!<br/><b>Вещь $it[name] сломана!</b></p><br/>";
}
//////////////
if($mag['kol']==1){
mysql_query("DELETE FROM `res` WHERE `usr` = '$log' and `id`='$_GET[id]'");
}else{
$mag['kol']--;
mysql_query("UPDATE res SET kol = '$mag[kol]' WHERE `usr` = '$log' and `id`='$_GET[id]'");
}
///////////////
}
break;
}
///////
echo"$msg";
echo"<br/><div class=silka><a href=\"inventar.php?\">Назад</a></div>";
include($path.'inc/down.php');
?>