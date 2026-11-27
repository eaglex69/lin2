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
        // Отсекаем гигантские тела до разбора JSON (иначе память/CPU зря).
        if ($req->isPost() && strlen($req->rawBody) > Request::MAX_BODY) {
            return Response::error('Слишком большой запрос.', 413, 'too_large');
        }

        $path = rtrim($req->path, '/');
        if ($path === '') {
            $path = '/';
        }

        // HEAD по стандарту обслуживается как GET, только без тела ответа.
        // Раньше HEAD / давал 404, что ломало мониторинг и кэширующие прокси.
        $method = $req->method === 'HEAD' ? 'GET' : $req->method;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
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

        // статические файлы отдаём как есть (Apache/Nginx обычно делают это раньше).
        // SECURITY: путь нормализуем и жёстко ограничиваем каталогом /public —
        // иначе /../src/Core/Config.php отдаёт исходники, а /../var/*.sqlite — базу.
        if ($method === 'GET') {
            $file = self::resolveStatic($path);
            if ($file !== null) {
                return self::serveFile($file);
            }
        }

        if (str_starts_with($path, '/api/') || $req->isAjax()) {
            // не отражаем пользовательский путь: это вектор отражённого XSS
            return Response::error('Маршрут не найден.', 404, 'not_found');
        }

        return Response::html(self::notFoundPage(), 404);
    }

    /** Разрешённые к отдаче расширения статики. */
    private const STATIC_EXT = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff2', 'webp'];

    /**
     * Возвращает реальный путь к статическому файлу или null.
     *
     * Защита от path traversal: отбрасываем «..», проверяем, что итоговый
     * realpath лежит внутри public/, и что расширение в белом списке.
     */
    private static function resolveStatic(string $path): ?string
    {
        if ($path === '/' || str_contains($path, "\0")) {
            return null;
        }
        // декодируем %2e%2e и т.п., чтобы обход не проскочил в закодированном виде
        $decoded = rawurldecode($path);
        if (str_contains($decoded, '..') || str_contains($decoded, "\0")) {
            return null;
        }

        $publicDir = realpath(dirname(__DIR__, 2) . '/public');
        if ($publicDir === false) {
            return null;
        }
        $candidate = realpath($publicDir . '/' . ltrim($decoded, '/'));
        if ($candidate === false || !is_file($candidate)) {
            return null;
        }
        // файл обязан находиться внутри public/
        if (!str_starts_with($candidate, $publicDir . DIRECTORY_SEPARATOR)) {
            return null;
        }
        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        if (!in_array($ext, self::STATIC_EXT, true)) {
            return null;
        }
        return $candidate;
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
