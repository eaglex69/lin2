<?
/*
define('PROTECTOR', 1);

$headmod = 'sab';//фикс. места

$textl='Саб класс';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');




switch($_GET[mod]){

default:

if ($udata[lvl]<40){echo "<font color=red> Саб класс доступен с 40 уровня. </font>";include($path.'inc/down.php');}


//---------------------------------------------------------//******
//  	----------		ИЗМЕНЕНИЕ САБ КЛАССА			-----------

if (isset($_GET[go]) and !empty($_POST[prof])){


$req = mysql_query("SELECT * FROM `profa` WHERE `next`='$_POST[prof]'");// ..грузим професии
$prof = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `profa` WHERE `next`='$prof[name]'");// ..грузим професии
$prof2 = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `profa` WHERE `next`='$prof2[name]'");// ..грузим професии
$prof3 = mysql_fetch_array($req);

$req6549874655 = mysql_query("SELECT usr,prof FROM `users` WHERE usr='$log' and 
`prof`='$prof[next]' or `prof`='$prof[name]' or 
`prof`='$prof3[next]' or `prof`='$prof3[name]' or 
`prof`='$prof2[next]' or `prof`='$prof2[name]' LIMIT 1");// ..добавить сторону и профу
////////////////////////////
$avto649846=mysql_num_rows($req654987465);
if($avto649846 == 1){$prof = $udata[prof];}else{$prof = $_POST[prof];}




$r = mysql_query("SELECT * FROM `sab` WHERE `prof`='$_POST[prof]' LIMIT 1");
$sabavto=mysql_num_rows($r);
$sab = mysql_fetch_array($r);

if ($sabavto==1){ // уже качал/получал этот саб
mysql_query ("UPDATE sab SET prof_you='$prof',lvl='$udata[lvl], exp='$udata[exp]' WHERE usr='$log' LIMIT 1"); // изменили саб
mysql_query ("UPDATE users SET prof='$sab[prof_you]',lvl='$sab[lvl]',exp='$sab[exp]' WHERE usr='$log' LIMIT 1"); // изменили профу

}else{//получил саб впервые
mysql_query ("INSERT INTO sab SET usr='$log',prof='$_POST[prof]',prof_you='$prof',exp='$udata[exp]',lvl='$udata[lvl]'"); // получил саб
mysql_query ("UPDATE users SET prof='$_POST[prof]',lvl='40',exp='1714758' WHERE usr='$log' LIMIT 1"); // изменили профу

}
}
//		-------			--------			----------	---
//---------------------------------------------------------




$rpr = mysql_fetch_array(mysql_query("SELECT prof FROM `users` WHERE `usr`='$log' LIMIT 1"));

if ($rpr[prof]=='' or $rpr[prof]=='no') {echo"Нет изученых професий.";}else{
echo "<p>Ваша професия  <a href=\"sab.php?info=$rpr[prof]\"><b>$rpr[prof]</b></a>.</p>";}
//---------------------------------------------------------//******
//  	----------		ИНФА О ПРОФЕСИИ			-----------

if (isset($_GET[info])){
$req = mysql_query("SELECT * FROM `profa` WHERE `name`='$_GET[info]'");
$mag = mysql_fetch_array($req);
if (!empty($mag)){echo "<div class=dot>";
$maginf=explode("|",$mag[info]);


if ($maginf[5]!=''){$inf5 = "<b>$maginf[4]</b>$maginf[5]";}

echo "<b>	$maginf[0]		<br/></b> 
			$maginf[1]		<br/>
	<b>		$maginf[2]		</b>
			$maginf[3]		<br/> $inf5 ";
echo " <a href=\"sab.php?\">[-]</a></div><br/>";
}
}
//		-------			--------			----------	---
//---------------------------------------------------------
/*<option value=\"Warrior\">Warrior</option>
<option value=\"Knight\">Knight</option>
<option value=\"Rogue\">Rogue</option>
<option value=\"Human Wizard\">Human Wizard</option>
<option value=\"Cleric\">Cleric</option>
<option value=\"Raider\">Raider</option>
<option value=\"Monk\">Monk</option>
<option value=\"Shaman\">Shaman</option>
<option value=\"Scavenger\">Scavenger</option>
<option value=\"Artisan\">Artisan</option>
<option value=\"Elf Knight\">Elf Knight</option>
<option value=\"Scout\">Scout</option>
<option value=\"Elf Wizard\">Elf Wizard</option>
<option value=\"Oracle\">Oracle</option>
<option value=\"Palus Knight\">Palus Knight</option>
<option value=\"Assassin\">Assassin</option>
<option value=\"Dark Elf Wizard\">Dark Elf Wizard</option>
<option value=\"Shillien Oracle\">Shillien Oracle</option>
*/
	
	echo "<hr/><p><b><font color=#76ABC7>Професии от саб классов:</font></b></p>";

echo "<form action=\"?go\" method=\"post\">";
		$req = mysql_query("SELECT * FROM `profa`");
		$avto = mysql_num_rows($req);
	if($avto>=1){
		echo "<select name=\"prof\">
<option value=\"\"></option>";

	While($mag = mysql_fetch_array($req))
	{

	
echo "<option value=\"$mag[name]\">$mag[name]</option>";

	}	echo "</select>";
}
echo '<br/><input class="button" type="submit" value="Получить" /></form>';

	
	echo"<br/><div class=inoy><a href=\"gorod.php?\">В город</a></div>";

break;		


}

include($path.'inc/down.php');
?>