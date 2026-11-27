<?php
// Per mysql_* function: distribution of argument counts + text of calls having link/extra args.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$stats = []; $extra = [];
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $code = file_get_contents($f);
    $toks = php8_tokens($code);
    $ctx = php8_ctx_map($toks);
    $n = count($toks);
    foreach ($toks as $i => [$id, $text, $line, $off]) {
        if ($id !== T_STRING || stripos($text, 'mysql_') !== 0 || $ctx[$i] === 'dq') continue;
        $j = php8_next_sig($toks, $i);
        if ($j < 0 || $toks[$j][1] !== '(') continue;
        // count top-level args
        $d = 0; $args = 1; $empty = true; $k = $j + 1; $start = $j + 1;
        for (; $k < $n; $k++) {
            $t = $toks[$k]; $s = $t[1];
            if ($t[0] === null && ($s === '(' || $s === '[' || $s === '{')) $d++;
            elseif ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES) $d++;
            elseif ($t[0] === null && ($s === ')' || $s === ']' || $s === '}')) { if ($d === 0) break; $d--; }
            elseif ($t[0] === null && $s === ',' && $d === 0) $args++;
            if (!php8_is_ws($t) && !($d === 0 && $s === ')')) $empty = false;
        }
        if ($empty) $args = 0;
        $fn = strtolower($text);
        $stats[$fn][$args] = ($stats[$fn][$args] ?? 0) + 1;
        $maxNormal = ['mysql_query'=>1,'mysql_fetch_array'=>1,'mysql_fetch_assoc'=>1,'mysql_num_rows'=>1,'mysql_result'=>2,'mysql_real_escape_string'=>1,'mysql_error'=>0,'mysql_numrows'=>1,'mysql_insert_id'=>0,'mysql_affected_rows'=>0];
        if ($args > ($maxNormal[$fn] ?? 99)) {
            $txt = '';
            for ($q = $i; $q <= $k; $q++) $txt .= $toks[$q][1];
            $extra[] = "$rel:$line  " . trim(preg_replace('/\s+/', ' ', mb_strimwidth($txt, 0, 150, '…', 'UTF-8')));
        }
    }
}
ksort($stats);
foreach ($stats as $fn => $d) { ksort($d); echo str_pad($fn, 28), json_encode($d), "\n"; }
echo "\n--- calls with more args than the plain form ---\n", implode("\n", $extra), "\n";
