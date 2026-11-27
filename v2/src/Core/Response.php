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
        return new self(
            $body === false ? '{"ok":false,"error":"json_encode"}' : $body,
            $status,
            ['Content-Type' => 'application/json; charset=utf-8', 'Cache-Control' => 'no-store']
        );
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
        if ($this->body !== '') {
            echo $this->body;
        }
    }
}
