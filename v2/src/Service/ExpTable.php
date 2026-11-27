<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;

/** Таблица опыта (legacy inc/lvl.php) с кэшем в памяти. */
final class ExpTable
{
    /** @var array<int,int>|null */
    private static ?array $table = null;

    /** @return array<int,int> */
    public static function all(): array
    {
        if (self::$table === null) {
            self::$table = [];
            $rows = Database::all('SELECT level, exp_total FROM ' . Database::table('level_exp') . ' ORDER BY level');
            foreach ($rows as $r) {
                self::$table[(int) $r['level']] = (int) $r['exp_total'];
            }
            if (self::$table === []) {
                // страховка, если миграция ещё не выполнена
                $total = 0;
                for ($lvl = 1; $lvl <= 50; $lvl++) {
                    if ($lvl > 1) {
                        $total += (int) round(30 * ($lvl - 1) ** 2.1 + 40 * ($lvl - 1));
                    }
                    self::$table[$lvl] = $total;
                }
            }
        }
        return self::$table;
    }

    public static function totalFor(int $level): int
    {
        $t = self::all();
        if ($level <= 1) {
            return 0;
        }
        return $t[$level] ?? (int) (max($t ?: [0]) * pow(1.12, max(0, $level - 50)));
    }

    public static function maxLevel(): int
    {
        $t = self::all();
        return $t === [] ? 50 : (int) max(array_keys($t));
    }

    public static function reset(): void
    {
        self::$table = null;
    }
}
