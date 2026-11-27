<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;

/**
 * Чат (legacy chat.php) — AJAX-поллинг, каналы global/trade/clan/system.
 * Сообщения чистятся от HTML, длина ограничена, есть антифлуд.
 */
final class ChatService
{
    public const CHANNELS = ['global', 'trade', 'clan', 'system'];

    public function post(int $charId, string $author, string $channel, string $text): array
    {
        $channel = in_array($channel, self::CHANNELS, true) ? $channel : 'global';
        if ($channel === 'system' && !$this->isAdmin($charId)) {
            $channel = 'global';
        }

        // strip_tags + удаление «висящих» угловых скобок и управляющих символов:
        // defence in depth к экранированию на клиенте (esc()).
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
        $text = str_replace(['<', '>'], '', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            throw new \InvalidArgumentException('Сообщение пустое.');
        }
        // mbstring может отсутствовать (сборка php-wasm) — безопасный fallback
        if (function_exists('mb_strlen')) {
            if (mb_strlen($text, 'UTF-8') > 240) {
                $text = mb_substr($text, 0, 240, 'UTF-8');
            }
        } else {
            if (strlen($text) > 240) {
                $text = substr($text, 0, 240);
                // не рвём многобайтовый символ
                while ($text !== '' && (ord($text[strlen($text) - 1]) & 0xC0) === 0x80) {
                    $text = substr($text, 0, -1);
                }
                $text = rtrim($text);
            }
        }

        $t = Database::table('chat_messages');
        $last = Database::one("SELECT created_at FROM {$t} WHERE char_id = ? ORDER BY id DESC LIMIT 1", [$charId]);
        if ($last !== null) {
            $diff = time() - strtotime((string) $last['created_at']);
            if ($diff < 2) {
                throw new \InvalidArgumentException('Слишком часто. Подождите пару секунд.');
            }
        }

        $id = Database::insert('chat_messages', [
            'channel' => $channel,
            'char_id' => $charId,
            'author' => $author,
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->message($id);
    }

    public function message(int $id): array
    {
        $t = Database::table('chat_messages');
        $row = Database::one("SELECT * FROM {$t} WHERE id = ?", [$id]);
        return $this->decorate($row ?? []);
    }

    /** @return array<int,array<string,mixed>> */
    public function list(string $channel = 'global', int $afterId = 0, int $limit = 50): array
    {
        $t = Database::table('chat_messages');
        $limit = max(1, min(100, $limit));

        if ($channel === 'all') {
            $rows = Database::all(
                "SELECT * FROM {$t} WHERE id > ? ORDER BY id DESC LIMIT {$limit}",
                [$afterId]
            );
        } else {
            $rows = Database::all(
                "SELECT * FROM {$t} WHERE channel = ? AND id > ? ORDER BY id DESC LIMIT {$limit}",
                [$channel, $afterId]
            );
        }
        $rows = array_reverse($rows);
        return array_map([$this, 'decorate'], $rows);
    }

    /** @param array<string,mixed> $row */
    private function decorate(array $row): array
    {
        if ($row === []) {
            return [];
        }
        return [
            'id' => (int) $row['id'],
            'channel' => (string) $row['channel'],
            'author' => (string) $row['author'],
            'text' => (string) $row['text'],
            'time' => date('H:i', strtotime((string) $row['created_at'])),
            'ts' => (int) strtotime((string) $row['created_at']),
        ];
    }

    private function isAdmin(int $charId): bool
    {
        $role = Database::value(
            'SELECT a.role FROM ' . Database::table('accounts') . ' a
             JOIN ' . Database::table('characters') . ' c ON c.account_id = a.id WHERE c.id = ?',
            [$charId]
        );
        return $role === 'admin' || $role === 'gm';
    }
}
