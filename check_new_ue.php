<?php
/**
 * Script pour vérifier quels fichiers contiennent de NOUVELLES UE
 * (non encore présentes en base de données)
 *
 * Usage : php check_new_ue.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UniteEnseignement;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UnitesEnseignementImport;

echo "🔍 Vérification des fichiers UE...\n\n";

$files = glob(__DIR__.'/../ue_part_*.xlsx');
sort($files);

echo "📦 " . count($files) . " fichiers à vérifier\n";
echo str_repeat("=", 80) . "\n\n";

$filesWithNewUE = [];
$totalNewUE = 0;

foreach ($files as $index => $file) {
    $filename = basename($file);
    $num = $index + 1;

    echo "[$num/" . count($files) . "] Vérification de $filename... ";

    try {
        $data = Excel::toArray(new UnitesEnseignementImport(), $file);
        $rows = $data[0] ?? [];

        $newCodes = [];

        foreach ($rows as $row) {
            $code = $row['code_ue'] ?? null;

            // Ignorer les lignes vides
            if (empty($code) || trim($code) == '') {
                continue;
            }

            // Vérifier si ce code existe déjà
            if (!UniteEnseignement::where('code_ue', $code)->exists()) {
                $newCodes[] = $code;
            }
        }

        $newCount = count($newCodes);

        if ($newCount > 0) {
            echo "✅ $newCount NOUVELLES UE trouvées\n";
            $filesWithNewUE[$filename] = $newCodes;
            $totalNewUE += $newCount;

            // Afficher les 5 premiers codes
            $sample = array_slice($newCodes, 0, 5);
            echo "    Exemples : " . implode(', ', $sample);
            if ($newCount > 5) {
                echo " (+" . ($newCount - 5) . " autres)";
            }
            echo "\n";
        } else {
            echo "⏭️  Déjà importé\n";
        }

    } catch (Exception $e) {
        echo "❌ ERREUR: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 RÉSUMÉ\n";
echo str_repeat("=", 80) . "\n";

if ($totalNewUE > 0) {
    echo "✅ $totalNewUE nouvelles UE trouvées dans " . count($filesWithNewUE) . " fichier(s)\n\n";

    echo "Fichiers à importer :\n";
    foreach ($filesWithNewUE as $filename => $codes) {
        echo "  • $filename (" . count($codes) . " UE)\n";
    }

    echo "\n💡 Pour importer ces fichiers, utilisez l'interface web ou le script reset_and_import_ue.php\n";
} else {
    echo "✅ Toutes les UE sont déjà importées en base de données !\n";
    echo "📈 Total en base : " . UniteEnseignement::count() . " UE\n";
}
