<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Простой роутер: статические маршруты + параметры вида {id}.
 * Поддерживает middleware (аутентификация, CSRF, JSON-парсер).
 */
final class Router
{
    /** @var array<int,array{method:string,pattern:string,regex:string,handler:callable,mw:array<int,string>}> */
    private array $routes = [];

    /** @var array<string,callable(Request):(Response|null|void)> */
    private array $middleware = [];

    public function add(string $method, string $pattern, callable $handler, array $mw = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $this->toRegex($pattern),
            'handler' => $handler,
            'mw' => $mw,
        ];
    }

    public function get(string $pattern, callable $handler, array $mw = []): void
    {
        $this->add('GET', $pattern, $handler, $mw);
    }

    public function post(string $pattern, callable $handler, array $mw = []): void
    {
        $this->add('POST', $pattern, $handler, $mw);
    }

    public function middleware(string $name, callable $fn): void
    {
        $this->middleware[$name] = $fn;
    }

    private function toRegex(string $pattern): string
    {
        $regex = preg_replace('#\{([a-z_][a-z0-9_]*)\}#i', '(?P<$1>[^/]+)', $pattern) ?? $pattern;
        return '#^' . $regex . '$#u';
    }

    public function dispatch(Request $req): Response
    {
        $path = rtrim($req->path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $req->method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }

            $params = [];
            foreach ($m as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = $v;
                }
            }

            // middleware
            foreach ($route['mw'] as $name) {
                $fn = $this->middleware[$name] ?? null;
                if ($fn === null) {
                    throw new \RuntimeException("Неизвестный middleware: {$name}");
                }
                $result = $fn($req);
                if ($result instanceof Response) {
                    return $result;
                }
            }

            $result = ($route['handler'])($req, $params);
            return $result instanceof Response ? $result : new Response((string) $result);
        }

        // статические файлы отдаём как есть (Apache/Nginx обычно делают это раньше)
        if ($req->method === 'GET') {
            $file = dirname(__DIR__, 2) . '/public' . $path;
            if (is_file($file)) {
                return self::serveFile($file);
            }
        }

        if (str_starts_with($path, '/api/') || $req->isAjax()) {
            return Response::error('Маршрут не найден: ' . $path, 404, 'not_found');
        }

        return Response::html(self::notFoundPage(), 404);
    }

    private static function serveFile(string $file): Response
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'text/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff2' => 'font/woff2',
            default => 'application/octet-stream',
        };
        return new Response((string) file_get_contents($file), 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private static function notFoundPage(): string
    {
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>404 — Miniage</title>'
            . '<link rel="stylesheet" href="/assets/css/app.css"></head><body class="page-404">'
            . '<h1>404</h1><p>Страница не найдена.</p><p><a href="/">Вернуться в игру</a></p></body></html>';
    }
}
