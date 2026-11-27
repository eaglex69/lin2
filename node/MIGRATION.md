# Карта переноса PHP -> Node.js (Miniage)

Сгенерировано: 2026-11-27T13:56:17.622Z

Всего PHP-файлов: 214 (корень: 147, inc/: 34, подпапки: 33)

## Ядро (реализовано полностью)

| PHP | Node.js |
|---|---|
| index.php | routes.js:index() + layout.js:pageGuest() |
| enter.php | auth.js:login() (md5, cookie 3ч) |
| reg.php | routes.js:reg() |
| office.php | routes.js:office() |
| pers.php | routes.js:pers() |
| gorod.php | routes.js:gorod() |
| okrestnosti.php | routes.js:okrestnosti() |
| battle.php | routes.js:battle() |
| fight.php | routes.js:fight() -> battle() |
| shop.php | routes.js:shop() |
| inventar.php | routes.js:inventar() |
| sklad.php | routes.js:sklad() |
| bank.php | routes.js:bank() |
| chat.php | routes.js:chat() |
| msg.php | routes.js:msg() |
| news.php | routes.js:news() |
| news_index.php | routes.js:news() |
| rating.php | routes.js:rating() |
| search.php | routes.js:search() |
| online.php | routes.js:online() |
| clan.php | routes.js:clan() |
| home.php | routes.js:home() |
| res.php | routes.js:res() |
| portal.php | routes.js:portal() |
| teleport.php | routes.js:portal() |
| to_gorod.php | routes.js:portal() |
| to_world.php | routes.js:portal() |
| to_zamok.php | routes.js:portal() |
| job.php | routes.js:job() |
| faq.php | routes.js:faq() |
| rules.php | routes.js:rules() |
| main.php | routes.js:main() |
| auto.php | routes.js:auto() |

## inc/ (библиотеки)

| PHP | Node.js |
|---|---|
| inc/AntiHack.class.php | src/func.js / stub |
| inc/antimat.php | src/func.js / stub |
| inc/auth.php | src/auth.js:auth() |
| inc/avto_online.php | src/func.js / stub |
| inc/clan_lvl.php | src/func.js / stub |
| inc/clan_lvl_2.php | src/func.js / stub |
| inc/clan_opit.php | src/func.js / stub |
| inc/core.php | src/func.js (sec2day, checkin, pagin) |
| inc/db.php | src/db.js (слой данных, shema.sql) |
| inc/down.php | src/layout.js:down() |
| inc/downmain.php | src/func.js / stub |
| inc/end.php | src/layout.js:down() |
| inc/endmain.php | src/layout.js:down() |
| inc/foot_inf.php | src/func.js / stub |
| inc/foot_pic.php | src/func.js / stub |
| inc/foot_text.php | src/func.js / stub |
| inc/func.php | src/func.js |
| inc/func_tik.php | src/func.js / stub |
| inc/function.php | src/func.js / stub |
| inc/gradient.php | src/func.js / stub |
| inc/head.php | src/layout.js:head() |
| inc/ini.php | src/func.js / stub |
| inc/lvl.php | src/func.js:checkLevel() |
| inc/mysql.php | src/db.js (запросы Q.*) |
| inc/nogi.php | src/func.js / stub |
| inc/online.php | src/auth.js (online.laikas) |
| inc/opit.php | src/func.js:expPercent() |
| inc/opit222.php | src/func.js / stub |
| inc/opitost.php | src/func.js / stub |
| inc/opits.php | src/func.js / stub |
| inc/path.php | server.js (ROOT) |
| inc/regeneration.php | src/func.js:regeneration() |
| inc/times.php | src/func.js / stub |
| inc/zag.php | src/layout.js:zag() (HP/MP/EXP-бары) |

## Остальные корневые *.php (stubPage в routes.js)

Эти страницы отдаются через routes.js:stubPage() — Node.js отвечает на тот же URL,
показывает карту переноса и навигацию. Логика оригинала сохранена в PHP-файле.

| PHP | Node.js |
|---|---|
| account.php | routes.js:stubPage() |
| add_blog.php | routes.js:stubPage() |
| adm_chat.php | routes.js:stubPage() |
| adm_panel.php | routes.js:stubPage() |
| akademmag.php | routes.js:stubPage() |
| akademwar.php | routes.js:stubPage() |
| anketa.php | routes.js:stubPage() |
| anti_pk.php | routes.js:stubPage() |
| apk.php | routes.js:stubPage() |
| arena.php | routes.js:stubPage() |
| avt.php | routes.js:stubPage() |
| baf.php | routes.js:stubPage() |
| baf_vip.php | routes.js:stubPage() |
| bazar.php | routes.js:stubPage() |
| bazar_col.php | routes.js:stubPage() |
| bazar_res.php | routes.js:stubPage() |
| bazar_vip.php | routes.js:stubPage() |
| bazar_wmr.php | routes.js:stubPage() |
| blog.php | routes.js:stubPage() |
| blog_user.php | routes.js:stubPage() |
| captcha.php | routes.js:stubPage() |
| chat2.php | routes.js:stubPage() |
| clan_shop.php | routes.js:stubPage() |
| clanroom.php | routes.js:stubPage() |
| coin_sms.php | routes.js:stubPage() |
| col_bonus.php | routes.js:stubPage() |
| combat.php | routes.js:stubPage() |
| date.php | routes.js:stubPage() |
| delete_usr.php | routes.js:stubPage() |
| dress.php | routes.js:stubPage() |
| elexirs.php | routes.js:stubPage() |
| event.php | routes.js:stubPage() |
| event_w.php | routes.js:stubPage() |
| fish.php | routes.js:stubPage() |
| fish_r.php | routes.js:stubPage() |
| fishing.php | routes.js:stubPage() |
| gallery.php | routes.js:stubPage() |
| gifts.php | routes.js:stubPage() |
| gifts_2.php | routes.js:stubPage() |
| gk.php | routes.js:stubPage() |
| gzip_pr.php | routes.js:stubPage() |
| hram.php | routes.js:stubPage() |
| index_site.php | routes.js:stubPage() |
| karma.php | routes.js:stubPage() |
| kill_ob.php | routes.js:stubPage() |
| lambard.php | routes.js:stubPage() |
| log.php | routes.js:stubPage() |
| lotomy.php | routes.js:stubPage() |
| mailadmin.php | routes.js:stubPage() |
| mod_panel.php | routes.js:stubPage() |
| moder_event.php | routes.js:stubPage() |
| msg_history.php | routes.js:stubPage() |
| msg_pers.php | routes.js:stubPage() |
| n_y.php | routes.js:stubPage() |
| news_tex.php | routes.js:stubPage() |
| obm_fs_ad.php | routes.js:stubPage() |
| ok.php | routes.js:stubPage() |
| okar.php | routes.js:stubPage() |
| open_s.php | routes.js:stubPage() |
| oprosvip.php | routes.js:stubPage() |
| opt.php | routes.js:stubPage() |
| opt_pass.php | routes.js:stubPage() |
| password.php | routes.js:stubPage() |
| path.php | routes.js:stubPage() |
| paty.php | routes.js:stubPage() |
| php.php | routes.js:stubPage() |
| pit.php | routes.js:stubPage() |
| pitomec.php | routes.js:stubPage() |
| pk.php | routes.js:stubPage() |
| players.php | routes.js:stubPage() |
| pnews.php | routes.js:stubPage() |
| profa.php | routes.js:stubPage() |
| qiwi.php | routes.js:stubPage() |
| reg_site.php | routes.js:stubPage() |
| rekc.php | routes.js:stubPage() |
| sab.php | routes.js:stubPage() |
| scroll.php | routes.js:stubPage() |
| sell.php | routes.js:stubPage() |
| sell_chat.php | routes.js:stubPage() |
| sell_gold.php | routes.js:stubPage() |
| sell_lux.php | routes.js:stubPage() |
| sell_ny.php | routes.js:stubPage() |
| sell_vip.php | routes.js:stubPage() |
| settings_sms.php | routes.js:stubPage() |
| shop_chat.php | routes.js:stubPage() |
| shop_gold.php | routes.js:stubPage() |
| shop_lux.php | routes.js:stubPage() |
| shop_ny.php | routes.js:stubPage() |
| shop_vip.php | routes.js:stubPage() |
| shopaura.php | routes.js:stubPage() |
| shopelexirs.php | routes.js:stubPage() |
| shopjuvilir.php | routes.js:stubPage() |
| shopjuvilir_gold.php | routes.js:stubPage() |
| shoppit.php | routes.js:stubPage() |
| shopsoul.php | routes.js:stubPage() |
| smile.php | routes.js:stubPage() |
| sms.php | routes.js:stubPage() |
| snyat.php | routes.js:stubPage() |
| st.php | routes.js:stubPage() |
| tchat.php | routes.js:stubPage() |
| tiket.php | routes.js:stubPage() |
| tiket_adm.php | routes.js:stubPage() |
| users.php | routes.js:stubPage() |
| users_new.php | routes.js:stubPage() |
| uslugi_exp.php | routes.js:stubPage() |
| vip_scroll.php | routes.js:stubPage() |
| vote_bon.php | routes.js:stubPage() |
| webmoney.php | routes.js:stubPage() |
| wmr_obmen.php | routes.js:stubPage() |
| workshop.php | routes.js:stubPage() |
| zaksms.php | routes.js:stubPage() |
| zamok.php | routes.js:stubPage() |
| zayavka.php | routes.js:stubPage() |
| zona.php | routes.js:stubPage() |

## Подпапки (forum/, gm/, bonus/, drink/)

| PHP | Node.js |
|---|---|
| forum/down.php | routes.js:stubPage() |
| forum/end.php | routes.js:stubPage() |
| forum/head.php | routes.js:stubPage() |
| forum/index.php | routes.js:stubPage() |
| forum/posting.php | routes.js:stubPage() |
| forum/topic.php | routes.js:stubPage() |
| gm/a1pay_log.php | routes.js:stubPage() |
| gm/ava.php | routes.js:stubPage() |
| gm/blok.php | routes.js:stubPage() |
| gm/blok_ip.php | routes.js:stubPage() |
| gm/index.php | routes.js:stubPage() |
| gm/logi.php | routes.js:stubPage() |
| gm/lvl_new.php | routes.js:stubPage() |
| gm/mail_vh.php | routes.js:stubPage() |
| gm/msg.php | routes.js:stubPage() |
| gm/msg_adm.php | routes.js:stubPage() |
| gm/msg_all.php | routes.js:stubPage() |
| gm/my_msg.php | routes.js:stubPage() |
| gm/new_smot.php | routes.js:stubPage() |
| gm/poisk.php | routes.js:stubPage() |
| gm/redshmot.php | routes.js:stubPage() |
| gm/skill.php | routes.js:stubPage() |
| gm/vhod.php | routes.js:stubPage() |
| gm/webmoney_log.php | routes.js:stubPage() |
| gm/wm.php | routes.js:stubPage() |
| gm/wmr_obmen.php | routes.js:stubPage() |
| bonus/bonus.php | routes.js:stubPage() |
| bonus/bonus_bot.php | routes.js:stubPage() |
| bonus/common.php | routes.js:stubPage() |
| bonus/config.php | routes.js:stubPage() |
| bonus/votes_bot.php | routes.js:stubPage() |
| drink/data.php | routes.js:stubPage() |
| drink/index.php | routes.js:stubPage() |

## БД

`shema.sql` (144 таблицы) — без изменений, см. README.md.
