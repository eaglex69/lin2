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
echo '<div class=forum>';
switch($_GET['act']){

###############################################################################
##                   Главная страница, список разделов                       ##
###############################################################################
default:

echo '<b>Разделы</b><br/>';

$result = mysql_query("SELECT * FROM `forum_cats` WHERE `id_cat` = 0");

while($cat = mysql_fetch_assoc($result)){
	$topics = mysql_result(mysql_query("SELECT COUNT(`id`) FROM `forum_topics` WHERE `id_cat` = '".$cat['id']."'"), 0);
	$msg = mysql_result(mysql_query("SELECT COUNT(`id`) FROM `forum_msg` WHERE `id_cat` = '".$cat['id']."'"), 0);
	echo '<hr/><a href="index.php?act=cat_view&amp;id='.$cat['id'].'">'.$cat['name'].'</a> ('.$topics.'/'.$msg.')<br/>';
	
	//echo '<img src="pic/main/truba.gif" alt="*"/> '.$cat['opis'].'<br/>'; - описание раздела
}

break;

###############################################################################
##                            Просмотр раздела                               ##
###############################################################################
case 'cat_view':

$id = intval($_GET['id']);

$row = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum_cats` WHERE `id` = '$id'"));
if(isset($row['id'])){
	echo '<b>'.$row['name'].'</b><br/>';
	$count_subcats = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_cats` WHERE `id_cat` = '$id'"), 0);
	if($count_subcats > 0){
		$result_subcats = mysql_query("SELECT * FROM `forum_cats` WHERE `id_cat` = '$id'");
		while($subcat = mysql_fetch_assoc($result_subcats)){
			$topics = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_topics` WHERE `id_cat` = ".$subcat['id'].""), 0);
			$msg = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_msg` WHERE `id_cat` = ".$subcat['id'].""), 0);
			echo '<a href="?act=cat_view&amp;id='.$subcat['id'].'">'.$subcat['name'].'</a> ('.$topics.'/'.$msg.')<br/>';
			
		}
		echo '</div>';
	}else{
		$count_topics = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_topics` WHERE `id_cat` = '$id'"), 0);
		if($count_topics > 0){
			$pages = ceil($count_topics/10);
			if(isset($_REQUEST['page'])){
				$page = num($_REQUEST['page']);
			}else{
				$page = 1;
			}
			$from = ($page-1)*10;

			$result_topics = mysql_query("SELECT * FROM `forum_topics` WHERE `id_cat` = '$id' ORDER BY `time` DESC LIMIT $from, 10");
			while($topic = mysql_fetch_assoc($result_topics)){
				$msg = mysql_result(mysql_query("SELECT COUNT(`id`) FROM `forum_msg` WHERE `id_topic` = '".$topic['id']."'"), 0);
				$last_user = mysql_fetch_assoc(mysql_query("SELECT `login` FROM `forum_msg` WHERE `id_topic` = '".$topic['id']."' ORDER BY `time` DESC LIMIT 1"));
				$last_page = ceil($msg/10);

				echo '<a href="topic.php?id='.$topic['id'].'"><b>'.$topic['title'].'</b></a> ['.$msg.']  <a href="../search.php?nick='.$last_user['login'].'&amp;go=go">'.$last_user['login'].'</a>  <a href="topic.php?id='.$topic['id'].'&amp;page='.$last_page.'">»</a><hr/>';

			}
		}else{
			echo 'В этом разделе пока нет сообщений<br/>Щёлкните <b>Новая тема</b>, и ваше сообщение станет первым!<br/>';
		}

		echo '<br/><a href="posting.php?act=topic_add&amp;id='.$id.'" class="button">Новая тема</a>';
	}
}else{
	echo'Такого раздела не существует!';
}

break;
}
echo'</div></div>';
include('end.php');
?>