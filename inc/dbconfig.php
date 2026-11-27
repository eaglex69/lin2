<?php
/**
 * Параметры подключения к MySQL.
 *
 * Источники (более поздний перекрывает более ранний):
 *   1. inc/config.php - файл создаёт установщик install.php (образец: inc/config.sample.php);
 *   2. переменные окружения LIN2_DB_HOST, LIN2_DB_USER, LIN2_DB_PASS, LIN2_DB_NAME
 *      (SetEnv в Apache, env[...] в php-fpm, export в cron).
 *
 * Значений «по умолчанию» нет: пока база не настроена, игра показывает страницу «игра не установлена»
 * (lin2_require_config()), а из командной строки (cron) завершается с понятным сообщением.
 */
if (!function_exists('lin2_db_config')) {

    /**
     * @return array host, user, pass, name, source ('none' | 'file' | 'env' | 'file+env'), configured (bool)
     */
    function lin2_db_config()
    {
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg;
        }
        $cfg = array('host' => 'localhost', 'user' => '', 'pass' => '', 'name' => '', 'source' => 'none', 'configured' => false);

        $file = dirname(__FILE__) . '/config.php';
        if (is_file($file)) {
            try {
                $c = include $file;
            } catch (Throwable $e) {
                $c = null;
                error_log('LIN2: inc/config.php: ' . $e->getMessage());
            }
            if (is_array($c)) {
                foreach (array('host', 'user', 'pass', 'name') as $k) {
                    if (isset($c['db_' . $k])) {
                        $cfg[$k] = (string) $c['db_' . $k];
                    }
                }
                $cfg['source'] = 'file';
            }
        }

        $env = array('host' => 'LIN2_DB_HOST', 'user' => 'LIN2_DB_USER', 'pass' => 'LIN2_DB_PASS', 'name' => 'LIN2_DB_NAME');
        foreach ($env as $k => $var) {
            $v = getenv($var);
            if ($v !== false) {
                $cfg[$k] = $v;
                $cfg['source'] = ($cfg['source'] === 'file' || $cfg['source'] === 'file+env') ? 'file+env' : 'env';
            }
        }

        $cfg['configured'] = ($cfg['user'] !== '' && $cfg['name'] !== '');
        return $cfg;
    }

    /**
     * Возвращает настройки базы; если их нет - выводит страницу «игра не установлена» и завершает работу.
     */
    function lin2_require_config()
    {
        $cfg = lin2_db_config();
        if ($cfg['configured']) {
            return $cfg;
        }

        $root = dirname(dirname(__FILE__));
        $hasInstaller = is_file($root . '/install.php');

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "Игра не установлена: задайте параметры базы в inc/config.php (образец - inc/config.sample.php) "
                . "или в переменных окружения LIN2_DB_HOST / LIN2_DB_USER / LIN2_DB_PASS / LIN2_DB_NAME"
                . ($hasInstaller ? ", либо откройте install.php в браузере" : "") . ".\n");
            exit(1);
        }

        // относительная ссылка на install.php из любого каталога игры (gm/, forum/, bonus/ ...)
        $href = 'install.php';
        if (!empty($_SERVER['SCRIPT_FILENAME'])) {
            $script = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME']));
            $base = str_replace('\\', '/', (string) realpath($root));
            if ($script !== '' && $base !== '' && strpos($script, $base . '/') === 0) {
                $href = str_repeat('../', substr_count(substr($script, strlen($base) + 1), '/')) . 'install.php';
            }
        }

        if (!headers_sent()) {
            http_response_code(503);
            header('Content-Type: text/html; charset=utf-8');
            header('Retry-After: 3600');
            header('Cache-Control: no-store');
        }
        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Игра не установлена</title></head>'
            . '<body style="font-family:sans-serif;max-width:34em;margin:2em auto;padding:0 1em;line-height:1.5">'
            . '<h2>Игра ещё не установлена</h2>';
        if ($hasInstaller) {
            echo '<p>Откройте установщик и следуйте подсказкам: <a href="' . htmlspecialchars($href, ENT_QUOTES) . '"><b>install.php</b></a>.</p>';
        } else {
            echo '<p>Параметры базы данных не заданы. Скопируйте <code>inc/config.sample.php</code> в <code>inc/config.php</code> '
                . 'и впишите в него данные своей базы MySQL (или задайте переменные окружения '
                . '<code>LIN2_DB_HOST</code>, <code>LIN2_DB_USER</code>, <code>LIN2_DB_PASS</code>, <code>LIN2_DB_NAME</code>).</p>';
        }
        echo '</body></html>';
        exit;
    }
}
