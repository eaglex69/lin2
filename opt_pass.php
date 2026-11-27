<?
define('PROTECTOR', 1);

$headmod = 'opt';//фикс. места

$textl='Настройки';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
///////////////
$taim = 150;
$date = time();
$timeout = $date - $taim;
////////////////



switch($_GET[mod]){

default:
////////////////////////////////////////////////////////////////////////////////
					//  СОХРАНЯЕМ НАСТРОЙКИ //
if ($_GET[mod]==save){


$psstar = md5(htmlspecialchars(stripslashes(addslashes($_POST['psstar']))));
$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$log' and `pass`='$psstar' LIMIT 1");
$avto=mysql_num_rows($req);
if ($avto==0)
		{	
		echo "<font color=red><p> Ошибка ввода пароля! </p></font>";  // неудачно =)
		}else{

$psnew = md5(htmlspecialchars(stripslashes(addslashes($_POST['psnew']))));
$psnew2 = md5(htmlspecialchars(stripslashes(addslashes($_POST['psnew2']))));
if ($psnew != $psnew2)
		{	
		echo "<font color=red><p> Новые пароли не совпадают! </p></font>";  // неудачно =)
		}else{


		
if (empty($psnew))
		{	
		echo "<font color=red><p> Повторите ввод! </p></font>";  // неудачно =)
		}

else{


			
if (strlen($_POST['psnew'])>15) { echo"<font color=red>Максимум 15!</font>";}else{

$ressave = mysql_query ("UPDATE users SET
        ps='$_POST[psnew]',
        pass='$psnew'
		 WHERE usr='$log' LIMIT 1");}
		 
				if ($ressave == 'true')
					{
					echo '<font color=#007F46><p>Настройки сохранены!</p></font>'; // удачно
					}
					else
					{
					echo "<font color=red><p> Неудача ! </p></font>";  // неудачно =)
					}



}}}}
/////////////////////////////////////////////////////////////////////////////////


$reqi2 = mysql_query("SELECT * FROM `users` where `usr`='$log' LIMIT 1");
$user2 = mysql_fetch_array($reqi2);

echo "<div class=menu><b><p>Изменение пароля:</p></b><hr/>";

echo '<form action="opt_pass.php?mod=save" method="post">';


echo "

Старый : <br/>
<input type=\"text\" name=\"psstar\" value=\"\" size=\"20\" maxlength=\"11\"/> <br/>

Новый : <br/>
<input type=\"text\" name=\"psnew\" value=\"\" size=\"20\" maxlength=\"11\"/> <br/>

Повторите новый: <br/>
<input type=\"text\" name=\"psnew2\" value=\"\" size=\"20\" maxlength=\"11\"/> <br/>


";


echo '<input class="button" type="submit" value="Сохранить" /></form>';

echo "</div>";

echo"<div class=silka>		<a href=\"pers.php?\">Назад</a>			</div>";

break;

}
include($path.'inc/down.php');
?>