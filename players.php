<?php

/**
 * @author FROSTY
 * @copyright 2014
 */

define('PROTECTOR', 1);
$textl='Аккаунты и персонажи';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
if(!$uid){
echo '<div class="error">Доступ не авторизованным ЗАПРЕЩЕН</div>';
include($path.'inc/end.php');
exit;
}
if($udata['dostup']<1){
echo'Доступ закрыт!';
mysql_query("INSERT INTO `block` (`id`, `usr`, `log`, `ban_time`, `text`, `cena`) VALUES (NULL, '$log', 'DiNo', '4985139097', 'Попытка входав админпанель', '')");
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = 'DiNo', `time` = '$times | $date', `read` = 1, `mail_msg` = 'Пытался зайти в адм панель $udata[usr]' ");
include($path.'inc/down.php');
exit;
}



$case=htmlspecialchars(trim($_GET['a']));

switch($case){

default:

$accounts = mysql_result(mysql_query("SELECT COUNT(*) FROM `account`"),0);
echo '<div class="title">Аккаунты</div>';

/*Поиск /НАЧАЛО/ * /
echo '<form action="players.php?a=search" method="post">';
echo '<br />';
echo 'Логин персонажа/аккаунта:<br />
<input class="input" type="text" size="10" name="name" maxlength="15" />';
echo '<input class="button" type="submit" value="Поиск" />';
echo '</form>';
/*Поиск /КОНЕЦ/ */

//Постраничка - обрабочтик
    //Это типа моя постраничка... А то я функции не нашел
    $my_page = abs(intval($_GET['p']));
    $display = 10;
    //Что бы не было пустоты если юзер ввел другое значение в качестве страницы
    $last_page = ceil($accounts/$display); //По 10 аккаунтов на страницу
    
        if(!$my_page || $my_page < 1 || $my_page > $last_page){ 
        $my_page = 1; 
    }
    
    $start = ($my_page-1)*$display;
//Обработчик постранички - КОНЕЦ

$players = mysql_query("SELECT * FROM `account` LIMIT $start,$display");
    while($player = mysql_fetch_assoc($players)){
        
        $perses = mysql_query("SELECT * FROM `users` WHERE `account` = '$player[id]'");
 
 echo '<a href="players.php?a=account&amp;id='.$player['id'].'"><div class="slot_menu">'.$player['nick'].' ['.mysql_num_rows($perses).']</div></a>';
       
    };

    //Постраничка - ВЫВОД
    echo '<div class="navigation">';
    if($my_page > 1){
        echo '<a href="players.php?p='.($my_page-1).'">Назад</a>';
    }
    if($my_page < $last_page){
        echo '<a href="players.php?p='.($my_page+1).'">Вперед</a>';
    }
    echo '</div>';
    //*************************
    // НАДО БУДЕТ ФУНКЦИЮ ПОСТРАНИЧКИ НАПИСАТЬ
    //*************************
    
    break;
    
    case 'account':
    $acc_id = abs(intval($_GET['id']));
    $perses = mysql_query("SELECT * FROM `users` WHERE `account` = '$acc_id'");
    echo '<div class="title">Персонажи</div>';
    
    if(!mysql_num_rows($perses)){
        echo '<div class="error">Ошибка! К этому аккаунту не было создано еще ни одного персонажа!</div>';
        break;
    }
    
    while($pers_data = mysql_fetch_assoc($perses)){
        echo '<div class="slot_menu"><a href="/search.php?nick='.$pers_data['usr'].'&go=go">'.$pers_data['usr'].'</a> ['.$pers_data['lvl'].']</div>';
    }
    echo '<div class="navigation"><a href="players.php">Аккаунты</a></div>';
    break;
    
}


include($path.'inc/end.php');

?>