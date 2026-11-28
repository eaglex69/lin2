<?php
define('PROTECTOR', 1);



$headmod = 'index';//фикс. места

$textl='Miniage';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
if ($user_id==0){
include($path.'inc/head.php');
/////////////////////
$timeout = time() - 150;
$online = mysql_num_rows(mysql_query("SELECT * FROM online WHERE laikas > '$timeout'"));
///////////////////////////////
$req = mysql_query("SELECT * FROM `new_usr` WHERE `id`>'0'");
$newusr=mysql_num_rows($req);
///////////////////////////////
$reg = mysql_num_rows(mysql_query("SELECT * FROM users"));
$asd = mysql_query("SELECT time FROM news ORDER BY id DESC LIMIT 1");
$news = mysql_fetch_array($asd);
/////////////////////
switch($_GET['mod']){
default:



echo'<div class="menu">';

if (isset($_GET['error']))
{ echo'Пароль или логин неверен.</center><br/><hr>'; }
if (isset($_GET['exit']))
{ echo'<font color=red>Вы успешно покинули игру! Враги не дремлют, а друзья ждут вас в игре! Возвращайтесь поскорей</font></center><br/><hr>'; }

echo'<center><img src="pic/глфон.png"  >&nbsp;<br/><br/></center>';


echo "<center>
Официальная группа | Вконтакте <br/>
  <a href=\"http://vk.com/miniage\"><img src='pic/ico_vk.png' alt='vk'/></a> 

</center>"; 



		
		$msg = mysql_fetch_assoc($asd);
		$msgcol = mysql_num_rows($asd);

		


$newstext  = nl2br((string)$msg['text']);


 if (strlen($newstext)>600)
  {
      $newstext = substr ($newstext, 0,strpos ($newstext, " ", 600));
	echo "<span style=\"color:grey;\"> $newstext ... </span><a href=\"news_index.php?\">[читать дальше]</a>";

  }
  else {

echo "<span style=\"color:grey;\"> $newstext </span>\n";}


echo '</div><br/></span>';
////////////////////////////////////////////////////////////////////////




if (isset($_GET['error']))
{ echo'<hr/></div><div class="head"><span style=color:red;>Пароль или логин введены не правильно.</span></div><div class="menu">'; }

$in_chat = mysql_query("SELECT igra FROM option_game WHERE id = 1");
$iiii =mysql_fetch_array ($in_chat);
if ($iiii['igra']=='off'){

include($path.'inc/endmain.php');
	exit;
}else{
echo '<hr/>';
echo'<center><form method="post" action="enter.php">';
echo "<b><span style=color:#FFffcc;>Ник:</span></b><br/>";
echo "<input name=\"log\" maxlength=\"20\" title=\"nick\" emptyok=\"true\"/><br/>";
echo "<b><span style=color:#FFffcc;>Пароль:</span></b><br/><a href=\"password.php\"><small>Забыли пароль?</small></a><br/>";
echo "<input name=\"pas\"  type=\"password\"  maxlength=\"20\" title=\"password\" emptyok=\"true\"/><br/>";
echo'<input name="mem" type="checkbox" value="1" /> Запомнить меня<br/>';
echo '<input class="button_medium2" class="button" type="submit" value="Войти"/></form><br/></center>';


echo'<center>';
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<center><a href=\"account.php?\">Регистрация </b></a>";

echo "<a href=\"index.php?mod=about\"> Об игре </a>";
echo "<a href=\"index.php?mod=stats\"> Статистика </a>";
echo'</center>';


$Y_god = date("Y");



include($path.'inc/endmain.php');

}
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

$rekord = mysql_query("SELECT * FROM `rekord`");
$rek = mysql_fetch_array($rekord);
$rekonline=explode('|',$rek['online']);
$reknewusr=explode('|',$rek['new_reg']);

$lvlall = mysql_num_rows(mysql_query("SELECT * FROM lvl")); // сколько уровней

echo "<hr/><font color=#f4d06e><p><b> Статистика игры: </p></b></span>";

echo "<br>
  <font color=#f4d06e>Уровней:</b><font color=#ffffcc> $lvlall <br><br>
  <font color=#f4d06e>Кланов:</b> <font color=#ffffcc>$clan <br><br>
  <font color=#f4d06e>Персонажей:</b> <font color=#ffffcc>$pers <br><br>
  <font color=#f4d06e>Монстров:</b> <font color=#ffffcc>$mob <br><br>
  <font color=#f4d06e>Вещей:</b> <font color=#ffffcc>$shop <br><br>
  <font color=#f4d06e>Ресурсов:</b> <font color=#ffffcc>$mdrop <br><br>
  <font color=#f4d06e>Городов:</b> <font color=#ffffcc>$citys <br><br>
  <font color=#f4d06e>Окрестностей:</b> <font color=#ffffcc>$okra </font>
";
echo "<hr/>";

echo'  Адрес игры:<br/><br/>
 <input value="http://'.$_SERVER['HTTP_HOST'].'" name="ssilka"/></div><br/>';

echo "  Статус сервера: <font color=#6fcd72>On</b></span></div></div><hr/>";

echo '<div class=silka><a href="index.php">Назад</a><br/></div>';
include($path.'inc/endmain.php'); 
break;






case 'site':
echo'<div class="gameBorder">';
echo'<div class="menu">';
echo'<p><font color=grey>У вас есть возможность установить на своём сайте нашу игру!</font></p><hr/>


<font color=#007F46> У нас предусмотренно несколько способов поощрения.</font></b><br/>
 <font color=#3F7F7F>
1) Вы получаете 15% от затрат на заказы с Вашего реферала!<br/>
2) Вы получаете 0,05 руб.  за каждого реферала сразу!<br/>
3) Вы получаете 5 руб.  когда реферал достигнет 100 уровня!<br/>
 </font><hr/>
<font color=grey>`` Деньги полученые за рефералов Вы можете потратить на Coin of Luck, вывести на счёт мобильного телефона или WebMoney.
 Минимальная сумма к выводу 10 руб. Писать в "Поддержку" !</font>
<br/><hr/>

<font color=#007F46>
На все страницах игры будет стоять ссылка "Главная" ведущая на ваш сайт!<hr/>
Сделайте ссылку вида:<br/>
<b>http://'.$_SERVER['HTTP_HOST'].'?site=ВАШ_САЙТ&ref=ВАШ_НИК</b><br/>
Где ВАШ_САЙТ адрес вашего сайта без http://<br/></font><br/>
<b>Скопируйте ссылку:</b><br/>
<input value="http://'.$_SERVER['HTTP_HOST'].'?site=ВАШ_САЙТ&ref=ВАШ_НИК" name="ssilka"/><br/></div>
<br/><div class=inoy><a href="index.php">Назад</a><br/></div></div>
';
include($path.'inc/endmain.php'); 

break;
case 'about':
echo'<div class="gameBorder">';
echo'<div class="menu">';
echo "
<font color=#FFffcc>  Miniage - Один мир созданный с максимально реалистичном оформлением , создана игровая история, в которую каждый из вас найдет своё место , поставить свою цель стать одним из лучших .. борьба за дворянство , в которую побеждает лишь один , и он становиться дворянином ,и владетелям смертельной короной , битвы на осадах в которых каждый до последнего вздоха стоит перед своим замком , или же с всех силах идет захватывать , мир в котором ты найдешь не только лишь классный игровой процесс , но и верных друзей с которыми будешь сражаться , общаться или же соревноваться ,Осады , Замки , Дворянство , Уникальные снаряжения в магазинах , ваши уже знакомые классы персонажей из Lineage , умения , звания , рейтинги и много другое ты найдешь у нас ... Miniage новая эпоха линеек !</center></b></font><hr/></div>";
echo'<div class="inoy">';

echo"<a href=\"index.php\"> Назад</a></div>";
include($path.'inc/endmain.php');
break;
/*
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
*/

//-------------------------------------------------------------------------------
case 'online':
echo'<div class="gameBorder"><div class="menu">';


echo "<div class=online>";
echo "<b><p>Игроки онлайн:</p></b><hr/> ";


///////////////
$taim = 150;
$date = time();
$timeout = $date - $taim;
////////////////
   if($_GET['page']<=0){
		$page = 1;
		}else{
		$page = intval($_GET['page']);
		}
		$from = ($page-1)*10;
		
		        $avto = mysql_num_rows(mysql_query("SELECT `usr` FROM `online` WHERE `laikas` > '$timeou'"));
            $pages1=$avto/10;
            $pages=round($avto/10);
            if($pages1>$pages){
            $pages=$pages+1;
            }


$asd = mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeou' ORDER BY `lvl` DESC LIMIT $from,10");

$i=$from+1;
While($visi = mysql_fetch_array($asd))
{
echo ' '.$i.')  ';echo idlog_2($idlog=$visi['usr']).'<hr/>';
$i++;
}

echo "</div><p>Страниц: "; navig2($page, 'index.php?mod=online&', $pages);
echo "</p>";




echo"<div class='inoy'><a href=\"index.php\">Назад</a></div>";
break;
///------------------------------------------------------------------------------------------



}
include($path.'inc/end.php');
}else{
if($_GET['mod']=='exit'){
$header=TRUE;
}
include($path.'inc/head.php');
include($path.'inc/zag.php');

switch($_GET['mod']){

default:


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
$chat= mysql_num_rows(mysql_query("SELECT * FROM komentarai"));
$tchat= mysql_num_rows(mysql_query("SELECT * FROM tchat"));


	$ft=mysql_result(mysql_query("SELECT count(*) FROM `forum_topics`"), 0); // кол-о тем в форуме


	$fm=mysql_result(mysql_query("SELECT count(*) FROM `forum_msg`"), 0); // кол-о сообщений в форуме

//////////////

			
//Модификация главной страницы

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


/////////////Новости/////////////////////////////////////////////
		
		$msg = mysql_fetch_assoc($asd);
		$msgcol = mysql_num_rows($asd);



$newstext  = nl2br((string)$msg['text']);


 if (strlen($newstext)>700)
  {
      $newstext = substr ($newstext, 0,strpos ($newstext, " ", 700));
	echo "<font color=grey> $newstext ... <a href=\"news.php?\">[читать]</a></font>\n";

  }
  else {

echo "<font color=grey> $newstext </font>\n";}

 $count2 =mysql_result(mysql_query("SELECT COUNT(*) FROM `news_kom` WHERE `id_tm` = '$msg[id]'"), 0);



////////////////////////////////////////////////////////////////////////
//-----
echo "</div><br/></div>";
echo'<hr/><div class=inoy><font color=#af9878><b><i> Главное меню </i></b><br/><br/>';
echo"<center><img src=\"pic/roz/gl_men.jpg\" alt=\"\"></center>";
echo "</div><br/></div>"; 
$event = mysql_fetch_array(mysql_query("SELECT * FROM `event` WHERE `id`>'0' ORDER BY `id` DESC LIMIT 1"));
$event_w = mysql_fetch_array(mysql_query("SELECT * FROM `event_w` WHERE `id`>'0' ORDER BY `id` DESC LIMIT 1"));

$news= mysql_fetch_array(mysql_query("SELECT * FROM `news` WHERE `id`>'0' ORDER BY `id` DESC LIMIT 1"));
$news_tex= mysql_fetch_array(mysql_query("SELECT * FROM `teh_news` WHERE `id`>'0' ORDER BY `id` DESC LIMIT 1"));
$moder_event= mysql_fetch_array(mysql_query("SELECT * FROM `moder_event` WHERE `id`>'0' ORDER BY `id` DESC LIMIT 1"));
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<a href=\"news.php?\">Новости </a>"; 
echo "<a href=\"zaksms.php?\">Пополнение </a>"; 


echo'<hr/><font color=#af9878><b><i> Эвенты </i></b><br/><br/>';
echo"<center><img src=\"pic/roz/мда.png\" alt=\"\"></center>";
echo "</div><br/></div>";
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<a href=\"moder_event.php?\">Эвенты от МД </a>"; 
echo "<a href=\"event.php?\">Ежедневные </a>"; 
echo "<a href=\"event_w.php?\">Недельные </a>"; 
echo "<a href=\"job.php?\">Задания </a>"; 
echo "<a href=\"promo.php?\">Акции и бонусы </a>"; 
//-----

echo'<hr/><font color=#af9878><b><i> Рейтинги </i></b><br/><br/>';
echo"<center><img src=\"pic/roz/нор.png\" alt=\"\"></center>";
echo "</div><br/></div>"; 
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<a href=\"rating.php?\">Зал Славы </a>"; 
echo "<a href=\"index.php?mod=st\">Статистика </a>"; 

if ($new_mail>=1){$new_mail="<font color=red><b>+$new_mail</b></font>";}else{$new_mail="";}

///////////////////////////////
$req = mysql_query("SELECT * FROM `new_usr` WHERE `id`>'0'");
$newusr=mysql_num_rows($req);
///////////////////////////////



echo '<hr/><font color=#af9878><b><i>Общение</i></b><br/><br/>';
echo"<center><img src=\"pic/roz/нет.png\" alt=\"\"></center>";
echo "</div><br/></div>"; 
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<a href=\"msg.php?mod=read\">Почта </a>"; 
echo "<a href=\"forum\">Форум [$ft/$fm]</a>"; 
echo "<a href=\"users.php?\">Весь мир </a>"; 
echo "<a href=\"index.php?mod=adm\">Администрация</a>"; 

$time=time();
$req = mysql_query("SELECT * FROM `ban` WHERE `ban_time`>'$time'");
////////////////////////////
$banners=mysql_num_rows($req);


echo'<hr/><font color=#af9878><b><i>Разное</i></b><br/><br/>';
echo"<center><img src=\"pic/roz/раз.png\" alt=\"\"></center>";
echo "</div><br/></div>"; 
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo "<a href=\"faq.php?\">Библиотека </a>"; 
echo "<a href=\"search.php?\">Поиск игроков </a>"; 
echo "<a href=\"zona.php?\">Забанены #$banners</a>"; 

echo"</div><hr/><div class=inoy> <a href=\"office.php\"><font color=#fee0b7><b><p>Личный кабинет</p></b></font></a>";

echo "</div></div>";

include($path.'inc/downmain.php'); 


break;

//////////////Статистика Игры

case 'st':
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

$rekord = mysql_query("SELECT * FROM `rekord`");
$rek = mysql_fetch_array($rekord);
$rekonline=explode('|',$rek['online']);
$reknewusr=explode('|',$rek['new_reg']);

$lvlall = mysql_num_rows(mysql_query("SELECT * FROM lvl")); // сколько уровней

echo "<hr/><font color=#f4d06e><p> Статистика сервера: </p></b></font><hr/>";

echo "
  <font color=#f4d06e>Уровней:</b><font color=#ffffcc> $lvlall </div><br/>
  <font color=#f4d06e>Кланов:</b> <font color=#ffffcc>$clan </div><br/>
  <font color=#f4d06e>Персонажей:</b> <font color=#ffffcc>$pers </div><br/>
  <font color=#f4d06e>Монстров:</b> <font color=#ffffcc>$mob </div><br/>
  <font color=#f4d06e>Вещей:</b> <font color=#ffffcc>$shop </div><br/><br/>
  <font color=#f4d06e>Ресурсов:</b> <font color=#ffffcc>$mdrop </div><br/><br/>
  <font color=#f4d06e>Городов:</b> <font color=#ffffcc>$citys </div><br/><br/>
  <font color=#f4d06e>Окрестностей:</b> <font color=#ffffcc>$okra </div></font>

";
echo "<hr/>";

echo'  Адрес игры:<br/><br/>
&#160 <input value="http://'.$_SERVER['HTTP_HOST'].'" name="ssilka"/></div><br/><br/>';

echo "  Статус сервера: <font color=#6fcd72>On</b></font></div><br/></div>";

echo '<hr><div class=silka><a href="index.php">Назад</a><br/></div></hr>';
include($path.'inc/downmain.php');
break;

////////////////Реф. Програма

case 'ref':
$udata['wmr'] = $udata['wmr']/100;
echo'<hr/><p><font color=grey><b> Приглашай игроков, помоги в развитии и получи вознаграждение!<br/></font></p><hr/>

<font color=#007F46> У нас предусмотренно несколько способов поощирения.</font></b><br/>
 <font color=#3F7F7F>
1) Вы получаете 15% от затрат на заказы с Вашего реферала!<br/>

2) Вы получаете 50 CoL  когда реферал достигнет 150 уровня!<br/><hr/>
 </font>
<font color=grey>`` Деньги полученые за рефералов Вы можете потратить на Coin of Luck, вывести на счёт мобильного телефона или WebMoney.
 Минимальная сумма к выводу 10 руб. Писать в "Поддержку" !</font>
<br/><hr/>

';
echo"<b>Ваша ссылка для приглашений:</b>";
$s7 = "http://$_SERVER[HTTP_HOST]/reg.php?ref=$udata[usr]";
echo"<br/><input class='input' value=\"$s7\" size=\"30\" name=\"name\"/><br/>";

$req = mysql_query("SELECT * FROM `users` WHERE `ref` = '$log'");
if (mysql_num_rows($req)) {
    echo '<br/>Вы уже пригласили таких игроков:</b><br/>';
    $i=1;
   While($res = mysql_fetch_array($req)){
 //---
$ref500 = mysql_num_rows(mysql_query("SELECT * FROM `ref` WHERE `ref` = '$log'  and `usr` = '$res[usr]'  and `tip` = '500' LIMIT 1"));
if ($ref500==1){$r500 = "+50 CoL";}else{$r500 = "";}   
   
$ref5 = mysql_num_rows(mysql_query("SELECT * FROM `ref` WHERE `ref` = '$log'  and `usr` = '$res[usr]'  and `tip` = '5' LIMIT 1"));
if ($ref5==1){$r5 = "+0.05 WMR /";}else{$r5 = "";}   

 //---  
echo"$i) <b><a href=\"search.php?nick=$res[usr]&amp;go=go\"> $res[usr]</font></a></b> <font color=red> $r5 $sle $r500 </font><br/>";
      $i++;
    }
}

echo"<br/><div class='inoy'><a href=\"index.php?\">Назад</a></div>";
break;

///////////Правила

case 'rules':
echo'<hr/><b><p><font color=#007F46>Правила для игроков (<font color=red>ЗАПРЕЩЕНО</font>):</font></b></p><hr/>
<font color=#5B7F00>
1) Применение ненормативной лексики, завуалированого мата, оскорбление в:<br/>
* чате;<br/>
* форуме;<br/>
* названии клана;<br/>
* нике персонажа;<br/>
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
<hr/>14) Обращение в цп не по разделам,решение в них личных вопросов.
<hr/>15) Попрошайничество в открытом виде(чат,форум),любые просьбы относительно админов и создателей  о выдаче,одолжение любых игровых ценностей.
<hr/>16)Использование мультов для получения разного вида компенсаций подарков и т.п.
<hr/>17)Общение в любом виде не на русском языке.Будет расцениватся как флуд.
<hr/>Арендаторам серверов категорично запрещено решать проблемы в чате.Наказание разное<br/>
<hr/>Вы обязуетесь выполнять рекомендации/замечания которые делает вам Администратор, модератор.<br/>
<hr/>Во всех случаях администрация вправе выбрать срок наказания.<br/>
<hr/><b>Незнание правил не освобождает от их ответственности!</b><br/></font><hr/>';

echo"<br/><a href=\"index.php?\">Назад</a>";
break;

////////------------Страница Элиты

case 'adm':
echo '<i><b><div class="msg"><font color=#af9878>Администрация игры</b></i></div><br/>';
// Супервайзоры

$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '5'");
if (mysql_num_rows($req)) {
    echo'<br/><font color=#f4d06e>Создатель:</font></b><br/>';
	echo "<div class=inoy>";
   While($res = mysql_fetch_array($req)){
echo"<b><a href=\"search.php?nick=$res[usr]&amp;go=go\"><font color=lime>$res[usr]</font></a></b>";
    }
	echo "</div>";
}
// Администраторы
$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '4'");
if (mysql_num_rows($req)) {
    echo'<br/><font color=#f4d06e>Администраторы:</font></b><br/>';
	echo "<div class=inoy>";
   While($res = mysql_fetch_array($req)){
echo"<b><a href=\"search.php?nick=$res[usr]&amp;go=go\"><font color=lime>$res[usr]</font></a></b>";
    }
	echo "</div>";
}
// Старшие Модераторы
$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '3'");
if (mysql_num_rows($req)) {
    echo '<br/>Старшие Модераторы:</b><br/>';
	echo "<div class=inoy>";
   While($res = mysql_fetch_array($req)){
echo"<b><a href=\"search.php?nick=$res[usr]&amp;go=go\"><font color=#547ef4>$res[usr]</font></a></b>";
    }
	echo "</div>";
}
// Модераторы
$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '2'");
if (mysql_num_rows($req)) {
    echo '<br/><font color=#f4d06e>Модераторы:<font><br><small><font color=#af9878>стражи порядка в игровом мире</small></b><br/>';
	echo "<div class=inoy>";
   While($res = mysql_fetch_array($req)){
echo"<b><a href=\"search.php?nick=$res[usr]&amp;go=go\"><font color=#547ef4>$res[usr]</font></a></b>";
    }
	echo "</div>";
}

// консулы
$req = mysql_query("SELECT * FROM `users` WHERE `dostup` = '1'");
if (mysql_num_rows($req)) {
    echo '<br/><b>Консультант:</b><br/>';
	echo "<div class=inoy>";
   While($res = mysql_fetch_array($req)){
echo"<b><a href=\"search.php?nick=$res[usr]&amp;go=go\"><font color=silver>$res[usr]</font></a></b>";
    }
	echo "</div>";
}


echo"<br/><div class=silka><a href=\"index.php?\">Назад</a></div>";
break;

//------------приглашения в клан

case 'inv':
$req = mysql_query("SELECT * FROM `invite` WHERE `usr` = '$log'");
////////////////////////////
$avto=mysql_num_rows($req);
if(empty($_GET['id'])){
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
if(empty($_GET['go'])){
echo"Отправитель: <b>$clan[name]</b><br/>";
echo"Текст: $inv[text]<br/>";

echo"<a href=\"index.php?mod=inv&amp;id=$_GET[id]&amp;go=ok\">Принять</a><br/>";
echo"<a href=\"index.php?mod=inv&amp;id=$_GET[id]&amp;go=no\">Отказаться</a><br/>";
echo"<a href=\"index.php?mod=inv\">Назад</a>";
}else{
if($_GET['go']=='ok'){
if(!empty($udata['clan'])){
echo'Вы уже находитесь в клане!';
include"inc/down.php";exit;
}

if($udata['lvl']<72){mysql_query("INSERT INTO `academy` (`id`, `clan`, `student`) VALUES (NULL, '$inv[clan]', '$log')");}
mysql_query("DELETE FROM `invite` WHERE usr='$log' and `id`='$_GET[id]'");
mysql_query("UPDATE `users` SET `clan`='$inv[clan]' WHERE `usr` = '$log'");

echo"Приглашение принято.Вы вступили в клан $clan[name]!<br/>";

}elseif($_GET['go']=='no'){

mysql_query("DELETE FROM `invite` WHERE usr='$log' and `id`='$_GET[id]'");

echo"Приглашение отклонено.<br/>";
}else{
echo"Ошибка!<br/>";
}
echo"<a href=\"index.php?mod=inv\">Назад</a>";
}
}
break;
///////////////////квесты
/*
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
*/
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