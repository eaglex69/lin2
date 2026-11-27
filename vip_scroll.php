<?
define('PROTECTOR', 1);

$headmod = 'vip_vip_scroll';//фикс. места

$textl='Заточка вещей';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


if(empty($_GET[tip])){

echo"<font color=green><b>Заточка вещей!</b><br/>";
echo"1 Coin of Luck заточки даёт +2 к Def (защите)!<br/>
На M.Def (Маг.Защита) точит можно АМУЛЕТЫ, КОЛЬЦА и СЕРЬГИ! Остальное точится только на P.Def (Физ.Защита)....<br/>";
echo"<u> У Вас $udata[almaz] Coin of Luck. </u></font><hr/>";


echo'<b><p>Заколдовать:</p></b>';
echo"<div class=inoy><a href=\"vip_scroll.php?tip=golova&amp;id=$_GET[id]\">Шлем</a>";
echo"<a href=\"vip_scroll.php?tip=shit&amp;id=$_GET[id]\">Щит</a>";
echo"<a href=\"vip_scroll.php?tip=ruki&amp;id=$_GET[id]\">Рукавицы</a>";
echo"<a href=\"vip_scroll.php?tip=body&amp;id=$_GET[id]\">Доспехи</a>";
echo"<a href=\"vip_scroll.php?tip=nogi&amp;id=$_GET[id]\">Сапоги</a> - - - - - ";
echo"<a href=\"vip_scroll.php?tip=plash&amp;id=$_GET[id]\">Плащ</a> ";
echo"<a href=\"vip_scroll.php?tip=amulet&amp;id=$_GET[id]\">Амулет</a> ";
echo"<a href=\"vip_scroll.php?tip=kolco&amp;id=$_GET[id]\">Левое кольцо</a> ";
echo"<a href=\"vip_scroll.php?tip=rkolco&amp;id=$_GET[id]\">Правое кольцо</a> ";
echo"<a href=\"vip_scroll.php?tip=poyas&amp;id=$_GET[id]\">Левая Серёжка</a> ";
echo"<a href=\"vip_scroll.php?tip=rpoyas&amp;id=$_GET[id]\">Правая серёжка</a> </div>";
}elseif(isset($_GET[tip]) and empty($_GET[what])){

echo"<font color=grey><p>Колдуются вещи, которые одеты на Вас! <br/>Поехали далее:</p><hr/></font>";
//-------ДА
echo "<form action=\"vip_scroll.php?tip=$_GET[tip]&amp;what=pdef&amp;id=$_GET[id]\" method=\"POST\">";
echo "<div class=menu><b><font color=grey>Отдать CoL:</font></b><br/>
<input type=\"text\" name=\"alm\" value=\"$udata[almaz]\" size=\"5\" maxlength=\"9\"/> ";
echo "<input type='submit' name='ok' value=' Точить ' /></font></form> ";
//-------

//echo"<a href=\"vip_scroll.php?tip=$_GET[tip]&amp;what=pdef&amp;id=$_GET[id]\"><font color=green> <b> Да </b> </font></a>";
echo"<div class=inoy><a href=\"col_bonus.php?\"><font color=red>  Отказаться  </font></a></div>";
}else{
if(
$_GET[tip]!=golova and 
$_GET[tip]!=body and 
$_GET[tip]!=nogi and 
$_GET[tip]!=shit and 
$_GET[tip]!=amulet and 
$_GET[tip]!=ruki and 
$_GET[tip]!=plash and
$_GET[tip]!=poyas and 
$_GET[tip]!=rpoyas and 
$_GET[tip]!=kolco and 
$_GET[tip]!=rkolco
){
echo'Ошибка, доступ закрыт!';
include($path.'inc/down.php');
exit;
}
// пишем если кольцо, амулет или серёжка то маг иначе физ защита
if($_GET[tip]==amulet or $_GET[tip]==kolco or $_GET[tip]==rkolco or$_GET[tip]==poyas or $_GET[tip]==rpoyas) {$_GET[what] = mdef;}else{$_GET[what] = pdef;}



if($_GET[what]!=pdef and $_GET[what]!=mdef){
echo'Ошибка, доступ закрыт!';
include($path.'inc/down.php');
exit;
}
$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `image`='yes' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto=='0'){
echo'Ошибка, сначала наденьте вещь!';
include($path.'inc/down.php');
exit;
}
//////////////////////////
if($_POST[alm] <= 0){echo'На 0 Coin of Luck точить нельзя!';echo"<br/><a href=\"col_bonus.php?\">Назад</a>";include($path.'inc/down.php');exit;}
if($udata[almaz] - $_POST[alm] < 0){echo'Недостаточно Coin of Luck!';echo"<br/><a href=\"col_bonus.php?\">Назад</a>";include($path.'inc/down.php');exit;}
$almaznew = $udata[almaz] - $_POST[alm];


$it = mysql_fetch_array($req);
$mag[give] = $_POST[alm] * 2;

$nprotect=$mag[give]+$udata[$_GET[what]];

$w=explode("*",$it[name]);
if(empty($w[1])){
$nam="$w[0]*$_POST[alm]";
}else{
$kol=$w[1]+$_POST[alm];
$nam="$w[0]*$kol";}

//////////////забираем колы//////////////


////////////////////////////////////////

$num=$mag[give]+$it[$_GET[what]];

mysql_query("UPDATE `users` SET `almaz` = '$almaznew',`$_GET[what]` = '$nprotect' WHERE usr = '$log'");
mysql_query("UPDATE `item` SET
      `$_GET[what]` = '$num',
      `name` = '$nam'
       WHERE `usr` = '$log' and `tip`='$_GET[tip]' and `image`='yes'");
       
$msg="Защита увеличена!<br/>
Новая защита: $num (+$mag[give])<br/>";

}

///////
echo"$msg";
echo"<br/><div class=silka><a href=\"col_bonus.php?\">Назад</a></div>";
include($path.'inc/down.php');
?>