<?php
// PHP 8 changed the precedence of "." relative to binary "+" and "-": "a" . $x + 1 used to be ("a" . $x) + 1 and is now
// "a" . ($x + 1). Lists expressions that mix "." with a binary "+"/"-" on the same nesting level without parentheses.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$splitters = ['=', ',', '?', ':', '<', '>', '&', '|', '^', '!', ';', '{', '}'];
$splitTokens = [T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_EQUAL, T_IS_NOT_IDENTICAL, T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL, T_BOOLEAN_AND, T_BOOLEAN_OR,
    T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR, T_SL, T_SR, T_DOUBLE_ARROW, T_COALESCE, T_CONCAT_EQUAL, T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL,
    T_OPEN_TAG, T_CLOSE_TAG, T_ECHO, T_PRINT, T_RETURN, T_AS, T_CASE, T_INSTANCEOF, T_SPACESHIP];
$operand = [T_VARIABLE, T_LNUMBER, T_DNUMBER, T_CONSTANT_ENCAPSED_STRING, T_STRING, T_ENCAPSED_AND_WHITESPACE];
$found = 0;
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $toks = php8_tokens(file_get_contents($f));
    $n = count($toks);
    $stack = [['dot' => false, 'pm' => false, 'line' => 0]];
    $inString = false;
    for ($i = 0; $i < $n; $i++) {
        $t = $toks[$i];
        if ($t[0] === null && $t[1] === '"') { $inString = !$inString; continue; }
        if ($inString) continue;
        if (php8_is_ws($t)) continue;
        $top = count($stack) - 1;
        $s = $t[1];
        if ($t[0] === null && ($s === '(' || $s === '[')) { $stack[] = ['dot' => false, 'pm' => false, 'line' => 0]; continue; }
        if ($t[0] === null && ($s === ')' || $s === ']')) { if (count($stack) > 1) array_pop($stack); continue; }
        if (($t[0] === null && in_array($s, $splitters, true)) || in_array($t[0], $splitTokens, true)) { $stack[$top] = ['dot' => false, 'pm' => false, 'line' => 0]; continue; }
        if ($t[0] === null && $s === '.') {
            $stack[$top]['dot'] = true; $stack[$top]['line'] = $stack[$top]['line'] ?: $t[2];
        } elseif ($t[0] === null && ($s === '+' || $s === '-')) {
            $p = php8_prev_sig($toks, $i);
            $pt = $p >= 0 ? $toks[$p] : [null, ''];
            $binary = in_array($pt[0], $operand, true) || ($pt[0] === null && ($pt[1] === ')' || $pt[1] === ']'));
            if ($binary) { $stack[$top]['pm'] = true; $stack[$top]['line'] = $stack[$top]['line'] ?: $t[2]; }
        }
        if ($stack[$top]['dot'] && $stack[$top]['pm']) {
            echo "$rel:{$t[2]}\n"; $found++;
            $stack[$top] = ['dot' => false, 'pm' => false, 'line' => 0];
        }
    }
}
echo "$found expression(s) mixing '.' and binary '+'/'-'\n";
