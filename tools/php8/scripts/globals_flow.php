<?php
// Flattened-include "first occurrence is a read" analysis (approximates register_globals dependence).
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$files = php8_list_files($root);
$byRefFns = array_flip(['preg_match','preg_match_all','sscanf','parse_str','similar_text','exec','getimagesize','is_callable','mb_parse_str','shuffle','sort','rsort','usort','uasort','uksort','ksort','krsort','asort','arsort','array_push','array_pop','array_shift','array_unshift','array_splice','array_walk','array_multisort','settype','end','reset','next','prev','each','current','key','fscanf','stream_select','openssl_sign','getmxrr','dns_get_record','fsockopen','flock','headers_sent','array_walk_recursive','natsort','natcasesort','extract','getopt','mysql_fetch_array']);
$super = array_flip(['GLOBALS','_SERVER','_GET','_POST','_FILES','_COOKIE','_SESSION','_REQUEST','_ENV','this','argv','argc','php_errormsg','http_response_header']);

function events(string $f, string $root, array $byRefFns, array $super): array {
    $toks = php8_tokens(file_get_contents($f));
    $ctx = php8_ctx_map($toks);
    $n = count($toks);
    $inFn = array_fill(0, $n, false);
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] === T_FUNCTION && $ctx[$i] === 'code') {
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
    $rel = ltrim(substr($f, strlen($root)), '/');
    $ev = [];
    $keys = [];
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] === T_VARIABLE && in_array($toks[$i][1], ['$_GET', '$_POST', '$_REQUEST', '$_COOKIE'], true)) {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][1] === '[') { $k = php8_next_sig($toks, $j); if ($k >= 0 && $toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $keys[trim($toks[$k][1], "'\"")] = true; }
        }
        // URL params / form names inside strings or inline html
        if (in_array($toks[$i][0], [T_CONSTANT_ENCAPSED_STRING, T_INLINE_HTML, T_ENCAPSED_AND_WHITESPACE], true)) {
            if (preg_match_all('/[?&;](?:amp;)?([A-Za-z_][A-Za-z0-9_]*)(?==|&|"|\'|\s|$)/', $toks[$i][1], $mm)) foreach ($mm[1] as $kk) $keys[$kk] = true;
            if (preg_match_all('/\bname=\\\\?["\']?([A-Za-z_][A-Za-z0-9_]*)/i', $toks[$i][1], $mm)) foreach ($mm[1] as $kk) $keys[$kk] = true;
        }
    }
    $GLOBALS['REQKEYS'] = ($GLOBALS['REQKEYS'] ?? []) + $keys;
    for ($i = 0; $i < $n; $i++) {
        if ($inFn[$i]) continue;
        [$id, $text, $line] = $toks[$i];
        if (in_array($id, [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
            $lits = []; $dyn = false;
            for ($k = $i + 1; $k < $n && $toks[$k][1] !== ';'; $k++) {
                if ($toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $lits[] = trim($toks[$k][1], "'\"");
                elseif ($toks[$k][0] === T_VARIABLE && $toks[$k][1] !== '$path') $dyn = true;
            }
            if ($lits && !$dyn) $ev[] = ['I', implode('', $lits), $line];
            continue;
        }
        if ($id === T_EXIT) { $ev[] = ['X', '', $line]; continue; }
        if ($id !== T_VARIABLE) continue;
        $name = substr($text, 1);
        if (isset($super[$name])) continue;
        $p = php8_prev_sig($toks, $i);
        $pt = $p >= 0 ? $toks[$p] : [null, ''];
        $j = php8_next_sig($toks, $i);
        $isDim = false; $lastDimEnd = $i;
        while ($j >= 0 && $toks[$j][1] === '[') {
            $isDim = true; $d = 0;
            for ($k = $j; $k < $n; $k++) { if ($toks[$k][1] === '[') $d++; elseif ($toks[$k][1] === ']') { $d--; if ($d === 0) break; } }
            $lastDimEnd = $k; $j = php8_next_sig($toks, $k);
        }
        $nt = $j >= 0 ? $toks[$j] : [null, ''];
        $isAssign = ($nt[0] === null && $nt[1] === '=')
            || in_array($nt[0], [T_CONCAT_EQUAL, T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_MOD_EQUAL, T_OR_EQUAL, T_AND_EQUAL, T_XOR_EQUAL, T_INC, T_DEC, T_SL_EQUAL, T_SR_EQUAL, T_POW_EQUAL], true)
            || $pt[0] === T_INC || $pt[0] === T_DEC;
        $isForeach = ($pt[0] === T_AS || $pt[0] === T_DOUBLE_ARROW);
        $isGlobal = ($pt[0] === T_GLOBAL || $pt[0] === T_STATIC);
        $isByRef = ($pt[1] === '&');
        $isCheck = false;
        // inside isset( ... ) / empty( ... ) / unset(...)?
        $d = 0; $callee = null;
        for ($k = $i - 1; $k >= 0; $k--) {
            $s = $toks[$k][1];
            if ($s === ')') $d++;
            elseif ($s === '(') { if ($d === 0) { $q = php8_prev_sig($toks, $k); if ($q >= 0) $callee = $toks[$q]; break; } $d--; }
            elseif ($s === ';' || $s === '{' || $s === '}') break;
        }
        if ($callee) {
            if ($callee[0] === T_ISSET || $callee[0] === T_EMPTY) $isCheck = true;
            elseif ($callee[0] === T_UNSET) $isByRef = true;
            elseif ($callee[0] === T_LIST || ($callee[0] === T_STRING && isset($byRefFns[strtolower($callee[1])]))) $isByRef = true;
        }
        if (!$isAssign && !$isForeach && !$isGlobal && !$isByRef && !$isCheck) {
            $k = $lastDimEnd;
            // [$a, $b] = / list(..) =
            $d = 0;
            for ($k = $i; $k < $n && $toks[$k][1] !== ';'; $k++) {
                if ($toks[$k][1] === ']' || $toks[$k][1] === ')') { $q = php8_next_sig($toks, $k); if ($q >= 0 && $toks[$q][1] === '=') $isAssign = true; break; }
                if ($toks[$k][1] === '[' || $toks[$k][1] === '(') break;
            }
        }
        $kind = ($isAssign || $isForeach || $isGlobal || $isByRef) ? 'W' : ($isCheck ? 'C' : 'R');
        if ($kind === 'W' && $nt[0] === null && $nt[1] === '=') {
            for ($k = $j + 1; $k < $n && $toks[$k][1] !== ';'; $k++) {
                if ($toks[$k][0] === T_VARIABLE && in_array($toks[$k][1], ['$_GET', '$_POST', '$_REQUEST', '$_COOKIE'], true)) { $kind = 'Q'; break; }
            }
        }
        // request param names used in this file (literal keys)
        
        // `$x = ...` where RHS reads $x is rare; ignore
        $ev[] = [$kind, $name, $line];
    }
    return [$rel, $ev];
}

$info = []; $byRel = [];
foreach ($files as $f) { [$rel, $ev] = events($f, $root, $byRefFns, $super); $info[$rel] = $ev; $byRel[$rel] = true; }

function resolve_inc(string $lit, string $fromRel, string $entryRel, array $byRel): ?string {
    $lit = ltrim($lit, '/');
    $lit = preg_replace('#^\./#', '', $lit);
    foreach ([dirname($fromRel), dirname($entryRel), '.'] as $base) {
        $p = ($base === '.' ? '' : $base . '/') . $lit;
        // normalise ../
        $parts = [];
        foreach (explode('/', $p) as $seg) { if ($seg === '..') array_pop($parts); elseif ($seg !== '.' && $seg !== '') $parts[] = $seg; }
        $c = implode('/', $parts);
        if (isset($byRel[$c])) return $c;
    }
    return null;
}

$included = [];
foreach ($info as $rel => $ev) foreach ($ev as $e) if ($e[0] === 'I') { $t = resolve_inc($e[1], $rel, $rel, $byRel); if ($t) $included[$t] = true; }

$seqFlatten = function (string $entry) use (&$seqFlatten, $info, $byRel) {
    $out = [];
    $walk = function (string $rel, array $stack) use (&$walk, &$out, $info, $byRel, $entry) {
        if (count($stack) > 12) return;
        foreach ($info[$rel] as $e) {
            if ($e[0] === 'I') {
                $t = resolve_inc($e[1], $rel, $entry, $byRel);
                if ($t && !in_array($t, $stack, true)) $walk($t, array_merge($stack, [$t]));
            } elseif ($e[0] === 'X') { /* exit: ignore */ }
            else $out[] = [$e[0], $e[1], $rel . ':' . $e[2]];
        }
    };
    $walk($entry, [$entry]);
    return $out;
};

$cand = [];   // var => [entry => firstReadLoc]
$candC = [];
foreach (array_keys($info) as $entry) {
    $src = file_get_contents($root . '/' . $entry);
    if (isset($included[$entry]) && strpos($src, "define('PROTECTOR'") === false) continue;
    $seq = $seqFlatten($entry);
    $first = []; $laterW = [];
    foreach ($seq as [$k, $name, $loc]) {
        if (!isset($first[$name])) $first[$name] = [$k, $loc];
        elseif ($k === 'W') $laterW[$name] = true;
    }
    foreach ($first as $name => [$k, $loc]) {
        if ($k === 'R' && isset($laterW[$name])) $cand[$name][$entry] = $loc;
        if ($k === 'C' && isset($laterW[$name])) $candC[$name][$entry] = $loc;
    }
}
ksort($cand); ksort($candC);
echo "=== first occurrence is READ, later assigned (entries: ", count($info), ") ===\n";
foreach ($cand as $name => $per) echo str_pad('$' . $name, 20), str_pad(count($per) . ' entries', 12), implode(' ', array_slice(array_unique(array_values($per)), 0, 3)), "\n";
echo "\n=== first occurrence is isset/empty check, later assigned ===\n";
foreach ($candC as $name => $per) echo str_pad('$' . $name, 20), str_pad(count($per) . ' entries', 12), implode(' ', array_slice(array_unique(array_values($per)), 0, 3)), "\n";


// ---------- (C) request-param-named variables read but never assigned from a request in the closure ----------
$P = $GLOBALS['REQKEYS'];
$candQ = [];
foreach (array_keys($info) as $entry) {
    $srcc = file_get_contents($root . '/' . $entry);
    if (isset($included[$entry]) && strpos($srcc, "define('PROTECTOR'") === false) continue;
    $seq = $seqFlatten($entry);
    $Q = []; $Wany = []; $firstR = [];
    foreach ($seq as [$k, $name, $loc]) {
        if ($k === 'Q') { $Q[$name] = true; $Wany[$name] = true; }
        elseif ($k === 'W') $Wany[$name] = true;
        elseif ($k === 'R' || $k === 'C') { if (!isset($firstR[$name])) $firstR[$name] = [$k, $loc]; }
    }
    foreach ($firstR as $name => [$k, $loc]) {
        if (isset($P[$name]) && !isset($Q[$name])) $candQ[$name][$entry] = [$loc, isset($Wany[$name]) ? 'assigned-elsewhere' : 'never-assigned', $k];
    }
}
ksort($candQ);
echo "\n=== (C) variable named like a request param, read, but never assigned from \$_GET/\$_POST/\$_REQUEST/\$_COOKIE in closure ===\n";
foreach ($candQ as $name => $per) {
    $kinds = array_count_values(array_map(fn($v) => $v[1], $per));
    $first = array_values($per)[0];
    echo str_pad('$' . $name, 18), str_pad(count($per) . ' entries', 12), str_pad(implode(',', array_map(fn($k, $v) => "$k:$v", array_keys($kinds), $kinds)), 36), $first[0], "\n";
}
