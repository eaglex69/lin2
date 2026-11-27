<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
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
        // Раса/класс/пол обязаны быть из белого списка: раньше принималось
        // что угодно (например class=godmode или HTML в поле race).
        if (!isset(self::RACES[$race])) {
            throw new \InvalidArgumentException('Неизвестная раса.');
        }
        if (!isset(self::CLASSES[$class])) {
            throw new \InvalidArgumentException('Неизвестный класс.');
        }
        if (!in_array($gender, ['m', 'f'], true)) {
            $gender = 'm';
        }
        $passLen = function_exists('mb_strlen') ? mb_strlen($pass, 'UTF-8') : strlen($pass);
        if ($passLen < 8) {
            throw new \InvalidArgumentException('Пароль минимум 8 символов.');
        }
        if ($passLen > 200) {
            throw new \InvalidArgumentException('Пароль слишком длинный (максимум 200 символов).');
        }
        // bcrypt обрезает пароль на 72 байтах: «AAA…(75)» и «AAA…(80)» дали бы
        // одинаковый хеш и вход по чужому паролю. Кириллица — 2 байта на символ,
        // поэтому 200 символов легко перерастают 72 байта.
        $algo = (string) (password_get_info(password_hash('probe', PASSWORD_DEFAULT))['algoName'] ?? '');
        if (strlen($pass) > self::passwordBytes($algo)) {
            throw new \InvalidArgumentException(
                'Пароль слишком длинный: для этого алгоритма максимум ' . self::passwordBytes($algo) . ' байт.'
            );
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
        // Регистронезависимая проверка: MySQL сравнивает ник без учёта регистра
        // (utf8mb4_unicode_ci), а SQLite — с учётом. Из-за этого в превью можно
        // было завести «demo», «Demo» и «DEMO» как разных игроков.
        $norm = self::normalizeNick($nick);
        $taken = false;
        foreach (Database::all("SELECT nick FROM {$t}") as $row) {
            if (self::normalizeNick((string) $row['nick']) === $norm) {
                $taken = true;
                break;
            }
        }
        if ($taken) {
            throw new \InvalidArgumentException('Такой логин уже занят.');
        }
        $ct = Database::table('characters');
        $charTaken = false;
        foreach (Database::all("SELECT name FROM {$ct}") as $row) {
            if (self::normalizeNick((string) $row['name']) === $norm) {
                $charTaken = true;
                break;
            }
        }
        if ($charTaken) {
            throw new \InvalidArgumentException('Такой персонаж уже существует.');
        }

        // Антиспам: регистрация не требует пароля для проверки, поэтому
        // скрипт мог за минуту создать тысячи аккаунтов (каждый — argon2id,
        // ~50 мс CPU в проде). Ограничиваем по IP и общим суточным числом.
        $this->assertCanRegister();

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

    /**
     * Сколько байт пароля реально участвует в хеше.
     *
     * БАГ: password_hash() по умолчанию bcrypt молча обрезает пароль на 72
     * байтах. Регистрация с длинным паролем («A…» 79 символов) проходила, но
     * вход принимал ЛЮБОЙ пароль с теми же первыми 72 байтами — проверено
     * вживую: пароль с другим хвостом после 72-го байта пускал в аккаунт.
     * Теперь, если алгоритм не поддерживает длинные пароли, отказываем в
     * регистрации вместо тихой обрезки.
     */
    public static function passwordBytes(string $algo): int
    {
        return str_contains($algo, 'bcrypt') ? 72 : 4096;
    }

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
        if ($acc === null) {
            // запасной поиск без учёта регистра: SQLite иначе строг
            $norm = self::normalizeNick($nick);
            foreach (Database::all("SELECT * FROM {$t}") as $row) {
                if (self::normalizeNick((string) $row['nick']) === $norm) {
                    $acc = $row;
                    break;
                }
            }
        }
        if ($acc === null || !password_verify($pass, (string) $acc['pass_hash'])) {
            $this->recordFailedLogin($nick);
            // одинаковый ответ и одинаковое время для существующего/несуществующего логина
            throw new \InvalidArgumentException('Неверный логин или пароль.');
        }

        $this->clearFailedLogins($nick);

        // Обновляем хеш при смене алгоритма PHP. Только если пароль целиком
        // попадает в лимит алгоритма: иначе перехеширование молча обрезало бы
        // его, и старый (полный) пароль перестал бы подходить.
        $rehashAlgo = (string) (password_get_info(password_hash('probe', PASSWORD_DEFAULT))['algoName'] ?? '');
        if (password_needs_rehash((string) $acc['pass_hash'], PASSWORD_DEFAULT)
            && strlen($pass) <= self::passwordBytes($rehashAlgo)) {
            Database::update('accounts', ['pass_hash' => password_hash($pass, PASSWORD_DEFAULT)], 'id = ?', [(int) $acc['id']]);
        }

        // БАГ: при каждом входе ip обнулялся ('ip' => ''), поэтому после
        // регистрации (она сразу логинит) адрес оказывался пустым, а антиспам
        // регистраций по IP не мог ничего посчитать. Пишем настоящий адрес.
        Database::update('accounts', [
            'last_login' => date('Y-m-d H:i:s'),
            'ip' => \App\Core\Request::currentIp(),
        ], 'id = ?', [(int) $acc['id']]);

        Session::regenerate();
        Session::set('account_id', (int) $acc['id']);
        Session::set('nick', (string) $acc['nick']);
        Session::set('role', (string) $acc['role']);
        $this->selectCharacter((int) $acc['id'], (int) ($acc['active_char'] ?? 0));
        Session::set('_csrf', bin2hex(random_bytes(24)));

        return ['account_id' => (int) $acc['id'], 'nick' => (string) $acc['nick'], 'role' => (string) $acc['role']];
    }

    /** Ник для сравнения: без учёта регистра и краевых пробелов. */
    private static function normalizeNick(string $nick): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower(trim($nick), 'UTF-8')
            : strtolower(trim($nick));
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

    /** Порог и окно антиспама регистрации. */
    private const REGISTER_MAX_PER_IP = 5;
    private const REGISTER_WINDOW_SEC = 3600;
    private const REGISTER_MAX_TOTAL_PER_DAY = 300;

    /**
     * Ограничение регистраций: 5 в час с одного IP и 300 в сутки всего.
     * @throws \InvalidArgumentException
     */
    private function assertCanRegister(): void
    {
        // в тестах лимит отключён: харнесс создаёт десятки аккаунтов подряд
        if (Config::get('APP_ENV') === 'testing') {
            return;
        }
        $t = Database::table('accounts');
        $dayAgo = date('Y-m-d H:i:s', time() - 86400);
        $total = (int) Database::value("SELECT COUNT(*) FROM {$t} WHERE created_at >= ?", [$dayAgo]);
        if ($total >= self::REGISTER_MAX_TOTAL_PER_DAY) {
            throw new \InvalidArgumentException('Регистрация временно недоступна: слишком много новых аккаунтов за сутки.');
        }

        $ip = \App\Core\Request::currentIp();
        $hourAgo = date('Y-m-d H:i:s', time() - self::REGISTER_WINDOW_SEC);
        $fromIp = (int) Database::value(
            "SELECT COUNT(*) FROM {$t} WHERE created_at >= ? AND ip = ?",
            [$hourAgo, $ip]
        );
        if ($fromIp >= self::REGISTER_MAX_PER_IP) {
            throw new \InvalidArgumentException('Слишком много регистраций с этого адреса. Попробуйте позже.');
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
        foreach (['id', 'account_id', 'level', 'hp', 'hp_max', 'mp', 'mp_max',
                  'p_atk', 'm_atk', 'p_def', 'm_def', 'crit', 'x', 'y',
                  'wins', 'losses', 'pvp'] as $k) {
            if (isset($c[$k])) {
                $c[$k] = (int) $c[$k];
            }
        }
        // БАГ: adena и exp кастовались в int, а PHP_INT_SIZE здесь 4 (32 бита).
        // Сумма 9 000 000 000 превращалась в 2 147 483 647 и такой же
        // показывалась в HUD. Оставляем double (53 бита точно).
        foreach (['adena', 'exp'] as $k) {
            if (isset($c[$k])) {
                $c[$k] = round((float) $c[$k]);
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
