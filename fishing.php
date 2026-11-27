<?php
define('PROTECTOR', 1);

$textl='Рыбалка';
///////////////////////
//	$path='../';			//
//////////////////////
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
///////////////


switch($_GET['f']){
default:
//-
$data = date("d F Yг. в H:i", strtotime("+20 seconds"));
//-
$data = str_replace("January","Января",$data);
$data = str_replace("February","Февраля",$data);
$data = str_replace("March","Марта",$data);
$data = str_replace("April","Апреля",$data);
$data = str_replace("May","Мая",$data);
$data = str_replace("June","Июня",$data);
$data = str_replace("July","Июля",$data);
$data = str_replace("August","Августа",$data);
$data = str_replace("September","Сентября",$data);
$data = str_replace("October","Октября",$data);
$data = str_replace("November","Ноября",$data);
$data = str_replace("December","Декабря",$data);

//===============================================================================================================//

//-----истории рыбалки--------
$sto = rand(1,5);
if ($sto==1){$story = "Эх... Однажды на рыбалке, сидя в лодке, решил перекусить. 
Положил удочку и за бутерброд....тут как клюнет...Хотя, я наверное, расказывал эту историю тебе? Так, что тебе нужно?";}
if ($sto==2){$story = "Однажды с другом, пошли на рыбалку и по дороге, снизу реки, смотрим лиса!!.... Ой, прости, что то я заболтался !!! Зачем пожаловал то?";}
if ($sto==3){$story = "Не люблю плохую рыбалку, но, если вспомнить, такой у меня ни разу небыло ! Что тебе ?";}
if ($sto==4){$story = "Как же хорошо на рыбалке... Свежый воздух...тишина.... Тоже собрался порыбачить?";}
if ($sto==5){$story = "Помню, помню.... Кажеться ты тут бывал уже... А может ошибаюсь! Ну и...зачем пришёл?";}
//-------------------------------------
echo "<p><font color=#f4d06e><b>Рыбалка ...</b></font></p>";



echo "
<img src='pic/fisher.png' align='left' width='89' height='116' alt='' style='margin-right:10px;border:1px solid #383838' >


<font color=#f4d06e><br/>Рыбак:</b></font>
<font color=#f4d06e>
<hr/><br/>
$story
</font>
<br/><br/>
<hr/>
</div>
</div>
<hr/>";

echo"<div class=inoy>";
echo"<a href=\"?f=ud\">Купить удочку</a>";
echo"<a href=\"?f=lur\">Купить наживку</a>";
echo"<a href=\"?f=r\">Рейтинг рыбаков</a>";
echo"</div>";

break;

//===============================================================================================================//

case 'ud':

if (isset($_GET['t'])){

if ($_GET['t']==1){$name='Baby Duck Rod';$patt=10; $matt=10; $nlvl=0;$cena=50;}
if ($_GET['t']==2){$name='Albatross Rod';$patt=100; $matt=100; $nlvl=40;$cena=100;}
if ($_GET['t']==3){$name='Pelican Rod';$patt=300; $matt=300; $nlvl=62;$cena=150;}
if ($_GET['t']==4){$name='KingFisher Rod';$patt=650; $matt=610; $nlvl=96;$cena=210;}

$colnew=$udata['almaz']-$cena;
if ($colnew>=0){
// --записуем удочку
mysql_query("INSERT INTO
        `item` SET
        `usr` = '$log',
        `tip` = 'weapon',
        `ruka` = 'fish',
        `name` = '$name',
        `cena` = '487214',
        `patt` = '$patt',
        `matt` = '$matt',
        `pdef` = '0',
        `mdef` = '0',
        `time` = '$mag[time]',
        `soul` = '0',
        `spirit` = '0',
        `nlvl` = '$nlvl',
        `image` = 'not'");
mysql_query("UPDATE `users` SET `almaz` = '$colnew' WHERE `usr` = '$log' LIMIT 1");
//--------------------------

echo "<p><font color=#f4d06e><b>Удочка $name куплена!</b></font></p></div>";
echo"<div class=inoy><a href=\"?\"> К рыбаку </a></div>";
}else{
echo "<p><font color=#ff4040><b>Не хватает Coin of Luck!</b></font></p></div>";
echo"<a href=\"?\"> К рыбаку </a>";
}

}else{



echo "<p><font color=#f4d06e>Купить удочку:</b></font><font color=#ffffcc>Нажав на удочку, вы её купите...</font></p><hr/>";

echo"<div class=inoy>";
echo"<a href=\"?f=ud&t=1\"> <img src=\"shmot/Baby Duck Rod.png\" height=30 width=30> Baby Duck Rod <font color=#ffffcc> [50 CoL] </font></a>";
echo"<a href=\"?f=ud&t=2\"> <img src=\"shmot/Albatross Rod.png\" height=30 width=30> Albatross Rod <font color=#ffffcc> [100 CoL] </font></a>";
echo"<a href=\"?f=ud&t=3\"> <img src=\"shmot/Pelican Rod.png\" height=30 width=30> Pelican Rod <font color=#ffffcc> [150 CoL] </font></a>";
echo"<a href=\"?f=ud&t=4\"> <img src=\"shmot/KingFisher Rod.png\" height=30 width=30> KingFisher Rod <font color=#ffffcc> [210 CoL] </font></a>";
echo"</div>";

}

break;

//===============================================================================================================//

case 'lur':

if (isset($_GET['t'])){
$colnew=$udata['almaz']-1;
$co = 100; // количество наживки
if ($colnew>=0){
//----------------------------------- пишем в инвентарь ----------------------
$req = mysql_query("SELECT * FROM `res` WHERE `usr` = '$log' and `lat_name` = 'lure'");
$res=mysql_num_rows($req);
$rs = mysql_fetch_array($req);
if($res==0){
mysql_query("INSERT INTO
        `res` SET
        `usr` = '$log',
        `name` = 'Lure',
        `lat_name` = 'lure',
        `tip` = 'res',
        `what` = 'lure',
        `give` = '0',
        `kol` = '$co',
        `cena` = '250000'");
$nk=$rs['kol']+$co;
echo "<p><font color=#f4d06e>Вы купили $co Lure. У Вас теперь $nk Lure.</font></p>";
}else{
$nk=$rs['kol']+$co;
mysql_query("UPDATE `res` SET `kol` = '$nk' WHERE `usr` = '$log' and `lat_name` = 'lure'");
echo "<p><font color=blue>Вы купили $co Lure. У Вас теперь $nk Lure.</font></p>";

}
mysql_query("UPDATE `users` SET `almaz` = '$colnew' WHERE `usr` = '$log' LIMIT 1");

//------------------------------------------------------------------------------------
}else{
echo "<p><font color=#ff4040>Не хватает Coin of Luck</font></p>";
}
}else{

echo "<p><font color=#f4d06e>Купить наживку:</b></font></p>";

echo"<div class=inoy>";
echo"<a href=\"?f=lur&t\"> <img src=\"pic/skr/Lure.gif\" height=30 width=30> Lure x100 <font color=#ffffcc> [1 CoL] </font></a>";
echo"</div>";

}
break;

//===============================================================================================================//

case 'r':
echo "<p><font color=#f4d06e>Рейтинг рыбаков:</b></font></p>";

echo "</div><div class=inoy>";


///////////////
$taim = 150;
$date = time();
$timeout = $date - $taim;
////////////////
   if($_GET['page']<=0){
		$page = 1;
		}else{
		$page = intval($_GET['page']);
		}
		$from = ($page-1)*10;
		
		        $avto = mysql_num_rows(mysql_query("SELECT * FROM `fish_log`"));
            $pages1=$avto/10;
            $pages=round($avto/10);
            if($pages1>$pages){
            $pages=$pages+1;
            }

			if ($avto > 0){

$asd = mysql_query("SELECT * FROM fish_log WHERE ids >= '0' ORDER BY `skoko` DESC LIMIT $from,10");

$i=1;
While($visi = mysql_fetch_array($asd))
{
echo ' ';echo idlog_online($idlog=$visi['usr']).' <font color=#f4d06e> - '.$visi['skoko'].' рыбок</font></div>';

}

echo "</div><hr><p>Страниц: "; navig2($page, '?f=r&', $pages);
}
else
{
echo " Нет рыбаков  </div><br/>";
}

echo "</p>";



echo "<div class=inoy><a href=\"?\">Рыбак</a></div>";


include($path.'inc/down.php');


break;

//===============================================================================================================//



}

echo "<hr/><div class=silka><a href=\"/gorod.php\">В город</a></div>";


include($path.'inc/down.php');
?>