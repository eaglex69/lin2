<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Ответ HTTP. Один класс для HTML-страниц, JSON API и редиректов.
 */
final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(
        public string $body = '',
        public int $status = 200,
        public array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /** @param array<string,mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $body = json_encode($data, $flags);

        if ($body === false) {
            // Битые UTF-8 последовательности (например, обрезанный на середине
            // символа текст) роняли ответ в бесполезное {"error":"json_encode"}.
            // Пробуем «починить» данные, а затем — заменить невалидные байты.
            error_log('[miniage] json_encode: ' . json_last_error_msg());
            $clean = self::scrubUtf8($data);
            $body = json_encode($clean, $flags);
            if ($body === false) {
                $body = json_encode(
                    ['ok' => false, 'error' => 'Ошибка кодирования ответа', 'code' => 'encode'],
                    $flags | JSON_INVALID_UTF8_SUBSTITUTE
                );
                $status = 500;
            }
        }

        return new self(
            (string) $body,
            $status,
            ['Content-Type' => 'application/json; charset=utf-8', 'Cache-Control' => 'no-store']
        );
    }

    /**
     * Рекурсивно заменяет невалидные UTF-8 байты на символ замены,
     * чтобы json_encode мог собрать ответ.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function scrubUtf8(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[is_string($k) ? self::scrubString($k) : $k] = self::scrubUtf8($v);
            }
            return $out;
        }
        if (is_string($value)) {
            return self::scrubString($value);
        }
        return $value;
    }

    private static function scrubString(string $s): string
    {
        if (!function_exists('mb_check_encoding')) {
            // mbstring нет: используем json с подстановкой как быстрый фильтр
            $probe = json_encode($s, JSON_INVALID_UTF8_SUBSTITUTE);
            return is_string($probe) ? (string) json_decode($probe, true) : $s;
        }
        if (mb_check_encoding($s, 'UTF-8')) {
            return $s;
        }
        return (string) mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    }

    /** @param array<string,mixed> $data */
    public static function ok(array $data = []): self
    {
        return self::json(['ok' => true] + $data);
    }

    public static function error(string $message, int $status = 400, string $code = 'bad_request'): self
    {
        return self::json(['ok' => false, 'error' => $message, 'code' => $code], $status);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /** @param array<string,string> $headers */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        // HEAD: заголовки отдаём, тело — нет (стандарт HTTP)
        if ($this->body !== '' && (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD')) {
            echo $this->body;
        }
    }
}
