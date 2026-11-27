<?php
declare(strict_types=1);

/**
 * Miniage v2 — единая точка входа (front controller).
 * PHP 8.1+, PDO (MySQL 8 / SQLite), JSON API, Canvas-клиент.
 *
 * Apache: .htaccess перенаправляет всё сюда.
 * Nginx:  try_files $uri $uri/ /index.php?$query_string;
 * Встроенный сервер: php -S 0.0.0.0:8080 -t public
 */

defined('MINIAGE_START') || define('MINIAGE_START', microtime(true));
defined('MINIAGE_ROOT') || define('MINIAGE_ROOT', dirname(__DIR__));

// --- автозагрузка PSR-4: App\ -> src/
if (!isset($GLOBALS['__miniage_autoload_registered'])) {
$GLOBALS['__miniage_autoload_registered'] = true;
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = MINIAGE_ROOT . '/src/' . $rel . '.php';
    if (is_file($file)) {
        require $file;
    }
});
}

use App\Core\Config;
use App\Core\Database;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Controller\ApiController;
use App\Controller\PageController;
use App\Service\AuthService;
use App\Service\ChatService;
use App\Service\GameService;
use App\Service\ShopService;

// --- ошибки: в проде не показываем, но логируем
// В превью (php-wasm) процесс живёт между запросами, поэтому перечитываем
// конфигурацию и кэш опыта. Под FPM эти вызовы безвредны.
if (defined('MINIAGE_WASM')) {
    Config::reload();
    Database::reset();
    \App\Service\ExpTable::reset();
}
Config::load(MINIAGE_ROOT);
$debug = Config::get('APP_ENV') === 'development';
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

if (!isset($GLOBALS['__miniage_handler'])) {
$GLOBALS['__miniage_handler'] = true;
set_exception_handler(static function (Throwable $e) use ($debug): void {
    error_log('[miniage] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $resp = $debug
        ? Response::json(['ok' => false, 'error' => $e->getMessage(), 'trace' => explode("\n", $e->getTraceAsString())], 500)
        : Response::json(['ok' => false, 'error' => 'Внутренняя ошибка сервера', 'code' => 'internal'], 500);
    $resp->send();
});
}

// --- БД и схема (миграция идемпотентна)
Database::boot();
Migrator::migrate();

$req = Request::fromGlobals();
Session::resetState();   // важно в превью: процесс PHP переиспользуется
Session::start($req);

$auth = new AuthService();
$game = new GameService();
$shop = new ShopService();
$chat = new ChatService();
$api = new ApiController($auth, $game, $shop, $chat);
$pages = new PageController();

$router = new Router();

// --- middleware
$router->middleware('csrf', static function (Request $r): ?Response {
    if (!$r->isPost()) {
        return null;
    }
    $data = $r->input();
    $token = (string) ($data['_csrf'] ?? $r->header('X-CSRF-Token'));
    return Session::checkCsrf($token) ? null : Response::error('CSRF-токен недействителен. Обновите страницу.', 419, 'csrf');
});

// --- страницы
$router->get('/', fn (Request $r) => $pages->index($r));
$router->get('/play', fn (Request $r) => $pages->index($r));

// --- API: доступ без входа
$router->get('/api/health', fn (Request $r) => $api->health($r));
$router->get('/api/bootstrap', fn (Request $r) => $api->bootstrap($r));
$router->post('/api/login', fn (Request $r) => $api->login($r), ['csrf']);
$router->post('/api/register', fn (Request $r) => $api->register($r), ['csrf']);
$router->post('/api/logout', fn (Request $r) => $api->logout($r), ['csrf']);
$router->post('/api/characters/{id}/select', fn (Request $r, array $p) => $api->selectCharacter($r, $p), ['csrf']);

// --- API: игровые данные
$router->get('/api/zones', fn (Request $r) => $api->zones($r));
$router->get('/api/zones/{zone}', fn (Request $r, array $p) => $api->zone($r, $p));
$router->post('/api/battle/start', fn (Request $r) => $api->battleStart($r), ['csrf']);
$router->post('/api/battle/attack', fn (Request $r) => $api->battleAttack($r), ['csrf']);
$router->post('/api/battle/flee', fn (Request $r) => $api->battleFlee($r), ['csrf']);
$router->get('/api/battle/current', fn (Request $r) => $api->battleCurrent($r));
$router->post('/api/move', fn (Request $r) => $api->move($r), ['csrf']);
$router->post('/api/heal', fn (Request $r) => $api->heal($r), ['csrf']);

$router->get('/api/shop', fn (Request $r) => $api->shop($r));
$router->post('/api/shop/buy', fn (Request $r) => $api->buy($r), ['csrf']);
$router->post('/api/shop/sell', fn (Request $r) => $api->sell($r), ['csrf']);
$router->get('/api/inventory', fn (Request $r) => $api->inventory($r));
$router->post('/api/inventory/equip', fn (Request $r) => $api->equip($r), ['csrf']);
$router->post('/api/inventory/use', fn (Request $r) => $api->useItem($r), ['csrf']);

$router->get('/api/chat', fn (Request $r) => $api->chatList($r));
$router->post('/api/chat', fn (Request $r) => $api->chatSend($r), ['csrf']);
$router->get('/api/rating', fn (Request $r) => $api->rating($r));
$router->get('/api/news', fn (Request $r) => $api->news($r));

// --- старые URL: аккуратные редиректы вместо 404 (сохраняем совместимость ссылок)
foreach (['index.php', 'home.php', 'main.php', 'auto.php', 'portal.php', 'office.php', 'pers.php'] as $legacy) {
    $router->get('/' . $legacy, fn (Request $r) => Response::redirect('/'));
}
foreach (['gorod.php', 'okrestnosti.php', 'battle.php', 'fight.php'] as $legacy) {
    $router->get('/' . $legacy, fn (Request $r) => Response::redirect('/?panel=map'));
}
foreach (['shop.php', 'inventar.php', 'sklad.php'] as $legacy) {
    $router->get('/' . $legacy, fn (Request $r) => Response::redirect('/?panel=shop'));
}
foreach (['chat.php', 'msg.php'] as $legacy) {
    $router->get('/' . $legacy, fn (Request $r) => Response::redirect('/?panel=chat'));
}
foreach (['rating.php', 'online.php'] as $legacy) {
    $router->get('/' . $legacy, fn (Request $r) => Response::redirect('/?panel=top'));
}
foreach (['news.php', 'news_index.php'] as $legacy) {
    $router->get('/' . $legacy, fn (Request $r) => Response::redirect('/?panel=news'));
}

$response = $router->dispatch($req);
$response->send();
