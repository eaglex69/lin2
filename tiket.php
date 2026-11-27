<?php
define('PROTECTOR', 1);

$head = 'tiket';//фикс. места

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



$tematik = isset($_POST['tematik']) ? $_POST['tematik'] : (isset($_GET['tematik']) ? $_GET['tematik'] : '');

switch($_GET['go']) {

default:

//--------------------------------------Удаляем тикет и все его сообщения-----------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------
if ($_GET['go']=='del'){

// проверка автора тикета
$req2 = mysql_query("SELECT * FROM `tiket_tm` WHERE `avtor` = '$log' && id = '$_GET[id]'");
$avto2=mysql_num_rows($req2);
if($avto2==0){
echo'<font color=red> Ошибка, это не Ваш тикет! </font><hr/>';
}else{

mysql_query("DELETE FROM `tiket_tm` WHERE `id` = '$_GET[id]' && `avtor` = '$log' Limit 1"); // чистим тикет
mysql_query("DELETE FROM `tiket_m` WHERE `id_tm` = '$_GET[id]'"); // чистим сообщения тикета

echo'<font color=#007F46> Тикет успешно удалён! </font><hr/>';

}
}
/////////////////////////////////////////////////////
echo '<font color=orange>Центр-Поддержки для игрока <font color=green><b>'.$log.'</b></font> !</font><br/>';

echo"<br/><a href=\"faq.php?$sd\"><u>Ответ на твой вопрос!</u></a><br/>";
echo '(Перед тем как писать обезательно прочти)<br/>';

	
	
	
	
																		///////////////////////////
//////////////////////////////////ТИКЕТЫ//////////////////////////////////
$message108="<br/><font color=lime>*Удалите пожалуйста тикет, если Вы 
получили ответ на свой вопрос просто нажав на <font color=red>[x]</font>. Это ускорит ответы на новые вопросы и защитит Вас от игнора со стороны Администрации!</font>'
";

$req = mysql_query("SELECT * FROM `tiket_tm` WHERE `avtor` = '$log'");

$avto=mysql_num_rows($req);
if($avto>0){ // есть тикеты

echo "<br/>Ваши тикеты!<br/>";

While($tik = mysql_fetch_array($req))
{

$r = mysql_query("SELECT * FROM `tiket_m` WHERE `id_tm` = '$tik[id]'");
$a = mysql_num_rows($r);

echo "<a href=\"?go=tk&id=$tik[id]\">&#160 &#187 $tik[title]</a> <b>($a)</b> ";
if  ($tik['new_adm']>0){ echo " <font color=orange><b>+$tik[new_adm]</b></font>";}
 echo "&#160 <a href=\"?go=del&id=$tik[id]\"><font color=red>[x]</font></a>";
echo "<br/>";
}

echo "<br/><font color=lime>* Удалите пожалуйста тикет, если Вы 
получили ответ на свой вопрос, нажав на <font color=red>[x]</font> </font>";

}


////////////////////////////////////////////////////////////////////////////	
	
	
	
	
	
	
	
	
	
	
	
	
	
echo '<br/><br/>
При создании темы:<br/>
- Выражайте, пожалуйста, свою мысль как можно яснее, описывайте сразу суть сообщения, чтобы вас не переспрашивали 
и небыло исчерпано впустую Ваше и наше время, которое может быть потрачено на разработку игры;<br/>
- Многие ответы на свои вопросы вы можете узнать на <a href="forum">форуме</a>, в <a href="chat.php">чате</a> или у игроков.
';


echo '<br/><br/>';
echo "<font color=grey>У Вас остались вопросы или у появилась проблема! Сообщите нам об этом.</font><br/> <b>Выберите направление вопроса:</b>";

//----------------Написать в ЦП-----(+)----------------------------------------------

echo '<form action="?go=sendok" method="post">';

echo "
<select name=\"tematik\">
<option value=\"nohelp\"> Выберите раздел  </option>
<option value=\"errorgame\">Ошибка игры </option>
<option value=\"errormany\">Ошибка пополнения </option>
<option value=\"helpgame\">Помощь по игре</option>
<option value=\"drugoe\">Другое</option>
</select>";

echo '<input type="hidden" name="uzer" maxlength="15" value="'.$helper.'"/>';


echo '<br/>Тема сообщения:<br /><input name="tema" maxlength="25" title="Тема"/><br />';

echo 'Введите сообщение:<br />';
echo "<textarea name=\"msg\" maxlength=\"1000\" rows=\"3\"></textarea><br/>";


echo '<input class="button" type="submit" value="Отправить">';
echo '<postfield name="tema" value="$(tema)"/>';
echo '<postfield name="msg" value="$(msg)"/>';

echo '<postfield name="user" value="'.$loginin.'"/>';
echo "</form>";

include($path.'inc/down.php');

break;

//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---
//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---//---

case "sendok":

if ($tematik == "nohelp") {echo "Вы не выбрали подходящий Вам раздел!"; include($path.'inc/down.php');}
if($udata['lvl']<"5"){echo"В Центр-Помощи можно писать только с 5 уровня."; include($path.'inc/down.php');}

if ($_SERVER['HTTP_X_FORWARDED_FOR'])
{ $ip=htmlspecialchars(stripslashes($_SERVER['HTTP_X_FORWARDED_FOR'])); }
else { $ip=htmlspecialchars(stripslashes($_SERVER['REMOTE_ADDR'])); }
$currHour=date("H",time());
$currDate=date("d.m.Y", time());
$currTime=date("$currHour:i", time());

$msg = $_POST['msg'];
$str=strlen($msg);

if($msg=="" || $str<6 || $str>2000){echo'Письмо должно быть не менее 6 символов и не более 2000 символов!';
echo'<br /><br /><a href="?">Вернуться</a>';
include($path.'inc/down.php');
exit;}

$tema = $_POST['tema'];
$strtema=strlen($tema);

if($tema=="" || $strtema<2 || $strtema>55){echo'Тема должна быть не менее 2 символов и не более 25 символов!';
echo'<br /><br /><a href="?">Вернуться</a>';
include($path.'inc/down.php');
exit;}

$tema = htmlspecialchars(stripslashes(trim($tema)));
$msg = htmlspecialchars(stripslashes(trim($msg)));
$log = htmlspecialchars(stripslashes(trim($log)));


// пишем защиту от повтора тикета
$req = mysql_query("SELECT * FROM `tiket_tm` WHERE `avtor` = '$log' && `title` = '$tema' && `msg` = '$msg'");
$avto=mysql_num_rows($req);
if($avto>0){
echo'Такой тикет уже создан!<br/>';
echo"<br/><a href=\"?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
///////////////////////////////////


//$tex="|$log|$tema|$currDate|$currTime|$ip|$msg|$data3[0]|$tematik|||||||";


mysql_query("INSERT INTO
        `tiket_tm` SET
        `title` = '$tema',
        `avtor` = '$log',
        `data` = '$currDate в $currTime',
        `closed` = '0',
        `msg` = '$msg',
        `news` = '1',
		`tip` = '$tematik'");
		





///----------------///----------------///----------------///----------------///----------------




echo '<font color=grey><p>Cообщение отправлено в центр помощи!<br>
Ваш тикет будет проверен в тeчении 24 часов, ожидайте ответа!</p></font>';

echo'<hr/><div class=menu><font color=#007F46>Тема:<br/></font>';
echo "<b>$tema</b>";
echo'<br/><font color=#006035>Текст сообщения:</font><br />';
echo "<b>$msg</b></div><hr/>";

echo"<br/><div class=silka> <a href=\"?\">Назад</a></dov>";


break;

//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------
//----------------------Просмотр тикета---------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------

case 'tk':
// проверка автора тикета
$req2 = mysql_query("SELECT * FROM `tiket_tm` WHERE `avtor` = '$log' && id = '$_GET[id]'");
$avto2=mysql_num_rows($req2);
if($avto2==0){
echo'Ошибка, это не Ваш тикет!<br/>';
echo"<br/><a href=\"?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
/////////////////////////
$tiko = mysql_fetch_array($req2);

// сбиваем счётчик на ноль
mysql_query ("UPDATE tiket_tm SET
        new_adm = '0'
		 WHERE id='$_GET[id]' && avtor = '$log' LIMIT 1");



//if ($tiko[closed]==1){echo "********";}


if (!empty($_POST['text']) && $tiko['closed']==0) // если естьтекст записуем сообщение
{

$msg = $_POST['text'];
$msg = htmlspecialchars(stripslashes(trim($msg)));
$log = htmlspecialchars(stripslashes(trim($log)));
$currHour=date("H",time());
$currDate=date("d.m.Y", time());
$currTime=date("$currHour:i", time());

// пишем антифлуд
$req6546 = mysql_query("SELECT * FROM `tiket_m` WHERE `usr` = '$log' && `id_tm` = '$_GET[id]' ORDER BY id DESC LIMIT 1");
$pr = mysql_fetch_array($req6546);
if($pr['text'] == $msg){
echo'<font color=#9E0000>Антифлуд!</font><br/>';}else{

$ressave = mysql_query("INSERT INTO `tiket_m` SET
        `id_tm` = '$_GET[id]',
        `text` = '$msg',
        `data` = '$currDate в $currTime',
        `usr` = '$log'"); // создаем строку с ответом
		
		$news2 = $tiko['news']+1; // пишем счётчик +1
		
mysql_query ("UPDATE tiket_tm SET
        news='$news2'
		 WHERE id='$_GET[id]' LIMIT 1");
		 
				if 		($ressave == 'true')	{echo "<hr><font color=#007F46>Сообщение добавленно!</font>";}
				else	{echo "<font color=red><p> Неудача ! </p></font>";}  // неудачно =)
}
}
$req = mysql_query("SELECT * FROM `tiket_tm` WHERE `avtor` = '$log' && id = '$_GET[id]'");
$tik = mysql_fetch_array($req);

echo "<div class=menu>";
echo "<hr/>Тема тикета: <br/><u><b>$tik[title]</b></u> &#160 <a href=\"?go=del&id=$tik[id]\"><font color=red><b>[x]</b></font></a><br/>
<a href=\"search.php?nick=$tik[avtor]&amp;go=go\">$tik[avtor]</a> <font color=grey>($tik[data])</font><br/>
<b><u>Сообщение:</u></b><br/> ".nl2br($tik['msg'])."




<hr/>
";

//// переписка в тикете
echo "<br/><font color=#407C61><b>Переписка:</b></font><hr/>";



if ($tik['closed']==1){
echo "<p><span style=color:#E12222;><b>Топик закрыт</b></span></p><hr/>";
}else{

echo "<form method=\"post\" action=\"?go=tk&id=$_GET[id]\">";
echo "Текст сообщения:<br/>";
echo "<textarea name=\"text\" rows=3></textarea><br/>";
echo "<input type=\"submit\" value=\"Отправить\" class=\"ibutton\"></form><hr/>";
}
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
$avt = mysql_num_rows($req);

if($avt>=1)
{

While($tk = mysql_fetch_array($req))
{
$us = mysql_query("SELECT * FROM `users` WHERE usr = '$tk[usr]' LIMIT 1");
$usr = mysql_fetch_array($us);
if ($usr['dostup']>=4)						{$color = '<font color=lime>'; $color2 = '<font color=#5e995c>';}
if ($usr['dostup']==2 or $usr['dostup']==3)	{$color = '<font color=#0026FF>'; $color2 = '<font color=#6DC2FF>';}
if ($usr['dostup']==1) 					{$color = '<font color=#7F6A00>'; $color2 = '<font color=#A09353>';}
if ($usr['dostup']==0) 					{$color = ''; $color2 = '';}

echo "<a href=\"search.php?nick=$tk[usr]&amp;go=go\">$color $tk[usr] </font></a> <font color=grey><small>$tk[data]</small></font><br/>
$color2 ".nl2br($tk['text'])."</font><hr/>
";
}

	navig($page, 'tiket.php?go=tk&amp;id='.$_GET['id'].'&amp;', $pages);

}else{
echo "Сообщений нет<hr/>";}

//////////////////////////////////


echo "</div>";
echo"<br/><a href=\"?\">Назад</a>";

break;

//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------
//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------//-------------------------------------------------------------------

}
echo'</div>';

include($path.'inc/down.php');
?>









