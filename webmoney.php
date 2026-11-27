<?php
define('PROTECTOR', 1);

$headmod = 'zaksms';//фикс. места

$textl='Покупка Монет';
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

// --- --- удалить заказ --- --- //
if (isset($_GET['del'])){
$reqcolpovtor = mysql_num_rows(mysql_query("SELECT * FROM `zakaz_col_wm` WHERE `usr`='$log' and `id`='$_GET[del]' Limit 1"));
if ($reqcolpovtor==1){
mysql_query("DELETE FROM `zakaz_col_wm` WHERE `id`='$_GET[del]' and `usr`='$log' LIMIT 1");
echo "<p><font color=#339966>Удаленно</font></p><hr/>";
}
}
// -- CoL оставить (записать БД) заявка --//

if (isset($_GET['go'])){

		$data = date("d F Yг. в H:i", strtotime("+20 seconds"));

//$data=date("d F Yг");
$data = str_replace("January","Января",$data);
$data = str_replace("February","Февраля",$data);
$data = str_replace("March","Марта",$data);
$data = str_replace("April","Апреля",$data);
$data = str_replace("May","Мая",$data);
$data = str_replace("June","Июня",$data);
$data = str_replace("July","Июля",$data);
$data = str_replace("August","Августа",$data);
$data = str_replace("September","Сентября",$data);
$data = str_replace("October","Октября",$data);
$data = str_replace("November","Ноября",$data);
$data = str_replace("December","Декабря",$data);

//защита//
if ((float)$_POST['col']<=0 or (float)$_POST['col']>2000000000){echo "<p><font color=#f4d06e>Кол-во заказаных Coin Of Luck</font></p><hr/>";}else{
//-
$reqcolpovtor = mysql_num_rows(mysql_query("SELECT * FROM `zakaz_col_wm` WHERE `usr`='$log' and `col`='$_POST[col]' Limit 1"));
if ($reqcolpovtor==1){echo "<p><font color=#990000>Повтор запроса</font></p><hr/>";}else{
//-
if (empty($_POST['com']) or $_POST['com'] == "Укажите WM перевода (R,U,B,Z). Код транзикции!"){echo "<p><font color=#990000>Не указан коментраий</font></p><hr/>";}else{
//-
//-----//

mysql_query("INSERT INTO
        `zakaz_col_wm` SET
        `usr` = '$log',
        `col` = '$_POST[col]',
        `com` = '$_POST[com]',
        `data` = '$data'");

echo "<p><font color=#f4d06e>Заявка принята. Ожидайте! </font></p><hr/>";
		
}}}
}
//--------Заявки узера уже созданны-------------//
$zakazavto = mysql_num_rows(mysql_query("SELECT * FROM `zakaz_col_wm` WHERE `usr`='$log'"));
$zak = mysql_query("SELECT * FROM `zakaz_col_wm` WHERE `usr`='$log'");
if ($zakazavto > 0){

echo '<p><font color=#f4d06e>Ваши заявки в обработке:</font></p><hr/>';

   While($zakaz = mysql_fetch_array($zak))
   {
		echo "<div class=dot>";
		echo"CoL: <b>$zakaz[col]</b>&nbsp;&nbsp;";
		echo "<a href=\"?del=$zakaz[id]\"><font color=red>[x]</font></a> <br/>";
		echo"Дата: <b>$zakaz[data]</b><br/>";
		echo"Коментарий: <b>$zakaz[com]</b><br/>";
		echo "</div>";
	}
echo "<hr/>";	
}
//----------------------------------------------//

//--------CoL заполнение заявка---------------
echo '<p><font color=#f4d06e>Оставить заявку на пополнения через Web Money</font></p><hr/>';


if (empty($_POST['col'])){$col_post = '';}else{$col_post = $_POST['col'];}
if (empty($_POST['com'])){$com_post = '';}else{$com_post = $_POST['com'];}

echo "<form action=\"?go\" method=\"POST\">

<small><font color=#f4d06e>Укажите кол-во заказаных Coin Of Luck !</font></small><br/>
<input type=\"text\" name=\"col\" value=\"$col_post\"/> <br/>

<small><font color=#f4d06e>Укажите WM перевода (R,U,B,Z). Код транзикции!</font></small><br/>
<textarea name=\"com\" maxlength=\"100\">$com_post</textarea><br/>

<input type=\"submit\" value=\"\" class=\"button_medium3\"><hr/>";


echo '<p><font color=#f4d06e> 
Если при переводе коментарии были не заполнены, тогда укажите:<br/>
<font color=#ffffcc> - Сумму заказа.<br/>
- Время и дату заказа<br/>
- Номер транзакции<br/>
- Или любую информацию подтверждающую ваш заказ.<br/>


 </font><br/><font color=#f4d06e>
Заявки обрабатываются в течении 24 часов.<br/>
Заявку остовлять после того, как перевели деньги на указанные реквизиты.<br/>
За ложные попытки пополнения<font color=#ff4040> БЛОК <font color=#f4d06e>без права на востановление.<br/>
</font></p>';

break;

}
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<p> <a href=\"zaksms.php\"> Назад</a></p></div><hr>";

include($path.'inc/down.php');
?>