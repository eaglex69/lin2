<?php
// Lists entry scripts that call mysql_*() but never (transitively, via static includes) load inc/db.php,
// inc/php8.php or inc/mysql_compat.php - those would die with "Call to undefined function mysql_connect()".
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$info = []; $byRel = [];
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $byRel[$rel] = true;
    $toks = php8_tokens(file_get_contents($f));
    $n = count($toks); $inc = []; $calls = 0; $first = 0;
    for ($i = 0; $i < $n; $i++) {
        if (in_array($toks[$i][0], [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
            $lits = []; $dyn = false;
            for ($k = $i + 1; $k < $n && $toks[$k][1] !== ';'; $k++) {
                if ($toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $lits[] = trim($toks[$k][1], "'\"");
                elseif ($toks[$k][0] === T_VARIABLE && $toks[$k][1] !== '$path') $dyn = true;
                elseif ($toks[$k][0] === T_STRING && in_array($toks[$k][1], ['dirname'], true)) { /* dirname(__FILE__) prefix */ }
            }
            if ($lits && !$dyn) $inc[] = implode('', $lits);
        }
        if ($toks[$i][0] === T_STRING && stripos($toks[$i][1], 'mysql_') === 0) {
            $j = php8_next_sig($toks, $i);
            $p = php8_prev_sig($toks, $i);
            if ($j >= 0 && $toks[$j][1] === '(' && !($p >= 0 && in_array($toks[$p][0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON], true))) { $calls++; if (!$first) $first = $toks[$i][2]; }
        }
    }
    $info[$rel] = [$inc, $calls, $first];
}
function res($lit, $from, $entry, $byRel) {
    $lit = ltrim(preg_replace('#^\./#', '', $lit), '/');
    foreach ([dirname($from), dirname($entry), '.'] as $b) {
        $p = ($b === '.' ? '' : $b . '/') . $lit; $parts = [];
        foreach (explode('/', $p) as $s) { if ($s === '..') array_pop($parts); elseif ($s !== '.' && $s !== '') $parts[] = $s; }
        $c = implode('/', $parts);
        if (isset($byRel[$c])) return $c;
    }
    return null;
}
$included = [];
foreach ($info as $rel => [$inc]) foreach ($inc as $l) { $t = res($l, $rel, $rel, $byRel); if ($t) $included[$t] = true; }
$bad = [];
foreach ($info as $entry => [$inc, $calls]) {
    if (isset($included[$entry]) && strpos(file_get_contents($root . '/' . $entry), "define('PROTECTOR'") === false) continue;
    $seen = [$entry => true]; $q = [$entry]; $anyCalls = $calls > 0; $shim = in_array($entry, ['inc/mysql_compat.php', 'inc/php8.php', 'inc/db.php'], true);
    while ($q) {
        $cur = array_shift($q);
        foreach ($info[$cur][0] as $l) {
            $t = res($l, $cur, $entry, $byRel);
            if ($t && !isset($seen[$t])) {
                $seen[$t] = true; $q[] = $t;
                if ($info[$t][1] > 0) $anyCalls = true;
                if (in_array($t, ['inc/mysql_compat.php', 'inc/php8.php', 'inc/db.php'], true)) $shim = true;
            }
        }
    }
    if ($anyCalls && !$shim) $bad[] = $entry . '  (' . $calls . ' direct calls, first at line ' . $info[$entry][2] . ')';
}
echo "entries calling mysql_*() without the shim in their include closure: ", count($bad), "\n";
foreach ($bad as $b) echo "  $b\n";
