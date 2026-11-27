<?php
defined('PROTECTOR') or die('Error: restricted access');

// ответ в чате
$avto_chat = mysql_num_rows(mysql_query("SELECT * FROM `chat_otv` WHERE `usr` = '$log' LIMIT 1"));
if ($avto_chat == 0) {$chat_otvet = '4at.jpg';}else{$chat_otvet = '4at2.JPG';}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


echo '</div></div>';
echo '<div class="lx-nav">';
$lx_tabs = array(
    array('/?', '/pic/down/menu.png', 'Меню'),
    array('/to_gorod.php?', '/pic/down/c0011.jpg', 'Город'),
    array('/to_world.php?', '/pic/down/okresnosti.jpg', 'Окра'),
    array('/pers.php?', '/pic/down/a3.jpg', 'Перс'),
    array('/inventar.php?', '/pic/down/a1.jpg', 'Шмот'),
);
if(!empty($udata['clan']) and $in_battle=='0'){$lx_tabs[] = array('/clanroom.php?', '/pic/down/klan.png', 'Клан');}
$lx_tabs[] = array('/chat.php?', '/pic/down/'.$chat_otvet, 'Чат');
foreach($lx_tabs as $lx_t){
echo '<a href="'.$lx_t[0].'"><img src="'.$lx_t[1].'" alt=""/><span>'.$lx_t[2].'</span></a>';
}
echo '</div>';
?>
