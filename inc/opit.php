<?php


$asd = $mysql->num_rows($mysql->query("SELECT * FROM lvl")); // сколько уровней
$lv = $mysql->super_query("SELECT * FROM lvl WHERE `lvl` = '".$udata['lvl']."' LIMIT 1"); // бало

$ln = $udata['lvl']-1;
$lvnext = $mysql->super_query("SELECT * FROM lvl WHERE `lvl` = '".$ln."' LIMIT 1"); // нужно для нового

$exp=round(lin2_div(($udata['exp']-$lvnext['exp']),($lv['exp']-$lvnext['exp']))*100,2);

if($exp>"100"){ $exp="100";}

if($udata['lvl']>=$asd){ $exp="100";}




?>
