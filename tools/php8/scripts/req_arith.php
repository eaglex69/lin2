<?php
// Audit (read-only): request values ($_GET/$_POST/$_REQUEST, or a variable that was assigned straight from one) that are used in
// arithmetic or passed to a numeric function without being converted first.
// PHP 5 silently turned '' / 'abc' into 0 there; PHP 8 throws TypeError ("Unsupported operand types: string + int",
// "round(): Argument #1 ($num) must be of type int|float, string given").  The smoke test only reaches the branches its generated
// requests select, so this static scan complements it.
//
// Flow-insensitive on purpose (a candidate list for manual triage):  a variable counts as "assigned from a request value" when some
// statement  $v = [wrapper(] $_GET['k'] [)]  [? ... : ...]  exists in the file and no sanitiser (intval, (int), abs, round, ...) is on
// that right-hand side.  The column G tells whether the file also contains is_numeric/ctype_digit/intval/(int) applied to that name.
// usage: node runphp.mjs 8.3 /tools/req_arith.php /work [--all]       (--all also lists the uses that look guarded)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$showAll = in_array('--all', $argv, true);

$sanitisers = ['intval', 'floatval', 'doubleval', 'abs', 'round', 'floor', 'ceil', 'is_numeric', 'ctype_digit', 'count', 'strlen',
               'mb_strlen', 'sizeof', 'min', 'max', 'mt_rand', 'rand', 'time', 'in_array', 'array_search', 'isset', 'preg_match'];
$castIds = [T_INT_CAST, T_DOUBLE_CAST];
$numFuncs = ['round', 'floor', 'ceil', 'abs', 'sqrt', 'number_format', 'intdiv', 'pow', 'fmod', 'array_sum', 'str_repeat', 'range',
             'array_fill', 'mktime', 'chr', 'dechex', 'decbin', 'decoct', 'log', 'exp', 'sin', 'cos', 'usleep', 'sleep', 'money_format'];
$arith = ['+', '-', '*', '/', '%'];
$arithIds = [T_POW];
$compoundIds = [T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_MOD_EQUAL];
$superNames = ['$_GET', '$_POST', '$_REQUEST'];

/** index of the last token of the request element starting at $i ($_GET [ 'k' ]) or -1 */
$elemEnd = function (array $toks, int $i): int {
    $a = php8_next_sig($toks, $i);
    if ($a < 0 || $toks[$a][1] !== '[') return -1;
    $b = php8_next_sig($toks, $a);
    if ($b < 0 || $toks[$b][0] !== T_CONSTANT_ENCAPSED_STRING) return -1;
    $c = php8_next_sig($toks, $b);
    if ($c < 0 || $toks[$c][1] !== ']') return -1;
    return $c;
};

/** "$_GET[k]" for a request element starting at token $i, else the variable name */
$reqKey = function (array $toks, int $i) use ($superNames, $elemEnd): string {
    if (!in_array($toks[$i][1], $superNames, true) || $elemEnd($toks, $i) < 0) return $toks[$i][1];
    return $toks[$i][1] . '[' . trim($toks[php8_next_sig($toks, php8_next_sig($toks, $i))][1], '\'"') . ']';
};

$rows = [];
foreach (php8_list_files($root) as $f) {
    $src = file_get_contents($f);
    $toks = php8_tokens($src);
    $ctx = php8_ctx_map($toks);
    $n = count($toks);
    $rel = ltrim(substr($f, strlen($root)), '/');

    // ---- 1. variables assigned from request values / guards
    $tainted = [];          // name => first line
    $guarded = [];          // name => true when a sanitiser/guard on that name exists in the file
    for ($i = 0; $i < $n; $i++) {
        if (($ctx[$i] ?? 'code') !== 'code') continue;
        $t = $toks[$i];
        // guards: is_numeric($v), ctype_digit($v), intval($v), (int)$v, $v = <sanitiser ...>
        if ($t[0] === T_STRING && in_array(strtolower($t[1]), ['is_numeric', 'ctype_digit', 'intval', 'floatval', 'abs', 'round', 'floor', 'ceil'], true)) {
            $p = php8_next_sig($toks, $i);
            $v = $p >= 0 ? php8_next_sig($toks, $p) : -1;
            if ($v >= 0 && $toks[$v][0] === T_VARIABLE) { $guarded[$toks[$v][1]] = true; $guarded[$reqKey($toks, $v)] = true; }
        }
        if (in_array($t[0], $castIds, true)) {
            $v = php8_next_sig($toks, $i);
            if ($v >= 0 && $toks[$v][0] === T_VARIABLE) { $guarded[$toks[$v][1]] = true; $guarded[$reqKey($toks, $v)] = true; }
        }
        // assignment  $v = <rhs> ;
        if ($t[0] !== T_VARIABLE || in_array($t[1], $superNames, true) || $t[1] === '$this') continue;
        $eq = php8_next_sig($toks, $i);
        if ($eq < 0 || $toks[$eq][0] !== null || $toks[$eq][1] !== '=') continue;
        $pv = php8_prev_sig($toks, $i);
        if ($pv >= 0 && ($toks[$pv][0] === T_OBJECT_OPERATOR || $toks[$pv][0] === T_DOUBLE_COLON)) continue;
        $rawIdx = -1; $san = false; $arithInRhs = false; $depth = 0;
        for ($j = $eq + 1; $j < $n; $j++) {
            $u = $toks[$j];
            if ($u[0] === T_WHITESPACE || $u[0] === T_COMMENT) continue;
            if ($u[0] === null) {
                if ($u[1] === '(' || $u[1] === '[') $depth++;
                elseif ($u[1] === ')' || $u[1] === ']') { $depth--; if ($depth < 0) break; }
                elseif ($u[1] === ';' && $depth === 0) break;
                elseif (in_array($u[1], $arith, true)) $arithInRhs = true;
            }
            if (in_array($u[0], $castIds, true)) $san = true;
            if ($u[0] === T_STRING && in_array(strtolower($u[1]), $sanitisers, true)) $san = true;
            if ($u[0] === T_VARIABLE && in_array($u[1], $superNames, true) && $rawIdx < 0 && $elemEnd($toks, $j) >= 0) $rawIdx = $j;
            if ($u[0] === T_CLOSE_TAG) break;
        }
        if ($rawIdx >= 0 && !$arithInRhs) {
            if ($san) $guarded[$t[1]] = true;
            else $tainted[$t[1]] = $tainted[$t[1]] ?? $t[2];
        } elseif ($san) {
            $guarded[$t[1]] = true;
        }
    }

    // ---- 2. uses
    for ($i = 0; $i < $n; $i++) {
        if (($ctx[$i] ?? 'code') !== 'code') continue;
        $t = $toks[$i];
        if ($t[0] !== T_VARIABLE) continue;
        $name = $t[1]; $end = $i; $label = $name;
        if (in_array($name, $superNames, true)) {
            $end = $elemEnd($toks, $i);
            if ($end < 0) continue;
            $label = $reqKey($toks, $i);
            $isG = !empty($guarded[$label]); $guardNote = $isG ? 'G' : '-';
        } else {
            if (!isset($tainted[$name])) continue;
            $isG = !empty($guarded[$name]); $guardNote = $isG ? 'G' : '-';
            $nn = php8_next_sig($toks, $i);
            if ($nn >= 0 && $toks[$nn][1] === '[') continue;                 // $v[...] : an array element, not the scalar
        }
        $pv = php8_prev_sig($toks, $i);
        $nx = php8_next_sig($toks, $end);
        if ($pv >= 0 && in_array($toks[$pv][0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_GLOBAL, T_STATIC], true)) continue;
        $kind = null;
        // binary arithmetic on the left of the operand:  X <op> $v
        if ($pv >= 0 && (($toks[$pv][0] === null && in_array($toks[$pv][1], $arith, true)) || in_array($toks[$pv][0], $arithIds, true))) {
            $pp = php8_prev_sig($toks, $pv);
            $unary = true;
            if ($pp >= 0) {
                $q = $toks[$pp];
                if ($q[0] === T_VARIABLE || $q[0] === T_LNUMBER || $q[0] === T_DNUMBER || $q[0] === T_CONSTANT_ENCAPSED_STRING || $q[0] === T_STRING
                    || ($q[0] === null && ($q[1] === ')' || $q[1] === ']'))) $unary = false;
            }
            $kind = $unary ? 'unary ' . $toks[$pv][1] : 'arith ' . $toks[$pv][1];
        }
        // binary arithmetic on the right of the operand:  $v <op> X
        if ($kind === null && $nx >= 0 && (($toks[$nx][0] === null && in_array($toks[$nx][1], $arith, true)) || in_array($toks[$nx][0], $arithIds, true))) {
            $kind = 'arith ' . $toks[$nx][1];
        }
        // compound assignment  $v += ...
        if ($kind === null && $end === $i && $nx >= 0 && in_array($toks[$nx][0], $compoundIds, true)) $kind = 'compound ' . $toks[$nx][1];
        // numeric function with the operand as a lone argument
        if ($kind === null && $pv >= 0 && $toks[$pv][1] === '(' && $nx >= 0 && ($toks[$nx][1] === ',' || $toks[$nx][1] === ')')) {
            $fn = php8_prev_sig($toks, $pv);
            if ($fn >= 0 && $toks[$fn][0] === T_STRING && in_array(strtolower($toks[$fn][1]), $numFuncs, true)) {
                $pf = php8_prev_sig($toks, $fn);
                if (!($pf >= 0 && in_array($toks[$pf][0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true))) $kind = 'call ' . strtolower($toks[$fn][1]) . '()';
            }
        }
        if ($kind === null) continue;
        if ($isG && !$showAll) continue;
        // snippet of the source line
        $ls = strrpos(substr($src, 0, $t[3]), "\n"); $ls = $ls === false ? 0 : $ls + 1;
        $le = strpos($src, "\n", $t[3]); if ($le === false) $le = strlen($src);
        $snippet = trim(substr($src, $ls, min($le - $ls, 150)));
        $rows[] = [$rel, $t[2], $guardNote, $label, $kind, $snippet];
    }
}
usort($rows, fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
$seen = [];
foreach ($rows as $r) {
    $key = $r[0] . ':' . $r[1] . ':' . $r[3] . ':' . $r[4];
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    echo "{$r[0]}:{$r[1]} [{$r[2]}] {$r[3]}  {$r[4]}  |  {$r[5]}\n";
}
echo count($seen) . " use(s) in " . count(array_unique(array_column($rows, 0))) . " file(s)\n";
