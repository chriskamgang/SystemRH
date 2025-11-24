<?php

$migrationsPath = __DIR__ . '/database/migrations/';
$files = glob($migrationsPath . '2025_*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Supprimer les doubles });
    $content = preg_replace('/\n {4}\}\);\n {8}\}\);/', "\n        });", $content);

    // Assurer que chaque "up()" a bien une structure correcte
    $content = preg_replace('/(\n {4}\}\n\n {8}\}\);\n {4}\})/', "\n        });\n    }", $content);

    file_put_contents($file, $content);
    echo "✅ Processed: " . basename($file) . "\n";
}

echo "\n🎉 All done!\n";
