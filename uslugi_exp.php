<?
include_once("inc/zag.php");
switch($_GET['go'])
{

default:

echo"Купить Coin of Luck через:<br/>";
//echo"<img src=\"img/col.png\"> <a href=\"uslugi_exp.php?go=sms\">SMS</a><br/>";
echo"<img src=\"img/col.png\"> <a href=\"uslugi_exp.php?go=wm\">WebMoney</a><font color=\"red\"></font><br/>";


break;

case 'wm':

echo"<b>Покупка Coin of Luck через WebMoney<b><br/>";
echo"<font color=\"grey\">10 Coin of Luck = 2 грн./5 руб./2000 бел. руб.</font><br/>";
echo"<font color=\"grey\">20 Coin of Luck = 4 грн./10 руб./4000 бел. руб.</font><br/>";
echo"<font color=\"grey\">30 Coin of Luck = 6 грн./15 руб./6000 бел. руб.</font><br/>";
echo"<font color=\"grey\">40 Coin of Luck = 8 грн./20 руб./8000 бел. руб.</font><br/>";
echo"<font color=\"grey\">50 Coin of Luck = 10 грн./25 руб./10,000 бел. руб.</font><br/>";
echo"<font color=\"grey\">60 Coin of Luck = 12 грн./30 руб./12,000 бел. руб.</font><br/>";
echo"<font color=\"grey\">70 Coin of Luck = 14 грн./35 руб./14,000 бел. руб.</font><br/>";
echo"<font color=\"grey\">80 Coin of Luck = 16 грн./40 руб./16,000 бел. руб.</font><br/>";
echo"<font color=\"grey\">90 Coin of Luck = 18 грн./45 руб./18,000 бел. руб.</font><br/>";
echo"<font color=\"grey\">100 Coin of Luck = 20 грн./50 руб./20,000 бел. руб.</font><br/><br/>";
echo"<font color=\"lime\">При переводе денег в описании введите:
Ваш ник, пробел, WM кошелек, пробел и количество Coin of Luck(без запятых или каких либо других знаков)</font><br/> Например: <font color=\"grey\"><b>$log WMU 3 ибо $log WMR 3</font></b><br/>";
echo"<br/>Заказы принимаются только на Web кошельки<br/><br/><img src=\"img/wm.png\"><B>Реквизиты WebMoney<B>:

<br/><img src=\"img/wmr.png\"> R384743139556 </b></br><img src=\"img/wmu.png\"> U376888655378</br><img src=\"img/wmb.png\"> B283905379103</br><img src=\"img/wmz.png\"> Z219798634227</br><img src=\"img/wme.png\"> E112456758395</br>";

echo"<font color=\"red\">*Мы принимаем деньги напрямую с кошельков,все округляется в меньшую сторону поэтому переводите точную сумму.<br/>
*Если вы ошиблись при переводе денег на кошелек,Администрация не несет некакой ответственности.<br/>
*Начисление Coin of Luck, происходит в тичении 24 часов,в независимости когда вы отправили деньги,или отправили запрос на услугу.</font><br/>";

break;


case 'sms':
echo"<img src=\"pic/ru.png\"> <a href=\"uslugi_exp.php?go=rus\">Для Росии</a><br/>";
echo"<img src=\"pic/ua.png\"> <a href=\"uslugi_exp.php?go=ua\">Для Украины</a><br/>";

break;

case 'rus':

echo"<b>Для покупки  EXP отправь SMS c текстом:
79exp$log<br/><br/>";

echo '7201 -  2,000,000 exp (~30 р. c НДС**)<br />';
echo '7202 -  5,000,000 exp (~60 р. c НДС**)<br />';
echo '3353 -  20,000,000 exp (~135 р. c НДС**)<br />';

echo '<br /><br /><font color=red>Услуга предоставляется только совершеннолетним абонентам. Тариф в гривнах с учетом НДС. Дополнительно взимается сбор в Пенсионный фонд в размере 7,5% от стоимости без учета НДС.
Услуга предоставляется при технической поддержке ООО «Азимут Медиа». 
Телефон службы поддержки 0800604461, круглосуточно. Звонки со стационарных телефонов бесплатные, с мобильных - тариф устанавливает Ваш оператор.</font>';

break;

case 'ua':

echo"<b>Для покупки  EXP отправь SMS c текстом:
79exp$log<br/><br/>";

echo '7204 -  6,000,000 exp (~13 грн. c НДС**)<br />';
echo '2855 -  10,000,000 exp (~25 грн. c НДС**)<br />';
echo '3855 -  30,000,000 exp (~50 грн. c НДС**)<br />';

echo '<br /><br /><font color=red>Услуга предоставляется только совершеннолетним абонентам. Тариф в гривнах с учетом НДС. Дополнительно взимается сбор в Пенсионный фонд в размере 7,5% от стоимости без учета НДС.
Услуга предоставляется при технической поддержке ООО «Азимут Медиа». 
Телефон службы поддержки 0800604461, круглосуточно. Звонки со стационарных телефонов бесплатные, с мобильных - тариф устанавливает Ваш оператор.</font>';

break;

}
include_once"inc/down.php";
?>




