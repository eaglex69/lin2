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
/* fantasy theme: theme/fantasy.css goes LAST (it overrides osn.css), ajax.js = page changes without reloads,
   battle.js = animated battle screen.  The old look is in git history (commit 7c23752: this file, zag.php, nogi.php,
   foot_pic.php, foot_inf.php, chat2.php, battle.php, pk.php, combat.php, fight.php). */
include_once dirname(__FILE__) . '/lin2_ui.php';
echo "\n" . '<meta name="viewport" content="width=device-width, initial-scale=1" />';
echo "\n" . '<link rel="stylesheet" href="' . lin2_ui_asset('fantasy.css') . '" type="text/css" />';
echo "\n" . '<script type="text/javascript" src="' . lin2_ui_asset('ajax.js') . '" defer="defer"></script>';
echo "\n" . '<script type="text/javascript" src="' . lin2_ui_asset('battle.js') . '" defer="defer"></script>';
echo "\n" . '<script type="text/javascript" src="' . lin2_ui_asset('blocks.js') . '" defer="defer"></script>';
/*------------------------------------------------------------------------------------------------------*/




echo "\n" . '</head><body>';
/* frame of every page: dark window, copper title bar with the title of the page, one table cell for the content.
   The title is the page's own $textl ('Магазин', 'Инвентарь' ...); «miniage ru» when a page has none.
   The small square cross (top right) leads to the main menu; the menu itself and the guest pages do not need it.
   The single-cell table is deliberate: the old pages print stray </div> tags and a table cell keeps them from
   closing the frame. inc/nogi.php closes it. */
$lx_title = isset($textl) ? trim((string)$textl) : '';
if ($lx_title === '') {
    $lx_ribbon = 'miniage <i>ru</i>';
    $lx_size = '';
} else {
    $lx_ribbon = htmlspecialchars($lx_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $lx_len = function_exists('mb_strlen') ? mb_strlen($lx_title, 'UTF-8') : strlen($lx_title);
    $lx_size = $lx_len > 22 ? ' xs' : ($lx_len > 14 ? ' s' : '');
}
$lx_script = isset($_SERVER['SCRIPT_NAME']) ? basename((string)$_SERVER['SCRIPT_NAME']) : '';
$lx_close = (!empty($user_id) && $lx_script !== 'index.php' && $lx_script !== 'main.php')
    ? '<a class="lx-x" href="/?" title="В меню"></a>' : '';
echo '
<div class="lx-app" id="lx-app">
<div class="lx-banner-wrap"><div class="lx-banner' . $lx_size . '"><b>' . $lx_ribbon . '</b></div></div>' . $lx_close . '
<table class="lx-frame" cellspacing="0" cellpadding="0"><tr><td class="centertd">';
///////////////////
}
///получаем положение
?>