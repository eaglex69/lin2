<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Service\AuthService;
use App\Service\ChatService;
use App\Service\ExpTable;
use App\Service\GameService;
use App\Service\ShopService;

/**
 * Единая JSON-точка для AJAX и Canvas.
 *
 * Все ответы: {ok:true, ...} либо {ok:false, error, code} с HTTP-статусом.
 * POST требует CSRF-токен из /api/bootstrap.
 */
final class ApiController
{
    public function __construct(
        private AuthService $auth,
        private GameService $game,
        private ShopService $shop,
        private ChatService $chat,
    ) {
    }

    // ---------------------------------------------------------------- bootstrap
    public function bootstrap(Request $req): Response
    {
        $acc = $this->auth->account();
        $char = $this->auth->character();
        if ($char !== null) {
            $this->game->touchOnline((int) $char['id']);
        }

        return Response::ok([
            'app' => [
                'name' => 'Miniage',
                'version' => '2.0',
                'php' => PHP_VERSION,
                'db' => Database::driver(),
                'time' => date('c'),
            ],
            'csrf' => Session::csrfToken(),
            'session' => [
                'id' => Session::id(),
                'in_url' => Session::inUrl(),
            ],
            'account' => $acc === null ? null : [
                'id' => (int) $acc['id'],
                'nick' => $acc['nick'],
                'role' => $acc['role'],
            ],
            'character' => $char,
            'characters' => $acc === null ? [] : $this->auth->characters((int) $acc['id']),
            'zones' => $this->game->zones($char === null ? 1 : $char['level']),
            'online' => $this->game->onlineCount(),
            'races' => AuthService::RACES,
            'classes' => AuthService::CLASSES,
            'max_level' => ExpTable::maxLevel(),
        ]);
    }

    // -------------------------------------------------------------------- auth
    public function login(Request $req): Response
    {
        try {
            $data = $req->input();
            $res = $this->auth->login((string) ($data['nick'] ?? ''), (string) ($data['pass'] ?? ''));
            return Response::ok($res + [
                'csrf' => Session::csrfToken(),
                'session' => ['id' => Session::id(), 'in_url' => Session::inUrl()],
            ])->withHeader('X-Miniage-Session', Session::id());
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 401, 'auth_failed');
        }
    }

    public function register(Request $req): Response
    {
        try {
            $d = $req->input();
            $res = $this->auth->register(
                (string) ($d['nick'] ?? ''),
                (string) ($d['pass'] ?? ''),
                (string) ($d['email'] ?? ''),
                (string) ($d['race'] ?? 'human'),
                (string) ($d['class'] ?? 'fighter'),
                (string) ($d['gender'] ?? 'm'),
            );
            return Response::ok($res + [
                'csrf' => Session::csrfToken(),
                'session' => ['id' => Session::id(), 'in_url' => Session::inUrl()],
            ])->withHeader('X-Miniage-Session', Session::id());
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422, 'register_failed');
        }
    }

    public function logout(Request $req): Response
    {
        $this->auth->logout();
        return Response::ok();
    }

    public function selectCharacter(Request $req, array $params): Response
    {
        $acc = $this->auth->account();
        if ($acc === null) {
            return Response::error('Требуется вход.', 401, 'unauthorized');
        }
        $char = $this->auth->selectCharacter((int) $acc['id'], (int) $params['id']);
        if ($char === null) {
            return Response::error('Персонаж не принадлежит аккаунту.', 403, 'forbidden');
        }
        return Response::ok(['character' => $this->auth->decorate($char)]);
    }

    // -------------------------------------------------------------------- game
    public function zones(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        return Response::ok(['zones' => $this->game->zones((int) $char['level'])]);
    }

    public function zone(Request $req, array $params): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        $zone = (string) $params['zone'];
        if (!isset(GameService::ZONES[$zone])) {
            return Response::error('Неизвестная зона.', 404, 'zone_not_found');
        }
        $meta = GameService::ZONES[$zone];
        if ((int) $char['level'] < $meta['min_level']) {
            return Response::error("Зона откроется на {$meta['min_level']} уровне.", 403, 'level_locked');
        }
        return Response::ok([
            'zone' => ['key' => $zone, 'label' => $meta['label'], 'color' => $meta['color']],
            'mobs' => $this->game->mobsInZone($zone, (int) $char['id']),
            'character' => $char,
        ]);
    }

    public function battleStart(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $in = $req->input();
            $mobId = (int) ($in['mob_id'] ?? $req->get['mob_id'] ?? 0);
            $battle = $this->game->startBattle((int) $char['id'], $mobId);
            return Response::ok(['battle' => $battle, 'character' => $this->refreshChar((int) $char['id'])]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 409, 'battle_failed');
        }
    }

    public function battleAttack(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $battleId = (int) ($req->input()['battle_id'] ?? 0);
            $battle = $this->game->attack((int) $char['id'], $battleId);
            return Response::ok(['battle' => $battle, 'character' => $this->refreshChar((int) $char['id'])]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 409, 'attack_failed');
        }
    }

    public function battleFlee(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $battleId = (int) ($req->input()['battle_id'] ?? 0);
            $battle = $this->game->flee((int) $char['id'], $battleId);
            return Response::ok(['battle' => $battle, 'character' => $this->refreshChar((int) $char['id'])]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 409, 'flee_failed');
        }
    }

    public function battleCurrent(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        return Response::ok(['battle' => $this->game->activeBattle((int) $char['id']), 'character' => $char]);
    }

    public function move(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        $d = $req->input();
        $res = $this->game->move(
            (int) $char['id'],
            (int) ($d['x'] ?? 0),
            (int) ($d['y'] ?? 0),
            (string) ($d['city'] ?? 'Giran')
        );
        return Response::ok($res);
    }

    public function heal(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $res = $this->game->heal((int) $char['id']);
            return Response::ok(['cost' => $res['cost'], 'character' => $this->refreshChar((int) $char['id'])]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 409, 'heal_failed');
        }
    }

    // -------------------------------------------------------------------- shop
    public function shop(Request $req): Response
    {
        $kind = $req->get('kind', 'all');
        return Response::ok([
            'items' => $this->shop->catalog($kind),
            'character' => $this->auth->character(),
        ]);
    }

    public function buy(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $d = $req->input();
            $res = $this->shop->buy((int) $char['id'], (string) ($d['code'] ?? ''), (int) ($d['qty'] ?? 1));
            return Response::ok($res + ['character' => $this->refreshChar((int) $char['id'])]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 409, 'buy_failed');
        }
    }

    public function sell(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $d = $req->input();
            $res = $this->shop->sell((int) $char['id'], (int) ($d['inv_id'] ?? 0), (int) ($d['qty'] ?? 1));
            return Response::ok($res + ['character' => $this->refreshChar((int) $char['id'])]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 409, 'sell_failed');
        }
    }

    public function inventory(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        return Response::ok([
            'items' => $this->shop->inventory((int) $char['id']),
            'character' => $char,
        ]);
    }

    public function equip(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $d = $req->input();
            $res = $this->shop->equip((int) $char['id'], (int) ($d['inv_id'] ?? 0), (bool) ($d['equip'] ?? true));
            return Response::ok(['character' => $this->refreshChar((int) $char['id'])]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 409, 'equip_failed');
        }
    }

    public function useItem(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $d = $req->input();
            $res = $this->shop->useItem((int) $char['id'], (int) ($d['inv_id'] ?? 0));
            return Response::ok($res);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 409, 'use_failed');
        }
    }

    // -------------------------------------------------------------------- chat
    public function chatList(Request $req): Response
    {
        $channel = (string) $req->get('channel', 'global');
        $after = $req->int('after', 0);
        try {
            return Response::ok(['messages' => $this->chat->list($channel, $after)]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400, 'chat_channel');
        }
    }

    public function chatSend(Request $req): Response
    {
        $char = $this->requireChar();
        if ($char instanceof Response) {
            return $char;
        }
        try {
            $d = $req->input();
            $msg = $this->chat->post(
                (int) $char['id'],
                (string) $char['name'],
                (string) ($d['channel'] ?? 'global'),
                (string) ($d['text'] ?? '')
            );
            return Response::ok(['message' => $msg]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 429, 'chat_failed');
        }
    }

    // ------------------------------------------------------------------ прочее
    public function rating(Request $req): Response
    {
        $rows = Database::all(
            'SELECT name, level, exp, wins, losses, pvp, race, class FROM ' . Database::table('characters') . '
             ORDER BY level DESC, exp DESC LIMIT 50'
        );
        return Response::ok(['rating' => array_map(function (array $r) {
            return [
                'name' => $r['name'],
                'level' => (int) $r['level'],
                'exp' => (int) $r['exp'],
                'wins' => (int) $r['wins'],
                'losses' => (int) $r['losses'],
                'pvp' => (int) $r['pvp'],
                'race' => AuthService::RACES[$r['race']] ?? $r['race'],
                'class' => AuthService::CLASSES[$r['class']] ?? $r['class'],
            ];
        }, $rows)]);
    }

    public function news(Request $req): Response
    {
        $rows = Database::all('SELECT id, title, body, author, created_at FROM ' . Database::table('news') . ' ORDER BY id DESC LIMIT 10');
        return Response::ok(['news' => array_map(fn (array $n) => [
            'id' => (int) $n['id'],
            'title' => $n['title'],
            'body' => $n['body'],
            'author' => $n['author'],
            'date' => date('d.m.Y', strtotime((string) $n['created_at'])),
        ], $rows)]);
    }

    /** Ответ на health-check для мониторинга. */
    public function health(Request $req): Response
    {
        return Response::ok([
            'php' => PHP_VERSION,
            'db' => Database::driver(),
            'wasm' => defined('MINIAGE_WASM'),
            'time' => date('c'),
        ]);
    }

    // ------------------------------------------------------------------ helpers
    private function requireChar(): array|Response
    {
        $char = $this->auth->character();
        if ($char === null) {
            return Response::error('Нужно войти и выбрать персонажа.', 401, 'unauthorized');
        }
        return $char;
    }

    private function refreshChar(int $charId): ?array
    {
        $t = Database::table('characters');
        $c = Database::one("SELECT * FROM {$t} WHERE id = ?", [$charId]);
        return $c === null ? null : $this->auth->decorate($c);
    }
}
