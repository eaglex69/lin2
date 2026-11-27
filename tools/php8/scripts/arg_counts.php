<?php
// PHP 8 throws ArgumentCountError when a user function is called with fewer arguments than it has required
// parameters (PHP 5/7: warning, the missing parameters were NULL). Finds such calls statically.
// Output: call site, function, required/passed, definition site.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');

$defs = [];     // lower name => list of [file, line, required, total, isMethod, class]
$files = php8_list_files($root);
$all = [];
foreach ($files as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $toks = php8_tokens(file_get_contents($f));
    $all[$rel] = $toks;
    $n = count($toks);
    $classStack = []; $depth = 0; $pendingClass = null;
    for ($i = 0; $i < $n; $i++) {
        $t = $toks[$i];
        if ($t[0] === T_CLASS || $t[0] === T_INTERFACE || $t[0] === T_TRAIT) {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][0] === T_STRING) $pendingClass = $toks[$j][1];
        }
        if ($t[0] === null && $t[1] === '{') { $depth++; if ($pendingClass !== null) { $classStack[] = [$pendingClass, $depth]; $pendingClass = null; } }
        if ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES) $depth++;
        if ($t[0] === null && $t[1] === '}') { if ($classStack && end($classStack)[1] === $depth) array_pop($classStack); $depth--; }
        if ($t[0] !== T_FUNCTION) continue;
        $j = php8_next_sig($toks, $i);
        if ($j >= 0 && $toks[$j][1] === '&') $j = php8_next_sig($toks, $j);
        if ($j < 0 || $toks[$j][0] !== T_STRING) continue;      // closure
        $name = $toks[$j][1];
        $k = php8_next_sig($toks, $j);
        if ($k < 0 || $toks[$k][1] !== '(') continue;
        // parse parameters
        $req = 0; $total = 0; $lvl = 0; $cur = []; $params = [];
        for ($m = $k; $m < $n; $m++) {
            $s = $toks[$m][1];
            if ($toks[$m][0] === null && $s === '(') { $lvl++; if ($lvl === 1) continue; }
            if ($toks[$m][0] === null && $s === ')') { $lvl--; if ($lvl === 0) { if ($cur) $params[] = $cur; break; } }
            if ($lvl === 1 && $toks[$m][0] === null && $s === ',') { $params[] = $cur; $cur = []; continue; }
            if (!php8_is_ws($toks[$m])) $cur[] = $toks[$m];
        }
        $lastRequired = 0; $idx = 0; $variadic = false;
        foreach ($params as $p) {
            $idx++;
            $hasDefault = false; $isVar = false;
            foreach ($p as $pt) { if ($pt[0] === null && $pt[1] === '=') $hasDefault = true; if ($pt[0] === T_ELLIPSIS) $isVar = true; }
            if ($isVar) { $variadic = true; continue; }
            if (!$hasDefault) $lastRequired = $idx;
        }
        $cls = $classStack ? end($classStack)[0] : null;
        $defs[strtolower($name)][] = [$rel, $toks[$j][2], $lastRequired, $idx, $cls !== null, $cls, $variadic];
    }
}

$problems = [];
foreach ($all as $rel => $toks) {
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] !== T_STRING) continue;
        $k = php8_next_sig($toks, $i);
        if ($k < 0 || $toks[$k][1] !== '(') continue;
        $p = php8_prev_sig($toks, $i);
        $prevTok = $p >= 0 ? $toks[$p] : [null, ''];
        if (in_array($prevTok[0], [T_FUNCTION, T_NEW, T_CLASS], true)) continue;
        $isMethodCall = in_array($prevTok[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR ?? -1], true);
        $lname = strtolower($toks[$i][1]);
        if (!isset($defs[$lname])) continue;
        // count args
        $lvl = 0; $args = 0; $hasTok = false; $unpack = false;
        for ($m = $k; $m < $n; $m++) {
            $s = $toks[$m][1];
            if ($toks[$m][0] === null && ($s === '(' || $s === '[' || $s === '{')) { $lvl++; if ($lvl === 1) continue; }
            if ($toks[$m][0] === T_CURLY_OPEN || $toks[$m][0] === T_DOLLAR_OPEN_CURLY_BRACES) $lvl++;
            if ($toks[$m][0] === null && ($s === ')' || $s === ']' || $s === '}')) { $lvl--; if ($lvl === 0) { if ($hasTok) $args++; break; } }
            if ($lvl === 1 && $toks[$m][0] === null && $s === ',') { $args++; $hasTok = false; continue; }
            if ($toks[$m][0] === T_ELLIPSIS) $unpack = true;
            if (!php8_is_ws($toks[$m])) $hasTok = true;
        }
        if ($unpack) continue;
        // candidate definitions: for plain calls -> functions (not methods); for method calls -> methods with that name
        $cands = array_filter($defs[$lname], fn($d) => $d[4] === $isMethodCall);
        if (!$cands) continue;
        // the definition in the same file wins, otherwise any
        $same = array_filter($cands, fn($d) => $d[0] === $rel);
        $use = $same ?: $cands;
        $minReq = min(array_map(fn($d) => $d[2], $use));
        $maxTotal = max(array_map(fn($d) => $d[6] ? 999 : $d[3], $use));
        if ($args < $minReq) {
            $d = reset($use);
            $problems[] = sprintf("%s:%d  %s%s() called with %d arg(s), %d required  (defined %s:%d)", $rel, $toks[$i][2], $isMethodCall ? ($d[5] ? $d[5] . '::' : '->') : '', $toks[$i][1], $args, $minReq, $d[0], $d[1]);
        }
    }
}
echo count($problems), " call(s) with too few arguments\n";
foreach ($problems as $p) echo "  $p\n";
