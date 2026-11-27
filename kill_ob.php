<?
define('PROTECTOR', 1);

$headmod = 'kill_ob';//фикс. места

$textl='Обменник Килов';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


switch($_GET[mod]){


default:
$res = mysql_query("SELECT * FROM `kill_mob` WHERE `id` = '$udata[id]' and `usr` = '$log'"); //проверка есть ли килы, если да то открываем таблицу
$av = mysql_num_rows($res);
$kill = mysql_fetch_array($res);
$av = $kill[skoko];
if (empty($av)){$av = 0;}
if ($av==0){
// если нет kill то пишем что пусто

echo "<p><font color=grey><b>У Вас $av убийств монстров. Набить Вы их можете в любой окрестности.</b></font></p>";

echo "<p><font color=green><b>Курс обмена: <br/> ** 1 Coin of Luck = 25 убийств</b></font></p>";

echo "<div class=silka><a href=\"pers.php\">Персонаж</a></div>";
}else{
// иначе пишем сколько и выщитуем


echo "<p><font color=grey><b>У Вас $av убийств монстров!</b></font></p>";

echo "<p><font color=green><b>Курс обмена: <br/> ** 1 Coin of Luck = 25 убийств</b></font></p>";


$col = $av/25;
$col = round($col);
$col = $col-1;

if($col<0){$col=0;}

echo "<form action=\"?mod=yes\" method=\"POST\">";

echo "<div class=menu><b><font color=grey>Max k обмену:<br/>
+ </b></font><input type=\"text\" name=\"col\" size=\"3\" value=\"$col\" maxlength=\"9\"/> ";

echo "<input type='submit' name='ok' value='CoL' /><hr/></div>\n";


echo "<div class=silka><a href=\"pers.php\">Персонаж</a></div>";

}

break;


case "yes";
// пишем защиты
$_POST['col'] = round($_POST['col']);

if ($_POST['col']<=0 or is_numeric($_POST['col'])==FALSE){
echo "<p><font color=red><b>Ошибка! Вводите цыфры больше ноля ! </b></font></p>";
echo "<div class=silka><a href=\"kill_ob.php\">Назад</a></div>";
include($path.'inc/down.php');EXIT;
}

$res = mysql_query("SELECT * FROM `kill_mob` WHERE `id` = '$udata[id]' and `usr` = '$log'"); //проверка есть ли килы, если да то открываем таблицу
$av=mysql_num_rows($res);
$kill = mysql_fetch_array($res);
$av = $kill[skoko];

$col = $_POST[col];
$fad = $_POST['col'] * 25;
$nav = $av - $fad;

if ($nav < 0){$navs = round($nav); $u=explode('-',$navs); 
echo "<p><font color=orange><b> Вам нехватает $u[1] убийств для этого обмена!!! </b></font></p>";
echo "<div class=silka><a href=\"kill_ob.php\">Назад</a></div>";
include($path.'inc/down.php');EXIT;
}




/// пишем результат
if($nav<=0){ // если равен нулю или меньше удаляем таблицу
mysql_query("DELETE FROM `kill_mob` WHERE `usr`='$log' and `id`='$udata[id]' LIMIT 1");//чистим логи
}else{ // иначе переписывем с новым количеством
mysql_query("UPDATE `kill_mob` SET `skoko` = '$nav' WHERE `usr`='$log' and `id`='$udata[id]' LIMIT 1");
}


$reqi = mysql_query("SELECT * FROM `users` WHERE `usr`='$log' LIMIT 1");
$udata = mysql_fetch_array($reqi);

$almaz = $udata[almaz] + $col; // щитаем колы

mysql_query("UPDATE `users` SET `almaz` = '$almaz' WHERE `usr`='$log' LIMIT 1");


echo "<p><font color=grey><b>Обмен успешно выполнен:</b><br/></p>";
echo " У вас было убийств - $av <br/>";
echo " Списали - $fad <br/>";
echo " Осталось - $nav<br/>";
echo " Зачисленно Coin of Luck - $col шт. - $almaz<br/></font>";


echo "<div class=silka><a href=\"kill_ob.php\">Назад</a></div>";
echo "<div class=silka><a href=\"pers.php\">Персонаж</a></div>";

break;


}
include($path.'inc/down.php');
?>