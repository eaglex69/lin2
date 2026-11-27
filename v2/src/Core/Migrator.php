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
        self::gc();
    }

    /**
     * Сборка мусора: сессии, старые бои, попытки входа.
     *
     * Без этого таблицы росли бесконечно: каждая анонимная загрузка страницы
     * создавала строку в sessions, каждый бой — строку в battles.
     * Вызывается с вероятностью ~1/50, чтобы не бить по БД каждый запрос.
     */
    public static function gc(bool $force = false): void
    {
        // БАГ: static-флаг «уже запускал» жил ровно одну обработку запроса, а
        // под PHP-FPM каждый запрос — новый процесс. Флаг всегда был false,
        // но следующий за ним random_int(1, 50) успевал «съесть» шанс: для
        // данного запроса gc не выполнялся с вероятностью 49/50, а следующий
        // запрос всё равно стартовал с чистым флагом. Итог — gc почти не
        // запускался, и sessions/battles_login_attempts росли бесконечно.
        // Правильная схема — просто случайная выборка на запрос: в среднем
        // одно срабатывание на 50 запросов, независимо от модели процессов.
        if (!$force && random_int(1, 50) !== 1) {
            return;
        }

        try {
            $now = time();

            // сессии: старше SESSION_TTL
            $ttl = Config::int('SESSION_TTL', 86400);
            $cutoff = date('Y-m-d H:i:s', $now - $ttl);
            Database::exec(
                'DELETE FROM ' . Database::table('sessions') . ' WHERE last_used < ?',
                [$cutoff]
            );

            // бои: завершённые более суток назад
            Database::exec(
                'DELETE FROM ' . Database::table('battles') . ' WHERE state <> ? AND updated_at < ?',
                ['active', $cutoff]
            );

            // попытки входа: старше суток и без активной блокировки
            Database::exec(
                'DELETE FROM ' . Database::table('login_attempts') . ' WHERE first_ts < ? AND (blocked_until IS NULL OR blocked_until < ?)',
                [$now - 86400, date('Y-m-d H:i:s', $now)]
            );
        } catch (\Throwable $e) {
            // уборка не должна ронять запрос
            error_log('[miniage] gc: ' . $e->getMessage());
        }
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

        self::syncEquipStats();

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

    /**
     * Догоняющая миграция: персонажи, созданные до исправления бага 64, носили
     * экипировку, бонусы которой не входили в hp_max/p_atk/p_def (стартовый
     * набор выдавался «мимо» пересчёта). Приводим их характеристики к
     * экипировке — иначе фикс действовал бы только на новых персонажей.
     *
     * Выполняется один раз на базу: маркер в служебной таблице, чтобы не
     * делать SELECT по всему инвентарю на каждый запрос.
     */
    private static function syncEquipStats(): void
    {
        try {
            $marker = 'sync_equip_stats_v1';
            $mt = Database::table('meta');
            $done = Database::value('SELECT `value` FROM ' . $mt . ' WHERE `key` = ?', [$marker]);
            if ($done === '1') {
                return;
            }
        } catch (\Throwable) {
            // таблицы meta нет (старая база) — не мешаем миграции
            return;
        }
        try {
            $shop = new \App\Service\ShopService();
            $rows = Database::all(
                'SELECT DISTINCT i.char_id FROM ' . Database::table('inventory') . ' i WHERE i.equipped = 1'
            );
            foreach ($rows as $r) {
                $shop->recalcStats((int) $r['char_id']);
            }
            Database::exec(
                'INSERT INTO ' . Database::table('meta') . ' (`key`, `value`) VALUES (?, ?)',
                [$marker, '1']
            );
        } catch (\Throwable $e) {
            error_log('[miniage] syncEquipStats: ' . $e->getMessage());
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
        // БАГ: раньше SEED_DEMO=0 (рекомендуется для прода!) отключал ВСЮ
        // инициализацию, включая уровни, предметы и мобов — игра оставалась
        // без контента. Демо-флаг должен касаться только демо-аккаунтов.
        self::seedLevels();
        self::seedItems();
        self::seedMobs();
        self::seedNews();

        if (Config::get('SEED_DEMO', '1') === '1') {
            self::seedAccounts();
        }
        self::seedChat();
    }

    private static function seedNews(): void
    {
        if ((int) Database::value('SELECT COUNT(*) FROM ' . Database::table('news')) > 0) {
            return;
        }
        foreach ([
            ['Запуск Miniage v2', 'Сервер переписан на PHP 8.1: canvas-карта, AJAX, MySQL. Добро пожаловать!', 'Admin'],
            ['Как играть', 'Кликните по монстру на карте — начнётся бой. Победа даёт опыт и адену.', 'Admin'],
        ] as [$title, $body, $author]) {
            Database::insert('news', [
                'title' => $title,
                'body' => $body,
                'author' => $author,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
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
        // БАГ: демо-аккаунт создавался с ролью admin, а его пароль по умолчанию
        // — «demo» (его же подставляет форма входа). При SEED_DEMO=1 (значение
        // по умолчанию!) на любом развёртывании любой желающий входил
        // администратором. Демо — витрина, прав ей не нужно: роль player.
        $demo = self::createAccount('demo', $demoPass, 'demo@miniage.local', 'player');
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
            // БАГ: ip не заполнялся вовсе, поэтому антиспам регистраций по IP
            // и журнал входа были пустыми (AuthService::login только чистил
            // поле на 'ip' => '').
            'ip' => \App\Core\Request::currentIp(),
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
            // БАГ: x/y по умолчанию 0 — персонаж появлялся в левом верхнем
            // углу карты, а мобы живут в 90..810 x 110..540. В пустом углу
            // некуда идти и некого бить. Ставим стартовую точку рядом с
            // центром карты (в тех же пределах, что и мобы).
            'x' => 480,
            'y' => 420,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Стартовый набор. Делегирует ShopService::starterKit(), который после
     * выдачи пересчитывает характеристики.
     *
     * БАГ: раньше предметы вставлялись здесь с equipped = 1, а статы оставались
     * базовыми — надетый меч (+8 атаки) и доспех (+14 защиты, +20 HP) не
     * учитывались, пока игрок вручную не снимал/надевал их. Проверено вживую:
     * p_atk=26 вместо 34, hp_max=120 вместо 140.
     */
    public static function giveStarterKit(int $charId): void
    {
        (new \App\Service\ShopService())->starterKit($charId);
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
