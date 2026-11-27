<?php
define('PROTECTOR', 1);

$headmod = 'lambard';//фикс. места

$textl='Ломбард';
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


if(!empty($_GET['id'])){

$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image`='not' and `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==1){
$item = mysql_fetch_array($req);

$cena=round($item['cena']/4);
$udata['money']=$udata['money']+$cena;

mysql_query("UPDATE `users` SET `money` = '$udata[money]' WHERE `usr` = '$log'");

mysql_query("DELETE FROM `item` WHERE usr='$log' and `image`='not' and `id`='$_GET[id]'");
        
echo"<div class=msg><p><span style=color:#FBB511;>Вещь <b>$item[name]</b> продана за <u>".number_format($cena, 0, ',', "`")."</u> Аден!</p></span></div>";
}}
//---------


//---выщитует страницы----------

	$count = mysql_result(mysql_query("SELECT COUNT(*) FROM `item` WHERE `usr` = '$log' and `image`='not'"), 0);
	if($count > 0){
		$pages = ceil($count/10);
		if(isset($_GET['page'])){
			$page = abs(intval($_GET['page']));
		}else{
			$page = 1;
		}
		$from = ($page-1)*10;
}
//-------------------------------



$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `image`='not' LIMIT $from, 10");
$avto=mysql_num_rows($req);
if($avto>=1){

echo "<p><span style=color:#1B7551;><b>Вещи, которые можно продать:</b></span>
<br/><span style=color:#636261;>&nbsp;(клацнув на вещь вы её продадите)</span></p>";

While($mag = mysql_fetch_array($req))
{
$cena=round($mag['cena']/4);
$u=explode('*',$mag['name']);
echo "<div class=inoy><a href=\"/lambard.php?id=$mag[id]&amp;page=$page\"><img src=\"shmot/$u[0].png\"  align='left' width='32' height='32' alt='' style='margin-right:10px;border:1px solid #383838'/>
 $mag[name] <br/><span style=color:#C2B6B2;>&nbsp;&nbsp;Продать за: ".number_format($cena, 0, ',', "`")." Аден</span></a></div>";

}
}else{
echo"Нет вещей<br/>";
}
// показ страниц
echo "<div class=msg><p>Стр: ";
	navig2($page, 'lambard.php?', $pages);
echo "</p></div>";
//---------------



echo"<div class=silka><a href=\"/gorod.php?pl=shop_zone\">В город</a></div>";
break;
}
include($path.'inc/down.php');
?>