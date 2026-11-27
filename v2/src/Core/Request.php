<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Абстракция HTTP-запроса.
 *
 * Работает и под обычным Apache/Nginx+PHP-FPM, и под preview-мостом
 * (php-wasm), который создаёт объект из данных Node.js-сервера.
 */
final class Request
{
    /** @param array<string,mixed> $get */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $get = [],
        public readonly array $post = [],
        public readonly array $cookies = [],
        public readonly array $files = [],
        public readonly string $rawBody = '',
        public readonly array $server = [],
    ) {
    }

    /** Максимальный размер тела запроса (защита от DoS). */
    public const MAX_BODY = 1048576; // 1 MiB

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // preview-мост передаёт тело запроса через глобал (php://input в WASM пуст)
        $raw = $GLOBALS['MINIAGE_RAW_BODY'] ?? (string) file_get_contents('php://input');

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET ?? [],
            $_POST ?? [],
            $_COOKIE ?? [],
            $_FILES ?? [],
            $raw,
            $_SERVER ?? [],
        );
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isAjax(): bool
    {
        $x = $this->server['HTTP_X_REQUESTED_WITH'] ?? '';
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return strtolower($x) === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $v = $this->get[$key] ?? $this->post[$key] ?? $default;
        return is_scalar($v) ? (string) $v : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->get($key);
        return $v === null || $v === '' ? $default : (int) $v;
    }

    /**
     * Единый вход для данных запроса: JSON-тело + поля формы.
     * Порядок: JSON перекрывает форму (фронтенд всегда шлёт JSON).
     *
     * @return array<string,mixed>
     */
    public function input(): array
    {
        $data = $this->post;
        $json = $this->json();
        if ($json !== []) {
            $data = array_merge($data, $json);
        }
        return $data;
    }

    /** @return array<string,mixed> */
    public function json(): array
    {
        $raw = $this->rawBody;
        // Мост превью кладёт тело в глобал: php://input в WASM недоступен
        if ($raw === '' && isset($GLOBALS['MINIAGE_RAW_BODY'])) {
            $raw = (string) $GLOBALS['MINIAGE_RAW_BODY'];
        }
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Безопасно достаёт строку из входных данных.
     *
     * (string) от массива даёт «Array» с warning — в чат попадало слово
     * "Array" при text={...}. Здесь не-скаляры превращаются в пустую строку.
     */
    public function str(string $key, string $default = ''): string
    {
        $v = $this->input()[$key] ?? null;
        if ($v === null) {
            return $default;
        }
        return is_scalar($v) ? (string) $v : $default;
    }

    /** Безопасно достаёт int из входных данных (массивы -> 0). */
    public function intInput(string $key, int $default = 0): int
    {
        $v = $this->input()[$key] ?? null;
        if ($v === null) {
            return $default;
        }
        if (is_int($v)) {
            return $v;
        }
        if (is_float($v)) {
            return (int) $v;
        }
        if (is_string($v) && preg_match('/^-?\d+$/', trim($v))) {
            return (int) trim($v);
        }
        return $default;
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($this->server[$key] ?? '');
    }

    /**
     * IP текущего запроса без создания объекта Request.
     *
     * Нужен сервисам (антиспам регистрации), которые не получают Request:
     * `new Request('GET','/')` всегда давал бы 127.0.0.1 и один общий лимит
     * на всех.
     */
    /**
     * IP клиента для лимитов (регистрация, антиспам, журнал входов).
     *
     * БАГ: X-Forwarded-For принимался БЕЗУСЛОВНО, первым значением. Любой
     * клиент подставлял заголовок и получал новый «IP» на каждый запрос —
     * проверено: health отдавал 1.2.3.4 и 9.9.9.9 по желанию. Это ломало
     * лимит регистраций (5 в час с адреса) и журнал входов: перебор шёл
     * с «разных» адресов.
     *
     * Теперь XFF читается только если запрос пришёл от доверенного прокси
     * (список в конфиге TRUSTED_PROXIES, по умолчанию пусто — значит
     * заголовок игнорируется и берётся REMOTE_ADDR).
     */
    public static function currentIp(): string
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        $fwd = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($fwd !== '' && self::isTrustedProxy($remote)) {
            $first = trim(explode(',', $fwd)[0]);
            // валидируем: мусор в заголовке не должен становиться «IP»
            if (filter_var($first, FILTER_VALIDATE_IP) !== false) {
                return $first;
            }
        }
        return $remote;
    }

    /** Доверенный ли прокси-адрес (список в конфиге, через запятую). */
    private static function isTrustedProxy(string $remote): bool
    {
        $list = Config::get('TRUSTED_PROXIES', '');
        if ($list === '') {
            return false;
        }
        foreach (explode(',', $list) as $entry) {
            $entry = trim($entry);
            if ($entry !== '' && $entry === $remote) {
                return true;
            }
        }
        return false;
    }

    public function ip(): string
    {
        $fwd = $this->header('X-Forwarded-For');
        if ($fwd !== '') {
            return trim(explode(',', $fwd)[0]);
        }
        return (string) ($this->server['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function userAgent(): string
    {
        return $this->header('User-Agent');
    }
}
