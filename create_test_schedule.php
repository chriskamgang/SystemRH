<?php

use App\Models\UniteEnseignement;
use App\Models\UeSchedule;
use App\Models\Campus;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$campus = Campus::first();
if (!$campus) {
    echo "❌ Aucun campus trouvé. Créez un campus d'abord.\n";
    exit;
}

$enseignant = User::where('employee_type', '!=', 'etudiant')->first();

// 1. Créer une UE pour Informatique L3
$ue = UniteEnseignement::create([
    'code_ue' => 'INF301',
    'nom_matiere' => 'Programmation Mobile Flutter',
    'specialite' => 'Informatique',
    'niveau' => 'Licence 3',
    'volume_horaire_total' => 45,
    'statut' => 'activee',
    'enseignant_id' => $enseignant ? $enseignant->id : null,
]);

// 2. Créer un créneau pour AUJOURD'HUI
$jours = [
    1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 
    4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche'
];
$aujourdhui = $jours[date('N')];

UeSchedule::create([
    'unite_enseignement_id' => $ue->id,
    'campus_id' => $campus->id,
    'jour_semaine' => $aujourdhui,
    'heure_debut' => '08:00:00',
    'heure_fin' => '12:00:00',
    'salle' => 'Salle B12',
    'is_active' => true,
]);

echo "✅ Cours de test créé pour Informatique Licence 3 aujourd'hui ($aujourdhui) !\n";
