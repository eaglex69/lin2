<?php
/**
 * Модули админ-панели:
 *   - mod=quests  : управление ежедневными и еженедельными квестами
 *   - mod=promos  : управление акциями и рейтами
 *   - mod=bonuses : управление бонусами и промокодами
 *   - mod=images  : замена и загрузка любых растровых изображений на сайте
 */

if (!defined('PROTECTOR')) {
    exit;
}

include_once(dirname(__FILE__) . '/lin2_ui.php');
include_once(dirname(__FILE__) . '/quests_promo.php');

function lin2_adm_nav_bar($activeMod)
{
    return lin2_ui_tabs(array(
        array('Квесты', $activeMod === 'quests' ? null : 'adm_panel.php?mod=quests'),
        array('Акции', $activeMod === 'promos' ? null : 'adm_panel.php?mod=promos'),
        array('Бонусы', $activeMod === 'bonuses' ? null : 'adm_panel.php?mod=bonuses'),
        array('Картинки сайта', $activeMod === 'images' ? null : 'adm_panel.php?mod=images'),
        array('Админ-меню', 'adm_panel.php?'),
    ));
}

// =========================================================================
// 1. УПРАВЛЕНИЕ КВЕСТАМИ (ежедневные и еженедельные)
// =========================================================================
function lin2_adm_quests()
{
    lin2_qp_ensure();
    echo lin2_ui_place('Админ-панель', 'Управление ежедневными и еженедельными заданиями');
    echo lin2_adm_nav_bar('quests');

    $metrics = lin2_qp_metrics();
    $msg = '';
    $ok = true;

    if (isset($_GET['del'])) {
        $did = (int)$_GET['del'];
        if ($did > 0) {
            mysql_query("DELETE FROM `lin2_quests` WHERE `id` = '$did' LIMIT 1");
            $msg = 'Задание #' . $did . ' удалено.';
        }
    } elseif (isset($_GET['toggle'])) {
        $tid = (int)$_GET['toggle'];
        $qr = mysql_query("SELECT `active` FROM `lin2_quests` WHERE `id` = '$tid' LIMIT 1");
        if ($qr && mysql_num_rows($qr) > 0) {
            $cur = (int)mysql_result($qr, 0);
            $next = $cur ? 0 : 1;
            mysql_query("UPDATE `lin2_quests` SET `active` = '$next' WHERE `id` = '$tid' LIMIT 1");
            $msg = 'Статус задания #' . $tid . ' изменён (' . ($next ? 'включено' : 'выключено') . ').';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quest'])) {
        $qid = isset($_POST['quest_id']) ? (int)$_POST['quest_id'] : 0;
        $period = (isset($_POST['period']) && $_POST['period'] === 'weekly') ? 'weekly' : 'daily';
        $title = trim((string)(isset($_POST['title']) ? $_POST['title'] : ''));
        $descr = trim((string)(isset($_POST['descr']) ? $_POST['descr'] : ''));
        $metric = isset($_POST['metric']) && isset($metrics[$_POST['metric']]) ? $_POST['metric'] : 'mob_kill';
        $target = max(1, (int)(isset($_POST['target']) ? $_POST['target'] : 1));
        $minLvl = max(0, (int)(isset($_POST['min_lvl']) ? $_POST['min_lvl'] : 0));
        $rewAdena = max(0, (int)(isset($_POST['rew_adena']) ? $_POST['rew_adena'] : 0));
        $rewExp = max(0, (int)(isset($_POST['rew_exp']) ? $_POST['rew_exp'] : 0));
        $rewCol = max(0, (int)(isset($_POST['rew_col']) ? $_POST['rew_col'] : 0));
        $rewVip = max(0, (int)(isset($_POST['rew_vip']) ? $_POST['rew_vip'] : 0));
        $sortOrder = (int)(isset($_POST['sort_order']) ? $_POST['sort_order'] : 50);
        $active = !empty($_POST['active']) ? 1 : 0;

        if ($title === '') {
            $ok = false;
            $msg = 'Укажите название задания.';
        } else {
            $tEsc = mysql_real_escape_string($title);
            $dEsc = mysql_real_escape_string($descr);
            $mEsc = mysql_real_escape_string($metric);
            if ($qid > 0) {
                mysql_query("UPDATE `lin2_quests` SET
                    `period` = '$period',
                    `title` = '$tEsc',
                    `descr` = '$dEsc',
                    `metric` = '$mEsc',
                    `target` = '$target',
                    `min_lvl` = '$minLvl',
                    `rew_adena` = '$rewAdena',
                    `rew_exp` = '$rewExp',
                    `rew_col` = '$rewCol',
                    `rew_vip` = '$rewVip',
                    `active` = '$active',
                    `sort_order` = '$sortOrder'
                    WHERE `id` = '$qid' LIMIT 1");
                $msg = 'Задание #' . $qid . ' обновлено.';
            } else {
                mysql_query("INSERT INTO `lin2_quests` SET
                    `period` = '$period',
                    `title` = '$tEsc',
                    `descr` = '$dEsc',
                    `metric` = '$mEsc',
                    `target` = '$target',
                    `min_lvl` = '$minLvl',
                    `rew_adena` = '$rewAdena',
                    `rew_exp` = '$rewExp',
                    `rew_col` = '$rewCol',
                    `rew_vip` = '$rewVip',
                    `active` = '$active',
                    `sort_order` = '$sortOrder'");
                $msg = 'Новое задание «' . $title . '» добавлено!';
            }
        }
    }

    if ($msg !== '') {
        echo '<div class="lx-hint" style="border-color:' . ($ok ? '#4c9e4f' : '#a84438') . ';color:' . ($ok ? '#9be89e' : '#ffb4a8') . ';">' . lin2_ui_h($msg) . '</div>';
    }

    // Форма создания / редактирования
    $editRow = array(
        'id' => 0, 'period' => 'daily', 'title' => '', 'descr' => '',
        'metric' => 'mob_kill', 'target' => 10, 'min_lvl' => 0,
        'rew_adena' => 5000, 'rew_exp' => 2500, 'rew_col' => 1, 'rew_vip' => 0,
        'active' => 1, 'sort_order' => 50,
    );
    if (isset($_GET['edit'])) {
        $eid = (int)$_GET['edit'];
        $qe = mysql_query("SELECT * FROM `lin2_quests` WHERE `id` = '$eid' LIMIT 1");
        if ($qe && mysql_num_rows($qe) > 0) {
            $editRow = mysql_fetch_assoc($qe);
        }
    }

    $form = '<form action="adm_panel.php?mod=quests" method="post">'
        . '<input type="hidden" name="save_quest" value="1" />'
        . '<input type="hidden" name="quest_id" value="' . (int)$editRow['id'] . '" />'
        . '<div style="margin-bottom:6px;">Тип периода:<br/>'
        . '<select name="period">'
        . '<option value="daily"' . ($editRow['period'] === 'daily' ? ' selected="selected"' : '') . '>Ежедневное</option>'
        . '<option value="weekly"' . ($editRow['period'] === 'weekly' ? ' selected="selected"' : '') . '>Еженедельное</option>'
        . '</select></div>'
        . '<div style="margin-bottom:6px;">Название задания:<br/>'
        . '<input class="input" type="text" name="title" maxlength="160" value="' . lin2_ui_h($editRow['title']) . '" style="width:96%;" /></div>'
        . '<div style="margin-bottom:6px;">Описание:<br/>'
        . '<input class="input" type="text" name="descr" maxlength="255" value="' . lin2_ui_h($editRow['descr']) . '" style="width:96%;" /></div>'
        . '<div style="margin-bottom:6px;">Условие (метрика):<br/><select name="metric">';
    foreach ($metrics as $mk => $mlabel) {
        $sel = ($editRow['metric'] === $mk) ? ' selected="selected"' : '';
        $form .= '<option value="' . lin2_ui_h($mk) . '"' . $sel . '>' . lin2_ui_h($mlabel) . ' (' . lin2_ui_h($mk) . ')</option>';
    }
    $form .= '</select></div>'
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:6px;">'
        . '<div>Цель (кол-во):<br/><input class="input" type="number" name="target" min="1" value="' . (int)$editRow['target'] . '" style="width:92%;" /></div>'
        . '<div>Мин. уровень:<br/><input class="input" type="number" name="min_lvl" min="0" value="' . (int)$editRow['min_lvl'] . '" style="width:92%;" /></div>'
        . '<div>Награда Адена:<br/><input class="input" type="number" name="rew_adena" min="0" value="' . (int)$editRow['rew_adena'] . '" style="width:92%;" /></div>'
        . '<div>Награда EXP:<br/><input class="input" type="number" name="rew_exp" min="0" value="' . (int)$editRow['rew_exp'] . '" style="width:92%;" /></div>'
        . '<div>Награда CoL:<br/><input class="input" type="number" name="rew_col" min="0" value="' . (int)$editRow['rew_col'] . '" style="width:92%;" /></div>'
        . '<div>Награда Gold Bar:<br/><input class="input" type="number" name="rew_vip" min="0" value="' . (int)$editRow['rew_vip'] . '" style="width:92%;" /></div>'
        . '<div>Порядок сортировки:<br/><input class="input" type="number" name="sort_order" value="' . (int)$editRow['sort_order'] . '" style="width:92%;" /></div>'
        . '<div style="display:flex;align-items:end;padding-bottom:4px;"><label><input type="checkbox" name="active" value="1"' . ((int)$editRow['active'] ? ' checked="checked"' : '') . ' /> Активно</label></div>'
        . '</div>'
        . '<input class="button" type="submit" value="' . ((int)$editRow['id'] > 0 ? 'Сохранить изменения' : 'Добавить квест') . '" />';
    if ((int)$editRow['id'] > 0) {
        $form .= ' <a href="adm_panel.php?mod=quests">Отмена</a>';
    }
    $form .= '</form>';

    echo lin2_ui_panel((int)$editRow['id'] > 0 ? 'Редактирование квеста #' . (int)$editRow['id'] : 'Добавить новый квест', $form);

    foreach (array('daily' => 'Ежедневные задания', 'weekly' => 'Еженедельные задания') as $per => $pTitle) {
        $ql = mysql_query("SELECT * FROM `lin2_quests` WHERE `period` = '$per' ORDER BY `sort_order` ASC, `id` ASC");
        $listHtml = '';
        $cnt = 0;
        if ($ql && mysql_num_rows($ql) > 0) {
            while ($r = mysql_fetch_assoc($ql)) {
                $cnt++;
                $rid = (int)$r['id'];
                $mName = isset($metrics[$r['metric']]) ? $metrics[$r['metric']] : $r['metric'];
                $statusTag = (int)$r['active'] ? '<span style="color:#7fe084;">[Активно]</span>' : '<span style="color:#e07f7f;">[Выкл]</span>';
                $listHtml .= '<div style="padding:7px 2px;border-bottom:1px solid rgba(185,145,84,.16);">'
                    . '<b>#' . $rid . ' ' . lin2_ui_h($r['title']) . '</b> ' . $statusTag
                    . '<div class="lx-qdesc">' . lin2_ui_h($mName) . ' &rarr; ' . number_format((int)$r['target'], 0, '', ' ')
                    . ((int)$r['min_lvl'] > 0 ? ' (с ' . (int)$r['min_lvl'] . ' ур.)' : '') . '</div>'
                    . lin2_qp_reward_chips_html($r['rew_adena'], $r['rew_exp'], $r['rew_col'], $r['rew_vip'])
                    . '<div style="margin-top:5px;font-size:12px;">'
                    . '<a href="adm_panel.php?mod=quests&edit=' . $rid . '">[Изменить]</a> &middot; '
                    . '<a href="adm_panel.php?mod=quests&toggle=' . $rid . '">[' . ((int)$r['active'] ? 'Выключить' : 'Включить') . ']</a> &middot; '
                    . '<a href="adm_panel.php?mod=quests&del=' . $rid . '" style="color:#ff7b7b;">[Удалить]</a>'
                    . '</div></div>';
            }
        }
        if ($cnt === 0) {
            $listHtml = '<div class="lx-qdesc">Нет заданий.</div>';
        }
        echo lin2_ui_panel($pTitle . ' (' . $cnt . ')', $listHtml);
    }
}

// =========================================================================
// 2. УПРАВЛЕНИЕ АКЦИЯМИ
// =========================================================================
function lin2_adm_promos()
{
    lin2_qp_ensure();
    echo lin2_ui_place('Админ-панель', 'Управление акциями и рейтами');
    echo lin2_adm_nav_bar('promos');

    $msg = '';
    $ok = true;

    if (isset($_GET['del'])) {
        $did = (int)$_GET['del'];
        if ($did > 0) {
            mysql_query("DELETE FROM `lin2_promos` WHERE `id` = '$did' LIMIT 1");
            $msg = 'Акция #' . $did . ' удалена.';
        }
    } elseif (isset($_GET['toggle'])) {
        $tid = (int)$_GET['toggle'];
        $qr = mysql_query("SELECT `active` FROM `lin2_promos` WHERE `id` = '$tid' LIMIT 1");
        if ($qr && mysql_num_rows($qr) > 0) {
            $next = ((int)mysql_result($qr, 0)) ? 0 : 1;
            mysql_query("UPDATE `lin2_promos` SET `active` = '$next' WHERE `id` = '$tid' LIMIT 1");
            $msg = 'Статус акции #' . $tid . ' изменён.';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_promo'])) {
        $pid = isset($_POST['promo_id']) ? (int)$_POST['promo_id'] : 0;
        $title = trim((string)(isset($_POST['title']) ? $_POST['title'] : ''));
        $descr = trim((string)(isset($_POST['descr']) ? $_POST['descr'] : ''));
        $badge = trim((string)(isset($_POST['badge']) ? $_POST['badge'] : ''));
        $expPct = max(100, min(1000, (int)(isset($_POST['exp_pct']) ? $_POST['exp_pct'] : 100)));
        $adenaPct = max(100, min(1000, (int)(isset($_POST['adena_pct']) ? $_POST['adena_pct'] : 100)));
        $giftAdena = max(0, (int)(isset($_POST['gift_adena']) ? $_POST['gift_adena'] : 0));
        $giftExp = max(0, (int)(isset($_POST['gift_exp']) ? $_POST['gift_exp'] : 0));
        $giftCol = max(0, (int)(isset($_POST['gift_col']) ? $_POST['gift_col'] : 0));
        $durationHours = max(0, (int)(isset($_POST['duration_hours']) ? $_POST['duration_hours'] : 0));
        $endsAt = $durationHours > 0 ? (time() + $durationHours * 3600) : 0;
        $active = !empty($_POST['active']) ? 1 : 0;

        if ($title === '') {
            $ok = false;
            $msg = 'Укажите название акции.';
        } else {
            $tEsc = mysql_real_escape_string($title);
            $dEsc = mysql_real_escape_string($descr);
            $bEsc = mysql_real_escape_string($badge);
            if ($pid > 0) {
                mysql_query("UPDATE `lin2_promos` SET
                    `title` = '$tEsc',
                    `descr` = '$dEsc',
                    `badge` = '$bEsc',
                    `exp_pct` = '$expPct',
                    `adena_pct` = '$adenaPct',
                    `gift_adena` = '$giftAdena',
                    `gift_exp` = '$giftExp',
                    `gift_col` = '$giftCol',
                    `ends_at` = '$endsAt',
                    `active` = '$active'
                    WHERE `id` = '$pid' LIMIT 1");
                $msg = 'Акция #' . $pid . ' обновлена.';
            } else {
                mysql_query("INSERT INTO `lin2_promos` SET
                    `title` = '$tEsc',
                    `descr` = '$dEsc',
                    `badge` = '$bEsc',
                    `exp_pct` = '$expPct',
                    `adena_pct` = '$adenaPct',
                    `gift_adena` = '$giftAdena',
                    `gift_exp` = '$giftExp',
                    `gift_col` = '$giftCol',
                    `starts_at` = '0',
                    `ends_at` = '$endsAt',
                    `active` = '$active'");
                $msg = 'Акция «' . $title . '» создана!';
            }
        }
    }

    if ($msg !== '') {
        echo '<div class="lx-hint" style="border-color:' . ($ok ? '#4c9e4f' : '#a84438') . ';color:' . ($ok ? '#9be89e' : '#ffb4a8') . ';">' . lin2_ui_h($msg) . '</div>';
    }

    $editRow = array(
        'id' => 0, 'title' => '', 'descr' => '', 'badge' => 'Акция',
        'exp_pct' => 150, 'adena_pct' => 150, 'gift_adena' => 0, 'gift_exp' => 0, 'gift_col' => 0,
        'ends_at' => 0, 'active' => 1,
    );
    if (isset($_GET['edit'])) {
        $eid = (int)$_GET['edit'];
        $qe = mysql_query("SELECT * FROM `lin2_promos` WHERE `id` = '$eid' LIMIT 1");
        if ($qe && mysql_num_rows($qe) > 0) {
            $editRow = mysql_fetch_assoc($qe);
        }
    }
    $remHours = ((int)$editRow['ends_at'] > time()) ? (int)ceil(((int)$editRow['ends_at'] - time()) / 3600) : 0;

    $form = '<form action="adm_panel.php?mod=promos" method="post">'
        . '<input type="hidden" name="save_promo" value="1" />'
        . '<input type="hidden" name="promo_id" value="' . (int)$editRow['id'] . '" />'
        . '<div style="margin-bottom:6px;">Название акции:<br/>'
        . '<input class="input" type="text" name="title" maxlength="160" value="' . lin2_ui_h($editRow['title']) . '" style="width:96%;" /></div>'
        . '<div style="margin-bottom:6px;">Короткая метка (например, «Рейты x2»):<br/>'
        . '<input class="input" type="text" name="badge" maxlength="64" value="' . lin2_ui_h($editRow['badge']) . '" style="width:96%;" /></div>'
        . '<div style="margin-bottom:6px;">Описание акции:<br/>'
        . '<textarea class="input" name="descr" rows="2" style="width:96%;">' . lin2_ui_h($editRow['descr']) . '</textarea></div>'
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:6px;">'
        . '<div>Множитель опыта (% — 100=x1, 200=x2):<br/><input class="input" type="number" name="exp_pct" min="100" max="1000" value="' . (int)$editRow['exp_pct'] . '" style="width:92%;" /></div>'
        . '<div>Множитель адены (% — 100=x1, 200=x2):<br/><input class="input" type="number" name="adena_pct" min="100" max="1000" value="' . (int)$editRow['adena_pct'] . '" style="width:92%;" /></div>'
        . '<div>Подарок Адена:<br/><input class="input" type="number" name="gift_adena" min="0" value="' . (int)$editRow['gift_adena'] . '" style="width:92%;" /></div>'
        . '<div>Подарок EXP:<br/><input class="input" type="number" name="gift_exp" min="0" value="' . (int)$editRow['gift_exp'] . '" style="width:92%;" /></div>'
        . '<div>Подарок CoL:<br/><input class="input" type="number" name="gift_col" min="0" value="' . (int)$editRow['gift_col'] . '" style="width:92%;" /></div>'
        . '<div>Длительность в часах (0 = бессрочно):<br/><input class="input" type="number" name="duration_hours" min="0" value="' . $remHours . '" style="width:92%;" /></div>'
        . '</div>'
        . '<div style="margin-bottom:8px;"><label><input type="checkbox" name="active" value="1"' . ((int)$editRow['active'] ? ' checked="checked"' : '') . ' /> Акция активна</label></div>'
        . '<input class="button" type="submit" value="' . ((int)$editRow['id'] > 0 ? 'Сохранить акцию' : 'Добавить акцию') . '" />';
    if ((int)$editRow['id'] > 0) {
        $form .= ' <a href="adm_panel.php?mod=promos">Отмена</a>';
    }
    $form .= '</form>';

    echo lin2_ui_panel((int)$editRow['id'] > 0 ? 'Редактирование акции #' . (int)$editRow['id'] : 'Создать новую акцию', $form);

    $ql = mysql_query("SELECT * FROM `lin2_promos` ORDER BY `id` DESC");
    $listHtml = '';
    if ($ql && mysql_num_rows($ql) > 0) {
        while ($r = mysql_fetch_assoc($ql)) {
            $rid = (int)$r['id'];
            $statusTag = (int)$r['active'] ? '<span style="color:#7fe084;">[Активна]</span>' : '<span style="color:#e07f7f;">[Выкл]</span>';
            $listHtml .= '<div style="padding:7px 2px;border-bottom:1px solid rgba(185,145,84,.16);">'
                . '<b>#' . $rid . ' ' . lin2_ui_h($r['title']) . '</b> ' . $statusTag
                . (!empty($r['badge']) ? ' <span class="lx-qchip">' . lin2_ui_h($r['badge']) . '</span>' : '')
                . '<div class="lx-qdesc">EXP: ' . (int)$r['exp_pct'] . '% | Адена: ' . (int)$r['adena_pct'] . '%'
                . ((int)$r['ends_at'] > 0 ? ' | до ' . date('d.m.Y H:i', (int)$r['ends_at']) : ' | бессрочно') . '</div>'
                . lin2_qp_reward_chips_html($r['gift_adena'], $r['gift_exp'], $r['gift_col'], 0)
                . '<div style="margin-top:5px;font-size:12px;">'
                . '<a href="adm_panel.php?mod=promos&edit=' . $rid . '">[Изменить]</a> &middot; '
                . '<a href="adm_panel.php?mod=promos&toggle=' . $rid . '">[' . ((int)$r['active'] ? 'Выключить' : 'Включить') . ']</a> &middot; '
                . '<a href="adm_panel.php?mod=promos&del=' . $rid . '" style="color:#ff7b7b;">[Удалить]</a>'
                . '</div></div>';
        }
    } else {
        $listHtml = '<div class="lx-qdesc">Акций пока нет.</div>';
    }
    echo lin2_ui_panel('Все акции', $listHtml);
}

// =========================================================================
// 3. УПРАВЛЕНИЕ БОНУСАМИ И ПРОМОКОДАМИ
// =========================================================================
function lin2_adm_bonuses()
{
    lin2_qp_ensure();
    echo lin2_ui_place('Админ-панель', 'Управление бонусами и промокодами');
    echo lin2_adm_nav_bar('bonuses');

    $msg = '';
    $ok = true;

    if (isset($_GET['del'])) {
        $did = (int)$_GET['del'];
        if ($did > 0) {
            mysql_query("DELETE FROM `lin2_bonuses` WHERE `id` = '$did' LIMIT 1");
            $msg = 'Бонус #' . $did . ' удалён.';
        }
    } elseif (isset($_GET['toggle'])) {
        $tid = (int)$_GET['toggle'];
        $qr = mysql_query("SELECT `active` FROM `lin2_bonuses` WHERE `id` = '$tid' LIMIT 1");
        if ($qr && mysql_num_rows($qr) > 0) {
            $next = ((int)mysql_result($qr, 0)) ? 0 : 1;
            mysql_query("UPDATE `lin2_bonuses` SET `active` = '$next' WHERE `id` = '$tid' LIMIT 1");
            $msg = 'Статус бонуса #' . $tid . ' изменён.';
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bonus'])) {
        $bid = isset($_POST['bonus_id']) ? (int)$_POST['bonus_id'] : 0;
        $title = trim((string)(isset($_POST['title']) ? $_POST['title'] : ''));
        $descr = trim((string)(isset($_POST['descr']) ? $_POST['descr'] : ''));
        $code = trim((string)(isset($_POST['code']) ? $_POST['code'] : ''));
        $cd = isset($_POST['cooldown_type']) ? (string)$_POST['cooldown_type'] : 'once';
        if ($cd !== 'once' && $cd !== 'daily' && $cd !== 'weekly') {
            $cd = 'once';
        }
        $minLvl = max(0, (int)(isset($_POST['min_lvl']) ? $_POST['min_lvl'] : 0));
        $rewAdena = max(0, (int)(isset($_POST['rew_adena']) ? $_POST['rew_adena'] : 0));
        $rewExp = max(0, (int)(isset($_POST['rew_exp']) ? $_POST['rew_exp'] : 0));
        $rewCol = max(0, (int)(isset($_POST['rew_col']) ? $_POST['rew_col'] : 0));
        $rewVip = max(0, (int)(isset($_POST['rew_vip']) ? $_POST['rew_vip'] : 0));
        $maxClaims = max(0, (int)(isset($_POST['max_claims']) ? $_POST['max_claims'] : 0));
        $active = !empty($_POST['active']) ? 1 : 0;

        if ($title === '') {
            $ok = false;
            $msg = 'Укажите название бонуса.';
        } else {
            $tEsc = mysql_real_escape_string($title);
            $dEsc = mysql_real_escape_string($descr);
            $cEsc = mysql_real_escape_string($code);
            if ($bid > 0) {
                mysql_query("UPDATE `lin2_bonuses` SET
                    `title` = '$tEsc',
                    `descr` = '$dEsc',
                    `code` = '$cEsc',
                    `cooldown_type` = '$cd',
                    `min_lvl` = '$minLvl',
                    `rew_adena` = '$rewAdena',
                    `rew_exp` = '$rewExp',
                    `rew_col` = '$rewCol',
                    `rew_vip` = '$rewVip',
                    `max_claims` = '$maxClaims',
                    `active` = '$active'
                    WHERE `id` = '$bid' LIMIT 1");
                $msg = 'Бонус #' . $bid . ' обновлён.';
            } else {
                mysql_query("INSERT INTO `lin2_bonuses` SET
                    `title` = '$tEsc',
                    `descr` = '$dEsc',
                    `code` = '$cEsc',
                    `cooldown_type` = '$cd',
                    `min_lvl` = '$minLvl',
                    `rew_adena` = '$rewAdena',
                    `rew_exp` = '$rewExp',
                    `rew_col` = '$rewCol',
                    `rew_vip` = '$rewVip',
                    `max_claims` = '$maxClaims',
                    `claims_count` = '0',
                    `ends_at` = '0',
                    `active` = '$active'");
                $msg = 'Бонус «' . $title . '» создан!';
            }
        }
    }

    if ($msg !== '') {
        echo '<div class="lx-hint" style="border-color:' . ($ok ? '#4c9e4f' : '#a84438') . ';color:' . ($ok ? '#9be89e' : '#ffb4a8') . ';">' . lin2_ui_h($msg) . '</div>';
    }

    $editRow = array(
        'id' => 0, 'title' => '', 'descr' => '', 'code' => '',
        'cooldown_type' => 'once', 'min_lvl' => 0,
        'rew_adena' => 2000, 'rew_exp' => 1000, 'rew_col' => 2, 'rew_vip' => 0,
        'max_claims' => 0, 'active' => 1,
    );
    if (isset($_GET['edit'])) {
        $eid = (int)$_GET['edit'];
        $qe = mysql_query("SELECT * FROM `lin2_bonuses` WHERE `id` = '$eid' LIMIT 1");
        if ($qe && mysql_num_rows($qe) > 0) {
            $editRow = mysql_fetch_assoc($qe);
        }
    }

    $form = '<form action="adm_panel.php?mod=bonuses" method="post">'
        . '<input type="hidden" name="save_bonus" value="1" />'
        . '<input type="hidden" name="bonus_id" value="' . (int)$editRow['id'] . '" />'
        . '<div style="margin-bottom:6px;">Название бонуса / промокода:<br/>'
        . '<input class="input" type="text" name="title" maxlength="160" value="' . lin2_ui_h($editRow['title']) . '" style="width:96%;" /></div>'
        . '<div style="margin-bottom:6px;">Описание:<br/>'
        . '<input class="input" type="text" name="descr" maxlength="255" value="' . lin2_ui_h($editRow['descr']) . '" style="width:96%;" /></div>'
        . '<div style="margin-bottom:6px;">Промокод (оставьте пустым для открытого бонуса по кнопке):<br/>'
        . '<input class="input" type="text" name="code" maxlength="64" value="' . lin2_ui_h($editRow['code']) . '" style="width:96%;" /></div>'
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:6px;">'
        . '<div>Периодичность:<br/><select name="cooldown_type">'
        . '<option value="once"' . ($editRow['cooldown_type'] === 'once' ? ' selected="selected"' : '') . '>Один раз</option>'
        . '<option value="daily"' . ($editRow['cooldown_type'] === 'daily' ? ' selected="selected"' : '') . '>Раз в день</option>'
        . '<option value="weekly"' . ($editRow['cooldown_type'] === 'weekly' ? ' selected="selected"' : '') . '>Раз в неделю</option>'
        . '</select></div>'
        . '<div>Мин. уровень:<br/><input class="input" type="number" name="min_lvl" min="0" value="' . (int)$editRow['min_lvl'] . '" style="width:92%;" /></div>'
        . '<div>Награда Адена:<br/><input class="input" type="number" name="rew_adena" min="0" value="' . (int)$editRow['rew_adena'] . '" style="width:92%;" /></div>'
        . '<div>Награда EXP:<br/><input class="input" type="number" name="rew_exp" min="0" value="' . (int)$editRow['rew_exp'] . '" style="width:92%;" /></div>'
        . '<div>Награда CoL:<br/><input class="input" type="number" name="rew_col" min="0" value="' . (int)$editRow['rew_col'] . '" style="width:92%;" /></div>'
        . '<div>Награда Gold Bar:<br/><input class="input" type="number" name="rew_vip" min="0" value="' . (int)$editRow['rew_vip'] . '" style="width:92%;" /></div>'
        . '<div>Лимит активаций (0 = без лимита):<br/><input class="input" type="number" name="max_claims" min="0" value="' . (int)$editRow['max_claims'] . '" style="width:92%;" /></div>'
        . '<div style="display:flex;align-items:end;padding-bottom:4px;"><label><input type="checkbox" name="active" value="1"' . ((int)$editRow['active'] ? ' checked="checked"' : '') . ' /> Активен</label></div>'
        . '</div>'
        . '<input class="button" type="submit" value="' . ((int)$editRow['id'] > 0 ? 'Сохранить бонус' : 'Добавить бонус') . '" />';
    if ((int)$editRow['id'] > 0) {
        $form .= ' <a href="adm_panel.php?mod=bonuses">Отмена</a>';
    }
    $form .= '</form>';

    echo lin2_ui_panel((int)$editRow['id'] > 0 ? 'Редактирование бонуса #' . (int)$editRow['id'] : 'Добавить бонус или промокод', $form);

    $ql = mysql_query("SELECT * FROM `lin2_bonuses` ORDER BY `id` DESC");
    $listHtml = '';
    if ($ql && mysql_num_rows($ql) > 0) {
        while ($r = mysql_fetch_assoc($ql)) {
            $rid = (int)$r['id'];
            $statusTag = (int)$r['active'] ? '<span style="color:#7fe084;">[Активен]</span>' : '<span style="color:#e07f7f;">[Выкл]</span>';
            $codeTag = ($r['code'] !== '')
                ? '<span class="lx-qchip">Код: ' . lin2_ui_h($r['code']) . '</span>'
                : '<span class="lx-qchip">Открытый бонус</span>';
            $listHtml .= '<div style="padding:7px 2px;border-bottom:1px solid rgba(185,145,84,.16);">'
                . '<b>#' . $rid . ' ' . lin2_ui_h($r['title']) . '</b> ' . $statusTag . ' ' . $codeTag
                . '<div class="lx-qdesc">Режим: ' . lin2_ui_h($r['cooldown_type'])
                . ' | Активаций: ' . (int)$r['claims_count'] . ((int)$r['max_claims'] > 0 ? ' / ' . (int)$r['max_claims'] : '') . '</div>'
                . lin2_qp_reward_chips_html($r['rew_adena'], $r['rew_exp'], $r['rew_col'], $r['rew_vip'])
                . '<div style="margin-top:5px;font-size:12px;">'
                . '<a href="adm_panel.php?mod=bonuses&edit=' . $rid . '">[Изменить]</a> &middot; '
                . '<a href="adm_panel.php?mod=bonuses&toggle=' . $rid . '">[' . ((int)$r['active'] ? 'Выключить' : 'Включить') . ']</a> &middot; '
                . '<a href="adm_panel.php?mod=bonuses&del=' . $rid . '" style="color:#ff7b7b;">[Удалить]</a>'
                . '</div></div>';
        }
    } else {
        $listHtml = '<div class="lx-qdesc">Бонусов пока нет.</div>';
    }
    echo lin2_ui_panel('Все бонусы и промокоды', $listHtml);
}

// =========================================================================
// 4. ЗАМЕНА ЛЮБЫХ ИЗОБРАЖЕНИЙ НА САЙТЕ (adm_panel.php?mod=images)
// =========================================================================
function lin2_adm_valid_img_rel($rel)
{
    $rel = ltrim(str_replace('\\', '/', trim((string)$rel)), '/');
    if ($rel === '' || strpos($rel, "\0") !== false || strpos($rel, '..') !== false) {
        return false;
    }
    if (!preg_match('~\.(png|jpe?g|gif|webp)$~i', $rel)) {
        return false;
    }
    $firstSeg = explode('/', $rel)[0];
    $blocked = array('.git', 'node_modules', 'tools', 'inc', 'Unselected files');
    if (in_array($firstSeg, $blocked, true)) {
        return false;
    }
    return $rel;
}

function lin2_adm_is_valid_raster_file($path)
{
    if (!is_file($path) || filesize($path) < 8) {
        return false;
    }
    if (function_exists('getimagesize')) {
        $info = @getimagesize($path);
        if ($info && in_array((int)$info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP), true)) {
            return true;
        }
    }
    $fh = @fopen($path, 'rb');
    if (!$fh) {
        return false;
    }
    $head = (string)@fread($fh, 12);
    @fclose($fh);
    if (strncmp($head, "\x89PNG\r\n\x1a\n", 8) === 0) {
        return true;
    }
    if (strncmp($head, "\xFF\xD8\xFF", 3) === 0) {
        return true;
    }
    if (strncmp($head, "GIF87a", 6) === 0 || strncmp($head, "GIF89a", 6) === 0) {
        return true;
    }
    if (strncmp($head, "RIFF", 4) === 0 && substr($head, 8, 4) === "WEBP") {
        return true;
    }
    return false;
}

function lin2_adm_scan_images($root)
{
    $skipDirs = array('.git', 'node_modules', 'tools', 'inc', 'Unselected files');
    $results = array();
    $stack = array('');

    while (!empty($stack)) {
        $relDir = array_pop($stack);
        $absDir = ($relDir === '') ? $root : ($root . '/' . $relDir);
        $entries = @scandir($absDir);
        if (!is_array($entries)) {
            continue;
        }
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            if ($relDir === '' && in_array($e, $skipDirs, true)) {
                continue;
            }
            $relPath = ($relDir === '') ? $e : ($relDir . '/' . $e);
            $absPath = $root . '/' . $relPath;
            if (is_dir($absPath) && !is_link($absPath)) {
                $stack[] = $relPath;
            } elseif (is_file($absPath) && preg_match('~\.(png|jpe?g|gif|webp)$~i', $e)) {
                $results[] = $relPath;
            }
        }
    }
    sort($results, SORT_STRING);
    return $results;
}

function lin2_adm_images()
{
    $root = lin2_ui_root();
    echo lin2_ui_place('Админ-панель', 'Замена изображений сайта');
    echo lin2_adm_nav_bar('images');

    $msg = '';
    $ok = true;

    // Восстановление резервной копии
    if (isset($_GET['restore'])) {
        $rel = lin2_adm_valid_img_rel($_GET['restore']);
        if ($rel !== false) {
            $abs = $root . '/' . $rel;
            $bak = $abs . '.lin2bak';
            if (is_file($bak)) {
                @copy($bak, $abs);
                @unlink($bak);
                if (strpos($rel, 'theme/') === 0 && is_file($root . '/theme/fantasy.css')) {
                    @touch($root . '/theme/fantasy.css');
                }
                $msg = 'Оригинальное изображение «' . $rel . '» восстановлено!';
            } else {
                $ok = false;
                $msg = 'Резервная копия для «' . $rel . '» не найдена.';
            }
        }
    }

    // Загрузка / замена файла
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_path'])) {
        $rel = lin2_adm_valid_img_rel($_POST['target_path']);
        if ($rel === false) {
            $ok = false;
            $msg = 'Недопустимый путь файла. Разрешены только растровые форматы (.png, .jpg, .jpeg, .gif, .webp) внутри игровых папок.';
        } elseif (empty($_FILES['new_img']) || (int)$_FILES['new_img']['error'] !== UPLOAD_ERR_OK) {
            $ok = false;
            $msg = 'Выберите файл изображения для загрузки.';
        } else {
            $tmp = $_FILES['new_img']['tmp_name'];
            if (!lin2_adm_is_valid_raster_file($tmp)) {
                $ok = false;
                $msg = 'Файл не является корректным растровым изображением (PNG, JPG, GIF, WEBP).';
            } else {
                $dest = $root . '/' . $rel;
                $destDir = dirname($dest);
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0775, true);
                }
                // Сохраняем резервную копию оригинала при первой замене
                if (is_file($dest) && !is_file($dest . '.lin2bak')) {
                    @copy($dest, $dest . '.lin2bak');
                }
                $moved = @move_uploaded_file($tmp, $dest);
                if (!$moved && is_file($tmp)) {
                    $moved = @copy($tmp, $dest);
                }
                if ($moved) {
                    @chmod($dest, 0664);
                    @touch($dest);
                    if (strpos($rel, 'theme/') === 0 && is_file($root . '/theme/fantasy.css')) {
                        @touch($root . '/theme/fantasy.css');
                    }
                    $msg = 'Изображение «' . $rel . '» успешно обновлено!';
                } else {
                    $ok = false;
                    $msg = 'Не удалось сохранить файл «' . $rel . '». Проверьте права записи.';
                }
            }
        }
    }

    if ($msg !== '') {
        echo '<div class="lx-hint" style="border-color:' . ($ok ? '#4c9e4f' : '#a84438') . ';color:' . ($ok ? '#9be89e' : '#ffb4a8') . ';">' . lin2_ui_h($msg) . '</div>';
    }

    $allImages = lin2_adm_scan_images($root);
    $folders = array();
    foreach ($allImages as $imgRel) {
        $d = dirname($imgRel);
        if ($d === '.') {
            $d = '(корень сайта)';
        }
        $folders[$d] = isset($folders[$d]) ? ($folders[$d] + 1) : 1;
    }
    ksort($folders);

    $selDir = isset($_GET['dir']) ? trim((string)$_GET['dir']) : '';
    $search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;

    // Форма быстрой загрузки по любому пути
    $upForm = '<form action="adm_panel.php?mod=images&dir=' . urlencode($selDir) . '&q=' . urlencode($search) . '" method="post" enctype="multipart/form-data">'
        . '<div style="margin-bottom:6px;">Относительный путь на сайте (например, <code>theme/fantasy/logo.png</code> или <code>pic/town/гиран.png</code>):<br/>'
        . '<input class="input" type="text" name="target_path" placeholder="theme/fantasy/logo.png" style="width:96%;" /></div>'
        . '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">'
        . '<input type="file" name="new_img" accept="image/png,image/jpeg,image/gif,image/webp" />'
        . '<input class="button" type="submit" value="Загрузить / Заменить" />'
        . '</div></form>';
    echo lin2_ui_panel('Загрузить или заменить по пути', $upForm);

    // Фильтр по папкам и поиск
    $filterForm = '<form action="adm_panel.php" method="get" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">'
        . '<input type="hidden" name="mod" value="images" />'
        . '<select name="dir"><option value="">Все папки (' . count($allImages) . ')</option>';
    foreach ($folders as $fName => $fCnt) {
        $sel = ($selDir === $fName) ? ' selected="selected"' : '';
        $filterForm .= '<option value="' . lin2_ui_h($fName) . '"' . $sel . '>' . lin2_ui_h($fName) . ' (' . $fCnt . ')</option>';
    }
    $filterForm .= '</select>'
        . '<input class="input" type="text" name="q" value="' . lin2_ui_h($search) . '" placeholder="Поиск по имени файла..." style="flex:1;min-width:130px;" />'
        . '<input class="button" type="submit" value="Найти" />'
        . '</form>';
    echo lin2_ui_panel('Фильтр изображений', $filterForm);

    $filtered = array();
    foreach ($allImages as $imgRel) {
        $d = dirname($imgRel);
        if ($d === '.') {
            $d = '(корень сайта)';
        }
        if ($selDir !== '' && $d !== $selDir) {
            continue;
        }
        if ($search !== '' && mb_stripos($imgRel, $search, 0, 'UTF-8') === false) {
            continue;
        }
        $filtered[] = $imgRel;
    }

    $perPage = 30;
    $totalFiltered = count($filtered);
    $totalPages = max(1, (int)ceil($totalFiltered / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $slice = array_slice($filtered, ($page - 1) * $perPage, $perPage);

    $grid = '<div class="lx-img-grid">';
    foreach ($slice as $rel) {
        $abs = $root . '/' . $rel;
        $mtime = @filemtime($abs);
        $sizeKb = round(@filesize($abs) / 1024, 1);
        $dims = '';
        if (function_exists('getimagesize')) {
            $inf = @getimagesize($abs);
            if ($inf) {
                $dims = (int)$inf[0] . 'x' . (int)$inf[1] . ' &middot; ';
            }
        }
        $parts = array_map('rawurlencode', explode('/', $rel));
        $url = '/' . implode('/', $parts) . '?v=' . (int)$mtime;
        $hasBak = is_file($abs . '.lin2bak');

        $grid .= '<div class="lx-img-card">'
            . '<div class="lx-img-prev"><img src="' . lin2_ui_h($url) . '" alt="" loading="lazy" /></div>'
            . '<div class="lx-img-path">' . lin2_ui_h($rel) . '<br/><span style="color:#8d7a5e;">' . $dims . $sizeKb . ' КБ</span></div>'
            . '<form action="adm_panel.php?mod=images&dir=' . urlencode($selDir) . '&q=' . urlencode($search) . '&p=' . $page . '" method="post" enctype="multipart/form-data">'
            . '<input type="hidden" name="target_path" value="' . lin2_ui_h($rel) . '" />'
            . '<input type="file" name="new_img" accept="image/png,image/jpeg,image/gif,image/webp" style="width:100%;font-size:10px;margin-bottom:4px;" />'
            . '<input class="button" type="submit" value="Заменить" style="width:100%;" />'
            . '</form>';
        if ($hasBak) {
            $grid .= '<a href="adm_panel.php?mod=images&dir=' . urlencode($selDir) . '&q=' . urlencode($search) . '&p=' . $page . '&restore=' . urlencode($rel) . '" style="font-size:11px;color:#f4d06e;text-align:center;">&#8634; Вернуть оригинал</a>';
        }
        $grid .= '</div>';
    }
    $grid .= '</div>';

    if ($totalFiltered === 0) {
        $grid = '<div class="lx-qdesc">По вашему запросу изображения не найдены.</div>';
    } elseif ($totalPages > 1) {
        $grid .= '<div style="margin-top:10px;text-align:center;font-size:12px;">Страница ' . $page . ' из ' . $totalPages . ': ';
        if ($page > 1) {
            $grid .= '<a href="adm_panel.php?mod=images&dir=' . urlencode($selDir) . '&q=' . urlencode($search) . '&p=' . ($page - 1) . '">&laquo; Назад</a> ';
        }
        if ($page < $totalPages) {
            $grid .= '<a href="adm_panel.php?mod=images&dir=' . urlencode($selDir) . '&q=' . urlencode($search) . '&p=' . ($page + 1) . '">Далее &raquo;</a>';
        }
        $grid .= '</div>';
    }

    echo lin2_ui_panel('Каталог изображений (' . $totalFiltered . ')', $grid);
}
