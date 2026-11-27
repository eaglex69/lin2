<?php
define('PROTECTOR', 1);

$headmod = 'zona';//фикс. места

$textl='Тюрма';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
switch($_GET['mod']){

default:
echo" <center><img src='/pic/roz/zona.png'   alt='' style='margin-right:10px;border:1px solid #383838' ></div><hr/>";
$time=time();
$req = mysql_query("SELECT * FROM `ban` WHERE `ban_time`>'$time'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){ echo "Список забаненых игроков:<hr/>";
While($mag = mysql_fetch_array($req))
{
echo" <a href=\"search.php?nick=$mag[usr]&amp;go=go\">$mag[usr]</a></a> <a href=\"zona.php?mod=1&amp;id=$mag[id]\">[i]</a><hr/>";
}
}else{
echo"<b>Нет забаненых!</b><br/>";
}
echo"<br/><a href=\"main.php?\">Назад</a>";
break;

case '1':
if(empty($_GET['id'])){
echo"Нет такого узника!";
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `ban` WHERE `id` = '$_GET[id]' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($req);
if ($avto == 1) {
    $ban = mysql_fetch_array($req);
    
    if($ban['ban_time']<$t){
    mysql_query("DELETE FROM `ban` WHERE `usr` = '$log'");
    }else{
    echo"$ban[usr] находится в тюрме! Причина: $ban[text]! Осталось: ";
    
$ban['ban_time']=$ban['ban_time']-time();
if($ban['ban_time']<60){
echo "$ban[ban_time] сек.<br/>";
}elseif($ban['ban_time']>60 and $ban['ban_time']<3600){
$ban['ban_time']=round($ban['ban_time']/60);
echo "$ban[ban_time] мин.<br/>";
}else{
$ban['ban_time']=round($ban['ban_time']/3600);
echo "$ban[ban_time] часов<br/>";
}
    }
    }else{
    echo"Нет такого узника!";
    }
    echo"<a href=\"zona.php?\">Назад</a>";
break;
}
include($path.'inc/down.php');
?>