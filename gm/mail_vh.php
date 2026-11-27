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

$times = date("H:i");
$display = 10;
$my_page = abs(intval($_GET['p']));
if(!$my_page || $my_page < 1){ 
$my_page = 1; 
}
$start = ($my_page-1)*$display;

$message = mysql_query("SELECT * FROM msg_r ORDER BY id DESC LIMIT $start, $display");
while($msg = mysql_fetch_assoc($message))
{
$text = $msg['mail_msg'];
$text = strip_tags($text);
$from = strip_tags($msg['user_from']);
$to = strip_tags($msg['user_to']);
echo'<div class="slot_menu">';
echo "От <a href=\"/search.php?&amp;nick=$from&go=go\">$from</a>|
Кому <a href=\"/search.php?&amp;nick=$to&go=go\">$to</a>
($msg[time])<br> $text</div>";
}

$all_posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `msg_r`"),0);
;
checkin::display_pagin($my_page, $all_posts, 'mail_vh.php?p='); 



include($path.'inc/down.php');
?>