<?
define('PROTECTOR', 1);

$headmod = 'oprosvip';//фикс. места

$textl='Голосование';

include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


switch($_GET[mod]){

default:

echo"<font color=grey><b><p> Голосование за игру: </p></b><hr/>";

echo"<font color=silver>
- Голосовать могут персонажи которые достигли 12 уровня.<br/>
- Голосовать персонаж может каждые 24 часа. (по времени системы рейтингов)<br/>
- Персонажу, который проголосовал за сервер, в течении суток (24 часа) будет зачисленно 5 VoteCoin <br/>
- Администрация сервера Lineage II будет очень благодарна, если Вы проголосуете за сервер.<br/>
";

echo '</font><hr/>';
echo '<br/>';


$reqop = mysql_query("SELECT id FROM `opros` WHERE `id` >= '0'");
$opr = mysql_num_rows($reqop);

echo"<div class=inoy><a href=\"http://vigre.su/vote?game_id=1192\">Проголосавать!</a>";
echo "<a href=\"?mod=sp\">Уже проголосовали!  <font color=#484848>[$opr]</font></a></div>"; 


echo "<br/><font color=green> Приймите во внимание, что первое голосование не в счёт! Тоесть, начиная со второго голосования и далее Ваш персонаж будет получать Coin of Luck 
</font>";


echo "<br/><font color=green>Акция \"<font color=lime>Бонус +</font>\"!<br/>
Раз в неделю случайным порядком будет выбератся 3 человека, проголосовавших за игру, которые получат в подарок: <br/><br/>
1-e место: 90 Coin of Luck<br/>
2-e место: 50 Coin of Luck<br/>
3-e место: 30 Coin of Luck <br/><br/>
Также, если общее количество голосов составит более 50% от зарегестрированых пользователей, всем игрокам в конце месяца будет начисленно по 15 Coin of Luck и 10kk Аден.
 
</font>";


break;

//-----------------------////-----------------------////-----------------------////-----------------------////-----------------------//
case 'sp':


$reqop = mysql_query("SELECT * FROM `opros` WHERE `id` >= '0' ORDER BY id DESC");
$opr = mysql_num_rows($reqop);
		

if ($opr>=1){

echo'<br/><b> Уже проголосовали:</b></br>';
echo'<br/>';
echo "<div class=adm></div>";

while ($usr = mysql_fetch_array($reqop)) 
{


if ($usr[sms]==1){$col=10;}else{$col=10;}

echo "<div class=adm>";
echo "<a href=\"search.php?nick=$usr[char]&amp;go=go\"> $usr[char] <font color=#ffffff>|</font><font color=grey>  $usr[date] </font><font color=#ffffff>|</font><font color=green> ID : $usr[voteId] </font>|<font color=orange> + 5	VoteCoin</font></a>";
echo "</div>";


}
}else{echo "<p>Нет голосов за игру!</p>";}

include_once"inc/down.php";

break;


}
include_once"inc/down.php";
?>
