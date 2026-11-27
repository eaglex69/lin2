<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$decl = []; $gassigned = [];
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $toks = php8_tokens(file_get_contents($f));
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] === T_GLOBAL) {
            for ($k = $i + 1; $k < $n && $toks[$k][1] !== ';'; $k++) if ($toks[$k][0] === T_VARIABLE) $decl[substr($toks[$k][1], 1)][$rel . ':' . $toks[$k][2]] = 1;
        }
        if ($toks[$i][0] === T_VARIABLE && $toks[$i][1] === '$GLOBALS') {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][1] === '[') { $k = php8_next_sig($toks, $j); if ($k >= 0 && $toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $decl[trim($toks[$k][1], "'\"")]['GLOBALS@' . $rel . ':' . $toks[$k][2]] = 1; }
        }
    }
}
echo "names used via `global`/\$GLOBALS in functions: ", count($decl), "\n";
foreach ($decl as $name => $locs) echo str_pad('$' . $name, 20), implode(' ', array_slice(array_keys($locs), 0, 3)), "\n";
