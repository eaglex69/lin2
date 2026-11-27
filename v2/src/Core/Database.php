<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Единый доступ к БД через PDO.
 *
 * Боевой режим: MySQL 8 (utf8mb4, prepared statements).
 * Режим превью: SQLite — тот же SQL, но с трансляцией нескольких
 * MySQL-конструкций, чтобы превью работало без установленного MySQL.
 *
 * Схема: v2/db/schema.mysql.sql и v2/db/schema.sqlite.sql.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';
    private static int $txLevel = 0;

    public static function boot(): void
    {
        if (self::$pdo !== null) {
            return;
        }

        $driver = strtolower(Config::get('DB_DRIVER', 'mysql'));

        if ($driver === 'sqlite') {
            $path = Config::get('DB_SQLITE_PATH');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            self::$pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
            self::$driver = 'sqlite';
            return;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Config::get('DB_HOST', '127.0.0.1'),
            Config::get('DB_PORT', '3306'),
            Config::get('DB_NAME', 'miniage')
        );

        try {
            self::$pdo = new PDO($dsn, Config::get('DB_USER'), Config::get('DB_PASS'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
            self::$driver = 'mysql';
        } catch (PDOException $e) {
            error_log('[miniage] MySQL недоступен: ' . $e->getMessage() . ' — переключаюсь на SQLite');
            Config::set('DB_DRIVER', 'sqlite');
            self::$pdo = null;
            self::boot();
        }
    }

    public static function driver(): string
    {
        self::boot();
        return self::$driver;
    }

    public static function pdo(): PDO
    {
        self::boot();
        assert(self::$pdo instanceof PDO);
        return self::$pdo;
    }

    /** @param array<string|int,mixed> $params */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $key = is_int($k) ? $k + 1 : $k;
            $type = match (true) {
                is_int($v)  => PDO::PARAM_INT,
                is_bool($v) => PDO::PARAM_BOOL,
                is_null($v) => PDO::PARAM_NULL,
                default     => PDO::PARAM_STR,
            };
            $stmt->bindValue($key, $v, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    /** @param array<string|int,mixed> $params */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param array<string|int,mixed> $params */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string|int,mixed> $params */
    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** @param array<string|int,mixed> $params */
    public static function exec(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    /** @param array<string,mixed> $data */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::table($table),
            implode(', ', array_map([self::class, 'quoteIdent'], $cols)),
            implode(', ', array_fill(0, count($cols), '?'))
        );
        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = implode(', ', array_map(fn ($c) => self::quoteIdent($c) . ' = ?', array_keys($data)));
        $sql = sprintf('UPDATE %s SET %s WHERE %s', self::table($table), $set, $where);
        return self::exec($sql, array_merge(array_values($data), $params));
    }

    public static function table(string $name): string
    {
        return self::quoteIdent(Config::get('DB_PREFIX', '') . $name);
    }

    public static function quoteIdent(string $ident): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $ident)) {
            throw new \InvalidArgumentException("Недопустимое имя поля/таблицы: {$ident}");
        }
        return self::$driver === 'mysql' ? "`{$ident}`" : '"' . $ident . '"';
    }

    /**
     * Транзакция с поддержкой вложенности (savepoint).
     *
     * БАГ (гонка при старте боя): на SQLite обычный BEGIN открывает
     * DEFERRED-транзакцию — блокировка записи берётся только на первом
     * INSERT/UPDATE. Два параллельных запроса успевают оба прочитать
     * «моб свободен» и только потом один из них получает SQLITE_BUSY —
     * либо, при неудачном тайминге, оба создают бой. BEGIN IMMEDIATE
     * захватывает блокировку записи сразу, поэтому проверка «моб занят»
     * и INSERT боя становятся атомарными относительно другого процесса.
     */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $root = self::$txLevel === 0;
        if ($root) {
            if (self::$driver === 'sqlite') {
                $pdo->exec('BEGIN IMMEDIATE');
            } else {
                $pdo->beginTransaction();
            }
        } else {
            $pdo->exec('SAVEPOINT sp' . self::$txLevel);
        }
        self::$txLevel++;
        try {
            $result = $fn();
            self::$txLevel--;
            if (self::$txLevel === 0) {
                // для SQLite COMMIT через exec: PDO::commit() не знает, что
                // транзакция открыта вручную оператором BEGIN
                if (self::$driver === 'sqlite') {
                    $pdo->exec('COMMIT');
                } else {
                    $pdo->commit();
                }
            } else {
                $pdo->exec('RELEASE SAVEPOINT sp' . self::$txLevel);
            }
            return $result;
        } catch (\Throwable $e) {
            self::$txLevel--;
            if (self::$txLevel === 0) {
                if (self::$driver === 'sqlite') {
                    // ROLLBACK при ошибке самого ROLLBACK не должен подменять
                    // исходное исключение
                    try {
                        $pdo->exec('ROLLBACK');
                    } catch (\Throwable) {
                    }
                } else {
                    $pdo->rollBack();
                }
            } else {
                $pdo->exec('ROLLBACK TO SAVEPOINT sp' . self::$txLevel);
            }
            throw $e;
        }
    }

    /**
     * Сбрасывает WAL в основной файл БД (только SQLite).
     *
     * В режиме WAL свежие коммиты живут в файле -wal, а основной файл
     * обновляется при закрытии соединения/чекпоинте. Preview-мост читает
     * основной файл, чтобы сохранить его на диск, поэтому без явного
     * чекпоинта последние изменения (регистрация, бой) не попадали в
     * снимок и терялись при рестарте сервера.
     */
    public static function checkpoint(): void
    {
        if (self::$pdo === null || self::$driver !== 'sqlite') {
            return;
        }
        try {
            self::$pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable $e) {
            error_log('[miniage] checkpoint: ' . $e->getMessage());
        }
    }

    /** NOW() одинаково в MySQL и SQLite — возвращает SQL-выражение текущего времени. */
    public static function nowExpr(): string
    {
        return self::$driver === 'mysql' ? 'NOW()' : "datetime('now')";
    }

    public static function reset(): void
    {
        self::$pdo = null;
        self::$driver = 'mysql';
        self::$txLevel = 0;
    }
}
