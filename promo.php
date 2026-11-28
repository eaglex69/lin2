<?php
define('PROTECTOR', 1);

$textl = 'Акции и бонусы';
include('inc/path.php');
include($path . 'inc/db.php');
include($path . 'inc/auth.php');
include($path . 'inc/func.php');
include($path . 'inc/core.php');
include_once($path . 'inc/lin2_ui.php');
include_once($path . 'inc/quests_promo.php');

lin2_qp_ensure();

$flash_msg = '';
$flash_ok = false;

if (!empty($log)) {
    if (isset($_GET['claim_promo'])) {
        list($flash_ok, $flash_msg) = lin2_qp_claim_promo_gift($log, (int)$_GET['claim_promo']);
    } elseif (isset($_GET['claim_bonus'])) {
        list($flash_ok, $flash_msg) = lin2_qp_claim_bonus($log, (int)$_GET['claim_bonus']);
    } elseif (isset($_POST['promo_code'])) {
        list($flash_ok, $flash_msg) = lin2_qp_redeem_code($log, $_POST['promo_code']);
    }

    if ($flash_msg !== '') {
        $qu = mysql_query("SELECT * FROM `users` WHERE `usr` = '" . mysql_real_escape_string($log) . "' LIMIT 1");
        if ($qu && mysql_num_rows($qu) > 0) {
            $udata = mysql_fetch_array($qu);
        }
    }
}

include($path . 'inc/head.php');
include($path . 'inc/zag.php');

echo lin2_ui_place('Акции и бонусы', 'Подарки, рейты и промокоды');

if ($flash_msg !== '') {
    echo '<div class="lx-hint" style="border-color:' . ($flash_ok ? '#4c9e4f' : '#a84438') . ';color:' . ($flash_ok ? '#9be89e' : '#ffb4a8') . ';">'
        . lin2_ui_h($flash_msg) . '</div>';
}

// 1. Действующие акции
$promos = lin2_qp_active_promos();
$promoBody = '';
$uEsc = mysql_real_escape_string($log);

if (!empty($promos)) {
    foreach ($promos as $p) {
        $pid = (int)$p['id'];
        $promoBody .= '<div style="padding:8px 2px;border-bottom:1px solid rgba(185,145,84,.16);">';
        $promoBody .= '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">';
        $promoBody .= '<b style="color:#f4d898;font-size:13px;">' . lin2_ui_h($p['title']) . '</b>';
        if (!empty($p['badge'])) {
            $promoBody .= '<span class="lx-qchip">' . lin2_ui_h($p['badge']) . '</span>';
        }
        $promoBody .= '</div>';

        if (!empty($p['descr'])) {
            $promoBody .= '<div class="lx-qdesc">' . nl2br(lin2_ui_h($p['descr'])) . '</div>';
        }

        $rateChips = array();
        if ((int)$p['exp_pct'] !== 100) {
            $rateChips[] = '<span class="lx-qchip">Опыт x' . round((int)$p['exp_pct'] / 100, 2) . '</span>';
        }
        if ((int)$p['adena_pct'] !== 100) {
            $rateChips[] = '<span class="lx-qchip">Адена x' . round((int)$p['adena_pct'] / 100, 2) . '</span>';
        }
        if ((int)$p['ends_at'] > 0) {
            $rateChips[] = '<span class="lx-qchip">до ' . date('d.m.Y H:i', (int)$p['ends_at']) . '</span>';
        }
        if (!empty($rateChips)) {
            $promoBody .= '<div class="lx-qrew">' . implode('', $rateChips) . '</div>';
        }

        $hasGift = ((int)$p['gift_adena'] > 0 || (int)$p['gift_exp'] > 0 || (int)$p['gift_col'] > 0);
        if ($hasGift) {
            $promoBody .= '<div class="lx-qdesc" style="margin-top:4px;">Подарок участника акции:</div>';
            $promoBody .= lin2_qp_reward_chips_html($p['gift_adena'], $p['gift_exp'], $p['gift_col'], 0);
            $ck = 'promo:' . $pid;
            $qcl = mysql_query("SELECT `id` FROM `lin2_bonus_claims` WHERE `usr` = '$uEsc' AND `bonus_id` = '0' AND `claim_key` = '$ck' LIMIT 1");
            $gotGift = ($qcl && mysql_num_rows($qcl) > 0);
            if ($gotGift) {
                $promoBody .= '<div style="margin-top:6px;"><span class="lx-qdone">&#10003; Подарок получен</span></div>';
            } else {
                $promoBody .= '<div style="margin-top:6px;">' . lin2_ui_btn('promo.php?claim_promo=' . $pid, 'Забрать подарок', '', 'sm') . '</div>';
            }
        }
        $promoBody .= '</div>';
    }
} else {
    $promoBody = '<div class="lx-qdesc">Сейчас нет активных акций. Следите за новостями сервера!</div>';
}
echo lin2_ui_panel('Действующие акции', $promoBody);

// 2. Открытые бонусы (ежедневные / еженедельные / разовые)
$now = time();
$qb = mysql_query("SELECT * FROM `lin2_bonuses` WHERE `active` = '1' AND `code` = '' ORDER BY `id` ASC");
$bonusBody = '';
$bShown = 0;

if ($qb && mysql_num_rows($qb) > 0) {
    while ($b = mysql_fetch_assoc($qb)) {
        if ((int)$b['ends_at'] > 0 && (int)$b['ends_at'] < $now) {
            continue;
        }
        $bShown++;
        $bid = (int)$b['id'];
        $cdLabel = ($b['cooldown_type'] === 'daily') ? 'Ежедневно' : (($b['cooldown_type'] === 'weekly') ? 'Раз в неделю' : 'Один раз');
        $ck = mysql_real_escape_string(lin2_qp_bonus_claim_key($b['cooldown_type']));
        $qcl = mysql_query("SELECT `id` FROM `lin2_bonus_claims` WHERE `usr` = '$uEsc' AND `bonus_id` = '$bid' AND `claim_key` = '$ck' LIMIT 1");
        $claimed = ($qcl && mysql_num_rows($qcl) > 0);
        $locked = ((int)$udata['lvl'] < (int)$b['min_lvl']);
        $exhausted = ((int)$b['max_claims'] > 0 && (int)$b['claims_count'] >= (int)$b['max_claims']);

        $bonusBody .= '<div style="padding:8px 2px;border-bottom:1px solid rgba(185,145,84,.16);">';
        $bonusBody .= '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">';
        $bonusBody .= '<b style="color:#f4d898;font-size:13px;">' . lin2_ui_h($b['title']) . '</b>';
        $bonusBody .= '<span style="font-size:11px;color:#b99a74;">' . $cdLabel . '</span>';
        $bonusBody .= '</div>';

        if (!empty($b['descr'])) {
            $bonusBody .= '<div class="lx-qdesc">' . lin2_ui_h($b['descr']) . '</div>';
        }
        $bonusBody .= lin2_qp_reward_chips_html($b['rew_adena'], $b['rew_exp'], $b['rew_col'], $b['rew_vip']);

        if ($claimed) {
            $bonusBody .= '<div style="margin-top:6px;"><span class="lx-qdone">&#10003; Получено</span></div>';
        } elseif ($exhausted) {
            $bonusBody .= '<div style="margin-top:6px;font-size:11px;color:#b99a74;">Лимит наград исчерпан</div>';
        } elseif ($locked) {
            $bonusBody .= '<div style="margin-top:6px;font-size:11px;color:#b99a74;">Доступно с ' . (int)$b['min_lvl'] . ' уровня</div>';
        } else {
            $bonusBody .= '<div style="margin-top:6px;">' . lin2_ui_btn('promo.php?claim_bonus=' . $bid, 'Получить бонус', '', 'sm') . '</div>';
        }
        $bonusBody .= '</div>';
    }
}
if ($bShown === 0) {
    $bonusBody = '<div class="lx-qdesc">Доступных открытых бонусов сейчас нет.</div>';
}
echo lin2_ui_panel('Бонусы для героев', $bonusBody);

// 3. Активация промокода
$codeForm = '<form action="promo.php" method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">'
    . '<input class="input" type="text" name="promo_code" maxlength="64" placeholder="Введите промокод..." style="flex:1;min-width:160px;" />'
    . '<input class="button" type="submit" value="Активировать" />'
    . '</form>';
echo lin2_ui_panel('Промокод', $codeForm);

$navLinks = array(
    array('job.php?', 'К заданиям'),
    array('gorod.php?', 'В город'),
);
if (!empty($udata['dostup']) && (int)$udata['dostup'] >= 4) {
    $navLinks[] = array('adm_panel.php?mod=promos', 'Управление акциями (Адм.)');
    $navLinks[] = array('adm_panel.php?mod=bonuses', 'Управление бонусами (Адм.)');
}
echo lin2_ui_go($navLinks);

include($path . 'inc/down.php');
