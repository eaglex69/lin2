<?php
/**
 * Шаблон для доставки бонуса за голосование
 *
 * @param int $voteId ID голоса
 * @param int $voteDate Дата голоса в формате unixtime
 * @param string $voteIp IP голоса
 * @param string $voteChar Имя чара (не null)
 * @param int $sms Голосование через SMS (1 или 0)
 * 
 * @return bool Удача/Неудача 
 * В случае удачи статус голоса устанавливается как 
 * VGR_CORE_INT_VOTE_STATUS_BONUS_RECEIVED 
 * и в следующий раз бонус за этот голос не зачисляется, 
 * в случае неудачи статус голоса не меняется, 
 * и робот попытается доставить бонус во время следующего запуска
 * 
 * Все строковые параметры в кодировке utf-8 БЕЗ mysql экранирования
 * 
 * Для логирования можно использовать функцию vgrInt_printMessage,
 * смотри описание в common.php 
 * 
 */
function vgrInt_deliverBonus($voteId, $voteDate, $voteIp, $voteChar, $sms)
{
	$voteId = intval($voteId);
	$voteDate = intval($voteDate);
	$sms = intval($sms);
	/*** Здесь нужно реализовать логику по доставке бонуса*/


$req = mysql_query("SELECT * FROM `users` WHERE `usr`='$voteChar'  LIMIT 1");
////////////////////////////
$avto=mysql_num_rows($req);
if($avto==1){
$rs = mysql_fetch_array($req);
$rs['votecoin']=$rs['votecoin']+5;
/////////////////
mysql_query("UPDATE `users` SET `votecoin`='".$rs['votecoin']."' WHERE `usr`='$voteChar'");
$time = date("H:i d.m.y");
mysql_query("INSERT INTO `msg_r` SET `user_from` = 'KraToS', `user_to` = '$voteChar', `time` = '$time', `read` = 1, `mail_msg` = 'Zachisleno 5 VoteCoin za golosovanie.'");
mysql_query("INSERT INTO
        `opros` SET
        `sms` = '$sms',
        `char` = '$voteChar',
        `voteIp` = '$voteIp',
        `date` = '$voteDate',
        `voteId` = '$voteId'
        "); // записываем в топ голосований
}
	
	/* ----------------------      ---------------------   */
	return true;
}


/**
 * Вызывается только один раз в начале работы робота
 * Открывает sql соединение с БД игрового сервера 
 *
 */
function vgrInt_deliverBonusInit()
{
	try
	{
		vgrInt_openGameSQLConnection();
	}
	catch (Exception $e)
	{
		vgrInt_printMessage(null, VGR_INT_MESSAGE_WARN, $e->getMessage());
	}
}

/**
 * Вызывается только один раз при окончании работы робота
 * Закрывает sql соединение с БД игрового сервера 
 *
 */
function vgrInt_deliverBonusDeinit()
{
	vgrInt_closeGameSQLConnection();
}

?>