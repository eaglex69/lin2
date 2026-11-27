<?
define('PROTECTOR', 1);

$headmod = 'obm_fs_ad';//фикс. места

$textl='Обменник Фестиваль Адены';
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
$res = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' && `lat_name` = 'FestivalAdena'"); //проверка есть ли адена если да то открываем таблицу
$av = mysql_num_rows($res);
$fa = mysql_fetch_array($res);
$av = $fa[kol];
if (empty($av)){$av = 0;}
if ($av==0){
// если нет фестиваль аден то пишем что пусто

echo "<p><font color=#f4d06e>У Вас <font color=#ffffcc>$av <font color=#f4d06e>Festival Aden. Набить Вы их можете в Фарм Зоне.</b></font></p>";

echo "<p><font color=#f4d06e>Курс обмена: <br/> 10 Coin of Luck = 200 Festival Aden</b></font></p>";

echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";
}else{
// иначе пишем сколько и выщитуем


echo "<p><font color=#f4d06e>У Вас <font color=#ffffcc>$av <font color=#f4d06e>Festival Aden. Набить Вы их можете в Фарм Зоне.</b></font></p>";

echo "<p><font color=#f4d06e>Курс обмена: <br/> 10 Coin of Luck = 200 Festival Aden</b></font></p>";


$col = $av/200;
$col = round($col);
$col = $col-10;

echo "<form action=\"obm_fs_ad.php?mod=yes\" method=\"POST\">";

echo "<div class=menu><font color=#ffffcc>Max k обмену:<br/>
+ </b></font><input type=\"text\" name=\"col\" size=\"3\" value=\"$col\" maxlength=\"9\"/> ";

echo "<input type='submit' name='ok' value='CoL' /><hr/></div>\n";


echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";

}

break;


case "yes";
// пишем защиты

$_POST[col] = round($_POST[col]);

if ($_POST[col]<=0 or is_numeric($_POST[col])==FALSE){
echo "<p><font color=#ff4040><b>Ошибка! Вводите цыфры больше ноля !!! </b></font></p>";
echo "<div class=silka><a href=\"obm_fs_ad.php\">Назад</a></div>";
include($path.'inc/down.php');EXIT;
}

$res = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' && `lat_name` = 'FestivalAdena'"); //проверка есть ли адена если да то открываем таблицу
$av=mysql_num_rows($res);
$fa = mysql_fetch_array($res);
$av = $fa[kol];

$col = $_POST[col];
$fad = $_POST[col] * 200;
$nav = $av - $fad;

if ($nav < 0){$navs = round($nav); $u=explode('-',$navs); 
echo "<p><font color=#f4d06e> Вам нехватает $u[1] Festival Aden!!! </b></font></p>";
echo "<div class=silka><a href=\"obm_fs_ad.php\">Назад</a></div>";
include($path.'inc/down.php');EXIT;
}




/// пишем результат
if($nav<=0){ // если равен нулю или меньше удаляем таблицу
mysql_query("DELETE FROM `res` WHERE `usr`='$log' and `lat_name`='FestivalAdena' LIMIT 1");//чистим логи
}else{ // иначе переписывем с новым количеством
mysql_query("UPDATE `res` SET `kol` = '$nav' WHERE `usr`='$log' and `lat_name`='FestivalAdena' LIMIT 1");
}


$reqi = mysql_query("SELECT * FROM `users` WHERE `usr`='$log' LIMIT 1");
$udata = mysql_fetch_array($reqi);

$almaz = $udata[almaz] + $col * 10; // щитаем колы

mysql_query("UPDATE `users` SET `almaz` = '$almaz' WHERE `usr`='$log' LIMIT 1");


echo "<p><font color=#f4d06e>Обмен успешно выполнен:</b><br/></p>";
echo " У вас было Ф.Аден - $av шт.<br/>";
echo " Забрали Ф.Аден - $fad шт.<br/>";
echo " Осталось Ф.Аден - $nav шт.<br/>";
echo " Зачисленно Coin of Luck - 10 шт.-$almaz<br/></font>";


echo "<div class=silka><a href=\"gorod.php\">В город</a></div>";

break;


}
include($path.'inc/down.php');
?>