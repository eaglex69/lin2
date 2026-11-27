<?
define('PROTECTOR', 1);

$head = 'cp';//фикс. места

//$path = '../'; //путь

$textl='Поддержка';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/func_tik.php'); // только для тикета
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


if($udata[dostup]<2){
echo'Доступ закрыт!';
include($path.'inc/down.php');
exit;
}



switch($_GET[go]) {

default:


//--------------------------------------Удаляем тикет и все его сообщения-----------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------
if ($_GET[go]==del){

if($udata[dostup]<4){
echo'Доступ закрыт!';
include($path.'inc/down.php');
exit;
}

// проверка автора тикета
$req2 = mysql_query("SELECT * FROM `tiket_tm` WHERE id = '$_GET[id]'");
$avto2=mysql_num_rows($req2);
if($avto2==0){
echo'<font color=red> Ошибка, это не Ваш тикет! </font><hr/>';
}else{

mysql_query("DELETE FROM `tiket_tm` WHERE `id` = '$_GET[id]' Limit 1"); // чистим тикет
mysql_query("DELETE FROM `tiket_m` WHERE `id_tm` = '$_GET[id]'"); // чистим сообщения тикета

echo'<font color=#007F46> Тикет успешно удалён! </font><hr/>';

}
}
/////////////////////////////////////////////////////

	
echo '<p>';	
if ($_GET[tip]=='all'){echo 'Все тикеты  | ';}else{ echo '<a href="tiket_adm.php?tip=all">Все тикеты </a> | ';}
if ($_GET[tip]=='errorgame'){echo 'Ош. игр.  | ';}else{ echo '<a href="tiket_adm.php?tip=errorgame">Ош. игр. </a> | ';}
if ($_GET[tip]=='errormany'){echo 'Ош. опл.  | ';}else{ echo '<a href="tiket_adm.php?tip=errormany">Ош. опл. </a> | ';}
if ($_GET[tip]=='helpgame'){echo 'Помощь  | ';}else{ echo '<a href="tiket_adm.php?tip=helpgame">Помощь </a> | ';}
if ($_GET[tip]=='drugoe'){echo 'Другое  | ';}else{ echo '<a href="tiket_adm.php?tip=drugoe">Другое</a> | ';}
echo '</p>';
	
	
if (empty($_GET[tip])){
$req = mysql_query("SELECT * FROM `tiket_tm` WHERE `id` != '' && `news` > '0'  ORDER BY news,id DESC");
}else{
$req = mysql_query("SELECT * FROM `tiket_tm` WHERE `id` != '' && `tip` = '$_GET[tip]'  ORDER BY news,id DESC");}

if ($_GET[tip]=='all'){$req = mysql_query("SELECT * FROM `tiket_tm` ORDER BY news,id DESC");}


$avto=mysql_num_rows($req);
if($avto>0){ // есть тикеты


echo "<b><p>Новые тикеты!</p></b>";
echo "<hr/>";
While($tik = mysql_fetch_array($req))
{

$r = mysql_query("SELECT * FROM `tiket_m` WHERE `id_tm` = '$tik[id]'");
$a = mysql_num_rows($r);

echo "<font color=grey>$tik[avtor]</font> <font color=#722B00>[$tik[data]]</font><br/> <a href=\"?go=tk&id=$tik[id]\"> $tik[title]</a> <b>($a)</b>";
if ($tik[news]>0) {echo "<font color=grey>+$tik[news]</font>";}
if($udata[dostup]>=4){echo " | <a href=\"?go=del&id=$tik[id]\"><font color=red><small>[X]</small></font></a> |";}
if ($tik[closed]>0) {echo "<font color=#76ABC7> OFF </font> |";}
echo "<hr/>";
}
}else{echo "<p>Новых тикетов нет =)</p>";}

/*
echo "<br/><div class=paty>";
echo "<br/>";
echo "<br/>";


$req = mysql_query("SELECT * FROM `tiket_tm` WHERE `id` != '' && `news` = '0'  ORDER BY news DESC");

$avto=mysql_num_rows($req);
if($avto>0){ // есть тикеты


echo "<b><p>Все тикеты!</p></b>";
echo "<hr/>";
While($tik = mysql_fetch_array($req))
{

$r = mysql_query("SELECT * FROM `tiket_m` WHERE `id_tm` = '$tik[id]'");
$a = mysql_num_rows($r);

echo "<font color=grey>$tik[avtor]</font> <font color=#722B00>[$tik[data]]</font><br/> <a href=\"?go=tk&id=$tik[id]\"> $tik[title]</a> <b>($a)</b>";
if ($tik[news]>0) {echo "<font color=grey>+$tik[news]</font>";}
if($udata[dostup]>=4){echo " | <a href=\"?go=del&id=$tik[id]\"><font color=red><small>[X]</small></font></a> |";}
echo "<hr/>";
}
}else{echo "<p>Тикетов нет =)</p>";}
echo "</div><br/>";

////////////////////////////////////////////////////////////////////////////	
	
*/	

break;



//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------
//----------------------Просмотр тикета---------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------

case 'tk':

// проверка автора тикета
$req2 = mysql_query("SELECT * FROM `tiket_tm` WHERE id = '$_GET[id]'");
$avto2=mysql_num_rows($req2);
if($avto2==0){
echo'Ошибка, тикета не существует!<br/>';
echo"<br/><a href=\"?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
/////////////////////////
$tiko = mysql_fetch_array($req2);
////////	// выщитуем права //	////////
if ($tiko[tip]='errorgame'){$dostup = 4;}
if ($tiko[tip]='errormany'){$dostup = 4;}
if ($tiko[tip]='helpgame'){$dostup = 1;}
if ($tiko[tip]='drugoe'){$dostup = 2;}

if($udata[dostup]<$dostup){
echo'Доступ закрыт!';
include($path.'inc/down.php');
exit;
}
//////////////////////////////////////////////


// удаляем пост
if (!empty($_GET[del])){
		 
$r54646 = mysql_query("DELETE FROM `tiket_m` WHERE `id` = '$_GET[ids]' Limit 1"); // чистим сообщения тикета

				if 		($r54646 == 'true')	{echo'<font color=#007F46> Сообщение успешно удалён! </font><hr/>';}
				else	{}  // неудачно =)


}


if (isset($_GET[na_null])){
// сбиваем счётчик на ноль
mysql_query ("UPDATE tiket_tm SET
        news = '0'
		 WHERE id='$_GET[id]' LIMIT 1");
		 echo "<hr><font color=#007F46>Прочитал =)</font>";
}

if (isset($_GET[closed])){
// закрыли тикет юзеру
mysql_query ("UPDATE tiket_tm SET
        news = '0',closed = '1'
		 WHERE id='$_GET[id]' LIMIT 1");

	// пишем юзеру сообщение что вам ответили в тикете
	
	$text = "<font color=#76ABC7> Ваш тикет:  <a href=\"tiket.php?go=tk&id=$tiko[id]\"> $tiko[title]</a> закрыт! Прочитайте и удалите его.</font>";

mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '$tiko[avtor]', `time` = '$time', `read` = 1, `mail_msg` = '$text'");
		 
		 
		 }
if (isset($_GET[open])){
// открыли тикет
mysql_query ("UPDATE tiket_tm SET
        closed = '0'
		 WHERE id='$_GET[id]' LIMIT 1");
		 echo "<hr><font color=#58988A>Тикет открыт!</font>";
}



if (!empty($_POST[text])) // если есть текст записуем сообщение
{

$msg = $_POST[text];
$msg = htmlspecialchars(stripslashes(trim($msg)));
$log = htmlspecialchars(stripslashes(trim($log)));
$currHour=date("H",time());
$currDate=date("d.m.Y", time());
$currTime=date("$currHour:i", time());

// пишем антифлуд
$req6546 = mysql_query("SELECT * FROM `tiket_m` WHERE `usr` = '$log' && `id_tm` = '$_GET[id]' ORDER BY id DESC LIMIT 1");
$pr = mysql_fetch_array($req6546);
if($pr[text] == $msg){
echo'<font color=#9E0000>Антифлуд!</font><br/>';}else{

$ressave = mysql_query("INSERT INTO `tiket_m` SET
        `id_tm` = '$_GET[id]',
        `text` = '$msg',
        `data` = '$currDate в $currTime',
        `usr` = '$log'"); // создаем строку с ответом



// сбиваем счётчик на ноль
mysql_query ("UPDATE tiket_tm SET
        news = '0'
		 WHERE id='$_GET[id]' LIMIT 1");


		 
		$news2 = $tiko[new_adm]+1; // пишем счётчик +1
		
mysql_query ("UPDATE tiket_tm SET
        new_adm='$news2'
		 WHERE id='$_GET[id]' LIMIT 1");
	
	// пишем юзеру сообщение что вам ответили в тикете
	
	$text = " Вам ответили на Ваш тикет:  <a href=\"tiket.php?go=tk&id=$tiko[id]\"> $tiko[title]</a> !";

mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '$tiko[avtor]', `time` = '$time', `read` = 1, `mail_msg` = '$text'");

	
				if 		($ressave == 'true')	{echo "<hr><font color=#007F46>Сообщение добавленно!</font>";}
				else	{echo "<font color=red><p> Неудача ! </p></font>";}  // неудачно =)
}
}
$req = mysql_query("SELECT * FROM `tiket_tm` WHERE id = '$_GET[id]'");
$tik = mysql_fetch_array($req);

echo "<div class=menu>";
echo "<hr/>Тема тикета: <b>$tik[title]</b><br/>
<a href=\"search.php?nick=$tik[avtor]&amp;go=go\">$tik[avtor]</a> <font color=grey>($tik[data])</font><br/>
<b><u>Сообщение:</u></b><br/> ".nl2br($tik[msg])."


<hr/><hr/>
";

//// переписка в тикете
echo "<br/><font color=#407C61><b>Переписка:</b></font><hr/>";
//---
echo "<div class=admin>";
echo "<a href=\"?go=tk&id=$_GET[id]&na_null\">[Прочитал]</a> | ";
if ($tik[closed]==1){	echo "<a href=\"?go=tk&id=$_GET[id]&open\">[Откр]</a>";}
else{					echo "<a href=\"?go=tk&id=$_GET[id]&closed\">[Закр]</a>";} //закрыть
echo "</div><hr/>";
if ($tik[closed]==1){
echo "<p><span style=color:#E12222;><b>Топик закрыт</b></span></p><hr/>";
}else{

echo "<form method=\"post\" action=\"?go=tk&id=$_GET[id]\">";
echo "Текст сообщения:";
echo "<textarea name=\"text\" rows=3></textarea><br/>";
echo "<input type=\"submit\" value=\"Отправить\" class=\"ibutton\"></form>";


echo "<hr/>";}
//---выщитует страницы----------

	$count = mysql_result(mysql_query("SELECT COUNT(*) FROM `tiket_m` WHERE `id_tm` = '$_GET[id]'"), 0);
	if($count > 0){
		$pages = ceil($count/10);
		if(isset($_GET['page'])){
			$page = abs(intval($_GET['page']));
		}else{
			$page = 1;
		}
		$from = ($page-1)*10;
}
//-------------------------------

$req = mysql_query("SELECT * FROM `tiket_m` WHERE id_tm = '$_GET[id]' ORDER BY id DESC LIMIT $from, 10");
$avt = ($req);

if($avt>=1)
{

While($tk = mysql_fetch_array($req))
{
$us = mysql_query("SELECT * FROM `users` WHERE usr = '$tk[usr]' LIMIT 1");
$usr = mysql_fetch_array($us);
if ($usr[dostup]>=4)						{$color = '<font color=lime>'; $color2 = '<font color=#5e995c>';}
if ($usr[dostup]==2 or $usr[dostup]==3)	{$color = '<font color=#0026FF>'; $color2 = '<font color=#6DC2FF>';}
if ($usr[dostup]==1) 					{$color = '<font color=#7F6A00>'; $color2 = '<font color=#A09353>';}
if ($usr[dostup]==0) 					{$color = ''; $color2 = '';}

if ($udata[dostup]>=4){$ss545 = "<a href=\"?go=tk&id=$_GET[id]&ids=$tk[id]&del=yes\"> <font color=green>[х]</font> </font></a>";}
echo "<a href=\"search.php?nick=$tk[usr]&amp;go=go\">$color $tk[usr] </font></a> <font color=grey><small>$tk[data]</small></font><br/>
$color2 ".nl2br($tk[text])."</font> 	$ss545<hr/>
";
}

	navig($page, 'tiket_adm.php?go=tk&amp;id='.$_GET[id].'&amp;', $pages);

}else{
echo "Сообщений нет<hr/>";}

//////////////////////////////////


echo "</div>";
echo"<br/><a href=\"?\">Назад</a>";

break;

//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------
//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------

}

/*echo"<div class=silka> &#160  &#160<a href=\"adm_panel.php?\">Админка</a> </div>";*/

include($path.'inc/down.php');
?>









