<?
echo "</table></table></div></div></div></div><span style=text-align:center;>";
///////////////////////////////////////////////////////////


//----------низ ссылка обратная-----------
if(!empty($udata[site])){
$u=explode('.',$udata[site]);
include($path.'site/'.$u[0].'.php');
}else{
if(isset($_SESSION['site'])){
//echo"<hr/><a href='$site'>Главная</a><br/>";
echo '<p> <a href="http://'.$site.'"> Главная '.$site.'</a> </p>'; // 
}}
//----------------------------------------

if ($user_id!==0){//не авторизован

echo'<small><span/><div align="center"><a href="/search.php?nick=Ultimate&go=go"><span style=color:#af9878;> Создатель</a></span>'; 
echo'<p><a href="/faq.php?"><span style=color:#af9878;> Помощь</a>|<a href="/tiket.php?"><span style=color:#af9878;> Служба поддержки</span></a>| <a href="/rules.php?"> <span style=color:#af9878;> Правила</a></span>';

$taim = 150;$date = time();$timeout = $date - $taim;
$online = mysql_num_rows(mysql_query("SELECT * FROM online WHERE laikas > '$timeout'"));
echo "<br/><a href=\"/online.php?\"><span style=color:#af9878;><b>Online:</span> <span style=color:#af9878;>$online</b></span></a></p>" ;
echo '<script type="text/javascript" src="http://mobtop.ru/c/111178.js"></script><noscript><a href="http://mobtop.ru/in/111178"><img src="http://mobtop.ru/111178.gif" alt="MobTop.Ru - Рейтинг и статистика мобильных сайтов"/></a></noscript></a>'; 
echo '<a href="http://statok.net/go/12918"><img src="http://statok.net/imageOther/12918" alt="Statok.net" /></a></a>'; 
echo '<a href="http://waplog.net/c.shtml?604415"><img src="http://c.waplog.net/604415.cnt" alt="waplog" /></a></a>';
}
///////////////////////////////////////////////////////////
$times = date("H:i:s");
$date = date("d.m.Y");

echo'<center><span style=color:#af9878;> '.$times.'  | '.$date.'</center>';
$Y_god = date("Y"); 

echo '</span><div align="center"><span style=color:#af9878;font-size:10px;> <br/> 
	© '.$_SERVER[HTTP_HOST].', '.$Y_god.'  <br/><br/>  </span>';

//    info_compress();

?>
