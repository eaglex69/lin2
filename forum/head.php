<?php



include($path.'inc/head.php');
include($path.'inc/zag.php');




// если игрок забанен
$req = mysql_query("SELECT * FROM `ban` WHERE `usr` = '$log' LIMIT 1");
// //////////////////////////
$avto = mysql_num_rows($req);
if ($avto == 1) {
    $ban = mysql_fetch_array($req);    
    if($ban[ban_time]<$t){
    mysql_query("DELETE FROM `ban` WHERE `usr` = '$log'");
    }else{
echo'<div class="forum"><center>';
    echo"<b><font color=red>Ваш персонаж был забанен!</font></b><br/> <font color=lime><b>Причина:</font> <font color=green>$ban[text]!</font> <br/>Осталось: ";
$ban[ban_time]=$ban[ban_time]-time();
if($ban[ban_time]<60){
echo "$ban[ban_time] сек.";
}elseif($ban[ban_time]>60 and $ban[ban_time]<3600){
$ban[ban_time]=round($ban[ban_time]/60);
echo "$ban[ban_time] мин.";
}else{$ban[ban_time]=round($ban[ban_time]/3600);
echo "$ban[ban_time] часов";}
echo "</b>"; 

echo "<br/><br/>Вам закрыт доступ в Почту, Чат и Форум.<br/><br/>";

echo"<a href=\"../index.php?\"><b>Главная</b></a><br/><br/>";

 include($path.'inc/end.php');exit;}}
////////////////////////////////////////////



function nav2($link, $link_name){
	echo '<br/><a href="'.$link.'">'.$link_name.'</a>';
}
function sec($sec){
	$sec = htmlspecialchars(mysql_real_escape_string($sec));
	return $sec;
}
function navig($page, $link, $pages){
	if($pages > 1){
		echo 'cтраницы:<br/>';
		for($i = 1; $i <= $pages; $i++){
			if($i != $page){
				echo '<a href="'.$link.'page='.$i.'">'.$i.'</a> ';
			}else{
				echo '<u>'.$i.'</u> '; // выводим активую страницу текстом
			}
		}
		echo '<br/>';
	}else{
		echo 'cтраницы:<br/><u>1</u><br/>';
	}	
}
?>