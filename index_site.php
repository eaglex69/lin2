<?
define('PROTECTOR', 1);

$headmod = 'index';//фикс. места

$textl='Lineage 3';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
if ($user_id==0){
include($path.'inc/head.php');
/////////////////////
$timeout = time() - 900;
$online = mysql_num_rows(mysql_query("SELECT * FROM online WHERE laikas > '$timeout'"));
///////////////////////////////
$req = mysql_query("SELECT * FROM `new_usr` WHERE `id`>'0'");
$newusr=mysql_num_rows($req);
///////////////////////////////
$reg = mysql_num_rows(mysql_query("SELECT * FROM users"));
$asd = mysql_query("SELECT time FROM news ORDER BY id DESC LIMIT 1");
$news = mysql_fetch_array($asd);
/////////////////////
switch($_GET[mod]){
default:
echo'<div class="gameBorder">';
echo'<div class="head"><b>Lin)e(age III - Новый Мир</b></div>';
echo'<div class="menu">';
echo'<center><img src="pic/logo.png"/>';
echo'</div><center><div class="inf">';
//echo"В мире $reg героев! Из них <a href=\"index.php?mod=online\">$online</a> в игре.";
echo'<center><font color=#7F0000> <b>P v P &#160&#160 S e r v e R</b> </font></center> </div><div class="menu">';


echo'<br/><font color=grey>Lineage III - это мир в котором идет война нескольких сторон!<br/>
За кого будешь ты? Присоединяйся к нам, ведь возможно именно ТЫ<br/>
сможешь изменить результат этого противостояния!<br/><br/></font>';


if (isset($_GET[error]))
{ echo'<hr/></div><div class="head"><font color=red>Пароль или логин введены не правильно.</font></div><div class="menu">'; }


if(isset($_GET['vxod'])){
echo'<form method="post" action="enter.php">';
echo "<b>Логин:</b><br/>";
echo "<input name=\"log\" maxlength=\"20\" title=\"nick\" emptyok=\"true\"/><br/>";
echo "<b>Пароль:</b><br/>";
echo "<input name=\"pas\"  type=\"password\"  maxlength=\"20\" title=\"password\" emptyok=\"true\"/><br/>";
echo'<input name="mem" type="checkbox" value="1" /> Запомнить меня<br /><br/>';
echo '<input class="button" type="submit" value="Войти"/></form>';
}
else{


echo '<hr/>';
echo'<form method="post" action="enter.php">';
echo "<b><font color=#FF6A00>Логин:</font></b><br/>";
echo "<input name=\"log\" maxlength=\"20\" title=\"nick\" emptyok=\"true\"/><br/>";
echo "<b><font color=#FF6A00>Пароль:</font></b><br/>";
echo "<input name=\"pas\"  type=\"password\"  maxlength=\"20\" title=\"password\" emptyok=\"true\"/><br/>";
echo'<input name="mem" type="checkbox" value="1" /> Запомнить меня<br/>';
echo '<input class="button" type="submit" value="Войти"/></form><hr/>';
//echo " <a href=\"password.php\"><small> Забыли пароль? </small></a>";

/*echo '<hr/>';
echo "<img src='pic/main/vxod.png' alt='*'/> <a href=\"index.php?vxod\">Войти</a><br/>";
echo "<img src='pic/main/sword2.png' alt='*'/> <a href=\"rules.php\">Начать игру</a>";
echo '<hr/>';*/
}
//echo'</div>';


echo" <center><div class=\"pic\">";
echo "<a href=\"reg.php?refs=$ref\">Регистрация <b>(".$reg.")</b><small><font color=red>+$newusr</font></small></a>";
echo "<a href=\"password.php\"> Восстановить пароль </a>";
echo "<a href=\"index.php?mod=online\">В игре <b>($online)</b></a>";
//echo" <a href=\"index.php?action=stat\">Статистика игры</a>";
echo "<a href=\"index.php?mod=about\">Об игре</a>";
echo "<a href=\"index.php?mod=stats\">Статистика</a>";
echo "<a href=\"index.php?mod=site\">Администраторам</a>";
echo "</div>";
/*
echo'<div class="foot"><div class="silka"><center>';

//echo "&#187; <a href=\"index.php?mod=lib\">Помощь</a><br/>";
echo "<font color=grey> - - - - - - - </font>";
echo " <a href=\"rules.php\">&#187; Регистрация &#171;</a>";
echo " <a href=\"index.php?mod=about\">&#187; Об игре &#171;</a>";
echo " <a href=\"index.php?mod=site\">&#187; Администраторам &#171;</a></div>";
echo "<center><font color=grey> - - - - - - - </font>";
*/

echo "</div><div class=foot><br/><center><font color=grey><b> (c) l3nw.ru 2012 </b></font><br/><br/></div>";

echo'</div></center>';
include($path.'inc/endmain.php');


break;


case 'stats':
echo'<div class="gameBorder">';
echo'<div class="menu">';

$clan = mysql_query("SELECT * FROM `clan` WHERE `id` > '0'");
$clan=mysql_num_rows($clan);

$pers = mysql_query("SELECT * FROM `users` WHERE `id` > '0'");
$pers=mysql_num_rows($pers);

$mob = mysql_query("SELECT * FROM `mobs` WHERE `id` > '0'");
$mob=mysql_num_rows($mob);

$shop = mysql_query("SELECT * FROM `shop` WHERE `id` > '0'");
$shop=mysql_num_rows($shop);

$mdrop = mysql_query("SELECT * FROM `mdrop` WHERE `id` > '0'");
$mdrop=mysql_num_rows($mdrop);

$citys = mysql_query("SELECT * FROM `gorod` WHERE `id` > '0'");
$citys=mysql_num_rows($citys);

$okra = mysql_query("SELECT * FROM `okra` WHERE `id` > '0'");
$okra=mysql_num_rows($okra);

echo "<font color=grey><b><p> &#160 &#160 В игре: </p></b></font>";

echo "
 &#160 Уровней: 112 <br/>
 &#160 Кланов: $clan <br/>
 &#160 Персонажей: $pers <br/>
 &#160 Монстров: $mob <br/>
 &#160 Вещей: $shop <br/>
 &#160 Ресурсов: $mdrop <br/>
 &#160 Городов: $citys <br/>
 &#160 Окрестностей: $okra <br/>
";
echo "<hr/>";

echo' &#160 Адрес игры:<br/>
&#160 <input value="http://'.$_SERVER[HTTP_HOST].'" name="ssilka"/><br/>';

echo " &#160 Статус сервера: <font color=lime><b>On</b></font><br/><br/></div>";

echo '<div class=silka><a href="index.php">Назад</a><br/></div>';
include($path.'inc/endmain.php');
break;







case 'site':
echo'<div class="gameBorder">';
echo'<div class="menu">';
echo'У вас есть возможность установить на своём сайте нашу игру!<br/>
На все страницах игры будет стоять ссылка "Главная" ведущая на ваш сайт!<br/>
Сделайте ссылку вида:<br/>
<b>http://'.$_SERVER[HTTP_HOST].'?site=ВАШ_САЙТ</b><br/>
Где ВАШ_САЙТ адрес вашего сайта без http://<br/>
Скопируйте ссылку:<br/>
<input value="http://'.$_SERVER[HTTP_HOST].'?site=ВАШ_САЙТ" name="ssilka"/><br/></div><div class="foot">
<a href="index.php">Назад</a><br/></div>
';
include($path.'inc/endmain.php');

break;
case 'about':
echo'<div class="gameBorder">';
echo'<div class="menu">';
echo "
<font color=#FF0000>  Линейка</font> - это бесплатная онлайн MMORPG игра для мобильных телефонов! Нам совершенно реально удалось перенести этот невероятно захватывающий мир в твой мобильный телефон и дополнить его новыми возможностями. Теперь ты можешь контролировать его в любое время, в любом месте.<br/>
<br/>
Выбери сторону добра или зла и посмотри вокруг … ты увидишь эльфов, орков, гномов, монстров и людей. Это мир где ты найдешь надежных друзей, которые не оставят тебя в сложный момент, с которыми ты будешь захватывать замки и нападать на монстров. Вселенная магии и постоянного развития, где каждый твой шаг изменяет все. Здесь есть все - кланы, арены, торговля, профессии, свитки, умения, таланты.<br/>
<br/>
Поначалу будет непросто и у тебя появится много вопросов, на которых ты найдешь ответы благодаря накопленным знаниям и поддержке более опытных героев, которым нужны твои качества и стремление побеждать.<br/>
<br/>
Мы ждем тебя, Герой!<br/></div>";
echo'<div class="foot">';

echo"<a href=\"index.php\"> Назад</a></div>";
include($path.'inc/endmain.php');
break;
case 'news':
echo'<div class="gameBorder">';
echo'<div class="menu">';
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
$viso = mysql_num_rows(mysql_query("SELECT title FROM news"));
$puslap = floor($viso/10);
if (is_double($num) || !is_integer($num))
{
echo "Ошибка!<br/>";
}
else {
$asd = mysql_query("SELECT title, text, time FROM news ORDER BY id DESC LIMIT $num,10");
while($dsa = mysql_fetch_array($asd))
{
$i2 = $i++;
$title = strip_tags($dsa['title']);
$text = strip_tags($dsa['text']);
$time = strip_tags($dsa['time']);
echo "<b>$title</b><small><b>[$time]</b></small><br/>$text<br/>------<br/>";
}
if ($_GET[page] > 0)
{
echo "<a href=\"index.php?mod=news&amp;page=$back\">back</a>";
}
elseif ($_GET[page] == 0)
{
echo "back";
}
echo"|";
if($_GET[page] < $puslap)
{echo "<a href=\"index.php?mod=news&amp;page=$next&\">next</a><br/>";}
else
{echo "next<br/>";}
}
echo'</div>';
echo"<div class='foot'><a href=\"index.php\">Назад</a></div>";
break;
case 'online':
echo'<div class="gameBorder">';
echo'<div class="menu">';
$timeout = time() - 150;
////////////////
$asd = mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeout'");
echo "<b>Игроки:</b><br/>";
$i=1;
$avto=mysql_num_rows($asd);
if($avto>=1){
While($visi = mysql_fetch_array($asd))
{
$onl = mysql_fetch_array(mysql_query("SELECT lvl FROM users WHERE usr = '$visi[usr]' LIMIT 1"));

echo "$i. $visi[usr]
 [<b>$onl[lvl]</b>]<br/>";
$i++;
}}else{
echo'Нет игроков онлайн!';
}
echo'</div>';
echo"<div class='foot'><a href=\"index.php\">Назад</a></div>";
break;
}
include($path.'inc/end.php');
}else{
if($_GET[mod]=='exit'){
$header=TRUE;
}
include($path.'inc/head.php');
include($path.'inc/zag.php');

switch($_GET[mod]){

default:

//////////
$taim = 150;
$date = time();
$timeout = $date - $taim;
$online = mysql_num_rows(mysql_query("SELECT * FROM online WHERE laikas > '$timeout'"));
$onl = mysql_num_rows(mysql_query("SELECT * FROM users WHERE id >= '0'"));
////////////////
$q = mysql_query("SELECT COUNT(*) FROM `msg_r` WHERE `user_to` = '$log' AND `read` = '1';");
$new_mail = mysql_result($q, 0);
$w = mysql_query("SELECT COUNT(*) FROM `msg_r` WHERE `user_to` = '$log';");
$old_mail = mysql_result($w, 0);
//////////////
$asd = mysql_query("SELECT time FROM news ORDER BY id DESC LIMIT 1");
$news = mysql_fetch_array($asd);
//////////////
$req = mysql_query("SELECT * FROM `invite` WHERE `usr` = '$log'");
$inv=mysql_num_rows($req);
///////////////////
$chat= mysql_num_rows(mysql_query("SELECT * FROM komentarai"));
$tchat= mysql_num_rows(mysql_query("SELECT * FROM tchat"));


	$ft=mysql_result(mysql_query("SELECT count(*) FROM `forum_topics`"), 0); // кол-о тем в форуме


	$fm=mysql_result(mysql_query("SELECT count(*) FROM `forum_msg`"), 0); // кол-о сообщений в форуме

//////////////
/*
if($udata[dostup]>=4){
echo'<a href="adm_panel.php?"><font color=lime><b><p> Админ-панель</b></p></font>  </a>';
}
if($udata[dostup]==2 or $udata[dostup]==3){
echo'<a href="mod_panel.php?"><font color=blue><b><p>Модер-панель</b></p></font></p></a>';
}
*/


//----------------- Код отображающий знаки --------- $i- от какого и $imax - до какого числа ----
/*
$i=1000;
$imax=1501;
if ($i<=$imax){
		while ($i<$imax)
				{
					echo "$i) <b>&#$i</b> <font color=red> | </font>";
					$i++;
				}
			}
*/			
//------------------       ------------------------             --------------------------------
			
//Модификация главной страницы
$reqevent = mysql_query("SELECT * FROM `event` WHERE `id`>'0' ORDER BY `id` DESC LIMIT 1");
$event = mysql_fetch_array($reqevent);		

///////////////////////////////////////////////////////////////////////////////////////////////
/////////// Сколько на арене
$reqav = mysql_query("SELECT * FROM `mesto` WHERE `place` = 'arena'");
////////////////////////////
if(mysql_num_rows($reqav)>=1)
{
While($arena = mysql_fetch_array($reqav))
{

$asd2 = mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeou' and usr = '$arena[usr]'");
$are = mysql_num_rows($asd2);
if ($arer==1){$arer=$arer+$are;}else{$arer=$are;}
}}
if (empty($arer)){$arer=0;}
////////////////////////////////////////////////////////////////////////////////////////////////


/////////////обновы форума/////////////////////////////////////////////
		$asd = mysql_query("SELECT * FROM news ORDER BY id DESC LIMIT 1");
		$msg = mysql_fetch_assoc($asd);
		$msgcol = mysql_num_rows($asd);

echo' </div><div class=silka><a href="news.php?">&#187; <b>'.$msg['title'].'</b> <font color=grey>&#160; ['.$news[time].']</font></a> ';
echo "<div class=news>";
echo "<font color=grey> ".nl2br($msg[text])." </font>\n";
////////////////////////////////////////////////////////////////////////
echo "</div></div><div class=inoy>";

echo'<hr><b> .:: Приключения ::. </b><hr/>';
//echo'  <a href="taverna.php?">Таверна</a>  <a href="index.php?mod=quests">Квесты</a>';
echo'  <a href="oprosvip.php?">&#187; Бонус <font color=red><b> NeW </b></font> </a>'; 
echo'  <a href="to_towers.php?">&#187; Земли </a>'; //domin();
echo'  <a href="arena.php?">&#187; Арена  <font color=#ffffff>('.$arer.')</font></a>';
echo'  <a href="event.php?">&#187; Эвэнты <font color=#ffffff> ('.$event[data].')</font></a>';
if($udata[klas]=="wizard"){echo'  <a href="akademmag.php?">&#187; Академия магии</a>';}
if($udata[klas]=="fighert"){echo'  <a href="akademwar.php?">&#187; Академия войны</a>';}
echo'  <a href="asshome.php?">&#187; Гильдия наемников</a>';
//echo' <a href="workshop.php?">&#187; Мастерская</a>  <a href="sklad.php?">&#187; Склад</a>';
echo'  <a href="istok.php?">&#187; Источник Жизни</a>';
echo'  <a href="home.php?">&#187; Ратуша</a>  <a href="index.php?mod=inv">&#187; Приглашения <font color=#ffffff>('.$inv.')</font></a> ';
//echo'  <a href="sklad.php?">Склад</a>';
echo'  <a href="bank.php?">&#187; Банк</a>';
echo'  <a href="portal.php?">&#187; Телепорт</a>';
echo' <a href="pitomec.php?">&#187; Питомник</a>';
echo' <a href="rydnik.php?">&#187; Рудник</a>';


echo'<div class=menu><br/>';
echo'<b><hr/>.:: Торговая площадь ::.</b><hr/></div>';
echo' <a href="bazar.php?">&#187; Базар</a>';
echo' <a href="shop.php?">&#187; Магазин оружия и доспехов</a>';
echo' <a href="shopelexirs.php?">&#187; Магазин элексиров</a>';
echo' <a href="shopjuvilir.php?">&#187; Ювелирная лавка</a>';
echo' <a href="shopaura.php?">&#187; Магазин аур</a>';
echo' <a href="shoppit.php?">&#187; Магазин для питомцев</a>';
echo' <a href="lambard.php?">&#187; Ломбард</a>';
echo' <a href="zaksms.php?">&#187; <b>Покупка Coin of Luck</b></a>';
/// хеппи енд города

echo'<div class=menu><br/>';
echo'<hr/><b>.:: Рейтинги ::.</b><hr/> </div>';
echo' <a href="rating.php?">&#187; Зал Славы</a>';
echo " <a href=\"index.php?mod=ref\">&#187; Пригласи друга</a>";

if ($new_mail>=1){$new_mail="<font color=red><b>+$new_mail</b></font>";}else{$new_mail="";}

///////////////////////////////
$req = mysql_query("SELECT * FROM `new_usr` WHERE `id`>'0'");
$newusr=mysql_num_rows($req);
///////////////////////////////


echo'<div class=menu><br/>';
echo'<hr/><b>.:: Общение ::.</b><hr/></div>';
echo' <a href="msg.php?mod=read">&#187; Почта '.$new_mail.'</a>';
//echo' <a href="news.php?">&#187; Новости <font color=grey>'.$news[time].'</font></a> ';
echo" <a href=\"forum\">&#187; Форум <font color=lime>($ft/$fm)</font></a>  " ;
echo " <a href=\"chat.php?\">&#187; Общий чат <font color=lime>($chat)</font></a>  
 <a href=\"tchat.php?\">&#187; Торговый <font color=lime>($tchat)</font></a>" ;
echo " <a href=\"online.php?\">&#187; Игроки онлайн <font color=orange>($online) </font></a> " ;
echo " <a href=\"users.php?\">&#187; Весь мир <font color=grey>($onl)</font><small><font color=red> +$newusr</font></small></a> " ;
echo " <a href=\"index.php?mod=adm\">&#187; Администрация </a>";

$time=time();
$req = mysql_query("SELECT * FROM `ban` WHERE `ban_time`>'$time'");
////////////////////////////
$banners=mysql_num_rows($req);

echo'<div class=menu><br/>';
echo"<hr/><b>.:: Разное ::.</b><hr/></div>";
echo' <a href="faq.php?">&#187; Библиотека</a>';
echo' <a href="search.php?">&#187; Поиск игроков</a>';
echo' <a href="zona.php?">&#187; Тюрма <font color=#660000># '.$banners.'</font> </a>';
echo' <a href="index.php?mod=rules">&#187; Правила</a> 
 <a href="library.php">&#187; Помощь</a>';
echo" <a href=\"index.php?mod=exit\"><p><font color=red><b>Выход</b></font></p></a>";
echo "</div></div>";
break;

case 'domin':
$req=mysql_query("SELECT * FROM domination WHERE id = '1'");
$dom = mysql_fetch_assoc($req);
echo'Доминация';echo domin();echo'<br/>';
echo'<img src="pic/main/white.png" alt="*"/>Светлые: '.$dom['white'].' очков<br/>';
echo'<img src="pic/main/black.png" alt="*"/>Тёмные: '.$dom['black'].' очков<br/>';
echo"<a href=\"index.php?\">Назад</a>";
break;


case 'ref':
echo'У вас есть возможность пригласить своих друзей и получить за это вознаграждение!<br/>
- Когда ваш друг достигает 100 уровня вы получаете 1`000`000`000 опыта и 25 Coins of Luck!<br/><br/>
';
echo"<b>Ваша ссылка для приглашений:</b>";
$s7 = "http://$_SERVER[HTTP_HOST]/reg.php?ref=$udata[id]";
echo"<br/><input class='input' value=\"$s7\" size=\"30\" name=\"name\"/><br/>";

$req = mysql_query("SELECT * FROM `users` WHERE `ref` = '$log'");
if (mysql_num_rows($req)) {
    echo '<br/><b>Вы уже пригласили таких игроков:</b><br/>';
    $i=1;
   While($res = mysql_fetch_array($req)){
echo"$i) <b><a href=\"search.php?nick=$res[usr]&amp;go=go\"> $res[usr]</font></a></b><br/>";
      $i++;
    }
}

echo"<br/><a href=\"index.php?\">Назад</a>";
break;

case 'rules':
echo'<hr/><b><p><font color=#007F46>Правила для игроков (<font color=red>ЗАПРЕЩЕНО</font>):</font></b></p><hr/>
<font color=#5B7F00>
1) Применение ненормативной лексики, завуалированого мата, оскорбление в:<br/>
* чате;<br/>
* форуме;<br/>
* названии клана;<br/>
* нике персонажа;<br/>
* личных сообщениях;
<hr/>2) Проявления религиозной, расовой, половой и прочей нетерпимости или дискриминации.
<hr/>3) Разжигание конфликтов между участниками игры!
<hr/>4) Преднамеренное многократное повторение сообщений, сообщения лишенные смысла (флуд).
<hr/>5) Верхний регистр при общении.
<hr/>6) Расспостранение ссылок на посторонние ресурсы, реклама посторонних интернет - ресурсов, как прямая так и косвенная!
<hr/>7) Нечестная прокачка игрового персонажа, использование сторонних програмных средств для улучшения/автоматизации игровых возможностей!
Использование недочетов игры, багов, програмных ошибок, расспостранение их другим лицам/продажа за деньги.
<hr/>P. S. При нахождении таковых немедленно сообщите Администрации, за что можете получить вознаграждение.
<hr/>8) Продажа персонажа, игровых денег, вещей и прочего, что касается игры, за реальные деньги, равно как и попытка совершить одно из данных действий, в обход Администрации!
<hr/>9) Намеренная регистрация персонажей с никами - плагиатами ников Администрации/модераторов, с никами несущими рекламу посторонних интернет- ресурсов.
Использование приставок Admin, GM, в имени персонажа, названии клана, звании персонаажа!
<hr/>10) Обман/ввод в заблуждение/дача ложных показаний/отказ в сотрудничестве при разбирательстве/угрозы Администрации/модераторам!
<hr/>11) Обсуждать, перечить действиям, оскорблять Администрацию/модераторов, игровой сервер!
<hr/>12) После наказания нарушителя правил, заведомое создание персонажей, или уже существующими персонажами вести подальшие разборки.
<hr/>13) Просьбы о назначении на должность модератора!
<hr/>Вы обязуетесь выполнять рекомендации/замечания которые делает вам Администратор, модератор.<br/>
<hr/><b>Незнание правил не освобождает от их ответственности!</b><br/></font><hr/>';
echo"<br/><a href=\"index.php?\">Назад</a>";
break;

case 'adm':
echo '<b>Администрация игры</b><br/>';
// Супервайзоры
$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '5'");
if (mysql_num_rows($req)) {
    echo '<b>Главные администраторы:</b><br/>';
    $i=1;
   While($res = mysql_fetch_array($req)){
echo"$i. <b><a href=\"search.php?nick=$res[usr]&amp;go=go\">$res[usr]</a></b><br/>";
      $i++;
    }
}
// Администраторы
$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '4'");
if (mysql_num_rows($req)) {
    echo'<b>Администраторы:</b><br/>';
    $i=1;
   While($res = mysql_fetch_array($req)){
echo"$i. <b><a href=\"search.php?nick=$res[usr]&amp;go=go\">$res[usr]</a></b><br/>";
      $i++;
    }
}else{
echo"";
}
// Старшие Модераторы
// Администраторы
$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '3'");
if (mysql_num_rows($req)) {
    echo '<b>Старшие Модераторы:</b><br/>';
    $i=1;
   While($res = mysql_fetch_array($req)){
echo"$i. <b><a href=\"search.php?nick=$res[usr]&amp;go=go\">$res[usr]</a></b><br/>";
      $i++;
    }
}else{
echo"";
}
// Модераторы
$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '2'");
if (mysql_num_rows($req)) {
    echo '<b>Модераторы:</b><br/>';
    $i=1;
   While($res = mysql_fetch_array($req)){
echo"$i. <b><a href=\"search.php?nick=$res[usr]&amp;go=go\">$res[usr]</a></b><br/>";
      $i++;
    }
}else{
echo"";
}
echo"<a href=\"index.php?\">Назад</a>";
break;
case 'inv':
$req = mysql_query("SELECT * FROM `invite` WHERE `usr` = '$log'");
////////////////////////////
$avto=mysql_num_rows($req);
if(empty($_GET[id])){
if($avto>=1){
While($inv = mysql_fetch_array($req))
{
$req = mysql_query("SELECT `name` FROM `clan` WHERE `lider` = '$inv[clan]'");
$clan = mysql_fetch_array($req);

echo"<a href=\"index.php?mod=inv&amp;id=$inv[id]\"><b>Приглашение от клана $clan[name]</b></a><br/>";
}
echo"<br/><b>Всего приглашений: $avto</b><br/>";
}else{
echo"<b>Нет приглашений!</b>";
}}else{
$req = mysql_query("SELECT * FROM `invite` WHERE `usr` = '$log' and `id`='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto=="0"){
echo'Нет такого приглашения!';
include"inc/down.php";exit;
}
$inv = mysql_fetch_array($req);

$req = mysql_query("SELECT `name` FROM `clan` WHERE `lider` = '$inv[clan]'");
$clan = mysql_fetch_array($req);
if(empty($_GET[go])){
echo"Отправитель: <b>$clan[name]</b><br/>";
echo"Текст: $inv[text]<br/>";

echo"<a href=\"index.php?mod=inv&amp;id=$_GET[id]&amp;go=ok\">Принять</a><br/>";
echo"<a href=\"index.php?mod=inv&amp;id=$_GET[id]&amp;go=no\">Отказаться</a><br/>";
echo"<a href=\"index.php?mod=inv\">Назад</a>";
}else{
if($_GET[go]==ok){
if(!empty($udata[clan])){
echo'Вы уже находитесь в клане!';
include"inc/down.php";exit;
}
mysql_query("DELETE FROM `invite` WHERE usr='$log' and `id`='$_GET[id]'");
mysql_query("UPDATE `users` SET `clan`='$inv[clan]' WHERE `usr` = '$log'");

echo"Приглашение принято.Вы вступили в клан $clan[name]!<br/>";

}elseif($_GET[go]==no){

mysql_query("DELETE FROM `invite` WHERE usr='$log' and `id`='$_GET[id]'");

echo"Приглашение отклонено.<br/>";
}else{
echo"Ошибка!<br/>";
}
echo"<a href=\"index.php?mod=inv\">Назад</a>";
}
}
break;
case 'quests':

echo "<b><font color=grey><p> Книга заданий: </p></font></b><hr/>";
if($_GET[act]==start){
$req = mysql_query("SELECT * FROM `userquests` WHERE `usr` = '$log' and `status`='start'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto=="0"){
echo'Нет квестов!';
include"inc/down.php";exit;
}
if(empty($_GET[id])){
While($q = mysql_fetch_array($req))
{
$reqo = mysql_query("SELECT * FROM `quests` WHERE `quest` = '$q[quest]'");
$quest = mysql_fetch_array($reqo);
echo"<div class=inoy><a href=\"index.php?mod=quests&amp;act=start&amp;id=$q[id]\"> &#187 $quest[name] </a></div>";
}
}else{
$req = mysql_query("SELECT * FROM `userquests` WHERE `usr` = '$log' and `status`='start' and `id`='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto=="0"){
echo'Нет такого квеста!';
include"inc/down.php";exit;
}
$q = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `quests` WHERE `quest` = '$q[quest]'");
$quest = mysql_fetch_array($req);
echo"<b><font color=#007F46> $quest[name] </font></b> <font color=lime> [on] </font><br/>";
echo"<br/><font color=#A0A0A0>$quest[mission] </font><br/>";

$req = mysql_query("SELECT * FROM `user_kill` WHERE `quest` = '$q[quest]' and `usr` = '$log'");
$quest = mysql_fetch_array($req);
$avto=mysql_num_rows($req);
if(empty($quest[killing]) or $quest[killing]==0){echo "<font color=green><b>Выполненно</b></font><br/>";}else{
echo"<br/><font color=#A0A0A0>Осталось убить: $quest[killing] шт. </font><br/>";}


}

}elseif($_GET[act]==complete){
$req = mysql_query("SELECT * FROM `userquests` WHERE `usr` = '$log' and `status`='complete'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto=="0"){
echo'Нет квестов!';
include"inc/down.php";exit;
}
if(empty($_GET[id])){
While($q = mysql_fetch_array($req))
{
$reqo = mysql_query("SELECT * FROM `quests` WHERE `quest` = '$q[quest]'");
$quest = mysql_fetch_array($reqo);
echo"<div class=inoy> <a href=\"index.php?mod=quests&amp;act=complete&amp;id=$q[id]\"> $quest[name] </a></div>";
}
}else{
$req = mysql_query("SELECT * FROM `userquests` WHERE `usr` = '$log' and `status`='complete' and `id`='$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto=="0"){
echo'Нет такого квеста!';
include"inc/down.php";exit;
}
$q = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `quests` WHERE `quest` = '$q[quest]'");
$quest = mysql_fetch_array($req);
echo"<b><font color=#007F46> $quest[name] </font></b> <font color=#FF6A00> [yes] </font><br/>";
echo"<br/><font color=#A0A0A0>$quest[mission] </font><br/><br/>";
}
}else{
echo "<div class=inoy>";
echo"<a href=\"index.php?mod=quests&amp;act=start\">Активные</a>";
echo"<a href=\"index.php?mod=quests&amp;act=complete\">Завершёные</a>";
echo "</div>";
}
echo"<br/><div class=inoy><a href=\"index.php?\">Назад</a></div>";
break;
case 'exit':
setcookie('log', '');
setcookie('pas', '');
session_destroy();
header ('Location: index.php?');exit;

break;
}
include($path.'inc/down.php');
}
?>
