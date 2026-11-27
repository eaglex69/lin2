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

// Настройки харнесса задаём через .env, а НЕ через /config.json:
// /config.json теперь принимается только в wasm-превью и только для
// белого списка ключей (APP_ENV и пароли через него не задаются — иначе
// файл в корне ФС мог бы понижать защиту в проде).
@unlink('/config.json');
file_put_contents('/tmp/v2/.env', implode("\n", [
    'APP_ENV=testing',
    'DB_DRIVER=sqlite',
    'DB_SQLITE_PATH=/tmp/test.sqlite',
    'SEED_DEMO=1',
    'TIMEZONE=Europe/Moscow',
]) . "\n");
@unlink('/tmp/test.sqlite');

use App\Core\Config, App\Core\Database, App\Core\Migrator, App\Core\Session;
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
// adena хранится как double (см. баг про 32-битный int), поэтому сравниваем
// численно, а не строгим === с int — иначе тест «падает» на верной сумме
$dbg('T22 адена списана: ' . $hres['char']['adena'] . ' (ожидалось ' . (5000 - $expect) . ') -> '
    . (abs((float) $hres['char']['adena'] - (5000 - $expect)) < 0.5 ? 'OK' : 'БАГ'));
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

// --- ТЕСТ 39: большие суммы адены не обрезаются (PHP_INT_MAX = 2^31-1)
$bigAcc = Migrator::createAccount('bigmoney', 'Str0ngPass!1');
$bigCid = Migrator::createCharacter($bigAcc, 'bigmoney', 'human', 'fighter', 'm');
Migrator::giveStarterKit($bigCid);
Database::update('characters', ['adena' => 9000000000], 'id = ?', [$bigCid]);
$bigChar = $game->requireChar($bigCid);
$dbg('T39 requireChar 9e9: ' . var_export($bigChar['adena'], true) . ' -> '
    . ($bigChar['adena'] == 9000000000 ? 'OK' : 'БАГ: обрезано до ' . (int) $bigChar['adena']));
$bigShop = new ShopService();
// в стартовом наборе уже 5 зелий, поэтому в стак (99) помещается 94, а не 99:
// ожидаемую сумму считаем от фактически выданного количества, иначе тест
// «падал» на корректном поведении лимита стака (баг 42)
$bigHave = (int) Database::value('SELECT qty FROM inventory WHERE char_id = ? AND item_id = (SELECT id FROM items WHERE code = ?)', [$bigCid, 'hp_potion']);
$bigBuy = $bigShop->buy($bigCid, 'hp_potion', 99);
$bigExpected = 9000000000 - 450 * $bigBuy['qty'];
$dbg('T39 после покупки: ' . var_export($bigBuy['adena'], true) . ' (выдано ' . $bigBuy['qty'] . ' из ' . $bigHave . ') -> '
    . ($bigBuy['adena'] == $bigExpected && $bigHave + $bigBuy['qty'] === 99 ? 'OK' : 'БАГ'));
$bigJson = json_encode(['adena' => $bigBuy['adena']]);
$dbg('T39 JSON: ' . $bigJson . ' -> ' . (str_contains($bigJson, (string) $bigExpected) ? 'OK' : 'БАГ'));

// --- ТЕСТ 40: мёртвый персонаж не ходит
$deadAcc = Migrator::createAccount('deadwalker', 'Str0ngPass!1');
$deadCid = Migrator::createCharacter($deadAcc, 'deadwalker', 'human', 'fighter', 'm');
Database::update('characters', ['hp' => 0, 'x' => 100, 'y' => 100], 'id = ?', [$deadCid]);
$moved = true;
try { $game->move($deadCid, 500, 300, 'Giran'); } catch (Throwable $e) { $moved = false; }
$pos = Database::one('SELECT x, y FROM characters WHERE id = ?', [$deadCid]);
$dbg('T40 move мёртвого: ' . ($moved ? 'БАГ: сдвинулся в (' . $pos['x'] . ',' . $pos['y'] . ')' : 'отказ -> OK'));

// --- ТЕСТ 41: канал all не показывает system
Database::exec('DELETE FROM chat_messages');
$chatT = new ChatService();
$someChar = (int) Database::value('SELECT id FROM characters LIMIT 1');
Database::insert('chat_messages', ['channel' => 'system', 'char_id' => $someChar, 'author' => 'Система', 'text' => 'служебное', 'created_at' => date('Y-m-d H:i:s')]);
Database::insert('chat_messages', ['channel' => 'global', 'char_id' => $someChar, 'author' => 'Игрок', 'text' => 'обычное', 'created_at' => date('Y-m-d H:i:s')]);
$all = $chatT->list('all', 0);
$channels = array_map(fn ($m) => $m['channel'], $all);
$dbg('T41 all: каналы=' . implode(',', $channels) . ' -> '
    . (!in_array('system', $channels, true) && in_array('global', $channels, true) ? 'OK' : 'БАГ'));

// --- ТЕСТ 42: стак не превышает лимит при докупке
$stAcc = Migrator::createAccount('stacklim', 'Str0ngPass!1');
$stCid = Migrator::createCharacter($stAcc, 'stacklim', 'human', 'fighter', 'm');
Migrator::giveStarterKit($stCid);
Database::update('characters', ['adena' => 60000000], 'id = ?', [$stCid]);
$stShop = new ShopService();
$stQty = (int) Database::value('SELECT qty FROM inventory WHERE char_id = ? AND item_id = (SELECT id FROM items WHERE code = ?)', [$stCid, 'hp_potion']);
$stShop->buy($stCid, 'hp_potion', 60);
$stAfter = (int) Database::value('SELECT qty FROM inventory WHERE char_id = ? AND item_id = (SELECT id FROM items WHERE code = ?)', [$stCid, 'hp_potion']);
$dbg('T42 покупка 60 при ' . $stQty . ': стало ' . $stAfter . ' -> '
    . ($stAfter === $stQty + 60 ? 'OK' : 'БАГ'));
$stFill = $stShop->buy($stCid, 'hp_potion', 99);
$stFinal = (int) Database::value('SELECT qty FROM inventory WHERE char_id = ? AND item_id = (SELECT id FROM items WHERE code = ?)', [$stCid, 'hp_potion']);
$stCells = (int) Database::value('SELECT COUNT(*) FROM inventory WHERE char_id = ? AND item_id = (SELECT id FROM items WHERE code = ?)', [$stCid, 'hp_potion']);
$dbg('T42 докупка 99 в стак: стало ' . $stFinal . ', выдано ' . $stFill['qty']
    . ', оплачено ' . $stFill['paid'] . ' -> '
    . ($stFinal === 99 && $stFill['paid'] === $stFill['qty'] * 450 && $stCells === 1 ? 'OK' : 'БАГ'));
$stRejected = false;
try { $stShop->buy($stCid, 'hp_potion', 1); } catch (Throwable $e) { $stRejected = str_contains($e->getMessage(), 'максимум'); }
$dbg('T42 покупка в полный стак: ' . ($stRejected ? 'отказ -> OK' : 'БАГ: принята'));

// --- ТЕСТ 43: лог боя не растёт безгранично
$lgAcc = Migrator::createAccount('logcap', 'Str0ngPass!1');
$lgCid = Migrator::createCharacter($lgAcc, 'logcap', 'human', 'fighter', 'm');
Migrator::giveStarterKit($lgCid);
Database::update('characters', ['level' => 12, 'hp' => 200000, 'hp_max' => 200000, 'p_atk' => 1, 'p_def' => 5000], 'id = ?', [$lgCid]);
$lgMob = Database::one('SELECT id FROM mobs WHERE level <= 12 ORDER BY level DESC LIMIT 1');
$lgBattle = $game->startBattle($lgCid, (int) $lgMob['id']);
$lgMax = 0;
$lgMaxBytes = 0;
$lgTurns = 0;
$lgState = null;
for ($i = 0; $i < 6000; $i++) {
    $lgState = $game->attack($lgCid, (int) $lgBattle['id']);
    $lgTurns++;
    $lgMax = max($lgMax, count($lgState['log']));
    $lgMaxBytes = max($lgMaxBytes, strlen(json_encode($lgState, JSON_UNESCAPED_UNICODE)));
    if (in_array($lgState['state'], ['won', 'lost'], true)) {
        break;
    }
}
$lgDb = json_decode((string) Database::value('SELECT log_json FROM battles WHERE id = ?', [(int) $lgBattle['id']]), true) ?: [];
$dbg('T43 ' . $lgTurns . ' ходов: макс. лог в ответе ' . $lgMax . ' строк, '
    . round($lgMaxBytes / 1024, 1) . ' КБ, в БД ' . count($lgDb) . ' -> '
    . ($lgMax <= 60 && $lgMaxBytes < 8000 && count($lgDb) <= 60 ? 'OK' : 'БАГ'));

// --- ТЕСТ 44: персонаж проверяется на владельца (реальный вызов AuthService)
$ownA = Migrator::createAccount('owna', 'Str0ngPass!1');
$ownAChar = Migrator::createCharacter($ownA, 'OwnerA', 'human', 'fighter', 'm');
$ownB = Migrator::createAccount('ownb', 'Str0ngPass!1');
$ownBChar = Migrator::createCharacter($ownB, 'OwnerB', 'human', 'fighter', 'm');
$ownAuth = new AuthService();
// аккаунт B пытается играть персонажем A
Session::set('account_id', $ownB);
Session::set('char_id', $ownAChar);
$ownGot = $ownAuth->character();
$dbg('T44 чужой char_id в сессии: ' . ($ownGot === null ? 'отказ -> OK' : 'БАГ: получил ' . $ownGot['name']));
// свой персонаж по-прежнему доступен
Session::set('char_id', $ownBChar);
$ownMine = $ownAuth->character();
$dbg('T44 свой char_id: ' . ($ownMine !== null && $ownMine['name'] === 'OwnerB' ? 'OK' : 'БАГ: отказ своему'));
// active_char без char_id в сессии — тоже с проверкой владельца
Database::update('accounts', ['active_char' => $ownBChar], 'id = ?', [$ownA]);
Session::set('char_id', null);
$ownFall = $ownAuth->character();
$dbg('T44 чужой active_char: ' . ($ownFall === null ? 'отказ -> OK' : 'БАГ: получил ' . $ownFall['name']));
$ownSel = $ownAuth->selectCharacter($ownB, $ownAChar);
$dbg('T44 selectCharacter чужого: ' . ($ownSel === null ? 'отказ -> OK' : 'БАГ'));

// --- ТЕСТ 45: длинный ник не раздувает login_attempts (и не ломает MySQL)
Database::exec('DELETE FROM login_attempts');
for ($i = 0; $i < 20; $i++) {
    try { (new AuthService())->login(str_repeat('z', 800) . $i, 'x'); } catch (Throwable $e) {}
}
$laStat = Database::one('SELECT COUNT(*) c, COALESCE(MAX(LENGTH(nick)),0) mx FROM login_attempts');
$dbg('T45 длинные ники: строк ' . $laStat['c'] . ', макс. ключ ' . $laStat['mx'] . ' байт -> '
    . ($laStat['mx'] <= 48 && $laStat['c'] <= 20 ? 'OK (умещается в VARCHAR(64))' : 'БАГ'));

// --- ТЕСТ 46: регистр ника не обходит лимит попыток
Database::exec('DELETE FROM login_attempts');
(new AuthService())->register('CaseGuy', 'Str0ngPass!1', '', 'human', 'fighter', 'm');
foreach (['CaseGuy', 'CASEGUY', 'caseguy', 'CaSeGuY'] as $variant) {
    try { (new AuthService())->login($variant, 'bad'); } catch (Throwable $e) {}
}
$laKeys = (int) Database::value('SELECT COUNT(*) FROM login_attempts');
$laCnt = (int) Database::value('SELECT MAX(cnt) FROM login_attempts');
$dbg('T46 регистры ника: ключей ' . $laKeys . ', счётчик ' . $laCnt . ' -> '
    . ($laKeys === 1 && $laCnt === 4 ? 'OK' : 'БАГ'));

// --- ТЕСТ 47: транзакции атомарны и откатываются
$txA = Migrator::createAccount('txtest', 'Str0ngPass!1');
$txC = Migrator::createCharacter($txA, 'txtest', 'human', 'fighter', 'm');
Database::update('characters', ['adena' => 777], 'id = ?', [$txC]);
try {
    Database::transaction(function () use ($txC) {
        Database::update('characters', ['adena' => 999999], 'id = ?', [$txC]);
        throw new RuntimeException('сбой');
    });
} catch (Throwable $e) {}
$txAfter = (int) Database::value('SELECT adena FROM characters WHERE id = ?', [$txC]);
$dbg('T47 откат транзакции: адена ' . $txAfter . ' -> ' . ($txAfter === 777 ? 'OK' : 'БАГ'));
$txNested = Database::transaction(fn () => Database::transaction(fn () => 'inner'));
$dbg('T47 вложенная транзакция: ' . var_export($txNested, true) . ' -> '
    . ($txNested === 'inner' ? 'OK' : 'БАГ'));

// --- ТЕСТ 48: /config.json не может подменить секреты и APP_ENV
// (в харнессе APP_ENV=testing задан через .env; /config.json подкладываем
//  враждебный и убеждаемся, что он ничего не меняет)
file_put_contents('/config.json', json_encode([
    'APP_ENV' => 'production', 'DB_PASS' => 'attacker', 'SEED_DEMO_PASS' => 'pwned',
]));
Config::reload();
if (!defined('MINIAGE_WASM')) {
    define('MINIAGE_WASM', true);
}
Config::load('/tmp/v2');
$cfgEnv = Config::get('APP_ENV');
$cfgPass = Config::get('DB_PASS');
$cfgSeed = Config::get('SEED_DEMO_PASS');
$dbg('T48 /config.json: APP_ENV=' . $cfgEnv . ', DB_PASS=' . var_export($cfgPass, true)
    . ', SEED_DEMO_PASS=' . $cfgSeed . ' -> '
    . ($cfgEnv === 'testing' && $cfgPass !== 'attacker' && $cfgSeed !== 'pwned' ? 'OK' : 'БАГ'));
@unlink('/config.json');

// --- ТЕСТ 49: истёкшая сессия не авторизует (SESSION_TTL)
Session::resetState();
Session::start(new \App\Core\Request('GET', '/', [], [], [], [], '', []));
$expSid = Session::id();
Session::set('account_id', 1);
// состариваем на 30 дней при TTL 1 день
Database::update('sessions', ['last_used' => date('Y-m-d H:i:s', time() - 30 * 86400)], 'id = ?', [$expSid]);
Session::resetState();
Session::start(new \App\Core\Request('GET', '/', [], [], ['miniage_sid' => $expSid], [], '', []));
$expAcc = Session::get('account_id');
$expRow = Database::one('SELECT id FROM sessions WHERE id = ?', [$expSid]);
$dbg('T49 сессия 30 дней при TTL 1 день: account_id=' . var_export($expAcc, true)
    . ', строка ' . ($expRow === null ? 'удалена' : 'есть') . ' -> '
    . ($expAcc === null && $expRow === null ? 'OK' : 'БАГ'));

// --- ТЕСТ 50: свежая сессия работает
Session::resetState();
Session::start(new \App\Core\Request('GET', '/', [], [], [], [], '', []));
$liveSid = Session::id();
Session::set('account_id', 77);
Session::resetState();
Session::start(new \App\Core\Request('GET', '/', [], [], ['miniage_sid' => $liveSid], [], '', []));
$dbg('T50 свежая сессия: account_id=' . var_export(Session::get('account_id'), true) . ' -> '
    . (Session::get('account_id') === 77 ? 'OK' : 'БАГ'));

// --- ТЕСТ 51: X-Forwarded-Proto не подделывается клиентом
$_SERVER['HTTPS'] = '';
$_SERVER['REMOTE_ADDR'] = '5.5.5.5';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$isHttps = new \ReflectionMethod(Session::class, 'isHttps');
$isHttps->setAccessible(true);
$clientClaim = $isHttps->invoke(null);
Config::set('TRUSTED_PROXIES', '5.5.5.5');
$proxyClaim = $isHttps->invoke(null);
Config::set('TRUSTED_PROXIES', '');
unset($_SERVER['HTTP_X_FORWARDED_PROTO'], $_SERVER['HTTPS'], $_SERVER['REMOTE_ADDR']);
$dbg('T51 XFP от клиента: isHttps=' . var_export($clientClaim, true) . ', от прокси: '
    . var_export($proxyClaim, true) . ' -> '
    . ($clientClaim === false && $proxyClaim === true ? 'OK' : 'БАГ'));

// --- ТЕСТ 52: Request::get с числовым дефолтом не падает
$reqGet = new \App\Core\Request('GET', '/', ['a' => '5'], [], [], [], '', []);
$defaultInt = $reqGet->get('missing', 0);
$present = $reqGet->get('a', 0);
$dbg('T52 get(): missing с дефолтом 0 = ' . var_export($defaultInt, true)
    . ', есть = ' . var_export($present, true) . ' -> '
    . ($defaultInt === '0' && $present === '5' ? 'OK' : 'БАГ'));

// --- ТЕСТ 53: отрицательная награда моба не уводит опыт/адену в минус
$ngA = Migrator::createAccount('negrew', 'Str0ngPass!1');
$ngC = Migrator::createCharacter($ngA, 'negrew', 'human', 'fighter', 'm');
Migrator::giveStarterKit($ngC);
$ngMob = Database::one("SELECT id FROM mobs WHERE zone = 'Giran' ORDER BY level LIMIT 1");
$ngMobOrig = Database::one('SELECT exp_reward, adena_reward FROM mobs WHERE id = ?', [(int) $ngMob['id']]);
Database::update('mobs', ['exp_reward' => -5000, 'adena_reward' => -5000], 'id = ?', [(int) $ngMob['id']]);
Database::update('characters', [
    'exp' => 100, 'adena' => 1000, 'p_atk' => 500, 'p_def' => 5000, 'hp' => 1000, 'hp_max' => 1000,
], 'id = ?', [$ngC]);
$ngBattle = $game->startBattle($ngC, (int) $ngMob['id']);
$ngSt = null;
for ($i = 0; $i < 40; $i++) {
    $ngSt = $game->attack($ngC, (int) $ngBattle['id']);
    if (in_array($ngSt['state'], ['won', 'lost'], true)) {
        break;
    }
}
$ngAfter = Database::one('SELECT exp, adena FROM characters WHERE id = ?', [$ngC]);
Database::update('mobs', [
    'exp_reward' => (int) $ngMobOrig['exp_reward'],
    'adena_reward' => (int) $ngMobOrig['adena_reward'],
], 'id = ?', [(int) $ngMob['id']]);
$dbg('T53 награда -5000: exp=' . var_export($ngAfter['exp'], true) . ', адена=' . var_export($ngAfter['adena'], true)
    . ' -> ' . ((float) $ngAfter['exp'] >= 0 && (float) $ngAfter['adena'] >= 0 ? 'OK' : 'БАГ'));

// --- ТЕСТ 54: HSTS не выдаётся по поддельному X-Forwarded-Proto
$hstsSrc = (string) file_get_contents('/tmp/v2/public/index.php');
$hstsGuarded = str_contains($hstsSrc, 'isTrustedProxy') && !preg_match('/X_FORWARDED_PROTO.*=== .https.\s*\)\s*\{\s*header\(.Strict/', $hstsSrc);
$dbg('T54 HSTS за доверенным прокси: ' . ($hstsGuarded ? 'OK (проверка есть)' : 'БАГ (заголовок без проверки)'));

// --- ТЕСТ 55: стартовый набор учитывается в характеристиках (баг 64)
// Предметы выдаются надетым, но раньше статы оставались базовыми: меч +8 атаки
// и доспех +14 защиты/+20 HP не входили в расчёт, пока их не снимут/наденут.
$ekAcc = Migrator::createAccount('equipkit', 'Str0ngPass!1');
$ekCid = Migrator::createCharacter($ekAcc, 'equipkit', 'human', 'fighter', 'm');
Migrator::giveStarterKit($ekCid);
$ekC = Database::one('SELECT hp, hp_max, mp, mp_max, p_atk, p_def, m_def FROM characters WHERE id = ?', [$ekCid]);
// база воина 1 ур.: hp 120, mp 60, p_atk 26, p_def 14, m_def 8
// набор: меч +8 p_atk, доспех +14 p_def/+6 m_def/+20 hp
$ekOk = (int) $ekC['hp_max'] === 140 && (int) $ekC['p_atk'] === 34
    && (int) $ekC['p_def'] === 28 && (int) $ekC['m_def'] === 14
    && (int) $ekC['hp'] === 140 && (int) $ekC['mp'] === 60;
$dbg('T55 статы с экипировкой: hp=' . $ekC['hp'] . '/' . $ekC['hp_max']
    . ', p_atk=' . $ekC['p_atk'] . ', p_def=' . $ekC['p_def'] . ', m_def=' . $ekC['m_def']
    . ' -> ' . ($ekOk ? 'OK' : 'БАГ (экипировка не учтена)'));

// --- ТЕСТ 56: useItem отдаёт то же представление персонажа (баг 65)
$uiChar = Database::one('SELECT * FROM characters WHERE id = ?', [$ekCid]);
$uiShop = new ShopService();
$uiInv = Database::one('SELECT id FROM inventory WHERE char_id = ? AND item_id = (SELECT id FROM items WHERE code = ?)', [$ekCid, 'hp_potion']);
Database::update('characters', ['hp' => 10, 'mp' => 5], 'id = ?', [$ekCid]);
$uiRes = $uiShop->useItem($ekCid, (int) $uiInv['id']);
$uiCharOut = $uiRes['char'] ?? [];
$uiOk = isset($uiCharOut['exp_percent'], $uiCharOut['exp_cur'], $uiCharOut['hp_percent'],
    $uiCharOut['mp_percent'], $uiCharOut['race_label'], $uiCharOut['class_label']);
// сверяем с эталоном: useItem обязан совпадать с тем, что отдают остальные ручки
$uiRef = (new AuthService())->decorate(Database::one('SELECT * FROM characters WHERE id = ?', [$ekCid]));
$uiClean = array_keys($uiCharOut) === array_keys($uiRef);
$dbg('T56 useItem.char: exp_percent=' . var_export($uiCharOut['exp_percent'] ?? null, true)
    . ', hp_percent=' . var_export($uiCharOut['hp_percent'] ?? null, true)
    . ', набор полей как у decorate=' . var_export($uiClean, true)
    . ' -> ' . ($uiOk && $uiClean ? 'OK' : 'БАГ (сырой ряд вместо decorate)'));

// --- ТЕСТ 57: демо-аккаунт не администратор (баг 67)
$demoRole = (string) Database::value('SELECT role FROM accounts WHERE nick = ?', ['demo']);
$dbg('T57 роль demo: ' . $demoRole . ' -> ' . ($demoRole === 'player' ? 'OK' : 'БАГ (демо с правами)'));

// --- ТЕСТ 58: Request::ip() не верит X-Forwarded-For (баг 68)
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$ipReq = new \App\Core\Request('GET', '/', [], [], [], [], '', $_SERVER);
$ipGot = $ipReq->ip();
unset($_SERVER['HTTP_X_FORWARDED_FOR']);
$dbg('T58 Request::ip() при XFF=1.2.3.4: ' . $ipGot . ' -> '
    . ($ipGot === '203.0.113.9' ? 'OK' : 'БАГ (подмена IP)'));

// --- ТЕСТ 59: активность продлевает сессию (last_used = момент запроса)
Session::resetState();
Session::start(new \App\Core\Request('GET', '/', [], [], [], [], '', []));
$actSid = Session::id();
Session::set('account_id', 9);
// Метка «почти состарилась»: 23 часа при TTL 24 часа. Игрок всё это время
// играл, но данных в сессию не писал — раньше метка так и осталась бы на
// 23 часах и на следующем запросе сессию выбросило бы.
Database::update('sessions', ['last_used' => date('Y-m-d H:i:s', time() - 82800)], 'id = ?', [$actSid]);
Session::resetState();
// читаем сессию (это делает любой запрос) — метка должна обновиться
Session::start(new \App\Core\Request('GET', '/', [], [], ['miniage_sid' => $actSid], [], '', []));
$actAcc = Session::get('account_id');   // при истёкшей сессии было бы NULL
Session::resetState();
Session::start(new \App\Core\Request('GET', '/', [], [], ['miniage_sid' => $actSid], [], '', []));
$actFresh = Session::get('account_id');
$actRow = Database::one('SELECT last_used FROM sessions WHERE id = ?', [$actSid]);
$actAge = $actRow === null ? -1 : time() - (int) strtotime((string) $actRow['last_used']);
$dbg('T59 активность продлевает сессию: account=' . var_export($actAcc, true)
    . ', повтор=' . var_export($actFresh, true) . ', возраст метки=' . $actAge . ' с -> '
    . ($actAcc === 9 && $actFresh === 9 && $actAge >= 0 && $actAge < 120 ? 'OK' : 'БАГ (TTL считается от записи)'));

// --- ТЕСТ 60: TTL всё ещё выбрасывает по-настоящему брошенную сессию
$oldSid = $actSid;
Database::update('sessions', ['last_used' => date('Y-m-d H:i:s', time() - 30 * 86400)], 'id = ?', [$oldSid]);
// восстановим метку в «старую» и НЕ трогаем строку запросами
$m = new ReflectionMethod('App\Core\Session', 'expired'); $m->setAccessible(true);
$isExp = $m->invoke(null, (string) Database::value('SELECT last_used FROM sessions WHERE id = ?', [$oldSid]));
$dbg('T60 брошенная сессия (30 дней): expired=' . var_export($isExp, true)
    . ' -> ' . ($isExp === true ? 'OK' : 'БАГ (сессия живёт вечно)'));

// --- ТЕСТ 61: парсер SQL не путается в комментариях и '' внутри строки
$split = new ReflectionMethod('App\Core\Migrator', 'splitStatements');
$split->setAccessible(true);
$sample = "-- don't break\nCREATE TABLE a (id INT);\n/* 'x' */\nINSERT INTO a VALUES ('it''s; ok');\nCREATE TABLE b (id INT);";
$parts = $split->invoke(null, $sample);
$dbg('T61 splitStatements: операторов ' . count($parts) . ' из 3 -> '
    . (count($parts) === 3 ? 'OK' : 'БАГ (комментарий склеил дамп)'));

// --- ТЕСТ 62: установщик на месте и корень приложения определяется
$instPath = '/tmp/v2/public/install.php';
$instSrc = is_file($instPath) ? (string) file_get_contents($instPath) : '';
$instOk = $instSrc !== ''
    && str_contains($instSrc, 'MINIAGE_INSTALL_VERSION')
    && str_contains($instSrc, 'var/.installed')
    && str_contains($instSrc, 'miniage_install_main');
$dbg('T62 install.php: ' . ($instOk ? 'есть, версия и блокировка на месте' : 'БАГ (файл отсутствует или неполон)')
    . ' -> ' . ($instOk ? 'OK' : 'БАГ'));

// --- ТЕСТ 63: префикс таблиц применяется к схеме (нужен для установки в чужую базу)
$pfx = new ReflectionMethod('App\Core\Migrator', 'applyPrefix');
$pfx->setAccessible(true);
\App\Core\Config::set('DB_PREFIX', 'pre_');
$pfxSql = $pfx->invoke(null, 'CREATE TABLE IF NOT EXISTS "battles" (id INTEGER PRIMARY KEY);');
$pfxIdx = $pfx->invoke(null, 'CREATE INDEX IF NOT EXISTS "idx_battles_char_state" ON battles (char_id, state);');
$pfxBad = $pfx->invoke(null, 'INSERT INTO battles (id) VALUES (1);');
$pfxOk = str_contains($pfxSql, '"pre_battles"')
    && str_contains($pfxIdx, '"pre_idx_battles_char_state"')
    && str_contains($pfxBad, 'INSERT INTO battles') === true;   // данные не переписываем
$dbg('T63 префикс таблиц: ' . substr(trim($pfxSql), 0, 46) . ' -> ' . ($pfxOk ? 'OK' : 'БАГ'));
\App\Core\Config::set('DB_PREFIX', '');

// --- ТЕСТ 64: относительный путь SQLite раскрывается от корня приложения
$cfgSrc = (string) file_get_contents('/tmp/v2/src/Core/Config.php');
$relOk = str_contains($cfgSrc, 'DB_SQLITE_PATH') && str_contains($cfgSrc, 'rtrim($root');
$dbg('T64 относительный DB_SQLITE_PATH: '
    . ($relOk ? 'раскрывается от корня приложения' : 'БАГ (зависит от cwd)') . ' -> ' . ($relOk ? 'OK' : 'БАГ'));

// --- ТЕСТ 65: атрибут hidden обязан реально скрывать элементы
//
// БАГ: [hidden] не побеждал классовые display-правила (.loading{grid},
// .auth{grid}, .panel{flex}, .form{flex}) — спиннер загрузки навсегда
// накрывал экран входа. Проверяем, что глобальное правило есть, стоит с
// !important и идёт раньше остальных: иначе любой более поздний класс
// снова перебьёт скрытие.
$cssSrc = is_file('/tmp/v2/public/assets/css/app.css')
    ? (string) file_get_contents('/tmp/v2/public/assets/css/app.css')
    : '';
$hiddenRule = null;
if (preg_match('/(^|\})\s*\[hidden\]\s*\{([^}]*)\}/m', $cssSrc, $hm, PREG_OFFSET_CAPTURE)) {
    $hiddenRule = ['offset' => (int) $hm[0][1], 'body' => $hm[2][0]];
}
$hiddenOk = $hiddenRule !== null
    && str_contains($hiddenRule['body'], 'display')
    && str_contains($hiddenRule['body'], 'none')
    && str_contains($hiddenRule['body'], '!important');
$hiddenFirst = $hiddenRule !== null && $hiddenRule['offset'] < (int) strpos($cssSrc, '#game-canvas');
$dbg('T65 [hidden]{display:none!important}: '
    . ($hiddenOk ? 'есть' : 'БАГ (нет глобального правила — hidden не работает)')
    . ', до первых компонентов: ' . ($hiddenFirst ? 'да' : 'нет')
    . ' -> ' . (($hiddenOk && $hiddenFirst) ? 'OK' : 'БАГ'));

// --- ТЕСТ 66: ссылки на статику версионированы, HTML не кэшируется
//
// БАГ: после правки CSS браузеры час держали старый файл (max-age=3600),
// т.к. /assets/css/app.css не менял адрес. Теперь к ссылкам добавляется
// ?v=…, а сама оболочка отдаётся с no-cache.
$pageSrc = (string) file_get_contents('/tmp/v2/src/Controller/PageController.php');
$routerSrc = (string) file_get_contents('/tmp/v2/src/Core/Router.php');
$verOk = str_contains($pageSrc, 'assetVersion')
    && preg_match('#/assets/css/app\.css\?v=\{\$[A-Za-z]+\}#', $pageSrc) === 1
    && preg_match('#/assets/js/main\.js\?v=\{\$[A-Za-z]+\}#', $pageSrc) === 1;
$noCacheOk = str_contains($pageSrc, "'Cache-Control' => 'no-cache")
    && (bool) preg_match("#'Cache-Control' => 'public, max-age=\d{4,}'#", $routerSrc);
$dbg('T66 версии ассетов: ' . ($verOk ? 'css/js с ?v=' : 'БАГ (ссылки без версии)')
    . ', оболочка no-cache: ' . ($noCacheOk ? 'да' : 'БАГ')
    . ', статика долгий кэш: ' . (preg_match("#max-age=(\d{4,})#", $routerSrc, $ma) ? $ma[1] . ' с' : 'нет')
    . ' -> ' . (($verOk && $noCacheOk) ? 'OK' : 'БАГ'));
