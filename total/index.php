<?
define('PROTECTOR', 1);

$headmod = 'Тотализатор';
$textl='total';
$path='../';
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
switch($_GET[mod]){

case "stav": 



$q2=mysql_query("SELECT * FROM `tot_stavki` WHERE `idm`='".intval($_GET['idm'])."' and `idu`='".$udata['id']."' ORDER BY id DESC"); 

if(mysql_num_rows($q2) != 0){ 
  echo'Вы уже ставили на этот матч';
include($path.'inc/down.php');
exit;
    } 

if(empty($_POST['submit'])){ 
if(empty($_GET['idm'])){exit('Окей');}else{$idm=intval($_GET['idm']);} 
$d=date('YmdHi'); 
$q=mysql_query("SELECT * FROM `tot_match` WHERE `id`='".$idm."' and `date` > $d LIMIT 1"); 
if(mysql_num_rows($q) == 0){exit('<br>Такого матча несуществует :-)');} 

$arr=mysql_fetch_array($q); 
$opisanie=mysql_result(mysql_query("SELECT (opisanie) FROM `tot_match` WHERE `id`='$idm'"),0);
echo " 
Описание: $opisanie<br/>
<br/>
<form action='?mod=stav&idm=$idm' method=post>$arr[kom1] : 
<input size='3' name='s1'><br/>$arr[kom2] :<input size='3' name='s2'><br/> 
<input type='submit' value='Ставим' name='submit'></form>"; 
}else{ 
if(empty($_GET['idm'])){exit("Ошибка ввода данных $_POST[s1] $_POST[s2] $_GET[idm]");} 
else 
{ 
$s1=intval($_POST['s1']); 
$s2=intval($_POST['s2']); 
$idm=intval($_GET['idm']); 
$time_ok=date("H:i:s"); 
$date_ok=date("d.m.y"); 
$koma1=mysql_result(mysql_query("SELECT (kom1) FROM `tot_match` WHERE `id`='$idm'"),0); 
$koma2=mysql_result(mysql_query("SELECT (kom2) FROM `tot_match` WHERE `id`='$idm'"),0); 


mysql_query("INSERT INTO `tot_stavki` SET `kom1`='$s1',`kom2`='$s2',`kom1name`='$koma1',`time`='$time_ok',`date`='$date_ok',`kom2name`='$koma2',`idm`='$idm',`idu`='$udata[id]'"); 
echo "Ставка сделана"; 
} 
} 
echo '<br><a href=index.php>В тотализатор</a><br>'; 
break;





default:
echo"<div class='menu_razd'>Матчи</div>";
echo "<table class='post'>\n";
$d=date('YmdHi');
$q=mysql_query("SELECT * FROM `tot_match` WHERE `s1`='nu' and `date` > $d");
if(mysql_num_rows($q) == 0){
	echo "<br><b>На данный момент матчи отсутствуют!<br></b><br />";}
$q5=mysql_query("SELECT * FROM `tot_match` WHERE `idm` = '".$arr['id']."' and `s1`='nu' and `date` > $d ORDER BY id DESC LIMIT 0,15");
while($arr=mysql_fetch_array($q5)){
$q2=mysql_query("SELECT * FROM `tot_stavki` WHERE `idm`='".$arr['id']."' and `idu`='".$udata['id']."' ORDER BY id DESC LIMIT 0,15");
$stav_all_m=mysql_result(mysql_query("SELECT COUNT(*) FROM `tot_stavki` WHERE `idm`='$arr[id]'"),0);
$time_m=$arr['time2'];
if(mysql_num_rows($q2) == 0){$a="<b>Вами ставка не зделана<font color='red'> :-(</font></b>";}
else
{$a="<b>Вами ставка зделана <font color='lime'>:-)</font></b>";}

echo "<a href='?mod=stav&idm=$arr[id]'>$arr[kom1] - $arr[kom2]</a><br/>$a<br/>";
echo"<small>Поставили : <b>$stav_all_m</b><br></small>";
echo"<small>Время игры : <b>$time_m</b><br></small>";
echo"<hr>";
}
echo"</table>";
$user_ok=mysql_result(mysql_query("SELECT COUNT(idu) FROM `tot_stavki` WHERE `idu`='$udata[id]'"),0);
$all_c_ok=mysql_result(mysql_query("SELECT COUNT(id) FROM `tot_istor` "),0);
echo"<br>";
echo "<a href='index.php?mod=stats'>Руководство<br>";
echo"<br>";
echo "<a href='all_matches.php'>История всех матчев</a> ($all_c_ok)<br>";
echo "<a href='my_stavki.php'>Мои ставки</a><br>";
echo "<a href='search.php'>Поиск в прошедших матчах</a><br>";
echo "<a href='top.php'>TOP 15 юзеров</a><br>";
echo "<a href='statistika.php'>Статистика</a><br>";
if($udata[dostup]>3){echo "<a href='admin.php'>Админ панель</a><br>";}
break;

case 'stats':
echo"<hr/><b><p><font color=#007F46>Руководство:</font></b></p><hr/>
<hr/>1. При ставках вы ничего не теряете (с вас не снимаются ни CoL ни адены).<br/>
<hr/>2. Правила ставок просты: <br/>
<br/>
    - есть 2 команды и 3 исхода матча: победа 1ой команды, победа 2ой команды, ничья<br/>
<br/>
    - вам необходимо поставить в ячейку 1 или 0. Если вы хотите определить победителем команду 1 вам необходимо поставить в ячейке напротив этой команды цифру 1, а напротив команды 2 цифру 0. Так же и наоборот. Если же вы считаете, что в матче будет ничья, то вам необходимо поставить в ячейках одинаковые цифры: 1-1 или же 0-0<br/><hr/>";

echo"<a href='index.php'>В тотализатор</a>";
break;





}
include($path.'inc/down.php');
?>