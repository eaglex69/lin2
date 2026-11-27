<?php
define('PROTECTOR', 1);

$textl='Справка по смайлам';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

$dir = opendir ("pic/smiles");
while ($file = readdir ($dir)) 
{ if (ereg (".gif$", "$file"))
{$a[]=$file;}}  
closedir ($dir); 
sort($a);

$total = count($a); 
if (empty($_GET['start'])) $start = 0;
else $start = $_GET['start'];
if ($total < $start + 20){ $end = $total; }
else {$end = $start + 20; }
for ($i = $start; $i < $end; $i++){ 

$smkod=str_replace(".gif","",$a[$i]);

echo '<div style="margin-right:10px;border:1px solid #383838" class="dot"><img src="pic/smiles/'.$a[$i].'" alt="">'; 
echo '- :'.$smkod.'</div>';
}


$a=count($a);
$ba=ceil($a/20);
$ba2=floor(($a-1)/20)*20;

echo'<br/>Страницы:';
$asd=$start-(20*4);
$asd2=$start+(20*5);

if($asd<$a && $asd>0){echo " <a href=\"smile.php?start=0&amp;id=\">1</a> ... ";}

for($i=$asd; $i<$asd2;)
{
if($i<$a && $i>=0){
$ii=floor(1+$i/20);

if ($start==$i) {
echo ' <b>'.$ii.'</b>';
               }
                else {
echo " <a href=\"smile.php?start=$i&amp;id=\">$ii</a>";
                     }}


$i=$i+20;}
if($asd2<$a){echo " ... <a href=\"smile.php?start=$ba2\">$ba</a>";}

echo "<br/><a href=\"chat.php?\">Назад</a>";

include($path.'inc/down.php');
?>
