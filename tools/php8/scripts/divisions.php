<?php
// Lists "/" and "%" operations whose divisor is not a numeric literal: PHP 8 throws DivisionByZeroError for a zero
// divisor (PHP 5 only warned and returned false). Output: file:line  <source line>
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$total = 0; $byFile = [];
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $src = file_get_contents($f);
    $lines = explode("\n", str_replace("\r", '', $src));
    $toks = php8_tokens($src);
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        $t = $toks[$i];
        $isOp = ($t[0] === null && ($t[1] === '/' || $t[1] === '%')) || $t[0] === T_DIV_EQUAL || $t[0] === T_MOD_EQUAL;
        if (!$isOp) continue;
        $j = php8_next_sig($toks, $i);
        if ($j < 0) continue;
        // skip unary +/-
        while ($j >= 0 && in_array($toks[$j][1], ['-', '+'], true)) $j = php8_next_sig($toks, $j);
        $lit = $j >= 0 && in_array($toks[$j][0], [T_LNUMBER, T_DNUMBER], true) && (float)$toks[$j][1] != 0.0;
        if ($lit) continue;
        $line = $toks[$i][2];
        $byFile[$rel][] = [$line, trim($lines[$line - 1] ?? '')];
        $total++;
    }
}
echo "dynamic divisions/modulos: $total in ", count($byFile), " files\n";
foreach ($byFile as $rel => $items) {
    echo "\n## $rel (", count($items), ")\n";
    $last = -1;
    foreach ($items as [$line, $text]) { if ($line === $last) continue; $last = $line; echo "  $line: ", mb_substr($text, 0, 170, 'latin1'), "\n"; }
}
