<?php

$migrationsPath = __DIR__ . '/database/migrations/';
$files = glob($migrationsPath . '2025_*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Pattern: trouver "    }" suivi de "    /**" et remplacer par "        });\n    }\n\n    /**"
    $pattern = '/(\n {4}}\n\n {4}\/\*\*)/';
    $replacement = "\n        });\n    }\n\n    /**";

    $newContent = preg_replace($pattern, $replacement, $content);

    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "✅ Fixed: " . basename($file) . "\n";
    } else {
        echo "⏭️  Skipped: " . basename($file) . "\n";
    }
}

echo "\n🎉 Done!\n";
