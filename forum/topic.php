<?php 
define('PROTECTOR', 1);


$path = '../'; //путь
$headmod = 'forum_in';//фикс. места

$textl = 'Форум';
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path . 'inc/core.php');
include('head.php');

echo'<div class="forum">';

$id = intval($_GET['id']);

$topic = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum_topics` WHERE `id` = '$id'"));
if(isset($topic['id'])){
	echo '<b>'.$topic['title'].'</b><br/>';
	$count = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_msg` WHERE `id_topic` = '$id'"), 0);
	if($count > 0){
		$pages = ceil($count/10);
		if(isset($_GET['page'])){
			$page = abs(intval($_GET['page']));
		}else{
			$page = 1;
		}
		$from = ($page-1)*10;

//start
function smiles($msg){
$dir = opendir ("../pic/smiles"); 
while ($file = lin2_readdir ($dir)) {
if (preg_match('/\.gif$/', $file)){
$file2=str_replace(".gif","",$file);
$msg=str_replace(":$file2",'<img src="../pic/smiles/'.$file.'" alt="" height="30" width="30">',$msg);
}}
lin2_closedir ($dir);
return $msg;  }


//end
//bb
function AddBB($var) {
$search = array(
'/\[b\](.*?)\[\/b\]/is',
'/\[i\](.*?)\[\/i\]/is',
'/\[adm\](.*?)\[\/adm\]/is',
'/\[u\](.*?)\[\/u\]/is',
'/\[url\](.*?)\[\/url\]/is',
'/\[url\=(.*?)\](.*?)\[\/url\]/is'
);

$replace = array(
'<strong>$1</strong>',
'<em>$1</em>',
'<font color="red">$1</font>',
'<u>$1</u>',
'<a href="$1">$1</a>',
'<a href="$1">$2</a>'
);

$var = preg_replace ($search, $replace, $var);
return $var;
}
//
		$result = mysql_query("SELECT * FROM `forum_msg` WHERE `id_topic` = '$id' ORDER BY `time` LIMIT $from, 10");
		while($msg = mysql_fetch_assoc($result)){
			if(!isset($num)) $num = 1;  
			$num++;
////////////////////////////
$laikas = 150;
$dabar = time();
$timeout = $dabar - $laikas;
$asd = mysql_num_rows(mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeout' AND usr='$msg[login]'"));

$reqs = mysql_query("SELECT * FROM `users` WHERE `usr` = '$msg[login]' Limit 1");
$ud = mysql_fetch_array($reqs);
if ($ud['dostup']==5)
{
$color1='<font color=lime>';
$color2='<font color=#ffffcc>';
}
if ($ud['dostup']==4)
{
$color1='<font color=lime>';
$color2='<font color=#ffffcc>';
}
if ($ud['dostup']==2 or $ud['dostup']==3)
{
$color1='<font color=#547ef4>';
$color2='<font color=#ffffcc>';
}
if ($ud['dostup']<2)
{
$color1='';
$color2='<font color=#ffffcc>';
}



if($asd == 0){
$status = "<font color='#ff4040'>Off</font>"; }
else
{
$status = "<font color='#6fcd72'>On</font>"; }

echo $status;
			echo ' <a href="../search.php?nick='.$msg['login'].'&amp;go=go">'.$color1.''.$msg['login'].'</font></a></b> 
			<font color=#af9878>'.date('d.m.Y / H:i', $msg['time']).'</font>';
			
						echo ' <font color=#f4d06e>[ <a href="posting.php?act=answer&amp;id='.$msg['id'].'&amp;page='.$pages.'">отв</a> ] </font>';

			if($msg['login'] == $log or $udata['dostup'] > 1){
				if($msg['time']+900 > time() or $udata['dostup'] > 1){
					echo '<font color=#f4d06e>[ <a href="posting.php?act=msg_edit&amp;id='.$msg['id'].'">ред</a> ] </font>';
echo '<font color=#f4d06e>[ <a href="posting.php?act=msg_del&amp;id='.$msg['id'].'">удал</a> ]</font>';
				}
			}
			
			echo '<br/>'.$color2.''.nl2br(smiles(AddBB(htmlspecialchars(addslashes($msg['text']))))).'</font><br/>';
			
			if(!empty($msg['edit_login']) && !empty($msg['edit_time'])){
			

			
				echo '_____________<br/><font color="#999999">посл. ред.: '.date('d.m.y / H:i', $msg['edit_time']).'</font></i><br/>';
			}
			echo "<hr>";
		}
	}
	$count = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_msg` WHERE `id_topic` = '$id'"), 0);
	
	if($topic['authour'] == $log or $udata['dostup'] > 1){
		if($topic['closed'] == 0){
			echo '<div><a href="posting.php?act=topic_close&amp;id='.$id.'">Закр</a><font color=#ffffcc>|</font>';
		}
	}
	if($udata['dostup'] > 1){
		if($topic['closed'] == 1){
			echo '<div><a href="posting.php?act=topic_open&amp;id='.$id.'">Откр</a><font color=#ffffcc>|</font>';
		}
	}
	if($topic['authour'] == $log or $udata['dostup'] > 1){
		if($topic['closed'] == 0 or $udata['dostup'] > 1){
			echo '<a href="posting.php?act=topic_edit&amp;id='.$id.'">Ред</a>';
		}
	}
	if($udata['dostup'] > 1){
		echo '<font color=#ffffcc>|</font><a href="posting.php?act=topic_del&amp;id='.$id.'">Удал</a>';
	}
	if($topic['authour'] == $log or $udata['dostup'] > 1){
		echo '</div><font color=#ffffcc>';
	}
	navig($page, 'topic.php?id='.$id.'&amp;', $pages);
echo '</font>';
	if($topic['closed'] == 0){
			echo '<font color=#ffffcc>Добавить сообщение:</font><br/>';
			echo '<form action="posting.php?act=msg_add&amp;id='.$topic['id'].'&amp;page='.$page.'" method="post" name="form">';
			echo '<textarea name="text" rows="3"></textarea><br/>';
			echo '<div align="left"><input class="button_medium3" class="button" type="submit" value=""/></form>';
	}else{
		echo 'Вы не можете добавить сообщение, т.к. тема закрыта!<br/><br/>';
	}
}else{
	echo'Выбранной темы не существует!';
}
	
include('end.php');
?>