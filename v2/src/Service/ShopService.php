<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;

/**
 * Магазин, инвентарь, экипировка. Заменяет legacy shop.php/inventar.php,
 * но работает через JSON API (AJAX) и canvas-интерфейс.
 */
final class ShopService
{
    /** @return array<int,array<string,mixed>> */
    public function catalog(?string $kind = null): array
    {
        $t = Database::table('items');
        $sql = "SELECT * FROM {$t}";
        $params = [];
        if ($kind !== null && $kind !== '' && $kind !== 'all') {
            $sql .= ' WHERE kind = ?';
            $params[] = $kind;
        }
        $sql .= ' ORDER BY kind, level_req, price';
        $rows = Database::all($sql, $params);
        return array_map([$this, 'decorateItem'], $rows);
    }

    public function itemByCode(string $code): ?array
    {
        $t = Database::table('items');
        $row = Database::one("SELECT * FROM {$t} WHERE code = ?", [$code]);
        return $row === null ? null : $this->decorateItem($row);
    }

    public function buy(int $charId, string $code, int $qty = 1): array
    {
        $qty = max(1, min(99, $qty));
        $item = $this->itemByCode($code);
        if ($item === null) {
            throw new \InvalidArgumentException('Такого предмета нет в магазине.');
        }
        if (!$item['stackable']) {
            $qty = 1;
        }

        return Database::transaction(function () use ($charId, $item, $qty) {
            $char = (new GameService())->requireChar($charId);
            $total = $item['price'] * $qty;
            if ($char['adena'] < $total) {
                throw new \InvalidArgumentException('Недостаточно адены: нужно ' . number_format($total, 0, '', ' ') . '.');
            }
            if ($char['level'] < $item['level_req']) {
                throw new \InvalidArgumentException("Требуется {$item['level_req']} уровень.");
            }

            $inv = Database::table('inventory');
            $row = Database::one("SELECT * FROM {$inv} WHERE char_id = ? AND item_id = ? AND equipped = 0", [$charId, $item['id']]);
            if ($row !== null && $item['stackable']) {
                Database::update('inventory', ['qty' => (int) $row['qty'] + $qty], 'id = ?', [(int) $row['id']]);
            } else {
                Database::insert('inventory', ['char_id' => $charId, 'item_id' => $item['id'], 'qty' => $qty, 'equipped' => 0]);
            }

            Database::update('characters', ['adena' => $char['adena'] - $total], 'id = ?', [$charId]);

            return [
                'item' => $item,
                'qty' => $qty,
                'paid' => $total,
                'adena' => $char['adena'] - $total,
            ];
        });
    }

    public function sell(int $charId, int $inventoryId, int $qty = 1): array
    {
        return Database::transaction(function () use ($charId, $inventoryId, $qty) {
            $inv = Database::table('inventory');
            $row = Database::one("SELECT * FROM {$inv} WHERE id = ? AND char_id = ?", [$inventoryId, $charId]);
            if ($row === null) {
                throw new \InvalidArgumentException('Предмет не найден в инвентаре.');
            }
            if ((int) $row['equipped'] === 1) {
                throw new \InvalidArgumentException('Сначала снимите предмет.');
            }
            $item = $this->itemByCodeById((int) $row['item_id']);
            if ($item === null) {
                throw new \InvalidArgumentException('Предмет не найден.');
            }
            $qty = max(1, min((int) $row['qty'], $qty));
            $refund = (int) floor($item['price'] * 0.5) * $qty;

            if ((int) $row['qty'] > $qty) {
                Database::update('inventory', ['qty' => (int) $row['qty'] - $qty], 'id = ?', [(int) $row['id']]);
            } else {
                Database::exec("DELETE FROM {$inv} WHERE id = ?", [(int) $row['id']]);
            }

            $char = (new GameService())->requireChar($charId);
            Database::update('characters', ['adena' => $char['adena'] + $refund], 'id = ?', [$charId]);

            return ['sold' => $qty, 'refund' => $refund, 'adena' => $char['adena'] + $refund];
        });
    }

    /** Надеть/снять предмет. Учитывает бонусы и слот. */
    public function equip(int $charId, int $inventoryId, bool $equip = true): array
    {
        return Database::transaction(function () use ($charId, $inventoryId, $equip) {
            $invT = Database::table('inventory');
            $row = Database::one("SELECT * FROM {$invT} WHERE id = ? AND char_id = ?", [$inventoryId, $charId]);
            if ($row === null) {
                throw new \InvalidArgumentException('Предмет не найден.');
            }
            $item = $this->itemByCodeById((int) $row['item_id']);
            if ($item === null || $item['slot'] === 'none') {
                throw new \InvalidArgumentException('Этот предмет нельзя надеть.');
            }

            $game = new GameService();
            $char = $game->requireChar($charId);

            if ($equip) {
                // снимаем всё, что было в том же слоте
                $same = Database::all(
                    "SELECT i.id, it.slot FROM {$invT} i JOIN " . Database::table('items') . " it ON it.id = i.item_id
                     WHERE i.char_id = ? AND i.equipped = 1 AND it.slot = ?",
                    [$charId, $item['slot']]
                );
                foreach ($same as $s) {
                    Database::update('inventory', ['equipped' => 0], 'id = ?', [(int) $s['id']]);
                }
            }

            Database::update('inventory', ['equipped' => $equip ? 1 : 0], 'id = ?', [$inventoryId]);

            $this->recalcStats($charId);

            return ['char' => $game->requireChar($charId)];
        });
    }

    /** Пересчёт итоговых характеристик: база + экипировка. */
    public function recalcStats(int $charId): void
    {
        $cT = Database::table('characters');
        $char = Database::one("SELECT * FROM {$cT} WHERE id = ?", [$charId]);
        if ($char === null) {
            return;
        }
        $level = (int) $char['level'];
        $base = match ($char['class']) {
            'mage'   => ['hp' => 80,  'mp' => 110, 'p_atk' => 10, 'm_atk' => 26, 'p_def' => 8,  'm_def' => 14],
            'archer' => ['hp' => 95,  'mp' => 70,  'p_atk' => 24, 'm_atk' => 8,  'p_def' => 10, 'm_def' => 9],
            'healer' => ['hp' => 85,  'mp' => 120, 'p_atk' => 9,  'm_atk' => 22, 'p_def' => 9,  'm_def' => 16],
            default  => ['hp' => 120, 'mp' => 60,  'p_atk' => 26, 'm_atk' => 6,  'p_def' => 14, 'm_def' => 8],
        };
        // прирост за уровни (совпадает с GameService::applyRewards при levelup)
        $hpMax = $base['hp'];
        $mpMax = $base['mp'];
        $pAtk = $base['p_atk'];
        $mAtk = $base['m_atk'];
        $pDef = $base['p_def'];
        $mDef = $base['m_def'];
        for ($lvl = 2; $lvl <= $level; $lvl++) {
            $hpMax += 12 + $lvl * 2;
            $mpMax += 6 + $lvl;
            $pAtk += 2;
            $mAtk += 2;
            $pDef += 1;
            $mDef += 1;
        }

        $rows = Database::all(
            "SELECT it.* FROM " . Database::table('inventory') . " i JOIN " . Database::table('items') . " it ON it.id = i.item_id
             WHERE i.char_id = ? AND i.equipped = 1",
            [$charId]
        );
        foreach ($rows as $it) {
            $hpMax += (int) $it['hp_bonus'];
            $mpMax += (int) $it['mp_bonus'];
            $pAtk += (int) $it['p_atk'];
            $mAtk += (int) $it['m_atk'];
            $pDef += (int) $it['p_def'];
            $mDef += (int) $it['m_def'];
        }

        Database::update('characters', [
            'hp_max' => $hpMax,
            'hp' => min((int) $char['hp'] > 0 ? (int) $char['hp'] : $hpMax, $hpMax),
            'mp_max' => $mpMax,
            'mp' => min((int) $char['mp'], $mpMax),
            'p_atk' => $pAtk,
            'm_atk' => $mAtk,
            'p_def' => $pDef,
            'm_def' => $mDef,
        ], 'id = ?', [$charId]);
    }

    /** @return array<int,array<string,mixed>> */
    public function inventory(int $charId): array
    {
        $sql = "SELECT i.id AS inv_id, i.qty, i.equipped, it.*
                FROM " . Database::table('inventory') . " i
                JOIN " . Database::table('items') . " it ON it.id = i.item_id
                WHERE i.char_id = ?
                ORDER BY i.equipped DESC, it.kind, it.name";
        $rows = Database::all($sql, [$charId]);
        return array_map(function (array $r) {
            $item = $this->decorateItem($r);
            $item['inv_id'] = (int) $r['inv_id'];
            $item['qty'] = (int) $r['qty'];
            $item['equipped'] = (bool) $r['equipped'];
            return $item;
        }, $rows);
    }

    public function useItem(int $charId, int $inventoryId): array
    {
        return Database::transaction(function () use ($charId, $inventoryId) {
            $invT = Database::table('inventory');
            $row = Database::one("SELECT * FROM {$invT} WHERE id = ? AND char_id = ?", [$inventoryId, $charId]);
            if ($row === null) {
                throw new \InvalidArgumentException('Предмет не найден.');
            }
            $item = $this->itemByCodeById((int) $row['item_id']);
            if ($item === null || $item['kind'] !== 'potion') {
                throw new \InvalidArgumentException('Это нельзя использовать.');
            }
            $game = new GameService();
            $char = $game->requireChar($charId);
            $hp = min($char['hp_max'], $char['hp'] + $item['hp_bonus']);
            $mp = min($char['mp_max'], $char['mp'] + $item['mp_bonus']);
            Database::update('characters', ['hp' => $hp, 'mp' => $mp], 'id = ?', [$charId]);

            if ((int) $row['qty'] > 1) {
                Database::update('inventory', ['qty' => (int) $row['qty'] - 1], 'id = ?', [(int) $row['id']]);
            } else {
                Database::exec("DELETE FROM {$invT} WHERE id = ?", [(int) $row['id']]);
            }

            return ['hp' => $hp, 'mp' => $mp, 'char' => $game->requireChar($charId)];
        });
    }

    private function itemByCodeById(int $id): ?array
    {
        $t = Database::table('items');
        $row = Database::one("SELECT * FROM {$t} WHERE id = ?", [$id]);
        return $row === null ? null : $this->decorateItem($row);
    }

    public function decorateItem(array $r): array
    {
        foreach (['id', 'p_atk', 'm_atk', 'p_def', 'm_def', 'hp_bonus', 'mp_bonus', 'price', 'level_req'] as $k) {
            if (isset($r[$k])) {
                $r[$k] = (int) $r[$k];
            }
        }
        $r['stackable'] = (bool) ($r['stackable'] ?? false);
        return $r;
    }
}
