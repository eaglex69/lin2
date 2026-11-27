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


echo"<form action='?otv' method='post'>";
echo"Текст<br>";
echo"<input name='simvol' value='$_POST[simvol]'><br>";
echo"Поиск по:<br>";
echo"<select name='vib' value='$_POST[vib]'>";


echo"<option value='date'>Дата матча";


echo"</select><br>";

 echo"<input value='Поиск' type='submit'>";

echo"</form>";



if(isset ($_GET['otv'])){


#### Поиск по дате ####

if($_POST['vib']=='date'){


$q1=mysql_query("SELECT * FROM `tot_istor` WHERE `date`='$_POST[simvol]'");

echo"<b>Результат :</b><br>";
while($dates=mysql_fetch_array($q1)){

echo"<b>Матч №$dates[id]</b><br>";
echo"$dates[kom1name] - $dates[kom2name]<br>";
echo"Счет : $dates[kom1] | $dates[kom2]<br>";
}
}

#####################








}
else
if(isset($_GET['faq'])){

echo"Для того что бы поиск выдал вам информацию вам сперва надо :<br>
1.Ввести дату в таком виде : 10(день месяца).12(месяц).10(год)<br>";
}





echo"<a href='?faq'>Правила поиска</a><br>";

echo"<a href='index.php'>В тотализатор</a>";

include($path.'inc/down.php');
?>