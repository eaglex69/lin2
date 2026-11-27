<?
define('PROTECTOR', 1);

$headmod = 'coin_sms';//фикс. места

$textl='Покупка Coin of Luck';
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
include($path.'inc/smsbill.class.php');
include($path.'inc/settings_sms.php');

switch($_GET[mod]){
default:
//--------монеты покупка---------------

foreach ($money as $serv_id => $money_val){
	echo '<font color=#FF8C00>'.$money_val.'</font> Coin of Luck <a href="coin_sms.php?go=pay&servid='.$serv_id.'">Купить</a><br />';
}
break;
//************************************************************************************************
case 'pay':
	$serv = htmlspecialchars($_GET['servid']);
	$coin = $money[$serv];
	$smsbill = new SMSBill();
	$smsbill->setServiceId($serv);
	$smsbill->useEncoding($charset);
	$smsbill->useHeader('no');
	$smsbill->useLang($lang); 
	$smsbill->useCss('http://'.$_SERVER['HTTP_HOST']);
	if (isset($_REQUEST['smsbill_password'])) {
		if (!$smsbill->checkPassword($_REQUEST['smsbill_password'])) { 
			//пароль не верный
			echo"Пароль указан не верно.";
			include_once"inc/down.php";
			exit;
		}else{ 
			//пароль верный
			mysql_query("UPDATE `users` SET `udata38` =`udata38`+ '$coin' WHERE `udata0` = '$log'");
			file_put_contents("data/pay.dat", date('Y-m-d') .' / '. $log .' / '. $coin ."\r\n", FILE_APPEND);	
			echo  "Персонажу ". $log ." зачислено ". $coin ." Coin of Luck. Платеж осуществлен сервисом www.smsbill.com.ua";		
		}
	}else{
		//показать форму т.к. пароль не введен
		echo $smsbill->getForm();
		include_once"inc/down.php";
	}
break;
//************************************************************************************************
}


include_once"inc/down.php";

?>