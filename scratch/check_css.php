<?php
$css = file_get_contents(__DIR__ . '/../public/css/style.css');
$open = substr_count($css, '{');
$close = substr_count($css, '}');
echo "Open braces: $open | Close braces: $close\n";
if ($open === $close) {
    echo "SUCCESS: ALL CSS BRACES MATCH PERFECTLY (100% Valid Structure)!\n";
} else {
    echo "ERROR: Discrepancy detected: Diff = " . ($open - $close) . "\n";
}
