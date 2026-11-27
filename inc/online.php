<?php
defined('PROTECTOR') or die('Error: restricted access');

//-------------------------------------

 //-------------------------------------

$laikas = 150;
$dabar = time();
$timeout = $dabar - $laikas;

$dabar = $dabar+$kol+1800;//+30 ����� �������

$mysql->query("DELETE FROM `online`	 WHERE `laikas` < '".$timeout."' ");
$ar_yra = $mysql->num_rows($mysql->query("SELECT `usr` FROM `online` WHERE usr = '".$log."' LIMIT 1"));
if ($ar_yra == '0')
{
$mysql->query("INSERT INTO online SET usr = '".$log."', laikas = '".$dabar."',`lvl`='".$udata['lvl']."'");
}
elseif($ar_yra > '0')
{
$mysql->query("UPDATE online SET laikas = '".$dabar."',`lvl`='".$udata['lvl']."' WHERE usr = '".$log."' LIMIT 1");
}

/*
If ($_GET[newtm]==258){
$minus = 19*60;

$newtm = time()+86400-$minus;
mysql_query("UPDATE `time` SET `eve_mob` = '$newtm'"); //  �������� �������� ����� �������




$newtm = time()+86400-$minus;
mysql_query("UPDATE `time` SET `eve_fish` = '$newtm'"); //  �������� �������� ����� �������

echo "$newtm - $minus";
}
*/
?>
