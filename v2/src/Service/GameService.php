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
    /** Цена лечения: адены за единицу восстановленного HP/MP. */
    private const HEAL_COST_PER_POINT = 0.5;

    /** Цена реанимации мёртвого персонажа. */
    private const RESURRECT_COST = 500;

    /**
     * Через сколько секунд бездействия бой считается брошенным.
     *
     * БАГ: лимит в 3 активных боя не освобождался. Клиент при входе
     * восстанавливает только ОДИН последний бой (battle/current → ORDER BY id
     * DESC LIMIT 1), поэтому два брошенных висели вечно, и игрок больше не мог
     * начать ни одного боя: «Слишком много активных боёв». Проверено вживую.
     * Теперь такие бои закрываются как «fled» — без награды и без штрафа.
     */
    private const BATTLE_IDLE_SEC = 1800;

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
        $out = [];
        foreach ($rows as $i => $m) {
            $mobId = (int) $m['id'];
            // Детерминированные координаты: стабильны между запросами
            mt_srand($mobId * 7919);
            $x = 90 + mt_rand(0, 720);
            $y = 110 + mt_rand(0, 430);
            mt_srand();
            $alive = $this->isMobAlive($charId, $mobId);
            // моб занят чужим активным боем: показать как «мёртв», чтобы клиент
            // не предлагал напасть и не получал отказ уже после клика
            $busy = $alive && $this->isMobBusy($mobId, $charId);
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
                'alive' => $alive && !$busy,
                'busy' => $busy,
                // БАГ: здесь был фиктивный расчёт по остатку от деления time()
                // на respawn_sec — таймер не совпадал с фактическим респавном
                // и всегда показывал произвольное число. Берём настоящее время.
                'respawn_in' => ($alive && !$busy) ? 0 : $this->respawnIn($mobId),
            ];
        }
        return $out;
    }

    private function isMobAlive(int $charId, int $mobId): bool
    {
        $t = Database::table('battles');
        // ВАЖНО: updated_at меняется при любом изменении строки, в том числе
        // при отступлении (flee) после победы. Тогда респавн обнулялся и можно
        // было убивать одного моба бесконечно. Храним время победы отдельно.
        //
        // БАГ: раньше респавн считался по battles.char_id = ? — то есть у
        // КАЖДОГО игрока был свой таймер. Два игрока могли по очереди бить
        // одного и того же монстра без ожидания: для второго он всегда жив.
        // Теперь решение принимается по последней победе над этим монстром
        // любого игрока («мировой» респавн, как в обычной MMO).
        $row = Database::one(
            "SELECT won_at, updated_at FROM {$t}
             WHERE mob_id = ? AND state = 'won' AND won_at IS NOT NULL
             ORDER BY won_at DESC, id DESC LIMIT 1",
            [$mobId]
        );
        if ($row === null) {
            // запасной путь для старых записей без won_at
            $row = Database::one(
                "SELECT updated_at AS won_at, updated_at FROM {$t}
                 WHERE mob_id = ? AND state = 'won'
                 ORDER BY id DESC LIMIT 1",
                [$mobId]
            );
        }
        if ($row === null) {
            return true;
        }
        $wonAt = (string) ($row['won_at'] ?? '') !== '' ? (string) $row['won_at'] : (string) $row['updated_at'];
        $mob = Database::one('SELECT respawn_sec FROM ' . Database::table('mobs') . ' WHERE id = ?', [$mobId]);
        $respawn = (int) ($mob['respawn_sec'] ?? 20);
        return (time() - strtotime($wonAt)) >= $respawn;
    }

    /**
     * Закрывает бои, в которых игрок не делал хода дольше BATTLE_IDLE_SEC.
     * Награды нет, поражение не засчитывается.
     */
    public function closeStaleBattles(int $charId): int
    {
        $bt = Database::table('battles');
        $cutoff = date('Y-m-d H:i:s', time() - self::BATTLE_IDLE_SEC);
        $stale = Database::all(
            "SELECT id FROM {$bt} WHERE char_id = ? AND state = 'active' AND updated_at < ?",
            [$charId, $cutoff]
        );
        foreach ($stale as $row) {
            $log = json_decode((string) Database::value(
                "SELECT log_json FROM {$bt} WHERE id = ?",
                [(int) $row['id']]
            ), true) ?: [];
            $log[] = ['t' => 'system', 'turn' => 0, 'text' => 'Бой прерван: вы слишком долго не сражались.'];
            Database::update('battles', [
                'state' => 'fled',
                'log_json' => json_encode($log, JSON_UNESCAPED_UNICODE),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [(int) $row['id']]);
        }
        return count($stale);
    }

    /**
     * Блокировка строки при SELECT внутри транзакции (MySQL).
     *
     * В SQLite нет SELECT ... FOR UPDATE: файловая блокировка там и так
     * сериализует писателей, поэтому возвращаем пустую строку.
     */
    private function forUpdate(): string
    {
        return Database::driver() === 'mysql' ? ' FOR UPDATE' : '';
    }

    /**
     * Занят ли моб чужим активным боем.
     *
     * БАГ: активный бой другого игрока не учитывался вовсе. Двое начинали
     * бой с ОДНИМ мобом и оба получали победу и награду — проверено: у A
     * wins=1 и у B wins=1 за одного слизня. Свои бои не считаются: игрок
     * должен иметь возможность вернуться в незавершённый бой.
     */
    private function isMobBusy(int $mobId, int $charId): bool
    {
        // учитываем только «свежие» бои: брошенный (нет хода дольше
        // BATTLE_IDLE_SEC) не должен держать моба вечно, даже если чужой
        // клиент ещё не вызывал closeStaleBattles
        $busy = Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('battles')
            . " WHERE mob_id = ? AND state = 'active' AND char_id <> ?"
            . ' AND updated_at >= ?',
            [$mobId, $charId, date('Y-m-d H:i:s', time() - self::BATTLE_IDLE_SEC)]
        );
        return (int) $busy > 0;
    }

    /** Секунды до возрождения монстра (0 — уже жив). */
    public function respawnIn(int $mobId): int
    {
        $t = Database::table('battles');
        // идёт чужой свежий бой — моб занят, таймер возрождения ещё не пошёл
        $busy = Database::value(
            "SELECT COUNT(*) FROM {$t} WHERE mob_id = ? AND state = 'active' AND updated_at >= ?",
            [$mobId, date('Y-m-d H:i:s', time() - self::BATTLE_IDLE_SEC)]
        );
        if ((int) $busy > 0) {
            $mob = Database::one('SELECT respawn_sec FROM ' . Database::table('mobs') . ' WHERE id = ?', [$mobId]);
            return max(1, (int) ($mob['respawn_sec'] ?? 20));
        }
        $row = Database::one(
            "SELECT won_at FROM {$t} WHERE mob_id = ? AND state = 'won' AND won_at IS NOT NULL
             ORDER BY won_at DESC, id DESC LIMIT 1",
            [$mobId]
        );
        if ($row === null || $row['won_at'] === null) {
            return 0;
        }
        $mob = Database::one('SELECT respawn_sec FROM ' . Database::table('mobs') . ' WHERE id = ?', [$mobId]);
        $respawn = max(1, (int) ($mob['respawn_sec'] ?? 20));
        $left = $respawn - (time() - strtotime((string) $row['won_at']));
        return max(0, $left);
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

        // SECURITY: проверяем доступ к зоне. Клиент ограничивает зоны только
        // визуально — без этой проверки с 1 уровня можно бить дракона 20 ур.
        $zone = (string) $mob['zone'];
        $minLevel = self::ZONES[$zone]['min_level'] ?? 1;
        if ((int) $char['level'] < $minLevel) {
            throw new \InvalidArgumentException("Зона откроется на {$minLevel} уровне.");
        }

        $bt = Database::table('battles');
        // сначала закрываем брошенные бои: иначе лимит съедают бои, к которым
        // клиент уже не может вернуться (восстанавливается только последний)
        $this->closeStaleBattles($charId);

        // не даём держать десятки параллельных боёв (спам запросами)
        $activeCnt = (int) Database::value(
            "SELECT COUNT(*) FROM {$bt} WHERE char_id = ? AND state = 'active'",
            [$charId]
        );
        if ($activeCnt >= 3) {
            throw new \InvalidArgumentException('Слишком много активных боёв. Завершите текущие.');
        }
        // незавершённый бой на того же моба переиспользуем
        $active = Database::one(
            "SELECT * FROM {$bt} WHERE char_id = ? AND mob_id = ? AND state = 'active' ORDER BY id DESC LIMIT 1",
            [$charId, $mobId]
        );

        if ($active !== null) {
            return $this->battleState($active);
        }

        // Захват моба: повторная проверка «моб свободен» и вставка боя в
        // ОДНОЙ транзакции. Без этого между isMobAlive() выше и INSERT на
        // MySQL успевал вклиниться второй игрок — один моб доставался двоим,
        // и оба получали победу с наградой.
        $id = Database::transaction(function () use ($charId, $mobId, $mob) {
            if (!$this->isMobAlive($charId, $mobId)) {
                throw new \InvalidArgumentException('Монстр ещё не возродился.');
            }
            if ($this->isMobBusy($mobId, $charId)) {
                throw new \InvalidArgumentException('Монстра уже атакует другой игрок.');
            }
            return Database::insert('battles', [
                'char_id' => $charId,
                'mob_id' => $mobId,
                'mob_hp' => (int) $mob['hp'],
                'turn' => 1,
                'log_json' => json_encode([], JSON_UNESCAPED_UNICODE),
                'state' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        });

        return $this->battleState(Database::one("SELECT * FROM {$bt} WHERE id = ?", [$id]));
    }

    /**
     * Ход в бою: игрок бьёт, монстр отвечает.
     */
    public function attack(int $charId, int $battleId): array
    {
        return Database::transaction(function () use ($battleId, $charId) {
            $bt2 = Database::table('battles');
            // БАГ: состояние боя читалось ДО транзакции. php-wasm однопоточный,
            // поэтому это не проявлялось, но на MySQL два параллельных удара
            // получали одинаковые mob_hp/turn/log и перезаписывали друг друга:
            // лишние ходы «терялись», а победа могла начисляться дважды.
            // Перечитываем строку внутри транзакции (при SELECT ... FOR UPDATE).
            $battle = Database::one(
                "SELECT * FROM {$bt2} WHERE id = ? AND char_id = ?" . $this->forUpdate(),
                [$battleId, $charId]
            );
            if ($battle === null) {
                throw new \InvalidArgumentException('Бой не найден.');
            }
            if ($battle['state'] !== 'active') {
                throw new \InvalidArgumentException('Бой уже завершён.');
            }

            // БАГ (гонка на MySQL): два одновременных удара читали одинаковые
            // mob_hp/turn/log и второй перезаписывал первого — ходы терялись.
            // Захватываем ход условным UPDATE: сработает только у одного.
            $claimed = Database::exec(
                "UPDATE {$bt2} SET turn = turn + 1
                 WHERE id = ? AND state = 'active' AND turn = ?",
                [(int) $battle['id'], (int) $battle['turn']]
            );
            if ($claimed !== 1) {
                throw new \InvalidArgumentException('Ход уже сделан. Обновите бой.');
            }

            $char = $this->requireChar($charId);
            // Мёртвый персонаж не может драться. Без этой проверки можно было
            // погибнуть в одном бою и продолжать бить в другом активном бою:
            // удары проходили, опыт и адена капали, хотя hp = 0.
            if ((int) $char['hp'] <= 0) {
                throw new \InvalidArgumentException('Персонаж мёртв — нужна реанимация.');
            }
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
            $dRaw = $atk * (0.9 + mt_rand(0, 40) / 100);
            $dReduction = min(0.55, (int) $mob['p_def'] * 0.012);
            $dmg = max(1, (int) round($dRaw * (1 - $dReduction)));
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
                // БАЛАНС: раньше защита вычиталась целиком, и при def 22 любой
                // моб с atk <= 9 наносил ровно 1 урона — первые два уровня были
                // безрисковыми, зелья не нужны. Теперь защита срезает процент:
                // не более 60 % урона, поэтому слабый моб всё равно кусается.
                $mRaw = $mAtk * (0.85 + mt_rand(0, 30) / 100);
                $mReduction = min(0.6, (int) $char['p_def'] * 0.012);
                $mDmg = max(1, (int) round($mRaw * (1 - $mReduction)));
                $charHp = max(0, (int) $char['hp'] - $mDmg);
                $log[] = ['t' => 'mob', 'turn' => $turn, 'dmg' => $mDmg, 'target_hp' => $charHp];
                Database::update('characters', ['hp' => $charHp], 'id = ?', [(int) $char['id']]);

                if ($charHp <= 0) {
                    $state = 'lost';
                    Database::update('characters', ['losses' => (int) $char['losses'] + 1], 'id = ?', [(int) $char['id']]);
                    $log[] = ['t' => 'system', 'turn' => $turn, 'text' => 'Вы погибли. Нужна реанимация в городе.'];
                    // бой обрываем, чтобы клиент не продолжал присылать удары
                    $log[] = ['t' => 'system', 'turn' => $turn, 'text' => 'Бой завершён.'];
                }
            }

            $battleUpdate = [
                'mob_hp' => $mobHp,
                // turn уже увеличен условным UPDATE выше — здесь не трогаем

                'log_json' => json_encode($log, JSON_UNESCAPED_UNICODE),
                'state' => $state,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($state === 'won') {
                // фиксируем момент победы: от него считается респавн
                $battleUpdate['won_at'] = date('Y-m-d H:i:s');
            }
            Database::update('battles', $battleUpdate, 'id = ?', [(int) $battle['id']]);

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
        // Отступать можно только из идущего боя. Иначе выигранный бой
        // превращался в «fled» и ломал подсчёт респавна/статистику.
        if ($battle['state'] !== 'active') {
            throw new \InvalidArgumentException('Бой уже завершён.');
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

        Database::update('characters', $update, 'id = ?', [(int) $char['id']]);

        if ($levelup) {
            // Рост характеристик пересчитываем целиком (база за уровень +
            // бонусы экипировки). Раньше прибавка шла к hp_max, который уже
            // включал бонус предметов, и при следующей смене экипировки
            // накопленный прирост «съедался».
            (new ShopService())->recalcStats((int) $char['id']);
            $refreshed = Database::one(
                'SELECT hp_max, mp_max FROM ' . Database::table('characters') . ' WHERE id = ?',
                [(int) $char['id']]
            );
            // после уровня — полное восстановление
            Database::update('characters', [
                'hp' => (int) ($refreshed['hp_max'] ?? 0),
                'mp' => (int) ($refreshed['mp_max'] ?? 0),
            ], 'id = ?', [(int) $char['id']]);
        }

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

    /** Все активные бои персонажа (клиент мог «потерять» часть из них). */
    public function activeBattles(int $charId): array
    {
        $bt = Database::table('battles');
        $rows = Database::all(
            "SELECT * FROM {$bt} WHERE char_id = ? AND state = 'active' ORDER BY id",
            [$charId]
        );
        return array_map(fn (array $r) => $this->battleState($r), $rows);
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
        $dead = (int) $char['hp'] <= 0;
        $cost = 0;

        // Проверять «полностью здоров» нужно только для живых: у мёртвого
        // hp = 0 < hp_max, и старая проверка ошибочно считала его здоровым.
        if (!$dead && (int) $char['hp'] >= (int) $char['hp_max'] && (int) $char['mp'] >= (int) $char['mp_max']) {
            throw new \InvalidArgumentException('Персонаж уже полностью здоров.');
        }
        if (!$dead) {
            // БАЛАНС: раньше лечение живого было БЕСПЛАТНЫМ в любой момент, и
            // зелье за 450 адены (120 HP) теряло смысл — выгоднее нажать
            // «отдых в таверне» и получить полный HP/MP. Цена пропорциональна
            // недостающему здоровью: полный хил с нуля чуть дороже зелья,
            // но дешевле трёх зелий.
            $hpMiss = max(0, (int) $char['hp_max'] - (int) $char['hp']);
            $mpMiss = max(0, (int) $char['mp_max'] - (int) $char['mp']);
            if ($hpMiss + $mpMiss > 0) {
                $cost = max(1, (int) ceil(($hpMiss + $mpMiss) * self::HEAL_COST_PER_POINT));
            }
        } else {
            $cost = self::RESURRECT_COST;
        }
        if ($cost > 0 && (int) $char['adena'] < $cost) {
            throw new \InvalidArgumentException(
                $dead
                    ? 'Не хватает адены на реанимацию (нужно ' . self::RESURRECT_COST . '). Продайте предмет из инвентаря.'
                    : 'Не хватает адены на лечение (нужно ' . $cost . '). Здоровье восстанавливается и само.'
            );
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
        // Клиент шлёт координаты из canvas; отрицательные и огромные — обрезаем.
        // БАГ: мусор в теле запроса ("x":"abc", "x":[], null) превращался в 0,
        // и персонаж молча телепортировался в левый верхний угол карты.
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
        $char = $this->applyRegen($char);
        foreach (['id', 'level', 'exp', 'hp', 'hp_max', 'mp', 'mp_max', 'p_atk', 'm_atk', 'p_def', 'm_def', 'crit', 'adena', 'wins', 'losses', 'x', 'y'] as $k) {
            $char[$k] = (int) $char[$k];
        }
        return $char;
    }

    /**
     * Пассивная регенерация HP/MP по времени.
     *
     * Клиент рисовал «восстановление» сам, но сервер о нём не знал: HUD
     * показывал одно, бой считал другое. Теперь источник истины — сервер.
     * Скорость: 0.2% от максимума в секунду, не чаще одного пересчёта в 3 с.
     */
    private function applyRegen(array $char): array
    {
        // мёртвых не регенерируем: воскрешение только через heal
        if ((int) $char['hp'] <= 0) {
            return $char;
        }
        $hpMax = (int) $char['hp_max'];
        $mpMax = (int) $char['mp_max'];
        if ((int) $char['hp'] >= $hpMax && (int) $char['mp'] >= $mpMax) {
            return $char;
        }

        $last = (string) ($char['last_seen'] ?? '');
        $since = $last === '' ? 0 : time() - strtotime($last);
        // слишком давно не заходил — не начисляем «офлайн-реген» пачкой
        if ($since < 3) {
            return $char;
        }
        $seconds = min($since, 60);   // не больше минуты за один раз

        $hp = (int) $char['hp'];
        $mp = (int) $char['mp'];
        if ($hp < $hpMax) {
            $hp = min($hpMax, $hp + max(1, (int) round($hpMax * 0.002 * $seconds)));
        }
        if ($mp < $mpMax) {
            $mp = min($mpMax, $mp + max(1, (int) round($mpMax * 0.002 * $seconds)));
        }
        if ($hp === (int) $char['hp'] && $mp === (int) $char['mp']) {
            return $char;
        }

        Database::update('characters', [
            'hp' => $hp,
            'mp' => $mp,
            'last_seen' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $char['id']]);

        $char['hp'] = $hp;
        $char['mp'] = $mp;
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
