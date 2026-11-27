<?php
// PHP 8.2 deprecates creating properties that were not declared in the class ("Creation of dynamic property").
// Lists $this->name assignments for names that the class does not declare.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$found = 0;
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $toks = php8_tokens(file_get_contents($f));
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] !== T_CLASS) continue;
        $j = php8_next_sig($toks, $i);
        if ($j < 0 || $toks[$j][0] !== T_STRING) continue;
        $cls = $toks[$j][1];
        // class body extent
        $k = $j; while ($k < $n && !($toks[$k][0] === null && $toks[$k][1] === '{')) $k++;
        $depth = 0; $end = $k;
        for ($m = $k; $m < $n; $m++) {
            if ($toks[$m][0] === null && $toks[$m][1] === '{') $depth++;
            elseif ($toks[$m][0] === T_CURLY_OPEN || $toks[$m][0] === T_DOLLAR_OPEN_CURLY_BRACES) $depth++;
            elseif ($toks[$m][0] === null && $toks[$m][1] === '}') { $depth--; if ($depth === 0) { $end = $m; break; } }
        }
        $declared = []; $assigned = [];
        // properties: var/public/protected/private/static followed by $name (at class-body depth, outside functions)
        $d = 0; $inFunc = 0;
        for ($m = $k; $m <= $end; $m++) {
            $t = $toks[$m];
            if ($t[0] === null && $t[1] === '{') $d++;
            if ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES) $d++;
            if ($t[0] === null && $t[1] === '}') $d--;
            if (in_array($t[0], [T_VAR, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC], true) && $d === 1) {
                $p = php8_next_sig($toks, $m);
                while ($p >= 0 && in_array($toks[$p][0], [T_STATIC, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR, T_READONLY ?? -1], true)) $p = php8_next_sig($toks, $p);
                if ($p >= 0 && $toks[$p][0] === T_VARIABLE) $declared[ltrim($toks[$p][1], '$')] = true;
            }
            if ($t[0] === T_VARIABLE && $t[1] === '$this') {
                $a = php8_next_sig($toks, $m);
                if ($a >= 0 && $toks[$a][0] === T_OBJECT_OPERATOR) {
                    $b = php8_next_sig($toks, $a);
                    $c = $b >= 0 ? php8_next_sig($toks, $b) : -1;
                    if ($b >= 0 && $toks[$b][0] === T_STRING && $c >= 0 && ($toks[$c][1] === '=' || $toks[$c][1] === '[' || preg_match('/^(\.|\+|-|\*|\/)?=$/', $toks[$c][1]))) $assigned[$toks[$b][1]] = $toks[$b][2];
                }
            }
        }
        foreach ($assigned as $name => $line) if (!isset($declared[$name])) { echo "$rel:$line  class $cls: \$this->$name is not declared\n"; $found++; }
    }
}
echo "$found undeclared property assignment(s)\n";
