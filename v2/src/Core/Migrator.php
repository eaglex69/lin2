<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Применяет schema.{driver}.sql и наполняет демо-данными.
 * Идемпотентно: повторный запуск ничего не ломает.
 */
final class Migrator
{
    public static function migrate(): void
    {
        $driver = Database::driver();
        $file = dirname(__DIR__, 2) . '/db/schema.' . $driver . '.sql';

        if (!is_file($file)) {
            throw new \RuntimeException("Не найден файл схемы: {$file}");
        }

        $sql = (string) file_get_contents($file);
        foreach (self::splitStatements($sql) as $stmt) {
            Database::pdo()->exec($stmt);
        }

        self::upgrade();
        self::seed();
    }

    /**
     * Догоняющие миграции для уже существующих баз.
     *
     * CREATE TABLE IF NOT EXISTS не добавляет новые колонки в старые таблицы,
     * поэтому здесь аккуратно доводим схему до актуальной.
     */
    private static function upgrade(): void
    {
        $bt = Database::table('battles');
        $driver = Database::driver();

        $has = static function (string $table, string $column) use ($driver): bool {
            try {
                if ($driver === 'mysql') {
                    $row = Database::one(
                        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                        [trim($table, '`'), $column]
                    );
                    return (int) ($row['c'] ?? 0) > 0;
                }
                $rows = Database::all('PRAGMA table_info(' . $table . ')');
                foreach ($rows as $r) {
                    if (($r['name'] ?? '') === $column) {
                        return true;
                    }
                }
                return false;
            } catch (\Throwable) {
                return false;
            }
        };

        if (!$has(trim($bt, '"`'), 'won_at')) {
            Database::pdo()->exec(
                $driver === 'mysql'
                    ? "ALTER TABLE {$bt} ADD COLUMN won_at DATETIME NULL AFTER state"
                    : "ALTER TABLE {$bt} ADD COLUMN won_at TEXT NULL"
            );
            // у старых записей лучшая догадка о времени победы — updated_at
            Database::pdo()->exec("UPDATE {$bt} SET won_at = updated_at WHERE state = 'won' AND won_at IS NULL");
            error_log('[miniage] миграция: добавлена колонка battles.won_at');
        }
    }

    /** Разбивает SQL-дамп на отдельные выражения (без процедур и триггеров). */
    private static function splitStatements(string $sql): array
    {
        $out = [];
        $buf = '';
        $len = strlen($sql);
        $inStr = false;
        $strCh = '';

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            $buf .= $c;

            if ($inStr) {
                if ($c === '\\' && $i + 1 < $len) {
                    $buf .= $sql[++$i];
                    continue;
                }
                if ($c === $strCh) {
                    $inStr = false;
                }
                continue;
            }
            if ($c === "'" || $c === '"') {
                $inStr = true;
                $strCh = $c;
                continue;
            }
            if ($c === ';') {
                $stmt = trim($buf);
                $buf = '';
                if ($stmt === '' || $stmt === ';') {
                    continue;
                }
                // пропускаем строки, состоящие только из комментариев
                $clean = preg_replace('/^\s*(--[^\n]*\n|\/\*.*?\*\/|\s)*/s', '', $stmt) ?? '';
                if (trim($clean, " \t\n\r;") === '') {
                    continue;
                }
                $out[] = $stmt;
            }
        }
        $tail = trim($buf);
        if ($tail !== '' && $tail !== ';') {
            $out[] = $tail;
        }
        return $out;
    }

    public static function seed(): void
    {
        if (Config::get('SEED_DEMO', '1') !== '1') {
            return;
        }

        self::seedLevels();
        self::seedItems();
        self::seedMobs();
        self::seedAccounts();
        self::seedChat();
    }

    private static function seedLevels(): void
    {
        if ((int) Database::value('SELECT COUNT(*) FROM ' . Database::table('level_exp')) > 0) {
            return;
        }
        // Кривая опыта в духе legacy inc/lvl.php
        $total = 0;
        $rows = [];
        for ($lvl = 1; $lvl <= 50; $lvl++) {
            if ($lvl > 1) {
                $total += (int) round(30 * ($lvl - 1) ** 2.1 + 40 * ($lvl - 1));
            }
            $rows[$lvl] = $total;
        }
        foreach ($rows as $lvl => $exp) {
            Database::insert('level_exp', ['level' => $lvl, 'exp_total' => $exp]);
        }
    }

    private static function seedItems(): void
    {
        if ((int) Database::value('SELECT COUNT(*) FROM ' . Database::table('items')) > 0) {
            return;
        }
        $items = [
            // code, name, kind, slot, p_atk, m_atk, p_def, m_def, hp, mp, price, lvl_req, stack, icon
            ['wooden_sword',   'Деревянный меч',      'weapon', 'weapon',  8,  0,  0, 0,   0,  0,      1200,  1, 0, 'sword'],
            ['short_blade',    'Короткий клинок',     'weapon', 'weapon', 22,  0,  0, 0,   0,  0,     18000,  3, 0, 'sword'],
            ['knight_blade',   'Клинок рыцаря',       'weapon', 'weapon', 52,  0,  0, 0,   0,  0,    250000,  8, 0, 'sword'],
            ['apprentice_wand', 'Посох ученика',      'weapon', 'weapon',  0, 16,  0, 0,   0, 20,     15000,  3, 0, 'wand'],
            ['elven_bow',      'Эльфийский лук',      'weapon', 'weapon', 30,  0,  0, 0,   0,  0,     62000,  6, 0, 'bow'],
            ['leather_vest',   'Кожаный доспех',      'armor',  'armor',   0,  0, 14, 6,  20,  0,     12000,  1, 0, 'armor'],
            ['chain_mail',     'Кольчуга',            'armor',  'armor',   0,  0, 34, 12, 40,  0,    180000,  7, 0, 'armor'],
            ['iron_helmet',    'Железный шлем',       'armor',  'helmet',  0,  0, 10, 4,  10,  0,     24000,  4, 0, 'helmet'],
            ['sprint_boots',   'Сапоги скорости',     'armor',  'boots',   0,  0,  6, 2,   0,  0,     30000,  4, 0, 'boots'],
            ['power_ring',     'Кольцо силы',         'armor',  'ring',    6,  0,  4, 0,   0,  0,     98000,  5, 0, 'ring'],
            ['hp_potion',      'Зелье лечения',       'potion', 'none',    0,  0,  0, 0, 120,  0,       450,  1, 1, 'potion'],
            ['mp_potion',      'Зелье маны',          'potion', 'none',    0,  0,  0, 0,   0, 90,       520,  1, 1, 'potion'],
            ['animal_bone',    'Кость животного',     'material', 'none',  0,  0,  0, 0,   0,  0,        35,  1, 1, 'bone'],
            ['wolf_pelt',      'Шкура волка',         'material', 'none',  0,  0,  0, 0,   0,  0,        80,  1, 1, 'pelt'],
        ];
        foreach ($items as [$code, $name, $kind, $slot, $pa, $ma, $pd, $md, $hp, $mp, $price, $lvl, $stack, $icon]) {
            Database::insert('items', [
                'code' => $code, 'name' => $name, 'kind' => $kind, 'slot' => $slot,
                'p_atk' => $pa, 'm_atk' => $ma, 'p_def' => $pd, 'm_def' => $md,
                'hp_bonus' => $hp, 'mp_bonus' => $mp, 'price' => $price,
                'level_req' => $lvl, 'stackable' => $stack, 'icon' => $icon,
            ]);
        }
    }

    private static function seedMobs(): void
    {
        if ((int) Database::value('SELECT COUNT(*) FROM ' . Database::table('mobs')) > 0) {
            return;
        }
        // code, name, zone, level, hp, p_atk, p_def, exp, adena, sprite, color, boss, respawn
        $mobs = [
            ['green_slime',   'Зелёный слизень',  'Giran',       1,  40,   6,  2,    18,    60, 'slime',  '6ab04c', 0, 15],
            ['field_rat',     'Полевая крыса',    'Giran',       2,  55,   9,  3,    30,   110, 'rat',    '8d6e63', 0, 18],
            ['red_kobold',    'Красный кобольд',  'Giran',       3,  80,  14,  5,    55,   190, 'orc',    'c0392b', 0, 22],
            ['wolf',          'Степной волк',     'Giran',       4, 110,  20,  7,    80,   260, 'wolf',   '95a5a6', 0, 25],
            ['skeleton',      'Скелет-воин',      'DeathPass',   6, 190,  30, 12,   150,   420, 'skeleton', 'ecf0f1', 0, 30],
            ['ghoul',         'Упырь',            'DeathPass',   8, 280,  42, 16,   230,   610, 'ghoul',  '7f8c8d', 0, 34],
            ['dark_hunter',   'Тёмный охотник',   'DeathPass',  10, 380,  55, 22,   340,   880, 'archer', '4a235a', 0, 40],
            ['mirror_fairy',  'Зеркальная фея',   'ForestMirrors', 12, 460, 62, 20, 430, 1000, 'fairy', '3498db', 0, 38],
            ['forest_golem',  'Лесной голем',     'ForestMirrors', 15, 700, 80, 34, 700, 1500, 'golem', '6d4c41', 0, 50],
            ['drake',         'Дрейк',            'DragonValley', 20, 1100, 110, 48, 1400, 2600, 'dragon', 'e67e22', 1, 60],
        ];
        foreach ($mobs as [$code, $name, $zone, $lvl, $hp, $atk, $def, $exp, $adena, $sprite, $color, $boss, $respawn]) {
            Database::insert('mobs', [
                'code' => $code, 'name' => $name, 'zone' => $zone, 'level' => $lvl,
                'hp' => $hp, 'p_atk' => $atk, 'p_def' => $def, 'exp_reward' => $exp,
                'adena_reward' => $adena, 'sprite' => $sprite, 'color' => $color,
                'is_boss' => $boss, 'respawn_sec' => $respawn,
            ]);
        }
    }

    private static function seedAccounts(): void
    {
        if ((int) Database::value('SELECT COUNT(*) FROM ' . Database::table('accounts')) > 0) {
            return;
        }
        $demoPass = Config::get('SEED_DEMO_PASS', 'demo');
        $demo = self::createAccount('demo', $demoPass, 'demo@miniage.local', 'admin');
        $charId = self::createCharacter($demo, 'Demo', 'human', 'fighter', 'm');
        Database::update('accounts', ['active_char' => $charId], 'id = ?', [$demo]);
        self::giveStarterKit($charId);
        // Демо-персонаж — витрина: даём стартовый капитал, чтобы можно было
        // сразу посмотреть магазин. Обычные новички начинают с 500 адены.
        Database::update('characters', ['adena' => 30000000], 'id = ?', [$charId]);

        // Пароль администратора: из .env либо случайный (печатается в лог один раз).
        // Никогда не оставляем admin/admin в проде.
        $adminPass = Config::get('SEED_ADMIN_PASS');
        $generated = false;
        if ($adminPass === '') {
            $adminPass = bin2hex(random_bytes(9));
            $generated = true;
        }
        $admin = self::createAccount('admin', $adminPass, 'admin@miniage.local', 'admin');
        $adminChar = self::createCharacter($admin, 'Admin', 'elf', 'mage', 'm');
        Database::update('accounts', ['active_char' => $adminChar], 'id = ?', [$admin]);
        self::giveStarterKit($adminChar);

        if ($generated && Config::get('APP_ENV') !== 'production') {
            error_log('[miniage] admin создан со случайным паролем: ' . $adminPass);
        }
    }

    public static function createAccount(string $nick, string $pass, string $email = '', string $role = 'player'): int
    {
        return Database::insert('accounts', [
            'nick' => $nick,
            'pass_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'email' => $email,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function createCharacter(int $accountId, string $name, string $race, string $class, string $gender): int
    {
        $base = match ($class) {
            'mage'   => ['hp' => 80,  'mp' => 110, 'p_atk' => 10, 'm_atk' => 26, 'p_def' => 8,  'm_def' => 14],
            'archer' => ['hp' => 95,  'mp' => 70,  'p_atk' => 24, 'm_atk' => 8,  'p_def' => 10, 'm_def' => 9],
            'healer' => ['hp' => 85,  'mp' => 120, 'p_atk' => 9,  'm_atk' => 22, 'p_def' => 9,  'm_def' => 16],
            default  => ['hp' => 120, 'mp' => 60,  'p_atk' => 26, 'm_atk' => 6,  'p_def' => 14, 'm_def' => 8],
        };

        return Database::insert('characters', [
            'account_id' => $accountId,
            'name' => $name,
            'race' => $race,
            'class' => $class,
            'gender' => $gender,
            'hp' => $base['hp'], 'hp_max' => $base['hp'],
            'mp' => $base['mp'], 'mp_max' => $base['mp'],
            'p_atk' => $base['p_atk'], 'm_atk' => $base['m_atk'],
            'p_def' => $base['p_def'], 'm_def' => $base['m_def'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function giveStarterKit(int $charId): void
    {
        $sword = Database::one('SELECT id FROM ' . Database::table('items') . ' WHERE code = ?', ['wooden_sword']);
        $vest = Database::one('SELECT id FROM ' . Database::table('items') . ' WHERE code = ?', ['leather_vest']);
        $potions = Database::one('SELECT id FROM ' . Database::table('items') . ' WHERE code = ?', ['hp_potion']);

        if ($sword) {
            Database::insert('inventory', ['char_id' => $charId, 'item_id' => (int) $sword['id'], 'qty' => 1, 'equipped' => 1]);
        }
        if ($vest) {
            Database::insert('inventory', ['char_id' => $charId, 'item_id' => (int) $vest['id'], 'qty' => 1, 'equipped' => 1]);
        }
        if ($potions) {
            Database::insert('inventory', ['char_id' => $charId, 'item_id' => (int) $potions['id'], 'qty' => 5]);
        }
    }

    private static function seedChat(): void
    {
        if ((int) Database::value('SELECT COUNT(*) FROM ' . Database::table('chat_messages')) > 0) {
            return;
        }
        foreach ([
            ['system', 'Система', 'Добро пожаловать в Miniage v2 — PHP 8.1 + Canvas!'],
            ['global', 'Admin', 'Сервер переписан с нуля. Кликайте по монстрам на карте.'],
        ] as [$ch, $author, $text]) {
            Database::insert('chat_messages', [
                'channel' => $ch, 'char_id' => null, 'author' => $author, 'text' => $text,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
