<?php
/**
 * Данный  робот  считывает данные о голосах из базы данных, полученные роботом votes_bot.php, 
 * и доставляет бонус за голосования. 
 * Для настройки робота откройте файл config.php и установите следующие параметры:
 * 1. VGR_INT_GAME_ID: Идентификатор игры в системе vigre.su. 
 * Целое число, смотри в личном кабинете на сайте vigre.su.
 * 2. VGR_INT_SEC_KEY: Секретный ключ игры, смотри в личном кабинете на сайте vigre.su.
 * 3. VGR_INT_VOTES_SQL_SERVER_HOST: IP адрес mysql сервера для хранения голосов.
 * 4. VGR_INT_VOTES_SQL_SERVER_DB: База данных для хранения голосов 
 * 5. VGR_INT_VOTES_SQL_SERVER_USER: Имя пользователя для доступа к базе голосов. 
 * Должен иметь права на выполнение select, insert, update, create table.
 * 6. VGR_INT_VOTES_SQL_SERVER_PASS: Пароль для доступа к базе голосов.
 * 
 * Если при доставке бонус необходимо открывать соединение с базой данных игрового сервера, 
 * то настроить дополнительные параметры:
 * 7. VGR_INT_GAME_SQL_SERVER_HOST: IP адрес mysql сервера игры.
 * 8. VGR_INT_GAME_SQL_SERVER_DB: База сервера игры.
 * 9. VGR_INT_GAME_SQL_SERVER_USER: Имя пользователя для доступа к базе игры.
 * 10.VGR_INT_GAME_SQL_SERVER_PASS: Пароль для доступа к базе игры.
 * Параметры 1-6 устанавливаются одни и те же для роботов votes_bot.php и bonus_bot.php. 
 * Параметры 7-10 для работы робота votes_bot.php не нужны.
 * После настройки параметров в файле config.php необходимо открыть файл bonus.php 
 * и реализовать логику доставки бонуса в функции vgrInt_deliverBonus.
 * 
 * Внимание! Один и тот же персонаж может получать бонус не раньше, чем раз в сутки! 
 * Бонус за дополнительные голоса для персонажа начислен не будет! 
 * Если в данных голосования нет имени персонажа, то бонус для такого голоса не начисляется
 * Для запуска робота предлагается использовать cron (Linux) или планировщик задач (Windows). 
 * 2 и более робота bonus_bot.php одновременно работать не могут. 
 * Робот votes_bot.php может одновременно работать с роботом bonus_bot.php.
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('common.php');
require_once('config.php');
require_once('bonus.php');

/**
 * Вызываемая функция робота
 *
 */
function vgrInt_Main()
{
	global $vgrInt_SQLLink;
	header("Content-type: text/plain;charset=utf-8");
	define('TAG', 'Робот получения бонусов за голосования');
	$botType = VGR_CORE_INT_BOT_TYPE_BONUS;
	$botType = intval($botType);
	vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, 'Начало работы');
	try
	{
		try
		{
			vgrInt_openVotesSQLConnection();
		}
		catch (Exception $e)
		{
			vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, $e->getMessage());
			vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, 'Окончание работы');
			return;
		}
		vgrInt_createSQLTables();
		vgrInt_rawMain();
	}
	catch (Exception $e)
	{
		vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, $e->getMessage());
	}
	$query = "update vigre_votes_bot set running = 0, last_run=now() where type=$botType";
	mysql_query($query, $vgrInt_SQLLink);
	try
	{
		vgrInt_deliverBonusDeinit();
	}
	catch (Exception $e)
	{
		vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, "vgrInt_deliverBonusDeinit call error: ".$e->getMessage());
	}
	vgrInt_closeVotesSQLConnection();
	vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, 'Окончание работы');
}

function vgrInt_rawMain()
{
	global $vgrInt_SQLLink;
	$botType = VGR_CORE_INT_BOT_TYPE_BONUS;
	$botType = intval($botType);
	$query = "select running from vigre_votes_bot where type=$botType";
	$res = mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
	$numRows = mysql_num_rows($res);
	switch ($numRows)
	{
		case 0:
			{
				$query = "insert into vigre_votes_bot (running, last_run, type) values (1, now(), $botType)";
				mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
				vgrInt_processVotes();
				break;
			}
		case 1:
			{
				$row = mysql_fetch_array($res);
				$running = $row['running'];
				if($running)
				{
					vgrInt_printMessage(TAG, VGR_INT_MESSAGE_ERROR, 'Предыдущий робот еще работает');
					return;
				}
				else
				{
					vgrInt_processVotes();
				}
				break;
			}
		default:
			{
				vgrInt_printMessage(TAG, VGR_INT_MESSAGE_ERROR, 'Неверное число записей в таблице vigre_votes_bot');
				return;
			}
	}
}


function vgrInt_processVotes()
{
	vgrInt_deliverBonusInit();
	global $vgrInt_SQLLink;
	$query = "select
	vigre_votes.sms,
	vigre_bonus.bonus_date,
	(DAYOFMONTH(vigre_bonus.bonus_date) <> DAYOFMONTH(now())
	and vigre_bonus.bonus_date < now())  as bonus_enabled,
	vigre_votes.id, 
	vigre_votes.date, 
	vigre_votes.charname, 
	vigre_votes.ip 
	from vigre_votes left join vigre_bonus on (vigre_bonus.charname=vigre_votes.charname)
	where vigre_votes.status is null";
	$res = mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
	$numRows = mysql_numrows($res);
	vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, "Получено $numRows необработанных голосов");
	$characters = array();
	//Список пользователей, получивших бонус
	$charactersBonus = array();
	while(($row = mysql_fetch_array($res)) != false)
	{
		$sms = $row['sms'];
		$sms = intval($sms);
		$id = $row['id'];
		$id = intval($id);
		$date = $row['date'];
		$dateString = $date;
		$character = $row['charname'];
		$ip = $row['ip'];
		$bonusDate = $row['bonus_date'];
		$bonusEnabled = $row['bonus_enabled'];
		$info = "[$id, $dateString, $ip, $character]";

		$setVoteStatus = true;
		if($character == null)
		{
			vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, "Персонаж не указан. Бонус не будет получен для голоса $info");
		}
		else
		{
			if(
				$bonusDate != null &&
				!$bonusEnabled &&
				!in_array($character, $charactersBonus) && 
				!$sms
				)
			{
				array_push($charactersBonus, $character);
			}
			if(in_array($character, $charactersBonus) && !$sms)
			{
				vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, "Персонаж $character уже получил бонус сегодня ($bonusDate). Бонус доставлен не будет. Голос: $info");
			}
			else
			{
				if(vgrInt_deliverBonus($id, $date, $ip, $character, $sms))
				{
					if(!$sms)
					{
						array_push($charactersBonus, $character);
					}
					vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, "Бонус для голоса $info доставлен");
					$characterSQL = mysql_real_escape_string($character, $vgrInt_SQLLink);
					if(!$sms)
					{
						if($bonusDate == null)
						{
							if(in_array($character, $characters))
							{
								$query = "update vigre_bonus set bonus_date=now() where charname='$characterSQL'";
							}
							else
							{
								array_push($characters, $character);
								$query = "insert into vigre_bonus (charname, bonus_date) values('$characterSQL', now())";
							}
						}
						else
						{
							$query = "update vigre_bonus set bonus_date=now() where charname='$characterSQL'";
						}
						mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
					}
				}
				else
				{
					$setVoteStatus = false;
					vgrInt_printMessage(TAG, VGR_INT_MESSAGE_ERROR, "Ошибка при доставке бонуса для голоса $info");
				}
			}
		}
		if($setVoteStatus)
		{
			$status = VGR_CORE_INT_VOTE_STATUS_BONUS_RECEIVED;
			$status = intval($status);
			$query = "update vigre_votes set status=$status where id=$id";
			mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
		}
	}
}

/**
 * Вызываем робота
 */
vgrInt_Main();
?>