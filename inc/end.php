<?php
defined('PROTECTOR') or die('Error: restricted access');
////////////////////////////////////////////////////////////
// Выводим информацию внизу страницы                      //
////////////////////////////////////////////////////////////

//////////////		реклама		///////////////////////////
include($path.'inc/nogi.php'); 
include($path.'inc/foot_inf.php');

echo'</body></html>';


	mysql_close($connect);

if($log == 'Talean'){
		echo '<div align="left"><b>Статистика производительности</b><br/>';
		echo 'Потребление ОЗУ: ' . formatsize(memory_get_usage()) . '<br/>';
        $loadavg_array = explode(" ", exec("cat /proc/loadavg"));
		$loadavg = $loadavg_array[2];
        if($loadavg>0.5){
            echo'Средняя загрузка (за минуту): <font style="color:red"><b>'.($loadavg*100).'%</b></font><br />';
        }else{
		    echo 'Средняя загрузка (за минуту): <font style="color:green"><b>'.($loadavg*100).'%</b></font><br />';
        }
		echo 'Загрузка страницы: '.round(microtime(1) - $starttime, 5) . ' сек.<br/>';
		echo 'Всего запросов: ' . $mysql->query_num . '<br />';
		echo 'Время запрОсов: ' . round ( $mysql->MySQL_time_taken, 5 ) . '<br/>';
		$total_time_query = $mysql->query_list;
		$time_query="";
		if(is_array($total_time_query)){
			$time_query .="<br>";
			for($i=0;$i<count($total_time_query);$i++){ 
				$time_query .= "".$total_time_query[$i]['time'] > 0.01."" ? "<font style=\"color:red\"><b>".round($total_time_query[$i]['time'],5)."</b></font> сек. - [".$total_time_query[$i]['query']."]<br><br>" : "<font style=\"color:green\"><b>".round($total_time_query[$i]['time'],5)."</b></font> сек. - [".$total_time_query[$i]['query']."]<br><br>";
			}
			$time_query .="</div>";
		}
		echo $time_query;
		echo"<br>".variables()."</div>"; }


exit;
ob_end_flush();
?>