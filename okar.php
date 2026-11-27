<?php
define('PROTECTOR', 1);

include('inc/path.php');
include($path.'inc/db.php');
include($path.'inc/auth.php');
include($path.'inc/func.php');
include($path.'inc/core.php');

$time=time();
////////////////////////////////////////////////////////
if ($user_id==1){
if ($inaw==1){
include($path.'inc/head.php');
include($path.'inc/zag.php');
echo'У вас бой на арене!';
include($path.'inc/down.php');exit;
}
///////////////
$id="$_GET[id]";
if(empty($_GET['k'])){
/////////////////////////////////////////////////////

}elseif($_GET['k']=='ar'){
$req = mysql_query("SELECT * FROM `users` WHERE `id` = '$_GET[id]'");
////////////////////////////
$avto=mysql_num_rows($req);

if($avto=="0"){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Нет такого игрока!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}

$pk = mysql_fetch_assoc($req);
$req = mysql_query("SELECT * FROM `pk` WHERE `usr` = '$pk[usr]' LIMIT 1");
$avto=mysql_num_rows($req);


if($avto>="1"){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Игрок уже в бою!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}


$reqtmp = mysql_query("SELECT * FROM `tmp` WHERE `usr` = '$pk[usr]' LIMIT 1");
if (mysql_num_rows($reqtmp)>=1){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Игрок уже в бою!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}

$reqtmp = mysql_query("SELECT * FROM `tmp_zamok` WHERE `usr` = '$pk[usr]' LIMIT 1");
if (mysql_num_rows($reqtmp)>=1){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Игрок уже в бою!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}


if($pk['usr']==$log){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='На себя напасть нельзя!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}

$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$pk[usr]'");
$mpk = mysql_fetch_assoc($req);
$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log'");
$mypk = mysql_fetch_assoc($req);

if($pk['lvl']<20){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='На слабых нападать нельзя!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}

if($pk['hp']<0){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Персонаж $pk[usr] убит!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}


if($udata['lvl']<20){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Арена будет доступна с 20 уровня!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}


$timeout = time() - 150;
$asd = mysql_num_rows(mysql_query("SELECT laikas, usr FROM online WHERE laikas > '$timeout' AND usr='$pk[usr]'"));
////////////////////////////

if($asd=='0'){
$textl='Битва';
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Этот игрок Офф-Лайн!',kto='user',place='battle',timer='$timer',new='1'");
header ('Location: arena.php?');exit;
}


///////////////// совпадение арены 	//////////////////

$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$pk[usr]'");
$mpk = mysql_fetch_array($req);
$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log'");
$mypk = mysql_fetch_array($req);

if($mpk['place'] != $mypk['place']){
$textl='Битва';

mysql_query("INSERT INTO logpk SET usr='$udata[usr]',text='Игрок не рядом!',kto='user',place='battle',timer='$timer',new='1'");

header ('Location: arena.php?');exit;
}

///////////////////////////////////////////////////////////



$timer=time()+300;
/////////////////////////////////////////////////////////////////////////////////////////////
// PK арена сразу снимает ХП обоим игрокам (расчёт)

$reqpk = mysql_query("SELECT * FROM `users` where `id`='$id'");
$pk = mysql_fetch_array($reqpk); 	//($pk[......]) - инфа атакуемого  
									//($udata[...]) - инфа атакующего

// щитаем урон

// тот, кто нападает
if ($udata['klas']=="wizard") {$uron = $udata['matt']-$pk['mdef'];}   // если маг, то  работает маг атака и маг защита
if ($udata['klas']=="fighert") {$uron = $udata['patt']-$pk['pdef'];}  // если воин, то работает физ атака и защита

// тот, на кого нападают
if ($pk['klas']=="wizard") {$uron2 = round(($pk['matt']-$udata['mdef'])/3);}   // если маг, то  работает маг атака и маг защита
if ($pk['klas']=="fighert") {$uron2 = round(($pk['patt']-$udata['pdef'])/3);}  // если воин, то работает физ атака и защита


If ($uron<0){$uron=0;} // если урон в минуса то он равен 0 иначе плюсует без этого кода
If ($uron2<0){$uron2=0;} // если урон в минуса то он равен 0 иначе плюсует без этого кода



// тот, кто атакует 
if ($pk['hp']>$uron){


$nhp=$pk['hp']-$uron;

mysql_query("UPDATE users SET hp = '$nhp' WHERE `id` = '$id'");
mysql_query("INSERT INTO logar SET usr='$pk[usr]',text='Вас атаковал  $udata[usr]! Урон -$uron!',kto='enemy',place='battle',timer='$timer',new='1'");
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Вы атаковали  $pk[usr]! Урон -$uron!',kto='user',place='battle',timer='$timer',new='1'");

///////////////////////////////////////////////////////////////////
// автоответ атаки на атаку
if ($udata['hp']>$uron2){ // не убил
$nhp2=$udata['hp']-$uron2;
mysql_query("UPDATE users SET hp = '$nhp2' WHERE `id` = '$udata[id]'");
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Вас атаковал в ответ $pk[usr]! Урон -$uron2!',kto='enemy',place='battle',timer='$timer',new='1'");
}else{ //убил


// проигравший
$nhp2=$udata['hp']-$uron2;
$nloses2=$udata['loses']+1;
mysql_query("UPDATE users SET hp = '$nhp2',loses = '$nloses2' WHERE `id` = '$udata[id]'");

// выгравший
$aren2=$pk['arenawins']+1;
$nwin2=$pk['wins']+1;


mysql_query("UPDATE users SET wins = '$nwin2',arenawins = '$aren2', 'WHERE `usr` = '$pk[usr]'");
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Вас убил в ответной атаке $pk[usr]! Урон -$uron2!',kto='enemy',place='battle',timer='$timer',new='1'");
}
///////////////////////////////////////////////////////////////////////

header ('Location: arena.php?');exit;

}else{ // убил



//	---	//	---	//	---	//	//	 	ЗАДАНИЯ		//	//	---	//	---	//	---	//
if ($udata['lvl']>49){
$reqev = mysql_query("SELECT * FROM job_pvp WHERE `usr` = '$log' Limit 1");
////////////////////////////
$jm = mysql_fetch_array($reqev);
if(mysql_num_rows($reqev)>=1){ // если уже участвует  то дописываем
if ($jm['kill']!=='off'){ // если уже получил вознагр то табла не созд
$kill=$jm['kill']+1; 
if ($kill<=100){ // 100 - это кол-во менять так же в job.php
mysql_query("UPDATE job_pvp SET `kill` = '$kill' WHERE `usr` = '$log' Limit 1");}}} // дописываем +1 к количеству}
else{ // если нет игрока в рейтинге то создаем ему
mysql_query("INSERT INTO job_pvp SET `usr` = '$log', `kill`='1', `data`='no'"); // создаем таблицу юзеру
}}
//-----------------------------------------------------------------------------



// проигравший
$nhp=$pk['hp']-$uron;
$nloses=$pk['loses']+1;
mysql_query("UPDATE users SET hp = '$nhp',loses = '$nloses' WHERE `id` = '$id'");

// выгравший
$aren=$udata['arenawins']+1;
$nwin=$udata['wins']+1;

mysql_query("UPDATE users SET wins = '$nwin',arenawins = '$aren'  WHERE `usr` = '$log'");
mysql_query("INSERT INTO logar SET usr='$pk[usr]',text='Вас убил  $udata[usr]! Урон -$uron!',kto='enemy',place='battle',timer='$timer',new='1'");
mysql_query("INSERT INTO logar SET usr='$udata[usr]',text='Вы убили  $pk[usr]! Урон -$uron!',kto='user',place='battle',timer='$timer',new='1'");

header ('Location: arena.php?');exit;

}



}else{
echo'Ошибка!';include($path.'inc/down.php');exit;}
}else
{
echo 'Ошибка!Вы не авторизованы!<a href="index.php"> <br/><br/>Авторизуйтесь</a>';require_once 'inc/end.php';exit;
}
?>