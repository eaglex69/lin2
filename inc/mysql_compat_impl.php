<?php
/**
 * mysql_* -> mysqli: реализация (класс Mysql_Compat и функции mysql_*()).
 * Не подключать напрямую - используйте inc/mysql_compat.php (там проверки и константы MYSQL_*).
 */

/**
 * Внутреннее состояние слоя совместимости. Напрямую не использовать.
 */
final class Mysql_Compat
{
    /** @var mysqli|null соединение по умолчанию (последнее открытое) */
    public static $default = null;

    /** @var array<string, mysqli> открытые соединения по ключу host|user|pass|flags */
    public static $pool = array();

    /** @var string текст последней ошибки подключения (для mysql_error() без соединения) */
    public static $connect_error = '';

    /** @var int код последней ошибки подключения */
    public static $connect_errno = 0;

    /** @var WeakMap|null закрытые соединения и освобождённые результаты (mysqli бросает Error при их использовании) */
    public static $closed = null;

    public static function markClosed($object)
    {
        if (self::$closed === null) {
            self::$closed = new WeakMap();
        }
        self::$closed[$object] = true;
    }

    public static function isClosed($object)
    {
        return self::$closed !== null && isset(self::$closed[$object]);
    }

    public static function warn($message)
    {
        trigger_error($message, E_USER_WARNING);
    }

    public static function typeName($value)
    {
        $t = gettype($value);
        return $t === 'boolean' ? 'bool' : ($t === 'integer' ? 'int' : ($t === 'double' ? 'float' : strtolower($t)));
    }

    /**
     * Соединение для вызова: $link === null -> по умолчанию, иначе должен быть результат mysql_connect().
     *
     * @return mysqli|null
     */
    public static function link($link, $fn)
    {
        if ($link === null) {
            if (self::$default === null) {
                self::warn("$fn(): A link to the server could not be established");
                return null;
            }
            return self::$default;
        }
        if ($link instanceof mysqli) {
            if (self::isClosed($link)) {
                self::warn("$fn(): supplied resource is not a valid MySQL-Link resource");
                return null;
            }
            return $link;
        }
        self::warn("$fn() expects parameter to be resource, " . self::typeName($link) . ' given');
        return null;
    }

    /**
     * @return mysqli_result|null
     */
    public static function result($result, $fn)
    {
        if ($result instanceof mysqli_result) {
            if (self::isClosed($result)) {
                self::warn("$fn(): supplied resource is not a valid MySQL result resource");
                return null;
            }
            return $result;
        }
        if ($result === true || $result === false) {
            self::warn("$fn(): supplied argument is not a valid MySQL result resource");
        } else {
            self::warn("$fn() expects parameter 1 to be resource, " . self::typeName($result) . ' given');
        }
        return null;
    }

    public static function connect($server, $username, $password, $new_link, $client_flags, $persistent, $fn)
    {
        $host = ($server === null || $server === '') ? null : (string) $server;
        $port = null;
        $socket = null;

        // "host:port" and "host:/path/to/socket" as in the old extension
        if ($host !== null) {
            if (preg_match('~^(.*):(\d+)$~', $host, $m) && substr_count($host, ':') === 1) {
                $host = $m[1];
                $port = (int) $m[2];
            } elseif (preg_match('~^(.*):(/.*)$~', $host, $m)) {
                $host = $m[1];
                $socket = $m[2];
            }
            if ($host === '') {
                $host = 'localhost';
            }
        }

        $flags = (int) $client_flags;
        $key = $host . ':' . $port . ':' . $socket . '|' . $username . '|' . $password . '|' . $flags . ($persistent ? '|p' : '');

        if (!$new_link && isset(self::$pool[$key])) {
            self::$default = self::$pool[$key];
            return self::$default;
        }

        $mysqli = mysqli_init();
        if ($mysqli === false) {
            self::$connect_errno = 2000;
            self::$connect_error = 'Unknown MySQL error';
            return false;
        }

        $connected = @mysqli_real_connect(
            $mysqli,
            ($persistent ? 'p:' . ($host !== null ? $host : (ini_get('mysqli.default_host') ?: 'localhost')) : $host),
            $username === null ? null : (string) $username,
            $password === null ? null : (string) $password,
            null,
            $port,
            $socket,
            $flags
        );

        if (!$connected) {
            self::$connect_errno = mysqli_connect_errno();
            self::$connect_error = (string) mysqli_connect_error();
            self::warn("$fn(): " . self::$connect_error);
            return false;
        }

        self::$connect_errno = 0;
        self::$connect_error = '';
        self::$pool[$key] = $mysqli;
        self::$default = $mysqli;
        return $mysqli;
    }

    /** Старый mysql_escape_string(): без соединения, эквивалент mysql_real_escape_string для однобайтных кодировок. */
    public static function escape($string)
    {
        return strtr((string) $string, array(
            "\0" => '\\0',
            "\n" => '\\n',
            "\r" => '\\r',
            '\\' => '\\\\',
            "'" => "\\'",
            '"' => '\\"',
            "\x1a" => '\\Z',
        ));
    }

    /** Название типа поля в стиле старого mysql_fetch_field() / mysql_field_type(). */
    public static function typeLabel($type)
    {
        switch ($type) {
            case MYSQLI_TYPE_TINY:
            case MYSQLI_TYPE_SHORT:
            case MYSQLI_TYPE_LONG:
            case MYSQLI_TYPE_INT24:
                return 'int';
            case MYSQLI_TYPE_LONGLONG:
                return 'int';
            case MYSQLI_TYPE_DECIMAL:
            case MYSQLI_TYPE_NEWDECIMAL:
            case MYSQLI_TYPE_FLOAT:
            case MYSQLI_TYPE_DOUBLE:
                return 'real';
            case MYSQLI_TYPE_TIMESTAMP:
                return 'timestamp';
            case MYSQLI_TYPE_DATE:
            case MYSQLI_TYPE_NEWDATE:
                return 'date';
            case MYSQLI_TYPE_TIME:
                return 'time';
            case MYSQLI_TYPE_DATETIME:
                return 'datetime';
            case MYSQLI_TYPE_YEAR:
                return 'year';
            case MYSQLI_TYPE_TINY_BLOB:
            case MYSQLI_TYPE_MEDIUM_BLOB:
            case MYSQLI_TYPE_LONG_BLOB:
            case MYSQLI_TYPE_BLOB:
                return 'blob';
            case MYSQLI_TYPE_NULL:
                return 'null';
            default:
                return 'string';
        }
    }

    /** @return object */
    public static function fieldObject($f)
    {
        $flags = (int) $f->flags;
        $o = new stdClass();
        $o->name = $f->name;
        $o->table = $f->table;
        $o->def = $f->def;
        $o->max_length = $f->max_length;
        $o->not_null = ($flags & MYSQLI_NOT_NULL_FLAG) ? 1 : 0;
        $o->primary_key = ($flags & MYSQLI_PRI_KEY_FLAG) ? 1 : 0;
        $o->multiple_key = ($flags & MYSQLI_MULTIPLE_KEY_FLAG) ? 1 : 0;
        $o->unique_key = ($flags & MYSQLI_UNIQUE_KEY_FLAG) ? 1 : 0;
        $o->numeric = ($flags & MYSQLI_NUM_FLAG) ? 1 : 0;
        $o->blob = ($flags & MYSQLI_BLOB_FLAG) ? 1 : 0;
        $o->type = self::typeLabel($f->type);
        $o->unsigned = ($flags & MYSQLI_UNSIGNED_FLAG) ? 1 : 0;
        $o->zerofill = ($flags & MYSQLI_ZEROFILL_FLAG) ? 1 : 0;
        return $o;
    }

    public static function close($link, $fn)
    {
        $l = self::link($link, $fn);
        if ($l === null) {
            return null;
        }
        try {
            $l->close();
        } catch (Throwable $e) {
            self::warn("$fn(): supplied resource is not a valid MySQL-Link resource");
            return false;
        }
        self::markClosed($l);
        foreach (self::$pool as $k => $v) {
            if ($v === $l) {
                unset(self::$pool[$k]);
            }
        }
        if (self::$default === $l) {
            self::$default = null;
        }
        return true;
    }
}

// ---------------------------------------------------------------------------------------------
// соединение
// ---------------------------------------------------------------------------------------------

function mysql_connect($server = null, $username = null, $password = null, $new_link = false, $client_flags = 0)
{
    return Mysql_Compat::connect($server, $username, $password, $new_link, $client_flags, false, __FUNCTION__);
}

function mysql_pconnect($server = null, $username = null, $password = null, $client_flags = 0)
{
    return Mysql_Compat::connect($server, $username, $password, false, $client_flags, true, __FUNCTION__);
}

function mysql_close($link_identifier = null)
{
    return Mysql_Compat::close($link_identifier, __FUNCTION__);
}

function mysql_select_db($database_name, $link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    if ($l === null) {
        return false;
    }
    try {
        return @mysqli_select_db($l, (string) $database_name);
    } catch (Throwable $e) {
        Mysql_Compat::warn(__FUNCTION__ . '(): ' . $e->getMessage());
        return false;
    }
}

function mysql_set_charset($charset, $link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? false : @mysqli_set_charset($l, (string) $charset);
}

function mysql_client_encoding($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? false : mysqli_character_set_name($l);
}

function mysql_ping($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    if ($l === null) {
        return false;
    }
    try {
        return @mysqli_query($l, 'DO 1') !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function mysql_stat($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? false : mysqli_stat($l);
}

function mysql_thread_id($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? false : mysqli_thread_id($l);
}

function mysql_get_server_info($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? false : mysqli_get_server_info($l);
}

function mysql_get_host_info($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? false : mysqli_get_host_info($l);
}

function mysql_get_proto_info($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? false : mysqli_get_proto_info($l);
}

function mysql_get_client_info()
{
    return mysqli_get_client_info();
}

// ---------------------------------------------------------------------------------------------
// запросы
// ---------------------------------------------------------------------------------------------

function mysql_query($query, $link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    if ($l === null) {
        return false;
    }
    if (is_array($query) || is_object($query)) {
        Mysql_Compat::warn(__FUNCTION__ . '() expects parameter 1 to be string, ' . Mysql_Compat::typeName($query) . ' given');
        return null;
    }
    try {
        return mysqli_query($l, (string) $query);
    } catch (Throwable $e) {
        Mysql_Compat::warn(__FUNCTION__ . '(): ' . $e->getMessage());
        return false;
    }
}

function mysql_unbuffered_query($query, $link_identifier = null)
{
    // отдельного небуферизованного режима нет: результат всегда буферизуется (как mysql_query)
    return mysql_query($query, $link_identifier);
}

function mysql_db_query($database_name, $query, $link_identifier = null)
{
    if (!mysql_select_db($database_name, $link_identifier)) {
        return false;
    }
    return mysql_query($query, $link_identifier);
}

function mysql_real_escape_string($unescaped_string, $link_identifier = null)
{
    if (is_array($unescaped_string) || is_object($unescaped_string)) {
        Mysql_Compat::warn(__FUNCTION__ . '() expects parameter 1 to be string, ' . Mysql_Compat::typeName($unescaped_string) . ' given');
        return null;
    }
    $l = $link_identifier === null ? Mysql_Compat::$default : $link_identifier;
    if ($l instanceof mysqli && !Mysql_Compat::isClosed($l)) {
        try {
            return mysqli_real_escape_string($l, (string) $unescaped_string);
        } catch (Throwable $e) {
            // закрытое соединение: экранируем вручную, чтобы не остаться без защиты
        }
    }
    return Mysql_Compat::escape($unescaped_string);
}

function mysql_escape_string($unescaped_string)
{
    return mysql_real_escape_string($unescaped_string);
}

function mysql_error($link_identifier = null)
{
    $l = $link_identifier === null ? Mysql_Compat::$default : $link_identifier;
    if ($l instanceof mysqli && !Mysql_Compat::isClosed($l)) {
        try {
            $e = mysqli_error($l);
            return $e !== '' ? $e : (string) Mysql_Compat::$connect_error;
        } catch (Throwable $e) {
            return '';
        }
    }
    return (string) Mysql_Compat::$connect_error;
}

function mysql_errno($link_identifier = null)
{
    $l = $link_identifier === null ? Mysql_Compat::$default : $link_identifier;
    if ($l instanceof mysqli && !Mysql_Compat::isClosed($l)) {
        try {
            $n = mysqli_errno($l);
            return $n ? $n : (int) Mysql_Compat::$connect_errno;
        } catch (Throwable $e) {
            return 0;
        }
    }
    return (int) Mysql_Compat::$connect_errno;
}

function mysql_affected_rows($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? -1 : mysqli_affected_rows($l);
}

function mysql_insert_id($link_identifier = null)
{
    $l = Mysql_Compat::link($link_identifier, __FUNCTION__);
    return $l === null ? false : mysqli_insert_id($l);
}

// ---------------------------------------------------------------------------------------------
// результат запроса
// ---------------------------------------------------------------------------------------------

function mysql_num_rows($result)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    return $r === null ? null : $r->num_rows;
}

/** Старый (недокументированный) псевдоним mysql_num_rows(); используется в bonus/bonus_bot.php. */
function mysql_numrows($result)
{
    return mysql_num_rows($result);
}

function mysql_num_fields($result)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    return $r === null ? null : $r->field_count;
}

function mysql_fetch_array($result, $result_type = MYSQL_BOTH)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    $type = (int) $result_type;
    if ($type !== MYSQL_ASSOC && $type !== MYSQL_NUM && $type !== MYSQL_BOTH) {
        Mysql_Compat::warn(__FUNCTION__ . '(): The result type should be either MYSQL_NUM, MYSQL_ASSOC or MYSQL_BOTH');
        $type = MYSQL_BOTH;
    }
    $row = $r->fetch_array($type);
    return $row === null ? false : $row;
}

function mysql_fetch_assoc($result)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    $row = $r->fetch_assoc();
    return $row === null ? false : $row;
}

function mysql_fetch_row($result)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    $row = $r->fetch_row();
    return $row === null ? false : $row;
}

function mysql_fetch_object($result, $class_name = 'stdClass', $params = array())
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    $row = $r->fetch_object((string) $class_name, (array) $params);
    return $row === null ? false : $row;
}

function mysql_fetch_lengths($result)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    return $r === null ? null : $r->lengths;
}

function mysql_fetch_field($result, $field_offset = null)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    if ($field_offset !== null) {
        $field_offset = (int) $field_offset;
        if ($field_offset < 0 || $field_offset >= $r->field_count) {
            Mysql_Compat::warn(__FUNCTION__ . '(): Field ' . $field_offset . ' is invalid for MySQL result index');
            return false;
        }
        $r->field_seek($field_offset);
    }
    $f = $r->fetch_field();
    return $f === false ? false : Mysql_Compat::fieldObject($f);
}

function mysql_field_seek($result, $field_offset)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    return $r === null ? false : $r->field_seek((int) $field_offset);
}

function mysql_field_name($result, $field_offset)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    $f = $r->fetch_field_direct((int) $field_offset);
    return $f === false ? false : $f->name;
}

function mysql_field_table($result, $field_offset)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    $f = $r->fetch_field_direct((int) $field_offset);
    return $f === false ? false : $f->table;
}

function mysql_field_len($result, $field_offset)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    $f = $r->fetch_field_direct((int) $field_offset);
    return $f === false ? false : $f->length;
}

function mysql_field_type($result, $field_offset)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return null;
    }
    $f = $r->fetch_field_direct((int) $field_offset);
    return $f === false ? false : Mysql_Compat::typeLabel($f->type);
}

function mysql_data_seek($result, $row_number)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return false;
    }
    $row_number = (int) $row_number;
    if ($row_number < 0 || $row_number >= $r->num_rows) {
        Mysql_Compat::warn(__FUNCTION__ . '(): Unable to jump to row ' . $row_number . ' on MySQL result index');
        return false;
    }
    return $r->data_seek($row_number);
}

/**
 * Значение одной ячейки: mysql_result($res, $row [, $field]), где $field - номер колонки,
 * имя колонки или "таблица.колонка". Типичное использование: mysql_result(mysql_query("SELECT COUNT(*) ..."), 0).
 */
function mysql_result($result, $row, $field = 0)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return false;
    }
    $row = (int) $row;
    if ($row < 0 || $row >= $r->num_rows) {
        Mysql_Compat::warn(__FUNCTION__ . '(): Unable to jump to row ' . $row . ' on MySQL result index');
        return false;
    }
    $r->data_seek($row);
    $data = $r->fetch_row();
    if (!is_array($data)) {
        return false;
    }

    if (is_int($field) || (is_string($field) && ctype_digit($field)) || is_float($field)) {
        $index = (int) $field;
        if (!array_key_exists($index, $data)) {
            Mysql_Compat::warn(__FUNCTION__ . '(): Bad column offset specified');
            return false;
        }
        return $data[$index];
    }

    $field = (string) $field;
    $table = null;
    if (($dot = strpos($field, '.')) !== false) {
        $table = substr($field, 0, $dot);
        $field = substr($field, $dot + 1);
    }
    foreach ($r->fetch_fields() as $i => $f) {
        if ($f->name === $field && ($table === null || $f->table === $table || $f->orgtable === $table)) {
            return $data[$i];
        }
    }
    Mysql_Compat::warn(__FUNCTION__ . '(): ' . ($table === null ? $field : $table . '.' . $field) . ' not found in MySQL result index');
    return false;
}

function mysql_free_result($result)
{
    $r = Mysql_Compat::result($result, __FUNCTION__);
    if ($r === null) {
        return false;
    }
    try {
        $r->free();
    } catch (Throwable $e) {
        return false;
    }
    Mysql_Compat::markClosed($r);
    return true;
}
