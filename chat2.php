<?php
define('PROTECTOR', 1);



echo "</div></div></div></div><div class=gameBorder><div class=menu><div class=\"lx-sec\"><b>Чат</b></div><div class=chat2>";

//---------------------------------------------------------
$in_chat = (mysql_query("SELECT chat FROM option_game WHERE id = '1' LIMIT 1"));
$iiii =mysql_fetch_array ($in_chat);
if ($iiii['chat']=='off'){
echo "<div class=msg><p>Чат на некоторое время закрыт Администрацией!</p></div>";
if ($udata['prava']>=2){
echo " <a href=\"/chat.php?chat_on\"><font color=green>Открыть чат</font></a> </div>";}
//include($path.'inc/down.php');exit;
}else{
//---------------------------------------------------------


function smiles($string){
$dir = opendir ("pic/smiles"); 
while ($file = lin2_readdir ($dir)) {
if (preg_match('/\.gif$/', $file)){
$file2=str_replace(".gif","",$file);
$string=str_replace(":$file2",' <img src="/pic/smiles/'.$file.'" alt="" height="25" width="25"> ',$string);
}}
lin2_closedir ($dir);
return $string;  }
/////////////
$rand = rand(1000,9999);
///////////////////





echo "<img src=\"pic/ob97772.png\" ></a>
<a href=\"tchat.php?\"><img src=\"pic/torg1.png\" ></a><br/><br/>" ;


echo "<a href=\"smile.php?\"><img src=\"pic/смайл.png\" ></a>
<a href=\"chat.php?r=$rand\"><img src=\"pic/обн.png\" ></a>";
echo "<form action=\"chat.php?mod=writes\" method=\"POST\" name=\"guestbook\">";

if (isset($_GET['otv'])){$nick = "$_GET[otv], ";}

echo //"	<input type=\"text\" name=\"zin\" maxlength=\"5000\"/> 
		"	<textarea name=\"zin\" rows=\"3\" maxlength=\"5000\">$nick</textarea><br/>";
		
echo '<input class="button_medium3" class="button" type="submit" value=""/></form></div>';



$viso = mysql_num_rows(mysql_query("SELECT komentaras FROM komentarai"));
$puslap = floor($viso/5);
$times = date("H:i");
//echo "<center>-=$times=-</center>";
$asd = mysql_query("SELECT * FROM komentarai ORDER BY id DESC LIMIT 0,5");
echo"<div align='left'>";
while($dsa = mysql_fetch_array($asd))
{
$nickas = strip_tags($dsa['nick']);
$koment = $dsa['komentaras'];
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
$pic = "<img src=\"/pic/clan/$wh[emblema]\" alt=\"cl\"/>";}}
////////////////////////////////////////////////
$antiyou = "<img src=\"pic/smiles/align2.gif\" alt=\"antiyou\"/>";


if($udata['prava']>=2){
$silka = " <a href=\"/chat.php?mod=del_post&amp;p=$dsa[id]\"><font color=red>x</font></a>";
}

if($ud['dostup']>4 and $dsa['nick']=='AntiYou')
{
$nickas1=str2gradient("$nickas", "f11717", "ffff00");
echo "
<hr/><font color=#af9878>$time | $antiyou <b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";
}

if($ud['dostup']>4 and $dsa['nick']!=='AntiYou')
{
$nickas1=str2gradient("$nickas", "f11717", "ffff00");
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";
}else{
if ($ud['dostup']==4)
{
$nickas1=str2gradient("$nickas", "ffa500", "21f117");
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";
}else{
if ($ud['dostup']==2 or $ud['dostup']==3)
{
$nickas1=str2gradient("$nickas", "547ef4", "547ef4");
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";}else{

// если есть цветной ник, то грузим цвет
$req222 = mysql_query("SELECT * FROM `color_akk` WHERE `usr` = '$nickas' LIMIT 1"); // защита от нескольких акк
$avto=mysql_num_rows($req222);
if($avto==1){
$colors = mysql_fetch_array($req222);

echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\"><font color=#$colors[color]> $nickas</font></a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b> $koment ";
}else{


///////////////////////////////////////
if ($dsa['nick']!=='AntiYou')
{
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\"> $nickas</font></a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b> $koment ";
}}}}}

echo'<br/>';
}
echo "<hr/></div>";
if($udata['prava'] >= 2)
{
echo "<br/><a href=\"/chat.php?mod=trinti\">Удалить все сообщения</a>";
}

if ($_GET['page'] > 0)
{
echo "<a href=\"/chat.php?page=$back\">Назад</a>";
}
elseif ($_GET['page'] == 0)
{
echo "Назад";
}
echo" | ";
if($_GET['page'] < $puslap || $_GET['page'] == "" || $_GET['page'] == 0)
{echo "<a href=\"/chat.php?page=$next\">Далее</a>";}
else
{echo "Далее";}

}
echo "</div></div></div></div>";



?>
