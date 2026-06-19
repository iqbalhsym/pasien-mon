<?php
$dir = 'c:/laragon/www/caseman-mon';

function search_in_dir($path, $pattern) {
    if (!is_dir($path)) return;
    $files = scandir($path);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = $path . '/' . $file;
        if (is_dir($fullPath)) {
            // Exclude common large folders
            if ($file === 'vendor' || $file === 'node_modules' || $file === 'storage') continue;
            search_in_dir($fullPath, $pattern);
        } else {
            if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($fullPath);
                if (str_contains($content, $pattern)) {
                    echo "Found in {$fullPath}:\n";
                    $lines = explode("\n", $content);
                    foreach ($lines as $i => $line) {
                        if (str_contains($line, $pattern)) {
                            echo "  Line " . ($i + 1) . ": " . trim($line) . "\n";
                        }
                    }
                    echo "\n";
                }
            }
        }
    }
}

echo "Searching for 'getPatientByNoRM' in {$dir}...\n";
search_in_dir($dir, 'getPatientByNoRM');
