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
/* Уберу пока, а то народ бесится =)
if($udata['lvl']=='0'){
echo'Писать на форуме можно только с первого уровня!';
include('end.php');exit;
} */

switch($_GET['act']){

###############################################################################
##                         Добавление сообщения                              ##
###############################################################################
case 'msg_add':



$page = intval($_GET['page']);

	$topic = mysql_fetch_assoc(mysql_query("SELECT `id`, `id_cat`, `closed` FROM `forum_topics` WHERE `id` = '$id'"));

if ($udata['lvl']<12){
echo "<b>В форуме можно писать с 12 уровня! </b>";
	nav2('topic.php?id='.$topic['id'].'&amp;page='.$page.'', 'Назад');
include($path.'inc/end.php');
exit;
}



	if(isset($topic['id'])){
		if($topic['closed'] == 0){
			if(!empty($_POST['text'])){
				$text = sec($_POST['text']);
				mysql_query("INSERT INTO `forum_msg` SET `id_cat` = '".$topic['id_cat']."', `id_topic` = '".$topic['id']."', `text` = '$text', `login` = '".$log."', `time` = '".time()."'");
				mysql_query("UPDATE `forum_topics` SET `time` = '".time()."' WHERE `id` = '".$topic['id']."'");
				echo'Ваше сообщение успешно добавлено!';
			}else{
				echo'Вы не заполнили поле!';
			}
		}else{
			echo'Тема закрыта! Добавить сообщение невозможно!';
		}
	}else{
		echo'Выбранная тема не существует!';
	}
	nav2('topic.php?id='.$topic['id'].'&amp;page='.$page.'', 'Назад');

break;

###############################################################################
##                           Форма ответа на сообщение                       ##
###############################################################################
case 'answer':

$page = intval($_GET['page']);


	$msg = mysql_fetch_assoc(mysql_query("SELECT `id`, `id_topic`, `login` FROM `forum_msg` WHERE `id` = '$id'"));

if ($udata['lvl']<12){
echo "<b>В форуме можно писать с 12 уровня! </b>";
	nav2('topic.php?id='.$msg['id_topic'].'&amp;page='.$page.'', 'Назад');
include($path.'inc/end.php');
exit;
}


	if(isset($msg['id'])){
		$topic = mysql_fetch_assoc(mysql_query("SELECT `closed` FROM `forum_topics` WHERE `id` = '".$msg['id_topic']."'"));
		if($topic['closed'] == 0 or $udata['dostup'] > 1){
			echo '<div class="s1">Ответ пользователю '.$msg['login'].'</div><div class="event">';
			echo '<form action="?act=answered&amp;id='.$id.'&amp;page='.$page.'" method="post" name="form">';
			echo '<textarea name="text" rows="4">[b]'.$msg['login'].'[/b], </textarea><br/>';
			echo '<input name="submit" type="submit" value="Ответить" /></form></div>';
		}else{
			echo'Тема закрыта, ответ невозможен!';
		}
	}else{
		echo'Сообщение, на которое вы хотите ответить не существует!';
	}
	nav2('topic.php?id='.$msg['id_topic'].'&amp;page='.$page.'', 'Назад');
break;

###############################################################################
##                           Ответ на сообщение                              ##
###############################################################################
case 'answered':

$page = intval($_GET['page']);


	$msg = mysql_fetch_assoc(mysql_query("SELECT `id`, `id_cat`, `id_topic` FROM `forum_msg` WHERE `id` = '$id'"));

if ($udata['lvl']<12){
echo "<b>В форуме можно писать с 12 уровня! </b>";
nav2('topic.php?id='.$msg['id_topic'].'', 'Назад');
include($path.'inc/end.php');
exit;
}


	if(isset($msg['id'])){
		$topic = mysql_fetch_assoc(mysql_query("SELECT `closed` FROM `forum_topics` WHERE `id` = '".$msg['id_topic']."'"));
		if($topic['closed'] == 0 or $udata['dostup'] > 1){
			if(!empty($_POST['text'])){
				$text = sec($_POST['text']);
				mysql_query("INSERT INTO `forum_msg` SET `id_cat` = '".$msg['id_cat']."', `id_topic` = '".$msg['id_topic']."', `text` = '$text', `login` = '".$log."', `time` = '".time()."'");
				mysql_query("UPDATE `forum_topics` SET `time` = '".time()."' WHERE `id` = '".$msg['id_topic']."'");
				echo'Вы успешно оставили ответ!';
			}else{
				echo'Вы не заполнили поле!';
			}
		}else{
			echo'Тема закрыта, ответ невозможен!';		}
	}else{
		echo'Сообщение, на которое вы хотите ответить не существует!';
	}
nav2('topic.php?id='.$msg['id_topic'].'', 'Назад');
break;

###############################################################################
##                    Форма редактирования сообщения                         ##
###############################################################################
case 'msg_edit':

$id = intval($_GET['id']);

$msg = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum_msg` WHERE `id` = '$id'"));

if(isset($msg['id'])){
	if($msg['login'] == $log or $udata['dostup'] > 1){
		if($msg['time']+900 > time() or $udata['dostup'] > 0){
			echo '<b>Редактирование сообщения</b><div class="event">';
			echo '<form action="?act=msg_edited&amp;id='.$id.'" method="post" name="form">';
			echo 'Сообщение:<br/><textarea name="text" rows="3">'.$msg['text'].'</textarea><br/>';
			echo '<input name="submit" type="submit" value="Редактировать" /></form></div>';
		}else{
			echo'Время в течение которого вы могли изменить сообщение истекло!';
		}
	}else{
		echo'Вы не можете изменить это сообщение!';
	}
}else{
	echo'Сообщение не существует!';
}
nav2('topic.php?id='.$msg['id_topic'].'', 'Назад');
break;

###############################################################################
##                       Редактирование сообщения                            ##
###############################################################################
case 'msg_edited':

$id = intval($_GET['id']);

$msg = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum_msg` WHERE `id` = '$id'"));

if(isset($msg['id'])){
	if($msg['login'] == $log or $udata['dostup'] > 0){
		if($msg['time']+900 > time() or $udata['dostup'] > 1){
$text = htmlspecialchars(addslashes($_POST['text']));
			mysql_query("UPDATE `forum_msg` SET `text` = '$text', `edit_login` = '".$log."', `edit_time` = '".time()."' WHERE `id` = '$id'");
			echo'Сообщение успешно отредактировано!';
		}else{
			echo'Время в течение которого вы могли отредактировать сообщение истекло!';
		}
	}else{
		echo'Вы не можете отредактировать это сообщение!';
	}
}else{
	echo'Сообщение не существует!';
}
break;

###############################################################################
##                           Удаление сообщения                              ##
###############################################################################
case 'msg_del':

$id = intval($_GET['id']);

$msg = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum_msg` WHERE `id` = '$id'"));

if(isset($msg['id'])){
	if($msg['login'] == $user_id or $udata['dostup'] > 1){
		if($msg['time']+900 > time() or $udata['dostup'] > 1){
			mysql_query("DELETE FROM `forum_msg` WHERE `id` = '$id'");
			echo'Сообщение успешно удалено!';
		}else{
			echo'Время в течение которого вы могли удалить сообщение истекло!<br/>';
		}
	}else{
		echo'Вы не можете удалить это сообщение!';
	}
}else{
	echo'Сообщение не существует!';
}
nav2('topic.php?id='.$msg['id_topic'].'', 'Назад');
break;

###############################################################################
##                           Форма создания темы                             ##
###############################################################################
case 'topic_add':

if ($udata['lvl']<40){
echo "<b>Создавать темы можно с 40 уровня! </b>";
	nav2('index.php?act=cat_view&amp;id='.$id.'', 'Назад');
include($path.'inc/end.php');
exit;
}



	echo '<b>Начать новую тему</b><div class="event">';
	echo '<form action="posting.php?act=topic_added&amp;id='.$id.'" method="post" name="form">';
	echo 'Название(max50):<br/><input name="title" type="text" maxlength="50" /><br/>';
	echo 'Сообщение:<br/><textarea name="text" rows="4"></textarea><br/>';
	echo '<input name="submit" type="submit" value="Создать тему" /></form></div>';
	nav2('index.php?act=cat_view&amp;id='.$id.'', 'Назад');
break;

###############################################################################
##                              Cозданиt темы                                ##
###############################################################################
case 'topic_added':

if ($udata['lvl']<40){
echo "<b>Создавать темы можно с 40 уровня! </b>";
 nav2('index.php?act=cat_view&amp;id='.$id.'', 'Назад');
include($path.'inc/end.php');
exit;
}


	if(time() >= $_SESSION['antispam']){
		if(!empty($_POST['title']) && !empty($_POST['text'])){	
				if(strlen($_POST['text']) <= 1699){
$title = htmlspecialchars(addslashes($_POST['title']));
$text = htmlspecialchars(addslashes($_POST['text']));


					mysql_query("INSERT INTO `forum_topics` SET `id_cat` = '$id', `title` = '$title', `authour` = '".$log."', `time` = '".time()."'");
					$last_id = mysql_insert_id();
					mysql_query("INSERT INTO `forum_msg` SET `id_cat` = '$id', `id_topic` = '$last_id', `text` = '$text', `login` = '".$log."', `time` = '".time()."'");
					$_SESSION['antispam'] = time() + 10;
					echo'Тема успешно создана!';
				}else{
					echo'Слишком длинное сообщение! Не больше 1000 символов!';
				}
		}else{
			echo'Вы не заполнили поля!';
		}
	}else{
		echo'Антиспам! Разрешено писать раз в 10 сек.!';
	}
 nav2('index.php?act=cat_view&amp;id='.$id.'', 'Назад');
break;

###############################################################################
##                              Закрытие темы                                ##
###############################################################################
case 'topic_close':

$topic = mysql_fetch_assoc(mysql_query("SELECT `id`, `authour` FROM `forum_topics` WHERE `id` = '$id'"));
if(isset($topic['id'])){
	if($topic['authour'] == $log or $udata['dostup'] > 1){
		$result = mysql_query("UPDATE `forum_topics` SET `closed` = 1 WHERE `id` = '$id'");
		echo'Тема успешно закрыта!';
	}else{
		echo'Вы не можете закрыть эту тему!';
	}
}else{
	echo'Выбранная тема не существует!';
}
nav2('topic.php?id='.$id.'', 'В тему');
break;

###############################################################################
##                              Открытие темы                                ##
###############################################################################
case 'topic_open':

$topic = mysql_fetch_assoc(mysql_query("SELECT `id` FROM `forum_topics` WHERE `id` = '$id'"));
if(isset($topic['id'])){
	if($udata['dostup'] > 1){
		$result = mysql_query("UPDATE `forum_topics` SET `closed` = 0 WHERE `id` = '$id'");
		echo'Тема успешно открыта!';
	}else{
		echo'Вы не можете открыть эту тему!';
	}
}else{
	echo'Выбранная тема не существует!';
}
nav2('topic.php?id='.$id.'', 'В тему');
break;

###############################################################################
##                       Форма редактирования темы                           ##
###############################################################################
case 'topic_edit':

$topic = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum_topics` WHERE `id` = '$id'"));

if(isset($topic['id'])){
	if($topic['authour'] == $log or $udata['dostup'] > 1){
		echo '<b>Редактирование темы</b><div class="event">';
		echo '<form action="?act=topic_edited&amp;id='.$id.'" method="post" name="form">';
		echo 'Название:<br/><input name="title" type="text" maxlength="25" value="'.$topic['title'].'" /><br/>';
		$msg = mysql_fetch_assoc(mysql_query("SELECT `text` FROM `forum_msg` WHERE `id_topic` = '$id' ORDER BY `time` LIMIT 1")) or die(mysql_error());
		echo 'Сообщение:<br/><textarea name="text" rows="3">'.$msg['text'].'</textarea><br/>';
		echo '<input name="submit" type="submit" value="Редактировать" /></form></div>';
	}else{
		echo'Вы не можете изменить эту тему!';
	}
}else{
	echo'Тема не существует!';
}
nav2('topic.php?id='.$id.'', 'Назад');
break;

###############################################################################
##                         Редактирование темы                               ##
###############################################################################
case 'topic_edited':

$topic = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum_topics` WHERE `id` = '$id'"));

if(isset($topic['id'])){
	if($topic['login'] == $log or $udata['dostup'] > 1){
		$title = sec($_POST['title']);
		$text = sec($_POST['text']);
		mysql_query("UPDATE `forum_topics` SET `title` = '$title' WHERE `id` = '$id'");
		mysql_query("UPDATE `forum_msg` SET `text` = '$text', `edit_login` = '".$log."', `edit_time` = '".time()."' WHERE `id_topic` = '$id'  ORDER BY `time` LIMIT 1");
		echo'Тема успешно отредактирована!';
	}else{
		echo'Вы не можете отредактировать эту тему!';
	}
}else{
	echo'Тема не существует!';
}
nav2('topic.php?id='.$id.'', 'Назад');
break;

###############################################################################
##                              Удаление темы                                ##
###############################################################################
case 'topic_del':

$topic = mysql_fetch_assoc(mysql_query("SELECT `id`, `id_cat` FROM `forum_topics` WHERE `id` = '$id'"));

if(isset($topic['id'])){
	if($udata['dostup'] > 1){
		$result = mysql_query("DELETE FROM `forum_topics` WHERE `id` = '$id'");
		$result2 = mysql_query("DELETE FROM `forum_msg` WHERE `id_topic` = '$id'");
		echo'Тема успешно удалена!';
	}else{
		echo'Нет доступа! Вам делать здесь нечего =)';
	}
}else{
	echo'Темы не существует!';
}
nav2('index.php?act=cat_view&amp;id='.$topic['id_cat'].'', 'Назад');
break;
}

echo'</div></div>';
include('end.php');
?>