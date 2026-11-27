<?php
defined('PROTECTOR') or die('Error: restricted access');





$date = time();

$be=$mysql->super_query("SELECT * FROM regenerator WHERE usr = '".$log."' LIMIT 1");
$k_point=intval((time()-$be['last'])/1);
if($k_point>=1){
if($in_battle==0 and $inpk==0 and $inar==0){
$newhp=$udata['hp']+(1*$k_point);
$newmp=$udata['mp']+(1*$k_point);
if($newhp>$udata['hpall']){$newhp=$udata['hpall'];}
if($newmp>$udata['mpall']){$newmp=$udata['mpall'];}
$mysql->query("UPDATE users SET hp = '".$newhp."',mp='".$newmp."' WHERE usr = '".$log."' LIMIT 1");
$mysql->query("UPDATE regenerator SET last = '".$date."' WHERE usr = '".$log."' LIMIT 1");}
}
?>