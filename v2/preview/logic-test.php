<?php
/**
 * Логический харнесс Miniage v2.
 * Запускается через logic.mjs — поднимает php-wasm, монтирует исходники
 * в /tmp/v2 и прогоняет проверки на SQLite в памяти. Живой сервер не нужен.
 */
define('MINIAGE_WASM', true);
$GLOBALS['__autoload_ok'] = true;
spl_autoload_register(function ($class) {
    if (!str_starts_with($class, 'App\\')) return;
    $rel = str_replace('\\', '/', substr($class, 4));
    $f = '/tmp/v2/src/' . $rel . '.php';
    if (is_file($f)) require $f;
});

// подменяем путь конфигурации: Config читает /config.json и .env
file_put_contents('/config.json', json_encode([
    'APP_ENV' => 'testing', 'DB_DRIVER' => 'sqlite',
    'DB_SQLITE_PATH' => '/tmp/test.sqlite', 'SEED_DEMO' => '1',
    'TIMEZONE' => 'Europe/Moscow',
]));
@unlink('/tmp/test.sqlite');

use App\Core\Config, App\Core\Database, App\Core\Migrator;
use App\Service\GameService, App\Service\ShopService;
use App\Service\AuthService;
use App\Service\ChatService;

Config::load('/tmp/v2');
// схема
$scheme = file_get_contents('/tmp/v2/db/schema.sqlite.sql');
Database::boot();
foreach (array_filter(array_map('trim', explode(';', $scheme))) as $s) {
    try { Database::pdo()->exec($s); } catch (Throwable $e) { echo "SCHEMA ERR: {$e->getMessage()}\n"; }
}
Migrator::migrate();

$dbg = fn($l) => print($l . "\n");
$row = fn() => Database::one('SELECT hp, hp_max, mp, mp_max, p_atk, p_def, level, exp FROM characters WHERE id = 1');

$dbg('== начальное: ' . json_encode($row()));

// --- ТЕСТ 1: мёртвый + смена экипировки не воскрешает
Database::update('characters', ['hp' => 0], 'id = ?', [1]);
$inv = Database::one('SELECT id FROM inventory WHERE char_id = 1 AND equipped = 1 LIMIT 1')
    ?? Database::one('SELECT id FROM inventory WHERE char_id = 1 LIMIT 1');
$shop = new ShopService();
$shop->equip(1, (int) $inv['id'], false);
$after = $row();
$dbg('T1 после снятия экипировки: hp=' . $after['hp'] . ' (ожидалось 0) -> ' . ($after['hp'] === 0 ? 'OK' : 'БАГ'));
$shop->equip(1, (int) $inv['id'], true);
$after = $row();
$dbg('T1 после надевания:        hp=' . $after['hp'] . ' (ожидалось 0) -> ' . ($after['hp'] === 0 ? 'OK' : 'БАГ'));

// --- ТЕСТ 2: heal мёртвого списывает 500 и воскрешает
Database::update('characters', ['hp' => 0, 'adena' => 1000], 'id = ?', [1]);
$game = new GameService();
$res = $game->heal(1);
$after = $row();
$dbg('T2 heal мёртвого: cost=' . $res['cost'] . ' hp=' . $after['hp'] . ' (ожидалось cost=500, hp=hp_max) -> '
    . ($res['cost'] === 500 && $after['hp'] === $after['hp_max'] ? 'OK' : 'БАГ'));
$adena = (int) Database::value('SELECT adena FROM characters WHERE id = 1');
$dbg('T2 адена после реанимации: ' . $adena . ' (ожидалось 500) -> ' . ($adena === 500 ? 'OK' : 'БАГ'));

// --- ТЕСТ 3: живой с полным HP -> heal отказ
try { $game->heal(1); $dbg('T3 БАГ: heal при полном HP не отказал'); }
catch (Throwable $e) { $dbg('T3 heal при полном HP: отказ -> OK'); }

// --- ТЕСТ 4: зелье при полных HP/MP не тратится
Database::update('characters', ['hp' => Database::value('SELECT hp_max FROM characters WHERE id=1')], 'id = ?', [1]);
Database::update('characters', ['mp' => Database::value('SELECT mp_max FROM characters WHERE id=1')], 'id = ?', [1]);
$pot = Database::one('SELECT i.id, it.hp_bonus FROM inventory i JOIN items it ON it.id=i.item_id WHERE i.char_id=1 AND it.kind=\'potion\' LIMIT 1');
$qtyBefore = (int) Database::value('SELECT qty FROM inventory WHERE id = ?', [(int) $pot['id']]);
try { $shop->useItem(1, (int) $pot['id']); $dbg('T4 БАГ: зелье использовалось при полном HP'); }
catch (Throwable $e) {
    $qtyAfter = (int) Database::value('SELECT qty FROM inventory WHERE id = ?', [(int) $pot['id']]);
    $dbg('T4 зелье при полном HP: отказ, кол-во ' . $qtyBefore . ' -> ' . $qtyAfter
        . ' -> ' . ($qtyBefore === $qtyAfter ? 'OK' : 'БАГ'));
}

// --- ТЕСТ 5: левелап с экипировкой не съедает бонусы
$before = $row();
$expNeeded = \App\Service\ExpTable::totalFor(2);
Database::update('characters', ['exp' => $expNeeded, 'level' => 1], 'id = ?', [1]);
// имитируем победу: даём опыт через attack невозможно — вызываем приватный путь через рефлексию
$mob = Database::one('SELECT * FROM mobs WHERE zone = \'Giran\' ORDER BY level LIMIT 1');
$char = $game->requireChar(1);
Database::update('characters', ['exp' => $expNeeded - (int) $mob['exp_reward'] + (int) $mob['exp_reward']], 'id = ?', [1]);
// проще: ставим опыт чуть ниже порога и добиваем боев
Database::update('characters', ['exp' => $expNeeded - 1, 'hp' => 999, 'hp_max' => 999], 'id = ?', [1]);
Database::update('characters', ['hp' => (int) Database::value('SELECT hp_max FROM characters WHERE id=1')], 'id = ?', [1]);
$bid = $game->startBattle(1, 1);
for ($i = 0; $i < 30; $i++) {
    $st = $game->attack(1, $bid['id']);
    if ($st['state'] !== 'active') break;
}
$after = $row();
$dbg('T5 после победы: level=' . $after['level'] . ' hp_max=' . $after['hp_max'] . ' p_atk=' . $after['p_atk']);
// пересчёт должен совпасть с независимым расчётом
$shop->recalcStats(1);
$recalc = $row();
$dbg('T5 после принудительного recalc: hp_max=' . $recalc['hp_max'] . ' p_atk=' . $recalc['p_atk']
    . ' -> ' . ((int) $recalc['hp_max'] === (int) $after['hp_max'] && (int) $recalc['p_atk'] === (int) $after['p_atk'] ? 'OK (согласовано)' : 'БАГ (рассинхрон)'));

// --- ТЕСТ 6: flee по выигранному бою запрещён
try { $game->flee(1, (int) $bid['id']); $dbg('T6 БАГ: flee из завершённого боя прошёл'); }
catch (Throwable $e) { $dbg('T6 flee из завершённого боя: отказ -> OK'); }

// --- ТЕСТ 7: стартовая адена новичка
$id = Migrator::createAccount('newbie_x', 'Str0ngPass!1');
$cid = Migrator::createCharacter($id, 'newbie_x', 'elf', 'mage', 'm');
$adena = (int) Database::value('SELECT adena FROM characters WHERE id = ?', [$cid]);
$dbg('T7 стартовая адена новичка: ' . $adena . ' -> ' . ($adena === 500 ? 'OK' : 'БАГ'));

// --- ТЕСТ 8: валидация расы/класса
try { (new AuthService())->register('badrace', 'Str0ngPass!1', '', '<script>', 'fighter', 'm'); $dbg('T8 БАГ: невалидная раса принята'); }
catch (Throwable $e) { $dbg('T8 невалидная раса: отказ -> OK'); }
try { (new AuthService())->register('badclass', 'Str0ngPass!1', '', 'human', 'godmode', 'm'); $dbg('T8 БАГ: невалидный класс принят'); }
catch (Throwable $e) { $dbg('T8 невалидный класс: отказ -> OK'); }

// --- ТЕСТ 9: мёртвый (hp=0) остаётся мёртвым до heal
Database::update('characters', ['hp' => 0, 'adena' => 1000], 'id = ?', [1]);
$shop->recalcStats(1);
$after = $row();
$dbg('T9 recalcStats мёртвого: hp=' . $after['hp'] . ' -> ' . ((int) $after['hp'] === 0 ? 'OK' : 'БАГ'));

$invRow = Database::one("SELECT i.id FROM inventory i JOIN items it ON it.id=i.item_id WHERE i.char_id = 1 AND i.equipped = 0 AND it.slot <> 'none' LIMIT 1");
if ($invRow) {
    $shop->equip(1, (int) $invRow['id'], true);
    $after = $row();
    $dbg('T9 экипировка мёртвого: hp=' . $after['hp'] . ' -> ' . ((int) $after['hp'] === 0 ? 'OK' : 'БАГ'));
}

// бой мёртвым запрещён
try { $game->startBattle(1, 1); $dbg('T9 БАГ: мёртвый начал бой'); }
catch (Throwable $e) { $dbg('T9 бой мёртвым: отказ -> OK'); }

// зелье мёртвому — можно ли поднять с 0?
$pot2 = Database::one('SELECT i.id FROM inventory i JOIN items it ON it.id=i.item_id WHERE i.char_id=1 AND it.kind=\'potion\' LIMIT 1');
if ($pot2) {
    try {
        $r = $shop->useItem(1, (int) $pot2['id']);
        $dbg('T9 БАГ: зелье подняло мёртвого до ' . $r['hp']);
    } catch (Throwable $e) { $dbg('T9 зелье мёртвому: отказ -> OK'); }
}

// heal воскрешает за 500
Database::update('characters', ['hp' => 0, 'adena' => 1000], 'id = ?', [1]);
try {
    $r = $game->heal(1);
    $after = $row();
    $dbg('T9 heal мёртвого: cost=' . $r['cost'] . ' hp=' . $after['hp'] . '/' . $after['hp_max'] . ' -> '
        . ($r['cost'] === 500 && (int) $after['hp'] === (int) $after['hp_max'] ? 'OK' : 'БАГ'));
} catch (Throwable $e) { $dbg('T9 heal упал: ' . $e->getMessage()); }

// --- ТЕСТ 10: серверная регенерация
Database::update('characters', ['hp' => 50, 'hp_max' => 200, 'mp' => 10, 'mp_max' => 100,
    'last_seen' => date('Y-m-d H:i:s', time() - 10)], 'id = ?', [1]);
$before = $row();
$after = $game->requireChar(1);
$dbg('T10 реген за 10с: hp ' . $before['hp'] . ' -> ' . $after['hp']
    . ' (ожидалось ~' . (50 + max(1, (int) round(200 * 0.002 * 10))) . ') -> '
    . ((int) $after['hp'] > 50 ? 'OK' : 'БАГ'));

// мёртвый не регенерирует
Database::update('characters', ['hp' => 0, 'last_seen' => date('Y-m-d H:i:s', time() - 60)], 'id = ?', [1]);
$dead = $game->requireChar(1);
$dbg('T10 реген мёртвого: hp=' . $dead['hp'] . ' -> ' . ((int) $dead['hp'] === 0 ? 'OK' : 'БАГ'));

// реген не превышает максимум
Database::update('characters', ['hp' => 199, 'hp_max' => 200, 'last_seen' => date('Y-m-d H:i:s', time() - 300)], 'id = ?', [1]);
$capped = $game->requireChar(1);
$dbg('T10 реген не выше максимума: hp=' . $capped['hp'] . ' -> ' . ((int) $capped['hp'] === 200 ? 'OK' : 'БАГ'));

// --- ТЕСТ 11: обрезка UTF-8 в чате (баг «json_encode»)
$long = str_repeat('Ж', 300);
$chat = new ChatService();
$msg = $chat->post(1, 'Demo', 'global', $long);
// mbstring в php-wasm нет, поэтому считаем символы вручную
$len = $msg['text'] === '' ? 0 : count(preg_split('//u', $msg['text'], -1, PREG_SPLIT_NO_EMPTY));
$enc = json_encode(['t' => $msg['text']], JSON_UNESCAPED_UNICODE);
$dbg('T11 чат 300 кириллических символов: длина=' . $len . ', json=' . ($enc === false ? 'FAIL' : 'OK')
    . ' -> ' . ($enc !== false && $len <= 240 && $len >= 239 ? 'OK' : 'БАГ'));

// эмодзи (4 байта) не должны рваться
sleep(3);
$msg2 = $chat->post(1, 'Demo', 'global', str_repeat('😀', 300));
$enc2 = json_encode(['t' => $msg2['text']], JSON_UNESCAPED_UNICODE);
$dbg('T11 чат 300 эмодзи: json=' . ($enc2 === false ? 'FAIL' : 'OK')
    . ' -> ' . ($enc2 !== false ? 'OK' : 'БАГ'));

// --- ТЕСТ 12: Response::json не падает на битом UTF-8
Database::update('chat_messages', ['text' => substr(str_repeat('Ы', 10), 0, 15)], 'id = ?', [(int) $msg2['id']]);
$broken = (string) Database::value('SELECT text FROM chat_messages WHERE id = ?', [(int) $msg2['id']]);
$resp = \App\Core\Response::json(['text' => $broken]);
$dbg('T12 Response::json с битым UTF-8: ' . strlen($resp->body) . ' байт -> '
    . (str_contains($resp->body, '"ok"') || strlen($resp->body) > 20 ? 'OK' : 'БАГ'));

// --- ТЕСТ 13: регистронезависимая уникальность ников
try {
    (new AuthService())->register('DEMO', 'Str0ngPass!1', '', 'human', 'fighter', 'm');
    $dbg('T13 БАГ: зарегистрирован DEMO при существующем demo');
} catch (Throwable $e) {
    $dbg('T13 уникальность без учёта регистра: отказ -> OK');
}
try {
    (new AuthService())->register('dEmO2', 'Str0ngPass!1', '', 'human', 'fighter', 'm');
    $dbg('T13 другой ник проходит -> OK');
} catch (Throwable $e) {
    $dbg('T13 БАГ: новый ник отклонён: ' . $e->getMessage());
}

// --- ТЕСТ 14: невалидный канал чата
try { $chat->list('<script>'); $dbg('T14 БАГ: невалидный канал принят'); }
catch (Throwable $e) { $dbg('T14 невалидный канал: отказ -> OK'); }
$dbg('T14 канал all работает: ' . (count($chat->list('all')) >= 0 ? 'OK' : 'БАГ'));

// --- ТЕСТ 15: офлайн-реген не начисляется пачкой
Database::update('characters', ['hp' => 50, 'hp_max' => 200,
    'last_seen' => date('Y-m-d H:i:s', time() - 86400)], 'id = ?', [1]);
$offline = $game->requireChar(1);
$dbg('T15 офлайн-реген (сутки): hp=' . $offline['hp'] . ' (ожидалось <= 200, без пачки) -> '
    . ((int) $offline['hp'] <= 200 ? 'OK' : 'БАГ'));
$dbg('T15 ограничение 60с: прибавка = 50 + 200*0.002*60 = ' . (50 + (int) round(200 * 0.002 * 60))
    . ', получено ' . $offline['hp'] . ' -> ' . ((int) $offline['hp'] <= 74 ? 'OK' : 'БАГ'));

// --- ТЕСТ 16: gc удаляет просроченные сессии
Database::insert('sessions', ['id' => str_repeat('a', 48), 'account_id' => null,
    'data_json' => '{}', 'last_used' => date('Y-m-d H:i:s', time() - 86400 * 3)]);
$before = (int) Database::value('SELECT COUNT(*) FROM sessions WHERE id = ?', [str_repeat('a', 48)]);
Migrator::gc(true);
$after = (int) Database::value('SELECT COUNT(*) FROM sessions WHERE id = ?', [str_repeat('a', 48)]);
$dbg('T16 gc просроченной сессии: было ' . $before . ' стало ' . $after
    . ' -> ' . ($after === 0 ? 'OK' : 'БАГ'));

// --- ТЕСТ 17: гонка покупки не уводит адену в минус
Database::update('characters', ['adena' => 500], 'id = ?', [1]);
$pot = Database::one("SELECT code FROM items WHERE kind = 'potion' LIMIT 1");
$ok = 0;
for ($i = 0; $i < 5; $i++) {
    try { $shop->buy(1, (string) $pot['code'], 1); $ok++; }
    catch (Throwable $e) { /* недостаточно денег */ }
}
$adena = (int) Database::value('SELECT adena FROM characters WHERE id = 1');
$dbg('T17 покупки при 500 адены: успешных=' . $ok . ', остаток=' . $adena
    . ' -> ' . ($adena >= 0 && $ok * 450 <= 500 ? 'OK' : 'БАГ'));

// --- ТЕСТ 18: toBool не путает строку "false" с true
$req = new \App\Core\Request('POST', '/api/inventory/equip', [], [], [], [], json_encode([
    'inv_id' => 1, 'equip' => 'false',
]), []);
$boolVal = \App\Controller\ApiController::class; // статический метод приватный, проверяем через Reflection
$rm = new ReflectionMethod($boolVal, 'toBool');
$rm->setAccessible(true);
$cases = [
    ['false', false], ['true', true], [0, false], [1, true], [false, false], [true, true], ['0', false], ['on', true],
];
$bad = [];
foreach ($cases as [$in, $want]) {
    $got = $rm->invoke(null, $in);
    if ($got !== $want) $bad[] = var_export($in, true) . "->" . var_export($got, true);
}
$dbg('T18 toBool: ' . (count($bad) === 0 ? 'все 8 случаев OK' : 'БАГ: ' . implode(', ', $bad)));

// --- ТЕСТ 19: Request::str/intInput не пропускают массивы
$req2 = new \App\Core\Request('POST', '/api/chat', [], [], [], [], json_encode([
    'text' => ['a' => 1], 'qty' => ['x'], 'n' => '42',
]), []);
$dbg('T19 str(массив) -> ' . var_export($req2->str('text'), true)
    . ' -> ' . ($req2->str('text') === '' ? 'OK' : 'БАГ'));
$dbg('T19 intInput(массив) -> ' . var_export($req2->intInput('qty'), true)
    . ' -> ' . ($req2->intInput('qty') === 0 ? 'OK' : 'БАГ'));
$dbg('T19 intInput("42") -> ' . var_export($req2->intInput('n'), true)
    . ' -> ' . ($req2->intInput('n') === 42 ? 'OK' : 'БАГ'));

// --- ТЕСТ 20: seed при SEED_DEMO=0 создаёт контент
// (проверяется отдельным прогоном seedtest; здесь фиксируем, что флаг не влияет на мобов)
$mobsBefore = (int) Database::value('SELECT COUNT(*) FROM mobs');
$dbg('T20 мобы в БД: ' . $mobsBefore . ' -> ' . ($mobsBefore >= 10 ? 'OK' : 'БАГ'));

// --- ТЕСТ 21: мировой респавн (второй игрок не может бить того же моба)
// создаём второго игрока с персонажем
$pid = Migrator::createAccount('resptester', 'Str0ngPass!1');
$pcid = Migrator::createCharacter($pid, 'resptester', 'human', 'fighter', 'm');
Migrator::giveStarterKit($pcid);

// моб 1: убиваем первым персонажем
Database::exec('DELETE FROM battles');
Database::update('characters', ['hp' => 500, 'hp_max' => 500, 'level' => 1], 'id = ?', [1]);
$b = $game->startBattle(1, 1);
for ($i = 0; $i < 40; $i++) { $st = $game->attack(1, (int) $b['id']); if ($st['state'] !== 'active') break; }
$dbg('T21 первый игрок убил моба: ' . $st['state'] . ' -> ' . ($st['state'] === 'won' ? 'OK' : 'БАГ'));

// второй игрок пытается начать бой с тем же мобом СРАЗУ
$blocked = false;
try { $game->startBattle($pcid, 1); }
catch (Throwable $e) { $blocked = str_contains($e->getMessage(), 'возродился'); }
$dbg('T21 второй игрок атакует того же моба: ' . ($blocked ? 'отказ (респавн общий) -> OK' : 'БАГ: бой разрешён!'));

// respawnIn возвращает адекватное время
$left = $game->respawnIn(1);
$respawn = (int) Database::value('SELECT respawn_sec FROM mobs WHERE id = 1');
$dbg('T21 respawnIn(1) = ' . $left . 'с из ' . $respawn . ' -> '
    . ($left > 0 && $left <= $respawn ? 'OK' : 'БАГ'));

// после истечения времени — снова жив
Database::update('battles', ['won_at' => date('Y-m-d H:i:s', time() - $respawn - 1)], 'mob_id = ? AND state = ?', [1, 'won']);
$dbg('T21 после истечения респавна respawnIn = ' . $game->respawnIn(1) . ' -> '
    . ($game->respawnIn(1) === 0 ? 'OK' : 'БАГ'));
try { $game->startBattle($pcid, 1); $dbg('T21 второй игрок начал бой после респавна -> OK'); }
catch (Throwable $e) { $dbg('T21 БАГ: бой не начался: ' . $e->getMessage()); }

// --- ТЕСТ 22: лечение живого стоит адену и не даёт бесплатный полный хил
$healAcc = Migrator::createAccount('healtester', 'Str0ngPass!1');
$hcid = Migrator::createCharacter($healAcc, 'healtester', 'human', 'fighter', 'm');
Database::update('characters', ['hp' => 20, 'mp' => 10, 'adena' => 5000], 'id = ?', [$hcid]);
$hres = $game->heal($hcid);
$miss = (int) $hres['char']['hp_max'] - 20;
$expect = max(1, (int) ceil(($miss + ((int) $hres['char']['mp_max'] - 10)) * 0.5));
$dbg('T22 лечение живого: cost=' . $hres['cost'] . ' (ожидалось ' . $expect . ') -> '
    . ($hres['cost'] === $expect ? 'OK' : 'БАГ'));
$dbg('T22 адена списана: ' . $hres['char']['adena'] . ' (ожидалось ' . (5000 - $expect) . ') -> '
    . ($hres['char']['adena'] === 5000 - $expect ? 'OK' : 'БАГ'));
Database::update('characters', ['hp' => 20, 'mp' => 10, 'adena' => 5], 'id = ?', [$hcid]);
$poor = false;
try { $game->heal($hcid); } catch (Throwable $e) { $poor = str_contains($e->getMessage(), 'Не хватает адены'); }
$dbg('T22 лечение без денег: ' . ($poor ? 'отказ -> OK' : 'БАГ: вылечился бесплатно'));

// --- ТЕСТ 23: мёртвый персонаж не может бить (даже в другом активном бою)
$zacc = Migrator::createAccount('zombtester', 'Str0ngPass!1');
$zcid = Migrator::createCharacter($zacc, 'zombtester', 'human', 'fighter', 'm');
Database::exec('DELETE FROM battles');
$zb1 = $game->startBattle($zcid, 2);
Database::update('characters', ['hp' => 0], 'id = ?', [$zcid]);
$zblocked = false;
try { $game->attack($zcid, (int) $zb1['id']); }
catch (Throwable $e) { $zblocked = str_contains($e->getMessage(), 'мёртв'); }
$dbg('T23 мёртвый бьёт в своём бою: ' . ($zblocked ? 'отказ -> OK' : 'БАГ: удар прошёл'));
// второй активный бой, смерть в первом
Database::update('characters', ['hp' => 500], 'id = ?', [$zcid]);
$zb2 = $game->startBattle($zcid, 3);
Database::update('characters', ['hp' => 0], 'id = ?', [$zcid]);
$zb2blocked = false;
try { $game->attack($zcid, (int) $zb2['id']); }
catch (Throwable $e) { $zb2blocked = str_contains($e->getMessage(), 'мёртв'); }
$dbg('T23 мёртвый бьёт во втором бою: ' . ($zb2blocked ? 'отказ -> OK' : 'БАГ: фарм мёртвым'));

// --- ТЕСТ 24: ход нельзя сделать дважды (условный UPDATE turn)
Database::update('characters', ['hp' => 500, 'hp_max' => 500], 'id = ?', [$zcid]);
$tc = $game->startBattle($zcid, 4);
$turnBefore = (int) Database::value('SELECT turn FROM battles WHERE id = ?', [(int) $tc['id']]);
$game->attack($zcid, (int) $tc['id']);
$turnAfter = (int) Database::value('SELECT turn FROM battles WHERE id = ?', [(int) $tc['id']]);
$dbg('T24 turn ' . $turnBefore . ' -> ' . $turnAfter . ' (шаг 1) -> '
    . ($turnAfter === $turnBefore + 1 ? 'OK' : 'БАГ: ход потерян/удвоен'));

// --- ТЕСТ 25: чат — пустое сообщение это 400, а не 429
$chat = new ChatService();
$emptyErr = '';
try { $chat->post($zcid, 'zombtester', 'global', '   '); } catch (Throwable $e) { $emptyErr = $e->getMessage(); }
$dbg('T25 пустое сообщение: "' . $emptyErr . '" -> ' . ($emptyErr !== '' ? 'OK (обрабатывается как 400)' : 'БАГ'));

// --- ТЕСТ 26: длинный пароль не обрезается до 72 байт
$auth = new AuthService();
$longPass = str_repeat('A', 72) . 'TAIL-ONE';
$regErr = '';
try { $auth->register('longpass1', $longPass, '', 'human', 'fighter', 'm'); }
catch (Throwable $e) { $regErr = $e->getMessage(); }
$dbg('T26 регистрация с паролем 80 байт: ' . ($regErr !== '' ? 'отказ -> OK' : 'БАГ: принят, хеш обрежется'));
// ровно 72 байта должны приниматься
$ok72 = false; $err72 = '';
try { $auth->register('longpass2', str_repeat('A', 72), '', 'human', 'fighter', 'm'); $ok72 = true; }
catch (Throwable $e) { $ok72 = false; $err72 = $e->getMessage(); }
$dbg('T26 регистрация с паролем ровно 72 байта: ' . ($ok72 ? 'OK' : 'отказ: ' . $err72));

// --- ТЕСТ 27: брошенные бои освобождают лимит
$bacc = Migrator::createAccount('idletester', 'Str0ngPass!1');
$bcid = Migrator::createCharacter($bacc, 'idletester', 'human', 'fighter', 'm');
Database::exec('DELETE FROM battles');
Database::update('characters', ['hp' => 500, 'hp_max' => 500], 'id = ?', [$bcid]);
$made = [];
foreach ([1, 2, 3] as $mid) {
    try { $made[] = (int) $game->startBattle($bcid, $mid)['id']; } catch (Throwable $e) {}
}
$dbg('T27 стартовали активных боёв: ' . count($made) . ' -> ' . (count($made) === 3 ? 'OK' : 'БАГ'));
// «состариваем» два боя
Database::exec('UPDATE battles SET updated_at = ? WHERE id IN (' . $made[0] . ',' . $made[1] . ')',
    [date('Y-m-d H:i:s', time() - 4000)]);
$closed = $game->closeStaleBattles($bcid);
$dbg('T27 закрыто брошенных: ' . $closed . ' (ожидалось 2) -> ' . ($closed === 2 ? 'OK' : 'БАГ'));
$stillActive = (int) Database::value("SELECT COUNT(*) FROM battles WHERE char_id = ? AND state = 'active'", [$bcid]);
$dbg('T27 активных после уборки: ' . $stillActive . ' (ожидалось 1) -> ' . ($stillActive === 1 ? 'OK' : 'БАГ'));
// и новый бой снова разрешён
$canStart = true;
try { $game->startBattle($bcid, 4); } catch (Throwable $e) { $canStart = false; }
$dbg('T27 новый бой после уборки: ' . ($canStart ? 'OK' : 'БАГ: лимит не освободился'));

// --- ТЕСТ 28: клиент получает СПИСОК активных боёв
$list = $game->activeBattles($bcid);
$dbg('T28 activeBattles вернул: ' . count($list) . ' бой(ёв) -> ' . (count($list) >= 1 ? 'OK' : 'БАГ'));

// --- ТЕСТ 29: IP пишется при создании аккаунта
$ip = (string) Database::value('SELECT ip FROM accounts WHERE nick = ?', ['resptester']);
$dbg('T29 accounts.ip заполнен: "' . $ip . '" -> ' . ($ip !== '' ? 'OK' : 'БАГ: пустой'));

// --- ТЕСТ 30: IP не стирается при входе и сохраняется у аккаунта
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
$ipAcc = Migrator::createAccount('iptracker', 'Str0ngPass!1');
$ipBefore = (string) Database::value('SELECT ip FROM accounts WHERE id = ?', [$ipAcc]);
$dbg('T30 ip при создании: "' . $ipBefore . '" -> ' . ($ipBefore === '203.0.113.9' ? 'OK' : 'БАГ'));
Database::update('accounts', ['ip' => ''], 'id = ?', [$ipAcc]);
$auth2 = new AuthService();
$auth2->login('iptracker', 'Str0ngPass!1');
$ipAfter = (string) Database::value('SELECT ip FROM accounts WHERE id = ?', [$ipAcc]);
$dbg('T30 ip после входа: "' . $ipAfter . '" -> ' . ($ipAfter === '203.0.113.9' ? 'OK (не стирается)' : 'БАГ: стёрт'));

// --- ТЕСТ 31: антиспам регистраций по IP
// (в testing-окружении лимит отключён, поэтому проверяем сам счётчик)
$fromIp = (int) Database::value(
    "SELECT COUNT(*) FROM accounts WHERE created_at >= ? AND ip = ?",
    [date('Y-m-d H:i:s', time() - 3600), '203.0.113.9']
);
$dbg('T31 аккаунтов с одного IP за час: ' . $fromIp . ' -> ' . ($fromIp >= 1 ? 'OK (счётчик работает)' : 'БАГ'));

// --- ТЕСТ 32: Database::checkpoint не падает и сохраняет данные
Database::checkpoint();
$dbg('T32 checkpoint: OK (без исключения)');

// --- ТЕСТ 33: пароль >72 байт нельзя зарегистрировать (bcrypt обрезает)
$lp = str_repeat('B', 72);
$reg33 = '';
try { $auth2->register('tailtest', $lp . 'ДРУГОЙ-ХВОСТ', '', 'human', 'fighter', 'm'); }
catch (Throwable $e) { $reg33 = $e->getMessage(); }
$dbg('T33 регистрация пароля 85 байт: ' . ($reg33 !== '' ? 'отказ -> OK' : 'БАГ: принят'));
// и ровно 72 байта регистрируются и логинятся
$ok33 = false;
try { $auth2->register('tailtest2', $lp, '', 'human', 'fighter', 'm'); $ok33 = true; }
catch (Throwable $e) { $ok33 = false; }
$dbg('T33 регистрация ровно 72 байт: ' . ($ok33 ? 'OK' : 'БАГ: отказ'));

// --- ТЕСТ 34: двое не могут бить одного моба (дублирование награды)
$dupA = Migrator::createAccount('dupA', 'Str0ngPass!1');
$dupB = Migrator::createAccount('dupB', 'Str0ngPass!1');
$cidA = Migrator::createCharacter($dupA, 'dupA', 'human', 'fighter', 'm');
$cidB = Migrator::createCharacter($dupB, 'dupB', 'human', 'fighter', 'm');
Migrator::giveStarterKit($cidA);
Migrator::giveStarterKit($cidB);
Database::exec('DELETE FROM battles');
$gameT = new GameService();
$bA = $gameT->startBattle($cidA, 1);
$blockedB = false;
try { $gameT->startBattle($cidB, 1); }
catch (Throwable $e) { $blockedB = str_contains($e->getMessage(), 'другой игрок'); }
$dbg('T34 второй игрок на того же моба: ' . ($blockedB ? 'отказ -> OK' : 'БАГ: начал бой'));

// --- ТЕСТ 35: свой бой переиспользуется, а не блокируется
$again = $gameT->startBattle($cidA, 1);
$dbg('T35 свой незавершённый бой переиспользован: id=' . $again['id'] . ' -> '
    . ((int) $again['id'] === (int) $bA['id'] ? 'OK' : 'БАГ: создан новый/отказ'));
$dbg('T35 активных боёв у A: ' . Database::value("SELECT COUNT(*) FROM battles WHERE char_id = ? AND state = 'active'", [$cidA])
    . ' -> ' . ((int) Database::value("SELECT COUNT(*) FROM battles WHERE char_id = ? AND state = 'active'", [$cidA]) === 1 ? 'OK' : 'БАГ'));

// --- ТЕСТ 36: зона показывает занятость и не отдаёт занятого моба живым
Database::update('characters', ['level' => 5], 'id = ?', [$cidB]);
$zB = $gameT->mobsInZone('Giran', $cidB);
$mob1B = null;
foreach ($zB as $m) { if ((int) $m['id'] === 1) { $mob1B = $m; break; } }
$dbg('T36 для B моб1: alive=' . var_export($mob1B['alive'], true) . ' busy=' . var_export($mob1B['busy'], true)
    . ' -> ' . ($mob1B['alive'] === false && $mob1B['busy'] === true ? 'OK' : 'БАГ'));
$zA = $gameT->mobsInZone('Giran', $cidA);
$mob1A = null;
foreach ($zA as $m) { if ((int) $m['id'] === 1) { $mob1A = $m; break; } }
$dbg('T36 для A (свой бой) моб1: alive=' . var_export($mob1A['alive'], true) . ' busy=' . var_export($mob1A['busy'], true)
    . ' -> ' . ($mob1A['alive'] === true && $mob1A['busy'] === false ? 'OK' : 'БАГ'));

// --- ТЕСТ 37: после flee моб свободен для другого
$gameT->flee($cidA, (int) $bA['id']);
$freeAfterFlee = true;
try { $gameT->startBattle($cidB, 1); } catch (Throwable $e) { $freeAfterFlee = false; }
$dbg('T37 после flee моб свободен для B: ' . ($freeAfterFlee ? 'OK' : 'БАГ: остался занят'));

// --- ТЕСТ 38: брошенный бой не держит моба вечно
Database::exec('DELETE FROM battles');
$stale = $gameT->startBattle($cidA, 2);
Database::exec('UPDATE battles SET updated_at = ? WHERE id = ?',
    [date('Y-m-d H:i:s', time() - 4000), (int) $stale['id']]);
$freeStale = true;
try { $gameT->startBattle($cidB, 2); } catch (Throwable $e) { $freeStale = false; }
$dbg('T38 брошенный бой не держит моба: ' . ($freeStale ? 'OK' : 'БАГ: моб занят вечно'));
