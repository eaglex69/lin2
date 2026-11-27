<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;

/**
 * Игровая логика: охота, бой, опыт, уровни, зоны, онлайн.
 *
 * Бой пошаговый (как legacy fight.php), но ходы сохраняются в таблице
 * battles, поэтому AJAX-клиент и Canvas могут переподключиться к бою.
 */
final class GameService
{
    public const ZONES = [
        'Giran' => ['label' => 'Окрестности Гирана', 'min_level' => 1, 'color' => '#2e4a2e'],
        'DeathPass' => ['label' => 'Перевал Смерти', 'min_level' => 5, 'color' => '#3a2f2a'],
        'ForestMirrors' => ['label' => 'Лес Зеркал', 'min_level' => 11, 'color' => '#22303a'],
        'DragonValley' => ['label' => 'Долина Драконов', 'min_level' => 18, 'color' => '#3a1f24'],
    ];

    /** @return array<int,array<string,mixed>> мобы зоны с координатами на карте */
    public function mobsInZone(string $zone, int $charId): array
    {
        $t = Database::table('mobs');
        $rows = Database::all("SELECT * FROM {$t} WHERE zone = ? ORDER BY level", [$zone]);
        $now = time();
        $out = [];
        foreach ($rows as $i => $m) {
            $mobId = (int) $m['id'];
            // Детерминированные координаты: стабильны между запросами
            mt_srand($mobId * 7919);
            $x = 90 + mt_rand(0, 720);
            $y = 110 + mt_rand(0, 430);
            mt_srand();
            $alive = $this->isMobAlive($charId, $mobId);
            $out[] = [
                'id' => $mobId,
                'code' => $m['code'],
                'name' => $m['name'],
                'level' => (int) $m['level'],
                'hp' => (int) $m['hp'],
                'exp' => (int) $m['exp_reward'],
                'adena' => (int) $m['adena_reward'],
                'sprite' => $m['sprite'],
                'color' => $m['color'],
                'boss' => (bool) $m['is_boss'],
                'x' => $x,
                'y' => $y,
                'alive' => $alive,
                'respawn_in' => $alive ? 0 : max(0, (int) $m['respawn_sec'] - ($now % max(1, (int) $m['respawn_sec']))),
            ];
        }
        return $out;
    }

    private function isMobAlive(int $charId, int $mobId): bool
    {
        $t = Database::table('battles');
        $row = Database::one(
            "SELECT state, updated_at FROM {$t} WHERE char_id = ? AND mob_id = ? AND state = 'won' ORDER BY id DESC LIMIT 1",
            [$charId, $mobId]
        );
        if ($row === null) {
            return true;
        }
        $mob = Database::one('SELECT respawn_sec FROM ' . Database::table('mobs') . ' WHERE id = ?', [$mobId]);
        $respawn = (int) ($mob['respawn_sec'] ?? 20);
        return (time() - strtotime((string) $row['updated_at'])) >= $respawn;
    }

    /**
     * Начать бой. Возвращает состояние боя для клиента.
     */
    public function startBattle(int $charId, int $mobId): array
    {
        $char = $this->requireChar($charId);
        $mob = Database::one('SELECT * FROM ' . Database::table('mobs') . ' WHERE id = ?', [$mobId]);
        if ($mob === null) {
            throw new \InvalidArgumentException('Монстр не найден.');
        }
        if ($char['hp'] <= 0) {
            throw new \InvalidArgumentException('Персонаж мёртв — нужна реанимация.');
        }
        if (!$this->isMobAlive($charId, $mobId)) {
            throw new \InvalidArgumentException('Монстр ещё не возродился.');
        }

        $bt = Database::table('battles');
        // незавершённый бой на того же моба переиспользуем
        $active = Database::one(
            "SELECT * FROM {$bt} WHERE char_id = ? AND mob_id = ? AND state = 'active' ORDER BY id DESC LIMIT 1",
            [$charId, $mobId]
        );

        if ($active !== null) {
            return $this->battleState($active);
        }

        $id = Database::insert('battles', [
            'char_id' => $charId,
            'mob_id' => $mobId,
            'mob_hp' => (int) $mob['hp'],
            'turn' => 1,
            'log_json' => json_encode([], JSON_UNESCAPED_UNICODE),
            'state' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->battleState(Database::one("SELECT * FROM {$bt} WHERE id = ?", [$id]));
    }

    /**
     * Ход в бою: игрок бьёт, монстр отвечает.
     */
    public function attack(int $charId, int $battleId): array
    {
        $bt = Database::table('battles');
        $battle = Database::one("SELECT * FROM {$bt} WHERE id = ? AND char_id = ?", [$battleId, $charId]);
        if ($battle === null) {
            throw new \InvalidArgumentException('Бой не найден.');
        }
        if ($battle['state'] !== 'active') {
            throw new \InvalidArgumentException('Бой уже завершён.');
        }

        return Database::transaction(function () use ($battle, $charId) {
            $char = $this->requireChar($charId);
            $mob = Database::one('SELECT * FROM ' . Database::table('mobs') . ' WHERE id = ?', [(int) $battle['mob_id']]);
            if ($mob === null) {
                throw new \InvalidArgumentException('Монстр исчез.');
            }

            $log = json_decode((string) $battle['log_json'], true);
            $log = is_array($log) ? $log : [];

            $mobHp = (int) $battle['mob_hp'];
            $turn = (int) $battle['turn'];

            // --- удар игрока
            $atk = max((int) $char['p_atk'], (int) $char['m_atk']);
            $critChance = (int) $char['crit'];
            $crit = random_int(1, 100) <= $critChance;
            $dmg = max(1, (int) round($atk * (0.9 + mt_rand(0, 40) / 100) - (int) $mob['p_def'] * 0.4));
            if ($crit) {
                $dmg = (int) round($dmg * 1.7);
            }
            $mobHp = max(0, $mobHp - $dmg);
            $log[] = ['t' => 'player', 'turn' => $turn, 'dmg' => $dmg, 'crit' => $crit, 'target_hp' => $mobHp];

            $state = 'active';
            $rewards = null;

            if ($mobHp <= 0) {
                $state = 'won';
                $rewards = $this->applyRewards($char, $mob);
                $log[] = ['t' => 'system', 'turn' => $turn, 'text' => "Победа над {$mob['name']}! +{$rewards['exp']} EXP, +{$rewards['adena']} адены"];
            } else {
                // --- ответ монстра
                $mAtk = (int) $mob['p_atk'];
                $mDmg = max(1, (int) round($mAtk * (0.85 + mt_rand(0, 30) / 100) - (int) $char['p_def'] * 0.45));
                $charHp = max(0, (int) $char['hp'] - $mDmg);
                $log[] = ['t' => 'mob', 'turn' => $turn, 'dmg' => $mDmg, 'target_hp' => $charHp];
                Database::update('characters', ['hp' => $charHp], 'id = ?', [(int) $char['id']]);

                if ($charHp <= 0) {
                    $state = 'lost';
                    Database::update('characters', ['losses' => (int) $char['losses'] + 1], 'id = ?', [(int) $char['id']]);
                    $log[] = ['t' => 'system', 'turn' => $turn, 'text' => 'Вы погибли. Нужна реанимация в городе.'];
                }
            }

            Database::update('battles', [
                'mob_hp' => $mobHp,
                'turn' => $turn + 1,
                'log_json' => json_encode($log, JSON_UNESCAPED_UNICODE),
                'state' => $state,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $battle['id']]);

            $this->touchOnline($charId);

            $stateArr = $this->battleState(Database::one(
                'SELECT * FROM ' . Database::table('battles') . ' WHERE id = ?',
                [(int) $battle['id']]
            ));
            if ($rewards !== null) {
                $stateArr['rewards'] = $rewards;
            }
            return $stateArr;
        });
    }

    public function flee(int $charId, int $battleId): array
    {
        $bt = Database::table('battles');
        $battle = Database::one("SELECT * FROM {$bt} WHERE id = ? AND char_id = ?", [$battleId, $charId]);
        if ($battle === null) {
            throw new \InvalidArgumentException('Бой не найден.');
        }
        $log = json_decode((string) $battle['log_json'], true) ?: [];
        $log[] = ['t' => 'system', 'turn' => (int) $battle['turn'], 'text' => 'Вы отступили из боя.'];
        Database::update('battles', [
            'state' => 'fled',
            'log_json' => json_encode($log, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $battle['id']]);
        return $this->battleState(Database::one("SELECT * FROM {$bt} WHERE id = ?", [(int) $battle['id']]));
    }

    /** @return array{exp:int,adena:int,levelup:bool,level:int} */
    private function applyRewards(array $char, array $mob): array
    {
        $exp = (int) $char['exp'] + (int) $mob['exp_reward'];
        $adena = (int) $char['adena'] + (int) $mob['adena_reward'];
        $level = (int) $char['level'];
        $levelup = false;

        while ($level < ExpTable::maxLevel() && $exp >= ExpTable::totalFor($level + 1)) {
            $level++;
            $levelup = true;
        }

        $update = [
            'exp' => $exp,
            'adena' => $adena,
            'level' => $level,
            'wins' => (int) $char['wins'] + 1,
        ];

        if ($levelup) {
            // рост характеристик: как legacy checkLevel()
            $hpMax = (int) $char['hp_max'] + 12 + $level * 2;
            $mpMax = (int) $char['mp_max'] + 6 + $level;
            $update['hp_max'] = $hpMax;
            $update['hp'] = $hpMax;
            $update['mp_max'] = $mpMax;
            $update['mp'] = $mpMax;
            $update['p_atk'] = (int) $char['p_atk'] + 2;
            $update['m_atk'] = (int) $char['m_atk'] + 2;
            $update['p_def'] = (int) $char['p_def'] + 1;
            $update['m_def'] = (int) $char['m_def'] + 1;
        }

        Database::update('characters', $update, 'id = ?', [(int) $char['id']]);

        return ['exp' => (int) $mob['exp_reward'], 'adena' => (int) $mob['adena_reward'], 'levelup' => $levelup, 'level' => $level];
    }

    public function battleState(array $battle): array
    {
        $mob = Database::one('SELECT * FROM ' . Database::table('mobs') . ' WHERE id = ?', [(int) $battle['mob_id']]);
        $log = json_decode((string) $battle['log_json'], true) ?: [];
        return [
            'id' => (int) $battle['id'],
            'state' => (string) $battle['state'],
            'turn' => (int) $battle['turn'],
            'mob' => $mob === null ? null : [
                'id' => (int) $mob['id'],
                'name' => $mob['name'],
                'level' => (int) $mob['level'],
                'hp' => (int) $mob['hp'],
                'sprite' => $mob['sprite'],
                'color' => $mob['color'],
                'boss' => (bool) $mob['is_boss'],
            ],
            'mob_hp' => (int) $battle['mob_hp'],
            'log' => $log,
        ];
    }

    public function activeBattle(int $charId): ?array
    {
        $bt = Database::table('battles');
        $row = Database::one("SELECT * FROM {$bt} WHERE char_id = ? AND state = 'active' ORDER BY id DESC LIMIT 1", [$charId]);
        return $row === null ? null : $this->battleState($row);
    }

    public function heal(int $charId): array
    {
        $char = $this->requireChar($charId);
        $cost = 0;
        if ((int) $char['hp'] >= (int) $char['hp_max'] && (int) $char['mp'] >= (int) $char['mp_max']) {
            throw new \InvalidArgumentException('Персонаж уже полностью здоров.');
        }
        if ((int) $char['hp'] <= 0) {
            $cost = 500;
            if ((int) $char['adena'] < $cost) {
                throw new \InvalidArgumentException('Не хватает адены на реанимацию (500).');
            }
        }
        Database::update('characters', [
            'hp' => (int) $char['hp_max'],
            'mp' => (int) $char['mp_max'],
            'adena' => (int) $char['adena'] - $cost,
        ], 'id = ?', [$charId]);
        return ['cost' => $cost, 'char' => $this->requireChar($charId)];
    }

    public function move(int $charId, int $x, int $y, string $city): array
    {
        $x = max(0, min(900, $x));
        $y = max(0, min(560, $y));
        $city = isset(self::ZONES[$city]) ? $city : 'Giran';
        Database::update('characters', ['x' => $x, 'y' => $y, 'city' => $city, 'last_seen' => date('Y-m-d H:i:s')], 'id = ?', [$charId]);
        $this->touchOnline($charId);
        return ['x' => $x, 'y' => $y, 'city' => $city];
    }

    public function touchOnline(int $charId): void
    {
        $t = Database::table('online');
        $now = time();
        if (Database::one("SELECT char_id FROM {$t} WHERE char_id = ?", [$charId]) === null) {
            Database::insert('online', ['char_id' => $charId, 'last_ts' => $now]);
        } else {
            Database::update('online', ['last_ts' => $now], 'char_id = ?', [$charId]);
        }
        // чистим тех, кто молчит больше 2 минут
        Database::exec("DELETE FROM {$t} WHERE last_ts < ?", [$now - 120]);
    }

    public function onlineCount(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM ' . Database::table('online') . ' WHERE last_ts > ?', [time() - 120]);
    }

    public function requireChar(int $charId): array
    {
        $t = Database::table('characters');
        $char = Database::one("SELECT * FROM {$t} WHERE id = ?", [$charId]);
        if ($char === null) {
            throw new \InvalidArgumentException('Персонаж не найден.');
        }
        foreach (['id', 'level', 'exp', 'hp', 'hp_max', 'mp', 'mp_max', 'p_atk', 'm_atk', 'p_def', 'm_def', 'crit', 'adena', 'wins', 'losses', 'x', 'y'] as $k) {
            $char[$k] = (int) $char[$k];
        }
        return $char;
    }

    /** Зоны с рекомендуемым уровнем и числом мобов. */
    public function zones(int $charLevel): array
    {
        $out = [];
        foreach (self::ZONES as $key => $meta) {
            $count = (int) Database::value('SELECT COUNT(*) FROM ' . Database::table('mobs') . ' WHERE zone = ?', [$key]);
            $out[] = [
                'key' => $key,
                'label' => $meta['label'],
                'color' => $meta['color'],
                'min_level' => $meta['min_level'],
                'mobs' => $count,
                'locked' => $charLevel < $meta['min_level'],
            ];
        }
        return $out;
    }
}
