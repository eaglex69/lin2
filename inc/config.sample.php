<?php
/**
 * Образец файла inc/config.php (параметры подключения к MySQL).
 *
 * Обычно inc/config.php создаёт установщик install.php. Вручную:
 *   1. скопируйте этот файл в inc/config.php;
 *   2. впишите данные своей базы MySQL.
 *
 * inc/config.php не попадает в git (см. .gitignore): не публикуйте его - в нём пароль от базы.
 * Те же значения можно задать переменными окружения LIN2_DB_HOST / LIN2_DB_USER / LIN2_DB_PASS / LIN2_DB_NAME,
 * они перекрывают этот файл.
 */
return array(
    // сервер MySQL; с портом - 'localhost:3307', с сокетом - 'localhost:/var/run/mysqld/mysqld.sock'
    'db_host' => 'localhost',
    'db_user' => 'game_user',
    'db_pass' => 'secret',
    'db_name' => 'game_db',
);
