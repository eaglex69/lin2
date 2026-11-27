<?php
// Finds plain global variables that are READ somewhere but never ASSIGNED anywhere in the project
// (candidates for register_globals dependence / typos).  Global scope only (function bodies skipped).
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');

$byRefFns = array_flip(['preg_match','preg_match_all','sscanf','parse_str','similar_text','exec','getimagesize','is_callable','mb_parse_str','list','shuffle','sort','rsort','usort','uasort','uksort','ksort','krsort','asort','arsort','array_push','array_pop','array_shift','array_unshift','array_splice','array_walk','array_multisort','settype','end','reset','next','prev','each','current','key','str_replace','preg_replace','fscanf','stream_select','socket_select','openssl_sign','getmxrr','dns_get_record','fsockopen','flock','headers_sent','preg_replace_callback','array_walk_recursive','natsort','natcasesort','extract','getopt','ob_get_status']);
$super = array_flip(['GLOBALS','_SERVER','_GET','_POST','_FILES','_COOKIE','_SESSION','_REQUEST','_ENV','this','argv','argc','php_errormsg','http_response_header']);

$assigned = []; $reads = [];   // name => [file:line,...]
$sessionKeys = [];
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $toks = php8_tokens(file_get_contents($f));
    $ctx = php8_ctx_map($toks);
    $n = count($toks);
    // mark function bodies
    $inFn = array_fill(0, $n, false);
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] === T_FUNCTION && $ctx[$i] === 'code') {
            // skip closures vs declarations alike: find the body's opening brace
            $k = $i; $paren = 0;
            while ($k < $n && !($toks[$k][1] === '{' && $paren === 0) && !($toks[$k][1] === ';' && $paren === 0)) {
                if ($toks[$k][1] === '(') $paren++; elseif ($toks[$k][1] === ')') $paren--;
                $k++;
            }
            if ($k < $n && $toks[$k][1] === '{') {
                $d = 0;
                for ($m = $k; $m < $n; $m++) {
                    if ($toks[$m][1] === '{' || $toks[$m][0] === T_CURLY_OPEN || $toks[$m][0] === T_DOLLAR_OPEN_CURLY_BRACES) $d++;
                    elseif ($toks[$m][1] === '}') { $d--; if ($d === 0) break; }
                    $inFn[$m] = true;
                }
                for ($m = $i; $m <= $k; $m++) $inFn[$m] = true;
            }
        }
    }
    for ($i = 0; $i < $n; $i++) {
        [$id, $text, $line] = $toks[$i];
        if ($id !== T_VARIABLE || $inFn[$i]) continue;
        $name = substr($text, 1);
        if (isset($super[$name])) {
            if ($name === '_SESSION') {
                $j = php8_next_sig($toks, $i);
                if ($j >= 0 && $toks[$j][1] === '[') { $k = php8_next_sig($toks, $j); if ($k >= 0 && $toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $sessionKeys[trim($toks[$k][1], "'\"")][$rel] = 1; }
            }
            continue;
        }
        $p = php8_prev_sig($toks, $i);
        $pt = $p >= 0 ? $toks[$p] : [null, ''];
        // skip over array subscripts / property fetches to find what follows the whole lvalue
        $j = php8_next_sig($toks, $i);
        $lval = $i;
        $isDim = false;
        while ($j >= 0 && ($toks[$j][1] === '[')) {
            $isDim = true; $d = 0;
            for ($k = $j; $k < $n; $k++) { if ($toks[$k][1] === '[') $d++; elseif ($toks[$k][1] === ']') { $d--; if ($d === 0) break; } }
            $lval = $k; $j = php8_next_sig($toks, $k);
        }
        $nt = $j >= 0 ? $toks[$j] : [null, ''];
        $assignOps = ['=', '.=', '+=', '-=', '*=', '/=', '%=', '|=', '&=', '^=', '??='];
        $isAssign = ($nt[0] === null && in_array($nt[1], $assignOps, true)) || in_array($nt[0], [T_CONCAT_EQUAL, T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_MOD_EQUAL, T_OR_EQUAL, T_AND_EQUAL, T_XOR_EQUAL, T_INC, T_DEC, T_SL_EQUAL, T_SR_EQUAL, T_POW_EQUAL, T_COALESCE_EQUAL], true)
                    || $nt[0] === T_INC || $nt[0] === T_DEC || $pt[0] === T_INC || $pt[0] === T_DEC;
        $isForeachTarget = false; $isGlobalDecl = false;
        if ($pt[0] === T_AS || $pt[0] === T_DOUBLE_ARROW) {
            // foreach (... as $v) / as $k => $v
            $isForeachTarget = true;
        }
        if ($pt[0] === T_GLOBAL || $pt[0] === T_STATIC || ($pt[1] === ',' && php8_prev_stmt_kw($toks, $p) === T_GLOBAL)) $isGlobalDecl = true;
        // direct argument to by-ref function / list() target
        $isByRefArg = false;
        if ($pt[1] === '&') $isByRefArg = true;
        if (!$isAssign && !$isForeachTarget && !$isGlobalDecl && !$isByRefArg) {
            // inside a call to a known by-ref function?
            $d = 0;
            for ($k = $i - 1; $k >= 0; $k--) {
                $s = $toks[$k][1];
                if ($s === ')') $d++;
                elseif ($s === '(') { if ($d === 0) { $q = php8_prev_sig($toks, $k); if ($q >= 0 && $toks[$q][0] === T_STRING && isset($byRefFns[strtolower($toks[$q][1])])) $isByRefArg = true; if ($q >= 0 && ($toks[$q][0] === T_LIST)) $isByRefArg = true; break; } $d--; }
                elseif ($s === ';' || $s === '{' || $s === '}') break;
            }
            // list(..) = / [..] = : variable followed later by ) =  -> handled above through T_LIST only
        }
        if ($isAssign || $isForeachTarget || $isGlobalDecl || $isByRefArg || ($isDim && $isAssign)) {
            $assigned[$name][$rel . ':' . $line] = 1;
        } else {
            $reads[$name][$rel . ':' . $line] = 1;
        }
    }
}
function php8_prev_stmt_kw($toks, $i) {
    for ($k = $i; $k >= 0; $k--) {
        if ($toks[$k][1] === ';') return null;
        if ($toks[$k][0] === T_GLOBAL || $toks[$k][0] === T_STATIC) return $toks[$k][0];
    }
    return null;
}

$never = [];
foreach ($reads as $name => $locs) if (!isset($assigned[$name])) $never[$name] = $locs;
ksort($never);
echo "global-scope variables read but never assigned anywhere: ", count($never), "\n\n";
foreach ($never as $name => $locs) {
    $files = []; foreach (array_keys($locs) as $l) { $files[explode(':', $l)[0]] = 1; }
    $first = array_slice(array_keys($locs), 0, 3);
    echo str_pad('$' . $name, 22), str_pad(count($locs) . ' reads', 10), str_pad(count($files) . ' files', 9), implode(' ', $first), "\n";
}
echo "\n--- \$_SESSION keys assigned/read in project ---\n";
ksort($sessionKeys);
foreach ($sessionKeys as $k => $fs) echo str_pad($k, 18), count($fs), " files", isset($reads[$k]) || isset($assigned[$k]) ? "   [also used as plain \$$k: " . (isset($assigned[$k]) ? 'assigned' : 'read-only') . "]" : '', "\n";
