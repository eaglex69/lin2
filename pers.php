<?php
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

// ------------------------------------------------------------------------------------------------------------------
// Страница персонажа (оформление: theme/fantasy.css, помощники: inc/lin2_ui.php).
//   pers.php            - имя, «кукла» (надетые вещи по бокам, образ в середине), под ней характеристики и сведения в блоках
//   pers.php?act=shmot  - снаряжение: что надето и кнопки «Снять»
//   pers.php?act=mag    - умения
// Очки навыков тратятся так же, как раньше: ссылки pers.php?hp|mp|pdef|mdef|patt|matt и форма pers.php?ac=skill.
// ------------------------------------------------------------------------------------------------------------------

/** вкладки страницы персонажа ($cur = 'info' | 'mag' | 'shmot': эта вкладка без ссылки) */
function pers_tabs($cur)
{
    $t = array('info' => array('Инфо', 'pers.php?'), 'mag' => array('Умения', 'pers.php?act=mag'), 'shmot' => array('Снаряжение', 'pers.php?act=shmot'));
    $items = array();
    foreach ($t as $k => $v) {
        $items[] = array($v[0], $k === $cur ? null : $v[1]);
    }
    $items[] = array('Настройки', 'opt.php?');
    return lin2_ui_tabs($items);
}

/** «5 мин 3 сек» / «2 ч. 5 мин. 3 сек.» / «3 Дн. 4 Ч.» */
function pers_dur($s)
{
    $s = (int)$s;
    if ($s < 60) {
        return $s . ' сек';
    }
    if ($s < 3600) {
        return floor($s / 60) . ' мин ' . ($s % 60) . ' сек';
    }
    if ($s < 86400) {
        return floor($s / 3600) . ' ч. ' . floor(($s % 3600) / 60) . ' мин. ' . ($s % 60) . ' сек.';
    }
    return floor($s / 86400) . ' Дн. ' . floor(($s % 86400) / 3600) . ' Ч.';
}

if(empty($_GET['act'])){

// ---- потратить активные навыки (как раньше: одна ссылка = одна характеристика, форма = характеристика и количество) ----
$ups = array(
    'pdef' => array('pdef', 3, 'Физ. защита'),
    'mdef' => array('mdef', 3, 'Маг. защита'),
    'patt' => array('patt', 3, 'Физ. атака'),
    'matt' => array('matt', 3, 'Маг. атака'),
    'hp' => array('hpall', 30, 'Жизни'),
    'mp' => array('mpall', 30, 'Мана'),
);
$col = 1;
if (!empty($_POST['col'])) {$col = $_POST['col'];}
$col = is_numeric($col) ? (int)$col : 0;
$ask = array();
foreach ($ups as $k => $u) {
    if (isset($_GET[$k]) or (isset($_POST['what']) and $_POST['what'] == $k)) {$ask[] = $k;}
}
$up_done = array();
$up_msg = '';
if (isset($_POST['what']) and $_POST['what'] == 'no') {$up_msg = 'Выберите, что повышать!';}
if ($col >= 1) {
    foreach ($ask as $k) {
        if ($udata['skill'] >= $col) {
            $nskill = $udata['skill'] - $col;
            $nparm = $udata[$ups[$k][0]] + $ups[$k][1] * $col;
            mysql_query("UPDATE users SET skill = '$nskill', ".$ups[$k][0]." = '$nparm' WHERE `usr` = '$log'");
            $udata['skill'] = $nskill;
            $udata[$ups[$k][0]] = $nparm;
            $up_done[$k] = $ups[$k][1] * $col;
        }
    }
}
if ($up_done) {
    // если что-то изменилось, читаем данные заново
    $reqi = mysql_query("SELECT * FROM `users` WHERE `usr`='$log'");
    $udata = mysql_fetch_array($reqi);
}

// ---- сведения ----
$lv = (int)$udata['lvl'];
if ($lv < 20) {$st = 'NG';}
elseif ($lv < 40) {$st = 'D';}
elseif ($lv < 52) {$st = 'C';}
elseif ($lv < 62) {$st = 'B';}
elseif ($lv < 76) {$st = 'A';}
else {$st = 'S';}
$ranks = array(0 => 'Бродяга', 1 => 'Вассал', 2 => 'Рыцарь', 3 => 'Маркиз');
$sta = isset($ranks[(int)$udata['st']]) ? $ranks[(int)$udata['st']] : '';
$races = array('ork' => 'Орк', 'elf' => 'Светлый Эльф', 'darkelf' => 'Тёмный Эльф', 'human' => 'Человек', 'gnom' => 'Гном', 'kamael' => 'Камаэль');
$race = isset($races[$udata['storona']]) ? $races[$udata['storona']] : '';
$kln = $udata['klas'] == 'wizard' ? 'Маг' : ($udata['klas'] == 'fighert' ? 'Воин' : '');
$skill = (int)$udata['skill'];

$clan = null;
if(!empty($udata['clan'])){
    $req = mysql_query("SELECT * FROM `clan` WHERE `lider` = '$udata[clan]' LIMIT 1");
    $clan = mysql_fetch_array($req);
}
$emblem = '';
if ($clan && !empty($clan['emblema'])) {
    $emblem = '<img src="/pic/clan/'.lin2_ui_h(rawurlencode($clan['emblema'])).'" alt=""/>';
}

// бонусы от бафов
$at_sum = 0;
$df_sum = 0;
$reqbf = mysql_query("SELECT * FROM `baf` WHERE `usr`='$log'");
while($mag = mysql_fetch_array($reqbf)) {
    $at_sum += (int)$mag['bafat'];
    $df_sum += (int)$mag['pmdef'];
}

// ---- имя, звание, уровень ----
echo '<div class="lx-who"><div class="c1"><span class="nm1">'.$emblem.lin2_ui_h($log).'</span><small>'.lin2_ui_h($sta);
if ((int)$udata['st'] < 3) {echo ' <a href="st.php">[+]</a>';}
echo '</small></div><div class="c2"><b>'.$lv.' ур.</b><small>'.lin2_ui_h(trim($race.' '.$kln)).'</small></div></div>';

// vip / анти-пк аккаунт
$reqvip = mysql_query("SELECT * FROM `vip` WHERE `usr` = '$log' LIMIT 1");
if (mysql_num_rows($reqvip) == 1) {
    $vip = mysql_fetch_array($reqvip);
    echo '<div class="lx-vip">Вип акаунт: <b>x'.lin2_ui_h($vip['tip']).'</b> · осталось '.lin2_ui_left($vip['time'] - time()).'</div>';
}
$reqvip = mysql_query("SELECT * FROM `anti_pk` WHERE `usr` = '$log' LIMIT 1");
if (mysql_num_rows($reqvip) == 1) {
    $vip = mysql_fetch_array($reqvip);
    echo '<div class="lx-vip bad">Анти пк акаунт · осталось '.lin2_ui_left($vip['time'] - time()).'</div>';
}
if (!empty($udata['status'])) {
    echo lin2_ui_note('<i>'.lin2_ui_h($udata['status']).'</i>');
}
if ($up_msg !== '') {echo lin2_ui_note('<b>'.lin2_ui_h($up_msg).'</b>');}
if ($up_done) {
    $t = array();
    foreach ($up_done as $k => $n) {$t[] = $ups[$k][2].' +'.$n;}
    echo lin2_ui_note('<b>Повышено:</b> '.lin2_ui_h(implode(', ', $t)));
}

echo pers_tabs('info');

// ---- кукла: образ в середине, надетые вещи по бокам ----
echo lin2_ui_doll(lin2_ui_worn($log), array(
    'img' => lin2_ui_user_img($udata, true),
    'rank' => 'Грейд '.$st,
    'take' => 'snyat.php?tip=',
    'bag' => 'inventar.php?mod=item&o=',
));

// ---- под образом: здоровье и характеристики ----
$exp_pct = isset($exp) ? max(0, min(100, (int)$exp)) : 0;
echo lin2_ui_panel('Здоровье',
    lin2_ui_meter('lx-hp', 'Жизни '.(int)$udata['hp'].' / '.(int)$udata['hpall'], $udata['hp'], $udata['hpall'], $skill > 0 ? 'pers.php?hp' : '', '+30')
    .lin2_ui_meter('lx-mp', 'Мана '.(int)$udata['mp'].' / '.(int)$udata['mpall'], $udata['mp'], $udata['mpall'], $skill > 0 ? 'pers.php?mp' : '', '+30')
    .lin2_ui_meter('lx-xp', 'Опыт '.$exp_pct.'%', $exp_pct, 100),
    'in');

echo lin2_ui_panel('Характеристики', lin2_ui_kv(array(
    array('att', 'Физ. атака', lin2_ui_num($udata['patt']), $at_sum > 0 ? '+'.number_format($at_sum, 0, ',', ' ') : '', $skill > 0 ? 'pers.php?patt' : '', '+3'),
    array('att', 'Маг. атака', lin2_ui_num($udata['matt']), $at_sum > 0 ? '+'.number_format($at_sum, 0, ',', ' ') : '', $skill > 0 ? 'pers.php?matt' : '', '+3'),
    array('def', 'Физ. защита', lin2_ui_num($udata['pdef']), $df_sum > 0 ? '+'.number_format($df_sum, 0, ',', ' ') : '', $skill > 0 ? 'pers.php?pdef' : '', '+3'),
    array('def', 'Маг. защита', lin2_ui_num($udata['mdef']), $df_sum > 0 ? '+'.number_format($df_sum, 0, ',', ' ') : '', $skill > 0 ? 'pers.php?mdef' : '', '+3'),
)), 'in kv', $skill > 0 ? 'Очков навыков: '.$skill : '');

if ($skill >= 5) {
    echo lin2_ui_panel('Повысить навык',
        '<form action="pers.php?ac=skill" method="post"><span class="lbl">Параметр:</span>'
        .'<select name="what"><option value="no">Выбрать</option><option value="pdef">P.Def (Физ.защ.)</option><option value="mdef">M.Def (Маг.защ.)</option>'
        .'<option value="patt">P.Att (Физ.атк.)</option><option value="matt">M.Att (Маг.атк.)</option><option value="hp">HP (Жизни)</option><option value="mp">MP (Мана)</option></select>'
        .'<span class="lbl">Количество:</span><input class="input" type="text" value="'.$skill.'" name="col"/>'
        .'<input class="button" type="submit" value="Повысить" /></form>', 'in');
}

// ---- кошелёк ----
echo lin2_ui_panel('Богатство', lin2_ui_kv(array(
    array('coin', 'Аден', lin2_ui_num($udata['money'])),
    array('bar', 'Gold Bar', lin2_ui_num($udata['gold'])),
    array('gem', 'Coin of Luck', lin2_ui_num($udata['almaz']), '', 'zaksms.php?', '+'),
    array('xp', 'Опыт', lin2_ui_num($udata['exp'])),
    array('st', 'Очки умений', lin2_ui_num($udata['sp'])),
)), 'in kv');

// ---- клан, питомец, профессия, титул ----
$rows = array();
if ($clan) {
    $rows[] = array('', 'Клан', '<a href="clan.php?id='.(int)$clan['id'].'">'.lin2_ui_h($clan['name']).'</a> · <a href="clanroom.php?go=clan">Выйти</a>');
}
$req = mysql_query("SELECT id,name FROM `pit` WHERE `usr` = '$log'");
if (mysql_num_rows($req) == 1) {
    $pit = mysql_fetch_array($req);
    $rows[] = array('', 'Питомец', '<a href="pit.php?id='.(int)$pit['id'].'">'.lin2_ui_h($pit['name']).'</a>');
}
$prof = ($udata['prof'] == '' or $udata['prof'] == 'no') ? 'Нет' : $udata['prof'];
$titul = ($udata['titul'] == '' or $udata['titul'] == 'no') ? 'Нет' : $udata['titul'];
$rows[] = array('', 'Профессия', lin2_ui_h($prof));
$rows[] = array('', 'Титул', lin2_ui_h($titul));
echo lin2_ui_panel('Сведения', lin2_ui_kv($rows), 'in kv');

// ---- бои ----
$rekc = mysql_num_rows(mysql_query("SELECT * FROM `rekc` WHERE `kogo` = '$log'"));
echo lin2_ui_panel('Бои', lin2_ui_kv(array(
    array('win', 'Побед на арене', lin2_ui_num($udata['arenawins'])),
    array('lose', 'Поражений на арене', lin2_ui_num($udata['arenaloses'])),
    array('win', 'Побед', lin2_ui_num($udata['wins'])),
    array('lose', 'Поражений', lin2_ui_num($udata['loses'])),
    array('', 'Рекомендации', '<a href="rekc.php?who">'.$rekc.'</a>'),
)), 'in kv');

// ---- время: на сайте и до бонуса ----
$bon = 36000 - (int)$udata['time_bon'];
$bon_text = $bon <= 0 ? 'Бонус зачислен!' : pers_dur($bon);
$bon_col = array(0 => 10, 1 => 30, 2 => 40, 3 => 50, 4 => 100, 5 => 150);
$bon_col34 = isset($bon_col[(int)$udata['dostup']]) ? $bon_col[(int)$udata['dostup']] : 10;
echo lin2_ui_panel('Время', lin2_ui_kv(array(
    array('', 'Провёл на сайте', pers_dur($udata['time'])),
    array('', 'Бонус +'.$bon_col34.' Coin Of Luck через', lin2_ui_h($bon_text)),
)), 'in kv');

// ---- подарки ----
$k_p = mysql_num_rows(mysql_query("SELECT * FROM `gifts` WHERE `id_user` = '$udata[id]'"));
$g = '';
if ($k_p == 0) {
    $g = '<span class="lx-note">Нет подарков</span>';
} else {
    $q = mysql_query("SELECT * FROM `gifts` WHERE `id_user` = '$udata[id]' ORDER BY time DESC LIMIT 4");
    $g .= '<div class="lx-gifts">';
    while ($f = mysql_fetch_array($q)) {
        $g .= '<img src="/gifts/'.(int)$f['id_gifts'].'.gif" alt="" class="icon"/>';
    }
    $g .= '</div><a class="lx-link" href="gifts_2.php?">Все подарки ('.$k_p.')</a>';
}
echo lin2_ui_panel('Подарки', $g, 'in');

// ---- ссылки ----
$go = array(array('pers.php?act=mag', 'Мои умения'));
$reqi222 = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log' and
`tip` = 'bafat' or `usr` = '$log' and
`tip` = 'bafkrit' or `usr` = '$log' and
`tip` = 'bafzash' or `usr` = '$log' and
`tip` = 'resp' or `usr` = '$log' and
`tip` = 'bafzat' or `usr` = '$log' and
`tip` = 'bafhp'");
if (mysql_num_rows($reqi222) >= 1) {$go[] = array('baf.php', 'Бафы');}
$go[] = array('opt.php?', 'Настройки');
$go[] = array('msg.php', 'Почта');
$go[] = array('anketa.php?go=go&nick='.rawurlencode($log), 'Анкета');
if ($udata['prof'] == 'Artisan' or $udata['prof'] == 'Warsmith' or $udata['prof'] == 'Maestro') {$go[] = array('workshop.php?', 'Книга рецептов');}
$go[] = array('kill_ob.php', 'Обмен Coin Of Luck за убийство');
if ($udata['dostup'] >= 4) {$go[] = array('adm_panel.php?mod=red&nick='.rawurlencode($log), 'Редакт');}
$go[] = array('/index.php?mod=exit', 'Выход');
echo lin2_ui_go($go);
echo lin2_ui_panel('Официальная группа', '<a href="http://vk.com/miniage">Вконтакте</a>', 'in');
}else
if($_GET['act']=='shmot'){
// ---- снаряжение: что надето ----
echo pers_tabs('shmot');
$worn = lin2_ui_worn($log);
echo lin2_ui_doll($worn, array('img' => lin2_ui_user_img($udata, true), 'rank' => 'Снаряжение', 'take' => 'snyat.php?tip=', 'bag' => 'inventar.php?mod=item&o='));
$any = false;
foreach (array('weapon', 'golova', 'body', 'pants', 'ruki', 'nogi', 'plash', 'shit', 'amulet', 'poyas', 'rpoyas', 'kolco', 'rkolco') as $t) {
    if (!isset($worn[$t])) {continue;}
    if (!$any) {echo lin2_ui_sub('Надето'); $any = true;}
    echo lin2_ui_item_row($worn[$t], 'inventar.php?mod=info&id='.(int)$worn[$t]['id'], array(array('snyat.php?tip='.$t, 'Снять', 'red')));
}
if (!$any) {echo lin2_ui_empty('Ничего не надето');}
echo lin2_ui_go(array(array('inventar.php?', 'Инвентарь'), array('pers.php?', 'Назад')));
}
elseif($_GET['act']=='mag'){
// ---- умения ----
echo pers_tabs('mag');
$mid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$activ = isset($_GET['activ']) ? $_GET['activ'] : '';
if ($activ == 'yes') {
    mysql_query("UPDATE `mag` SET `activ` = 'yes' WHERE usr = '$log' and id = '$mid'"); // скил активен
}
if ($activ == 'no') {
    mysql_query("UPDATE `mag` SET `activ` = 'no' WHERE usr = '$log' and id = '$mid'");  // скил отключен
}
if ($activ == 'del') {
    if (!empty($_POST['result'])) {
        $ressave = mysql_query("DELETE FROM `mag` WHERE usr = '$log' and id = '".(int)$_POST['result']."'"); // удаляем скил
        if ($ressave) {
            echo lin2_ui_note('<b>Умение удалено!</b>');
        } else {
            echo lin2_ui_note('<b>Ошибка доступа!</b>');
        }
    } else {
        echo lin2_ui_note('<b>Вы не выбрали умение!</b>');
    }
}
$kinds = array('me4' => 'Меч', 'kas' => 'Кастет', 'rap' => 'Рапиру', 'luk' => 'Лук', 'kin' => 'Кинжал');
$types = array('atack' => 'Атака', 'bafkrit' => 'Баф Критов', 'bafat' => 'Баф Атаки', 'bafzash' => 'Баф Защиты', 'bafzat' => 'Баф Защиты и Атаки',
    'hpot' => 'Отжор', 'hp' => 'Реген. HP', 'mp' => 'Реген. MP', 'bafmp' => 'Баф на ману', 'bafhp' => 'Баф на жизни', 'resp' => 'Респ. игрока',
    'spoil' => 'Спойлинг', 'spoilall' => 'Спойлинг всех вещей');
$req = mysql_query("SELECT * FROM `mag` WHERE `usr` = '$log'");
if(mysql_num_rows($req)>=1)
{
    echo '<form action="pers.php?act=mag&amp;activ=del" method="post">';
    while($mag = mysql_fetch_array($req))
    {
        $maglvl = (int)$mag['lvl'] - 1;
        $f = array();
        foreach (array('mp', 'hp', 'plushp', 'uron') as $k) {
            $a = explode("|", (string)$mag[$k]);
            $f[$k] = isset($a[$maglvl]) ? $a[$maglvl] : '';
        }
        $on = ($mag['activ'] == '' or $mag['activ'] == 'yes');
        $toggle = 'pers.php?act=mag&activ='.($on ? 'no' : 'yes').'&id='.(int)$mag['id'];
        $title = '<input type="radio" name="result" value="'.(int)$mag['id'].'"/><img class="lx-sk-i" src="/pic/skils/'.lin2_ui_h(rawurlencode($mag['name'])).'.jpg" alt=""/>'
            .'<a class="lx-sk '.($on ? 'on' : 'off').'" href="'.lin2_ui_h($toggle).'">'.lin2_ui_h($mag['name']).'</a>';
        $rows = array(
            array('', 'Уровень', lin2_ui_h($mag['lvl'])),
            array('', 'Класс', $mag['klas'] == 'wizard' ? 'Маг' : ($mag['klas'] == 'fighert' ? 'Воин' : '')),
            array('', 'Урон', lin2_ui_h($f['uron'])),
            array('', 'Забирает маны', lin2_ui_h($f['mp'])),
            array('', 'Забирает здоровья', lin2_ui_h($f['hp'])),
            array('', 'Даёт здоровья', lin2_ui_h($f['plushp'])),
            array('', 'Тип умения', lin2_ui_h(isset($types[$mag['tip']]) ? $types[$mag['tip']] : '')),
        );
        $body = lin2_ui_kv($rows);
        if ($mag['ruka'] != 'not' and $mag['ruka'] != '' and isset($kinds[$mag['ruka']])) {
            $body .= '<div class="lx-nt"><span class="lx-note">Для использования нужно держать в руках <b>'.lin2_ui_h($kinds[$mag['ruka']]).'</b></span></div>';
        }
        echo '<div class="lx-pn in kv"><div class="lx-pt lx-pts">'.$title.'</div>'.$body.'</div>';
    }
    echo '<input type="submit" name="ok" value="Удалить умение" />';
    echo '</form>';
    echo lin2_ui_note('Выберите умение из списка, если хотите удалить его. <b>Зелёное</b> имя - умение активно, <b>красное</b> - отключено. Нажатие на имя включает или отключает умение.');
}else{
    echo lin2_ui_empty('Нет умений!');
}
echo lin2_ui_go(array(array('pers.php?', 'Назад')));
}
else{
echo lin2_ui_note('Ошибка');
}
include($path.'inc/down.php');
?>
