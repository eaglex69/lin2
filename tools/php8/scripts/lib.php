<?php
// Shared helpers for the PHP 8 migration tooling (runs inside php-wasm 8.x).
// Operates on raw bytes so that cp1251/utf8/BOM/CRLF are preserved exactly.

const PHP8_WATCH = [
    'count','sizeof','implode','join','explode','mt_rand','rand','array_rand','max','min','str_repeat',
    'in_array','array_merge','array_sum','array_combine','array_fill','range','number_format','round',
    'floor','ceil','abs','mktime','strtotime','substr_count','str_pad','array_search','array_keys',
    'array_values','array_unique','array_slice','array_splice','array_flip','array_map','array_filter',
    'array_walk','array_key_exists','key_exists','is_resource','crypt','parse_str','extract','setcookie',
    'intdiv','fmod','pow','sqrt','log','array_pop','array_shift','array_push','array_unshift','end','reset',
    'current','key','next','prev','sort','rsort','usort','uasort','uksort','ksort','krsort','asort','arsort',
    'array_multisort','array_diff','array_intersect','array_reverse','array_chunk','array_pad','natsort',
    'array_count_values','array_product','str_split','wordwrap','chunk_split','strrev','strtr','money_format',
    'utf8_encode','utf8_decode','strftime','gmstrftime','date_default_timezone_get','mb_substr','mb_strlen',
    'iconv','mb_convert_encoding','htmlentities','html_entity_decode','htmlspecialchars_decode','sprintf','printf',
    'setlocale','each','create_function','file','fopen','fwrite','fread','file_get_contents','file_put_contents',
    'unlink','mkdir','opendir','readdir','scandir','glob','is_dir','is_file','filesize','filemtime','move_uploaded_file',
    'getimagesize','imagecreatefromjpeg','imagecreatefromgif','imagecreatefrompng','imagecreatetruecolor','imagecreate',
    'imagettftext','imagepng','imagejpeg','imagegif','imagedestroy','imagecolorallocate','imagecopyresampled',
    'session_start','session_destroy','session_unset','session_id','session_name','session_regenerate_id',
    'ob_start','ob_get_clean','ob_end_clean','ob_get_contents','flush','header','headers_sent','mail','curl_init',
    'curl_exec','curl_setopt','get_headers','fsockopen','stream_context_create','simplexml_load_string','json_decode',
    'json_encode','serialize','unserialize','call_user_func','call_user_func_array','func_get_args','is_callable',
    'array_key_last','array_key_first','ctype_digit','ctype_alpha','ctype_alnum','filter_var','get_class','method_exists',
    'property_exists','settype','gettype','intval','floatval','doubleval','strval','boolval','is_numeric','ini_set',
    'set_time_limit','error_reporting','set_error_handler','assert','eval','trigger_error','shuffle','array_rand',
    'random_int','mt_srand','srand','lcg_value','uniqid','hash','md5','sha1','base64_encode','base64_decode',
    'urlencode','rawurlencode','urldecode','nl2br','strip_tags','addslashes','stripslashes','trim','ltrim','rtrim',
    'substr','strpos','stripos','strrpos','strstr','stristr','strrchr','str_replace','str_ireplace','substr_replace',
    'ucfirst','ucwords','strtolower','strtoupper','lcfirst','str_word_count','similar_text','levenshtein','soundex',
    'metaphone','number_format','date','gmdate','checkdate','time','microtime','usleep','sleep','date_create',
];

function php8_list_files(string $dir, array $skipDirs = ['.git', 'node_modules', 'tools/php8']): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        foreach ($skipDirs as $s) {
            if (strpos($p, DIRECTORY_SEPARATOR . $s . DIRECTORY_SEPARATOR) !== false) continue 2;
        }
        if (preg_match('/\.php$/i', $p)) $out[] = $p;
    }
    sort($out);
    return $out;
}

/** Normalise token_get_all() output to [id|null, text, line, byteOffset]. */
function php8_tokens(string $code): array
{
    $toks = [];
    $off = 0; $line = 1;
    foreach (token_get_all($code) as $t) {
        if (is_array($t)) { $id = $t[0]; $text = $t[1]; }
        else { $id = null; $text = $t; }
        $toks[] = [$id, $text, $line, $off];
        $off += strlen($text);
        $line += substr_count($text, "\n");
    }
    return $toks;
}

function php8_is_ws(array $t): bool
{
    return $t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT;
}

function php8_prev_sig(array $toks, int $i): int
{
    for ($j = $i - 1; $j >= 0; $j--) if (!php8_is_ws($toks[$j])) return $j;
    return -1;
}

function php8_next_sig(array $toks, int $i): int
{
    $n = count($toks);
    for ($j = $i + 1; $j < $n; $j++) if (!php8_is_ws($toks[$j])) return $j;
    return -1;
}

/** Tokens that make a following T_STRING a declaration/member name rather than a constant reference. */
function php8_name_prefix_ids(): array
{
    static $ids = null;
    if ($ids === null) {
        $ids = [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_CONST, T_CLASS, T_INTERFACE, T_TRAIT,
                T_EXTENDS, T_IMPLEMENTS, T_NEW, T_INSTANCEOF, T_USE, T_NAMESPACE, T_GOTO, T_INSTEADOF];
        if (defined('T_NULLSAFE_OBJECT_OPERATOR')) $ids[] = T_NULLSAFE_OBJECT_OPERATOR;
        if (defined('T_ENUM')) $ids[] = T_ENUM;
    }
    return $ids;
}

/**
 * For each token return the lexical context it lives in:
 *   'code'  – ordinary PHP code
 *   'dq'    – literal part of a double-quoted string / heredoc / backtick string (simple interpolation zone)
 *   'curly' – PHP expression inside "{$...}" / "${...}" within a string
 */
function php8_ctx_map(array $toks): array
{
    $map = [];
    $ctx = ['code'];
    $depth = [0];
    foreach ($toks as $i => [$id, $text]) {
        $top = end($ctx);
        if ($id === null && ($text === '"' || $text === '`')) {
            if ($top === 'dq') { $map[$i] = 'dq'; array_pop($ctx); array_pop($depth); }
            else { $map[$i] = $top; $ctx[] = 'dq'; $depth[] = 0; }
            continue;
        }
        if ($id === T_START_HEREDOC) { $map[$i] = $top; $ctx[] = 'dq'; $depth[] = 0; continue; }
        if ($id === T_END_HEREDOC)   { $map[$i] = 'dq'; if ($top === 'dq') { array_pop($ctx); array_pop($depth); } continue; }
        if ($top === 'dq' && ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES)) {
            $map[$i] = 'dq'; $ctx[] = 'curly'; $depth[] = 1; continue;
        }
        if ($top === 'curly') {
            if ($id === T_CURLY_OPEN) { $depth[count($depth) - 1]++; $map[$i] = 'curly'; continue; }
            if ($id === null && $text === '{') { $depth[count($depth) - 1]++; }
            elseif ($id === null && $text === '}') {
                $depth[count($depth) - 1]--;
                if (end($depth) === 0) { $map[$i] = 'curly'; array_pop($ctx); array_pop($depth); continue; }
            }
        }
        $map[$i] = $top;
    }
    return $map;
}

/**
 * Classify every T_STRING.  Returns list of
 *   ['i'=>tokenIndex,'kind'=>'const'|'call'|'newclass'|'staticclass'|'skip', 'sub'=>..., 'name'=>..., 'line'=>..., 'off'=>...]
 * "$a[key]" (simple interpolation, key is a string) is skipped,
 * "{$a[key]}" (complex syntax, key is a constant expression) is reported.
 */
function php8_classify(array $toks): array
{
    $res = [];
    $ctxs = php8_ctx_map($toks);
    $prefix = php8_name_prefix_ids();
    foreach ($toks as $i => [$id, $text, $line, $off]) {
        if ($id !== T_STRING) continue;
        $top = $ctxs[$i];
        $p = php8_prev_sig($toks, $i);
        $nx = php8_next_sig($toks, $i);
        $pt = $p >= 0 ? $toks[$p] : [null, '', 0, 0];
        $nt = $nx >= 0 ? $toks[$nx] : [null, '', 0, 0];
        $name = $text;
        $rec = ['i' => $i, 'name' => $name, 'line' => $line, 'off' => $off, 'ctx' => $top];

        if ($top === 'dq') { $rec['kind'] = 'skip'; $rec['why'] = 'dq'; $res[] = $rec; continue; }
        if ($pt[0] !== null && in_array($pt[0], $prefix, true)) {
            $rec['kind'] = ($pt[0] === T_NEW) ? 'newclass' : 'skip'; $rec['why'] = 'prefix'; $res[] = $rec; continue;
        }
        if ($pt[1] === '&') {                       // function &name(
            $pp = php8_prev_sig($toks, $p);
            if ($pp >= 0 && $toks[$pp][0] === T_FUNCTION) { $rec['kind'] = 'skip'; $rec['why'] = 'decl'; $res[] = $rec; continue; }
        }
        if ($nt[1] === '(') { $rec['kind'] = 'call'; $res[] = $rec; continue; }
        if ($nt[0] === T_DOUBLE_COLON) { $rec['kind'] = 'staticclass'; $res[] = $rec; continue; }
        if ($nt[0] === T_VARIABLE || $nt[1] === '&' || $nt[0] === T_ELLIPSIS) {   // type hints
            $nn = $nx >= 0 ? php8_next_sig($toks, $nx) : -1;
            if ($nt[0] === T_VARIABLE || ($nn >= 0 && ($toks[$nn][0] === T_VARIABLE || $toks[$nn][0] === T_ELLIPSIS))) {
                $rec['kind'] = 'skip'; $rec['why'] = 'typehint'; $res[] = $rec; continue;
            }
        }
        if ($nt[1] === ':' && ($p < 0 || in_array($pt[1], [';', '{', '}'], true) || $pt[0] === T_OPEN_TAG)) {
            $rec['kind'] = 'skip'; $rec['why'] = 'label'; $res[] = $rec; continue;
        }
        $l = strtolower($name);
        if ($l === 'true' || $l === 'false' || $l === 'null') { $rec['kind'] = 'skip'; $rec['why'] = 'literal'; $res[] = $rec; continue; }

        $rec['kind'] = 'const';
        if ($pt[1] === '[' && $nt[1] === ']') $rec['sub'] = 'subscript';
        elseif ($nt[0] === T_DOUBLE_ARROW) $rec['sub'] = 'arraykey';
        elseif ($pt[0] === T_CASE) $rec['sub'] = 'case';
        else $rec['sub'] = 'other';
        $res[] = $rec;
    }
    return $res;
}

function php8_line_text(string $code, int $line): string
{
    static $cache = [];
    $h = md5($code);
    if (!isset($cache[$h])) { $cache = [$h => preg_split('/\r\n|\n|\r/', $code)]; }
    return trim(mb_strimwidth(@iconv('UTF-8', 'UTF-8//IGNORE', $cache[$h][$line - 1] ?? '') ?: '', 0, 160, '…', 'UTF-8'));
}

function php8_known_constants(): array
{
    static $set = null;
    if ($set !== null) return $set;
    $set = [];
    foreach (get_defined_constants() as $k => $v) $set[$k] = true;
    // constants of the removed ext/mysql that the compat layer provides
    foreach (['MYSQL_ASSOC','MYSQL_NUM','MYSQL_BOTH','MYSQL_CLIENT_COMPRESS','MYSQL_CLIENT_SSL','MYSQL_CLIENT_INTERACTIVE','MYSQL_CLIENT_IGNORE_SPACE'] as $c) $set[$c] = true;
    return $set;
}
