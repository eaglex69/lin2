<?php
define('PROTECTOR', 1);
$headmod = 'add_blog';//фикс. места
$textl='Добавление блога';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
if($udata['lvl']<101){
echo'Запрещено давать обьявление или писать блог,не достигнув 101 уровня!';
include($path.'inc/down.php');
exit;
}
if(empty($_POST['name'])){
echo '<form action="adm_panel.php?mod=teh_news" method="post">';
echo"Название:<br/>
<input class='input' type=\"text\" size=\"20\" name=\"name\"/><br/>";
echo'Текст:<br/>
<textarea name="text" cols="30" rows="10"></textarea><br/>';
echo '<input class="button" type="submit" value="Добавить" /></form>';
}else{
mysql_query("INSERT INTO `blog` SET
        `title` = '$_POST[name]',
        `text` = '$_POST[text]',
        `nick` = '$log',`time`='".date("d.m.y")."'");
echo'Заголовок: <b>'.$_POST['name'].'!</b><br/>';
echo'Текст: <b>'.$_POST['text'].'!</b><br/>';
echo"<a href=\"blog.php?\">Назад</a>";
}
include($path.'inc/down.php');
?>