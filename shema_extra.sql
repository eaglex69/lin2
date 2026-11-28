-- Таблицы, которые использует код игры, но которых нет в shema.sql (дамп от января 2015 старше кода).
-- Структура восстановлена по коду и по соседним таблицам; данных здесь нет.
-- Установщик (install.php) выполняет этот файл после shema.sql. Файл можно дополнять своими таблицами.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Структура таблицы `job_pvp`
-- Ежедневное задание «PvP» (job.php, okar.php, inc/zag.php); поля те же, что у job_mob / job_fish / job_col
--

CREATE TABLE IF NOT EXISTS `job_pvp` (
  `usr` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `kill` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `clan_shop`
-- Магазин клана (clan_shop.php). В дампе та же таблица называется `shop_clan`: набор полей совпадает с тем,
-- что читает clan_shop.php (city, tip, tip2, nlvl, cena, ...).
--

CREATE TABLE IF NOT EXISTS `clan_shop` (
`id` int(11) NOT NULL,
  `city` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `tip` varchar(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `tip2` varchar(35) NOT NULL,
  `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `cena` bigint(11) NOT NULL,
  `patt` int(11) DEFAULT NULL,
  `matt` int(11) DEFAULT NULL,
  `pdef` int(11) DEFAULT NULL,
  `mdef` int(11) DEFAULT NULL,
  `time` int(11) DEFAULT NULL,
  `nlvl` int(11) NOT NULL,
  `soul` int(5) NOT NULL,
  `spirit` int(5) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `clan_shop`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `clan_shop`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Ежедневные / еженедельные задания, акции и бонусы (job.php, promo.php, adm_panel.php)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `lin2_qp_meta` (
  `k` varchar(40) NOT NULL DEFAULT '',
  `v` varchar(120) NOT NULL DEFAULT '',
  PRIMARY KEY (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `lin2_quests` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `lin2_quest_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usr` varchar(40) NOT NULL DEFAULT '',
  `period_key` varchar(24) NOT NULL DEFAULT '',
  `metric` varchar(40) NOT NULL DEFAULT '',
  `val` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `lin2_quest_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usr` varchar(40) NOT NULL DEFAULT '',
  `quest_id` int(11) NOT NULL DEFAULT '0',
  `period_key` varchar(24) NOT NULL DEFAULT '',
  `claimed_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `lin2_promos` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `lin2_bonuses` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `lin2_bonus_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usr` varchar(40) NOT NULL DEFAULT '',
  `bonus_id` int(11) NOT NULL DEFAULT '0',
  `claim_key` varchar(40) NOT NULL DEFAULT '',
  `claimed_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

