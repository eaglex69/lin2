<?php

/**
 * @author FROSTY
 * @copyright 2013
 */

define('PROTECTOR', 1);
$textl='Мой аккаунт';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
/*include($path.'inc/core.php');*/
include($path.'inc/head.php');

if(!$uid){
echo '<div class="error">Доступ не авторизованным ЗАПРЕЩЕН</div>';
include($path.'inc/end.php');
exit;
}

$block1 = mysql_result(mysql_query("SELECT COUNT(*) FROM `block_account` WHERE `acc_id` = '".$uid."'"),0);
if($block1){
$block_data =mysql_fetch_assoc(mysql_query("SELECT * FROM `block_account` WHERE `acc_id` = '".$uid."' LIMIT 1"));
echo '<div class="error">ВАШ АККАУНТ ЗАБЛОКИРОВАН!<br />Админ: '.$block_data['log'].'<br />Причина: '.$block_data['text'].'<br /></div>';
include($path.'inc/end.php');
exit;
}

$case=htmlspecialchars(trim($_GET['a']));

switch($case){

default:
$my_pers = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `account` = '$uid'"),0);


echo '<div class="title">Аккаунт <font color=#f4d06e>'.$account['nick'].' </font><br/>';

if($_GET['error']){
    echo '<div class="error" style="color: #6fcd72;">Для входа в игру выберите / создайте персонажа!</div>';
}
/*
echo'<div class="dot">
<center>ВНИМАНИЕ!!! ВНИМАНИЕ!!! ВНИМАНИЕ!!!<br/>
ВСЕ ПЕРСОНАЖИ ЦЕЛЫ!!!<br/>
Мы перешли на новую систему аккаунтов!<br/>
Что сделать, чтобы попасть на персонажа:<br/>
<font color=red>1.</font>Регистрируете новый аккаунт.<br/>
<font color=red>2.</font>Заходите офис-привязать персонажа.<br/>
<font color=red>3.</font>Вводите там старые данные.<br/>
<font color=red>4.</font>Все, вы можете пользоваться старыми персами.<br/>
НИ В КОЕМ СЛУЧАЕ НЕ ПАНИКУЙТЕ, ЕСЛИ ЧТО ТО НЕ ЯСНО - СОЗДАВАЙТЕ МУЛЬТА И В ЦП</center></div>';*/
echo "<br><div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo '<a href="office.php?a=my_pers">Войти в игру</a></div>';
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo '<a href="office.php?a=password">Изм пароль</a> ';
if(!$account[email])echo ' <a href="office.php?a=email">Уст e-mail</a>';
if(!$account[sekret])echo ' <a href="office.php?a=sekret">Секретное слово</a>';
echo'</div>';
echo "<div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo '<a href="office.php?a=my_pers">Персонажи '.$my_pers.' - 7</b>';
if($my_pers<7){echo' <a href="office.php?a=new">Создать</a>';}
echo'</div>';

echo '<div class="slot_menu"><a href="office.php?a=exit"><font color=#ffffcc><b>Выход</b></font></a></div>';
break;


case'email':
$user2 = mysql_fetch_assoc(mysql_query("SELECT * FROM `account` WHERE `id` = '".$uid."' LIMIT 1"));

echo "<div class=menu><b><p>Установка e-mail:</p></b><hr/>";
echo '<form action="office.php?a=saveemail" method="post">';
echo "Введите e-mail: <br/>
<input type=\"text\" name=\"email\" value=\"\" size=\"20\" maxlength=\"36\"/> <br/>";
echo '<input class="button" type="submit" value="Сохранить" /></form>';
echo "</div>";
echo"<div class=silka><a href=\"office.php?\">Назад</a></div>";
break;

case'saveemail':
$mail=$_POST['email'];
if(preg_match("|^[-0-9a-z_\.]+@[-0-9a-z_^\.]+\.[a-z]{2,6}$|i", $mail)){
mysql_query(" UPDATE `account` SET `email` = '".mysql_real_escape_string($mail)."' WHERE `id` = '".$uid."' ");
echo'e-mail '.$mail.' успешно прикреплен к вашему аккаунту!<br/>';
echo'<a href="office.php">Назад</a>';
}else{echo "<br/>Не правильно введён e-mail!</div><br/>";}
break;



case'sekret':
$user2 = mysql_fetch_assoc(mysql_query("SELECT * FROM `account` WHERE `id` = '".$uid."' LIMIT 1"));

echo "<div class=menu><b><p>Установка секретного слова:</p></b><hr/>";
echo '<form action="office.php?a=savesekret" method="post">';
echo "Введите секретное слово: <br/>
<input type=\"text\" name=\"sekret\" value=\"\" size=\"20\" maxlength=\"36\"/> <br/>";
echo '<input class="button" type="submit" value="Сохранить" /></form>';
echo "</div>";
echo"<div class=silka><a href=\"office.php?\">Назад</a></div>";
break;

case'savesekret':
$sekret=md5(md5($_POST['sekret']));
mysql_query(" UPDATE `account` SET `sekret` = '".mysql_real_escape_string($sekret)."' WHERE `id` = '".$uid."' ");
echo'секретное слово '.$sekret.' успешно прикреплен к вашему аккаунту!<br/>';
echo'<a href="office.php">Назад</a>';
break;

case'savepass';
$psstar = md5(htmlspecialchars(stripslashes(addslashes($_POST['psstar']))));
$req = mysql_query("SELECT * FROM `account` WHERE `id` = '".$uid."' and `pass`='".$psstar."' LIMIT 1");
$avto= mysql_num_rows($req);
if ($avto==0)
		{	
		echo "<font color=red><p> Ошибка ввода пароля! </p></font>";  // неудачно =)
		}else{
$psnew = md5(htmlspecialchars(stripslashes(addslashes($_POST['psnew']))));
$psnew2 = md5(htmlspecialchars(stripslashes(addslashes($_POST['psnew2']))));
if ($psnew != $psnew2)
		{	
		echo "<font color=red><p> Новые пароли не совпадают! </p></font>";  // неудачно =)
		}else{		
if (empty($psnew))
		{	
		echo "<font color=red><p> Повторите ввод! </p></font>";  // неудачно =)
		}

else{			
if (strlen($_POST['psnew'])>15) { echo"<font color=red>Максимум 15!</font>";}else{
$ressave = mysql_query ("UPDATE account SET
 pass='".$psnew."'
		 WHERE `id` = '".$uid."' LIMIT 1");}
		 
				if ($ressave == 'true')
					{
					echo '<font color=#007F46><p>Настройки сохранены!</p></font>'; // удачно
echo '<a href="/index.php"><p>Авторизоватся под новыми данными!</p></a></font>';
					}
					else
					{
					echo "<font color=red><p> Неудача ! </p></font>";  // неудачно =)
					}
}}}
break;




case 'new':
$my_pers = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `account` = '".$uid."'"),0);
if($my_pers>6){echo'Регистрировать более 7 персонажей ЗАПРЕЩЕНО';break;}
echo '<div class="title">Создать персонажа</div>';

echo '<div class="account_reg">';
echo '<form action="office.php?a=add" method="post">';

echo '<br />';
echo 'Имя персонажа: <br /> <small>min 4, max 10, A-z-А-я-0-9</small> <br />
<input class="input" type="text" size="10" name="name" maxlength="15" />';
echo '<br />';

echo "Укажите ваш пол:<br/>
<select name=\"pol\"><option value=\"m\">Парень</option>
<option value=\"w\">Девушка</option></select><br/>";

echo "Раса:<br/>
<select name=\"storona\">
<option value=\"human\">Человек</option>
<option value=\"gnom\">Гном</option>
<option value=\"elf\">Светлый эльф</option>
<option value=\"darkelf\">Темный эльф</option>
<option value=\"ork\">Орк</option>
</select>";

echo "<br/>";

echo "Выберите класс:<br/>
<select  name=\"klas\">
<option value=\"wizard\">Маг</option>
<option value=\"fighert\">Воин</option></select><br/>";



echo '<input class="button" type="submit" value="Создать" /></form>';
echo "</div>";
echo '<a href="office.php"><div class="back">Кабинет ['.$account['nick'].']</div></a>';
break;

case 'add':
$my_pers = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `account` = '$uid'"),0);
if($my_pers>6){echo'Регистрировать более 7 персонажей ЗАПРЕЩЕНО';break;}
$name = $_POST['name'];
$storona = $_POST['storona'];
$class = $_POST['klas'];
$pol = $_POST['pol'];


if($storona != 'human' && $storona != 'gnom' && $storona != 'elf' && $storona != 'darkelf' && $storona != 'ork'){
    echo '<div class="error">Выберите сторону!</div>';
    break;
}

if($pol!='m' and $pol!='w'){
echo "Невыбран пол персонажа!<br/>";
echo "<a href=\"reg.php\">Назад</a></div>";
}

if(!$name){
echo '<div class="error">Выберите Ник!</div>';
break;
}elseif(mb_strlen($name) < 4 || mb_strlen($name) > 10) {
echo'Ошибка! Ник должен быть не менее 4-х символов но не более 10';
break;
}



    

if($class != 'wizard' && $class != 'fighert'){
    echo '<div class="error">Выберите класс персонажа!</div>';
    break;
}
if($storona=='gnom' and $class=='wizard'){
echo "Гномы не могут быть магами!<br/>";
    break;
}


$tkr = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `usr` = '".mysql_real_escape_string($name)."'"),0);
if($tkr){
 echo '<div class="error">Такое имя уже существует! Назовите своего персонажа по-другому!</div>';   
    break;
}




if($storona=="human"){$city="Talking Island Village"; $all=50;}
if($storona=="gnom"){$city="Dwarven Village"; $all=100;}
if($storona=="elf"){$city="Elven Village";  $all=60;}
if($storona=="darkelf"){$city="Dark Elven Village"; $all=75;}
if($storona=="ork"){$city="Orc Village";  $all=100;}
if($storona=="kamael"){$city="Kamael Village";  $all=100;}


if($class=="wizard"){
$mp = 100 + $all; $hp = 100 + $all;
$patt = 23; 
$matt = 56;
$pdef = 63;
$mdef = 52;
}


if($class=="fighert"){
$mp=80+$all; $hp=120+$all;
$patt = 55;
$matt = 21;
$pdef = 68;
$mdef = 49;
}

if(!preg_match('~[^a-z0-9]+~iu', $name)) {
    mysql_query("INSERT INTO
        `users` SET
        `usr` = '".$name."',
        `pass` = '".$account['pass']."',
        `email` = '".$account['email']."',
        `ip` = '".$account['ip']."',
        `pol` = '".$pol."',
        `storona` = '".$storona."',
        `lvl` = '0',
        `money` = '10000000000',
        `almaz` = '0',
        `skill` = '3',
        `hp` = '".$hp."',
        `hpall` = '".$hp."',
        `mp` = '".$mp."',
        `mpall` = '".$mp."',
        `exp` = '0',
        `patt` = '".$patt."',
        `matt` = '".$matt."',
        `pdef` = '".$pdef."',
        `mdef` = '".$mdef."',
        `klas` = '".$class."',
        `city` = '".$city."',
        `lvisit` = '".time()."',
        `account` = '".$uid."'");


$times=time()+3600*24;
mysql_query("INSERT INTO
        `vip` SET
        `usr` = '$name',
        `tip` = '25',
        `time` = '$times'");



$time = date("H:i d.m.y");
$text = 'Добро пожаловать в игру Miniage!<br />
Как и что делать я вам расскажу ,для начало идем в город берем вип баф на 2к его взять можно за аденов,идем дальше в окрестностей и качаем своего уровня, фармим Coin Of Luck которые падают из любых мобов ,также Festival Aden которые вы сможете обменять на Coin Of Luck в торг площаде-NPC магазин, есть еще способов заработать Coin Of Luck выполнить задания которые вы найдете в городе ,участвовать и выиграть в эвентамы , вам для начало будет по карману вип магазин где вы можете прикупится и одется ,там все не так уж и дорого ,а дальше уже люкс ,вы уже получили вип акк х25 в подарок, его потом можно купить в торг площаде NPC магазин там сможете найти раздел VIP акк в котором можно купить вип аккаунтов они помогут вам качаться быстрее ,если будут другие вопросы всегда ответа можно получить в чате , на форуме или же в службу поддержки с ув. Иванов Александр<br />Для начала зайдите в свой инвентарь, и оденьте шмот, который вам выдан на первое время <a href="inventar.php">Инвентарь.</a>
рекомендуем ознакомиться с правилами игры</a> и изучить игровой процесс в <a href="faq.php">Библиотекe игры.</a>
Приятной игры!</a>';

mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Ultimate', `user_to` = '$name', `time` = '$time', `read` = 1, `mail_msg` = '$text'");

mysql_query("INSERT INTO
        `item` SET
        `usr` = '$name',
        `tip` = 'weapon',
        `name` = 'Club',
        `cena` = '768',
        `patt` = '160',
        `matt` = '160',
        `pdef` = '0',
        `mdef` = '0',
        `soul` = '1',
        `spirit` = '1',
        `image` = 'not'");
        
mysql_query("INSERT INTO
        `item` SET
        `usr` = '$name',
        `tip` = 'body',
        `name` = 'Cotton Robe',
        `cena` = '2345',
        `patt` = '0',
        `matt` = '0',
        `pdef` = '200',
        `mdef` = '200',
        `soul` = '0',
        `spirit` = '0',
        `image` = 'not'");

mysql_query("INSERT INTO
        `item` SET
        `usr` = '$name',
        `tip` = 'amulet',
        `name` = 'Necklace of Magic',
        `cena` = '60',
        `patt` = '0',
        `matt` = '0',
        `pdef` = '125',
        `mdef` = '180',
        `soul` = '0',
        `spirit` = '0',
        `image` = 'not'");

		
if($class=="wizard"){
mysql_query("INSERT INTO
        `mag` SET
        `usr` = '$name',
        `name` = 'Magic attack',
        `lat_name` = 'mag_strela',
        `tip` = 'atack',
        `klas` = 'wizard',
        `uron` = '3|5|8|9|10|11|12|13|15|17',   
        `mp` = '9|10|12|13|14|15|16|17|18|19',   
        `lv` = '1|1|1|3|3|3|5|5|7|7',   
        `hp` = '0|0|0|0|0|0|0|0|0|0',   
        `plushp` = '0|0|0|0|0|0|0|0|0|0',   
        `lvlmax` = '8',   
        `cena` = '5|10|20|38|54|80|112|180|260',        
        `lvl` = '1'");
}
if($class=="fighert"){
mysql_query("INSERT INTO
        `mag` SET
        `usr` = '$name',
        `name` = 'Strong blow',
        `lat_name` = 'siln_udar',
        `tip` = 'atack',
        `klas` = 'fighert',
        `uron` = '20|35|48|61|74|87|100|133|157|240',   
        `mp` = '7|9|10|11|12|13|14|15|16|17',   
        `lv` = '1|1|1|3|3|3|5|5|7|7',   
        `hp` = '0|0|0|0|0|0|0|0|0|0',   
        `plushp` = '0|0|0|0|0|0|0|0|0|0',   
        `lvlmax` = '8',   
        `cena` = '5|10|20|38|54|80|112|180|260',
        `lvl` = '1'");
}

mysql_query("INSERT INTO `mesto` SET `usr` = '$name',`place` = 'main',`page` = '0',`mesto` = 'Shadow of the Monster Tree',`city` = '0'");


mysql_query("INSERT INTO regenerator SET usr = '$name', last = '".time()."'");
if(isset($_SESSION['site'])){
$site=htmlspecialchars($_SESSION['site']);
mysql_query("INSERT INTO `set` SET `usr` = '$name',`part`='0',`site` = '$site'");
mysql_query("UPDATE `users` SET `site` = '$site' WHERE `usr` = '$name'");
}
$time = date("H:i");



$actime=time()+86400;
$data = date("y/m/d");
mysql_query("INSERT INTO
        `new_usr` SET
        `usr` = '$name',
        `data` = '$data',
        `time` = '$actime'");

$_SESSION['log'] = $name;
$_SESSION['pas'] = $account['pass'];
$_SESSION['klas'] = $class;
$_SESSION['storona'] = $storona;

/*echo'<tr>
<td class="l"></td>
<td class="centertd"><div class="menu"><b>Регистрация</b><br/>
Ты очутился в Огромном Мегаполисе. Перед тобой стоит:Повелитель Мира Miniage<br/>
<div class="slot_menu">[Стражник]</div><img src ="pic/1111.jpg">
<center>--Стой на месте! Кто ты? И почему ты явился в этот мир?</center><br/>
<div class="slot_menu">Вы</div><br/>
<img src="pic/2222.jpg">
<center> Я попал в этот Мир не случайно скоро весь мир Miniage заговорит обо мне!</center><br/>
<div class="slot_menu">[Стражник]</div><img src ="pic/1111.jpg">
<center>--Да я многое слышал о таких как ты,все кто приходит с Мира Miniage особенные, но ты должен сам узнать что в тебе скрыто.Проходи далее,мы тебя ждем!<br/> 
</div>';*/
echo'<div class="inoy">Регистрация прошла успешно! <br> <a href="office.php?a=my_pers">- Войти в игру</a></div></td>
<td class="r"></td>
</tr>';
}else{
echo '<div class="error">Ник содержит запрещенные символы!</div>';
break;
}

break;

case 'exit':
setcookie('nick', '');
setcookie('password', '');
session_destroy();
header ('Location: index.php?');exit;
break;

case 'choose':
echo '<div class="title">Выбор персонажа</div>';
$id_pers = abs(intval($_GET['id']));
$mypers = mysql_query("SELECT * FROM `users` WHERE `id` = '$id_pers' AND `account` = '$uid'");
if(mysql_num_rows($mypers)){
    mysql_query("UPDATE `account` SET `active` = '$id_pers' WHERE `id` = '$uid'");
    
    echo "<center><div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
    echo '<a href="index.php"><div class="slot_menu">Продолжить...</div></a>';
    
}else{
    echo '<div class="error">ОШИБКА! Этот персонаж либо не найден либо он принадлежит не вам!</div>';
    break;
}
echo '<a href="office.php"><div class="back">Кабинет </div></a>';
break;

case 'my_pers':
echo '<div class="title">Мои персонажи</div>';
$all_pers = mysql_query("SELECT * FROM `users` WHERE `account` = '$uid'");
if(!mysql_num_rows($all_pers)){
    echo '<div class="error">У вас нет ни одного персонажа. - <a href="office.php?a=new">Создать</a></div>'; break;
}
while($pers = mysql_fetch_assoc($all_pers)){
echo "<center><div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
  echo '<center><a href="office.php?a=choose&amp;id='.$pers['id'].'">'.$pers['usr'].'</a>Ур: '.$pers['lvl']. '</a> Клас: '.$pers['storona'].'</div>';  
};
echo '<a href="office.php"><div class="back">Кабинет ['.$account['nick'].']</div></a>';
break;







case 'plus':
$my_pers = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `account` = '$uid'"),0);
if($my_pers>6){echo'Регистрировать более 7 персонажей ЗАПРЕЩЕНО';break;}
echo '<div class="title">Привязка персонажа</div>';

if(isset($_POST['submit'])){
 //Обработчик
 $login = $_POST['usr'];
 $password = md5($_POST['pass']);
$personaj = mysql_query("SELECT * FROM `users` WHERE `usr` = '".mysql_real_escape_string($login)."' AND `pass` = '".mysql_real_escape_string($password)."' LIMIT 1");
 if(mysql_num_rows($personaj)){
    $row1 = mysql_fetch_assoc($personaj);
    
    if($row1['account'] && $row1['account'] != $uid){ //Если перс уже привязан за каким то аккаунтом то...
        echo '<div class="error">Ошибка! Этот персонаж уже закреплен за одним из аккаунтов. Если вы владелец этого персонажа то немедленно обратитесь к администратору</div>';
        echo '<a href="office.php"><div class="back">Кабинет ['.$account['nick'].']</div></a>';
        break;
    }
    //Если уже закрплен
    if($row1['account'] == $uid){
        echo '<div class="error">Ошибка! Этот персонаж уже закреплен за <strong>вашим</strong> аккаунтом</div>';
        echo '<a href="office.php"><div class="back">Кабинет ['.$account['nick'].']</div></a>';
        break;
    }
    
    //И если же все проверки прошли успешно то можем закреплять))
    mysql_query("UPDATE `users` SET `account` = '$uid' WHERE `id` = '$row1[id]'");
    echo '<div class="ok">Поздравляем! Вы успешно привязали персонажа "'.$row1['usr'].'" к своему аккаунту! 
    <br /> <a href="office.php?a=my_pers">Выбрать персонажа</a> / <a href="office.php?a=plus">Привязать еще одного</a></div>';
    
 }else{
    echo '<div class="error">Ошибка! Такого <strong>персонажа не существует</strong> или <strong>не верный пароль</strong>!</div>';
echo '<a href="office.php"><div class="back">Кабинет ['.$account['nick'].']</div></a>';
    break;
 }
 
}else{
    //Форма ввода
echo '<div class="account_reg">';
echo '<form action="office.php?a=plus" method="post">';

echo '<br />';
echo 'Имя вашего персонажа: <br /> <small>Для привязки персонажа к аккаунту вы должны знать логин и пароль от персонажа</small> <br />
<input class="input" type="text" size="10" name="usr" maxlength="15" />';
echo '<br />';
echo 'Пароль от персонажа: <br />
<input class="input" type="password" size="10" name="pass" maxlength="32" />';
echo '<br />';
echo '<input class="button" type="submit" name="submit" value="Привязать" /></form>';
echo "</div>";
}
break;

case'password':
$user2 = mysql_fetch_array(mysql_query("SELECT * FROM `account` where `nick`='$account[nick]' LIMIT 1"));

echo "<div class=menu><b><p>Изменение пароля:</p></b><hr/>";
echo '<form action="office.php?a=savepass" method="post">';
echo "
Старый : <br/>
<input type=\"text\" name=\"psstar\" value=\"\" size=\"20\" maxlength=\"11\"/> <br/>
Новый : <br/>
<input type=\"text\" name=\"psnew\" value=\"\" size=\"20\" maxlength=\"11\"/> <br/>
Повторите новый: <br/>
<input type=\"text\" name=\"psnew2\" value=\"\" size=\"20\" maxlength=\"11\"/> <br/>
";
echo '<input class="button" type="submit" value="Сохранить" /></form>';
echo "</div>";
echo"<div class=silka><a href=\"office.php?\">Назад</a></div>";
break;



echo '<a href="office.php"><div class="back">Кабинет ['.$account['nick'].']</div></a>';

}



include($path.'inc/end.php');

?>