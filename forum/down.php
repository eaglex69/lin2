<?
defined('PROTECTOR') or die('Error: restricted access');

echo'</div><div class="foot">';
if ($in_battle=='1'){
echo"<img src='pic/main/sword.png' alt='*'/> <a href=\"battle.php?\">Вернуться в бой</a><br/>";
}
echo"<img src='pic/main/player.png' alt='*'/> <a href=\"pers.php?\">Персонаж</a><br/>";
echo"<img src='pic/main/bag.png' alt='*'/> <a href=\"inventar.php?\">Инвентарь</a><br/>";
$req = mysql_query("SELECT id FROM `pit` WHERE `usr` = '$log' and `status`='on'");
$avto=mysql_num_rows($req);
if($avto==1){
echo"<img src='pic/main/star.png' alt='*'/> <a href=\"pit.php?mod=mypit\">Мой зверь</a><br/>";
}
$req = mysql_query("SELECT * FROM `asasins_active` WHERE `usr` = '$log' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto=='1'){
echo"<img src='pic/main/sword.png' alt='*'/> <a href=\"assasin.php?\">Ассасин</a><br/>";
}
if($mestouser['city']=='0'){
if ($inryd=='1'){
echo"<img src='pic/main/gold.png' alt='*'/> <a href=\"rydnik.php?\">Рудник</a><br/>";
}else{
echo"<img src='pic/main/home.png' alt='*'/> <a href=\"gorod.php?\">Город</a><br/>";
echo"<img src='pic/main/sword.png' alt='*'/> <a href=\"to_world.php?\">Мир</a><br/>";
}
}elseif($mestouser['city']=='1'){
echo"<img src='pic/main/sword.png' alt='*'/> <a href=\"okrestnosti.php?\">Мир</a><br/>";
echo"<img src='pic/main/sword.png' alt='*'/> <a href=\"zasada.php?\">Засады</a><br/>";
echo"<img src='pic/main/star.png' alt='*'/> <a href=\"maps.php\">Карты</a><br/>";
}elseif($mestouser['city']=='2'){
echo"<img src='pic/main/sword.png' alt='*'/> <a href=\"zamok.php?\">Замок</a><br/>";
echo"<img src='pic/main/home.png' alt='*'/> <a href=\"to_gorod.php?\">Город</a><br/>";
}elseif($mestouser['city']=='3'){
echo"<img src='pic/main/sword.png' alt='*'/> <a href=\"towers.php?\">Башни</a><br/>";
echo"<img src='pic/main/home.png' alt='*'/> <a href=\"to_gorod.php?\">Город</a><br/>";
}
if(!empty($udata['clan']) and $in_battle=='0'){
echo"<img src='pic/main/flags.png' alt='*'/> <a href=\"clanroom.php?\">Комната клана</a><br/>";
}

echo"<img src='pic/main/home.gif' alt='*'/> <a href='main.php?'>Моё меню</a><br/>";

echo"<img src='pic/main/general.png' alt='*'/> <a href='index.php?'>Главная</a><br/>";
echo'</div>';

include($path.'inc/nogi.php');
include($path.'inc/end.php');
?>
