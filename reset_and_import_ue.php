<?php
/**
 * Script pour réinitialiser et réimporter toutes les UE
 *
 * ATTENTION : Ce script va SUPPRIMER toutes les UE existantes !
 *
 * Usage : php reset_and_import_ue.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UniteEnseignement;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UnitesEnseignementImport;

echo "⚠️  ATTENTION : Ce script va supprimer toutes les UE existantes et réimporter.\n";
echo "Appuyez sur ENTER pour continuer ou CTRL+C pour annuler...\n";
fgets(STDIN);

// Étape 1 : Supprimer toutes les UE
echo "\n🗑️  Suppression des UE existantes...\n";
$count = UniteEnseignement::count();
UniteEnseignement::truncate();
echo "✅ $count UE supprimées\n";

// Étape 2 : Importer tous les fichiers ue_part_*.xlsx
$files = glob(__DIR__.'/../ue_part_*.xlsx');
sort($files);

echo "\n📦 " . count($files) . " fichiers trouvés\n";
echo "🚀 Début de l'import...\n\n";

$totalImported = 0;
$totalSkipped = 0;
$totalErrors = 0;

foreach ($files as $index => $file) {
    $filename = basename($file);
    $num = $index + 1;

    echo "[$num/" . count($files) . "] Import de $filename... ";

    try {
        $import = new UnitesEnseignementImport();
        Excel::import($import, $file);

        $imported = $import->getRowCount();
        $skipped = $import->getSkippedCount();
        $errors = count($import->failures());

        $totalImported += $imported;
        $totalSkipped += $skipped;
        $totalErrors += $errors;

        echo "✅ $imported importées, $skipped ignorées, $errors erreurs\n";

    } catch (Exception $e) {
        echo "❌ ERREUR: " . $e->getMessage() . "\n";
        $totalErrors++;
    }

    // Petite pause pour éviter surcharge
    usleep(100000); // 0.1 seconde
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ FINAL\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Total importé  : $totalImported UE\n";
echo "⏭️  Total ignoré   : $totalSkipped UE\n";
echo "❌ Total erreurs  : $totalErrors\n";
echo "\n🎉 Import terminé !\n";

// Vérification finale
$finalCount = UniteEnseignement::count();
echo "\n📈 Nombre total d'UE en base : $finalCount\n";
