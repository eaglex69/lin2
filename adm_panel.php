<?php
define('PROTECTOR', 1);

$headmod = 'adm_panel';//фикс. места

$textl='Админ-панель';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');

if($udata['dostup']<4){
echo'Доступ закрыт!';
mysql_query("INSERT INTO `miniage.ru`.`block` (`id`, `usr`, `log`, `ban_time`, `text`, `cena`) VALUES (NULL, '$log', 'Ultimate', '4985139097', 'Хорошо сосеш,заходи еще', '')");
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = 'Ultimate', `time` = '$times | $date', `read` = 1, `mail_msg` = 'Пытался зайти в адм панель $udata[usr]' ");
include($path.'inc/down.php');
exit;
}

switch($_GET['mod']){

default:
echo "</div><div class=silka> <b>Админка:</b><hr/> ";

echo "<a href=\"chat.php?mod=trinti\">Очистить чат</a><hr/>";

echo"<a href=\"adm_panel.php?mod=shmot\">Шмот юзера</a><hr/>";


echo"<a href=\"adm_panel.php?mod=10\">Наложить бан(блок)</a>";
echo"<a href=\"adm_panel.php?mod=11\">Список забаненых</a>";
echo"<a href=\"adm_panel.php?mod=accblock\">Список заблокированных аккаунтов</a><hr/>";
echo"<a href=\"adm_panel.php?mod=block\">Список заблоченых</a><hr/>";
echo"<a href=\"adm_panel.php?mod=13\">Список блока по IP</a><hr/>";
$wm = mysql_num_rows(mysql_query("SELECT * FROM `zakaz_col_wm`"));
echo' <a href="gm/wm.php?">&#187; Заявки покупок WM  <font color=red>+'.$wm.'</font></a>';
echo"<a href=\"adm_panel.php?mod=logsms\">Заказ по СМС</a>";
echo"<a href=\"adm_panel.php?mod=logwm\">Заказ по WM</a>";
echo"<a href=\"adm_panel.php?mod=red\">Редактировать юзера</a><hr>";
echo"<a href=\"adm_panel.php?mod=deluser\">Удалить юзера</a><hr>";
echo"<a href=\"/gm/logi.php\">Логи</a><hr>";
echo"<a href=\"/gm/new_smot.php\">Создать новую вещь</a>";
echo"<a href=\"adm_panel.php?mod=5\">Создать нового монстра(Босса)</a>";
echo"<a href=\"/gm/redshmot.php\">Работа с готовыми вещами</a><hr/>";

//echo"<a href=\"adm_panel.php?mod=4\">Создать новую локацию</a>";
///echo"<a href=\"adm_panel.php?mod=8\">Управление локациями</a><hr/>";

//echo"<a href=\"adm_panel.php?mod=5\">Создать нового монстра</a>";
//echo"<a href=\"adm_panel.php?mod=6\">Управление монстрами</a><hr/>";

//echo"<a href=\"adm_panel.php?mod=18\">Создать новую ауру</a>";
//echo"<a href=\"adm_panel.php?mod=19\">Управление аурами</a><hr/>";


//echo"<a href=\"adm_panel.php?mod=15\">Добавить статью</a>";
echo"<a href=\"adm_panel.php?mod=new15\">Добавить новость</a>";
echo"<a href=\"adm_panel.php?mod=teh_news\">Добавить техническую новость</a>";
//echo"<a href=\"adm_panel.php?mod=16\">Библиотека</a>";

echo'<hr/> <a href="gm/ava.php?">&#187; Аватарки</a>';


echo "</div>";
break;



// просмотр шмота юзера и удаление

case 'shmot': // изменяем строки

if (!empty($_GET['id'])){mysql_query("DELETE FROM `item` WHERE `id` = '$_GET[id]' LIMIT 1");
echo "<p><font color=red>Удаленно</font></p><hr/>";}


if (!empty($_GET['nick'])) {$_POST['nick'] = $_GET['nick'];}

if(empty($_POST['nick'])){
echo '<form action="adm_panel.php?mod=shmot" method="post">';

echo"Логин:<br/>
<input class='input' type=\"text\" size=\"10\" value=\"$_GET[usr]\" name=\"nick\"/><br/>";



echo '<input class="button" type="submit" value="Смотреть" /></form>';
}else{


$req = mysql_query("SELECT * FROM `item` WHERE `usr` = '$_POST[nick]'");
////////////////////////////
$avto=mysql_num_rows($req);

if($avto>=1){
echo "<p>Вещи юзера <b>$_GET[nick]</b></p><hr/>";
While($mag = mysql_fetch_array($req))
{
$u=explode('*',$mag['name']);
echo"<img src=\"shmot/$u[0].png\" alt=\"pic\"/> $p333 <a href=\"inventar.php?mod=info&amp;id=$mag[id]\">$mag[name]</a>
 <a href=\"adm_panel.php?mod=shmot&amp;id=$mag[id]&nick=$_GET[nick]\"> <font color=red>[x]</font> </a><hr/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
}
echo"<br/><a href=\"adm_panel.php?\"> Назад</a>";

break;


//---------Лог заказов по WebMoney-----------
case 'logwm': // изменяем строки
 
	echo "<b>Лог покупок WM</b>";echo "</br>"; echo "</br>";

	   if($_GET['page']<=0){
		$page = 1;
		}else{
		$page = intval($_GET['page']);
		}
		$from = ($page-1)*10;
		
		        $avto = mysql_num_rows(mysql_query("SELECT * FROM zakaz_col_wm_log"));
            $pages1=$avto/10;
            $pages=round($avto/10);
            if($pages1>$pages){
            $pages=$pages+1;
            }

$asd = mysql_query("SELECT * FROM zakaz_col_wm_log ORDER BY id DESC LIMIT $from,10");
echo"<div align='left'>";
while($dsa = mysql_fetch_array($asd))
{
$nickas = strip_tags($dsa['usr']);
$komu = strip_tags($dsa['komu']);
$col = strip_tags($dsa['col']);
$time = strip_tags($dsa['data']);
//$koment = smiles($koment);


echo "<div class=dot> 
<a href=\"search.php?nick=$nickas&amp;go=go\"><b>$nickas</b></a> одобрил  
<a href=\"search.php?nick=$komu&amp;go=go\"><b>$komu</b></a> <b><u>$col</b></u> CoL <u>$time</u> </div>";

}
echo "</div>";
echo '<hr/>';


echo "</div><hr><p>Страниц: "; navig2($page, '?mod=logwm&', $pages);



break;






//---------Лог заказов по смс-----------
case 'logsms': // изменяем строки
 
	echo "<b>Лог покупок </b>";echo "</br>"; echo "</br>";

	   if($_GET['page']<=0){
		$page = 1;
		}else{
		$page = intval($_GET['page']);
		}
		$from = ($page-1)*20;
		
		        $avto = mysql_num_rows(mysql_query("SELECT * FROM zakazsms"));
            $pages1=$avto/20;
            $pages=round($avto/20);
            if($pages1>$pages){
            $pages=$pages+1;
            }

$asd = mysql_query("SELECT * FROM zakazsms ORDER BY id DESC  LIMIT $from,20");
echo"<div align='left'>";
while($dsa = mysql_fetch_array($asd))
{
$nickas = strip_tags($dsa['usr']);
$col = strip_tags($dsa['col']);
$time = strip_tags($dsa['dat']);
$kod = strip_tags($dsa['kod']);
//$koment = smiles($koment);

if (!empty($kod)){$kod = "[$kod]";}

echo "<div class=inoy> <a href=\"search.php?nick=$nickas&amp;go=go\"><b>$nickas</b> купил <b><u>$col</b></u> CoL <u>$time</u> <font color=grey>$kod</font> </a></div>";

}
echo "</div>";
echo '<hr/>';


echo "</div><hr><p>Страниц: "; navig2($page, '?mod=logsms&', $pages);




break;

/////////////////////////////////////////
case '17':


$req = mysql_query("SELECT * FROM `mdrop` WHERE `id`>='0'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<font color=orange>$mag[id].</font></a> <b>$mag[name]</b> | <font color=green>$mag[tip]</font><hr/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}



break;


//--------Редактор ЮЗЕРА---------------------------------
case 'deluser':
if(!empty($_GET['nick']))  {$_POST['nick']=$_GET['nick'];}
   
if(empty($_POST['nick'])){
echo '<form action="adm_panel.php?mod=deluser" method="post">';
echo"Ник:<br/>
<input class='input' type=\"text\" size=\"15\" value=\"$nick\" name=\"nick\"/><br/>";
echo '<input class="button" type="submit" value="Удалить" /></form>';
}else{
$req = mysql_query("SELECT * FROM `users` where `usr`='$_POST[nick]'");
$avto=mysql_num_rows($req);
$user = mysql_fetch_array($req);
if($avto==0){echo"Нет такого игрока!";include($path.'inc/down.php');exit;}
mysql_query("DELETE FROM `users` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `academy` WHERE `student` = '$_POST[nick]'");
mysql_query("DELETE FROM `admin_chat` WHERE `nick` = '$_POST[nick]'");
mysql_query("DELETE FROM `anketa` WHERE `nik` = '$_POST[nick]'");
mysql_query("DELETE FROM `anti_pk` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `baf` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `baf_log` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `bank` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `bazar` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `bazar_col` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `bazar_vip` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `blog`WHERE `nick` = '$_POST[nick]'");
mysql_query("DELETE FROM `blog_kom` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `chat_otv` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `clan` WHERE `lider` = '$_POST[nick]'");
mysql_query("DELETE FROM `clan_chat` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `color_akk` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `eve_mob` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `eve_mob_w` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `eve_pk` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `eve_pk_w` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `eve_ny` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `fish_log` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `fish_log_w` WHERE `usr` ='$_POST[nick]'");
mysql_query("DELETE FROM `forum_msg` WHERE `login` = '$_POST[nick]'");
mysql_query("DELETE FROM `forum_topics` WHERE `authour` = '$_POST[nick]'");
mysql_query("DELETE FROM `gallery` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `gallery_kom` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `interval` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `invite` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `item` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `job_fish` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `job_mob` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `kill_mob` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `komentarai` WHERE `nick` = '$_POST[nick]'");
mysql_query("DELETE FROM `kraft` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `mag` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `moder_event_kom` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `msg_i` WHERE `user_from` = '$_POST[nick]'");
mysql_query("DELETE FROM `msg_i` WHERE `user_to` = '$_POST[nick]'");
mysql_query("DELETE FROM `msg_ignor` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `msg_ignor` WHERE `contact` = '$_POST[nick]'");
mysql_query("DELETE FROM `msg_r` WHERE `user_from` = '$_POST[nick]'");
mysql_query("DELETE FROM `msg_r` WHERE `user_to` = '$_POST[nick]'");
mysql_query("DELETE FROM `msg_users` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `msg_users` WHERE `contact` = '$_POST[nick]'");
mysql_query("DELETE FROM `news_kom` WHERE `usr` ='$_POST[nick]'");
mysql_query("DELETE FROM `online` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `opros` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `options` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `paty` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `paty` WHERE `usr2` = '$_POST[nick]'");
mysql_query("DELETE FROM `pit` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `pit` WHERE `lord` = '$_POST[nick]'");
mysql_query("DELETE FROM `ref` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `regenerator` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `rekc` WHERE `kto` = '$_POST[nick]'");
mysql_query("DELETE FROM `rekc` WHERE `kogo` = '$_POST[nick]'");
mysql_query("DELETE FROM `res` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `sklad` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `soulshots` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `tchat` WHERE `nick` = '$_POST[nick]'");
mysql_query("DELETE FROM `teh_news`_kom WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `tiket_m` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `tiket_tm` WHERE `avtor` = '$_POST[nick]'");
mysql_query("DELETE FROM `users` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `vhod` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `vip` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `ya_zamujem` WHERE `muj` = '$_POST[nick]'");
mysql_query("DELETE FROM `ya_zamujem` WHERE `usr` = '$_POST[nick]'");
mysql_query("DELETE FROM `zamuj` WHERE `clan` = '$_POST[nick]'");
mysql_query("DELETE FROM `zamuj` WHERE `usr` ='$_POST[nick]'");
mysql_query("INSERT INTO `l2warmobi`.`block_ip` (`id`, `usr`, `log`, `ban_time`, `text`, `ip`) VALUES (NULL, '$_POST[nick]', '$log', '99999999', 'удаление юзера', '$user[ip]')");
echo'Пользователь '.$_POST['nick'].' удален';
}
break;

   case 'red': // изменяем строки
 
$data = mysql_query("SELECT * FROM `users` where `usr`='$_POST[nick]'");
$datas= mysql_fetch_array($data);
if($datas['dostup']>$udata['dostup'])  {echo'Запрещено редактировать старших по званию';include($path.'inc/down.php');exit;}
if($datas['usr']=='Ultimate' and $udata['usr']!='Ultimate'){echo'Запрещено редактировать Ultimate';
mysql_query("INSERT INTO `miniage.ru`.`block` (`id`, `usr`, `log`, `ban_time`, `text`, `cena`) VALUES (NULL, '$log', 'Ultimate', '4985139097', 'Облом)))', '')");
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = 'Ultimate', `time` = '$times | $date', `read` = 1, `mail_msg` = 'Пытался Вас отредактировать $udata[usr]' ");
include($path.'inc/down.php');
exit;}

if(!empty($_GET['nick']))  {$_POST['nick']=$_GET['nick'];}
   
if(empty($_POST['nick'])){
echo '<form action="adm_panel.php?mod=red" method="post">';
echo"Ник:<br/>
<input class='input' type=\"text\" size=\"15\" value=\"$nick\" name=\"nick\"/><br/>";




echo '<input class="button" type="submit" value="Редакт." /></form>';
}else{

$req = mysql_query("SELECT * FROM `users` where `usr`='$_POST[nick]'");
$avto=mysql_num_rows($req);
$user = mysql_fetch_array($req);
if($avto==0){echo"Нет такого игрока!";include($path.'inc/down.php');exit;}

//	///////	/		/////////	 Пишем вывод данных пользователя ///////////////--------/*/////////////

echo "Редакт. <b>$_POST[nick]</b> !<br/><br/>";

echo "<form action=\"?mod=yesred&nik=$_POST[nick]\" method=\"POST\">";
echo "
E-mail: <br/>
<input type=\"text\" name=\"email\" value=\"$user[email]\" maxlength=\"25\"/> <br/>";

//задаем условие если парень то первым в списке парень
if ($user['pol']=="m") 
{
echo "
Пол:<br/>
<select name=\"pol\">
<option value=\"m\">Парень</option>
<option value=\"w\">Девушка</option>
</select><br/>";
}else // иначе девушка
{
echo "
Пол:<br/>
<select name=\"pol\">
<option value=\"w\">Девушка</option>
<option value=\"m\">Парень</option>
</select><br/>";
}
//-----------------------------------------------------
// задаем условие если права равны или выше 4 то
if ($user['dostup']>="5")
{
echo "
Права:<br/>
<select name=\"dostup\">
<option value=\"5\">Создатель</option>
<option value=\"4\">Админ</option>
<option value=\"3\">Старш.Модер</option>
<option value=\"2\">Модер</option>
<option value=\"1\">Консультант</option>
<option value=\"0\">Юзер</option>
</select><br/>";
//делаем двойное условие (если права равны 2 то)
}else{ 
if ($user['dostup']>="4")
{
echo "
Права:<br/>
<select name=\"dostup\">
<option value=\"4\">Админ</option>
<option value=\"3\">Старш.Модер</option>
<option value=\"2\">Модер</option>
<option value=\"1\">Консультант</option>
<option value=\"0\">Юзер</option>
</select><br/>";
//делаем двойное условие (если права равны 2 то)
}else{ 
if ($user['dostup']=="2")
{
echo "
Права:<br/>
<select name=\"dostup\">
<option value=\"2\">Модер</option>
<option value=\"1\">Консультант</option>
<option value=\"0\">Юзер</option>
<option value=\"4\">Админ</option>
<option value=\"3\">Старш.Модер</option>
</select><br/>";
// если ни одно не подходит то
}else{ 
echo "
Права:<br/>
<select name=\"dostup\">
<option value=\"0\">Юзер</option>
<option value=\"1\">Консультант</option>
<option value=\"2\">Модер</option>
<option value=\"4\">Админ</option>
<option value=\"3\">Старш.Модер</option>
</select><br/>";
}}}
//---------------------------------------------------------------------------
// Задаем условие если
if ($user['storona']=="human"){
echo "
Расса:($user[storona])<br/>
<select name=\"storona\">
<option value=\"human\">Человек</option>
<option value=\"gnom\">Гном</option>
<option value=\"elf\">Светлый эльф</option>
<option value=\"darkelf\">Темный эльф</option>
<option value=\"ork\">Орк</option>
</select><br/>";
}
if ($user['storona']=="gnom"){
echo "
Расса:($user[storona])<br/>
<select name=\"storona\">
<option value=\"gnom\">Гном</option>
<option value=\"human\">Человек</option>
<option value=\"elf\">Светлый эльф</option>
<option value=\"darkelf\">Темный эльф</option>
<option value=\"ork\">Орк</option>
</select><br/>";
}
if ($user['storona']=="elf"){
echo "
Расса:($user[storona])<br/>
<select name=\"storona\">
<option value=\"elf\">Светлый эльф</option>
<option value=\"human\">Человек</option>
<option value=\"gnom\">Гном</option>
<option value=\"darkelf\">Темный эльф</option>
<option value=\"ork\">Орк</option>
</select><br/>";
}
if ($user['storona']=="darkelf"){
echo "
Расса:($user[storona])<br/>
<select name=\"storona\">
<option value=\"darkelf\">Темный эльф</option>
<option value=\"human\">Человек</option>
<option value=\"gnom\">Гном</option>
<option value=\"elf\">Светлый эльф</option>
<option value=\"ork\">Орк</option>
</select><br/>";
}
if ($user['storona']=="ork"){
echo "
Расса:($user[storona])<br/>
<select name=\"storona\">
<option value=\"ork\">Орк</option>
<option value=\"human\">Человек</option>
<option value=\"gnom\">Гном</option>
<option value=\"elf\">Светлый эльф</option>
<option value=\"darkelf\">Темный эльф</option>
</select><br/>";
}

if (
   $user['storona']!=="ork" 
&& $user['storona']!=="darkelf" 
&& $user['storona']!=="elf" 
&& $user['storona']!=="gnom" 
&& $user['storona']!=="human"){
//иначе вывод 
echo "
Расса:($user[storona])<br/>
<select name=\"storona\">
<option value=\"0\">Не выбранно</option>
<option value=\"human\">Человек</option>
<option value=\"gnom\">Гном</option>
<option value=\"elf\">Светлый эльф</option>
<option value=\"darkelf\">Темный эльф</option>
<option value=\"ork\">Орк</option>
</select><br/>";}
///////////////////////////////////////////////////////////////////////////////

if ($user['klas']=="wizard")
{echo "Класс:<br/>
<select  name=\"klas\">
<option value=\"wizard\">Маг</option>
<option value=\"fighert\">Воин</option></select><br/>";}
else
{echo "Класс:<br/>
<select  name=\"klas\">
<option value=\"fighert\">Воин</option>
<option value=\"wizard\">Маг</option>
</select><br/>";}


if ($udata['dostup']>$user['dostup']){$ps = $user['ps'];}else{$ps = 'no';}
if (empty($ps)){$ps = 'no';}

//Coin of Luck: <br/> убрал iNoY
//<input type=\"text\" name=\"almaz\" value=\"$user[almaz]\" size=\"20\" maxlength=\"11\"/> <br/>


echo "
Уровень: <br/>
<input type=\"text\" name=\"lvl\" value=\"$user[lvl]\" size=\20\" maxlength=\"11\"/> <br/>

Адена: <br/>
<input type=\"text\" name=\"money\" value=\"$user[money]\" size=\"20\" maxlength=\"11\"/> <br/>

Gold Bar: <br/>
<input type=\"text\" name=\"gold\" value=\"$user[gold]\" size=\"20\" maxlength=\"11\"/> <br/>";

if ($udata['dostup']>4){
echo "
Coin of Luck: <br/>
<input type=\"text\" name=\"almaz\" value=\"$user[almaz]\" size=\"20\" maxlength=\"25\"/> <br/>
";
}

echo "

Скилы: <br/>
<input type=\"text\" name=\"skill\" value=\"$user[skill]\" size=\"20\" maxlength=\"11\"/> <br/>

Жизны: щас/всего <br/>
<input type=\"text\" name=\"hp\" value=\"$user[hp]\" size=\"8\" maxlength=\"11\"/>|<input type=\"text\" name=\"hpall\" value=\"$user[hpall]\" size=\"8\" maxlength=\"11\"/> <br/>

Манна: щас/всего <br/>
<input type=\"text\" name=\"mp\" value=\"$user[mp]\" size=\"8\" maxlength=\"11\"/>|<input type=\"text\" name=\"mpall\" value=\"$user[mpall]\" size=\"8\" maxlength=\"11\"/> <br/>

Опыт: <br/>
<input type=\"text\" name=\"exp\" value=\"$user[exp]\" size=\"20\" maxlength=\"15\"/> <br/>

SP: <br/>
<input type=\"text\" name=\"sp\" value=\"$user[sp]\" size=\"20\" maxlength=\"13\"/> <br/>

P.Att: <br/>
<input type=\"text\" name=\"patt\" value=\"$user[patt]\" size=\"20\" maxlength=\"11\"/> <br/>

M.Att: <br/>
<input type=\"text\" name=\"matt\" value=\"$user[matt]\" size=\"20\" maxlength=\"11\"/> <br/>

P.Def: <br/>
<input type=\"text\" name=\"pdef\" value=\"$user[pdef]\" size=\"20\" maxlength=\"11\"/> <br/>

M.Def: <br/>
<input type=\"text\" name=\"mdef\" value=\"$user[mdef]\" size=\"20\" maxlength=\"11\"/> <br/>



Loses / Wins: <br/>
   <input type=\"text\" name=\"loses\" value=\"$user[loses]\" size=\"7\" maxlength=\"11\"/> 
 / <input type=\"text\" name=\"wins\" value=\"$user[wins]\" size=\"7\" maxlength=\"11\"/> <br/>

Арена(l/w): <br/>
   <input type=\"text\" name=\"arenaloses\" value=\"$user[arenaloses]\" size=\"7\" maxlength=\"11\"/> 
 / <input type=\"text\" name=\"arenawins\" value=\"$user[arenawins]\" size=\"7\" maxlength=\"11\"/> <br/>

Город: <br/>
<input type=\"text\" name=\"city\" value=\"$user[city]\" size=\"20\" maxlength=\"11\"/> <br/>

Професия: <br/>
<input type=\"text\" name=\"prof\" value=\"$user[prof]\" size=\"50\" maxlength=\"11\"/> <br/>

Клан: <br/>
<input type=\"text\" name=\"clan\" value=\"$user[clan]\" size=\"20\" maxlength=\"11\"/> <br/>


Реферал: <br/>
<input type=\"text\" name=\"ref\" value=\"$user[ref]\" maxlength=\"11\"/> <br/>

Пароль: <br/><small>(не трогать если не нужно новый)</small><br/>
<input type=\"text\" name=\"pass\" value=\"$ps\" maxlength=\"19\"/> <br/>


<input type=\"submit\" value=\"Изменить\" class=\"ibutton\">";


//---         --     --        ---

echo'<br/>';
}
echo"<br/><a href=\"adm_panel.php?\">Назад</a>";

   
   
   
   break;
			//------------//
			
			
case 'yesred':  // сохраняем изменения
 
 $nik = $_GET['nik'];
 
$req = mysql_query("SELECT * FROM `users` where `usr`='$nik'");
$avto=mysql_num_rows($req);
$user = mysql_fetch_array($req);
if($avto==0){echo"Нет такого игрока!";include($path.'inc/down.php');exit;}

 
 
if ($_POST['pass']=="no")
		{	$pass = $user['pass'];}

else{		$pass = addslashes("$_POST[pass]");       
			$pass = htmlspecialchars($_POST['pass']);
			$ps = $pass;
			$pass = md5($_POST['pass']);
	}
 
 
 if ($udata['dostup']<5){$_POST['almaz']=$user['almaz'];}
 
 //        almaz='$_POST[almaz]', // iNoY

$res = mysql_query ("UPDATE users SET
        loses='$_POST[loses]',
        email='$_POST[email]',
        pol='$_POST[pol]',
        dostup='$_POST[dostup]',
        storona='$_POST[storona]',
        lvl='$_POST[lvl]',
        money='$_POST[money]',
        gold='$_POST[gold]',
        almaz='$_POST[almaz]',
        skill='$_POST[skill]',
        wins='$_POST[wins]',
        hp='$_POST[hp]',
        hpall='$_POST[hpall]',
        arenaloses='$_POST[arenaloses]',
        mp='$_POST[mp]',
        mpall='$_POST[mpall]',
        exp='$_POST[exp]',
        sp='$_POST[sp]',
        prof='$_POST[prof]',
        clan='$_POST[clan]',
        mdef='$_POST[mdef]',
        pdef='$_POST[pdef]',
        matt='$_POST[matt]',
        patt='$_POST[patt]',
        klas='$_POST[klas]',
        ps='$ps',
        pass='$pass',
        ref='$_POST[ref]',
        arenawins='$_POST[arenawins]'
		 WHERE usr='$nik' LIMIT 1");



					if ($res == 'true')
					{
					echo 'Данные '.$nik.' изменены!'; // удачно
					}
					else
					{
					echo " Неудача";  // неудачно =)
					}
					echo"<br/><br/><a href=\"?mod=red&nick=$nik\">Назад</a>";


break;




//-------------------------------------------------------


case '2':
$req = mysql_query("SELECT * FROM `shop`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name]</b> [<a href=\"adm_panel.php?mod=3&amp;id=$mag[id]\">удалить</a>] [<a href=\"adm_panel.php?mod=14&amp;id=$mag[id]\">изм</a>]<br/>";
}
}else{
echo"<b>Нет вещей</b><br/>";
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
case '3':
if(empty($_GET['id'])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `shop` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}

mysql_query("DELETE FROM `shop` WHERE `id`='$_GET[id]'");
echo'Вещь удалена!<br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;



/////////////////////////////////////                     СОЗДАНИЕ
///////////////////////////                               МОНСТРОВ
/////////////////////
////////////
//////////
case '5':
if(empty($_POST['name'])){
echo '<form action="adm_panel.php?mod=5" method="post">';
/*
echo "Город:<br/>
<select name=\"city\">
<option value=\"schuttgart\">Schuttgart</option>
<option value=\"dion\">Dion</option>
<option value=\"fornost\">Форност</option>
</select><br/><hr/>";
*/
echo "Тип:<br/>
<select name=\"tip\">
<option value=\"life\">Обычный</option>
<option value=\"boss\">Босс</option>
</select><br/><hr/>";

echo"Имя<br/>
<input class='input' type=\"text\" size=\"10\" name=\"name\"/><br/><hr/>";

echo"Уровень<br/>
<input class='input' type=\"text\" size=\"10\" name=\"lvl\"/><br/><hr/>";

//-----
echo"Урон (Patt/Matt)<br/>
		<input class='input' type=\"text\" size=\"3\" name=\"patt\"/>";
echo"|<input class='input' type=\"text\" size=\"3\" name=\"matt\"/><br/><hr/>";
//-----

echo"Жизни:<br/>
		";
echo"<input class='input' type=\"text\" size=\"10\" name=\"hp\"/><br/><hr/>";

//-----
echo"Защита (Pdef/Mdef)<br/>
		<input class='input' type=\"text\" size=\"3\" name=\"pdef\"/>";
echo"|<input class='input' type=\"text\" size=\"3\" name=\"mdef\"/><br/><hr/>";
//-----

echo"Шанс выпадания дропа (1-100%)<br/>
<input class='input' type=\"text\" size=\"10\" name=\"shans\"/><br/>";

//------//-------------

echo"Дроп (пример: 1/4/6)<br/>";

echo "<a href=\"adm_panel.php?mod=17\">Список</a><br/>";


echo "</b><input class='input' type=\"text\" size=\"20\" name=\"drop\"/><hr/>";


//------//-------------
echo"Шанс выпадания монет в % от 1 до 100 
<br/>Исклучение: (0=100%)<br/>
<input class='input' type=\"text\" size=\"10\" name=\"msh\"/><br/><hr/>";

echo"Кол. монет:<br/>
<input class='input' type=\"text\" size=\"10\" name=\"money\"/><br/><hr/>";

echo"Опыта:<br/>
<input class='input' type=\"text\" size=\"10\" name=\"exp\"/><br/><hr/>";

echo"Квэст:(нету-оставить пустым)<br/>
<input class='input' type=\"text\" size=\"10\" name=\"quest\"/><br/><hr/>";


echo"Время смерти (cек):<br/>
<input class='input' type=\"text\" size=\"10\" name=\"time\"/><br/><hr/>";

echo"Окра: <br/>
<input class='input' type=\"text\" size=\"10\" name=\"okra\"/>";


echo '<input class="button" type="submit" value="Создать" /></form>';
}else{

mysql_query("INSERT INTO
        `mobs` SET
        `name` = '$_POST[name]',
        `lvl` = '$_POST[lvl]',
        `money` = '$_POST[money]',
        `msh` = '$_POST[msh]',
        `exp` = '$_POST[exp]',
		`hp` = '$_POST[hp]',
        `hpall` = '$_POST[hp]',
        `patt` = '$_POST[patt]',
        `matt` = '$_POST[matt]',
        `pdef` = '$_POST[pdef]',
        `mdef` = '$_POST[mdef]',
 		`okra` = '$_POST[okra]',
        `status` = 'on',
        `time` = '$_POST[time]',
        `oponent` = 'not',
        `drop` = '$_POST[drop]',
        `shans` = '$_POST[shans]',
     
		`tip` = '$_POST[tip]',
        `quest` = '$_POST[quest]'");

echo'Монстр '.$_POST['name'].' создана!Чтобы создать точно такого же рядом ОБНОВИТЕ СТРАНИЦУ!<br/>';

}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
/*
case '6':
if (empty($sort)){$sort="id";}
echo "<b>Сортировать:</b> ";
if ($sort=='name'){echo "Имя | ";}else{ echo"<a href=\"adm_panel.php?mod=6&sort=name\">Имени</a> | ";}
if ($sort=='x'){echo "X | ";}else{ echo"<a href=\"adm_panel.php?mod=6&sort=x\">X</a> | ";}
if ($sort=='y'){echo "Y | ";}else{ echo"<a href=\"adm_panel.php?mod=6&sort=y\">Y</a> | ";}
if ($sort=='lvl'){echo "LvL | ";}else{ echo"<a href=\"adm_panel.php?mod=6&sort=lvl\">LvL</a> | ";}
if ($sort=='id'){echo "ID";}else{ echo"<a href=\"adm_panel.php?mod=6&sort=id\">ID</a><br/><br/>";}

echo "<hr/>";
$req = mysql_query("SELECT * FROM `mobs` WHERE `id`>='0' ORDER BY `$sort` ASC");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<font color=orange>$mag[id])</font> <b><font color=green>$mag[name]</font></b> <font color=grey>($mag[x];$mag[y])</font>-<b>$mag[lvl] lvl</b>  | <a href=\"adm_panel.php?mod=13&amp;id=$mag[id]\">изм</a> | <a href=\"adm_panel.php?mod=7&amp;id=$mag[id]\"><font color=red><b>x</b></font></a><hr/>";
}
}else{
echo"<b>Нет монстров</b><br/>";
}
echo"<br/><a href=\"adm_panel.php?\">Назад</a>";
break;
case '7':
if(empty($_GET[id])){
echo"Ошибка невыбран монстр!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `mob` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}

mysql_query("DELETE FROM `mob` WHERE `id`='$_GET[id]'");
echo'Монстр удалён!<br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
*/

/*
case '8':

echo "<hr/>";

if ($_GET[city]=="fotnost"){echo " Форност |";}else{echo"<a href=\"adm_panel.php?mod=8&city=fornost\"> Форност</a> | ";}
if ($_GET[city]=="dion"){echo " dion |";}else{echo"<a href=\"adm_panel.php?mod=8&city=dion\"> dion</a> | ";}
if ($_GET[city]=="schuttgart"){echo " schuttgart |";}else{echo"<a href=\"adm_panel.php?mod=8&city=schuttgart\"> schuttgart</a> | ";}

echo "<hr/>";

$req = mysql_query("SELECT * FROM `world` WHERE `city`='$_GET[city]'");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name](X:$mag[x];Y:$mag[y])</b> [<a href=\"adm_panel.php?mod=9&amp;id=$mag[id]\">удалить</a>]<br/>";
}
}else{
echo"<b>Нет локаций</b><br/>";
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
case '9':
if(empty($_GET[id])){
echo"Ошибка невыбрана локация!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `world` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}

mysql_query("DELETE FROM `world` WHERE `id`='$_GET[id]'");
echo'Локация удалена!<br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
*/

case '10':
if(empty($_POST['nick'])){
echo '<form action="adm_panel.php?mod=10" method="post">';

echo "Наложить:<br/>
<select name=\"tip\">
<option value=\"ban\">Бан</option>
<option value=\"block\">Заблокировать</option>

</select><br/>";

echo"Логин:<br/>
<input class='input' type=\"text\" size=\"10\" value=\"$_GET[usr]\" name=\"nick\"/><br/>";

echo"Причина бана:<br/>
<input class='input' type=\"text\" size=\"10\" name=\"text\"/><br/>";

echo"Время бана:<br/>
<input class='input' type=\"text\" size=\"10\" name=\"time\"/><br/>";

echo "Тип времени:<br/>
<select name=\"times\">
<option value=\"sec\">Секунд</option>
<option value=\"min\">Минут</option>
<option value=\"cha\">Часов</option>
</select><br/>";
echo"Цена розблока:<br/>
<input class='input' type=\"text\" size=\"10\" name=\"cena\"/><br/>";

echo '<input class="button" type="submit" value="Забанить" /></form>';
}else{
$req = mysql_query("SELECT * FROM `users` where `usr`='$_POST[nick]'");
$avto=mysql_num_rows($req);
	$usdata_bl = mysql_fetch_array($req);

if($udata['dostup'] < $usdata_bl['dostup']){echo"У Вас права меньше... Так что не выйдет...=)!";include($path.'inc/down.php');exit;}

if($avto==0){echo"Нет такого игрока!";include($path.'inc/down.php');exit;}

$req = mysql_query("SELECT * FROM `$_POST[tip]` where `usr`='$_POST[nick]'");
$avto=mysql_num_rows($req);
if($avto==1){echo"Игрок уже в бане(блоке)!";include($path.'inc/down.php');exit;}

if($_POST['times']=='sec'){$_POST['time']=$_POST['time'];}
if($_POST['times']=='min'){$_POST['time']=$_POST['time']*60;}
if($_POST['times']=='cha'){$_POST['time']=$_POST['time']*3600;}

$time_ban=time()+$_POST['time'];

mysql_query("INSERT INTO
        `$_POST[tip]` SET
        `usr` = '$_POST[nick]',
        `log` = '$log',
        `text` = '$_POST[text]',
        `ban_time` = '$time_ban' ");

echo'Игрок '.$_POST['nick'].' забанен(-блочен)/заблокирован айпи!<br/>';
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
/////////////////////////////////
case 'block':

if ($_GET['delbl']==1){
if(empty($_GET['id'])){
echo"<p>Ошибка невыбран игрок!</p>";
}else{

$req = mysql_query("SELECT * FROM `block` where `id`='$_GET[id]' ");
$avto=mysql_num_rows($req);
if($avto==0){echo"<p><font color=red>Ошибка!</font></p>";}else{

mysql_query("DELETE FROM `block` WHERE `id`='$_GET[id]'");
echo'<p><font color=green>Блок снят!</p></font>';
//echo"<a href=\"adm_panel.php?\">Назад</a>";
}}}
$time=time();
$req = mysql_query("SELECT * FROM `block` WHERE `ban_time`>'$time' ORDER BY id DESC");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
echo "<p><font color=grey>Списки заблокированых</font></p><hr/>";
While($mag = mysql_fetch_array($req))
{
echo"&#160   <font color=grey>ID: $mag[id] - </font>  <a href=\"search.php?nick=$mag[usr]&amp;go=go\"><b>$mag[usr]</b></a> &#160  &#160 <a href=\"adm_panel.php?mod=block&amp;id=$mag[id]&amp;delbl=1\"><font color=red>[x]</font></a><hr/>";
}
}else{
echo"<b>Нет заблоченых!</b><br/>";
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;

/////////////////////////////////

case '11':
$time=time();
$req = mysql_query("SELECT * FROM `ban` WHERE `ban_time`>'$time' ORDER BY id DESC");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
echo "<p><font color=grey>Списки забаненных</font></p><hr/>";

While($mag = mysql_fetch_array($req))
{
echo"&#160   <font color=grey>ID: $mag[id] - </font>  <a href=\"search.php?nick=$mag[usr]&amp;go=go\"><b>$mag[usr]</b></a> &#160  &#160 <a href=\"adm_panel.php?mod=12&amp;id=$mag[id]\"><font color=orange>[x]</font></a><hr/>";
}
}else{
echo"<b>Нет забаненых!</b><br/>";
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;

case '12':
if(empty($_GET['id'])){
echo"Ошибка невыбран игрок!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `ban` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}

mysql_query("DELETE FROM `ban` WHERE `id`='$_GET[id]'");
echo'Бан снят!<br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;

case '777':
if(empty($_GET['id'])){
echo"Ошибка невыбран игрок!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `block_ip` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}

mysql_query("DELETE FROM `block_ip` WHERE `id`='$_GET[id]'");
echo'Бан снят!<br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;

case '13':
$time=time();
$req = mysql_query("SELECT * FROM `block_ip` WHERE `ban_time`>'$time' ORDER BY id DESC");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
echo "<p><font color=grey>Списки забаненных по IP</font></p><hr/>";

While($mag = mysql_fetch_array($req))
{
echo"&#160   <font color=grey>ID: $mag[id] - </font>  <a href=\"search.php?nick=$mag[usr]&amp;go=go\"><b>$mag[usr]</b></a> IP:$mag[ip] Причина :$mag[text] &#160  &#160 <a href=\"adm_panel.php?mod=777&amp;id=$mag[id]\"><font color=orange>[x]</font></a><hr/>";
}
}else{
echo"<b>Нет забаненых!</b><br/>";
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;


/*
case '13':
if(empty($_GET[id])){
echo"Ошибка невыбран монстр!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `mob` WHERE `id`='$_GET[id]' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такого монстра не существует.";include($path.'inc/down.php');exit;}
$mob = mysql_fetch_array($req);


if(empty($_POST[name])){
echo '<form action="adm_panel.php?mod=13&amp;id='.$_GET[id].'" method="post">';

//--------------------
if ($mob[tip]=="boss"){echo "Тип:<br/>
<select name=\"tip\">
<option value=\"boss\">Босс</option>
<option value=\"life\">Обычный</option>
</select><br/><hr/>";}else{

echo "Тип:<br/>
<select name=\"tip\">
<option value=\"life\">Обычный</option>
<option value=\"boss\">Босс</option>
</select><br/><hr/>";}
//--------------------

echo"Имя<br/>
<input class='input' type=\"text\" value=\"$mob[name]\" size=\"10\" name=\"name\"/><hr/>";

echo"Уровень<br/>
<input class='input' type=\"text\" value=\"$mob[lvl]\" size=\"10\" name=\"lvl\"/><hr/>";

echo"Урон(min/max)<br/>
<input class='input' type=\"text\" value=\"$mob[umin]\" size=\"3\" name=\"umin\"/>|";
echo"<input class='input' type=\"text\" value=\"$mob[umax]\" size=\"3\" name=\"umax\"/><hr/>";

echo"HP (min/max)<br/>
<input class='input' type=\"text\" value=\"$mob[hp]\" size=\"3\" name=\"hp\"/>|";
echo"<input class='input' type=\"text\" value=\"$mob[hpall]\" size=\"3\" name=\"hpall\"/><hr/>";

echo"Защ головы<br/>
<input class='input' type=\"text\" value=\"$mob[pgolova]\" size=\"10\" name=\"pgolova\"/><hr/>";
echo"Защ тела<br/>
<input class='input' type=\"text\" value=\"$mob[pbody]\" size=\"10\" name=\"pbody\"/><hr/>";
echo"Защита ног<br/>
<input class='input' type=\"text\" value=\"$mob[pnogi]\" size=\"10\" name=\"pnogi\"/><hr/>";
echo"Защ от умений<br/>
<input class='input' type=\"text\" value=\"$mob[pmag]\" size=\"10\" name=\"pmag\"/><hr/>";
echo"Шанс крит(%)<br/>
<input class='input' type=\"text\" value=\"$mob[krit]\" size=\"10\" name=\"krit\"/><hr/>";
echo"Урон-крит(%)<br/>
<input class='input' type=\"text\" value=\"$mob[ukrit]\" size=\"10\" name=\"ukrit\"/><hr/>";
echo"Анти-крит(%)<br/>
<input class='input' type=\"text\" value=\"$mob[antikrit]\" size=\"10\" name=\"antikrit\"/><hr/>";

echo"Ловкость<br/>
<input class='input' type=\"text\" value=\"$mob[lovk]\" size=\"10\" name=\"lovk\"/><hr/>";

echo"Шанс выпадания дропа (1-100%)<br/>
<input class='input' type=\"text\" value=\"$mob[sd]\" size=\"10\" name=\"sd\"/><br/>";
echo"Дроп<br/>";
echo "<a href=\"adm_panel.php?mod=17\">Список</a><br/>";

echo "<input class='input' type=\"text\" value=\"$mob[drop]\" size=\"10\" name=\"drop\"/><hr/>";

echo"Шанс выпадания монет в % от 1 до 100 
<br/>Исклучение: (0=100%)<br/>
<input class='input' type=\"text\" value=\"$mob[sm]\" size=\"10\" name=\"sm\"/><hr/>";

echo"Кол. монет:<br/>
<input class='input' type=\"text\" value=\"$mob[money]\" size=\"10\" name=\"money\"/><hr/>";

echo"Опыта +:<br/>
<input class='input' type=\"text\" value=\"$mob[exp]\" size=\"10\" name=\"exp\"/><hr/>";

echo"Время смерти (сек):<br/>
<input class='input' type=\"text\" value=\"$mob[time]\" size=\"10\" name=\"time\"/><hr/>";

echo"Квэст:(нету-ост.пуст.)<br/>
<input class='input' type=\"text\" value=\"$mob[quest]\" size=\"10\" name=\"quest\"/><hr/>";


echo"Коорд. <b><font color=red>X</font>;<font color=blue>Y</font></b>:<br/>
<input class='input' type=\"text\" value=\"$mob[x]\" size=\"3\" name=\"x\"/>|";

echo"<input class='input' type=\"text\" value=\"$mob[y]\" size=\"3\" name=\"y\"/><hr/>";

echo '<input class="button" type="submit" value="Изменить" /></form>';
}else{



mysql_query("UPDATE `mob` SET
        `name` = '$_POST[name]',
        `lvl` = '$_POST[lvl]',
        `hp` = '$_POST[hp]',
        `hpall` = '$_POST[hpall]',
        `umin` = '$_POST[umin]',
        `umax` = '$_POST[umax]',
        `pgolova` = '$_POST[pgolova]',
        `pbody` = '$_POST[pbody]',
        `pnogi` = '$_POST[pnogi]',
        `pmag` = '$_POST[pmag]',
        `krit` = '$_POST[krit]',
        `ukrit` = '$_POST[ukrit]',
        `antikrit` = '$_POST[antikrit]',
        `lovk` = '$_POST[lovk]',
        `sd` = '$_POST[sd]',
        `drop` = '$_POST[drop]',
        `sm` = '$_POST[sm]',
        `money` = '$_POST[money]',
        `exp` = '$_POST[exp]',
        `time` = '$_POST[time]',
        `quest` = '$_POST[quest]',
       `x` = '$_POST[x]',
        `y` = '$_POST[y]' WHERE `id`='$_GET[id]' LIMIT 1");

echo'Монстр '.$_POST[name].' изменён!<br/>';
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
*/
case '14':
if(empty($_GET['id'])){
echo"Ошибка невыбрана вещь!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `shop` WHERE `id`='$_GET[id]' LIMIT 1");
////////////////////////////
if (mysql_num_rows($req)==0){echo"Такой вещи не существует.";include($path.'inc/down.php');exit;}
$item = mysql_fetch_array($req);

if(empty($_POST['name'])){
echo '<form action="adm_panel.php?mod=14&amp;id='.$_GET['id'].'" method="post">';

echo"Имя<br/>
<input class='input' type=\"text\" value=\"$item[name]\" size=\"10\" name=\"name\"/><br/>";

echo"Цена<br/>
<input class='input' type=\"text\" value=\"$item[cena]\" size=\"10\" name=\"cena\"/><br/>";

echo"Город<br/>
<input class='input' type=\"text\" value=\"$item[city]\" size=\"10\" name=\"cena\"/><br/>";

echo"P.Att<br/>
<input class='input' type=\"text\" value=\"$item[patt]\" size=\"10\" name=\"umin\"/><br/>";
echo"M.Att<br/>
<input class='input' type=\"text\" value=\"$item[matt]\" size=\"10\" name=\"umax\"/><br/>";

echo"P.Def<br/>
<input class='input' type=\"text\" value=\"$item[pdef]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"M.Def<br/>
<input class='input' type=\"text\" value=\"$item[mdef]\" size=\"10\" name=\"pbody\"/><br/>";

echo"SoulShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[soul]\" size=\"10\" name=\"pgolova\"/><br/>";
echo"SpiritShot (кол-во)<br/>
<input class='input' type=\"text\" value=\"$item[spirit]\" size=\"10\" name=\"pbody\"/><br/>";



echo"Требуемый уровень<br/>
<input class='input' type=\"text\" value=\"$item[nlvl]\" size=\"10\" name=\"nlvl\"/><br/>";

echo '<input class="button" type="submit" value="Изменить" /></form>';
}else{

mysql_query("UPDATE `shop` SET
        `name` = '$_POST[name]',
        `cena` = '$_POST[cena]',
        `city` = '$_POST[city]',
        `pdef` = '$_POST[pdef]',
        `mdef` = '$_POST[mdef]',
        `patt` = '$_POST[patt]',
        `matt` = '$_POST[matt]',
        `soul` = '$_POST[soul]',
        `spirit` = '$_POST[spirit]',
        `nlvl` = '$_POST[nlvl]' WHERE id='$_GET[id]' LIMIT 1");

echo'Вещь '.$_POST['name'].' изменена!<br/>';

}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
case '15':
if(empty($_POST['name'])){
echo '<form action="adm_panel.php?mod=15" method="post">';

echo"Название:<br/>
<input class='input' type=\"text\" size=\"20\" name=\"name\"/><br/>";

echo'Текст:<br/>
<textarea name="text" cols="30" rows="10"></textarea><br/>';

echo '<input class="button" type="submit" value="Добавить" /></form>';
}else{
mysql_query("INSERT INTO `library` SET
        `title` = '$_POST[name]',
        `text` = '$_POST[text]',
        `author` = '$log',`time`='".date("d.m.y")."'");

echo'Статья '.$_POST['name'].' написана!<br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
}
break;


//----------------INOY--------NEWS--------------
case 'new15':

echo 'Новости!<br><br>';
if(empty($_POST['name'])){
echo '<form action="adm_panel.php?mod=new15" method="post">';

echo"Название:<br/>
<input class='input' type=\"text\" size=\"20\" name=\"name\"/><br/>";

echo'Текст:<br/>
<textarea name="text" cols="30" rows="10"></textarea><br/>';

echo '<input class="button" type="submit" value="Добавить" /></form>';
}else{
mysql_query("INSERT INTO `news` SET
        `title` = '$_POST[name]',
        `text` = '$_POST[text]',
        `nick` = '$log',`time`='".date("d.m.y")."'");

$text = $_POST['text'];
$title = $_POST['name'];
$gameId = 1192;
$secKey = "b31a7a15d8ccdcdb78f478e109dee0cfb29cc1f9";
$sign = $gameId.$text.$title.$secKey;
$sign = sha1($sign);
$url = "http://vigre.su/xml/index.php?game_id=1192&command=news";
$url .= "&data=".urlencode($text);
$url .= "&title=".urlencode($title);
$url .= "&sign=".urlencode($sign);
$response = file_get_contents($url);
echo'Статья '.$_POST['name'].' написана!<br/>';
echo'Заголовок: <b>'.$_POST['name'].'!</b><br/>';
echo'Текст: <b>'.$_POST['text'].'!</b><br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
}
break;

case 'teh_news':
echo 'Тех новости!<br><br>';
if(empty($_POST['name'])){
echo '<form action="adm_panel.php?mod=teh_news" method="post">';
echo"Название:<br/>
<input class='input' type=\"text\" size=\"20\" name=\"name\"/><br/>";
echo'Текст:<br/>
<textarea name="text" cols="30" rows="10"></textarea><br/>';
echo '<input class="button" type="submit" value="Добавить" /></form>';
}else{
mysql_query("INSERT INTO `teh_news` SET
        `title` = '$_POST[name]',
        `text` = '$_POST[text]',
        `nick` = '$log',`time`='".date("d.m.y")."'");
echo'Заголовок: <b>'.$_POST['name'].'!</b><br/>';
echo'Текст: <b>'.$_POST['text'].'!</b><br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
}
break;








//----------------------------------------------
/*
case '16':
$req = mysql_query("SELECT * FROM `library`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[title]</b> [<a href=\"adm_panel.php?mod=17&amp;id=$mag[id]\">удалить</a>]<br/>";
}
}else{
echo"<b>Нет статей</b><br/>";
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;

case '7':
if(empty($_GET[id])){
echo"Ошибка невыбрана статья!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `library` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}

mysql_query("DELETE FROM `library` WHERE `id`='$_GET[id]'");
echo'Статья удалена!<br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;

case '18':
if(empty($_POST[name])){
echo '<form action="adm_panel.php?mod=18" method="post">';

echo "Город:<br/>
<select name=\"city\">
<option value=\"fornost\">Форност</option>
</select><br/>";

echo"Имя<br/>
<input class='input' type=\"text\" size=\"10\" name=\"name\"/><br/>";

echo'Латиницей (пишем латинецей, пробелы заменять "_"! Примеры: Аура шока - aura_shoka !Нельзя чтобы латинские именна повторялись!)<br/>';
echo"<input class='input' type=\"text\" size=\"10\" name=\"lat_name\"/><br/>";

echo"Цена<br/>
<input class='input' type=\"text\" size=\"10\" name=\"cena\"/><br/>";

echo"Минимальный урон<br/>
<input class='input' type=\"text\" size=\"10\" name=\"umin\"/><br/>";
echo"Максимальный урон<br/>
<input class='input' type=\"text\" size=\"10\" name=\"umax\"/><br/>";

echo"Защита<br/>
<input class='input' type=\"text\" size=\"10\" name=\"protect\"/><br/>";

echo"Бонус здоровья<br/>
<input class='input' type=\"text\" size=\"10\" name=\"hp\"/><br/>";
echo"Бонус маны<br/>
<input class='input' type=\"text\" size=\"10\" name=\"mp\"/><br/>";
echo"Бонус крит<br/>
<input class='input' type=\"text\" size=\"10\" name=\"krit\"/><br/>";
echo"Бонус урон-крит<br/>
<input class='input' type=\"text\" size=\"10\" name=\"ukrit\"/><br/>";

echo "Класс:<br/>
<select name=\"klas\">
<option value=\"not\">Все</option>
<option value=\"wizard\">Маг</option>
<option value=\"fighert\">Воин</option>
</select><br/>";

echo"Требуется силы<br/>
<input class='input' type=\"text\" size=\"10\" name=\"nsila\"/><br/>";

echo"Требуется ловкости<br/>
<input class='input' type=\"text\" size=\"10\" name=\"nlovk\"/><br/>";

echo"Требуется интелекта<br/>
<input class='input' type=\"text\" size=\"10\" name=\"num\"/><br/>";

echo"Требуемый уровень<br/>
<input class='input' type=\"text\" size=\"10\" name=\"nlvl\"/><br/>";

echo"Время действия(в секундах)<br/>
<input class='input' type=\"text\" size=\"10\" name=\"actime\"/><br/>";

echo '<input class="button" type="submit" value="Создать" /></form>';
}else{

mysql_query("INSERT INTO
        `shop_aura` SET
        `city` = '$_POST[city]',
        `name` = '$_POST[name]',
        `lat_name` = '$_POST[lat_name]',
        `cena` = '$_POST[cena]',
        `umin` = '$_POST[umin]',
        `umax` = '$_POST[umax]',
        `protect` = '$_POST[protect]',
        `hp` = '$_POST[hp]',
        `mp` = '$_POST[mp]',
        `krit` = '$_POST[krit]',
        `ukrit` = '$_POST[ukrit]',
        `klas` = '$_POST[klas]',
        `nsila` = '$_POST[nsila]',
        `nlovk` = '$_POST[nlovk]',
        `num` = '$_POST[num]',
        `actime` = '$_POST[actime]',
        `nlvl` = '$_POST[nlvl]'");

echo'Аура '.$_POST[name].' создана!<br/>';
}
echo"<a href=\"adm_panel.php?\">Назад</a>";

break;
case '19':
$req = mysql_query("SELECT * FROM `shop_aura`");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{
echo"<b>$mag[name]</b> [<a href=\"adm_panel.php?mod=20&amp;id=$mag[id]\">удалить</a>]<br/>";
}
}else{
echo"<b>Нет аур</b><br/>";
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
case '20':
if(empty($_GET[id])){
echo"Ошибка невыбрана аура!";
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `shop_aura` where `id`='$_GET[id]'");
$avto=mysql_num_rows($req);
if($avto==0){echo"Ошибка!";include($path.'inc/down.php');exit;}

mysql_query("DELETE FROM `shop_aura` WHERE `id`='$_GET[id]' LIMIT 1");
echo'Аура удалена!<br/>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;
*/


case 'accblock':
if ($_GET['delbl']==1){
if(empty($_GET['id'])){
echo"<p>Ошибка невыбран игрок!</p>";
}else{
$req = mysql_query("SELECT * FROM `block_account` where `id`='".$_GET['id']."' ");
$avto= mysql_num_rows($req);
if($avto==0){echo"<p><font color=red>Ошибка!</font></p>";}else{
mysql_query("DELETE FROM `block_account` WHERE `id`='".$_GET['id']."'");
echo'<p><font color=green>Блок аккаунта снят!</p></font>';
echo"<a href=\"adm_panel.php?\">Назад</a>";
}}}
$time=time();
$req = mysql_query("SELECT * FROM `block_account` ORDER BY id DESC");
////////////////////////////
$avto= mysql_num_rows($req);
if($avto>=1){ 
echo "<p><font color=grey>Списки заблокированых</font></p><hr/>";
While($mag = mysql_fetch_assoc($req))
{
$acc= mysql_query("SELECT * FROM `account` WHERE `id` = '".$mag['acc_id']."' ");
$uacc= mysql_fetch_assoc($acc);
echo"&#160   <font color=grey>ID: $mag[id] - </font>  <a href=\"players.php?a=account&id=$mag[acc_id]\"><b>$uacc[nick]</b></a> &#160  &#160 <a href=\"adm_panel.php?mod=accblock&amp;id=$mag[id]&amp;delbl=1\"><font color=red>[x]</font></a><hr/>";
}
}else{
echo"<b>Нет заблокированных аккаунтов!</b><br/>";
}
echo"<a href=\"adm_panel.php?\">Назад</a>";
break;

case 'block_acc':
$id_acc = abs(intval($_GET['id']));
if(!$id_acc){
    echo 'Не найден ИД аккаунта!';
    break;
}
$account_ex = mysql_query("SELECT * FROM `account` WHERE `id` = '$id_acc'");

if(!mysql_num_rows($account_ex)){
    echo 'Такого Аккаунта не существует!';
    break;
}
$data_acc = mysql_fetch_assoc($account_ex);
if(isset($_POST['submit'])){
    $reason = $_POST['text'];
    mysql_query("UPDATE `account` SET `active` = '0' WHERE `id` = '$id_acc'");
    //Что бы кинуло в кабинет (ну а там уже сделать проверку на бан, для того что бы не писать лишний раз в шапку всякий быдлокод)
mysql_query("INSERT INTO `block_account` (`id`, `acc_id`, `log`, `time`, `text`) VALUES (NULL,'$id_acc', '$log', '".time()."', '".mysql_real_escape_string($reason)."');");

echo '<div class="ok">Аккаунт успешно заблокирован!!!</div>';
}else{
echo '<div class="title">Заблокировать аккаунт '.$data_acc['nick'].'</div>';
echo '<form action="adm_panel.php?mod=block_acc&amp;id='.$id_acc.'" method="POST">';
echo '<input type="text" name="text" value="Введите причину" />';
echo '<input type="submit" name="submit" value="Заблокировать" />';
echo '</form>';
}
break;
}


include($path.'inc/down.php');
?>