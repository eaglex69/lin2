<?
define('PROTECTOR', 1);

$headmod = 'clan';//фикс. места

$textl='Клан';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
if(empty($_GET[id])){echo"Невыбран клан"; include($path.'inc/down.php'); exit;}
$req = mysql_query("SELECT * FROM `clan` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==1){
$clan = mysql_fetch_array($req);
$req = mysql_query("SELECT usr FROM `users` where `clan`='$clan[lider]'");
////////////////////////////
$kk=mysql_num_rows($req);
}else{
echo"Невыбран клан";include($path.'inc/down.php'); exit;
}
switch($_GET[mod]){

default:


if(empty($clan[logo])){
$logo = "<img src='pic/pusto.png' align='left' width='50' height='50' alt='' style='margin-right:10px;border:1px solid #383838'>";
}else{
$logo = "<img src='pic/clan_logo/$clan[logo]' align='left' width='50' height='50' alt='' style='margin-right:10px;border:1px solid #383838'>";
}
if(!empty($clan[emblema])){
$embl = "<img src=\"pic/clan/$clan[emblema]\" alt=\"clan\"/> ";}

// уровень и опыт клана
include($path.'inc/clan_lvl.php');
include($path.'inc/clan_lvl_2.php');
include($path.'inc/clan_opit.php');


echo "$logo";
echo "$embl <font color=silver><b>$clan[name]</b></font><br/> 
<font color=#E2E2E2><b>Опыт:</b> ".number_format($clan[exp], 0, ',', "`")."<small> / </small>".number_format($clan_new_exp, 0, ',', "`")." </font> <font color=grey><b>($expcl%)</b></font><br/>
Уровень: $clan[lvl] $pic<br/><br/>";
// полоса jgsnf
echo '<div style="border:1px solid #7F3300;background:1px #282828;height:3px;width:100%;"><div style="background-color:#FF6A00;height:3px;width:'.$expcl.'%;"></div><span style="position:relative;top:-99%;left:1%;"> </font></span></div></small><!--</div>-->';
//////////////////////////////////////////////////
echo"<p>Бойцов: $kk<br/></p>";

$laikas = 150;
$dabar = time();
$timeout = $dabar - $laikas;
$asd = mysql_num_rows(mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeout' AND usr='$clan[lider]'"));
if($asd==0 && $cl[dostup]<3){$stat47 = ' <font color=red> Off</font> ';}else{$stat47 = ' <font color=lime> On </font> ';}

echo"<div class=inoy><a href=\"search.php?nick=$clan[lider]&amp;go=go\"><font color=#ffffff>1.</font> $clan[lider] <font color=grey>[Лидер]</font> $stat47 </a></div>";
$req = mysql_query("SELECT clan,usr,lvl FROM `users` where `clan`='$clan[lider]' and `usr`!='$clan[lider]' ORDER BY lvl DESC");
////////////////////////////
$avto=mysql_num_rows($req);
$i=2;
if($avto>=1){
While($cl = mysql_fetch_array($req))
{

$laikas = 150;
$dabar = time();
$timeout = $dabar - $laikas;
$asd = mysql_num_rows(mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeout' AND usr='$cl[usr]'"));
if($asd==0 && $cl[dostup]<3){$stat47 = ' <font color=red> Off</font> ';}else{$stat47 = ' <font color=lime> On </font> ';}


echo"<div class=inoy><a href=\"search.php?nick=$cl[usr]&amp;go=go\"><font color=#ffffff>$i.</font> $cl[usr]  <font color=#ffffff>[$cl[lvl] lvl]</font> $stat47</a></div>";
$i++;
}
}else{

$laikas = 150;
$dabar = time();
$timeout = $dabar - $laikas;
$asd = mysql_num_rows(mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeout' AND usr='$clan[lider]'"));
if($asd==0 && $cl[dostup]<3){$stat47 = ' <font color=red> Off</font> ';}else{$stat47 = ' <font color=lime> On </font> ';}

echo"<div class=inoy><a href=\"search.php?nick=$clan[lider]&amp;go=go\"><font color=#ffffff>1.</font>$clan[lider] <font color=grey>[Лидер]</font> $stat47 </a></div>";
}
break;
}

include($path.'inc/down.php');
?>