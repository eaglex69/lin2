<?
define('PROTECTOR', 1);

$headmod = 'pers';//фикс. места

$textl='Персонаж';
include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');
include($path.'inc/head.php');
include($path.'inc/zag.php');
if(empty($_GET[act])){






if($udata[lvl]<20){
$st="NG";}
elseif($udata[lvl]>=20 and $udata[lvl]<40){
$st="D";}
elseif($udata[lvl]>=40 and $udata[lvl]<52){
$st="C";}
elseif($udata[lvl]>=52 and $udata[lvl]<62){
$st="B";}
elseif($udata[lvl]>=62 and $udata[lvl]<76){
$st="A";}
elseif($udata[lvl]>=76){
$st="S";}


if($udata[klas]=="wizard"){$kl="m";$kln="Маг";}
if($udata[klas]=="fighert"){$kl="f";$kln="Воин";}




//картинка клана 
if(!empty($udata[clan])){
$req = mysql_query("SELECT `emblema` FROM `clan` WHERE `lider` = '$udata[clan]'");
$wh = mysql_fetch_array($req);

if(!empty($wh[emblema])){
$pic = "<img src=\"pic/clan/$wh[emblema]\" alt=\"clan\"/>";}}
//----------------

if ($udata[st]==0) {$sta='Бродяга';}
if ($udata[st]==1) {$sta='Вассал';}
if ($udata[st]==2) {$sta='Рыцарь';}
if ($udata[st]==3) {$sta='Маркиз';}


echo"<br/>$pic <font color=#f4d06e> $log  <font color=#f4d06e></b> [$sta]  ";
if ($udata[st]<3)
{echo "<a href=\"st.php\">[+]</a>";}

echo "<font color='#ffffcc'><br/>";

if($udata[storona]=="ork"){echo"Орк</b> ";}
if($udata[storona]=="elf"){echo"Cветлый Эльф</b> ";}
if($udata[storona]=="darkelf"){echo"Темный Эльф</b> ";}
if($udata[storona]=="human"){echo"Человек</b> ";}
if($udata[storona]=="gnom"){echo"Гном</b> ";}
if($udata[storona]=="kamael"){echo"Камаэль</b> ";}

echo $kln;
echo"</font></b><br/>";

//	статус 
if (!empty($udata[status])){
echo "<small><img src=\"pic/pers.png\" ><font color=#af9878><i> $udata[status] </i></center></font></small>";
echo '</div>';
}

			if (is_file('foto/'.$udata[usr].'.gif'))
				{
					echo'<br/><img src="foto/'.$udata[usr].'.gif" alt="foto" style="margin-right:10px;border:2px solid #383838"/><br/><br/>';
				} else 	{
							echo"<br/><img src='pic/avatar/$udata[storona]/$kl/$udata[pol]/$st.png' style='margin-right:10px;border:2px solid #383838'/><br/><br/>";
						}
						
	echo "<hr/>";

////////////////////	VIP AKK //////////////////////	
$reqvip = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1");
$avto=mysql_num_rows($reqvip);
if($avto==1){
$vip = mysql_fetch_array($reqvip);
echo' <font color=#6fcd72> Вип акаунт : x'.$vip[tip].' <br/>';
$vip[time]=$vip[time]-time();



												$time_dd = floor($vip[time]/86400); // сколько дней										
												$vip[time] = floor($vip[time]-($time_dd*86400)); // остача дня

												
												
												$time_ch = floor($vip[time]/3600); // сколько часов										
												$vip[time] = floor($vip[time]-($time_ch*3600)); // остача часов
												
												$time_min = floor($vip[time]/60);
												
												$time_sek = floor($vip[time]-($time_min*60));
												
													if ($time_sek<0) {
																	$time_min=$time_min-1;
																	$time_sek = floor($udata[time]-($time_min*60));
																	}
	
											echo "Осталось : $time_dd Дн. $time_ch Ч. $time_min М. $time_sek С.";



echo "</font><hr/>";
}

///////////////////	Anti PK AKK 	////////////////////
$reqvip = mysql_query("SELECT * FROM `anti_pk` WHERE `usr` = '$log' LIMIT 1");
$avto=mysql_num_rows($reqvip);
if($avto==1){
$vip = mysql_fetch_array($reqvip);
echo'<font color=#ff4040>Анти пк акаунт : <br/>';
$vip[time]=$vip[time]-time();




												$time_dd = floor($vip[time]/86400); // сколько дней										
												$vip[time] = floor($vip[time]-($time_dd*86400)); // остача дня

												
												
												$time_ch = floor($vip[time]/3600); // сколько часов										
												$vip[time] = floor($vip[time]-($time_ch*3600)); // остача часов
												
												$time_min = floor($vip[time]/60);
												
												$time_sek = floor($vip[time]-($time_min*60));
												
													if ($time_sek<0) {
																	$time_min=$time_min-1;
																	$time_sek = floor($udata[time]-($time_min*60));
																	}
	
											echo "Осталось $time_dd Дн. $time_ch Ч. $time_min М. $time_sek С.";




echo "</font><hr/>";
}
						
//echo"Уровень: $udata[lvl]<br/>";

//include($path.'inc/opitost.php');

echo" <img src=\"pic/rate.png\" > <font color=#f4d06e>Опыт :<font color=#ffffcc>      ".number_format($udata[exp], 0, ',', "`")."<br/>";
echo" <img src=\"pic/battles.png\" > <font color=#f4d06e>Очки умений :<font color=#ffffcc> 		".number_format($udata[sp], 0, ',', "`")."<br/>";

echo"<br/><font color=#ffffcc>";
echo" <img src=\"pic/col.png\" > <font color=#f4d06e>Аден :<font color=#ffffcc> ".number_format($udata[money], 0, ',', " ")."<br/>";
echo" <img src=\"pic/col.png\" > <font color=#f4d06e>Gold Bar :<font color=#ffffcc> ".number_format($udata[gold], 0, ',', " ")."<br/>";
echo" <img src=\"pic/col.png\" > <font color=#f4d06e>Coin of Luck :<font color=#ffffcc> ".number_format($udata[almaz], 0, ',', " ")." <a href=\"zaksms.php?\"><font color=#f4d06e>[+]</a><br/><br/></font>";

/*echo"<font color=#006400>Иголки: ".number_format($udata[ig], 0, ',', " ")."</font><br/>";*/
echo"<br/>";

/*$udata[wmr] = $udata[wmr]/100;
echo"<font color=#CC6633>WMR: ".number_format($udata[wmr], 2, ',', "`")."</font> <a href=\"/wmr_obmen.php?\"> [+] </a>";*/

echo "<hr/>";
if(!empty($udata[clan])){
$req = mysql_query("SELECT * FROM `clan` WHERE `lider` = '$udata[clan]' LIMIT 1");
$clan = mysql_fetch_array($req);

echo"<img src=\"pic/iclan.png\" > <font color=#f4d06e>Вы в клане : <b><a href=\"clan.php?id=$clan[id]\"><font color=#ffffcc>$clan[name]</a></b> | <a href=\"clanroom.php?go=clan\"><font color=#f4d06e>Выйти</font></a> <br/>";
}else{echo"";}
$req = mysql_query("SELECT id,name FROM `pit` WHERE `usr` = '$log'");
$avto=mysql_num_rows($req);
if($avto==1){
$pit = mysql_fetch_array($req);
echo"Питомец: <b><a href=\"pit.php?id=$pit[id]\">$pit[name]</a></b><br/>";
}
if ($udata[prof]=='' or $udata[prof]=='no'){$udata[prof]='Нет';}
echo "<img src=\"pic/prof.png\" > <font color=#f4d06e>Проффесия: <font color=#ffffcc>$udata[prof]</font><br/>";
if ($udata[titul]=='' or $udata[titul]=='no')
{$udata[titul]='Нет';}
echo"<img src=\"pic/prof.png\" > <font color=#f4d06e>Титул: <font color=#ffffcc>$udata[titul]</font><br/><br/>";

// расчёт онлайна


if ($udata[time]<60){$tim = "$udata[time] сек";} // cек

if ($udata[time]>59 and $udata[time]<3600)	{ // мин и cек
												
												$time_min = floor($udata[time]/60);
												$time_sek = floor($udata[time]-($time_min*60));
												
											
											$tim = "$time_min мин $time_sek сек";
											} 


if ($udata[time]>=3600 and $udata[time]<86400)	{ // часы, мин и cек
												
												$time_ch = floor($udata[time]/3600); // сколько часов										
												$udata[time] = floor($udata[time]-($time_ch*3600)); // остача часов
												
												$time_min = floor($udata[time]/60);
												
												$time_sek = floor($udata[time]-($time_min*60));
												
													if ($time_sek<0) {
																	$time_min=$time_min-1;
																	$time_sek = floor($udata[time]-($time_min*60));
																	}
	
											$tim = "$time_ch ч. $time_min мин. $time_sek сек.";
											} 
											
											
if ($udata[time]>=86400)	{ // дни, часы, мин и cек
												
												$time_dd = floor($udata[time]/86400); // сколько дней										
												$udata[time] = floor($udata[time]-($time_dd*86400)); // остача дня

												
												
												$time_ch = floor($udata[time]/3600); // сколько часов										
												$udata[time] = floor($udata[time]-($time_ch*3600)); // остача часов
												
												$time_min = floor($udata[time]/60);
												
												$time_sek = floor($udata[time]-($time_min*60));
												
													if ($time_sek<0) {
																	$time_min=$time_min-1;
																	$time_sek = floor($udata[time]-($time_min*60));
																	}
	
											$tim = "<font color=#ffffcc> $time_dd Дн. $time_ch Ч. ";
											} 


echo "<hr/> <p><img src=\"pic/rate.png\" > <font color=#f4d06e>Провёл на сайте :</font><font color=#ffffcc> $tim </font></p> </div>";



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// расчёт до бонуса


$udata[time] = 36000 - $udata[time_bon];

if ($udata[time]<0){
$tim = "Бонус зачислен!";
} // cек


if ($udata[time]<60){
$tim = "$udata[time] сек";
} // cек

if ($udata[time]>59 and $udata[time]<3600)	{ // мин и cек
												
												$time_min = floor($udata[time]/60);
												$time_sek = floor($udata[time]-($time_min*60));
												
											
											$tim = "$time_min мин $time_sek сек";
											} 


if ($udata[time]>=3600 and $udata[time]<86400)	{ // часы, мин и cек
												
												$time_ch = floor($udata[time]/3600); // сколько часов										
												$udata[time] = floor($udata[time]-($time_ch*3600)); // остача часов
												
												$time_min = floor($udata[time]/60);
												
												$time_sek = floor($udata[time]-($time_min*60));
												
													if ($time_sek<0) {
																	$time_min=$time_min-1;
																	$time_sek = floor($udata[time]-($time_min*60));
																	}
	
											$tim = "$time_ch ч. $time_min мин. $time_sek сек.";
											} 
											
											
if ($udata[time]>=86400)	{ // дни, часы, мин и cек
												
												$time_dd = floor($udata[time]/86400); // сколько дней										
												$udata[time] = floor($udata[time]-($time_dd*86400)); // остача дня

												
												
												$time_ch = floor($udata[time]/3600); // сколько часов										
												$udata[time] = floor($udata[time]-($time_ch*3600)); // остача часов
												
												$time_min = floor($udata[time]/60);
												
												$time_sek = floor($udata[time]-($time_min*60));
												
													if ($time_sek<0) {
																	$time_min=$time_min-1;
																	$time_sek = floor($udata[time]-($time_min*60));
																	}
	
											$tim = "<font color=#ffffcc> $time_dd Дн. $time_ch Ч.";
											} 

if ($udata[dostup]==0){$bon_col34 = 10;} 
if ($udata[dostup]==1){$bon_col34 = 30;} 
if ($udata[dostup]==2){$bon_col34 = 40;}
if ($udata[dostup]==3){$bon_col34 = 50;}
if ($udata[dostup]==4){$bon_col34 = 100;}
if ($udata[dostup]==5){$bon_col34 = 150;}

echo "<hr/> <p><img src=\"pic/rate.png\" > <font color=#f4d06e>Бонус +$bon_col34 Coin Of Luck через : </font><font color=#ffffcc> $tim </font></p> </div>";



$req654s55 = mysql_query("SELECT * FROM `rekc` WHERE `kogo` = '$log'");
///////////////////////////
$rekc=mysql_num_rows($req654s55);


echo"<hr/><font color=#f4d06e></b><br/>
<img src=\"pic/battles.png\" > <font color=#f4d06e>Побед на арене :<font color=#ffffcc> $udata[arenawins]<br/>
<img src=\"pic/ipvp.png\" > <font color=#f4d06e>Поражений на арене :<font color=#ffffcc> $udata[arenaloses]<br/>
<img src=\"pic/battles.png\" > <font color=#f4d06e>Побед :<font color=#ffffcc> $udata[wins]<br/>
<img src=\"pic/ipvp.png\" > <font color=#f4d06e>Поражений :<font color=#ffffcc> $udata[loses]</br>
<img src=\"pic/olymp.png\" > <font color=#f4d06e>Рекомендации : <a href=\"rekc.php?who\"><font color=#ffffcc>$rekc</a>
</div><br/>";

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

echo"<table class='msg'>";

$k_p = mysql_num_rows(mysql_query("SELECT * FROM `gifts` WHERE `id_user` = '$udata[id]'"));
if ($k_p > 0){
echo "<div class=silka><small><a href=\"gifts_2.php?\" title=\"Все\">Все подарки <font color=silver>($k_p)</font></a></small>";
}

$k_post = mysql_num_rows(mysql_query("SELECT * FROM `gifts` WHERE `id_user` = '$udata[id]' LIMIT 1"));

if ($k_post==0)
{
echo'<sub>Нет подарков</sub>';
}

$q = mysql_query("SELECT * FROM `gifts` WHERE `id_user` = '$udata[id]' ORDER BY time DESC LIMIT 4");
while ($f = mysql_fetch_array($q))
{
$a = mysql_fetch_array(mysql_query("SELECT * FROM `user` WHERE `id` = '$f[ot_id]' LIMIT 1"));
echo" <img src='/gifts/".$f['id_gifts'].".gif' width='32' height='32'  alt='' class='icon'/> ";
}
echo "</td></tr></table>";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//-------------------------------------------------------------
echo "<br><div class=inoy><a href=\"kill_ob.php\"><font color=#ffffcc><b>Обмен Coin Of Luck за убийство </b></font></a></div>";
//-------------------------------------------------------------


echo'<hr/>';
// повышение акт навыков

if (empty($_POST[col])){$col = 1;}else{$col = $_POST[col];}
if (!empty($_POST[what])){$_GET[what] = $_POST[what];}

//////////////равняем пост к гет////////////////

if(is_numeric($col)==TRUE) {
if($col>=0 && $udata[skill]>=$col){



if ($_GET[what]==no){
echo'<font color=red><p>Выберите что повышать!</p></font>';
}else{


if (isset($_GET[pdef]) or $_POST[what]==pdef){
if($udata[skill]>=$col){
$nskill=$udata[skill]-$col;
$nparm=$udata[pdef]+3*$col;
mysql_query("UPDATE users SET skill = '$nskill',pdef='$nparm' WHERE `usr` = '$log'");
$pdefpl = '&nbsp;|&nbsp;+'. 3*$col .'';
}}

if (isset($_GET[mdef]) or $_POST[what]==mdef){
if($udata[skill]>=$col){
$nskill=$udata[skill]-$col;
$nparm=$udata[mdef]+3*$col;
mysql_query("UPDATE users SET skill = '$nskill',mdef='$nparm' WHERE `usr` = '$log'");
$mdefpl = '&nbsp;|&nbsp;+'. 3*$col .'';
}}

if (isset($_GET[patt]) or $_POST[what]==patt){
if($udata[skill]>=$col){
$nskill=$udata[skill]-$col;
$nparm=$udata[patt]+3*$col;
mysql_query("UPDATE users SET skill = '$nskill',patt='$nparm' WHERE `usr` = '$log'");
$pattpl = '&nbsp;|&nbsp;+'. 3*$col .'';
}}

if (isset($_GET[matt]) or $_POST[what]==matt){
if($udata[skill]>=$col){
$nskill=$udata[skill]-$col;
$nparm=$udata[matt]+3*$col;
mysql_query("UPDATE users SET skill = '$nskill',matt='$nparm' WHERE `usr` = '$log'");
$mattpl = '&nbsp;|&nbsp;+'. 3*$col .'';
}}

if (isset($_GET[hp]) or $_POST[what]==hp){
if($udata[skill]>=$col){
$nskill=$udata[skill]-$col;
$nparm=$udata[hpall]+30*$col;
mysql_query("UPDATE users SET skill = '$nskill',hpall='$nparm' WHERE `usr` = '$log'");
$hppl = '&nbsp;|&nbsp;+'. 30*$col .'';
}}

if (isset($_GET[mp]) or $_POST[what]==mp){
if($udata[skill]>=$col){
$nskill=$udata[skill]-$col;
$nparm=$udata[mpall]+30*$col;
mysql_query("UPDATE users SET skill = '$nskill',mpall='$nparm' WHERE `usr` = '$log'");
$mppl = '&nbsp;|&nbsp;+'. 30*$col .'';

}}}}}




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// если нет записи в БД выше, то и обновлять инфу не нужно // // минус запрос к БД плюс оптимизация
if (!empty($mppl) or !empty($hppl) or !empty($mattpl) or !empty($pattpl) or !empty($mdefpl) or !empty($pdefpl)){
$reqi = mysql_query("SELECT * FROM `users` WHERE `usr`='$log'");
$udata = mysql_fetch_array($reqi);}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  if($udata[skill]>0){echo"<p><font color=#f4d06e> Активные навыки </font><font color=#ff4040>+".$udata[skill]."</font></p></div>";}

		//	вывод жизней //
echo"<img src=\"pic/life.png\" > <font color=#ff4040><b>Жизни:</b> $udata[hp]/$udata[hpall]</font>";if($udata[skill]>0){echo" <a href=\"pers.php?hp\">[+30]</a>";}echo"<font color=#04590A>$hppl</font><br/>";
echo"<img src=\"pic/energy.png\" > <font color=skyblue><b>Мана:</b> $udata[mp]/$udata[mpall]</font>";if($udata[skill]>0){echo" <a href=\"pers.php?mp\">[+30]</a>";}echo"<font color=#04590A>$mppl</font><br/>";
echo'<br/>';
/////////////////////////////////////////////////////////////////////////
		
		
// открываем табл бафов если есть
$reqbf = mysql_query("SELECT * FROM `baf` WHERE `usr`='$log'");
$avto=mysql_num_rows($reqbf);
while($mag = mysql_fetch_array($reqbf))
{
$at=$mag[bafat]+$at;
$df=$mag[pmdef]+$df;

if (!empty($mag[bafat])){$bafat="<font color=grey><small>+	[".number_format($at, 0, ',', "`")."]</small></font>";}
if (!empty($mag[pmdef])){$pmdef="<font color=grey><small>+	[".number_format($df, 0, ',', "`")."]</small></font>";}
}
/////////////////////////////////



echo"<img src=\"pic/ipvp.png\" > <font color=#f4d06e>Физ. защита :  <font color=#ffffcc> ".number_format($udata[pdef], 0, ',', "`")." $pmdef ";if($udata[skill]>0){echo" <a href=\"pers.php?pdef\">[+3]</a>";}echo"<font color=#ffffcc>$pdefpl</font><br/>";
echo"<img src=\"pic/ipvp.png\" > <font color=#f4d06e>Маг. защита :  <font color=#ffffcc> ".number_format($udata[mdef], 0, ',', "`")." $pmdef "; if($udata[skill]>0){echo" <a href=\"pers.php?mdef\">[+3]</a>";}echo"<font color=#f4d06e>$mdefpl</font><br/>";
echo"<img src=\"pic/battles.png\" > <font color=#f4d06e>Физ. атака :  <font color=#ffffcc> ".number_format($udata[patt], 0, ',', "`")." $bafat "; if($udata[skill]>0){echo" <a href=\"pers.php?patt\">[+3]</a>";}echo"<font color=#f4d06e>$pattpl</font><br/>";
echo"<img src=\"pic/battles.png\" > <font color=#f4d06e>Маг. атака :  <font color=#ffffcc> ".number_format($udata[matt], 0, ',', "`")." $bafat "; if($udata[skill]>0){echo" <a href=\"pers.php?matt\">[+3]</a>";}echo"<font color=#f4d06e>$mattpl</font>";


if($udata[skill]>=5){
echo '<hr/><form action="pers.php?ac=skill" method="post">';
echo "<font color=grey><b>Параметр:</b></font><br/>
<select name=\"what\">
<option value=\"no\">Выбрать</option>
<option value=\"pdef\">P.Def (Физ.защ.)</option>
<option value=\"mdef\">M.Def (Маг.защ.)</option>
<option value=\"patt\">P.Att (Физ.атк.)</option>
<option value=\"matt\">M.Att (Маг.атк.)</option>
<option value=\"hp\">HP (Жизни)</option>
<option value=\"mp\">MP (Мана)</option>
</select><br/>";

echo"<font color=grey><b>Кол-во:</b></font>:<br/>
<input class='input' type=\"text\" value=\"$udata[skill]\" name=\"col\"/><br/>";




echo '<input class="button" type="submit" value="Повысить" /></form><hr/>';}else{echo'<br/><br/>';}



echo "<center>
Официальная группа | Вконтакте<br/>
  <a href=\"http://vk.com/miniage\"><img src='pic/ico_vk.png' alt='vk'/></a> 

</center><hr/>";

if($udata[dostup] >= 4)
{
echo" <br><a href=\"adm_panel.php?mod=red&nick=$log\"><font color=#ffffcc>Редакт</font></a></b> </br> ";
}


echo "<br><div style=\"text-align:center;margin:10 auto\"><div class=\"pic\">";
echo"	<a href=\"pers.php?act=mag\"> Мои умения</a>";
				

// если есть бафы
$reqi222 = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log' and 
`tip` = 'bafat' or `usr` = '$log' and
`tip` = 'bafkrit' or `usr` = '$log' and
`tip` = 'bafzash' or `usr` = '$log' and
`tip` = 'resp' or `usr` = '$log' and
`tip` = 'bafzat' or `usr` = '$log' and
`tip` = 'bafhp'");

if(mysql_num_rows($reqi222)>=1)
{
echo"<a href=\"baf.php\"> Бафы</a>";

}
echo"<a href=\"opt.php?\"> Настройки</a>	";
//echo"		<a href=\"pers.php?act=stats\"> &#160 Статистика</a>			";
echo"<a href=\"pers.php?act=shmot\">  Вооружение</a>";
echo"<a href='msg.php'> Почта</a>";
echo"<a href=\"anketa.php?go=go&nick=$log\">  Анкета</a>";

if ($udata[prof]=='Artisan' or $udata[prof]=='Warsmith'  or $udata[prof]=='Maestro')
{echo "<a href=\"workshop.php?\"> Книга рецептов</a>";}

echo '		<a href="/index.php?mod=exit"> Выход </span></a>';


//echo '		<a href="index.php?mod=quests"> &#160 Квесты </a>';http://l-nw.ru/index.php?mod=exit
echo"</div>";
////////////////////
}else

if($_GET[act]==shmot){
//Шмот

echo"<b>Снаряжение:</b><br/><br/>";
$i=1;
while($i<=13){
switch($i){
case '1':
$tip='Шлем';
$gt='golova';
break;
case '2':
$tip='Доспех';
$gt='body';
break;
case '3':
$tip='Оружие';
$gt='weapon';
break;
case '4':
$tip='Рукавицы';
$gt='ruki';
break;
case '5':
$tip='Штаны';
$gt='pants';
break;
case '6':
$tip='Сапоги';
$gt='nogi';
break;
case '7':
$tip='Щит';
$gt='shit';
break;
case '8':
$tip='Амулет';
$gt='amulet';
break;
case '9':
$tip='Левая серьга';
$gt='poyas';
break;
case '10':
$tip='Правая серьга';
$gt='rpoyas';
break;
case '11':
$tip='Левое кольцо';
$gt='kolco';
break;
case '12':
$tip='Правое кольцо';
$gt='rkolco';
break;
case '13':
$tip='Плащ';
$gt='plash';
break;

}

$req = mysql_query("SELECT id,name FROM `item` WHERE `usr` = '$log' and `tip`='$gt' and `image`='yes'");
$mag = mysql_fetch_array($req);
$u = explode("*",$mag[name]);

if($gt!='vkolco'){
//echo" $tip: ";
$req = mysql_query("SELECT id,name FROM `item` WHERE `usr` = '$log' and `tip`='$gt' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"<img src=\"shmot/pusto/$gt.gif\" alt=\"pic\"/> Ничего не одето<br/>";
}else{               
$mag = mysql_fetch_array($req);
echo" <img src=\"shmot/$u[0].png\" alt=\"pic\"/> <a href='inventar.php?mod=info&amp;id=$mag[id]'><b>$mag[name]</b></a> [<a href='snyat.php?tip=$gt'><font color='red'>X</font></a>]
<br/>";
}}else{
$req = mysql_query("SELECT id,name FROM `item` WHERE `usr` = '$log' and `tip`='$gt' and `image`='yes'");
$avto=mysql_num_rows($req);
if($avto==0){
echo"<img src=\"shmot/pusto/$gt.gif\" alt=\"pic\"/> Ничего не одето<br/>";
echo"<img src=\"shmot/pusto/$gt.gif\" alt=\"pic\"/> Ничего не одето<br/>";
}elseif($avto==1){
$mag = mysql_fetch_array($req);
echo"<img src=\"shmot/$u[0].png\" alt=\"pic\"/> <a href='inventar.php?mod=info&amp;id=$mag[id]'><b>$mag[name]</b></a> [<a href='snyat.php?tip=$gt'><font color='red'>X</font></a>]<br/>";
echo"<img src=\"shmot/pusto/$gt.gif\" alt=\"pic\"/> Ничего не одето<br/>";
}elseif($avto==2){
While($best = mysql_fetch_array($req))
{       $u = explode("*",$best[name]);      
echo"<img src=\"shmot/$u[0].png\" alt=\"pic\"/> <a href='inventar.php?mod=info&amp;id=$best[id]'><b>$best[name]</b></a> [<a href='snyat.php?tip=$gt&amp;id=$best[id]'><font color='red'>X</font></a>]<br/>";
}
}
}
$i++;
}



//Конец
}

elseif($_GET[act]==mag){

echo "<b>Ваши умения:</b> <br/><br/>";

if($_GET[activ]==yes){

mysql_query("UPDATE `mag` SET `activ` = 'yes' WHERE usr = '$log' and id = '$_GET[id]'");} // скил активен

if($_GET[activ]==no){

mysql_query("UPDATE `mag` SET `activ` = 'no' WHERE usr = '$log' and id = '$_GET[id]'");}	//скил отключ

if($_GET[activ]==del){

if (!empty($_POST[result])){
$ressave = mysql_query("DELETE FROM `mag` WHERE usr = '$log' and id = '$_POST[result]'");	//удаляем скил

				if ($ressave == 'true')
					{
					echo '<font color=#007F46><p>Умение удаленно!</p></font>'; // удачно
					}
					else
					{
					echo "<font color=red><p> Ошибка доступа! </p></font>";  // неудачно =)
					}

//mysql_query("UPDATE `mag` SET `activ` = 'no' WHERE usr = '$log' and id = '$_GET[id]'");
}else{echo "<font color=#007F7F><p>Вы не выбрали умение!</p></font> ";}

}

$req = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log'");
if(mysql_num_rows($req)>=1)
{
While($mag = mysql_fetch_array($req))
{
$maglvl=$mag[lvl]-1;
$magmp=explode("|",$mag[mp]);
$mag[mp]=$magmp[$maglvl];//мп

$maghp=explode("|",$mag[hp]);
$mag[hp]=$maghp[$maglvl];//хп

$maghp=explode("|",$mag[plushp]);
$mag[plushp]=$maghp[$maglvl];//+хп

$maghp=explode("|",$mag[uron]);
$mag[uron]=$maghp[$maglvl];//+урон
echo'<div class="event">';

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

echo "<form action=\"pers.php?act=mag&activ=del\" method=\"POST\">";


echo"<hr/><img src=\"pic/skils/$mag[name].jpg\" alt=\"pic\"/> ";

echo " <label><input type='radio' name='result' value='$mag[id]' /></label>\n";

if ($mag[activ]=='' or $mag[activ]=='yes'){
echo"	<a href=\"pers.php?act=mag&activ=no&id=$mag[id]\"><b><font color=green>$mag[name]</font></b></a>				";}
if ($mag[activ]=='no'){
echo"	<a href=\"pers.php?act=mag&activ=yes&id=$mag[id]\"><b><font color=red>$mag[name]</font></b></a>				";}

echo"<br/>Уровень: $mag[lvl]<br/>";
echo"Класс: ";
if($mag[klas]=="wizard"){echo"Маг<br/>";}
if($mag[klas]=="fighert"){echo"Воин<br/>";}
echo"Урон: $mag[uron]<br/>";
echo"Забирает маны: $mag[mp]<br/>";
echo"Забирает здоровья: $mag[hp]<br/>";
echo"Даёт здоровья: $mag[plushp]<br/>";
echo"Тип умения: $tip</div>";
if ($mag[ruka] =="not" or $mag[ruka]==""){}else
{
echo "<font color=grey>Для использования нужно держать в руках <b>$tp</b></font><br/>";
}

}
}else{
echo '<b>Нет умений!</b>';
}
/////////////

echo "<hr/>";
echo " <div class=menu><input type='submit' name='ok' value='Удалить умение' /></div>\n";

echo "<font color = lime>  ** Выберите скил из списка если хотите удалить его. </font><br/>";
echo "<font color = lime> - <font color=red>Красное</font> умение означает выключенно.<br/>
-<font color=green> Зелёное</font> активно. <br/>
- <b>Клацнув на скил Вы его \"активируете\" или \"отключите\".</b></small>
";

echo "</font><hr/>";
echo"<br/><a href=\"pers.php?\">Назад</a>";
}
else{
echo'Ошибка';
}
include($path.'inc/down.php');
?>