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

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($this->server[$key] ?? '');
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
