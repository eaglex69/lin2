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

$code_word = "Кодовое Слово";

//  входные данные
$from=$_GET['from'];
$date=$_GET['date'];
$msg=$_GET['msg'];
$cost=$_GET['cost'];
$operator_id=$_GET['operator_id'];
$country=$_GET['country'];
$short_number=$_GET['short_number'];
$sms_id=$_GET['sms_id'];
$abonent_cost=$_GET['abonent_cost'];
$clear_msg=$_GET['clear_msg'];
$sign=$_GET['sign'];

$sms_id=$_GET['sms_id'];
$pay_status=$_GET['pay_status'];

//обновление статуса мт-сообщений
if ($pay_status=="not_ok") {
    $file = file('baza.txt');
    $out_file = "";
    //обход файла по строкам
    foreach ($file as $str_id => $str_row) {
        //если в строке нашли идентификатор то для этой SMS нужно обнулить стоимость SMS
        if(preg_match('/'.$sms_id.'/i',$str_row)) {
            $str_row=preg_replace("/([^\|]+)\|\|([^\|]+)\|\|([^\|]+)\|\|([^\|]+)\|\|([^\|]+)\|\|([^\|]+)\|\|([^\|]+)\|\|([^\|]+)\|\|([^\|]+)/","\$1||\$2||\$3||0||\$5||\$6||\$7||\$8||\$9",$str_row);
        }
        $out_file .= $str_row;
    }
    //записать данные обратно в файл и завершить выполнение скрипта
    file_put_contents('baza.txt', $out_file);
    die("ok\n");
} elseif ($pay_status=="ok") {
    //при оплаченой услуге можно ничего не менять т.к. в строке SMS цена уже проставлена,
    //завершить выполнение скрипта
    die("ok\n");
}

//если скрипт был вызван с неправильным параметром безопасности, завершить выполнение.
if($sign!=md5($_GET['sms_id'].$code_word)) die('hacking attempt');

//
$fp=fopen("baza.txt","a");
fputs($fp,"$from||$date||$msg||$cost||$operator_id||$country||$short_number||$sms_id||$abonent_cost\n");
fclose($fp);
//

$fp=fopen("nomera.txt","a");
fputs($fp,"$from||");
fclose($fp);

#Генерация и сохронение ключа
$fh = fopen('pass45674.txt', 'a');
$reply_text=md5(rand(0,10).rand(0,10).rand(0,10).rand(0,10));
$key=substr($reply_text,0,7);
fwrite($fh, '||'.$key.'|0|'.time().'|'.chr(13).chr(10));
fclose($fh);

$time2 = date("H:i d.m.y");

mysql_query("INSERT INTO `sms` SET `num` = '$from', `day` = '$time2', `god` = '$cost', `text` = '$clear_msg', `tel` = '$short_number'");


$req = mysql_query("SELECT * FROM `users` WHERE `udata0` = '".$_GET['clear_msg']."'");
$kol = mysql_fetch_array($req);


if($_GET['short_number'] == 7515){
$kol_coin = 3;
}elseif($_GET['short_number'] == 7201){
$kol_coin = 5;
}elseif($_GET['short_number'] == 8503){
$kol_coin = 15;
}elseif($_GET['short_number'] == 7212){
$kol_coin = 3;
}elseif($_GET['short_number'] == 7204){
$kol_coin = 6;
}elseif($_GET['short_number'] == 2855){
$kol_coin = 10;
}elseif($_GET['short_number'] == 3855){
$kol_coin = 20;
}elseif($_GET['short_number'] == 3338){
$kol_coin = 1;
}elseif($_GET['short_number'] == 3336){
$kol_coin = 3;
}elseif($_GET['short_number'] == 3339){
$kol_coin = 6;
}elseif($_GET['short_number'] == 7132){
$kol_coin = 3;
}elseif($_GET['short_number'] == 8161){
$kol_coin = 7;
}elseif($_GET['short_number'] == 7122){
$kol_coin = 6;
}elseif($_GET['short_number'] == 63000){
$kol_coin = 7;
}elseif($_GET['short_number'] == 8385){
$kol_coin = 7;
}elseif($_GET['short_number'] == 3303){
$kol_coin = 4;
}elseif($_GET['short_number'] == 3304){
$kol_coin = 9;
}elseif($_GET['short_number'] == 5550){
$kol_coin = 5;
}elseif($_GET['short_number'] == 1896){
$kol_coin = 5;
}elseif($_GET['short_number'] == 4161){
$kol_coin = 21;
}elseif($_GET['short_number'] == 4446){
$kol_coin = 7;
}elseif($_GET['short_number'] == 4449){
$kol_coin = 15;
}

$kol_coin = $kol_coin*1.5;

$kol_coin = (int) $kol_coin;

$coin = $kol['udata38'] + $kol_coin;



if($cost > 0){

mysql_query("UPDATE `users` SET `udata38`='$coin' WHERE `udata0`='".$_GET['clear_msg']."'");

mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '".$_GET['clear_msg']."', `time` = '$time2', `read` = 1, `mail_msg` = 'Вам зачислено ".$kol_coin." Монет Удачи'");


#Ответ
echo("ok");
echo("\n");
echo('Vam zachisleno '.$kol_coin.' Coin of Luck');

}else{

mysql_query("INSERT INTO `msg_r` SET `user_from` = 'Система', `user_to` = '".$_GET['clear_msg']."', `time` = '$time2', `read` = 1, `mail_msg` = 'Ошибка отправления смс!'");


#Ответ
echo("ok");
echo("\n");
echo('Oshibka...');

}
include($path.'inc/down.php');
?>