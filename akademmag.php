<?
define('PROTECTOR', 1);

$headmod = 'akademmag';//фикс. места

$textl='Академия магии';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
going();
place_okr();
place_zamok();
place_tower();
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');


if($udata[klas]!='wizard'){echo"Вход только для магов!";include($path.'inc/down.php');exit;}


// делаем светлым и тёмным эльфам одинаковые скилы  //
if ($udata[storona] == 'darkelf'){$udata[storona] = 'elf';}
//-------------        ------------            ----//
// Приравниваем професии магу //----------------------------
if ($udata[prof] == 'Dark Elf Mage'){$udata[prof] = 'Elf Mage';}// 1профа
if ($udata[prof] == 'Dark Elf Wizard'){$udata[prof] = 'Elf Wizard';} // 2,1
if ($udata[prof] == 'Shillien Oracle'){$udata[prof] = 'Oracle';} // 2,2
if ($udata[prof] == 'Spell Howler'){$udata[prof] = 'Elemental Summoner';} // 3,1
if ($udata[prof] == 'Phantom Summoner'){$udata[prof] = 'Spellsinger';} // 3,2
if ($udata[prof] == 'Shillien Elder'){$udata[prof] = 'Elder';} // 3,3
if ($udata[prof] == 'Storm Screamer'){$udata[prof] = 'Elemental Master';} // 4,1
if ($udata[prof] == 'Spectral Master'){$udata[prof] = 'Mystic Muse';} // 4,2
if ($udata[prof] == 'Shillen Saint'){$udata[prof] = 'Eva Saint';} // 4,3
/////////////////////////////////////////////////////////////////



switch($_GET[mod]){

default:

echo" <center><img src='/pic/roz/верхмаг.png'   alt=''  ></div><hr/>";

echo "<b><font color=#f4d06e>Верховный тёмный маг:</font></b><br/><br>";
echo "Здравствуй, $log!<br> Мы тебя обучим магии лечебной и боевой, 
но сначало ты должен доказать что ты достоин её! 
Получай определенные профессии и мы будем обучать тебя новой силе.<br/><br/>";

echo"<div class=inoy><a href=\"profa.php?\">Получение професии</a>";
echo"<a href=\"akademmag.php?mod=new\">Выучить умение</a>";
echo"<a href=\"akademmag.php?mod=up\">Повысить умение</a></div>";
break;

case 'new':

echo "<b>Тёмный маг: </b>Какое умение нужно?!<br/><br/>";
echo "<hr/>";

$req = mysql_query("SELECT * FROM `profa` WHERE `name`='$udata[prof]'");// ..грузим професии
$prof = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `profa` WHERE `name`='$prof[next]'");// ..грузим професии
$prof2 = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `profa` WHERE `name`='$prof2[next]'");// ..грузим професии
$prof3 = mysql_fetch_array($req);

$req = mysql_query("SELECT * FROM `shop_mag` WHERE 
`klas`='wizard' and `storona`='$udata[storona]' and `prof`='$udata[prof]' or 
`klas`='wizard' and `storona`='$udata[storona]' and `prof`='$prof[next]' or
`klas`='wizard' and `storona`='$udata[storona]' and `prof`='$prof3[next]' or
`klas`='wizard' and `storona`='$udata[storona]' and `prof`='$prof2[next]'");// ..добавить сторону и профу
////////////////////////////
$avto=mysql_num_rows($req);
if($avto>=1){
While($mag = mysql_fetch_array($req))
{

$reqin = mysql_query("SELECT * FROM `mag` WHERE `usr`='$log' and `lat_name`='$mag[lat_name]'");
$magia = mysql_num_rows($reqin);
$magia = mysql_fetch_array($reqin);
if ($magia==0) 
{
echo"<img src=\"pic/skils/$mag[name].jpg\" alt=\"pic\"/>  <a href=\"akademmag.php?mod=info&amp;id=$mag[id]\">$mag[name]</a><hr/>";
}
else {echo "<img src=\"pic/skils/$mag[name].jpg\" alt=\"pic\"/>  <font color = green><b>$mag[name]</font></b> <hr/>";} // нет значения

}echo "- - - - -";
}else{
echo"Нет умений !<br/>";
}
echo"<br/><a href=\"akademmag.php?\">Назад</a>";
break;
case 'info':
$req = mysql_query("SELECT * FROM `shop_mag` WHERE `id`='$_GET[id]' and `klas`='wizard'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

$mag_mp=$mag[mp];
$magmp=explode("|",$mag[mp]);
$mag[mp]=$magmp[0];//мп

$mag_hp=$mag[hp];
$maghp=explode("|",$mag[hp]);
$mag[hp]=$maghp[0];//хп

$mag_plushp=$mag[plushp];
$maghp=explode("|",$mag[plushp]);
$mag[plushp]=$maghp[0];//+хп

$mag_uron=$mag[uron];
$maghp=explode("|",$mag[uron]);
$mag[uron]=$maghp[0];//+урон

$mag_cena=$mag[cena];
$magcena=explode("|",$mag[cena]);
$mag[cena]=$magcena[0];//+цена


if($_GET[act]!='by'){

if ($mag[ruka]=='me4'){$tp="Меч";}
if ($mag[ruka]=='kas'){$tp="Кастет";}
if ($mag[ruka]=='rap'){$tp="Рапиру";}
if ($mag[ruka]=='luk'){$tp="Лук";}
if ($mag[ruka]=='kin'){$tp="Кинжал";}


if ($mag[tip]=='atack'){$tip="Атака";}
if ($mag[tip]=='bafkrit'){$tip="Баф Критов";}
if ($mag[tip]=='bafat'){$tip="Баф Атаки";}
if ($mag[tip]=='bafzash'){$tip="Баф Защиты";}
if ($mag[tip]=='bafzat'){$tip="Баф Защиты и Атаки";}
if ($mag[tip]=='hpot'){$tip="Отжор";}
if ($mag[tip]=='hp'){$tip="Реген. HP";}
if ($mag[tip]=='mp'){$tip="Реген. MP";}
if ($mag[tip]=='bafmp'){$tip="Баф на ману";}
if ($mag[tip]=='bafhp'){$tip="Баф на жизни";}
if ($mag[tip]=='resp'){$tip="Респ. игрока";}
if ($mag[tip]=='spoil'){$tip="Спойлинг";}
if ($mag[tip]=='spoilall'){$tip="Спойлинг всех вещей";}


echo'<div class="event">';
echo"<img src=\"pic/skils/$mag[name].jpg\" alt=\"pic\"/> <b>$mag[name]:</b><br/>";
echo"Доступно с $mag[lvl] уровня<br/>";
echo"Цена: $mag[cena] SP<br/>";
echo"Урон: $mag[uron]<br/>";
echo"Забирает маны: $mag[mp]<br/>";
echo"Забирает здоровья: $mag[hp]<br/>";
echo"Даёт здоровья: $mag[plushp]<br/>";
echo"Тип умения: $tip<br/>";

if ($mag[ruka] =="not" or $mag[ruka]==""){}else
{
echo "<font color=grey>Для использования нужно держать в руках <b>$tp</b></font><br/>";
}
if ($mag[nead_res]=="net" or $mag[nead_res]=="") {}else{

echo"Что бы получить умение нужны ресурсы: <br/>";

$res=explode("|",$mag[nead_res]);
$kres=count($res);
$i=0;
$b=1;
while($b<=$kres){
$r=explode(",",$res[$i]);
$lat_name=$r[0];
$kol=$r[1];

$req = mysql_query("SELECT name FROM `mdrop` WHERE `lat_name`='$lat_name' LIMIT 1");
$reso = mysql_fetch_array($req);
echo"$reso[name] - $kol штук<br/>";
$i++;
$b++;
}}
echo"</div>";
echo"<a href=\"akademmag.php?mod=info&amp;act=by&amp;id=$mag[id]\"><b>Изучить</b></a><br/>";
}else{

if($udata[lvl]<$mag[lvl]){
echo'Низкий уровень!';
include($path.'inc/down.php');
exit;
}

if($udata[sp]<$mag[cena]){
echo'Недостаточно SP! Иди в окресности и бей мобов.';
include($path.'inc/down.php');
exit;
}

if($udata[storona]!=$mag[storona])
{
echo'Ошибка! Умение не подходит!';
include($path.'inc/down.php');
exit;
}

$req = mysql_query("SELECT * FROM `profa` WHERE `name`='$udata[prof]'");// ..грузим професии
$prof = mysql_fetch_array($req);
$req2 = mysql_query("SELECT * FROM `profa` WHERE `name`='$prof[next]'");// ..грузим професии
$prof2 = mysql_fetch_array($req2);
$req3 = mysql_query("SELECT * FROM `profa` WHERE `name`='$prof2[next]'");// ..грузим професии
$prof3 = mysql_fetch_array($req3);

$reqprof = mysql_query("SELECT * FROM `shop_mag` WHERE 
`klas`='wizard' and `storona`='$udata[storona]' and `prof`='$udata[prof]' or 
`klas`='wizard' and `storona`='$udata[storona]' and `prof`='$prof[next]' or
`klas`='wizard' and `storona`='$udata[storona]' and `prof`='$prof3[next]' or
`klas`='wizard' and `storona`='$udata[storona]' and `prof`='$prof2[next]'");// ..добавить сторону и профу
$magia = mysql_num_rows($reqprof);
if ($magia==0) 
{
echo"Ошибка! Умение не подходит!";
include($path.'inc/down.php');
exit;
}



$reqin = mysql_query("SELECT * FROM `mag` WHERE `usr`='$log' and `lat_name`='$mag[lat_name]' LIMIT 1");
$magia = mysql_num_rows($reqin);
if ($magia!=0) 
{
echo"Ошибка! Умение уже есть!";
include($path.'inc/down.php');
exit;
}






if ($mag[nead_res]=="net") {echo "";}else{
$res=explode("|",$mag[nead_res]);
$kres=count($res);
$i=0;
$b=1;
while($b<=$kres){
$r=explode(",",$res[$i]);
$lat_name=$r[0];
$kol=$r[1];

$req = mysql_query("SELECT * FROM `res` WHERE `usr`='$log' and `lat_name`='$lat_name' and `kol`>='$kol' LIMIT 1");
$avto=mysql_num_rows($req);
if($avto==0){
$not=TRUE;
}
$res[$b]=$lat_name;
$rb[$b]=$kol;
$i++;
$b++;
}

if($not==TRUE){
echo'Недостаточно ресурсов!';
include($path.'inc/down.php');
exit;
}

if(empty($not)){
$b=1;
while($b<=$kres){
$r=explode(",",$res[$i]);
$lat_name=$res[$b];
$kol=$rb[$b];

$req = mysql_query("SELECT * FROM `res` WHERE `usr`='$log' and `lat_name`='$lat_name' LIMIT 1");
$re = mysql_fetch_array($req);
if(($re[kol]-$kol)<=0){
mysql_query("DELETE FROM `res` WHERE `usr`='$log' and `lat_name`='$lat_name' LIMIT 1");//чистим логи
}else{
$ros=$re[kol]-$kol;
mysql_query("UPDATE `res` SET `kol` = '$ros' WHERE `usr`='$log' and `lat_name`='$lat_name' LIMIT 1");
}
$b++;
}
}
}



$udata[sp]=$udata[sp]-$mag[cena];// снимает очки SP

mysql_query("UPDATE `users` SET `sp` = '$udata[sp]' WHERE `usr` = '$log'");

mysql_query("INSERT INTO
        `mag` SET
        `usr` = '$log',
        `name` = '$mag[name]',
        `lat_name` = '$mag[lat_name]',
        `tip` = '$mag[tip]',
        `ruka` = '$mag[ruka]',
        `klas` = '$mag[klas]',
        `uron` = '$mag_uron',   
        `mp` = '$mag_mp',   
        `hp` = '$mag_hp',   
        `plushp` = '$mag_plushp',   
        `cena` = '$mag_cena',        
        `lv` = '$mag[lv]',        
        `lvlmax` = '$mag[lvlmax]',        
        `lvl` = '1'");

echo"Умение $mag[name] изучено!<br/>";
}
echo"<br/><a href=\"akademmag.php?\">Назад</a>";
break;

case 'up':


echo "<b>Тёмный маг:</b><br/> ";

//-----------
$req = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log'");
$avto=mysql_num_rows($req);

if($avto>=1){

While($mag = mysql_fetch_array($req))
{

$maglv=explode("|",$mag[lv]);
$mag[lv]=$maglv[$mag[lvl]];//след уровень
//-----------



$req = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log'");
////////////////////////////
$avto=mysql_num_rows($req);

if($avto>=1){

echo "Вот какие умения ты можешь повысить, или я скажу с какого уровня они будут доступны для повышения...<br/><br/>";
echo "<hr/>";

While($mag = mysql_fetch_array($req))
{
$maglv=explode("|",$mag[lv]);
$mag[lv]=$maglv[$mag[lvl]];//след уровень

if ($mag[lvl]>=$mag[lvlmax]){}else{
echo "<img src=\"pic/skils/$mag[name].jpg\" alt=\"pic\"/> ";
if ($mag[lv]>=$udata[lvl]){
echo"<b>$mag[name]</b><br/>Доступен с $mag[lv] уровня<hr/>";
}else{
echo"<a href=\"akademmag.php?mod=upgo&amp;id=$mag[id]\">$mag[name]</a></div> <hr/>";
}}}}}}else {echo "<p>Умений нет или у Вас еще мал уровень.</p> ";}

echo"<br/><a href=\"akademmag.php?\">Назад </a>";
break;


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	//				Повышение скила 			///


case 'upgo':
$req = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log' and `id`='$_GET[id]'");

$avto=mysql_num_rows($req);
if($avto==0){
echo'Ошибка!';
include($path.'inc/down.php');
exit;
}
$mag = mysql_fetch_array($req);

if($mag[lvl]>=$mag[lvlmax]){
echo'У умения макс. уровень!';
include($path.'inc/down.php');
exit;
}


$magmp=explode("|",$mag[mp]);
$mag[mp]=$magmp[$mag[lvl]];//мп

$maghp=explode("|",$mag[hp]);
$mag[hp]=$maghp[$mag[lvl]];//хп

$maghp=explode("|",$mag[plushp]);
$mag[plushp]=$maghp[$mag[lvl]];//+хп

$maghp=explode("|",$mag[uron]);
$mag[uron]=$maghp[$mag[lvl]];//+урон

$magcena=explode("|",$mag[cena]);
$mag[cena]=$magcena[$mag[lvl]];//+cena

$maglv=explode("|",$mag[lv]);
$mag[lv]=$maglv[$mag[lvl]];//след уровень


$mag[lvl]++;
//--------------------------------
if($_GET[act]=='by'){

if ($udata[lvl]<$mag[lv]){
echo "У Вас мал уровень!";
include($path.'inc/down.php');
exit;
}
/*
if (empty($mag[uron]) or empty($mag[lv]) or empty($mag[cena]) or empty($mag[mp]) or empty($mag[hp]) or empty($mag[cena])){
echo "Ошибка! Нет значения. Сообщите администрации об этом. Не забудте указать название скила - <font color=red>$mag[lat_name]</font> - и ID скила - <font color=red>$mag[id]</font>. Благодарим за помощь!";
include($path.'inc/down.php');
exit;
}
*/

if($udata[sp]<$mag[cena]){
echo'Мало SP!';
include($path.'inc/down.php');
exit;
}

$udata[sp]=$udata[sp]-$mag[cena];//sp очки 

//$mag[lvl]++;

mysql_query("UPDATE `users` SET `sp` = '$udata[sp]' WHERE `usr` = '$log'");

mysql_query("UPDATE `mag` SET `lvl` = '$mag[lvl]' WHERE `usr`='$log' and `lat_name`='$mag[lat_name]' LIMIT 1");

echo"<font color=#007F46><p>Умение $mag[name] повышено!</p></font>";}

//----------------------------------
echo'<div class="event">';
echo"<b><p>$mag[name]:</b></p>";
echo"Нужен уров.: $mag[lv]<br/>";
echo"Цена: <b>$mag[cena]</b> SP<br/>";
echo"Урон: +$mag[uron] DM<br/>";
echo"Забирает маны: -$mag[mp] MP<br/>";
echo"Забирает здоровья: -$mag[hp] HP<br/>";
echo"Даёт здоровья: +$mag[plushp] HP<br/>";
echo"</div>";
echo"<br/><a href=\"akademmag.php?mod=upgo&amp;act=by&amp;id=$mag[id]\"><b>Повысить</b></a>";
 


echo"<br/><a href=\"akademmag.php?\">Назад</a>";
echo "<hr/><font color=grey> * Если в умениях нет значения какого либо параметра, просьба сообщить об этом Администрацию.</font><hr/>";
break;
}
include($path.'inc/down.php');
?>