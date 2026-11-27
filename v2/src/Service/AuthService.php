<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Migrator;
use App\Core\Session;

/**
 * Регистрация, вход, выход. Пароли — password_hash(ARGON2ID/BCRYPT).
 * Legacy md5 не используется.
 */
final class AuthService
{
    public function register(string $nick, string $pass, string $email, string $race, string $class, string $gender): array
    {
        $nick = trim($nick);
        $email = trim($email);

        if (!preg_match('/^[A-Za-z0-9_-]{3,16}$/u', $nick)) {
            throw new \InvalidArgumentException('Логин: 3–16 символов, латиница, цифры, _ и -.');
        }
        $passLen = function_exists('mb_strlen') ? mb_strlen($pass, 'UTF-8') : strlen($pass);
        if ($passLen < 4) {
            throw new \InvalidArgumentException('Пароль минимум 4 символа.');
        }
        $t = Database::table('accounts');
        if (Database::one("SELECT id FROM {$t} WHERE nick = ?", [$nick]) !== null) {
            throw new \InvalidArgumentException('Такой логин уже занят.');
        }
        $ct = Database::table('characters');
        if (Database::one("SELECT id FROM {$ct} WHERE name = ?", [$nick]) !== null) {
            throw new \InvalidArgumentException('Такой персонаж уже существует.');
        }

        return Database::transaction(function () use ($nick, $pass, $email, $race, $class, $gender) {
            $accountId = Migrator::createAccount($nick, $pass, $email);
            $charId = Migrator::createCharacter($accountId, $nick, $race, $class, $gender);
            Database::update('accounts', ['active_char' => $charId], 'id = ?', [$accountId]);
            Migrator::giveStarterKit($charId);
            $this->login($nick, $pass);
            return ['account_id' => $accountId, 'char_id' => $charId, 'name' => $nick];
        });
    }

    public function login(string $nick, string $pass): array
    {
        $t = Database::table('accounts');
        $acc = Database::one("SELECT * FROM {$t} WHERE nick = ?", [trim($nick)]);
        if ($acc === null || !password_verify($pass, (string) $acc['pass_hash'])) {
            throw new \InvalidArgumentException('Неверный логин или пароль.');
        }

        // обновляем хеш при смене алгоритма PHP
        if (password_needs_rehash((string) $acc['pass_hash'], PASSWORD_DEFAULT)) {
            Database::update('accounts', ['pass_hash' => password_hash($pass, PASSWORD_DEFAULT)], 'id = ?', [(int) $acc['id']]);
        }

        Database::update('accounts', [
            'last_login' => date('Y-m-d H:i:s'),
            'ip' => '',
        ], 'id = ?', [(int) $acc['id']]);

        Session::regenerate();
        Session::set('account_id', (int) $acc['id']);
        Session::set('nick', (string) $acc['nick']);
        Session::set('role', (string) $acc['role']);
        $this->selectCharacter((int) $acc['id'], (int) ($acc['active_char'] ?? 0));
        Session::set('_csrf', bin2hex(random_bytes(24)));

        return ['account_id' => (int) $acc['id'], 'nick' => (string) $acc['nick'], 'role' => (string) $acc['role']];
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function account(): ?array
    {
        $id = Session::get('account_id');
        if (!is_int($id) && !is_numeric($id)) {
            return null;
        }
        $t = Database::table('accounts');
        return Database::one("SELECT id, nick, email, role, active_char, created_at FROM {$t} WHERE id = ?", [(int) $id]);
    }

    public function character(): ?array
    {
        $charId = Session::get('char_id');
        if ($charId === null) {
            $acc = $this->account();
            $charId = $acc['active_char'] ?? null;
            if ($charId === null) {
                return null;
            }
        }
        $t = Database::table('characters');
        $char = Database::one("SELECT * FROM {$t} WHERE id = ?", [(int) $charId]);
        return $char === null ? null : $this->decorate($char);
    }

    public function selectCharacter(int $accountId, int $charId): ?array
    {
        $t = Database::table('characters');
        $char = Database::one("SELECT * FROM {$t} WHERE id = ? AND account_id = ?", [$charId, $accountId]);
        if ($char === null) {
            return null;
        }
        Session::set('char_id', (int) $char['id']);
        Database::update('accounts', ['active_char' => (int) $char['id']], 'id = ?', [$accountId]);
        return $char;
    }

    /** Список персонажей аккаунта. */
    public function characters(int $accountId): array
    {
        $t = Database::table('characters');
        $rows = Database::all("SELECT * FROM {$t} WHERE account_id = ? ORDER BY id", [$accountId]);
        return array_map(fn (array $r) => $this->decorate($r), $rows);
    }

    /** Приводим типы и добавляем производные поля для JSON API. */
    public function decorate(array $c): array
    {
        foreach (['id', 'account_id', 'level', 'exp', 'hp', 'hp_max', 'mp', 'mp_max',
                  'p_atk', 'm_atk', 'p_def', 'm_def', 'crit', 'adena', 'x', 'y',
                  'wins', 'losses', 'pvp'] as $k) {
            if (isset($c[$k])) {
                $c[$k] = (int) $c[$k];
            }
        }
        $c['exp_next'] = ExpTable::totalFor((int) $c['level'] + 1);
        $c['exp_cur'] = ExpTable::totalFor((int) $c['level']);
        $span = max(1, $c['exp_next'] - $c['exp_cur']);
        $c['exp_percent'] = max(0, min(100, (int) round((($c['exp'] - $c['exp_cur']) / $span) * 100)));
        $c['hp_percent'] = $c['hp_max'] > 0 ? max(0, min(100, (int) round($c['hp'] / $c['hp_max'] * 100))) : 0;
        $c['mp_percent'] = $c['mp_max'] > 0 ? max(0, min(100, (int) round($c['mp'] / $c['mp_max'] * 100))) : 0;
        $c['race_label'] = self::RACES[$c['race']] ?? $c['race'];
        $c['class_label'] = self::CLASSES[$c['class']] ?? $c['class'];
        return $c;
    }

    public const RACES = [
        'human' => 'Человек', 'elf' => 'Светлый эльф', 'darkelf' => 'Тёмный эльф',
        'orc' => 'Орк', 'dwarf' => 'Гном', 'kamael' => 'Камаэль',
    ];

    public const CLASSES = [
        'fighter' => 'Воин', 'mage' => 'Маг', 'archer' => 'Лучник', 'healer' => 'Целитель',
    ];
}
