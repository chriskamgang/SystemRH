<?php

/**
 * Script pour nettoyer les doublons dans la table unites_enseignement
 *
 * Usage: php fix_duplicates_ue.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Recherche des doublons dans unites_enseignement...\n\n";

// Trouver les doublons
$duplicates = DB::select("
    SELECT code_ue, nom_matiere, COUNT(*) as count
    FROM unites_enseignement
    GROUP BY code_ue, nom_matiere
    HAVING count > 1
");

if (empty($duplicates)) {
    echo "✅ Aucun doublon trouvé!\n";
    exit(0);
}

echo "⚠️  " . count($duplicates) . " groupe(s) de doublons trouvé(s):\n\n";

foreach ($duplicates as $dup) {
    echo "  - {$dup->code_ue} - {$dup->nom_matiere} ({$dup->count} entrées)\n";
}

echo "\n📋 Détails des doublons:\n\n";

// Pour chaque groupe de doublons
foreach ($duplicates as $dup) {
    echo "----------------------------------------\n";
    echo "Code UE: {$dup->code_ue}\n";
    echo "Matière: {$dup->nom_matiere}\n";
    echo "Nombre de doublons: {$dup->count}\n\n";

    // Récupérer tous les enregistrements pour ce doublon
    $records = DB::table('unites_enseignement')
        ->where('code_ue', $dup->code_ue)
        ->where('nom_matiere', $dup->nom_matiere)
        ->orderBy('created_at', 'asc')
        ->get();

    echo "Enregistrements:\n";
    foreach ($records as $i => $record) {
        $enseignant = DB::table('users')->where('id', $record->enseignant_id)->first();
        $enseignantName = $enseignant ? $enseignant->first_name . ' ' . $enseignant->last_name : 'N/A';

        echo sprintf(
            "  %d. ID: %d | Enseignant: %s | Statut: %s | Créé: %s | Heures validées: %s\n",
            $i + 1,
            $record->id,
            $enseignantName,
            $record->statut,
            $record->created_at,
            $record->heures_effectuees_validees ?? '0'
        );
    }

    // Stratégie: Garder le premier créé, supprimer les autres SAUF s'ils ont des heures validées
    echo "\n💡 Stratégie de nettoyage:\n";

    $toKeep = null;
    $toDelete = [];

    foreach ($records as $i => $record) {
        $hasValidatedHours = isset($record->heures_effectuees_validees) && $record->heures_effectuees_validees > 0;
        $hasPayments = DB::table('vacataire_payment_details')
            ->where('unite_enseignement_id', $record->id)
            ->exists();

        if ($hasValidatedHours || $hasPayments) {
            if (!$toKeep) {
                $toKeep = $record;
                echo "  ✅ GARDER ID {$record->id} (a des heures validées ou paiements)\n";
            } else {
                // Fusionner les données si possible
                echo "  ⚠️  ID {$record->id} a aussi des données - nécessite fusion manuelle\n";
                $toDelete[] = $record->id;
            }
        } else {
            if (!$toKeep && $i === 0) {
                $toKeep = $record;
                echo "  ✅ GARDER ID {$record->id} (premier créé)\n";
            } else {
                $toDelete[] = $record->id;
                echo "  ❌ SUPPRIMER ID {$record->id}\n";
            }
        }
    }

    echo "\n";
}

echo "\n⚠️  ATTENTION: Ce script va supprimer les doublons!\n";
echo "Voulez-vous continuer? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim(strtolower($line));
fclose($handle);

if ($answer !== 'yes') {
    echo "\n❌ Annulé par l'utilisateur.\n";
    exit(0);
}

echo "\n🔧 Nettoyage des doublons...\n\n";

DB::beginTransaction();

try {
    $totalDeleted = 0;

    foreach ($duplicates as $dup) {
        $records = DB::table('unites_enseignement')
            ->where('code_ue', $dup->code_ue)
            ->where('nom_matiere', $dup->nom_matiere)
            ->orderBy('created_at', 'asc')
            ->get();

        // Garder le premier, supprimer les autres (sauf s'ils ont des données importantes)
        $first = true;
        foreach ($records as $record) {
            if ($first) {
                $first = false;
                continue; // Garder le premier
            }

            // Vérifier s'il a des données importantes
            $hasPayments = DB::table('vacataire_payment_details')
                ->where('unite_enseignement_id', $record->id)
                ->exists();

            $hasIncidents = DB::table('presence_incidents')
                ->where('unite_enseignement_id', $record->id)
                ->exists();

            if (!$hasPayments && !$hasIncidents) {
                // Supprimer en toute sécurité
                DB::table('unites_enseignement')->where('id', $record->id)->delete();
                echo "  ✅ Supprimé ID {$record->id} ({$dup->code_ue} - {$dup->nom_matiere})\n";
                $totalDeleted++;
            } else {
                echo "  ⚠️  Conservé ID {$record->id} ({$dup->code_ue} - {$dup->nom_matiere}) - a des données liées\n";
            }
        }
    }

    DB::commit();

    echo "\n✅ Nettoyage terminé! {$totalDeleted} enregistrement(s) supprimé(s).\n";
    echo "\n💡 Vous pouvez maintenant relancer: php artisan migrate\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Erreur lors du nettoyage: " . $e->getMessage() . "\n";
    exit(1);
}
