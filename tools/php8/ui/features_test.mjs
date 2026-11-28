// End-to-end browser verification of Tasks 1-8 & 10 against demo/server.mjs.
import { BASE, open, reporter, reset, sleep, sql } from './lib.mjs';
import { existsSync, writeFileSync, unlinkSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import { REPO } from '../common.mjs';

await reset();
const { browser, page } = await open({ width: 390, height: 900 });
const { check, done } = reporter();
const ev = (fn, ...a) => page.evaluate(fn, ...a);
const go = async (u) => { await page.goto(BASE + u, { waitUntil: 'domcontentloaded' }); await sleep(400); };
const idle = async () => { try { await page.waitForFunction(() => !document.documentElement.classList.contains('lx-busy'), { timeout: 8000 }); } catch (e) {} await sleep(250); };
const phpErrors = () => ev(() => /(Fatal error|Warning:|Notice:|Deprecated:|Parse error|Uncaught)/.test(document.body.innerText));
const one = async (q) => { const r = await sql(q); return r.rows && r.rows[0] ? r.rows[0][0] : undefined; };

// ---------------------------------------------------------------- 1 & 2: Starting adena = 1000 + Cyrillic nicknames
const adminStartMoney = await one(`SELECT money FROM users WHERE usr='Admin1'`);
check('T1 fresh character created by install.php starts with 1000 adena', String(adminStartMoney) === '1000', String(adminStartMoney));

await go('/_demo/login/Tester?to=/office.php?act=new');
check('T2 office.php?act=new opens character creation form without PHP errors', !(await phpErrors()) && await ev(() => !!document.querySelector('input[name="name"]')));

await page.type('input[name="name"]', 'ВоинСвета');
await page.click('input[type="submit"]');
await idle();

const cyrRow = await sql(`SELECT id, usr, money FROM users WHERE usr='ВоинСвета'`);
check('T1+T2 Cyrillic nickname "ВоинСвета" is created with starting money = 1000',
  cyrRow.rows.length === 1 && cyrRow.rows[0][1] === 'ВоинСвета' && String(cyrRow.rows[0][2]) === '1000',
  JSON.stringify(cyrRow.rows));

// Select the newly created Cyrillic character via office.php?a=choose&id=<id> and view pers.php
const cyrCharId = cyrRow.rows[0][0];
await go('/office.php?a=choose&id=' + cyrCharId);
await go('/pers.php');
check('T2 logging in and viewing pers.php as Cyrillic character works cleanly',
  !(await phpErrors()) && await ev(() => document.body.innerText.includes('ВоинСвета') && (document.body.innerText.includes('1\u00a0000') || document.body.innerText.includes('1 000'))),
  await ev(() => document.body.innerText.slice(0, 120)));

// Switch Tester's active character back to Tester
const testerId = await one(`SELECT id FROM users WHERE usr='Tester'`);
await go('/office.php?a=choose&id=' + testerId);
await go('/pers.php');

// ---------------------------------------------------------------- 6: All SVGs replaced with raster PNG/JPG
const svgCheck = await ev(() => {
  const imgs = [...document.querySelectorAll('img')].map((i) => i.getAttribute('src') || '');
  const sheetUrls = [];
  for (const ss of document.styleSheets) {
    try {
      for (const r of ss.cssRules) {
        if (/\.svg|image\/svg/i.test(r.cssText)) sheetUrls.push(r.cssText.slice(0, 120));
      }
    } catch (e) {}
  }
  return { svgImgs: imgs.filter((s) => /\.svg/i.test(s)), svgCss: sheetUrls };
});
check('T6 zero .svg <img> or CSS rules anywhere in the live theme',
  svgCheck.svgImgs.length === 0 && svgCheck.svgCss.length === 0,
  JSON.stringify(svgCheck));

// ---------------------------------------------------------------- 8: Mail icon in top header bar
await sql(`INSERT INTO msg_r SET user_from='Mage01', user_to='Tester', time='12:00', read='1', mail_msg='Привет!'`);
await go('/pers.php');
const mailInfo = await ev(() => {
  const a = document.querySelector('.lx-banner-wrap a.lx-mail');
  if (!a) return null;
  const r = a.getBoundingClientRect();
  const bd = a.querySelector('.bd');
  const iconBg = getComputedStyle(a, '::before').backgroundImage;
  return { href: a.getAttribute('href'), badge: bd ? bd.textContent.trim() : null, top: r.top, iconBg };
});
check('T8 mail icon is in top banner bar with unread badge and raster g-mail.png icon',
  !!mailInfo && mailInfo.badge === '1' && mailInfo.top < 80 && /g-mail\.png/.test(mailInfo.iconBg),
  JSON.stringify(mailInfo));

await page.click('.lx-banner-wrap a.lx-mail');
await idle();
check('T8 clicking top mail icon opens msg.php?mod=read',
  await ev(() => location.pathname === '/msg.php' && location.search.includes('mod=read')) && !(await phpErrors()),
  await ev(() => location.pathname + location.search));

// ---------------------------------------------------------------- 10: No "Здоровье" panel under avatar on pers.php
await go('/pers.php');
const persPanels = await ev(() => [...document.querySelectorAll('.lx-pn .lx-pt')].map((e) => e.textContent.trim()));
check('T10 pers.php has no duplicate "Здоровье" block under avatar while top bar shows HP',
  !persPanels.some((t) => t.includes('Здоровье')) && await ev(() => [...document.querySelectorAll('.lx-head .lx-bar b')].some((e) => e.textContent.includes('HP'))),
  persPanels.join(', '));

// ---------------------------------------------------------------- 3: 10 Daily Quests + 10 Weekly Quests
await go('/job.php?tab=daily');
check('T3 job.php opens without PHP errors and displays 10 daily quests',
  !(await phpErrors()) && await ev(() => document.body.innerText.includes('Ежедневные (10)') && document.body.innerText.includes('Еженедельные (10)')),
  await ev(() => [...document.querySelectorAll('.lx-tab')].map((t) => t.textContent.trim()).join(' | ')));

const moneyBeforeQuest = Number(await one(`SELECT money FROM users WHERE usr='Tester'`));
const colBeforeQuest = Number(await one(`SELECT almaz FROM users WHERE usr='Tester'`));
check('T3 "Ежедневный дозор" is automatically ready to claim on visit',
  await ev(() => !!document.querySelector('a.lx-btn[href*="claim="]')));

await page.click('a.lx-btn[href*="claim="]');
await idle();
const moneyAfterQuest = Number(await one(`SELECT money FROM users WHERE usr='Tester'`));
const colAfterQuest = Number(await one(`SELECT almaz FROM users WHERE usr='Tester'`));
check('T3 claiming daily quest grants Adena (+1500) and CoL (+1) and marks quest completed',
  moneyAfterQuest === moneyBeforeQuest + 1500 && colAfterQuest === colBeforeQuest + 1 && await ev(() => document.body.innerText.includes('Получено')),
  `${moneyBeforeQuest}->${moneyAfterQuest}, col ${colBeforeQuest}->${colAfterQuest}`);

await go('/job.php?tab=weekly');
const weeklyCount = await ev(() => document.querySelectorAll('.lx-pn .lx-pb .lx-mt').length);
check('T3 weekly tab renders all 10 weekly quest progress bars', weeklyCount === 10 && !(await phpErrors()), String(weeklyCount));

// ---------------------------------------------------------------- 4: Admin Panel Quests, Promotions & Bonuses + promo.php
await go('/_demo/login/Admin1?to=/adm_panel.php?mod=quests');
check('T4 adm_panel.php?mod=quests opens cleanly for Admin1', !(await phpErrors()) && await ev(() => document.body.innerText.includes('Добавить новый квест')));

await page.type('input[name="title"]', 'Особый дозор админа');
await page.type('input[name="descr"]', 'Тестовое задание из админки');
await page.$eval('input[name="target"]', (el) => { el.value = '1'; });
await page.click('input[type="submit"]');
await idle();
const customQuestId = await one(`SELECT id FROM lin2_quests WHERE title='Особый дозор админа'`);
check('T4 admin can create a new quest via adm_panel.php?mod=quests', Number(customQuestId) > 0, String(customQuestId));
await go('/adm_panel.php?mod=quests&del=' + customQuestId);
check('T4 admin can delete a quest via adm_panel.php?mod=quests', !(await one(`SELECT id FROM lin2_quests WHERE id='${customQuestId}'`)));

// Admin creates a promo & a promo-code bonus
await go('/adm_panel.php?mod=promos');
await page.type('input[name="title"]', 'Праздник Адена');
await page.$eval('input[name="gift_adena"]', (el) => { el.value = '777'; });
await page.click('input[type="submit"]');
await idle();
const newPromoId = await one(`SELECT id FROM lin2_promos WHERE title='Праздник Адена'`);
check('T4 admin can create a promotion via adm_panel.php?mod=promos', Number(newPromoId) > 0, String(newPromoId));

await go('/adm_panel.php?mod=bonuses');
await page.type('input[name="title"]', 'Код арены');
await page.type('input[name="code"]', 'ARENA777');
await page.$eval('input[name="rew_adena"]', (el) => { el.value = '3333'; });
await page.$eval('input[name="rew_col"]', (el) => { el.value = '5'; });
await page.click('input[type="submit"]');
await idle();
check('T4 admin can create a promo-code bonus via adm_panel.php?mod=bonuses',
  Number(await one(`SELECT id FROM lin2_bonuses WHERE code='ARENA777'`)) > 0);

// Player claims promo gift & redeems promo code on promo.php
await go('/_demo/login/Tester?to=/promo.php');
const testerMoney0 = Number(await one(`SELECT money FROM users WHERE usr='Tester'`));
await page.click(`a[href="promo.php?claim_promo=${newPromoId}"]`);
await idle();
const testerMoney1 = Number(await one(`SELECT money FROM users WHERE usr='Tester'`));
check('T4 player claims active promotion gift (+777 Adena) on promo.php', testerMoney1 === testerMoney0 + 777, `${testerMoney0}->${testerMoney1}`);

await page.type('input[name="promo_code"]', 'ARENA777');
await page.click('form[action="promo.php"] input[type="submit"]');
await idle();
const testerMoney2 = Number(await one(`SELECT money FROM users WHERE usr='Tester'`));
check('T4 player redeems promo code ARENA777 (+3333 Adena) on promo.php', testerMoney2 === testerMoney1 + 3333, `${testerMoney1}->${testerMoney2}`);

// ---------------------------------------------------------------- 5: Admin Panel Site-Wide Image Replacement + Restore
const origGiftPath = join(REPO, 'theme/fantasy/g-gift.png');
const origGiftBytes = readFileSync(origGiftPath);
const tmpPng = join(tmpdir(), 'test_upload_icon.png');
//Valid 1x1 transparent PNG
writeFileSync(tmpPng, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64'));

await go('/_demo/login/Admin1');
await go('/adm_panel.php?mod=images&dir=' + encodeURIComponent('theme/fantasy') + '&q=g-gift.png');
check('T5 adm_panel.php?mod=images lists site images with folder filter and search',
  !(await phpErrors()) && await ev(() => document.body.innerText.includes('theme/fantasy/g-gift.png')));

const fileInput = await page.$('.lx-img-card input[type="file"]');
await fileInput.uploadFile(tmpPng);
await page.click('.lx-img-card input[type="submit"]');
await idle();

const replacedBytes = readFileSync(origGiftPath);
const hasBakOnDisk = existsSync(origGiftPath + '.lin2bak');
check('T5 uploading replacement PNG overwrites target image and creates .lin2bak backup',
  hasBakOnDisk && replacedBytes.length !== origGiftBytes.length && await ev(() => document.body.innerText.includes('успешно обновлено')),
  `bak=${hasBakOnDisk}, len=${origGiftBytes.length}->${replacedBytes.length}`);

await page.click('a[href*="restore="]');
await idle();
const restoredBytes = readFileSync(origGiftPath);
check('T5 clicking restore brings back exact original image bytes and removes .lin2bak',
  !existsSync(origGiftPath + '.lin2bak') && Buffer.compare(origGiftBytes, restoredBytes) === 0);
try { unlinkSync(tmpPng); } catch (e) {}

// ---------------------------------------------------------------- 7: Antimat & Anti-advertising in Chat
await sql(`UPDATE users SET time='3600', lvl='25' WHERE usr='Tester'`);
await sql(`DELETE FROM komentarai WHERE nick='Tester'`);
await go('/_demo/login/Tester?to=/chat.php?mod=write');
const testChatMsg = 'Купил саблю за три рубля на корабле у её брата, команда готова! А тут бля сука пиздец х.у.й и реклама https://l2top-serv.ru www.evil.com mega-lin(точка)рф 185.11.22.33:7777';
await page.$eval('textarea[name="zin"]', (el, val) => { el.value = val; }, testChatMsg);
await page.click('input[type="submit"]');
await idle();

const savedChat = String(await one(`SELECT komentaras FROM komentarai WHERE nick='Tester' ORDER BY id DESC LIMIT 1`) || '');
const cleanKept = savedChat.includes('Купил саблю за три рубля на корабле у её брата, команда готова!');
const matFiltered = !/(?:^|\s)(?:бля|сука|пиздец)(?:\s|$)|х\.у\.й/iu.test(savedChat) && savedChat.includes('***');
const adsFiltered = !/(l2top-serv|evil\.com|mega-lin|185\.11\.22\.33)/i.test(savedChat) && savedChat.includes('[реклама запрещена]');
check('T7 chat filters profanity and website/IP ads while keeping normal Russian words intact',
  cleanKept && matFiltered && adsFiltered,
  savedChat);

await browser.close();
done();
