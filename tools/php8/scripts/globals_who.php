<?php
// helper: re-run globals_entry analysis but print entry names for selected variables
$_SERVER['argv'] = [$argv[0], $argv[1] ?? '/work'];
ob_start(); include __DIR__ . '/globals_entry.php'; ob_end_clean();
foreach (array_slice($argv, 2) as $v) {
    echo "\$$v  entries: ", implode(', ', array_keys($unbound[$v] ?? [])), "\n";
}
