<?
defined('PROTECTOR') or die('Error: restricted access');

// ответ в чате
$avto_chat = mysql_num_rows(mysql_query("SELECT * FROM `chat_otv` WHERE `usr` = '$log' LIMIT 1"));
if ($avto_chat == 0) {$chat_otvet = '4at.jpg';}else{$chat_otvet = '4at2.JPG';}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


echo '</div></div>';
echo '<div class="lich">
<span style="
border 				:1px double #af9878;
background			: #1f1f1f;
padding-left		: 0px;
padding				:2% 0% 2% 0%;
display :table;
width :100%;
text-align			:center;
">';
$adres = $_SERVER['SCRIPT_NAME'];
if($adres=="/chat.php"){$hwgm = 'height=35 width=35';}
echo '<table style="width:100%" cellspacing="0" cellpadding="0"><tr>';
echo '<td style="vertical-align:top;width:11%;border-right:solid;border-width:1px;color:#af9878"><a href="/?"><img src="/pic/down/menu.png" height=35 width=35 alt="Меню"/></a></td>';
echo '<td style="vertical-align:top;width:11%;border-right:solid;border-width:1px;color:#af9878"><a href="/to_gorod.php?"><img src="/pic/down/c0011.jpg" height=35 width=35 alt="Город"/></a></td>';
echo '<td style="vertical-align:top;width:11%;border-right:solid;border-width:1px;color:#af9878"><a href="/to_world.php?"><img src="/pic/down/okresnosti.jpg" height=35 width=35 alt="Окра"/></a></td>';
echo '<td style="vertical-align:top;width:11%;border-right:solid;border-width:1px;color:#af9878"><a href="/pers.php?"><img src="/pic/down/a3.jpg" height=35 width=35 alt="Перс"/></a></td>';
echo '<td style="vertical-align:top;width:11%;border-right:solid;border-width:1px;color:#af9878"><a href="/inventar.php?"><img src="/pic/down/a1.jpg" height=35 width=35 alt="Шмот"/></a></td>';
if(!empty($udata['clan']) and $in_battle=='0'){echo '<td style="vertical-align:top;width:11%;border-right:solid;border-width:1px;color:#af9878"><a href="/clanroom.php?"><img src="/pic/down/klan.png" height=35 width=35 alt="Клан"/></a></td>';}
echo '<td style="vertical-align:top;width:11%;border-right:solid;border-width:1px;color:#1f1f1f"><a href="/chat.php?"><img src="/pic/down/'.$chat_otvet.'" height=35 width=35 alt="Чат"/></a></td>';
echo '</tr></table>';
echo '</span></div>';
?>
