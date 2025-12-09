<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================================\n";
echo "  SUPPRESSION DES EMPLOYÉS (PERMANENTS, VACATAIRES, SEMI-PERMANENTS)\n";
echo "========================================================\n\n";

try {
    // Compter chaque type
    $permanents = DB::table('users')->where('employee_type', 'enseignant_titulaire')->count();
    $vacataires = DB::table('users')->where('employee_type', 'enseignant_vacataire')->count();
    $semiPermanents = DB::table('users')->where('employee_type', 'semi_permanent')->count();

    echo "📊 État actuel de la base de données:\n";
    echo "  • Permanents: {$permanents}\n";
    echo "  • Vacataires: {$vacataires}\n";
    echo "  • Semi-permanents: {$semiPermanents}\n";
    echo "  • TOTAL: " . ($permanents + $vacataires + $semiPermanents) . "\n\n";

    if (($permanents + $vacataires + $semiPermanents) === 0) {
        echo "✅ Aucun employé à supprimer.\n\n";
        exit(0);
    }

    // Récupérer tous les IDs
    $allIds = DB::table('users')
        ->whereIn('employee_type', ['enseignant_titulaire', 'enseignant_vacataire', 'semi_permanent'])
        ->pluck('id')
        ->toArray();

    echo "🔍 Suppression des relations pour {$allIds ? count($allIds) : 0} employé(s)...\n";

    // Supprimer les relations campus
    try {
        $deletedCampus = DB::table('user_campus')->whereIn('user_id', $allIds)->delete();
        if ($deletedCampus > 0) {
            echo "  ✓ user_campus: {$deletedCampus} relation(s)\n";
        }
    } catch (\Exception $e) {}

    // Supprimer les shifts
    try {
        $deletedShifts = DB::table('user_campus_shifts')->whereIn('user_id', $allIds)->delete();
        if ($deletedShifts > 0) {
            echo "  ✓ user_campus_shifts: {$deletedShifts} shift(s)\n";
        }
    } catch (\Exception $e) {}

    // Supprimer les attendances
    try {
        $deletedAttendances = DB::table('attendances')->whereIn('user_id', $allIds)->delete();
        if ($deletedAttendances > 0) {
            echo "  ✓ attendances: {$deletedAttendances} pointage(s)\n";
        }
    } catch (\Exception $e) {}

    // Supprimer les tardiness
    try {
        $deletedTardiness = DB::table('tardiness')->whereIn('user_id', $allIds)->delete();
        if ($deletedTardiness > 0) {
            echo "  ✓ tardiness: {$deletedTardiness} retard(s)\n";
        }
    } catch (\Exception $e) {}

    // Supprimer les absences
    try {
        $deletedAbsences = DB::table('absences')->whereIn('user_id', $allIds)->delete();
        if ($deletedAbsences > 0) {
            echo "  ✓ absences: {$deletedAbsences} absence(s)\n";
        }
    } catch (\Exception $e) {}

    // Supprimer les UE (unités d'enseignement) pour les vacataires et semi-permanents
    try {
        $deletedUE = DB::table('unites_enseignement')->whereIn('enseignant_id', $allIds)->delete();
        if ($deletedUE > 0) {
            echo "  ✓ unites_enseignement: {$deletedUE} UE\n";
        }
    } catch (\Exception $e) {}

    // Supprimer les presence_incidents
    try {
        $deletedIncidents = DB::table('presence_incidents')->whereIn('user_id', $allIds)->delete();
        if ($deletedIncidents > 0) {
            echo "  ✓ presence_incidents: {$deletedIncidents} incident(s)\n";
        }
    } catch (\Exception $e) {}

    echo "\n🗑️  Suppression des employés par type...\n";

    // Supprimer chaque type
    $deletedPermanents = DB::table('users')->where('employee_type', 'enseignant_titulaire')->delete();
    if ($deletedPermanents > 0) {
        echo "  ✓ Permanents: {$deletedPermanents}\n";
    }

    $deletedVacataires = DB::table('users')->where('employee_type', 'enseignant_vacataire')->delete();
    if ($deletedVacataires > 0) {
        echo "  ✓ Vacataires: {$deletedVacataires}\n";
    }

    $deletedSemiPerm = DB::table('users')->where('employee_type', 'semi_permanent')->delete();
    if ($deletedSemiPerm > 0) {
        echo "  ✓ Semi-permanents: {$deletedSemiPerm}\n";
    }

    $totalDeleted = $deletedPermanents + $deletedVacataires + $deletedSemiPerm;

    echo "\n";
    echo "========================================================\n";
    echo "✅ {$totalDeleted} EMPLOYÉ(S) SUPPRIMÉ(S) AU TOTAL\n";
    echo "========================================================\n";
    echo "  • Permanents: {$deletedPermanents}\n";
    echo "  • Vacataires: {$deletedVacataires}\n";
    echo "  • Semi-permanents: {$deletedSemiPerm}\n";
    echo "========================================================\n";
    echo "\n";
    echo "✨ Vous pouvez maintenant importer vos fichiers Excel.\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n\n";
    exit(1);
}
