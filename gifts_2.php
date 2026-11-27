<?php

define('PROTECTOR', 1);

$headmod = 'Подарки';//фикс. места

$textl='Подарки';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');



//include_once 'sys/inc/compress.php';

if (empty($_GET['id'])){$_GET['id']=$udata['id'];}



$ank=mysql_fetch_array(mysql_query("SELECT * FROM `users` WHERE `id` = '$_GET[id]' LIMIT 1"));
if(empty($ank['usr'])){
echo'<font color=red>Нет такого игрока!</font>';
echo"<br/><div class=silka><a href=\"/?\">Главная</a>";
include($path.'inc/down.php');exit;
}


$k_post = mysql_result(mysql_query("SELECT COUNT(*) FROM `gifts` WHERE `id_user` = '$ank[id]' LIMIT 1"), 0);

if ($k_post==0)
{
echo '<div class=msg>У игорка '.$ank['usr'].' нет подарков</div>';
}



   if($_GET['page']<=0){
		$page = 1;
		}else{
		$page = intval($_GET['page']);
		}
		$from = ($page-1)*10;
		
		        $avto = mysql_num_rows(mysql_query("SELECT * FROM `gifts` WHERE id_user = '$ank[id]'"));
            $pages1=$avto/10;
            $pages=round($avto/10);
            if($pages1>$pages){
            $pages=$pages+1;
            }



echo "<div class=dot>Подарки игрока $ank[usr]</div><hr/>";

$q = mysql_query("SELECT * FROM `gifts` WHERE `id_user` = '$ank[id]' ORDER BY time DESC LIMIT $from,10");
while ($f = mysql_fetch_array($q))
{
$a = mysql_fetch_array(mysql_query("SELECT * FROM `users` WHERE `id` = '$f[ot_id]' LIMIT 1"));
if($num==1){ 
echo "<div class='dot'>"; 
$num=0;
}else{ 
echo "<div class='dot'>"; 
$num=1;}

echo"<img src='/gifts/".$f['id_gifts'].".gif' alt='' class='icon'/><br>";
echo "<div class=msg>От: <a href='search.php?nick=$a[usr]&go=go'>$a[usr]</a> ";
echo " <small><font color=grey>(".$f['time'].") </font></small></div> ";
echo "<div class=msg>".$f['text']." </div> ";
echo "</div>";

}


echo "</div><hr><p>Страниц: "; navig2($page, '?id='.$ank['id'].'&', $pages);

include($path.'inc/down.php');
?>