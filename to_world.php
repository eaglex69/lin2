<?
define('PROTECTOR', 1);

include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');

/////////место
$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
$mestouser = mysql_fetch_array($req);

if($mestouser[city]==0){
mysql_query("UPDATE `mesto` SET `city` = '1' WHERE `usr` = '$log'");     
header("Location: okrestnosti.php?");exit;
}else{
place_city();
place_okr();
place_zamok();
place_tower();
}
?>
