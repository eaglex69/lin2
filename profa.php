<?
define('PROTECTOR', 1);

$headmod = 'prof';//фикс. места

$textl='Професии';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
place_okr();
place_zamok();
place_tower();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

//---------------------
$n=$_GET[n];
//---------------------

switch($_GET[mod]){

default:


if ($udata[klas]=="wizard"){$klas="mage";$klas2="Маг";}
if ($udata[klas]=="fighert"){$klas="Fighter";$klas2="Воин";}
if ($udata[prof]=='') {$udata[prof]="no";}
if ($udata[prof]=='' or $udata[prof]=='no') {$prof="Нет изученых проф.";}else{$prof=$udata[prof];}

if ($udata[storona]=="ork"){$storona2="Орк";}
if ($udata[storona]=="elf"){$storona2="Cветлый Эльф";}
if ($udata[storona]=="darkelf"){$storona2="Темный Эльф";}
if ($udata[storona]=="human"){$storona2="Человек";}
if ($udata[storona]=="gnom"){$storona2="Гном";}
if ($udata[storona]=="kamael"){$storona2="Камаэль";}


if ($udata[prof]=='' or $udata[prof]=='no') {echo"Нет изученых професий.";}else{
echo "Ваша професия  <a href=\"profa.php?mod=info&n=$prof\"><b>$prof</b></a>. ";}





// отображает в зависимости от професии перса
	$req = mysql_query("SELECT * FROM `profa` WHERE 
		`storona`='$udata[storona]' and 
		`klas`='$klas' and `next`='$udata[prof]'");
	//$req = mysql_query("SELECT * FROM `profa` WHERE  `next`='$udata[prof]'");


		$avto = mysql_num_rows($req);
	
	
	if($avto>=1){
	
	echo "<p><b>Вы можете изучить професии:</b></p>";

	While($mag = mysql_fetch_array($req))
	{
	echo" <a href=\"profa.php?mod=prof&id=$mag[id]\">$mag[name]</a> <br/>";
	}}
	else{echo "<b><p>Вы выучили все професии!</p></b>";}

	echo"<br/><a href=\"gorod.php?\">В город</a>";



			
			
	break;		


//---------------------------------------------------------//---------------------
//  	----------	Получение ПРОФЕСИИ			-----------//******

case 'prof':

$req = mysql_query("SELECT * FROM `profa` WHERE `id`='$_GET[id]'");
$prof = mysql_fetch_array($req);

//$prof=explode("|",$mag[info]);
##############################################################
// защита от чужих проф

if ($udata[klas]=="wizard"){$udata[klas]="mage";}
if ($udata[klas]=="fighert"){$udata[klas]="Fighter";}
if ($udata[prof]==""){$udata[prof]="no";}


if ($prof[next]==$udata[prof] && $prof[storona]==$udata[storona] && $prof[klas]==$udata[klas])
		{
		
		if($_GET[act]!='by'){


$req = mysql_query("SELECT * FROM `profa` WHERE `id`='$_GET[id]'");
$mag = mysql_fetch_array($req);

$maginf=explode("|",$mag[info]);

if ($maginf[5]!=''){$inf5 = "<b>$maginf[4]</b>$maginf[5]<br/>";}

echo "<b>	$maginf[0]		<br/></b> 
			$maginf[1]		<br/>
	<b>		$maginf[2]		</b>
			$maginf[3]		<br/> $inf5 <br/>";


if ($udata[lvl]>=$prof[lvl])		{echo"<br/><a href=\"profa.php?mod=prof&amp;act=by&amp;id=$_GET[id]\"><b>Получить</b></a>";}
else {echo "<b>Старик:</b><br/><font color=green><b>Професия будет доступна с $prof[lvl] уровня. </b></font>";}
			echo "<br/><a href=\"profa.php?\">Назад</a>";
	
								
							}else
							
								{
if ($udata[lvl]<$prof[lvl]) {
	echo "<b>Старик:</b><br/>У Вас мал уровень!!!<br/> 
	Будешь $prof[lvlmax] уровня приходи...<br/>";
	echo"<br/><a href=\"profa.php?\"><b>Професии</b></a>";
	include($path.'inc/down.php');
	exit;
}
	
$res = mysql_query ("UPDATE users SET
        prof='$prof[name]'
		 WHERE usr='$log' LIMIT 1");


					if ($res == 'true')
					{
					echo "Профа $prof[name] получена. ";
					}				
					else
					{
					echo " Неудача. Вернитесь и повторите запрос. После 5 неудачных попыток обратесь к Администрации.";  // неудачно =)
					}
				
									echo"<br/><br/><a href=\"profa.php?\"><b>Професии</b></a>";

								}
									
			
	

	include($path.'inc/down.php');
	exit;
		}
else{

//echo"<p><b>$prof[next]==$udata[prof] && $prof[storona]==$udata[storona] && $prof[klas]==$udata[klas]</b></p>";

echo'<font color=red>Ошибка!</font> Вернитесь и повторите еще раз! Возможно Вы уже получили эту професию.';
			echo "<br/><br/><a href=\"profa.php?\">Назад</a>";

include($path.'inc/down.php');
exit;
}
//else{echo "Ошибка с профой. Обратитесь к Администратору!";}

///////////////////////



echo "Ошибка с профой. Обратитесь к Администратору!";
//echo"<br/><a href=\"profa.php?mod=prof&amp;act=by&amp;id=$_GET[id]\"><b>Получить</b></a>";



break;



	
//---------------------------------------------------------//******
//  	----------		ИНФА О ПРОФЕСИИ			-----------

case 'info':
$req = mysql_query("SELECT * FROM `profa` WHERE `name`='$n'");
$mag = mysql_fetch_array($req);

$maginf=explode("|",$mag[info]);


if ($maginf[5]!=''){$inf5 = "<b>$maginf[4]</b>$maginf[5]<br/>";}

echo "<b>	$maginf[0]		<br/></b> 
			$maginf[1]		<br/>
	<b>		$maginf[2]		</b>
			$maginf[3]		<br/> $inf5";


			echo "<a href=\"profa.php?\">Назад</a>";
break;
//		-------			--------			----------	---
//---------------------------------------------------------
}
include($path.'inc/down.php');
?>