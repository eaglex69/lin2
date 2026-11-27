<?
define('PROTECTOR', 1);

$textl='Востановление пароля';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');

echo'<div class="gameBorder"><div class="menu">';
switch($_GET[go]){

default:


echo "<form action=\"password.php?$sd&go=go\" method=\"post\">Ведите ваш ник:<br/>";
echo "<input class=\"button\" name=\"name\" maxlength=\"11\" title=\"name\"/><br/>";
echo "Ведите e-mail указанный при регистрации:<br/>";
echo "<input class=\"button\" name=\"email\" maxlength=\"50\" title=\"email\"/><br/>";
echo "<input class=\"button\" type=\"submit\" value=\"Востановить\" /></form>";

break;

case 'go':
$name=$_POST[name];
if(!empty($name)){


$req = mysql_query("SELECT * FROM `users` WHERE `usr` = '$name'");
$usr = mysql_fetch_array($req);


$mail=$usr[email];
}else{echo'Вы не ввели необходимые данные<br/>';
echo "</p></body></html>"; exit;}

if($_POST[email]!==$mail || $mail=="" || $mail=="@") {echo'Вы ввели несоответствующие данные, пароль не может быть востановлен!<br/>';
echo "</p></body></html>"; exit;} else {





$ip = $_SERVER['REMOTE_ADDR'];
$agent = strtok($HTTP_USER_AGENT,'/');
$ip=htmlspecialchars(stripslashes($ip));
$agent=htmlspecialchars(stripslashes($agent));
$now= date("j-m-Y");
$curr=date("H:i:s", time());

$agent = "pass@$_SERVER[HTTP_HOST]";

include_once "inc/function.php";

//---------------------------
$EOL="\r\n";
$subj='Lineage II - Password';
$bound = "--".md5(uniqid(time()));

$headers="From: \"pass@$_SERVER[HTTP_HOST]\" <pass@$_SERVER[HTTP_HOST]>$EOL";
$headers.="To: pass@$_SERVER[HTTP_HOST]$EOL";
$headers.="Subject: $subj$EOL";
$headers.="Mime-Version: 1.0$EOL";
$headers.="Content-Type: multipart/mixed; boundary=\"$bound\"$EOL";

$body="--$bound$EOL";
$body.="Content-Type: text/plain; charset=\"utf-8\"$EOL";
$body.="Content-Transfer-Encoding: 8bit$EOL";
$body.=$EOL;
$body.="Автоматическая отправка пароля:$EOL";
$body.="Ник: $usr[usr] $EOL";
$body.="Пароль: $usr[ps] $EOL";
$body.="$EOL";
$body.="Ссылка для автовхода:$EOL http://l-nw.ru/avt.php?logi=$usr[usr]&pass=$usr[ps] $EOL$EOL";
$body.="На письмо отвечать не нужно.$EOL";
//----------------------------


mail($usr[email],$subj, $body, $headers);




echo '<p>Пароль востановлен успешно!<br/>Ваши  данные высланы на e-mail указанный в при регистрации.</p><br/>';


//echo '<small>Пароль востановлен успешно!<br/>Ваши новые данные высланы на e-mail указанный в при регистрации.</small>';




}
break;
}

echo "<br/> <a href=\"index.php?\">Назад </a><br/>";


echo'</div></td>
<td class="r"></td>
</tr>
<tr>
<td class="lb"></td>
<td valign="top" class="bb"></td>
<td class="rb"></td>';
echo "</tr></table></body></html>";

//--------------------------------
include_once("inc/endmain.php");
//--------------------------------
?>


