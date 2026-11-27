<?php
/**
 * Данный  робот отсылает http запрос на сервер vigre.su получает список проголосовавших за игру 
 * и пишет полученные данные в базу данных. 
 * Для настройки робота откройте файл config.php и установите следующие параметры:
 * 1. VGR_INT_GAME_ID: Идентификатор игры в системе vigre.su. 
 * Целое число, смотри в личном кабинете на сайте vigre.su
 * 2. VGR_INT_SEC_KEY: Секретный ключ игры, смотри в личном кабинете на сайте vigre.su
 * 3. VGR_INT_VOTES_SQL_SERVER_HOST: IP адрес mysql сервера для хранения голосов 
 * 4. VGR_INT_VOTES_SQL_SERVER_DB: База данных для хранения голосов 
 * 5. VGR_INT_VOTES_SQL_SERVER_USER: Имя пользователя для доступа к базе голосов. 
 * Должен иметь права на выполнение select, insert, update, create table.
 * 6. VGR_INT_VOTES_SQL_SERVER_PASS: Пароль для доступа к базе голосов
 * 
 * Все данные о голосовании сохраняются на mysql сервере в базе данных в кодировке UTF-8. 
 * Запуск робота возможен не чаще, чем один раз в 5 минут. 
 * Для запуска робота предлагается использовать cron (Linux) или планировщик задач (Windows). 
 * 2 и более робота votes_bot.php одновременно работать не могут. 
 * Робот votes_bot.php может одновременно работать с роботом bonus_bot.php.
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('common.php');
require_once('config.php');

/**
 * Вызываемая функция робота
 *
 */
function vgrInt_Main()
{
	global $vgrInt_SQLLink;
	header("Content-type: text/plain;charset=utf-8"); //задел iNoY_GM
	define('TAG', 'Робот получения голосов');
	$botType = VGR_CORE_INT_BOT_TYPE_VOTES;
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
	vgrInt_closeVotesSQLConnection();
	vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, 'Окончание работы');
}

function vgrInt_rawMain()
{
	global $vgrInt_SQLLink;
	$botType = VGR_CORE_INT_BOT_TYPE_VOTES;
	$botType = intval($botType);
	$query = "select
	running, 
	date_add(last_run, INTERVAL 5 MINUTE) < now()  as can_run_bot 
	from vigre_votes_bot where type=$botType";
	$res = mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
	$numRows = mysql_num_rows($res);
	switch ($numRows)
	{
		case 0:
			{
				$query = "insert into vigre_votes_bot (running, last_run, type) values (1, now(), $botType)";
				mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
				vgrInt_getVotes();
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
					$canRunBot = $row['can_run_bot'];
					if($canRunBot)
					{
						vgrInt_getVotes();
					}
					else
					{
						vgrInt_printMessage(TAG, VGR_INT_MESSAGE_ERROR, 'Не прошло достаточно времени с предыдущего запуска робота');
						return;
					}
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


function vgrInt_getVotes()
{
	global $vgrInt_SQLLink;
	$gameId = VGR_INT_GAME_ID;
	$url = "http://vigre.su/xml/index.php?command=votes&game_id=$gameId&vote_id=";
	$sign = $gameId;
	$query = "select max(id) as id from vigre_votes";
	$res = mysql_query($query, $vgrInt_SQLLink) or vgrInt_throwMySQLError();
	$row = mysql_fetch_array($res);
	$maxId = $row['id'];
	$maxId = intval($maxId);
	$url .= $maxId;
	$sign .= $maxId;
	$sign .= VGR_INT_SEC_KEY;
	$sign = sha1($sign);
	$url .= "&sign=$sign";
	vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, "Отправка запроса: $url");
	$content = vgrInt_getFileContentByURL($url);
	if($content === FALSE)
	{
		vgrInt_printMessage(TAG, VGR_INT_MESSAGE_ERROR, 'Не удалось получить данные с сервера vigre.su');
	}
	else
	{
		$vals = null;
		$index = null;
		$p = xml_parser_create();
		xml_parse_into_struct($p, $content, $vals, $index);
		if (PHP_VERSION_ID < 80000) { xml_parser_free($p); } // PHP 8: the parser is an object, freed automatically (the function is deprecated in 8.5)
		$votes = array();
		$errorObject = array();
		$voteData = null;
		$xmlId = null;
		foreach ($vals as $value)
		{
			$array = $value;
			$tagName = $array['tag'];
			$tagName = strtolower($tagName);
			switch($tagName)
			{
				case "error":
					{
						$errorObject[$tagName] = $array['value'];
						break;
					}
				case "comment":
					{
						$errorObject[$tagName] = $array['value'];
						break;
					}
				case "vote":
					{
						if($voteData != null)
						{
							$votes[$xmlId] = $voteData;
						}
						$voteData = array();
						break;
					}
				case "sms":
					{
						$sms = $array['value'];
						$sms = intval($sms);
						$voteData[$tagName] = $sms;
						break;
					}
				case "id":
					{
						$xmlId = $array['value'];
						$xmlId = intval($xmlId);
						$voteData[$tagName] = $xmlId;
						break;
					}
				case "date":
					{
						$voteData[$tagName] = $array['value'];
						break;
					}
				case "ip":
					{
						$voteData[$tagName] = $array['value'];
						break;
					}
				case "char":
					{
						$xmlChar = null;
						$x = 'value';
						if(array_key_exists($x, $array))
						{
							$xmlChar = $array[$x];
						}
						$voteData['char'] = $xmlChar;
						break;
					}
				default:
					{
						break;
					}
			}
		}
		$x = "error";
		if(array_key_exists($x, $errorObject))
		{
			$errorCode = $errorObject[$x];
			switch ($errorCode)
			{
				case VGR_CORE_XML_API_ERROR_SUCCESS:
					{
						$count = count($votes);
						vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, "Получено $count голосов");
						$processedCount = 0;
						foreach ($votes as $value)
						{
							$sms  = $value['sms'];
							$sms = intval($sms);					
							$id  = $value['id'];
							$id = intval($id);
							$date  = $value['date'];
							$date = intval($date);
							$dateString = date("d.m.y H:i:s", $date);
							$character  = $value['char'];
							$characterSQL = mysql_real_escape_string($character, $vgrInt_SQLLink);
							$ip  = $value['ip'];
							$info = "$id $dateString $ip $character";
							vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, $info);
							$ipSQL = mysql_real_escape_string($ip, $vgrInt_SQLLink);
							$query = "insert into vigre_votes (id, date, charname, ip, sms)
							values($id, FROM_UNIXTIME($date), '$characterSQL', '$ipSQL', $sms)";
							if(mysql_query($query, $vgrInt_SQLLink))
							{
								$processedCount++;
							}
							else
							{
								vgrInt_printMessage(TAG, VGR_INT_MESSAGE_ERROR, "Голосование с ID $id уже есть в БД");
							}
						}
						vgrInt_printMessage(TAG, VGR_INT_MESSAGE_INFO, "Добавленно $processedCount голосов");
						break;
					}
				default:
					{
						$x = "comment";
						$comment = null;
						if(array_key_exists($x, $errorObject))
						{
							$comment = $errorObject[$x];
						}
						vgrInt_printMessage(TAG, VGR_INT_MESSAGE_ERROR, "Cервер vigre.su вернул код ошибки $errorCode, описание ошибки: $comment");
						break;
					}
			}
		}
		else
		{
			vgrInt_printMessage(TAG, VGR_INT_MESSAGE_ERROR, 'Неверный формат ответа от сервера vigre.su');
		}
	}
}

/**
 * Вызов робота
 */
vgrInt_Main();
?>