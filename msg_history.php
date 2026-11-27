<?php
define('PROTECTOR', 1);

$headmod = 'msg';//фикс. места

$textl='Письма';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
if (!empty($_POST['nick'])){$_GET['n'] = $_POST['nick'];}
if (empty($_GET['n'])){
echo "<form action=\"msg_history.php?\" method=\"post\">Переписка с игроком:<br/>";
echo "<input type=\"text\" value=\"$_GET[n]\" name=\"nick\"><br/>";
echo "<input type=\"submit\" value=\"Далее\" class=\"ibutton\"></form>";
include($path.'inc/down.php');
}else{
$pers = $_GET['n'];
echo "<p><font color=#057F46><b>Переписка с $_GET[n]:</b></font></p><hr/>";
function smilesmsg($string54545){
$dir = opendir ("pic/smiles"); 
while ($file = lin2_readdir ($dir)) {
if (preg_match('/\.gif$/', $file)){
$file2=str_replace(".gif","",$file);
$string54545=str_replace(":$file2",'<img src="pic/smiles/'.$file.'" alt="" height="30" width="30">',$string54545);
}}
lin2_closedir ($dir);
return $string54545;  }
$result = mysql_result(mysql_query("SELECT COUNT(*) FROM `msg_r` WHERE user_to = '$log'"),0);
$result2 = mysql_result(mysql_query("SELECT COUNT(*) FROM `msg_i` WHERE user_to = '$pers'"),0);
if ($result == 0 or $result2 == 0)
{
echo "<b>Почта пуста!</b><br/>";
}else{
if (!is_numeric($_GET['page']) || $_GET['page'] < 0 || $_GET['page'] == "0") 
{
$_GET['page'] = 0;
}
$next = $_GET['page'] + 1;
$back = $_GET['page'] - 1;
$num = $_GET['page'] *10;
if($_GET['page'] == "0")
{$i = 0;}
else{$i = ($_GET['page']*10)+1;}
$viso = mysql_num_rows(mysql_query("SELECT komentaras FROM komentarai"));
$puslap = floor($viso/10);
$message = mysql_query("SELECT * FROM msg_r WHERE user_to = '$log'  ORDER BY id DESC LIMIT 10");
$message2 = mysql_query("SELECT * FROM msg_r WHERE user_from = '$pers'  ORDER BY id DESC LIMIT 10");
while ($msg = mysql_fetch_array($message) or $msg2 = mysql_fetch_array($message2))
{
if ($msg['read'] == 1){
mysql_query("UPDATE `msg_r` SET `read` = 0 WHERE `user_to` = '$log'");
}
if ($msg['read'] == 1){
$read = "<font color=red>Не прочитано<br/></font>";
} else{
$read = "";
}
$text = $msg['mail_msg'];
$text = smilesmsg($text);
$text = nl2br($text);
echo'<b><font color=grey>Текст:</font></b>';
$text = strip_tags($text); 


}

}
}
include($path.'inc/down.php');
?>