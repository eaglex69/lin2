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
