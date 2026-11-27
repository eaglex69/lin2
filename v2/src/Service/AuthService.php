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
        if ($passLen < 8) {
            throw new \InvalidArgumentException('Пароль минимум 8 символов.');
        }
        if ($passLen > 200) {
            throw new \InvalidArgumentException('Пароль слишком длинный (максимум 200 символов).');
        }
        // не даём регистрировать очевидно слабые пароли
        if (preg_match('/^(?:pass|password|1234567|qwerty|letmein|admin|demo|1111)/i', $pass)) {
            throw new \InvalidArgumentException('Пароль слишком простой.');
        }
        // email, если указан, обязан быть корректным и не слишком длинным
        if ($email !== '' && (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            throw new \InvalidArgumentException('Некорректный email.');
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

    /** Порог и окно блокировки подбора пароля. */
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SEC = 300;
    private const LOGIN_BLOCK_SEC = 600;

    public function login(string $nick, string $pass): array
    {
        $t = Database::table('accounts');
        $nick = trim($nick);

        // Пустой пароль/логин не пускаем даже при наличии хеша в БД,
        // и ограничиваем длину: bcrypt молча обрезает на 72 байтах, а
        // огромный ввод зря жжёт CPU при проверке.
        if ($nick === '' || $pass === '' || strlen($nick) > 64 || strlen($pass) > 200) {
            $this->recordFailedLogin($nick === '' ? '-' : $nick);
            throw new \InvalidArgumentException('Неверный логин или пароль.');
        }

        $this->assertNotLockedOut($nick);

        $acc = Database::one("SELECT * FROM {$t} WHERE nick = ?", [$nick]);
        if ($acc === null || !password_verify($pass, (string) $acc['pass_hash'])) {
            $this->recordFailedLogin($nick);
            // одинаковый ответ и одинаковое время для существующего/несуществующего логина
            throw new \InvalidArgumentException('Неверный логин или пароль.');
        }

        $this->clearFailedLogins($nick);

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

    /** @throws \InvalidArgumentException если попытки исчерпаны */
    private function assertNotLockedOut(string $nick): void
    {
        $row = Database::one(
            'SELECT cnt, blocked_until FROM ' . Database::table('login_attempts') . ' WHERE nick = ?',
            [strtolower($nick)]
        );
        if ($row === null) {
            return;
        }
        $until = $row['blocked_until'] === null ? 0 : strtotime((string) $row['blocked_until']);
        if ($until > time()) {
            $wait = (int) ceil(($until - time()) / 60);
            throw new \InvalidArgumentException("Слишком много попыток. Повторите через {$wait} мин.");
        }
    }

    private function recordFailedLogin(string $nick): void
    {
        $t = Database::table('login_attempts');
        $key = strtolower($nick);
        $now = time();

        $row = Database::one("SELECT * FROM {$t} WHERE nick = ?", [$key]);
        if ($row === null) {
            Database::insert('login_attempts', [
                'nick' => $key,
                'cnt' => 1,
                'first_ts' => $now,
                'blocked_until' => null,
            ]);
            return;
        }

        $first = (int) $row['first_ts'];
        $cnt = (int) $row['cnt'];
        // окно истекло — начинаем счёт заново
        if ($now - $first > self::LOGIN_WINDOW_SEC) {
            $cnt = 0;
            $first = $now;
        }
        $cnt++;

        $blockedUntil = null;
        if ($cnt >= self::LOGIN_MAX_ATTEMPTS) {
            $blockedUntil = date('Y-m-d H:i:s', $now + self::LOGIN_BLOCK_SEC);
        }

        Database::update('login_attempts', [
            'cnt' => $cnt,
            'first_ts' => $first,
            'blocked_until' => $blockedUntil,
        ], 'nick = ?', [$key]);
    }

    private function clearFailedLogins(string $nick): void
    {
        Database::exec(
            'DELETE FROM ' . Database::table('login_attempts') . ' WHERE nick = ?',
            [strtolower($nick)]
        );
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
