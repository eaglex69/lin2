<?php
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
$viso = mysql_num_rows(mysql_query("SELECT title FROM news"));
$puslap = floor($viso/10);
if (is_double($num) || !is_integer($num))
{
echo "Îøèáêà!<br/>";
}
else {
$asd = mysql_query("SELECT title, text, time FROM news ORDER BY id DESC LIMIT $num,1");
while($dsa = mysql_fetch_array($asd))
{
$i2 = $i++;
$title = strip_tags($dsa['title']);
$text = strip_tags($dsa['text']);
$time = strip_tags($dsa['time']);
echo "<b>$title</b><small><b>[$time]</b></small><br/>$text<br/>";
}}
?>