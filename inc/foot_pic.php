<?php
defined('PROTECTOR') or die('Error: restricted access');

// ответ в чате
$avto_chat = mysql_num_rows(mysql_query("SELECT * FROM `chat_otv` WHERE `usr` = '$log' LIMIT 1"));
if ($avto_chat == 0) {$chat_otvet = '4at.jpg';}else{$chat_otvet = '4at2.JPG';}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


echo '</div></div>';
// нижнее меню: круглые медальоны с золотым кольцом (рисунки: theme/fantasy/g-*.svg, не зависят от pic/down/*);
// 4-й элемент = страницы, на которых вкладка подсвечена; красная точка на «Чат» = вам ответили
$lx_here = isset($_SERVER['SCRIPT_NAME']) ? basename((string)$_SERVER['SCRIPT_NAME']) : '';
echo '<div class="lx-nav">';
$lx_tabs = array(
    array('/?', 'g-menu', 'Меню', array('index.php', 'main.php')),
    array('/to_gorod.php?', 'g-city', 'Город', array('gorod.php', 'to_gorod.php')),
    array('/to_world.php?', 'g-world', 'Окра', array('okrestnosti.php', 'ok.php', 'to_world.php')),
    array('/pers.php?', 'g-hero', 'Перс', array('pers.php')),
    array('/inventar.php?', 'g-backpack', 'Шмот', array('inventar.php', 'dress.php')),
);
if(!empty($udata['clan']) and $in_battle=='0'){$lx_tabs[] = array('/clanroom.php?', 'g-clan', 'Клан', array('clanroom.php'));}
$lx_tabs[] = array('/chat.php?', 'g-chat', 'Чат', array('chat.php'));
foreach($lx_tabs as $lx_t){
echo '<a href="'.$lx_t[0].'"'.(in_array($lx_here, $lx_t[3]) ? ' class="on"' : '').'><span class="m '.$lx_t[1].'">'
    .(($lx_t[2] == 'Чат' and $avto_chat != 0) ? '<i class="bd"></i>' : '').'</span><span>'.$lx_t[2].'</span></a>';
}
echo '</div>';
?>
