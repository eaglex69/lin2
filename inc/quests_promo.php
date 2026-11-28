<?php
/**
 * Система ежедневных / еженедельных заданий, акций и бонусов (промокодов).
 * Используется в job.php, promo.php, adm_panel.php и игровых обработчиках.
 */

if (!function_exists('lin2_qp_metrics')) {

function lin2_qp_metrics()
{
    return array(
        'daily_visit' => 'Вход в игру (уникальных дней)',
        'mob_kill'    => 'Убить любых монстров',
        'mob_strong'  => 'Убить сильных монстров (20+ ур. / боссов)',
        'fish_catch'  => 'Поймать рыбу на озере',
        'pvp_win'     => 'Победы в PvP и на арене',
        'baf_use'     => 'Получить благословение бафера',
        'shop_buy'    => 'Покупки в городских магазинах',
        'adena_farm'  => 'Добыть Адену в боях с монстрами',
        'chat_msg'    => 'Сообщения в чате',
        'online_min'  => 'Провести минут в игре',
    );
}

function lin2_qp_daily_key()
{
    return 'd:' . date('Y-m-d');
}

function lin2_qp_weekly_key()
{
    return 'w:' . date('o-W');
}

function lin2_qp_period_key($period)
{
    return ($period === 'weekly') ? lin2_qp_weekly_key() : lin2_qp_daily_key();
}

function lin2_qp_ensure()
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;

    mysql_query("CREATE TABLE IF NOT EXISTS `lin2_qp_meta` (
  `k` varchar(40) NOT NULL DEFAULT '',
  `v` varchar(120) NOT NULL DEFAULT '',
  PRIMARY KEY (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    mysql_query("CREATE TABLE IF NOT EXISTS `lin2_quests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period` varchar(16) NOT NULL DEFAULT 'daily',
  `title` varchar(160) NOT NULL DEFAULT '',
  `descr` varchar(255) NOT NULL DEFAULT '',
  `metric` varchar(40) NOT NULL DEFAULT 'mob_kill',
  `target` bigint(20) NOT NULL DEFAULT '1',
  `min_lvl` int(11) NOT NULL DEFAULT '0',
  `rew_adena` bigint(20) NOT NULL DEFAULT '0',
  `rew_exp` bigint(20) NOT NULL DEFAULT '0',
  `rew_col` int(11) NOT NULL DEFAULT '0',
  `rew_vip` int(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

    mysql_query("CREATE TABLE IF NOT EXISTS `lin2_quest_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usr` varchar(40) NOT NULL DEFAULT '',
  `period_key` varchar(24) NOT NULL DEFAULT '',
  `metric` varchar(40) NOT NULL DEFAULT '',
  `val` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

    mysql_query("CREATE TABLE IF NOT EXISTS `lin2_quest_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usr` varchar(40) NOT NULL DEFAULT '',
  `quest_id` int(11) NOT NULL DEFAULT '0',
  `period_key` varchar(24) NOT NULL DEFAULT '',
  `claimed_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

    mysql_query("CREATE TABLE IF NOT EXISTS `lin2_promos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(160) NOT NULL DEFAULT '',
  `descr` text NOT NULL,
  `badge` varchar(64) NOT NULL DEFAULT '',
  `exp_pct` int(11) NOT NULL DEFAULT '100',
  `adena_pct` int(11) NOT NULL DEFAULT '100',
  `gift_adena` bigint(20) NOT NULL DEFAULT '0',
  `gift_exp` bigint(20) NOT NULL DEFAULT '0',
  `gift_col` int(11) NOT NULL DEFAULT '0',
  `starts_at` int(11) NOT NULL DEFAULT '0',
  `ends_at` int(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

    mysql_query("CREATE TABLE IF NOT EXISTS `lin2_bonuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(160) NOT NULL DEFAULT '',
  `descr` varchar(255) NOT NULL DEFAULT '',
  `code` varchar(64) NOT NULL DEFAULT '',
  `cooldown_type` varchar(16) NOT NULL DEFAULT 'once',
  `min_lvl` int(11) NOT NULL DEFAULT '0',
  `rew_adena` bigint(20) NOT NULL DEFAULT '0',
  `rew_exp` bigint(20) NOT NULL DEFAULT '0',
  `rew_col` int(11) NOT NULL DEFAULT '0',
  `rew_vip` int(11) NOT NULL DEFAULT '0',
  `max_claims` int(11) NOT NULL DEFAULT '0',
  `claims_count` int(11) NOT NULL DEFAULT '0',
  `ends_at` int(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

    mysql_query("CREATE TABLE IF NOT EXISTS `lin2_bonus_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usr` varchar(40) NOT NULL DEFAULT '',
  `bonus_id` int(11) NOT NULL DEFAULT '0',
  `claim_key` varchar(40) NOT NULL DEFAULT '',
  `claimed_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");

    $qm = mysql_query("SELECT `v` FROM `lin2_qp_meta` WHERE `k` = 'seeded_v1' LIMIT 1");
    if ($qm && mysql_num_rows($qm) > 0) {
        return;
    }

    // Проверяем реальное количество квестов, чтобы не дублировать при повторном вызове
    $qc = mysql_query("SELECT COUNT(*) FROM `lin2_quests`");
    $cnt = $qc ? (int)mysql_result($qc, 0) : 0;
    if ($cnt === 0) {
        $defaults = array(
            // 10 ежедневных заданий
            array('daily', 'Ежедневный дозор', 'Зайти в игру и отметиться у городской стражи.', 'daily_visit', 1, 0, 1500, 500, 1, 0, 10),
            array('daily', 'Первая охота', 'Одолеть 10 любых монстров в окрестностях.', 'mob_kill', 10, 0, 5000, 2500, 1, 0, 20),
            array('daily', 'Зачистка угодий', 'Истребить 50 монстров за текущий день.', 'mob_kill', 50, 5, 25000, 15000, 3, 0, 30),
            array('daily', 'Гроза бестий', 'Победить 150 монстров за день.', 'mob_kill', 150, 15, 80000, 60000, 5, 0, 40),
            array('daily', 'Охота на вожаков', 'Сразить 15 опасных монстров (20+ уровня или боссов).', 'mob_strong', 15, 10, 45000, 35000, 3, 0, 50),
            array('daily', 'Свежий улов', 'Выловить 10 рыб на городском озере.', 'fish_catch', 10, 0, 15000, 8000, 2, 0, 60),
            array('daily', 'Испытание арены', 'Одержать 3 победы в PvP или на арене.', 'pvp_win', 3, 10, 40000, 25000, 4, 0, 70),
            array('daily', 'Благословение храма', 'Воспользоваться услугами бафера в городе.', 'baf_use', 1, 0, 5000, 3000, 1, 0, 80),
            array('daily', 'Поход за припасами', 'Совершить покупку снаряжения, зарядов или эликсиров в лавке.', 'shop_buy', 1, 0, 8000, 4000, 1, 0, 90),
            array('daily', 'Трофейная казна', 'Добыть 25 000 Адены в сражениях с монстрами.', 'adena_farm', 25000, 0, 15000, 12000, 2, 0, 100),

            // 10 еженедельных заданий
            array('weekly', 'Верный защитник Эльморадена', 'Заходить в игру в течение 5 разных дней на этой неделе.', 'daily_visit', 5, 0, 50000, 40000, 10, 0, 10),
            array('weekly', 'Истребитель чудовищ', 'Победить 500 монстров в течение недели.', 'mob_kill', 500, 5, 250000, 200000, 15, 0, 20),
            array('weekly', 'Легендарный каратель', 'Уничтожить 1 500 монстров за неделю.', 'mob_kill', 1500, 20, 900000, 750000, 35, 1, 30),
            array('weekly', 'Покоритель глубин и чащ', 'Сразить 100 сильных монстров (20+ уровня или боссов) за неделю.', 'mob_strong', 100, 15, 400000, 300000, 20, 0, 40),
            array('weekly', 'Мастер рыбного промысла', 'Выловить 100 рыб на озере за неделю.', 'fish_catch', 100, 0, 180000, 120000, 15, 0, 50),
            array('weekly', 'Чемпион гладиаторов', 'Одержать 20 побед в PvP или на арене за неделю.', 'pvp_win', 20, 15, 500000, 350000, 25, 1, 60),
            array('weekly', 'Хранитель священных печатей', 'Получить благословение бафера 10 раз за неделю.', 'baf_use', 10, 0, 75000, 50000, 8, 0, 70),
            array('weekly', 'Щедрый купец', 'Совершить 10 покупок в городских магазинах за неделю.', 'shop_buy', 10, 0, 120000, 80000, 10, 0, 80),
            array('weekly', 'Золотая лихорадка', 'Добыть 500 000 Адены на охоте за неделю.', 'adena_farm', 500000, 10, 250000, 200000, 18, 0, 90),
            array('weekly', 'Душа компании', 'Отправить 15 сообщений в игровой, торговый или клановый чат за неделю.', 'chat_msg', 15, 0, 60000, 45000, 8, 0, 100),
        );

        foreach ($defaults as $d) {
            $per = mysql_real_escape_string($d[0]);
            $tit = mysql_real_escape_string($d[1]);
            $dsc = mysql_real_escape_string($d[2]);
            $met = mysql_real_escape_string($d[3]);
            mysql_query("INSERT INTO `lin2_quests` SET
                `period` = '$per',
                `title` = '$tit',
                `descr` = '$dsc',
                `metric` = '$met',
                `target` = '" . (int)$d[4] . "',
                `min_lvl` = '" . (int)$d[5] . "',
                `rew_adena` = '" . (int)$d[6] . "',
                `rew_exp` = '" . (int)$d[7] . "',
                `rew_col` = '" . (int)$d[8] . "',
                `rew_vip` = '" . (int)$d[9] . "',
                `active` = '1',
                `sort_order` = '" . (int)$d[10] . "'");
        }
    }

    $qp = mysql_query("SELECT COUNT(*) FROM `lin2_promos`");
    $pcnt = $qp ? (int)mysql_result($qp, 0) : 0;
    if ($pcnt === 0) {
        mysql_query("INSERT INTO `lin2_promos` SET
            `title` = 'Благословение Эльморадена',
            `descr` = 'Повышенные рейты опыта и адены в охоте на монстров, а также приветственный дар для каждого героя!',
            `badge` = 'Рейты x1.5',
            `exp_pct` = '150',
            `adena_pct` = '150',
            `gift_adena` = '2500',
            `gift_exp` = '1000',
            `gift_col` = '2',
            `starts_at` = '0',
            `ends_at` = '0',
            `active` = '1'");
    }

    $qb = mysql_query("SELECT COUNT(*) FROM `lin2_bonuses`");
    $bcnt = $qb ? (int)mysql_result($qb, 0) : 0;
    if ($bcnt === 0) {
        mysql_query("INSERT INTO `lin2_bonuses` SET
            `title` = 'Ежедневный сундук снабжения',
            `descr` = 'Бесплатный ежедневный набор путешественника: адена, опыт и Coin of Luck.',
            `code` = '',
            `cooldown_type` = 'daily',
            `min_lvl` = '0',
            `rew_adena` = '1000',
            `rew_exp` = '500',
            `rew_col` = '1',
            `rew_vip` = '0',
            `max_claims` = '0',
            `claims_count` = '0',
            `ends_at` = '0',
            `active` = '1'");

        mysql_query("INSERT INTO `lin2_bonuses` SET
            `title` = 'Еженедельный дар Гирана',
            `descr` = 'Еженедельный бонус гильдии торговцев для героев с 5 уровня.',
            `code` = '',
            `cooldown_type` = 'weekly',
            `min_lvl` = '5',
            `rew_adena` = '15000',
            `rew_exp` = '10000',
            `rew_col` = '5',
            `rew_vip` = '0',
            `max_claims` = '0',
            `claims_count` = '0',
            `ends_at` = '0',
            `active` = '1'");

        mysql_query("INSERT INTO `lin2_bonuses` SET
            `title` = 'Стартовый промокод героя',
            `descr` = 'Введите промокод START2026 для получения стартовой поддержки.',
            `code` = 'START2026',
            `cooldown_type` = 'once',
            `min_lvl` = '0',
            `rew_adena` = '5000',
            `rew_exp` = '2500',
            `rew_col` = '3',
            `rew_vip` = '0',
            `max_claims` = '0',
            `claims_count` = '0',
            `ends_at` = '0',
            `active` = '1'");
    }

    mysql_query("INSERT INTO `lin2_qp_meta` SET `k` = 'seeded_v1', `v` = '" . time() . "'");
}

function lin2_qp_inc_raw($usr, $periodKey, $metric, $delta)
{
    $delta = (int)$delta;
    if ($usr === '' || $delta <= 0) {
        return;
    }
    $u = mysql_real_escape_string($usr);
    $pk = mysql_real_escape_string($periodKey);
    $m = mysql_real_escape_string($metric);

    $q = mysql_query("SELECT `id`, `val` FROM `lin2_quest_counters` WHERE `usr` = '$u' AND `period_key` = '$pk' AND `metric` = '$m' LIMIT 1");
    if ($q && mysql_num_rows($q) > 0) {
        $row = mysql_fetch_assoc($q);
        $nv = (int)$row['val'] + $delta;
        mysql_query("UPDATE `lin2_quest_counters` SET `val` = '$nv' WHERE `id` = '" . (int)$row['id'] . "' LIMIT 1");
    } else {
        mysql_query("INSERT INTO `lin2_quest_counters` SET `usr` = '$u', `period_key` = '$pk', `metric` = '$m', `val` = '$delta'");
    }
}

function lin2_qp_touch_daily_visit($usr)
{
    if (empty($usr)) {
        return;
    }
    lin2_qp_ensure();
    $u = mysql_real_escape_string($usr);
    $dk = mysql_real_escape_string(lin2_qp_daily_key());
    $q = mysql_query("SELECT `id`, `val` FROM `lin2_quest_counters` WHERE `usr` = '$u' AND `period_key` = '$dk' AND `metric` = 'daily_visit' LIMIT 1");
    if ($q && mysql_num_rows($q) > 0) {
        $r = mysql_fetch_assoc($q);
        if ((int)$r['val'] >= 1) {
            return;
        }
        mysql_query("UPDATE `lin2_quest_counters` SET `val` = '1' WHERE `id` = '" . (int)$r['id'] . "' LIMIT 1");
    } else {
        mysql_query("INSERT INTO `lin2_quest_counters` SET `usr` = '$u', `period_key` = '$dk', `metric` = 'daily_visit', `val` = '1'");
    }
    // Учитываем уникальный день входа в еженедельном счетчике
    lin2_qp_inc_raw($usr, lin2_qp_weekly_key(), 'daily_visit', 1);
}

function lin2_qp_progress($usr, $metric, $delta = 1)
{
    if (empty($usr) || $delta <= 0) {
        return;
    }
    lin2_qp_ensure();
    if ($metric === 'daily_visit') {
        lin2_qp_touch_daily_visit($usr);
        return;
    }
    lin2_qp_inc_raw($usr, lin2_qp_daily_key(), $metric, $delta);
    lin2_qp_inc_raw($usr, lin2_qp_weekly_key(), $metric, $delta);
}

function lin2_qp_counters_map($usr, $periodKey)
{
    lin2_qp_ensure();
    $map = array();
    if (empty($usr)) {
        return $map;
    }
    $u = mysql_real_escape_string($usr);
    $pk = mysql_real_escape_string($periodKey);
    $q = mysql_query("SELECT `metric`, `val` FROM `lin2_quest_counters` WHERE `usr` = '$u' AND `period_key` = '$pk'");
    if ($q) {
        while ($r = mysql_fetch_assoc($q)) {
            $map[$r['metric']] = (int)$r['val'];
        }
    }
    return $map;
}

function lin2_qp_claims_map($usr, $periodKey)
{
    lin2_qp_ensure();
    $map = array();
    if (empty($usr)) {
        return $map;
    }
    $u = mysql_real_escape_string($usr);
    $pk = mysql_real_escape_string($periodKey);
    $q = mysql_query("SELECT `quest_id` FROM `lin2_quest_claims` WHERE `usr` = '$u' AND `period_key` = '$pk'");
    if ($q) {
        while ($r = mysql_fetch_assoc($q)) {
            $map[(int)$r['quest_id']] = true;
        }
    }
    return $map;
}

function lin2_qp_grant_rewards($usr, $adena, $exp, $col, $gold = 0)
{
    $u = mysql_real_escape_string($usr);
    $qu = mysql_query("SELECT `money`, `exp`, `almaz`, `gold` FROM `users` WHERE `usr` = '$u' LIMIT 1");
    if (!$qu || mysql_num_rows($qu) === 0) {
        return false;
    }
    $ud = mysql_fetch_assoc($qu);
    $nm = (int)$ud['money'] + (int)$adena;
    $ne = (int)$ud['exp'] + (int)$exp;
    $nc = (int)$ud['almaz'] + (int)$col;
    $ng = (int)$ud['gold'] + (int)$gold;
    mysql_query("UPDATE `users` SET `money` = '$nm', `exp` = '$ne', `almaz` = '$nc', `gold` = '$ng' WHERE `usr` = '$u' LIMIT 1");
    return true;
}

function lin2_qp_reward_summary($adena, $exp, $col, $gold = 0)
{
    $parts = array();
    if ((int)$adena > 0) {
        $parts[] = '+' . number_format((int)$adena, 0, '', ' ') . ' Адена';
    }
    if ((int)$exp > 0) {
        $parts[] = '+' . number_format((int)$exp, 0, '', ' ') . ' EXP';
    }
    if ((int)$col > 0) {
        $parts[] = '+' . number_format((int)$col, 0, '', ' ') . ' CoL';
    }
    if ((int)$gold > 0) {
        $parts[] = '+' . number_format((int)$gold, 0, '', ' ') . ' Gold Bar';
    }
    return empty($parts) ? 'Почетная отметка' : implode(', ', $parts);
}

function lin2_qp_reward_chips_html($adena, $exp, $col, $gold = 0)
{
    $out = '<div class="lx-qrew">';
    if ((int)$adena > 0) {
        $out .= '<span class="lx-qchip">+' . number_format((int)$adena, 0, '', ' ') . ' Адена</span>';
    }
    if ((int)$exp > 0) {
        $out .= '<span class="lx-qchip">+' . number_format((int)$exp, 0, '', ' ') . ' EXP</span>';
    }
    if ((int)$col > 0) {
        $out .= '<span class="lx-qchip">+' . number_format((int)$col, 0, '', ' ') . ' CoL</span>';
    }
    if ((int)$gold > 0) {
        $out .= '<span class="lx-qchip">+' . number_format((int)$gold, 0, '', ' ') . ' Gold Bar</span>';
    }
    $out .= '</div>';
    return $out;
}

function lin2_qp_claim_quest($usr, $questId)
{
    lin2_qp_ensure();
    $qid = (int)$questId;
    if (empty($usr) || $qid <= 0) {
        return array(false, 'Задание не найдено.');
    }
    $qq = mysql_query("SELECT * FROM `lin2_quests` WHERE `id` = '$qid' AND `active` = '1' LIMIT 1");
    if (!$qq || mysql_num_rows($qq) === 0) {
        return array(false, 'Задание недоступно или отключено.');
    }
    $quest = mysql_fetch_assoc($qq);
    $u = mysql_real_escape_string($usr);
    $qu = mysql_query("SELECT `lvl` FROM `users` WHERE `usr` = '$u' LIMIT 1");
    if (!$qu || mysql_num_rows($qu) === 0) {
        return array(false, 'Персонаж не найден.');
    }
    $urow = mysql_fetch_assoc($qu);
    if ((int)$urow['lvl'] < (int)$quest['min_lvl']) {
        return array(false, 'Требуется ' . (int)$quest['min_lvl'] . ' уровень.');
    }

    $pk = lin2_qp_period_key($quest['period']);
    $pke = mysql_real_escape_string($pk);
    $qcl = mysql_query("SELECT `id` FROM `lin2_quest_claims` WHERE `usr` = '$u' AND `quest_id` = '$qid' AND `period_key` = '$pke' LIMIT 1");
    if ($qcl && mysql_num_rows($qcl) > 0) {
        return array(false, 'Награда за это задание уже получена в текущем периоде.');
    }

    $met = mysql_real_escape_string($quest['metric']);
    $qcnt = mysql_query("SELECT `val` FROM `lin2_quest_counters` WHERE `usr` = '$u' AND `period_key` = '$pke' AND `metric` = '$met' LIMIT 1");
    $cur = ($qcnt && mysql_num_rows($qcnt) > 0) ? (int)mysql_result($qcnt, 0) : 0;
    if ($cur < (int)$quest['target']) {
        return array(false, 'Задание еще не выполнено (' . $cur . ' / ' . (int)$quest['target'] . ').');
    }

    mysql_query("INSERT INTO `lin2_quest_claims` SET `usr` = '$u', `quest_id` = '$qid', `period_key` = '$pke', `claimed_at` = '" . time() . "'");
    lin2_qp_grant_rewards($usr, $quest['rew_adena'], $quest['rew_exp'], $quest['rew_col'], $quest['rew_vip']);

    return array(true, 'Задание «' . $quest['title'] . '» завершено! Получено: ' . lin2_qp_reward_summary($quest['rew_adena'], $quest['rew_exp'], $quest['rew_col'], $quest['rew_vip']));
}

function lin2_qp_ready_quest_count($usr, $lvl)
{
    if (empty($usr)) {
        return 0;
    }
    lin2_qp_ensure();
    $dCounters = lin2_qp_counters_map($usr, lin2_qp_daily_key());
    $wCounters = lin2_qp_counters_map($usr, lin2_qp_weekly_key());
    $dClaims = lin2_qp_claims_map($usr, lin2_qp_daily_key());
    $wClaims = lin2_qp_claims_map($usr, lin2_qp_weekly_key());

    $ready = 0;
    $q = mysql_query("SELECT `id`, `period`, `metric`, `target`, `min_lvl` FROM `lin2_quests` WHERE `active` = '1'");
    if ($q) {
        while ($r = mysql_fetch_assoc($q)) {
            if ((int)$lvl < (int)$r['min_lvl']) {
                continue;
            }
            $qid = (int)$r['id'];
            $isWeekly = ($r['period'] === 'weekly');
            $claims = $isWeekly ? $wClaims : $dClaims;
            if (!empty($claims[$qid])) {
                continue;
            }
            $counters = $isWeekly ? $wCounters : $dCounters;
            $cur = isset($counters[$r['metric']]) ? (int)$counters[$r['metric']] : 0;
            if ($cur >= (int)$r['target']) {
                $ready++;
            }
        }
    }
    return $ready;
}

function lin2_qp_active_promos()
{
    lin2_qp_ensure();
    $now = time();
    $list = array();
    $q = mysql_query("SELECT * FROM `lin2_promos` WHERE `active` = '1' ORDER BY `id` DESC");
    if ($q) {
        while ($r = mysql_fetch_assoc($q)) {
            if ((int)$r['starts_at'] > 0 && (int)$r['starts_at'] > $now) {
                continue;
            }
            if ((int)$r['ends_at'] > 0 && (int)$r['ends_at'] < $now) {
                continue;
            }
            $list[] = $r;
        }
    }
    return $list;
}

function lin2_qp_rates()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $expPct = 100;
    $adenaPct = 100;
    foreach (lin2_qp_active_promos() as $p) {
        if ((int)$p['exp_pct'] > $expPct) {
            $expPct = (int)$p['exp_pct'];
        }
        if ((int)$p['adena_pct'] > $adenaPct) {
            $adenaPct = (int)$p['adena_pct'];
        }
    }
    $cached = array('exp_pct' => $expPct, 'adena_pct' => $adenaPct);
    return $cached;
}

function lin2_qp_bonus_claim_key($cooldownType)
{
    if ($cooldownType === 'daily') {
        return lin2_qp_daily_key();
    }
    if ($cooldownType === 'weekly') {
        return lin2_qp_weekly_key();
    }
    return 'once';
}

function lin2_qp_claim_promo_gift($usr, $promoId)
{
    lin2_qp_ensure();
    $pid = (int)$promoId;
    if (empty($usr) || $pid <= 0) {
        return array(false, 'Акция не найдена.');
    }
    $now = time();
    $qp = mysql_query("SELECT * FROM `lin2_promos` WHERE `id` = '$pid' AND `active` = '1' LIMIT 1");
    if (!$qp || mysql_num_rows($qp) === 0) {
        return array(false, 'Акция завершена или недоступна.');
    }
    $p = mysql_fetch_assoc($qp);
    if (((int)$p['starts_at'] > 0 && (int)$p['starts_at'] > $now) || ((int)$p['ends_at'] > 0 && (int)$p['ends_at'] < $now)) {
        return array(false, 'Срок действия акции истёк.');
    }
    if ((int)$p['gift_adena'] <= 0 && (int)$p['gift_exp'] <= 0 && (int)$p['gift_col'] <= 0) {
        return array(false, 'У этой акции нет разового подарка.');
    }
    $u = mysql_real_escape_string($usr);
    $ck = 'promo:' . $pid;
    $qcl = mysql_query("SELECT `id` FROM `lin2_bonus_claims` WHERE `usr` = '$u' AND `bonus_id` = '0' AND `claim_key` = '$ck' LIMIT 1");
    if ($qcl && mysql_num_rows($qcl) > 0) {
        return array(false, 'Подарок по этой акции уже получен.');
    }
    mysql_query("INSERT INTO `lin2_bonus_claims` SET `usr` = '$u', `bonus_id` = '0', `claim_key` = '$ck', `claimed_at` = '$now'");
    lin2_qp_grant_rewards($usr, $p['gift_adena'], $p['gift_exp'], $p['gift_col'], 0);
    return array(true, 'Подарок по акции «' . $p['title'] . '» получен: ' . lin2_qp_reward_summary($p['gift_adena'], $p['gift_exp'], $p['gift_col'], 0));
}

function lin2_qp_do_claim_bonus_row($usr, $b)
{
    $now = time();
    $bid = (int)$b['id'];
    if ((int)$b['active'] !== 1) {
        return array(false, 'Бонус отключен.');
    }
    if ((int)$b['ends_at'] > 0 && (int)$b['ends_at'] < $now) {
        return array(false, 'Срок действия бонуса истёк.');
    }
    if ((int)$b['max_claims'] > 0 && (int)$b['claims_count'] >= (int)$b['max_claims']) {
        return array(false, 'Лимит активаций этого бонуса исчерпан.');
    }
    $u = mysql_real_escape_string($usr);
    $qu = mysql_query("SELECT `lvl` FROM `users` WHERE `usr` = '$u' LIMIT 1");
    if (!$qu || mysql_num_rows($qu) === 0) {
        return array(false, 'Персонаж не найден.');
    }
    $urow = mysql_fetch_assoc($qu);
    if ((int)$urow['lvl'] < (int)$b['min_lvl']) {
        return array(false, 'Бонус доступен с ' . (int)$b['min_lvl'] . ' уровня.');
    }

    $ck = mysql_real_escape_string(lin2_qp_bonus_claim_key($b['cooldown_type']));
    $qcl = mysql_query("SELECT `id` FROM `lin2_bonus_claims` WHERE `usr` = '$u' AND `bonus_id` = '$bid' AND `claim_key` = '$ck' LIMIT 1");
    if ($qcl && mysql_num_rows($qcl) > 0) {
        return array(false, 'Вы уже получали этот бонус.');
    }

    $newCnt = (int)$b['claims_count'] + 1;
    mysql_query("INSERT INTO `lin2_bonus_claims` SET `usr` = '$u', `bonus_id` = '$bid', `claim_key` = '$ck', `claimed_at` = '$now'");
    mysql_query("UPDATE `lin2_bonuses` SET `claims_count` = '$newCnt' WHERE `id` = '$bid' LIMIT 1");
    lin2_qp_grant_rewards($usr, $b['rew_adena'], $b['rew_exp'], $b['rew_col'], $b['rew_vip']);

    return array(true, 'Бонус «' . $b['title'] . '» получен: ' . lin2_qp_reward_summary($b['rew_adena'], $b['rew_exp'], $b['rew_col'], $b['rew_vip']));
}

function lin2_qp_claim_bonus($usr, $bonusId)
{
    lin2_qp_ensure();
    $bid = (int)$bonusId;
    if (empty($usr) || $bid <= 0) {
        return array(false, 'Бонус не найден.');
    }
    $qb = mysql_query("SELECT * FROM `lin2_bonuses` WHERE `id` = '$bid' AND `code` = '' LIMIT 1");
    if (!$qb || mysql_num_rows($qb) === 0) {
        return array(false, 'Бонус не найден.');
    }
    return lin2_qp_do_claim_bonus_row($usr, mysql_fetch_assoc($qb));
}

function lin2_qp_redeem_code($usr, $codeRaw)
{
    lin2_qp_ensure();
    $code = trim((string)$codeRaw);
    if (empty($usr) || $code === '') {
        return array(false, 'Введите промокод.');
    }
    $ce = mysql_real_escape_string($code);
    $qb = mysql_query("SELECT * FROM `lin2_bonuses` WHERE `code` != '' AND UPPER(`code`) = UPPER('$ce') LIMIT 1");
    if (!$qb || mysql_num_rows($qb) === 0) {
        return array(false, 'Промокод не найден или введён неверно.');
    }
    return lin2_qp_do_claim_bonus_row($usr, mysql_fetch_assoc($qb));
}

}
