<?php
define('PROTECTOR', 1);

$textl = 'Задания';
include('inc/path.php');
include($path . 'inc/db.php');
include($path . 'inc/auth.php');
include($path . 'inc/func.php');
include($path . 'inc/core.php');
include_once($path . 'inc/lin2_ui.php');
include_once($path . 'inc/quests_promo.php');

lin2_qp_ensure();
if (!empty($log)) {
    lin2_qp_touch_daily_visit($log);
}

$flash_msg = '';
$flash_ok = false;

// Получение награды за ежедневное / еженедельное задание ДО вывода шапки, чтобы баланс в шапке обновился сразу
if (!empty($log) && isset($_GET['claim'])) {
    list($flash_ok, $flash_msg) = lin2_qp_claim_quest($log, (int)$_GET['claim']);
    // Перечитываем $udata для актуального баланса в шапке
    $qu = mysql_query("SELECT * FROM `users` WHERE `usr` = '" . mysql_real_escape_string($log) . "' LIMIT 1");
    if ($qu && mysql_num_rows($qu) > 0) {
        $udata = mysql_fetch_array($qu);
    }
}

include($path . 'inc/head.php');
include($path . 'inc/zag.php');

$tab = isset($_GET['tab']) ? (string)$_GET['tab'] : 'daily';
if ($tab !== 'daily' && $tab !== 'weekly' && $tab !== 'all' && $tab !== 'epic') {
    $tab = 'daily';
}

echo lin2_ui_place('Гильдия приключенцев', 'Ежедневные и еженедельные задания');

if ($flash_msg !== '') {
    echo '<div class="lx-hint" style="border-color:' . ($flash_ok ? '#4c9e4f' : '#a84438') . ';color:' . ($flash_ok ? '#9be89e' : '#ffb4a8') . ';">'
        . lin2_ui_h($flash_msg) . '</div>';
}

$dCountRes = mysql_query("SELECT COUNT(*) FROM `lin2_quests` WHERE `period` = 'daily' AND `active` = '1'");
$dTotal = $dCountRes ? (int)mysql_result($dCountRes, 0) : 0;
$wCountRes = mysql_query("SELECT COUNT(*) FROM `lin2_quests` WHERE `period` = 'weekly' AND `active` = '1'");
$wTotal = $wCountRes ? (int)mysql_result($wCountRes, 0) : 0;

echo lin2_ui_tabs(array(
    array('Ежедневные (' . $dTotal . ')', $tab === 'daily' ? null : 'job.php?tab=daily'),
    array('Еженедельные (' . $wTotal . ')', $tab === 'weekly' ? null : 'job.php?tab=weekly'),
    array('Все', $tab === 'all' ? null : 'job.php?tab=all'),
    array('Особые', $tab === 'epic' ? null : 'job.php?tab=epic'),
));

if ($tab !== 'epic') {
    $dKey = lin2_qp_daily_key();
    $wKey = lin2_qp_weekly_key();
    $dCounters = lin2_qp_counters_map($log, $dKey);
    $wCounters = lin2_qp_counters_map($log, $wKey);
    $dClaims = lin2_qp_claims_map($log, $dKey);
    $wClaims = lin2_qp_claims_map($log, $wKey);

    $periods = ($tab === 'all') ? array('daily', 'weekly') : array($tab);

    foreach ($periods as $per) {
        $isWeekly = ($per === 'weekly');
        $sectionTitle = $isWeekly
            ? 'Еженедельные задания (сброс в Пн 00:00)'
            : 'Ежедневные задания (сброс в 00:00)';
        $counters = $isWeekly ? $wCounters : $dCounters;
        $claims = $isWeekly ? $wClaims : $dClaims;

        $q = mysql_query("SELECT * FROM `lin2_quests` WHERE `period` = '" . mysql_real_escape_string($per) . "' AND `active` = '1' ORDER BY `sort_order` ASC, `id` ASC");
        $body = '';
        $shown = 0;

        if ($q && mysql_num_rows($q) > 0) {
            while ($row = mysql_fetch_assoc($q)) {
                $shown++;
                $qid = (int)$row['id'];
                $target = max(1, (int)$row['target']);
                $minLvl = (int)$row['min_lvl'];
                $cur = isset($counters[$row['metric']]) ? (int)$counters[$row['metric']] : 0;
                $displayCur = $cur > $target ? $target : $cur;
                $claimed = !empty($claims[$qid]);
                $locked = ((int)$udata['lvl'] < $minLvl);

                $body .= '<div style="padding:8px 2px;border-bottom:1px solid rgba(185,145,84,.16);">';
                $body .= '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">';
                $body .= '<b style="color:#f4d898;font-size:13px;">' . lin2_ui_h($row['title']) . '</b>';
                if ($claimed) {
                    $body .= '<span class="lx-qdone">&#10003; Получено</span>';
                } elseif ($locked) {
                    $body .= '<span style="font-size:11px;color:#b99a74;">с ' . $minLvl . ' ур.</span>';
                } else {
                    $body .= '<span style="font-size:11px;color:#d8c39a;">' . number_format($displayCur, 0, '', ' ') . ' / ' . number_format($target, 0, '', ' ') . '</span>';
                }
                $body .= '</div>';

                if (!empty($row['descr'])) {
                    $body .= '<div class="lx-qdesc">' . lin2_ui_h($row['descr']) . '</div>';
                }

                $barKind = ($claimed || ($displayCur >= $target && !$locked)) ? 'lx-hp' : 'lx-xp';
                $body .= lin2_ui_meter($barKind, number_format($displayCur, 0, '', ' ') . ' / ' . number_format($target, 0, '', ' '), $displayCur, $target);
                $body .= lin2_qp_reward_chips_html($row['rew_adena'], $row['rew_exp'], $row['rew_col'], $row['rew_vip']);

                if (!$claimed && !$locked && $cur >= $target) {
                    $body .= '<div style="margin-top:6px;">'
                        . lin2_ui_btn('job.php?tab=' . urlencode($tab) . '&claim=' . $qid, 'Забрать награду', '', 'sm')
                        . '</div>';
                }
                $body .= '</div>';
            }
        }

        if ($shown === 0) {
            $body = '<div class="lx-qdesc">В этом разделе пока нет активных заданий.</div>';
        }
        echo lin2_ui_panel($sectionTitle, $body);
    }
} else {
    // Классические долгосрочные достижения сервера
    $epicBody = '';
    if ($udata['lvl'] > 76) {
        if (isset($_GET['jbm'])) {
            $jm = mysql_fetch_array(mysql_query("SELECT * FROM job_mob WHERE `usr` = '$log' LIMIT 1"));
            if ($jm && (int)$jm['kill'] >= 20000) {
                $newexp = $udata['exp'] + 5000000000;
                $newalmaz = $udata['almaz'] + 1200;
                mysql_query("UPDATE users SET `exp` = '$newexp', `almaz` = '$newalmaz' WHERE `usr` = '$log' LIMIT 1");
                $date = date("d.m.Y");
                mysql_query("UPDATE job_mob SET `kill` = 'off', `data` = '$date' WHERE `usr` = '$log' LIMIT 1");
            }
        }
        $jm = mysql_fetch_array(mysql_query("SELECT * FROM `job_mob` WHERE `usr` = '$log' LIMIT 1"));
        $mobkill = empty($jm['kill']) ? 0 : $jm['kill'];
        $epicBody .= '<div style="padding:6px 0;border-bottom:1px solid rgba(185,145,84,.16);"><b style="color:#f4d06e;">Убить 20 000 мобов</b>';
        if ($mobkill === 'off') {
            $epicBody .= ' <span class="lx-qdone">ВЫПОЛНЕНО</span>';
        } else {
            $epicBody .= lin2_ui_meter('lx-xp', (int)$mobkill . ' / 20 000', (int)$mobkill, 20000);
            $epicBody .= lin2_qp_reward_chips_html(0, 5000000000, 1200, 0);
            if ((int)$mobkill >= 20000) {
                $epicBody .= '<div style="margin-top:6px;">' . lin2_ui_btn('job.php?tab=epic&jbm=1', 'Завершить', '', 'sm') . '</div>';
            }
        }
        $epicBody .= '</div>';
    } else {
        $epicBody .= '<div style="padding:6px 0;border-bottom:1px solid rgba(185,145,84,.16);"><b style="color:#f4d06e;">Убить 20 000 мобов</b><div class="lx-qdesc">Доступно с 77 уровня.</div></div>';
    }

    if ($udata['lvl'] > 49) {
        if (isset($_GET['jbf'])) {
            $jm = mysql_fetch_array(mysql_query("SELECT * FROM job_fish WHERE `usr` = '$log' LIMIT 1"));
            if ($jm && (int)$jm['kill'] >= 10000) {
                $newexp = $udata['exp'] + 1000000000;
                $newalmaz = $udata['almaz'] + 450;
                mysql_query("UPDATE users SET `exp` = '$newexp', `almaz` = '$newalmaz' WHERE `usr` = '$log' LIMIT 1");
                $date = date("d.m.Y");
                mysql_query("UPDATE job_fish SET `kill` = 'off', `data` = '$date' WHERE `usr` = '$log' LIMIT 1");
            }
        }
        $jm = mysql_fetch_array(mysql_query("SELECT * FROM `job_fish` WHERE `usr` = '$log' LIMIT 1"));
        $mobkill = empty($jm['kill']) ? 0 : $jm['kill'];
        $epicBody .= '<div style="padding:6px 0;border-bottom:1px solid rgba(185,145,84,.16);"><b style="color:#f4d06e;">Словить 10 000 рыб</b>';
        if ($mobkill === 'off') {
            $epicBody .= ' <span class="lx-qdone">ВЫПОЛНЕНО</span>';
        } else {
            $epicBody .= lin2_ui_meter('lx-xp', (int)$mobkill . ' / 10 000', (int)$mobkill, 10000);
            $epicBody .= lin2_qp_reward_chips_html(0, 1000000000, 450, 0);
            if ((int)$mobkill >= 10000) {
                $epicBody .= '<div style="margin-top:6px;">' . lin2_ui_btn('job.php?tab=epic&jbf=1', 'Завершить', '', 'sm') . '</div>';
            }
        }
        $epicBody .= '</div>';

        if (isset($_GET['jpvp'])) {
            $jm = mysql_fetch_array(mysql_query("SELECT * FROM job_pvp WHERE `usr` = '$log' LIMIT 1"));
            if ($jm && (int)$jm['kill'] >= 100) {
                $newexp = $udata['exp'] + 2000000000;
                $newalmaz = $udata['almaz'] + 75;
                mysql_query("UPDATE users SET `exp` = '$newexp', `almaz` = '$newalmaz' WHERE `usr` = '$log' LIMIT 1");
                $date = date("d.m.Y");
                mysql_query("UPDATE job_pvp SET `kill` = 'off', `data` = '$date' WHERE `usr` = '$log' LIMIT 1");
            }
        }
        $jm = mysql_fetch_array(mysql_query("SELECT * FROM `job_pvp` WHERE `usr` = '$log' LIMIT 1"));
        $mobkill = empty($jm['kill']) ? 0 : $jm['kill'];
        $epicBody .= '<div style="padding:6px 0;"><b style="color:#f4d06e;">PvP: 100 побед при нападении</b>';
        if ($mobkill === 'off') {
            $epicBody .= ' <span class="lx-qdone">ВЫПОЛНЕНО</span>';
        } else {
            $epicBody .= lin2_ui_meter('lx-xp', (int)$mobkill . ' / 100', (int)$mobkill, 100);
            $epicBody .= lin2_qp_reward_chips_html(0, 2000000000, 75, 0);
            if ((int)$mobkill >= 100) {
                $epicBody .= '<div style="margin-top:6px;">' . lin2_ui_btn('job.php?tab=epic&jpvp=1', 'Завершить', '', 'sm') . '</div>';
            }
        }
        $epicBody .= '</div>';
    } else {
        $epicBody .= '<div style="padding:6px 0;"><b style="color:#f4d06e;">Рыбалка (10 000) и PvP (100)</b><div class="lx-qdesc">Доступно с 50 уровня.</div></div>';
    }
    echo lin2_ui_panel('Особые достижения', $epicBody);
}

$navLinks = array(
    array('promo.php?', 'Акции и бонусы'),
    array('gorod.php?', 'В город'),
);
if (!empty($udata['dostup']) && (int)$udata['dostup'] >= 4) {
    $navLinks[] = array('adm_panel.php?mod=quests', 'Управление квестами (Адм.)');
}
echo lin2_ui_go($navLinks);

include($path . 'inc/down.php');
