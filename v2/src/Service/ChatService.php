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

    /** Каналы, которые попадают в общий поток (без служебного system). */
    public const PUBLIC_CHANNELS = ['global', 'trade', 'clan'];

    /** Максимальная длина сообщения в символах. */
    private const MAX_LEN = 240;

    /**
     * Обрезка строки до N символов UTF-8 без mbstring.
     *
     * Раньше здесь был substr()+обрезка хвостовых байт, и она ломала строку:
     * граница символа искалась от конца уже обрезанной строки, поэтому
     * последний символ оставался обрезанным, json_encode() падал с
     * «Malformed UTF-8», а пользователь получал {'ok':false,'error':'json_encode'}.
     * Теперь идём от начала и копим целые символы.
     */
    private static function limitUtf8(string $text, int $maxChars): string
    {
        $out = '';
        $chars = 0;
        $len = strlen($text);
        for ($i = 0; $i < $len && $chars < $maxChars; ) {
            $byte = ord($text[$i]);
            if ($byte < 0x80) {
                $size = 1;
            } elseif (($byte & 0xE0) === 0xC0) {
                $size = 2;
            } elseif (($byte & 0xF0) === 0xE0) {
                $size = 3;
            } elseif (($byte & 0xF8) === 0xF0) {
                $size = 4;
            } else {
                // битый байт — пропускаем, чтобы не тянуть мусор дальше
                $i++;
                continue;
            }
            if ($i + $size > $len) {
                break;
            }
            // проверяем, что продолжения действительно 10xxxxxx
            $valid = true;
            for ($k = 1; $k < $size; $k++) {
                if ((ord($text[$i + $k]) & 0xC0) !== 0x80) {
                    $valid = false;
                    break;
                }
            }
            if (!$valid) {
                $i++;
                continue;
            }
            $out .= substr($text, $i, $size);
            $i += $size;
            $chars++;
        }
        return $out;
    }

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
        // mbstring может отсутствовать (сборка php-wasm) — свой безопасный обрез.
        if (function_exists('mb_strlen')) {
            if (mb_strlen($text, 'UTF-8') > self::MAX_LEN) {
                $text = mb_substr($text, 0, self::MAX_LEN, 'UTF-8');
            }
        } else {
            $text = self::limitUtf8($text, self::MAX_LEN);
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
        // Неизвестный канал раньше молча возвращал пустой список — клиент
        // выглядел «пустым чатом» вместо ошибки.
        if ($channel !== 'all' && !in_array($channel, self::CHANNELS, true)) {
            throw new \InvalidArgumentException('Неизвестный канал чата.');
        }
        if ($afterId < 0) {
            $afterId = 0;
        }

        $t = Database::table('chat_messages');
        $limit = max(1, min(100, $limit));

        if ($channel === 'all') {
            // БАГ: канал all отдавал вообще всё, включая system. Служебные
            // записи (объявления сервера, события) не должны всплывать в общем
            // потоке — иначе игрок видит админские сообщения и путает их с
            // обычными. Отдаём только игровые каналы.
            $public = self::PUBLIC_CHANNELS;
            $in = implode(',', array_fill(0, count($public), '?'));
            $rows = Database::all(
                "SELECT * FROM {$t} WHERE id > ? AND channel IN ({$in}) ORDER BY id DESC LIMIT {$limit}",
                array_merge([$afterId], $public)
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
