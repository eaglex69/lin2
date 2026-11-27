<?php
define('PROTECTOR', 1);

$headmod = 'chat';//фикс. места

$textl='Чат';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');



//----------------------------------
//		ЧАТ ОТКЛЮЧИТЬ/ВКЛЮЧИТЬ
if (isset($_GET[chat_off]) and $udata[dostup]>=2){ //Отключить
mysql_query("UPDATE `option_game` SET `chat` = 'off' WHERE `id` = '1'");
header ("Location: chat.php?",false);exit;}

if (isset($_GET[chat_on]) and $udata[dostup]>=2){ //Отключить
mysql_query("UPDATE `option_game` SET `chat` = 'on' WHERE `id` = '1'");
header ("Location: chat.php?",false);exit;}

//----------------------------------


//---------------------------------------------------------


$in_chat = mysql_query("SELECT chat FROM option_game WHERE id = '1' LIMIT 1");
$iiii =mysql_fetch_array ($in_chat);
if ($iiii[chat]=='off'){
echo "<div class=msg><p>Чат на некоторое время закрыт Администрацией!</p></div>";
if ($udata[dostup]>=2){
echo "<div class=inoy> <a href=\"/chat.php?chat_on\"><font color=green>Открыть чат</font></a> </div>";}
include($path.'inc/down.php');
	exit;
}else{
//---------------------------------------------------------



// при входе в чат чистим таблу с плюсиком
mysql_query("DELETE FROM chat_otv WHERE `usr`='$log'");



switch($_GET[mod]){

default:


function smiles($string){
$dir = opendir ("pic/smiles"); 
while ($file = readdir ($dir)) {
if (ereg (".gif$", "$file")){
$file2=str_replace(".gif","",$file);
$string=str_replace(":$file2",' <img src="pic/smiles/'.$file.'" alt="" > ',$string);
}}
closedir ($dir);
return $string;  }
/////////////
$rand = rand(1000,9999);
///////////////////
?>

<script language="javascript" type="text/javascript">
<!--
var ie=document.all?1:0;
var ns=document.getElementById&&!document.all?1:0;

function InsertSmile(SmileId)
{
    if(ie)
    {
    document.all.message.focus();
    document.all.message.value+=" "+SmileId+" ";
    }

    else if(ns)
    {
    document.forms['guestbook'].elements['zin'].focus();
    document.forms['guestbook'].elements['zin'].value+=" "+SmileId+" ";
    }

    else
    alert("Ваш браузер не поддерживается!");
}
// -->
</script>
<?

echo'<div class="hid" align="left">';

echo "</b><img src=\"pic/ob97772.png\" > </a> 
<a href=\"tchat.php?\"><img src=\"pic/torg1.png\" > </a><br/><br/>" ;


echo "<a href=\"smile.php?\"><img src=\"pic/смайл.png\" ></a>
<a href=\"chat.php?r=$rand\"><img src=\"pic/обн.png\" ></a>";
echo "<form action=\"chat.php?mod=writes\" method=\"POST\" name=\"guestbook\">";

if (isset($_GET[otv])){$nick = "$_GET[otv], ";}

echo //"	<input type=\"text\" name=\"zin\" maxlength=\"5000\"/> 
		"		<textarea name=\"zin\" rows=\"2\" maxlength=\"5000\">$nick</textarea><br/>";
		
echo '<div align="left"><input class="button_medium3" class="button" type="submit" value=""/></form>';


?>

<script language="JavaScript">
        function xhs(id) {
            t=document.getElementById(id);
                if(t.style.display=='none') t.style.display='';
                else t.style.display='none'
            return false;
        }
     </script>
<input class="button_medium4" type="button" value="" onclick="xhs('id123')" />
<table id="id123" style="display: none;">

<? 

//----------------------------------------
$dir = opendir ("pic/smiles");
while ($file = readdir ($dir)) 
{ if (ereg (".gif$", "$file"))
{$a[]=$file;}}  
closedir ($dir); 
sort($a);

$total = count($a); $start = 0; // стар значени от какого
if ($total < $start + 460){ $end = $total; }
else {$end = $start + 460; }
echo "</div></div>";
echo "<tr>";

for ($i = $start; $i < $end; $i++){ 

$smkod=str_replace(".gif","",$a[$i]);


if ($i==10){echo "</tr><tr>";}if ($i==20){echo "</tr><tr>";}if ($i==30){echo "</tr><tr>";}if ($i==40){echo "</tr><tr>";}
if ($i==50){echo "</tr><tr>";}if ($i==60){echo "</tr><tr>";}if ($i==70){echo "</tr><tr>";}if ($i==80){echo "</tr><tr>";}
if ($i==90){echo "</tr><tr>";}if ($i==100){echo "</tr><tr>";}
if ($i==110){echo "</tr><tr>";}if ($i==120){echo "</tr><tr>";}if ($i==130){echo "</tr><tr>";}if ($i==140){echo "</tr><tr>";}
if ($i==150){echo "</tr><tr>";}if ($i==160){echo "</tr><tr>";}if ($i==170){echo "</tr><tr>";}if ($i==180){echo "</tr><tr>";}
if ($i==190){echo "</tr><tr>";}

if ($i==200){echo "</tr><tr>";}


?>
    <td style="cursor: pointer;" class="dot" onclick='InsertSmile("<?echo":$smkod";?>")'><img src='/pic/smiles/<?echo"$a[$i]";?>' title="<?echo":$smkod";?>" height='30' width='30' alt='<?echo"$smkod";?>' / ></td>
<?

}
echo "</tr>";


echo "</table></div>";

if ($_GET[page] == "" || $_GET[page] < 0 || $_GET[page] == "0") 
{
$_GET[page] = 0;
}
$next = $_GET[page] + 1;
$back = $_GET[page] - 1;
$num = $_GET[page] * 10;
if($_GET[page] == "0")
{$i = 1;}
else{$i = ($_GET[page]*10)+1;}
$viso = mysql_num_rows(mysql_query("SELECT komentaras FROM komentarai"));
$puslap = floor($viso/10);
$times = date("H:i");
//echo "<center>-=$times=-</center>";
$asd = mysql_query("SELECT * FROM komentarai ORDER BY id DESC LIMIT $num,10");
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
if(!empty($ud[clan])){
$req6546566 = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$ud[clan]' LIMIT 1");
$wh = mysql_fetch_array($req6546566);

if(!empty($wh[emblema])){
$pic = "<img src=\"pic/clan/$wh[emblema]\" alt=\"cl\"/>";}}
////////////////////////////////////////////////
$antiyou = "<img src=\"pic/smiles/align2.gif\" alt=\"antiyou\"/>";


if($udata[dostup]>=2){
$silka = " <a href=\"chat.php?mod=del_post&amp;p=$dsa[id]\"><font color=#ff4040>x</font></a>";
}



if($ud[dostup]==5 and $dsa['nick']!=='AntiYou')
{
$nickas1=str2gradient("$nickas", "f11717", "ffff00");
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";
}else{
if ($ud[dostup]==4)
{
$nickas1=str2gradient("$nickas", "ffa500", "21f117");
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";
}else{
if ($ud[dostup]==2 or $ud[dostup]==3)
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
if($udata[dostup] >= 2)
{
echo "<div class=inoy> <a href=\"?chat_off\"><font color=#ff4040>Закрыть чат</font></a></div>";
}

if ($_GET[page] > 0)
{
echo "<a href=\"chat.php?page=$back\">Назад</a>";
}
elseif ($_GET[page] == 0)
{
echo "Назад";
}
echo" | ";
if($_GET[page] < $puslap || $_GET[page] == "" || $_GET[page] == 0)
{echo "<a href=\"chat.php?page=$next\">Далее</a>";}
else
{echo "Далее";}
echo "</div>";
break;

case 'del_post':
if($udata[dostup] >= 2)
{
if(empty($_GET[p])){
echo"Не выбран пост!<br/>";
}else{
$asd = mysql_query("SELECT * FROM komentarai WHERE id='$_GET[p]' LIMIT 1");
$avto=mysql_num_rows($asd);
if($avto==0){
echo'Нет такого поста!<br/>';
}else{
mysql_query("DELETE FROM `komentarai` WHERE id='$_GET[p]' LIMIT 1");
echo'Пост успешно удалён!<br/>';
echo "<a href=\"chat.php?\">Назад</a>";
}
}
}else{
echo "Ошибка!Доступ закрыт!";
}
break;

case 'write':

echo"<b>Сообщение</b><br/>";
echo "<form action=\"chat.php?mod=writes\" method=\"POST\">";
if (isset($_GET[nick]))
{
$_GET[nick] = htmlspecialchars($_GET[nick]);
//echo "<input type=\"text\" name=\"zin\" maxlength=\"250\" value=\"$_GET[nick], \" size=\"20\"/><br/>";
echo "<textarea name=\"zin\" rows=\"3\" maxlength=\"5000\">$_GET[nick], </textarea><br/>";


}
else
{
echo "<textarea name=\"zin\" rows=\"3\" maxlength=\"5000\">$_GET[nick], </textarea><br/>";
}
echo "<input type=\"submit\" value=\"Ok\" class=\"ibutton\"><br/>";
echo "<div class=silka><a href=\"chat.php?\">Назад</a></div>";
break;
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
case 'writes':

if ($udata[time]<600){
echo "<b>В чате можно писать проведя 10минут в игре! </b>";
					echo"<br/><br/><a href=\"chat.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}

$msg=$_POST['zin'];

// пишем защиту от повтора сообщений
$pov = mysql_fetch_array(mysql_query("SELECT * FROM komentarai WHERE nick = '$log' ORDER BY id DESC LIMIT 1"));

if ($pov[komentaras]==$msg){
echo "<b>Спам. Запрещенно писать одинаковые сообщения! </b>";
					echo"<br/><br/><a href=\"chat.php?\">Назад</a>";
include($path.'inc/down.php');
exit;
}

////////	Добавляем плюс в чат 	///////////////////
$ch_o = explode(",", $msg);


$avt_usr = mysql_num_rows(mysql_query("SELECT usr FROM users WHERE usr = '$ch_o[0]'"));
$avt_cha = mysql_num_rows(mysql_query("SELECT usr FROM chat_otv WHERE usr = '$ch_o[0]'"));
if ($avt_usr > 0 and  $avt_cha == 0){
mysql_query("INSERT INTO `chat_otv` (`id`, `usr`, `time`) VALUES (NULL, '$ch_o[0]', '$timec' )");
}


//////////////////////////////////////////////////


$msg=substr($msg, 0, 512);
$msg=stripslashes(htmlspecialchars($msg));
$msg=str_replace("\r\n","<br />",$msg);
$msg=str_replace("\r","<br />",$msg);
$msg=str_replace("\n","<br />",$msg);
$msg = addslashes($msg);
$msg=preg_replace ("|[\r\n]+|si","",$msg);
$a = mysql_num_rows(mysql_query("SELECT komentaras FROM komentarai WHERE komentaras = '$msg'"));
$b = mysql_fetch_array(mysql_query("SELECT kada FROM komentarai WHERE nick = '$log' ORDER BY kada DESC LIMIT 1"));
$data_kom = strip_tags($b['kada']);
$data = date("y/m/d H:i:s", strtotime("+20 seconds"));
$data_dbr = date("y/m/d H:i:s");
$time = date("H:i");
if($data_dbr >= $data_kom && $msg != "")
{
mysql_query("INSERT INTO komentarai SET nick = '$log', komentaras = '$msg', kada = '$data', time = '$time'");
mysql_query("UPDATE `users` SET `post` = `post` + 1 WHERE `usr` = '".$log."'");
$rand = rand(1000,9999);
//start
function smiles($string){
$dir = opendir ("pic/smiles"); 
while ($file = readdir ($dir)) {
if (ereg (".gif$", "$file")){
$file2=str_replace(".gif","",$file);
$string=str_replace(":$file2",'<img src="pic/smiles/'.$file.'" alt="" height="30" width="30">',$string);
}}
closedir ($dir);
return $string;  }



/////////////
$rand = rand(1000,9999);
///////////////////
echo'<div class="hid" align="left">';
echo "<a href=\"smile.php?\"><img src=\"pic/смайл.png\" ></a>
<a href=\"chat.php?r=$rand\"><img src=\"pic/обн.png\" ></a>";
echo "<form action=\"chat.php?mod=writes\" method=\"POST\">";
echo //"<input type=\"text\" name=\"zin\" maxlength=\"5000\"/> 
		"	<textarea name=\"zin\" rows=\"3\" maxlength=\"5000\"></textarea><br/>";
echo '<input class="button_medium3" class="button" type="submit" value=""/></form></br>';		


if ($_GET[page] == "" || $_GET[page] < 0 || $_GET[page] == "0") 
{
$_GET[page] = 0;
}
$next = $_GET[page] + 1;
$back = $_GET[page] - 1;
$num = $_GET[page] * 10;
if($_GET[page] == "0")
{$i = 1;}
else{$i = ($_GET[page]*10)+1;}
$viso = mysql_num_rows(mysql_query("SELECT komentaras FROM komentarai"));
$puslap = floor($viso/10);
$times = date("H:i");
//echo "<center>-=$times=-</center>";
$asd = mysql_query("SELECT * FROM komentarai ORDER BY id DESC LIMIT $num,10");
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
if(!empty($ud[clan])){
$req6546566 = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$ud[clan]' LIMIT 1");
$wh = mysql_fetch_array($req6546566);

if(!empty($wh[emblema])){
$pic = "<img src=\"pic/clan/$wh[emblema]\" alt=\"cl\"/>";}}
////////////////////////////////////////////////
$antiyou = "<img src=\"pic/smiles/align2.gif\" alt=\"antiyou\"/>";

if($udata[dostup]>=2){
$silka = " <a href=\"chat.php?mod=del_post&amp;p=$dsa[id]\"><font color=#ff4040>x</font></a>";
}
if($ud[dostup]>4 and $dsa['nick']=='AntiYou')
{
$nickas1=str2gradient("$nickas", "f11717", "ffff00");
echo "
<hr/><font color=#af9878>$time | $antiyou<b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";
}

if($ud[dostup]>4 and $dsa['nick']!=='AntiYou')
{
$nickas1=str2gradient("$nickas", "f11717", "ffff00");
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";
}else{
if ($ud[dostup]==4)
{
$nickas1=str2gradient("$nickas", "ffa500", "21f117");
echo "
<hr/><font color=#af9878>$time | $pic <b><a href=\"search.php?nick=$nickas&amp;go=go\">$nickas1</a>
<a href=\"chat.php?otv=$nickas\"><img src=\"pic/чат.png\" > $silka</a>
<br/><font color=#ffffcc></b>$koment";
}else{
if ($ud[dostup]==2 or $ud[dostup]==3)
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
if($udata[dostup] >= 2)
{
echo "<div class=inoy> <a href=\"?chat_off\"><font color=#ff4040>Закрыть чат</font></a></div>";
}

if ($_GET[page] > 0)
{
echo "<a href=\"chat.php?page=$back\">Назад</a>";
}
elseif ($_GET[page] == 0)
{
echo "Назад";
}
echo" | ";
if($_GET[page] < $puslap || $_GET[page] == "" || $_GET[page] == 0)
{echo "<a href=\"chat.php?page=$next\">Далее</a>";}
else
{echo "Далее";}
echo "</div>";
//end
}
elseif($data_dbr < $data_kom)
{
$sec = $data_kom-$data_dbr;
$rand = rand(1000,9999);
echo "Защита от Флуда! Подождите $sec секунд<br/>";
echo"<a href=\"chat.php?r=$rand\">Продолжить</a>";
}
elseif($msg == "")
{
$rand = rand(1000,9999);
echo "Вы не написали сообщение!<br/>";
echo"<a href=\"chat.php?r=$rand\">Продолжить</a>";
}
else
{
$rand = rand(1000,9999);
echo "Ошибка!<br/>";
echo"<a href=\"chat.php?r=$rand\">Продолжить</a>";
}
break;

case 'trinti':

if($udata[dostup] >= 2)
{
mysql_query("DELETE FROM komentarai");
echo "Все сообщения удалены!";
echo "<br/><a href=\"chat.php?\">Назад</a><br/></div>";
}
else
{
echo "Ошибка!Доступ закрыт!<br/></div>";
}
break;
}
}
include($path.'inc/down.php');
?>