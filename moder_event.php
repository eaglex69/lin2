<?php
define('PROTECTOR', 1);

$headmod = 'moder_event';//фикс. места

$textl='Технические новости';
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


echo "<font color=#f4d06e>Эвенты модеров!</b></font></p>";



//-------------------------------------- Удаление коментариев ---------------------------------------------------------
if (isset($_GET[del])){


if($udata[dostup] >= 2)
{
if(empty($_GET[del])){
echo"<hr/><font color=#ff4040><p>Не выбран пост!</p></font>";
}else{
$asd = mysql_query("SELECT * FROM moder_event_kom WHERE id='$_GET[del]' LIMIT 1");
$avto=mysql_num_rows($asd);
if($avto==0){
echo'<hr/><font color=#ff4040><p>Нет такого поста!</p></font>';
}else{
mysql_query("DELETE FROM `moder_event_kom` WHERE id='$_GET[del]' LIMIT 1");
echo'<hr/><font color=#ff4040><p>Пост успешно удалён!</p></font>';
}
}
}else{
echo "Ошибка!Доступ закрыт!";
}

}






//////////////////////////////		Коментарии	/////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_GET[kom])){


//-------------------------------------------
if (!empty($_POST[text])) // если естьтекст записуем сообщение
{

$msg = $_POST[text];
$msg = htmlspecialchars(stripslashes(trim($msg)));
$log = htmlspecialchars(stripslashes(trim($log)));
$currHour=date("H",time());
$currDate=date("d.m.Y", time());
$currTime=date("$currHour:i", time());

// пишем антифлуд
$req6546 = mysql_query("SELECT * FROM `moder_event_kom` WHERE `usr` = '$log' && `id_tm` = '$_GET[kom]' ORDER BY id DESC LIMIT 1");
$pr = mysql_fetch_array($req6546);
if($pr[text] == $msg){
echo'<font color=#9E0000>Антифлуд!</font><br/>';}else{

$ressave = mysql_query("INSERT INTO `moder_event_kom` SET
        `id_tm` = '$_GET[kom]',
        `text` = '$msg',
        `data` = '$currDate в $currTime',
        `usr` = '$log'"); // создаем строку с ответом
		
		

		 
				if 		($ressave == 'true')	{echo "<hr><font color=#f4d06e>Сообщение добавленно!</font>";}
				else	{echo "<font color=#ff4040><p> Неудача ! </p></font>";}  // неудачно =)
}
}
//----------------------------------------------





$asd = mysql_query("SELECT * FROM `moder_event` WHERE `id` = '$_GET[kom]' LIMIT 1");
$dsa = mysql_fetch_array($asd);

$title = strip_tags($dsa['title']);
$time = strip_tags($dsa['time']);
echo "<hr/><font color=#f4d06e>$title </b><small>
<font color=#af9878> | $time</small><br/></font>
<font color=#ffffcc>$text ".$dsa['text']."</font><br/>";
echo'<font color=#f4d06e>Автор новости:<font color=#ffffcc> '.$dsa['nick'].'<hr/>';

//----cчётчик комов
$count2 = mysql_result(mysql_query("SELECT COUNT(*) FROM `moder_event_kom` WHERE `id_tm` = '$_GET[kom]'"), 0);


echo "<p><font color=#f4d06e>Коментарии <font color=#ffffcc>($count2):</font></p>";


echo "<form method=\"post\" action=\"moder_event.php?kom=$_GET[kom]\">";
echo "Текст сообщения:<br/>";
echo "<textarea name=\"text\" rows=3></textarea><br/>";
echo "<input type=\"submit\" value=\"\" class=\"button_medium3\"></form><hr/>";



//---выщитует страницы----------

	$count = mysql_result(mysql_query("SELECT COUNT(*) FROM `moder_event_kom` WHERE `id_tm` = '$_GET[kom]'"), 0);
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

$req = mysql_query("SELECT * FROM `moder_event_kom` WHERE id_tm = '$_GET[kom]' ORDER BY id DESC LIMIT $from, 10");
$avt = mysql_num_rows($req);

if($avt>=1)
{


function smilesmsg($string54545){
$dir = opendir ("pic/smiles"); 
while ($file = readdir ($dir)) {
if (ereg (".gif$", "$file")){
$file2=str_replace(".gif","",$file);
$string54545=str_replace(":$file2",'<img src="pic/smiles/'.$file.'" alt="" height="30" width="30">',$string54545);
}}
closedir ($dir);
return $string54545;  }



While($tk = mysql_fetch_array($req))
{
$us = mysql_query("SELECT * FROM `users` WHERE usr = '$tk[usr]' LIMIT 1");
$usr = mysql_fetch_array($us);
if ($usr[dostup]>=4)						{$color = '<font color=lime>'; $color2 = '<font color=#ffffcc>';}
if ($usr[dostup]==2 or $usr[dostup]==3)	{$color = '<font color=#547ef4>'; $color2 = '<font color=#ffffcc>';}
if ($usr[dostup]==1) 					{$color = '<font color=#7F6A00>'; $color2 = '<font color=#ffffcc>';}
if ($usr[dostup]==0) 					{$color = ''; $color2 = '';}


if($udata[dostup]>=2){
$silka = " <a href=\"moder_event.php?kom=$_GET[kom]&amp;del=$tk[id]\"><font color=#ff4040><small> [x] </small></font></a>";
}


$text = $tk[text];

$text = smilesmsg($text);
$text = nl2br($text);

echo "<div class=msg><a href=\"search.php?nick=$tk[usr]&amp;go=go\">$color $tk[usr] </font></a> <font color=grey><small>$tk[data]</small></font> $silka </div>
<div class=msg>
$color2 $text</font></div><hr/>";
}

echo "<div class=dot><p>";
	navig2($page, 'moder_event.php?kom='.$_GET[kom].'&amp;', $pages);
echo "</p></div>";
	
}else{
echo "Сообщений нет<hr/>";}

//////////////////////////////////





echo"<br/><div class=silka><a href=\"/moder_event.php?\">Назад</a></div>";

include($path.'inc/down.php');

}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


if ($_GET[page] == "" || $_GET[page] < 0 || $_GET[page] == "0") 
{
$_GET[page] = 0;
}
$next = $_GET[page] + 1;
$back = $_GET[page] - 1;
$num = $_GET[page] * 10;
if($_GET[page] == "0")
{$i = 1;}
else{$i = ($_GET[page]*10)+1;}
$viso = mysql_num_rows(mysql_query("SELECT title FROM moder_event"));
$puslap = floor($viso/10);
if (is_double($num) || !is_integer($num))
{
echo "Ошибка!<br/>";
}
else {
$asd = mysql_query("SELECT id,title, text,nick,time FROM moder_event ORDER BY id DESC LIMIT $num,10");
while($dsa = mysql_fetch_array($asd))
{
$i2 = $i++;
$title = strip_tags($dsa['title']);
$time = strip_tags($dsa['time']);
echo "<hr/><font color=#f4d06e>$title </b><small>
<font color=#af9878> | $time</small><br/></font>
<font color=#ffffcc>$text ".$dsa['text']."</font><br/>";
echo'<font color=#f4d06e>Автор новости: <font color=#ffffcc>' .$dsa['nick'].' </a>';

$count2 = mysql_result(mysql_query("SELECT COUNT(*) FROM `moder_event_kom` WHERE `id_tm` = '$dsa[id]'"), 0);

echo "<div class=silka><a href=\"moder_event.php?kom=$dsa[id]\"><font color=#f4d06e>Коментарии <font color=#ffffcc>($count2)</font></a></div>";


}
//echo '<br/>';
echo '<hr/>';
if ($_GET[page] > 0)
{
echo "<a href=\"moder_event.php?page=$back\"> Назад</a>";
}
elseif ($_GET[page] == 0)
{
echo "<font color=#ffffcc>Назад ";
}
echo"|";
if($_GET[page] < $puslap || $_GET[page] == "" || $_GET[page] == 0)
{echo "<a href=\"moder_event.php?page=$next&\">Далее </a><br/>";}
else
{echo "Далее <br/>";}
}
echo"<a href=\"index.php?\">Назад</a>";

include($path.'inc/down.php');
?>
