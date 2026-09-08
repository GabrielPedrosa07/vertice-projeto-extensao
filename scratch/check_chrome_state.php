<?php
$localAppData = getenv('LOCALAPPDATA');
$path = $localAppData . '/Google/Chrome/User Data/Local State';
echo "Checking path: $path\n";
if (file_exists($path)) {
    $data = json_decode(file_get_contents($path), true);
    echo "Found Local State!\n";
    $experiments = $data['browser']['enabled_labs_experiments'] ?? [];
    echo "Experiments: " . json_encode($experiments, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Not found\n";
}
