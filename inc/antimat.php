<?php
/**
 * Фильтрация чата: антиреклама сайтов/ссылок и антимат (UTF-8).
 */

if (!function_exists('lin2_anti_ads')) {

function lin2_anti_ads($string)
{
    $s = (string)$string;
    if ($s === '') {
        return '';
    }

    $ownHost = '';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $ownHost = strtolower(preg_replace('~:\d+$~', '', trim((string)$_SERVER['HTTP_HOST'])));
    }

    $tlds = 'ru|su|рф|com|net|org|info|biz|mobi|ua|by|kz|tk|ml|ga|cf|gq|xyz|top|online|site|club|fun|io|me|cc|tv|gg|pro|pw|ws|co|uk|de|name|space|store|app|dev|live|games|game';

    // 1. Явные ссылки с протоколом или www.
    $s = preg_replace_callback(
        '~\b(?:(?:https?|ftp)://|www\.)[^\s<>"{}|\\^`\[\]]+~iu',
        function ($m) use ($ownHost) {
            if ($ownHost !== '' && stripos($m[0], $ownHost) !== false) {
                return $m[0];
            }
            return '[реклама запрещена]';
        },
        $s
    );

    // 2. IPv4-адреса серверов (например 185.12.34.56 или 185.12.34.56:7777)
    $s = preg_replace('~(?<![0-9.])(?:\d{1,3}\.){3}\d{1,3}(?::\d{2,5})?(?![0-9.])~u', '[реклама запрещена]', $s);

    // 3. Разрядка буквами с пробелами вида: s i t e . r u, l 2 w a r . m o b i
    $s = preg_replace(
        '~(?<![\p{L}\p{N}])(?:[\p{L}\p{N}]\s+){2,}[\p{L}\p{N}]\s*(?:\.|,|\(\s*точка\s*\)|\[\s*dot\s*\]|\bточка\b)\s*(?:r\s*u|р\s*ф|c\s*o\s*m|n\s*e\s*t|o\s*r\s*g|m\s*o\s*b\s*i|s\s*u|t\s*k|u\s*a|x\s*y\s*z|t\s*o\s*p|f\s*u\s*n|p\s*r\s*o|i\s*n\s*f\s*o)(?![\p{L}\p{N}])~iu',
        '[реклама запрещена]',
        $s
    );

    // 4. Обычные и замаскированные домены: example.ru, site . com, site(точка)рф, t.me/xxx, vk.com/xxx
    $s = preg_replace_callback(
        '~(?<![\p{L}\p{N}@])([a-zа-яё0-9](?:[a-zа-яё0-9-]{0,61}[a-zа-яё0-9])?)(?:\s*(?:\.|(?:\(\s*(?:точка|dot)\s*\))|(?:\[\s*(?:точка|dot)\s*\])|(?:\s+точка\s+))\s*)(' . $tlds . ')(?:/[^\s<>"]*)?(?![\p{L}\p{N}])~iu',
        function ($m) use ($ownHost) {
            $candidate = mb_strtolower($m[1] . '.' . $m[2], 'UTF-8');
            if ($ownHost !== '' && $candidate === $ownHost) {
                return $m[0];
            }
            // Не трогаем чисто числовые дроби/цены, если вдруг попали (TLD всегда буквенный, но на всякий случай)
            return '[реклама запрещена]';
        },
        $s
    );

    return $s;
}

}

if (!function_exists('antimat')) {

function antimat($string)
{
    $s = (string)$string;
    if ($s === '') {
        return '';
    }

    // Символы-разделители внутри одного слова без пробелов (х.у.й, п_и_з_д), чтобы не склеивать соседние обычные слова («у её брата»)
    $sep = '[._\-*`\'\\~]*';

    // Набор точных корней (с поддержкой латинских/цифровых замен букв-двойников)
    $L = array(
        'а' => '[аa@]+',
        'б' => '[бb6]+',
        'в' => '[вb]+',
        'г' => '[гg]+',
        'д' => '[дd]+',
        'е' => '[еёe]+',
        'ж' => '[ж]+',
        'з' => '[зz3]+',
        'и' => '[ийiu1]+',
        'к' => '[кk]+',
        'л' => '[лl]+',
        'м' => '[мm]+',
        'н' => '[нnh]+',
        'о' => '[оo0]+',
        'п' => '[пnp]+',
        'р' => '[рp]+',
        'с' => '[сcs$]+',
        'т' => '[тt]+',
        'у' => '[уy]+',
        'ф' => '[фf]+',
        'х' => '[хx]+',
        'ц' => '[ц]+',
        'ч' => '[ч4]+',
        'ш' => '[шщw]+',
        'ы' => '[ыi]+',
        'ь' => '[ьъb]*',
        'ю' => '(?:[ю]|yu|iu)+',
        'я' => '(?:[я]|ya|ia)+',
    );

    $build = function ($word) use ($L, $sep) {
        $len = mb_strlen($word, 'UTF-8');
        $parts = array();
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($word, $i, 1, 'UTF-8');
            $parts[] = isset($L[$ch]) ? $L[$ch] : preg_quote($ch, '~');
        }
        return implode($sep, $parts);
    };

    // 1. Безусловные матерные основы (слова целиком с любыми приставками/окончаниями, кроме безопасных исключений)
    $strongStems = array(
        'хуй', 'хуя', 'хуе', 'хуи', 'хули', 'охуе', 'ахуе', 'нихуя', 'похуй', 'нахуй',
        'пизд', 'пезд',
        'бляд', 'блят',
        'ебат', 'ебал', 'ебан', 'ебаш', 'ебис', 'ебну', 'ебло', 'ебли', 'уеб', 'заеб', 'выеб', 'отъеб', 'отьеб', 'разъеб', 'долбоеб',
        'пидор', 'педик', 'пидр',
        'гандон', 'гондон',
        'мудак', 'мудил', 'мудозвон',
        'залуп', 'шлюх', 'шалав', 'дрочи', 'драчи', 'сучк', 'сцук',
    );

    foreach ($strongStems as $stem) {
        $pat = '~(?<![\p{L}])[\p{L}]*?' . $build($stem) . '[\p{L}]*~iu';
        $s = preg_replace_callback($pat, function ($m) {
            $low = mb_strtolower($m[0], 'UTF-8');
            // Исключения для обычных литературных слов
            $safe = array('оскорблять', 'оскорбление', 'оскорблял', 'оскорбляла', 'оскорбляют', 'влюбляться', 'расслаблять', 'употреблять', 'углублять', 'истреблять', 'скипидар', 'мандарин', 'команда', 'страховка', 'подстрахуй', 'застрахуй', 'страхуй');
            foreach ($safe as $ok) {
                if (mb_strpos($low, $ok, 0, 'UTF-8') !== false) {
                    return $m[0];
                }
            }
            return '***';
        }, $s);
    }

    // 2. Слова «пидар» (не задевая «скипидар»)
    $s = preg_replace('~(?<![\p{L}])(?!скипидар)' . $build('пидар') . '[\p{L}]*~iu', '***', $s);

    // 3. Отдельно стоящие короткие бранные слова (строго по границам слов, чтобы не трогать «сабля», «рубля», «тебя», «хлеб», «плохо», «парикмахер»)
    $exactWords = array(
        'бля', 'блять', 'сука', 'суки', 'суку', 'сукой',
        'еб', 'ёб', 'ебу', 'еби', 'ипал',
        'хер', 'нахер', 'похер', 'пох', 'нах',
        'чмо', 'лох', 'жопа', 'жопу', 'жопе', 'жопой',
        'манда', 'манду',
        'fuck', 'bitch', 'shit', 'dick', 'cunt', 'whore', 'slut',
    );
    foreach ($exactWords as $ew) {
        $pat = '~(?<![\p{L}\p{N}])' . $build($ew) . '(?![\p{L}\p{N}])~iu';
        $s = preg_replace($pat, '***', $s);
    }

    // 4. Разрядка мата одиночными буквами через пробел («х у й», «с у к а», «п и з д а», «б л я»)
    $spacedWords = array('хуй', 'хуя', 'пизда', 'пиздец', 'сука', 'бля', 'блять', 'блядь', 'ебать', 'пидор', 'пидар', 'гандон');
    foreach ($spacedWords as $sw) {
        $len = mb_strlen($sw, 'UTF-8');
        $sp = array();
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($sw, $i, 1, 'UTF-8');
            $sp[] = isset($L[$ch]) ? $L[$ch] : preg_quote($ch, '~');
        }
        $s = preg_replace('~(?<![\p{L}\p{N}])' . implode('\s+', $sp) . '(?![\p{L}\p{N}])~iu', '***', $s);
    }

    return $s;
}

}

if (!function_exists('lin2_chat_filter')) {

function lin2_chat_filter($string)
{
    $s = (string)$string;
    $s = lin2_anti_ads($s);
    $s = antimat($s);
    return $s;
}

}
