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
