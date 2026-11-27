<?php
// Per-entry-script analysis: global-scope variables READ but never ASSIGNED in {entry + statically included files}.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$files = php8_list_files($root);

$byRefFns = array_flip(['preg_match','preg_match_all','sscanf','parse_str','similar_text','exec','getimagesize','is_callable','mb_parse_str','list','shuffle','sort','rsort','usort','uasort','uksort','ksort','krsort','asort','arsort','array_push','array_pop','array_shift','array_unshift','array_splice','array_walk','array_multisort','settype','end','reset','next','prev','each','current','key','fscanf','stream_select','openssl_sign','getmxrr','dns_get_record','fsockopen','flock','headers_sent','array_walk_recursive','natsort','natcasesort','extract','getopt']);
$super = array_flip(['GLOBALS','_SERVER','_GET','_POST','_FILES','_COOKIE','_SESSION','_REQUEST','_ENV','this','argv','argc','php_errormsg','http_response_header']);

function analyse(string $f, string $root, array $byRefFns, array $super): array {
    $code = file_get_contents($f);
    $toks = php8_tokens($code);
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
    $assigned = []; $reads = []; $includes = [];
    for ($i = 0; $i < $n; $i++) {
        [$id, $text, $line] = $toks[$i];
        if ($inFn[$i]) continue;
        if (in_array($id, [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
            // collect string literals up to ';'
            $lits = []; $dyn = false;
            for ($k = $i + 1; $k < $n && $toks[$k][1] !== ';'; $k++) {
                if ($toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $lits[] = trim($toks[$k][1], "'\"");
                elseif ($toks[$k][0] === T_VARIABLE && $toks[$k][1] !== '$path') $dyn = true;
            }
            if ($lits && !$dyn) $includes[] = implode('', $lits);
            continue;
        }
        if ($id !== T_VARIABLE) continue;
        $name = substr($text, 1);
        if (isset($super[$name])) continue;
        $p = php8_prev_sig($toks, $i);
        $pt = $p >= 0 ? $toks[$p] : [null, ''];
        $j = php8_next_sig($toks, $i);
        $isDim = false;
        while ($j >= 0 && $toks[$j][1] === '[') {
            $isDim = true; $d = 0;
            for ($k = $j; $k < $n; $k++) { if ($toks[$k][1] === '[') $d++; elseif ($toks[$k][1] === ']') { $d--; if ($d === 0) break; } }
            $j = php8_next_sig($toks, $k);
        }
        $nt = $j >= 0 ? $toks[$j] : [null, ''];
        $assignOps = ['=', '.=', '+=', '-=', '*=', '/=', '%=', '|=', '&=', '^='];
        $isAssign = ($nt[0] === null && in_array($nt[1], $assignOps, true))
            || in_array($nt[0], [T_CONCAT_EQUAL, T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_MOD_EQUAL, T_OR_EQUAL, T_AND_EQUAL, T_XOR_EQUAL, T_INC, T_DEC, T_SL_EQUAL, T_SR_EQUAL, T_POW_EQUAL], true)
            || $pt[0] === T_INC || $pt[0] === T_DEC;
        $isForeach = ($pt[0] === T_AS || $pt[0] === T_DOUBLE_ARROW);
        $isGlobal = ($pt[0] === T_GLOBAL || $pt[0] === T_STATIC);
        $isByRef = ($pt[1] === '&');
        if (!$isAssign && !$isForeach && !$isGlobal && !$isByRef) {
            $d = 0;
            for ($k = $i - 1; $k >= 0; $k--) {
                $s = $toks[$k][1];
                if ($s === ')') $d++;
                elseif ($s === '(') { if ($d === 0) { $q = php8_prev_sig($toks, $k); if ($q >= 0 && (($toks[$q][0] === T_STRING && isset($byRefFns[strtolower($toks[$q][1])])) || $toks[$q][0] === T_LIST)) $isByRef = true; break; } $d--; }
                elseif ($s === ';' || $s === '{' || $s === '}') break;
            }
            // [$a, $b] = ...  /  list($a,$b) =
            if (!$isByRef) {
                $d = 0; $k = $i;
                while ($k < $n && $toks[$k][1] !== ';') { if ($toks[$k][1] === ']' || $toks[$k][1] === ')') { $q = php8_next_sig($toks, $k); if ($q >= 0 && $toks[$q][1] === '=') { $isAssign = true; } break; } if ($toks[$k][1] === '[' || $toks[$k][1] === '(' ) break; $k++; }
            }
        }
        if ($isAssign || $isForeach || $isGlobal || $isByRef) $assigned[$name] = true;
        else $reads[$name][] = ltrim(substr($f, strlen($root)), '/') . ':' . $line;
    }
    return [$assigned, $reads, $includes];
}

$info = [];
foreach ($files as $f) $info[$f] = analyse($f, $root, $byRefFns, $super);
$rel = fn($f) => ltrim(substr($f, strlen($root)), '/');
$byRel = []; foreach ($files as $f) $byRel[$rel($f)] = $f;

function resolve(string $lit, string $fromFile, string $entry, string $root, array $byRel): ?string {
    $cands = [];
    $lit = ltrim($lit, '/');
    foreach ([dirname($fromFile), dirname($entry), $root] as $base) {
        $p = realpath($base . '/' . $lit);
        if ($p) $cands[] = ltrim(substr($p, strlen($root)), '/');
    }
    foreach ($cands as $c) if (isset($byRel[$c])) return $byRel[$c];
    return null;
}

// entries = files that define PROTECTOR or are not included by anyone
$included = [];
foreach ($info as $f => [$a, $r, $inc]) foreach ($inc as $lit) { $t = resolve($lit, $f, $f, $root, $byRel); if ($t) $included[$t] = true; }

$unbound = [];   // var => [entry => [locs]]
foreach ($files as $entry) {
    if (isset($included[$entry]) && strpos(file_get_contents($entry), "define('PROTECTOR'") === false) continue;
    // transitive closure of includes
    $seen = [$entry => true]; $queue = [$entry];
    while ($queue) {
        $cur = array_shift($queue);
        foreach ($info[$cur][2] as $lit) {
            $t = resolve($lit, $cur, $entry, $root, $byRel);
            if ($t && !isset($seen[$t])) { $seen[$t] = true; $queue[] = $t; }
        }
    }
    $A = [];
    foreach ($seen as $f => $_) foreach ($info[$f][0] as $name => $__) $A[$name] = true;
    foreach ($seen as $f => $_) foreach ($info[$f][1] as $name => $locs) {
        if (!isset($A[$name])) foreach ($locs as $loc) $unbound[$name][$rel($entry)][$loc] = true;
    }
}
ksort($unbound);
echo "variables read without assignment in {entry + includes}: ", count($unbound), "\n\n";
foreach ($unbound as $name => $perEntry) {
    $locs = []; foreach ($perEntry as $e => $ls) foreach (array_keys($ls) as $l) $locs[$l] = true;
    echo str_pad('$' . $name, 20), str_pad(count($locs) . ' reads', 10), str_pad(count($perEntry) . ' entries', 12), implode(' ', array_slice(array_keys($locs), 0, 3)), "\n";
}
