<?php
defined('PROTECTOR') or die('Error: restricted access');

$path2='';
//include($path2.'inc/gzips.php');
////////////////////////////////////////////////////////
//ini_set(‘zlib.output_compression’, ‘On’);
//ini_set(‘zlib.output_compression_level’, ’1′);

$req = mysql_query("SELECT * FROM `mesto` WHERE `usr` = '$log' LIMIT 1");
////////////////////////////
$mestouser = mysql_fetch_assoc($req);
///////////////////
if(empty($header)){
///////
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-type: text/html; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
echo "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">';
echo "\n" . "<head><meta http-equiv='content-type' content='application/xhtml+xml; charset=utf-8'/>
<meta name='google-site-verification' content='SLDULLi_vnVsXEtqnTJY2D_WJLeyyIEpEv3ij-WF-OY' />
<meta name='google-site-verification' content='bp9g83Rt2lovVNjvxSP27NrFKSPOftVsi_FRmkyjuWQ' />
<meta name='yandex-verification' content='53feb7d73bbd3a08' />
";
echo "\n" . '
<meta name="keywords" content="
l2war.mobi,l2war.mobi,онлайн,игра,lineage,wap,бесплатно,играть,линейдж 2 онлайн,l2 ОНЛАЙН,битвы,атака,бои,осады,la2,warriors,mmorpg,l2,www.l2war.mobi,онлайн lineage 2,l2 jykfqy,wap.l2war.mobi,онлайн игра,www.l2war.mobi,грэн каин,wap.l2war.mobi,buhf,jykfqy,,tcgkfnyj,jcfls,buhfnm,Lineage,lineage,новинка,2013,хит игра
" />
<meta name="description" content="
Miniage - Один мир созданный с максимально реалистичном оформлением , создана игровая история, в которую каждый из вас найдет своё место , поставить свою цель стать одним из лучших .. борьба за дворянство , в которую побеждает лишь один , и он становиться дворянином ,и владетелям смертельной короной , битвы на осадах в которых каждый до последнего вздоха стоит перед своим замком , или же с всех силах идет захватывать , мир в котором ты найдешь не только лишь классный игровой процесс , но и верных друзей с которыми будешь сражаться , общаться или же соревноваться ,Осады , Замки , Дворянство , Уникальные снаряжения в магазинах , ваши уже знакомые классы персонажей из Lineage , умения , звания , рейтинги и много другое ты найдешь у нас ... Miniage новая эпоха линеек !
" /><title>'.$log.' | miniage </title>';


/*опции настройка тем. Если есть, то выбранную включаем, нету, стандарт*/

$user_opt = mysql_fetch_array(mysql_query("SELECT theme FROM `options` where `usr`='$log' LIMIT 1"));
if (empty($user_opt['theme'])){$style = 'osn';}else{$style = $user_opt['theme'];}
echo "\n" . '<link rel="stylesheet" href="/theme/'.$style.'.css" type="text/css" />';
echo "\n" . '<link rel="stylesheet" href="/theme/osn.css" type="text/css" />';
/*------------------------------------------------------------------------------------------------------*/




echo "\n" . '</head><body>
<script src="theme/fon.gif"></script>';
echo '

<td valign="top" width="100%">

<table width="100%" cellpadding="0" 
<tr>
<td class="lt1"></td>
<td height="48">
 <table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
 <td class="t1">&nbsp;</td>
 <td height="48" width="232" align="center" valign="middle" class="tc"><div><font color=#af9878><b>miniage <b>ru</b></div>
</td>
 <td class="t1">&nbsp;</td>
 </tr>
 </table>
</td>
<td class="rt1">&nbsp;</td>
<tr>
<td width="19" class="l">&nbsp;</td>
<td class="centertd">';
///////////////////
}
///получаем положение
?>