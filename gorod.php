<?php
define('PROTECTOR', 1);

$headmod = 'gorod';//фикс. места

$textl='Город';
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









if ($udata['karma']>10) // если большая карма
{
echo" <center><img src='roz/karma.jpg'  width='330' height='130' alt='' style='margin-right:10px;border:1px solid #383838' class='dot'></div><hr/>";
echo "<b><font color=#FF6A00>Стой..стой..стой... У тебя слишком большая карма, я не могу пропустить тебя в город. Иди и отмойся в окрестностьх на монстрах.</font><hr/>";

}else{

/////загружаем город
$req = mysql_query("SELECT * FROM `zamok` WHERE `city` = '$udata[city]' LIMIT 1");

////////////////////////////
$avto=mysql_num_rows($req);
if($avto==1){
$city = mysql_fetch_array($req);
if($city['clan']!='not'){
echo'<div class=bt></div>';
$req = mysql_query("SELECT * FROM `clan` WHERE `lider`='$city[clan]'");
//////////////////
$clan = mysql_fetch_array($req);
echo"<font color=\"af9878\"><b><i>Город под контролем клана:</i></b> </font><a href=\"clan.php?id=$clan[id]\">$clan[name]</a>";
echo "<br/>";
echo "<br/>";
}
}
//////////////
$b = mysql_query("SELECT * FROM bazar WHERE `city`='$udata[city]'");
////////////////////////////
$cbaz=mysql_num_rows($b);
$req6546566 = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$city[clan]'");
$wh = mysql_fetch_array($req6546566);
if(!empty($wh['emblema'])){
$pic = "<img src=\"/pic/clan/$wh[emblema]\" alt=\"cl\"/>";}
////////]!='shop_z////------------------------------------	ГОРОД	------------------------------------



if($_GET['pl']!='shop_zone'){
/*include($path.'inc/include.php');*/

echo' <center><font color=#f4d06e><i>Город:</i><b><i> '.$udata['city'].'</i></center></b></div>';
echo "<br/>"; 
echo' <a href="job.php?"><center><img src="pic/gorod/Trof.png" alt=""/> Квест </font></a>';
if($udata['klas']=="wizard"){echo' <a href="akademmag.php?"> <img src="pic/gorod/Shlem.png" alt=""/>  Акад </a>';}
if($udata['klas']=="fighert"){echo'<a href="akademwar.php?"><img src="pic/gorod/Shlem.png" alt=""/>  Акад  </a>';}
echo'<a href="home.php?"><img src="pic/gorod/кланс.png" alt=""/> Клан </a>';
echo'<a href="sklad.php?"> <img src="pic/gorod/Склад.png" alt=""/> Склад </a>';
echo'<a href="hram.php?"> <img src="pic/gorod/Загс.png" alt="">  Загс </a>';
/*echo''.$pic.'<a href="bonus.php?">  bonus</a><br/>';*/
$gallery =mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery`"), 0);
echo'<a href="gallery.php?"><img src="pic/gorod/fotoa.png" alt="">  Фото </a></center>';
if ($udata['city']=='Северный полюс') echo" <center><img src='/pic/roz/gorod.jpg'  alt='' </div>"; 
if ($udata['city']=='Hunter Village') echo" <center><img src='/pic/town/дварвен.png'  alt='' </div>"; 
if ($udata['city']=='Dark Elven Village') echo" <center><img src='/pic/town/даркелф.png'  alt='' </div>";  
if ($udata['city']=='Oren Town') echo" <center><img src='/pic/town/глудио.png'  alt='' </div>"; 
if ($udata['city']=='Orc Village') echo" <center><img src='/pic/town/орк.png'  alt='' </div>"; 
if ($udata['city']=='Elven Village') echo" <center><img src='/pic/town/елвен.png'  alt='' </div>"; 
if ($udata['city']=='Farm Zone') echo" <center><img src='/pic/roz/gorod.jpg' alt='' </div>"; 
if ($udata['city']=='Rune Township') echo" <center><img src='/pic/town/рунее.png'  alt='' </div>"; 
if ($udata['city']=='Kamael Village') echo" <center><img src='/pic/town/даркелф.png'  alt='' </div>"; 
if ($udata['city']=='Talking Island Village') echo" <center><img src='/pic/town/хуман.png'  </div>"; 
if ($udata['city']=='Godard Town') echo" <center><img src='/pic/town/годарт.png'  alt='' </div>"; 
if ($udata['city']=='Gludio Village') echo" <center><img src='/pic/town/глудин.png'  alt='' </div>"; 
if ($udata['city']=='Giran Castle Town') echo" <center><img src='/pic/town/гиран.png' alt='' </div>"; 
if ($udata['city']=='Dwarven Village') echo" <center><img src='/pic/town/гном.png'  alt='' </div>"; 
if ($udata['city']=='Dion Town') echo" <center><img src='/pic/town/дион.png'  alt='' </div>"; 
if ($udata['city']=='Shtutgart Town') echo" <center><img src='/pic/town/даркелф.png'  alt='' </div>"; 
if ($udata['city']=='Aden Castle Town') echo" <center><img src='/pic/town/аден.png'  alt='' </div>"; 
if ($udata['city']=='Casino Royal') echo" <center><img src='/pic/roz/gorod.jpg'  alt='' style='margin-right:10px;border:1px solid #383838' class='dot'></div>"; 
$blog =mysql_result(mysql_query("SELECT COUNT(*) FROM `blog`"), 0); 
echo'<a href="bank.php?"><center><img src="pic/gorod/bank.png" alt=""> Банк </a>';
echo'<a href="portal.php?"> <img src="pic/gorod/телепорт.png" alt=""> ТП </a>';

///////////////////////////////////////////////////////////////////////////////////////////////
/////////// Сколько на арене
$reqav = mysql_query("SELECT * FROM `mesto` WHERE `place` = 'arena'");
////////////////////////////
if(mysql_num_rows($reqav)>=1)
{
While($arena = mysql_fetch_array($reqav))
{

$asd2 = mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeou' and usr = '$arena[usr]'");
$are = mysql_num_rows($asd2);
if ($arer==1){$arer=$arer+$are;}else{$arer=$are;}
}}
if (empty($arer)){$arer=0;}
////////////////////////////////////////////////////////////////////////////////////////////////

echo'<a href="arena.php?"> <img src="pic/gorod/пвп.png" alt=""> ПвП <font color=#ffffff></font></a>';
echo'<a href="pitomec.php?"> <img src="pic/gorod/pitomec.png" alt=""> Пит </a>';
echo'<a href="baf_vip.php?"> <img src="pic/gorod/baff.png" alt=""> ВИП Баф </a></center><br/>';
$d=date('YmdHi');
$q=mysql_query("SELECT * FROM `tot_match` WHERE `s1`='nu' and `date` > $d");
$as=mysql_num_rows($q);
if (empty($as)){$as=0;}
//echo''.$pic.'<a href="/total/index.php?">  Тотализатор</a>('.$as.')<br/><br/>';

//----------------------------------------------------------------------//
echo "<br/>";
echo "<br/>";
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<a href=\"gorod.php?pl=shop_zone\">Торг площадь </a>";
echo "<a href=\"lotomy.php?\">Казино </a>";
echo "<a href=\"to_zamok.php?\">Замок </a>"; 
echo "<a href=\"to_world.php?\">Окра </a>"; 
echo "<a href=\"fish.php?\">Озеро </a>";
}else{

echo'<font color=#f4d06e><i>Торговая площадь города:</i><b><i> '.$udata['city'].' </b></i></div>';
echo "<br/>";
echo' <center><a href="shop_lux.php?"><img src="pic/gorod/magaz.png" alt=""> Люкс Магазин</font></a>';
echo' <a href="shop_vip.php?"><img src="pic/gorod/magaz.png" alt=""> VIP Shop</a>';
echo' <a href="shop_gold.php?"><img src="pic/gorod/magaz.png" alt=""> Gold Bar</a>';
echo' <a href="shopsoul.php?"><img src="pic/gorod/soul.png" alt=""> SoulShots</a>';
echo'<center> <img src="pic/gorod/torg3.png" alt=""></center>';
echo' <a href="bazar_vip.php?"><img src="pic/gorod/magaz.png" alt=""> Базар Шмота</a>';
$cbazvip = mysql_num_rows(mysql_query("SELECT * FROM `bazar_vip` WHERE city = '$udata[city]'"));

$cbazwmr = mysql_num_rows(mysql_query("SELECT * FROM `bazar_wmr` WHERE `usr`!='$log'"));
$cbazwmrvi = mysql_num_rows(mysql_query("SELECT * FROM `bazar_wmr` WHERE `usr`='$log'"));
$count_res = mysql_result(mysql_query("SELECT COUNT(*) FROM `sell_res` WHERE `city` = '".$udata['city']."' AND `pers_id` != '".$active."'"),0);
echo' <a href="shop.php?"><img src="pic/gorod/magaz.png" alt=""> Магазин шмота</a>';
echo' <a href="col_bonus.php?"><img src="pic/gorod/npc.png" alt=""> NPC </font></a></center><br/><br/>';

echo "<br/>";
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<a href=\"gorod.php?\">Центр города </a>"; 
echo' <a href="to_zamok.php?">  Замок</a><br/>';

}













////////////////////////
}
include($path.'inc/down.php');
?>