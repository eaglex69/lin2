<?php
define('PROTECTOR', 1);

$headmod = 'tchat';//фикс. места

$textl='Торговый Чат';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

switch($_GET['mod']){

default:

function smiles($string){
$dir = opendir ("pic/smiles"); 
while ($file = lin2_readdir ($dir)) {
if (preg_match('/\.gif$/', $file)){
$file2=str_replace(".gif","",$file);
$string=str_replace(":$file2",'<img src="pic/smiles/'.$file.'" alt="">',$string);
}}
lin2_closedir ($dir);
return $string;  }
/////////////
$rand = rand(1000,9999);
///////////////////


echo'<div class="hid" align="left"><div class=chat2>';

echo "<a href=\"chat.php?\"><img src=\"pic/ob9777.png\" ></a> 
<img src=\"pic/torg2.png\" > </a><br/><br/>" ;


echo "<a href=\"smile.php?\"><img src=\"pic/смайл.png\" ></a>
<a href=\"tchat.php?r=$rand\"><img src=\"pic/обн.png\" ></a>";
echo "<form action=\"tchat.php?mod=writes\" method=\"POST\">";
echo "<input type=\"text\" name=\"zin\" maxlength=\"5000\"/> <input type=\"submit\" value=\"\" class=\"button_medium3\"></div>";

if (!is_numeric($_GET['page']) || $_GET['page'] < 0 || $_GET['page'] == "0") 
{
$_GET['page'] = 0;
}
$next = $_GET['page'] + 1;
$back = $_GET['page'] - 1;
$num = $_GET['page'] * 10;
if($_GET['page'] == "0")
{$i = 1;}
else{$i = ($_GET['page']*10)+1;}
$viso = mysql_num_rows(mysql_query("SELECT komentaras FROM tchat"));
$puslap = floor($viso/10);
$times = date("H:i");
//echo "<center>-=$times=-</center>";
$asd = mysql_query("SELECT * FROM tchat ORDER BY id DESC LIMIT $num,10");
echo"<div align='left'>";
while($dsa = mysql_fetch_array($asd))
{
$nickas = strip_tags($dsa['nick']);
$koment = strip_tags($dsa['komentaras']);
$time = strip_tags($dsa['time']);
$koment = smiles($koment);

$reqs = mysql_query("SELECT * FROM `users` WHERE `usr` = '$nickas'");
$ud = mysql_fetch_array($reqs);

//картинка клана /////////////////////////////
$pic = "";
if(!empty($ud['clan'])){
$req6546566 = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$ud[clan]' LIMIT 1");
$wh = mysql_fetch_array($req6546566);

if(!empty($wh['emblema'])){
$pic = "<img src=\"pic/clan/$wh[emblema]\" alt=\"cl\"/>";}}
////////////////////////////////////////////////
$antiyou = "<img src=\"pic/smiles/align2.gif\" alt=\"antiyou\"/>";

$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$nickas' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
$color = mysql_fetch_array($req222);
$cl = "<font color=#$color[color]>";}else{
$cl = "";}


if($udata['dostup']>=2){
$silka = " <a href=\"tchat.php?mod=del_post&amp;p=$dsa[id]\"><font color=red>x</font></a>";
}

if($dsa['nick']=="AntiYou")
{
echo "
<hr/><font color=#af9878>$time | $pic $antiyou<b><a href=\"search.php?nick=$nickas&amp;go=go\"><font color=#f4d06e>$nickas</font></a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
:<br/><font color=#ffffcc></b> $koment </font>";
}else{

if ($ud['dostup']>=4)
{
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\"><font color=#f4d06e>$nickas</font></a>
<a href=\"tchat.php?nick=$nickas&amp;mod=write\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b> $koment </font>";
}else{
if ($ud['dostup']==2 or $ud['dostup']==3)
{
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\"><font color=#f4d06e>$nickas</font></a>
<a href=\"tchat.php?nick=$nickas&amp;mod=write\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b> $koment </font>";}else{
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$cl$nickas</font></a>
<a href=\"tchat.php?nick=$nickas&amp;mod=write\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b> $koment ";
}}}

echo'<br/>';
}
echo "<hr/></div>";
if($udata['dostup'] >= 2)
{
echo "<br/><div class=inoy><a href=\"tchat.php?mod=trinti\">Удалить все сообщения</a></div>";
}


if ($_GET['page'] > 0)
{
echo "<a href=\"tchat.php?page=$back\">Назад</a>";
}
elseif ($_GET['page'] == 0)
{
echo "Назад";
}
echo" | ";
if($_GET['page'] < $puslap || $_GET['page'] == "" || $_GET['page'] == 0)
{echo "<a href=\"tchat.php?page=$next\">Далее</a>";}
else
{echo "Далее";}
echo "</div>";
break;

case 'del_post':
if($udata['dostup'] >= 2)
{
if(empty($_GET['p'])){
echo"Не выбран пост!<br/>";
}else{
$asd = mysql_query("SELECT * FROM tchat WHERE id='$_GET[p]' LIMIT 1");
$avto=mysql_num_rows($asd);
if($avto==0){
echo'Нет такого поста!<br/>';
}else{
mysql_query("DELETE FROM `tchat` WHERE id='$_GET[p]' LIMIT 1");
echo'Пост успешно удалён!<br/>';
echo "<a href=\"tchat.php?\">Назад</a>";
}
}
}else{
echo "Ошибка!Доступ закрыт!";
}
break;

case 'write':

echo"<b>Объявление</b><br/>";
echo "<form action=\"tchat.php?mod=writes\" method=\"POST\">";
if (isset($_GET['nick']))
{
$_GET['nick'] = htmlspecialchars($_GET['nick']);
echo "<input type=\"text\" name=\"zin\" maxlength=\"250\" value=\"$_GET[nick], \" size=\"10\"/><br/>";
}
else
{
echo "<input type=\"text\" name=\"zin\" maxlength=\"250\" size=\"10\"/><br/>";
}
echo "<input type=\"submit\" value=\"Ok\" class=\"ibutton\"><br/>";
echo "<img src='img/feather.png' alt=''><a href=\"tchat.php?\">Назад</a><br>";
break;

case 'writes':

$msg=$_POST['zin'];

if ($udata['lvl']<11){
echo "<b>В чате можно писать с 11 уровня! </b>";
			echo"<br/><br/><a href=\"chat.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}
/////////////////////защита спёр с линяги фаворита///////////////////////////

/*if("http://Anonymouse.org/ (Unix)"==getenv('HTTP_USER_AGENT')){
echo"Извините, но с прокси писать запрещено.<br/><br/>Если это сообщение вам выдало по непонятной причине, напишите на ник iNoY_GM браузер с которого
вы заходите.";
echo "</p></card></wml>";
include 'inc/down.php';
exit;
}

if($brow==getenv('HTTP_USER_AGENT')){
if($ip!==$_SERVER['REMOTE_ADDR']){
echo"Извините, но с прокси писать запрещено.<br/><br/>Если это сообщение вам выдало по непонятной причине, напишите на ник iNoY_GM браузер с которого
вы заходите.";
echo "</p></card></wml>";
include 'inc/down.php';

exit;}}*/
//////////////////////////////////////////////////////////////////////////////////


$msg = preg_replace('~(l 2 s e r v a k . r u|l2mobi.h1r.biz|darklineage.ru|l2mir.h1r.biz|l2perchiki.tk|l2pirates.ru|l2servak.ru|l2mobile.ru|wapl2pvp.ru|wapl2pvp.tk|l3mobile.ru|game-l2.ru|game-l2.ru|l2war.mobi|war-lin.ru|barbars.su|l2full.ru|miru.mobi|waplin.ru|l2mobi.su|l3waps.ru|l2mobi.su|l2servak|l2wap.ru|l3pvp.ru|l3new.ru|wapl2pvp|l3new.tk|l2mo.ru|lineage|. mobi|. info|ln8.ru|. org|. net|. com|l3pvp|l3new|. ua|. tk|. ru|mobi|line|ln8|lin|war|la2|la3|l2|l3)~is', 'Реклама запрещена', $msg);
$msg = preg_replace('~(l 2 s e r v a k . r u|l2mobi.h1r.biz|darklineage.ru|l2mir.h1r.biz|l2perchiki.tk|l2pirates.ru|l2servak.ru|l2mobile.ru|wapl2pvp.ru|wapl2pvp.tk|l3mobile.ru|game-l2.ru|game-l2.ru|l2war.mobi|war-lin.ru|barbars.su|l2full.ru|miru.mobi|waplin.ru|l2mobi.su|l3waps.ru|l2mobi.su|l2servak|l2wap.ru|l3pvp.ru|l3new.ru|wapl2pvp|l3new.tk|l2mo.ru|lineage|. mobi|. info|ln8.ru|. org|. net|. com|l3pvp|l3new|. ua|. tk|. ru|mobi|line|ln8|lin|war|la2|la3|l2|l3)~is', 'Реклама запрещена', $msg);

$msg=preg_replace('~(БАЛЬНИК|АХХУЕН|ДРАЦХИ|БЛАТЬ|БЛЙАД|БЛЙАТ|БЛИАД|БЛИАТ|ПИДОР|ПИДАР|НИДОР|НИДАР|ДРАЧИ|ЗАЛУП|ДАРАС|СЦУКА|СУКА|ИПАЛ|ПИТБ|АХУЕ|ОХУЕ|ХУЕЛ|ОХУИ|ОХУУ|ОХИУ|ОХУЮ|АХУИ|АХИИ|АХИЕ|АХУУ|АХИУ|ПИЗД|ПИСД|ПЫЗД|ПЫСД|ПИЦД|ПЕЗД|ПЕСД|БЛЯД|БЛАД|БЛЯТ|АЙОБ|АИОБ|УЙОБ|УИОБ|МУДИ|МУДА|ЧЛЕН|ЧЛЕП|ПЕДИ|ТВАЮ|САСИ|ЕДРИ|ПИДО|ГОНД|МАНД|ОТХУ|СУЧЕ|СРАН|МУДО| ЛОХ| ЛОШ|ХУЙ|ХУИ|ХУЁ|ХУЕ|ИБА|ХУЯ|ХУУ|БЛЯ|ЙОБ|ИОБ|ЪЕБ|АЁБ|АЁП|АЕБ|ОЁБ|ОЕБ|УЁБ|УЁП|УЕБ|ИЕБ|ЫЕБ|ЧМО|ЖОП|ПОЦ|ПОХ|ХЕР| ЁБ| ЁП| ЕБ)~is', '***', $msg);
$msg=preg_replace('~(бальник|аххуен|драчун|блять|блйад|блйат|блиад|блиат|уёбок|уебок|пидор|пидар|нидор|нидар|педик|драчи|залуп|сучка|дарас|сцука|сука|ипал|питв|ахуе|охуе|хуел|охуи|ахие|пизд|писд|пызд|пысд|пицд|пезд|песд|бляд|блят|уйоб|член|члеп|дроч|саси|пидо|гонд|хуй|хуи|хеё|хуе|иба|хуя|бля|чмо|пох|лох|хер)~is', '***', $msg);



$msg=substr($msg, 0, 512);
$msg=stripslashes(htmlspecialchars($msg));
$msg=str_replace("\r\n","<br />",$msg);
$msg=str_replace("\r","<br />",$msg);
$msg=str_replace("\n","<br />",$msg);
$msg = addslashes($msg);
$msg=preg_replace ("|[\r\n]+|si","",$msg);
$a = mysql_num_rows(mysql_query("SELECT komentaras FROM tchat WHERE komentaras = '$msg'"));
$b = mysql_fetch_array(mysql_query("SELECT kada FROM tchat WHERE nick = '$log' ORDER BY kada DESC LIMIT 1"));
$data_kom = strip_tags($b['kada']);
$data = date("y/m/d H:i:s", strtotime("+900 seconds"));
$data_dbr = date("y/m/d H:i:s");
$time = date("H:i");
if($data_dbr >= $data_kom && $msg != "")
{
mysql_query("INSERT INTO tchat SET nick = '$log', komentaras = '$msg', kada = '$data', time = '$time'");
$rand = rand(1000,9999);
//start
function smiles($string){
$dir = opendir ("pic/smiles"); 
while ($file = lin2_readdir ($dir)) {
if (preg_match('/\.gif$/', $file)){
$file2=str_replace(".gif","",$file);
$string=str_replace(":$file2",'<img src="pic/smiles/'.$file.'" alt="">',$string);
}}
lin2_closedir ($dir);
return $string;  }
/////////////
$rand = rand(1000,9999);
///////////////////
echo'<div class="hid" align="left">';
echo "<a href=\"smile.php?\"><img src=\"pic/смайл.png\" ></a>
<a href=\"tchat.php?r=$rand\"><img src=\"pic/обн.png\" ></a>";
echo "<form action=\"tchat.php?mod=writes\" method=\"POST\">";
echo "<input type=\"text\" name=\"zin\" maxlength=\"5000\"/> <input type=\"submit\" value=\"\" class=\"button_medium3\"></div>";

if (!is_numeric($_GET['page']) || $_GET['page'] < 0 || $_GET['page'] == "0") 
{
$_GET['page'] = 0;
}
$next = $_GET['page'] + 1;
$back = $_GET['page'] - 1;
$num = $_GET['page'] * 10;
if($_GET['page'] == "0")
{$i = 1;}
else{$i = ($_GET['page']*10)+1;}
$viso = mysql_num_rows(mysql_query("SELECT komentaras FROM tchat"));
$puslap = floor($viso/10);
$times = date("H:i");
//echo "<center>-=$times=-</center>";
$asd = mysql_query("SELECT * FROM tchat ORDER BY id DESC LIMIT $num,10");
echo"<div align='left'>";
while($dsa = mysql_fetch_array($asd))
{
$nickas = strip_tags($dsa['nick']);
$koment = strip_tags($dsa['komentaras']);
$time = strip_tags($dsa['time']);
$koment = smiles($koment);


$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$nickas' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
$color = mysql_fetch_array($req222);
$cl = "<font color=#$color[color]>";}else{
$cl = "";}


$reqs = mysql_query("SELECT * FROM `users` WHERE `usr` = '$nickas'");
$ud = mysql_fetch_array($reqs);

//картинка клана /////////////////////////////
$pic = "";
if(!empty($ud['clan'])){
$req6546566 = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$ud[clan]' LIMIT 1");
$wh = mysql_fetch_array($req6546566);

if(!empty($wh['emblema'])){
$pic = "<img src=\"pic/clan/$wh[emblema]\" alt=\"cl\"/>";}}
////////////////////////////////////////////////


if($udata['dostup']>=2){
$silka = " <a href=\"tchat.php?mod=del_post&amp;p=$dsa[id]\"><font color=red>x</font></a>";
}


if ($ud['dostup']>=4)
{
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\"><font color=#f4d06e>$nickas</font></a>
<a href=\"tchat.php?nick=$nickas&amp;mod=write\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b> $koment </font>";
}else{
if ($ud['dostup']==2 or $ud['dostup']==3)
{
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\"><font color=#f4d06e>$nickas</font></a>
<a href=\"tchat.php?nick=$nickas&amp;mod=write\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b> $koment </font>";}else{
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$cl$nickas</font></a>
<a href=\"tchat.php?nick=$nickas&amp;mod=write\"><img src=\"pic/чат.png\" > $silka</a>
:<br/><font color=#ffffcc></b> $koment ";
}}

echo'<br/>';
}
echo "<hr/></div>";
if($udata['dostup'] >= 2)
{
echo "<br/><div class=inoy><a href=\"tchat.php?mod=trinti\">Удалить все сообщения</a></div>";
}


if ($_GET['page'] > 0)
{
echo "<a href=\"tchat.php?page=$back\">Назад</a>";
}
elseif ($_GET['page'] == 0)
{
echo "Назад";
}
echo" | ";
if($_GET['page'] < $puslap || $_GET['page'] == "" || $_GET['page'] == 0)
{echo "<a href=\"tchat.php?page=$next\">Далее</a>";}
else
{echo "Далее";}
echo "</div>";
//end
}
elseif($data_dbr < $data_kom)
{
$sec = $data_kom-$data_dbr;
$rand = rand(1000,9999);
echo "Массовое размещение сообщений в торговом чате запрещено. Одно сообщение в 15 минут.<br/>";
echo"<a href=\"tchat.php?r=$rand\">Продолжить</a>";
}
elseif($msg == "")
{
$rand = rand(1000,9999);
echo "Вы не написали сообщение!<br/>";
echo"<a href=\"tchat.php?r=$rand\">Продолжить</a>";
}
else
{
$rand = rand(1000,9999);
echo "Ошибка!<br/>";
echo"<a href=\"tchat.php?r=$rand\">Продолжить</a>";
}
break;

case 'trinti':

if($udata['dostup'] >= 2)
{
mysql_query("DELETE FROM tchat");
echo "Все сообщения удалены!";
echo "<br/><a href=\"tchat.php?\">Назад</a><br/></div>";
}
else
{
echo "Ошибка!Доступ закрыт!<br/></div>";
}
break;
}

include($path.'inc/down.php');
?>
