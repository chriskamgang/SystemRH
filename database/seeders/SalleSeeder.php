<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalleSeeder extends Seeder
{
    public function run(): void
    {
        // Données extraites du document "LES INFRASTRUCTURE DE RECEPTION"
        // Format: code / nombre_pc / capacite
        $sallesParCampus = [
            'A' => [
                ['code' => 'AMPLI', 'nom' => 'Amphithéâtre A', 'pc' => 0, 'capacite' => 60, 'type' => 'amphi'],
                ['code' => 'A11', 'nom' => 'Salle A11', 'pc' => 0, 'capacite' => 40, 'type' => 'cours'],
                ['code' => 'A210', 'nom' => 'Salle A210', 'pc' => 0, 'capacite' => 32, 'type' => 'cours'],
                ['code' => 'A212', 'nom' => 'Salle A212', 'pc' => 0, 'capacite' => 15, 'type' => 'cours'],
                ['code' => 'A213', 'nom' => 'Salle A213', 'pc' => 0, 'capacite' => 15, 'type' => 'cours'],
                ['code' => 'A312', 'nom' => 'Salle A312', 'pc' => 0, 'capacite' => 28, 'type' => 'cours'],
                ['code' => 'A313', 'nom' => 'Salle A313', 'pc' => 0, 'capacite' => 10, 'type' => 'cours'],
                ['code' => 'A314', 'nom' => 'Salle A314', 'pc' => 0, 'capacite' => 14, 'type' => 'cours'],
                ['code' => 'A311', 'nom' => 'Salle A311', 'pc' => 0, 'capacite' => 15, 'type' => 'cours'],
                ['code' => 'A310', 'nom' => 'Salle A310', 'pc' => 0, 'capacite' => 15, 'type' => 'cours'],
            ],
            'B' => [
                ['code' => 'B110', 'nom' => 'Salle B110', 'pc' => 0, 'capacite' => 48, 'type' => 'cours'],
                ['code' => 'B-LABO-INFO', 'nom' => 'Labo Informatique B', 'pc' => 0, 'capacite' => 48, 'type' => 'labo_info'],
                ['code' => 'B210', 'nom' => 'Salle B210', 'pc' => 0, 'capacite' => 50, 'type' => 'cours'],
                ['code' => 'B-ANCIEN-REPRO', 'nom' => 'Ancien Repro B', 'pc' => 0, 'capacite' => 48, 'type' => 'cours'],
                ['code' => 'B-BIBLIO', 'nom' => 'Bibliothèque B', 'pc' => 0, 'capacite' => 20, 'type' => 'autre'],
                ['code' => 'B310', 'nom' => 'Salle B310', 'pc' => 0, 'capacite' => 25, 'type' => 'cours'],
                ['code' => 'B311a', 'nom' => 'Salle B311a', 'pc' => 0, 'capacite' => 12, 'type' => 'cours'],
                ['code' => 'B311b', 'nom' => 'Salle B311b', 'pc' => 0, 'capacite' => 25, 'type' => 'cours'],
                ['code' => 'B312', 'nom' => 'Salle B312', 'pc' => 0, 'capacite' => 50, 'type' => 'cours'],
            ],
            'E' => [
                ['code' => 'E01', 'nom' => 'Salle E01', 'pc' => 0, 'capacite' => 40, 'type' => 'cours'],
                ['code' => 'E02', 'nom' => 'Salle E02', 'pc' => 0, 'capacite' => 20, 'type' => 'cours'],
                ['code' => 'E03', 'nom' => 'Salle E03', 'pc' => 0, 'capacite' => 10, 'type' => 'cours'],
                ['code' => 'E04', 'nom' => 'Salle E04', 'pc' => 0, 'capacite' => 30, 'type' => 'cours'],
                ['code' => 'E05', 'nom' => 'Salle E05', 'pc' => 0, 'capacite' => 10, 'type' => 'cours'],
            ],
            'F' => [
                ['code' => 'FNIV1', 'nom' => 'Salle F Niveau 1', 'pc' => 0, 'capacite' => 100, 'type' => 'cours'],
                ['code' => 'FNIV2a', 'nom' => 'Salle F Niveau 2a', 'pc' => 28, 'capacite' => 54, 'type' => 'labo_info'],
                ['code' => 'FNIV2b', 'nom' => 'Salle F Niveau 2b (RS)', 'pc' => 30, 'capacite' => 30, 'type' => 'labo_info'],
                ['code' => 'FNIV3', 'nom' => 'Salle F Niveau 3', 'pc' => 0, 'capacite' => 44, 'type' => 'cours'],
                ['code' => 'FNIV4', 'nom' => 'Salle F Niveau 4', 'pc' => 0, 'capacite' => 40, 'type' => 'cours'],
                ['code' => 'FNIV5', 'nom' => 'Salle F Niveau 5', 'pc' => 0, 'capacite' => 80, 'type' => 'cours'],
            ],
            'C' => [
                ['code' => 'C-CANTINE', 'nom' => 'Cantine C', 'pc' => 0, 'capacite' => 42, 'type' => 'cantine'],
                ['code' => 'C-LABO-ET', 'nom' => 'Labo ET C', 'pc' => 0, 'capacite' => 62, 'type' => 'labo_sciences'],
                ['code' => 'C-LABO-SF', 'nom' => 'Labo SF C', 'pc' => 0, 'capacite' => 50, 'type' => 'labo_sciences'],
                ['code' => 'C-LABO-INFO', 'nom' => 'Labo Informatique C', 'pc' => 0, 'capacite' => 50, 'type' => 'labo_info'],
                ['code' => 'C-LABO-TL', 'nom' => 'Labo TL C', 'pc' => 0, 'capacite' => 20, 'type' => 'labo_sciences'],
                ['code' => 'C109', 'nom' => 'Salle C109', 'pc' => 0, 'capacite' => 12, 'type' => 'cours'],
                ['code' => 'C110', 'nom' => 'Salle C110', 'pc' => 0, 'capacite' => 50, 'type' => 'cours'],
                ['code' => 'C111', 'nom' => 'Salle C111', 'pc' => 0, 'capacite' => 50, 'type' => 'cours'],
                ['code' => 'C112', 'nom' => 'Salle C112', 'pc' => 0, 'capacite' => 70, 'type' => 'cours'],
                ['code' => 'C210', 'nom' => 'Salle C210', 'pc' => 0, 'capacite' => 120, 'type' => 'cours'],
                ['code' => 'C211', 'nom' => 'Salle C211', 'pc' => 0, 'capacite' => 140, 'type' => 'cours'],
                ['code' => 'C312', 'nom' => 'Salle C312', 'pc' => 0, 'capacite' => 10, 'type' => 'cours'],
                ['code' => 'C311', 'nom' => 'Salle C311', 'pc' => 0, 'capacite' => 120, 'type' => 'cours'],
                ['code' => 'C310', 'nom' => 'Salle C310', 'pc' => 0, 'capacite' => 130, 'type' => 'cours'],
                ['code' => 'C410', 'nom' => 'Salle C410', 'pc' => 0, 'capacite' => 140, 'type' => 'cours'],
                ['code' => 'C411', 'nom' => 'Salle C411', 'pc' => 0, 'capacite' => 120, 'type' => 'cours'],
                ['code' => 'C412', 'nom' => 'Salle C412', 'pc' => 0, 'capacite' => 16, 'type' => 'cours'],
                ['code' => 'C510', 'nom' => 'Salle C510', 'pc' => 0, 'capacite' => 60, 'type' => 'cours'],
                ['code' => 'C511', 'nom' => 'Salle C511', 'pc' => 0, 'capacite' => 120, 'type' => 'cours'],
                ['code' => 'C512', 'nom' => 'Salle C512', 'pc' => 0, 'capacite' => 100, 'type' => 'cours'],
                ['code' => 'C610', 'nom' => 'Salle C610', 'pc' => 0, 'capacite' => 60, 'type' => 'cours'],
                ['code' => 'C611', 'nom' => 'Salle C611', 'pc' => 0, 'capacite' => 120, 'type' => 'cours'],
                ['code' => 'C612', 'nom' => 'Salle C612', 'pc' => 0, 'capacite' => 100, 'type' => 'cours'],
                ['code' => 'C710', 'nom' => 'Salle C710', 'pc' => 0, 'capacite' => 60, 'type' => 'cours'],
                ['code' => 'C711', 'nom' => 'Salle C711', 'pc' => 0, 'capacite' => 120, 'type' => 'cours'],
                ['code' => 'C712', 'nom' => 'Salle C712', 'pc' => 0, 'capacite' => 40, 'type' => 'cours'],
                ['code' => 'C810', 'nom' => 'Salle C810', 'pc' => 0, 'capacite' => 60, 'type' => 'cours'],
                ['code' => 'C811', 'nom' => 'Salle C811', 'pc' => 0, 'capacite' => 120, 'type' => 'cours'],
                ['code' => 'C812', 'nom' => 'Salle C812', 'pc' => 0, 'capacite' => 100, 'type' => 'cours'],
                ['code' => 'C-AMPHI', 'nom' => 'Amphithéâtre C', 'pc' => 0, 'capacite' => 350, 'type' => 'amphi'],
            ],
        ];

        // Récupérer les campus existants par code (lettre du campus)
        $campuses = Campus::all();
        $campusMap = [];
        foreach ($campuses as $campus) {
            // Essayer de matcher par le code ou le nom du campus
            foreach (array_keys($sallesParCampus) as $letter) {
                if (
                    stripos($campus->code, $letter) !== false ||
                    stripos($campus->name, "Campus $letter") !== false ||
                    stripos($campus->name, "campus $letter") !== false
                ) {
                    $campusMap[$letter] = $campus->id;
                }
            }
        }

        // Si aucun campus n'est trouvé, créer des campus pour chaque bâtiment
        $sallesInserted = 0;
        $now = now();

        foreach ($sallesParCampus as $letter => $salles) {
            $campusId = $campusMap[$letter] ?? null;

            if (!$campusId) {
                $campus = Campus::create([
                    'name' => "Campus $letter",
                    'code' => "CAMPUS-$letter",
                    'address' => "INSAM - Bâtiment $letter",
                    'latitude' => 5.4781,
                    'longitude' => 10.4178,
                    'radius' => 500,
                    'start_time' => '07:30:00',
                    'end_time' => '17:30:00',
                    'late_tolerance' => 15,
                    'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                    'is_active' => true,
                ]);
                $campusId = $campus->id;
                $this->command->info("Campus $letter créé (ID: $campusId)");
            }

            foreach ($salles as $salle) {
                DB::table('salles')->updateOrInsert(
                    ['code' => $salle['code']],
                    [
                        'campus_id' => $campusId,
                        'nom' => $salle['nom'],
                        'code' => $salle['code'],
                        'nombre_pc' => $salle['pc'],
                        'capacite' => $salle['capacite'],
                        'type' => $salle['type'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
                $sallesInserted++;
            }
        }

        $this->command->info("$sallesInserted salles importées avec succès");
    }
}
