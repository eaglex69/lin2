<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Сессии.
 *
 * По умолчанию — нативные PHP-сессии (PHP 8.1, безопасные cookie).
 * Если нативная сессия недоступна (php-wasm без записи в /tmp), включается
 * файловый режим: sid в cookie + data_json в таблице sessions.
 *
 * Cookie: HttpOnly, SameSite=Lax; в HTTPS — Secure.
 * CSRF-токен генерируется на сессию и проверяется на всех POST-запросах API.
 */
final class Session
{
    private static bool $native = false;
    private static ?string $sid = null;
    private static bool $sessionInUrl = false;

    /**
     * Сброс статического состояния перед обработкой запроса.
     *
     * Нужен там, где PHP-процесс переиспользуется между запросами
     * (php-wasm в превью): иначе sid одной сессии «протекает» в следующую.
     * Под PHP-FPM состояние и так создаётся заново на каждый запрос.
     */
    public static function resetState(): void
    {
        self::$native = false;
        self::$sid = null;
        self::$sessionInUrl = false;
    }

    public static function start(Request $req): void
    {
        if (self::$sid !== null) {
            return;
        }

        $name = Config::get('SESSION_NAME', 'miniage_sid');
        $cookies = $req->cookies;
        $candidate = $cookies[$name] ?? '';

        // Резерв 1: заголовок X-Miniage-Session (клиент хранит его сам)
        if ($candidate === '') {
            $hdr = (string) ($req->header('X-Miniage-Session') ?: '');
            if (preg_match('/^[a-f0-9]{32,64}$/', $hdr)) {
                $candidate = $hdr;
            }
        }

        // Резерв 2: sid в URL (iframe-превью, где cookie заблокированы)
        if ($candidate === '') {
            $q = $req->get[$name] ?? '';
            if (is_string($q) && preg_match('/^[a-f0-9]{32,64}$/', $q)) {
                $candidate = $q;
                self::$sessionInUrl = true;
            }
        }

        if (self::tryNative($candidate)) {
            self::$native = true;
            self::$sid = session_id() ?: $candidate;
            // даже с нативной сессией отдаём sid заголовком — превью в iframe
            // и php-wasm теряют Set-Cookie
            self::sendCookie();
            return;
        }

        self::$sid = $candidate !== '' && preg_match('/^[a-f0-9]{32,64}$/', $candidate)
            ? $candidate
            : bin2hex(random_bytes(24));

        self::ensureRow();
        self::sendCookie();
    }

    private static function tryNative(string $candidate): bool
    {
        if (PHP_SAPI === 'cli' && !defined('MINIAGE_WASM')) {
            return false;
        }
        // В php-wasm нет записи сессий в файловую систему — используем БД.
        if (defined('MINIAGE_WASM')) {
            return false;
        }
        $savePath = @ini_get('session.save_path');
        if ($savePath === false || $savePath === '' || !is_writable(sys_get_temp_dir())) {
            return false;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        if ($candidate !== '' && preg_match('/^[a-f0-9]{32,64}$/', $candidate)) {
            session_id($candidate);
        }
        $params = [
            'lifetime' => Config::int('SESSION_TTL', 86400),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => self::isHttps(),
        ];
        try {
            return @session_start($params);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function ensureRow(): void
    {
        $t = Database::table('sessions');
        $exists = Database::one("SELECT id FROM {$t} WHERE id = ?", [self::$sid]);
        if ($exists === null) {
            Database::insert('sessions', [
                'id' => self::$sid,
                'account_id' => null,
                'data_json' => '{}',
                'last_used' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public static function sendCookie(): void
    {
        $name = Config::get('SESSION_NAME', 'miniage_sid');
        if (headers_sent() || self::$sid === null) {
            return;
        }
        // Дублируем sid заголовком: setcookie() может потеряться в embed SAPI.
        header('X-Miniage-Session: ' . self::$sid);
        header('Access-Control-Expose-Headers: X-Miniage-Session');
        setcookie($name, self::$sid, [
            'expires' => time() + Config::int('SESSION_TTL', 86400),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => self::isHttps(),
        ]);
    }

    private static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';
        $fwd = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        return ($https !== '' && $https !== 'off') || $fwd === 'https';
    }

    public static function id(): string
    {
        return self::$sid ?? '';
    }

    public static function inUrl(): bool
    {
        return self::$sessionInUrl;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$native && session_status() === PHP_SESSION_ACTIVE) {
            return $_SESSION[$key] ?? $default;
        }
        $data = self::loadFileData();
        return $data[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        if (self::$native && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$key] = $value;
            return;
        }
        $data = self::loadFileData();
        $data[$key] = $value;
        self::saveFileData($data);
    }

    public static function forget(string $key): void
    {
        if (self::$native && session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[$key]);
            return;
        }
        $data = self::loadFileData();
        unset($data[$key]);
        self::saveFileData($data);
    }

    /**
     * Смена sid при входе (защита от fixation).
     *
     * В файловом режиме переносим данные в новую строку, чтобы клиент,
     * приславший старый sid, не потерял сессию до обновления cookie/заголовка.
     */
    public static function regenerate(): void
    {
        if (self::$native && session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
            self::$sid = session_id() ?: self::$sid;
            return;
        }

        $old = self::$sid;
        $data = $old !== null ? self::loadFileData() : [];
        $newSid = bin2hex(random_bytes(24));

        // новая строка с теми же данными
        self::$sid = $newSid;
        self::ensureRow();
        if ($data !== []) {
            self::saveFileData($data);
        }

        // старую сессию удаляем, но не раньше, чем данные перенесены
        if ($old !== null && $old !== $newSid) {
            Database::exec('DELETE FROM ' . Database::table('sessions') . ' WHERE id = ?', [$old]);
        }

        self::sendCookie();
    }

    /** @return array<string,mixed> */
    private static function loadFileData(): array
    {
        $t = Database::table('sessions');
        $row = Database::one("SELECT data_json FROM {$t} WHERE id = ?", [self::$sid]);
        if ($row === null) {
            return [];
        }
        $data = json_decode((string) $row['data_json'], true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string,mixed> $data */
    private static function saveFileData(array $data): void
    {
        $t = Database::table('sessions');
        Database::update('sessions', [
            'data_json' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'last_used' => date('Y-m-d H:i:s'),
        ], 'id = ?', [self::$sid]);
    }

    public static function csrfToken(): string
    {
        $token = self::get('_csrf');
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(24));
            self::set('_csrf', $token);
        }
        return $token;
    }

    public static function checkCsrf(?string $token): bool
    {
        $stored = self::get('_csrf');
        return is_string($stored) && is_string($token) && $token !== ''
            && hash_equals($stored, $token);
    }

    public static function destroy(): void
    {
        if (self::$native && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            @session_destroy();
            self::$sid = null;
            return;
        }
        if (self::$sid !== null) {
            Database::exec('DELETE FROM ' . Database::table('sessions') . ' WHERE id = ?', [self::$sid]);
            self::$sid = null;
        }
    }
}
