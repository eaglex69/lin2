<?php
define('PROTECTOR', 1);

$headmod = 'zaksms';//фикс. места

$textl='Покупка Coin of Luck';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
place_okr();
place_zamok();
place_tower();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


switch($_GET['mod']){
default:
//--------монеты покупка---------------
echo"<hr>";
echo"<font color=#f4d06e> Купить <img src=\"pic/лааак.png\" height=15 width=15><font color=#ffffcc> Coin of Luck</font></font></p></b>";
echo"<font color=#f4d06e> У вас <img src=\"pic/лааак.png\" height=15 width=15><font color=#ffffcc> ".number_format($udata['almaz'], 0, ',', "`")."</font><font color=#f4d06e> Coin of Luck !</hr>";
echo "</div><br/></div>";
echo"<font color=#f4d06e> Заказы принимаются только на: <font color=#ffffcc>Яндекс деньги, и Web Money кошельки !</font></font></p></b>";
echo "</div><br/></div>";
echo"<img src=\"pic/яндекс.png\" height=15 width=20><font color=#ffffcc> Яндекс деньги кошелёк: <font color=#f4d06e> 410013042481355 </font></font></p>";
echo"<img src=\"pic/вебмани.png\" height=15 width=15><font color=#ffffcc> WebMoney WMZ кошелёк: <font color=#f4d06e> Z578365311205 </font></font></p></b>";
echo "</div><br/></div>";
echo" <font color=#ffffcc>1000 <img src=\"pic/лааак.png\" height=15 width=15><font color=#f4d06e> Coin of Luck<font color=#ffffcc> = 100 руб или 2$ </font><br/></br>";
echo "</div><br/></div>";
echo"<font color=#f4d06e> Внимание ! </font></font></p></b>";
echo"<font color=#ffffcc> При переводе денег в описании введите:<br/>Ваш ник, пробел,  слово WM с кодом( код - U,R,Z,B )  , пробел и количество CoL(без запятых или каких либо других знаков) </font></font></p></b>";
echo "</div><br/></div>";
echo"<font color=#f4d06e> Пример: <font color=#ffffcc>$log <font color=#f4d06e>CoL <font color=#ffffcc>100</b></p></div><hr>";
echo "</div><br/></div>";
echo"<font color=#ffffcc> Мы принимаем деньги напрямую, все округляется в меньшую сторону, поэтому переводите точную сумму.</font></font></p></b>";
echo"<font color=#f4d06e> Если вы ошиблись при переводе денег на кошелек, Администрация не несет никакой ответственности.</font></font></p></b>";
echo"<font color=#ffffcc> Начисление <font color=#f4d06e><img src=\"pic/лааак.png\" height=15 width=15> Coin of Luck<font color=#ffffcc> происходит в течении 24 часов, независимо когда вы отправили деньги или запрос на услугу.</font></font></p></b>";
echo"<font color=#f4d06e> После пополнения, оставьте заявку ! </b></p></div>";
/*$udata[wmr] = $udata[wmr]/100;




//echo "<font color=grey><small>* перевод с кошелька на кошелёк </small></font></a>";
//echo "<div class=silka><a href=\"/wmr_obmen.php?\"> Обмен WMR на CoL </a></div></div>";
///echo"<font color=#CC6633>WMR: ".number_format($udata[wmr], 2, ',', "`")."</font> <a href=\"/wmr_obmen.php?\"> [+] </a>";



echo "<div class=dot>";

/* echo"<p><u><font color=orange> -=: Доступно :=-</font></u><br/>"; */

echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<a href=\"webmoney.php\">Заявка </a></p></div>";





///echo "<p> <a href=\"?mod=webmoney\"> Как пользоватся WebMoney? </a></p>";


///echo "<hr/>



//-------покупка по СМС --------------
echo "<hr/> ";


/*
if ($_SERVER[HTTP_HOST]=="line-age.mobi"){

//echo"<b><p><font color=yellow>Заказ CoL через WebMoney</font></p></b>";

//echo "<hr/><form action=\"zaksms_x5.php?$sd&mod=1\" method=\"post\">";
//echo '<input type="submit" value="Купить 5 CoL" /></form>';
//echo"<font color=green>+10% бонус (1 CoL)</font></b>";

//echo "<hr/><form action=\"zaksms_x10.php?$sd&mod=1\" method=\"post\">";
//echo '<input type="submit" value="Купить 10 CoL" /></form>';
//echo"<font color=green>+20% бонус (4 CoL)</font></b>";

//echo "<hr/><form action=\"zaksms_x25.php?$sd&mod=1\" method=\"post\">";
//echo '<input type="submit" value="Купить 25 CoL" /></form>';
//echo"<font color=green>+30% бонус (15 CoL)</font></b>";

//echo "<hr/><form action=\"zaksms_x50.php?$sd&mod=1\" method=\"post\">";
//echo '<input type="submit" value="Купить 50 CoL" /></form>';
//echo"<font color=green>+40% бонус (40 CoL)</font></b>";

//echo "<hr/><form action=\"zaksms_x160.php?$sd&mod=1\" method=\"post\">";
//echo '<input type="submit" value="Купить 160 CoL" /></form>';
//echo"<font color=green>+50% бонус (160 CoL)</font></b>";

//echo "<hr/><form action=\"zaksms_x600.php?$sd&mod=1\" method=\"post\">";
//echo '<input type="submit" value="Купить 300 CoL" /></form>';
//echo"<font color=green>+50% бонус (300 CoL)</font></b>";

}else{
echo"<b><font color=grey>Заказ через СМС работает только с адреса:</font></b><br/>";
echo ' <a href="http://line-age.mobi">line-age.mobi</a> '; // 

}
*/
//echo "<b><font color=red>Пополнение через Смс на данный момент не доступно.</b></font>";


echo "<br/>";


//------Покупка через WebMoney ---------
//echo"<b><font color=yellow>Заказ WMR за WebMoney</font></b><br/><br/>";
//echo"<font color=#0077CC>4 WMR = 1 грн / 4 руб / 0,2$ / 1`200 WMB</font><br/></br>";





break;


//************************************************************************************************
case 'webmoney':

echo'<font color=orange><p><b>Как пользоватся WebMoney?</b></p></font><hr/>';

echo'<b>Инструкция:</b><br/>';
echo'- Найдите термимнал в Вашем городе или регионе<br/>';
echo'- Найди в терминале кнопку WebMoney<br/>';
echo '- Введи номер WM кошелька (смотрите какой просят: U / R / Z / B)<br/>
<font color=lime>
U376888655378 - гривна (Украина)<br/>
R384743139556 - рубли (Россия)<br/>
Z219798634227 - долары <font color=green>$</font><br/>
B283905379103 - бел.руб. (Беларусия)</font> <br/><br/>'; 
echo "<a href=\"webmoney.php\"> Оставте заявку на пополнение </a>";
echo'<hr/><img src="pic/webmoney.png"/>';

echo'<hr/>';
echo ' <a href="http://gde.webmoney.ru">Узнать пункты пополнения WEBMONEY в моем регионе</a>';
echo'<hr/>';
//echo ' <a href="http://webmoney.ru/rus/addfunds/">Пополнить кошелек WEBMONEY (Статья на webmoney.ru - оф.сайт)</a>';
echo'<hr/>';

///echo 'На доработке';

break;

}
include($path.'inc/down.php');
?>