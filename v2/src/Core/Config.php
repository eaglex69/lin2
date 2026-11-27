<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Конфигурация приложения.
 *
 * Источники (по возрастанию приоритета):
 *   1. значения по умолчанию;
 *   2. файл .env в корне v2/;
 *   3. переменные окружения процесса;
 *   4. файл /config.json — его пишет preview-мост (php-wasm), чтобы передать
 *      настройки внутрь виртуальной ФС.
 */
final class Config
{
    /** @var array<string,string> */
    private static array $values = [];
    private static bool $loaded = false;

    public static function load(string $root): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        self::$values = [
            'APP_NAME'        => 'Miniage',
            'APP_ENV'         => 'production',
            'APP_URL'         => '',
            'DB_DRIVER'       => 'mysql',           // mysql | sqlite
            'DB_HOST'         => '127.0.0.1',
            'DB_PORT'         => '3306',
            'DB_NAME'         => 'miniage',
            'DB_USER'         => 'miniage',
            'DB_PASS'         => '',
            'DB_SQLITE_PATH'  => $root . '/var/miniage.sqlite',
            'DB_PREFIX'       => '',
            'SESSION_NAME'    => 'miniage_sid',
            'SESSION_TTL'     => '86400',
            'SEED_DEMO'       => '1',
            'TIMEZONE'        => 'Europe/Moscow',
        ];

        // 2. .env
        $envFile = $root . '/.env';
        if (is_file($envFile)) {
            foreach (self::parseEnv((string) file_get_contents($envFile)) as $k => $v) {
                self::$values[$k] = $v;
            }
        }

        // 3. окружение
        foreach (array_keys(self::$values) as $key) {
            $env = getenv($key);
            if ($env !== false && $env !== '') {
                self::$values[$key] = $env;
            }
        }

        // 4. /config.json от preview-моста
        if (is_file('/config.json')) {
            $json = json_decode((string) file_get_contents('/config.json'), true);
            if (is_array($json)) {
                foreach ($json as $k => $v) {
                    self::$values[$k] = is_scalar($v) ? (string) $v : self::$values[$k] ?? '';
                }
            }
        }

        date_default_timezone_set(self::get('TIMEZONE', 'UTC'));
    }

    private static function parseEnv(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[-1] === $v[0]) {
                $v = substr($v, 1, -1);
            }
            $out[$k] = $v;
        }
        return $out;
    }

    public static function get(string $key, string $default = ''): string
    {
        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::$values[$key] ?? null;
        if ($v === null) {
            return $default;
        }
        return in_array(strtolower($v), ['1', 'true', 'on', 'yes'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        return isset(self::$values[$key]) ? (int) self::$values[$key] : $default;
    }

    /** Переопределение в рантайме (fallback MySQL -> SQLite). */
    public static function set(string $key, string $value): void
    {
        self::$values[$key] = $value;
    }

    /** Повторная инициализация (переиспользуемый PHP-процесс в превью). */
    public static function reload(): void
    {
        self::$loaded = false;
        self::$values = [];
    }
}
