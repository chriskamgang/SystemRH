<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\UeSchedule;
use App\Models\UniteEnseignement;
use Illuminate\Database\Seeder;

/**
 * Jeu d'essai pour l'emploi du temps de l'etudiant INSAM BUS.
 *
 * L'etudiant du transport est aussi un etudiant de l'ecole : une fois son
 * niveau et sa specialite renseignes, l'API `/api/emploi-du-temps/*` lui
 * rend ses creneaux. Ce seeder pose de quoi le verifier de bout en bout —
 * un campus, des unites d'enseignement et leurs horaires.
 *
 * Il ne touche a rien de l'espace employe : ni comptes, ni pointage, ni
 * paie. Il n'ajoute que des UE et des creneaux.
 */
class EmploiDuTempsEtudiantSeeder extends Seeder
{
    /** Les deux specialites du jeu d'essai. */
    private const SPECIALITES = ['Genie Logiciel', 'Reseaux et Telecoms'];

    private const NIVEAU = 'Licence 1';

    public function run(): void
    {
        $campus = $this->campus();

        // --- Unites de specialite -----------------------------------------

        $ues = [
            ['Genie Logiciel', 'GL101', 'Algorithmique et structures de donnees'],
            ['Genie Logiciel', 'GL102', 'Programmation orientee objet'],
            ['Reseaux et Telecoms', 'RT101', 'Architecture des reseaux'],
            ['Reseaux et Telecoms', 'RT102', 'Transmission de donnees'],
        ];

        foreach ($ues as [$specialite, $code, $matiere]) {
            $this->ue($code, $matiere, $specialite, 'specialite');
        }

        // --- Tronc commun --------------------------------------------------
        //
        // Suivi par les deux specialites : `groupes` porte la liste, que
        // lit le scope pourSpecialite d'UniteEnseignement.

        $this->ue('TC101', 'Mathematiques generales', null, 'tronc_commun', self::SPECIALITES);
        $this->ue('TC102', 'Anglais technique', null, 'tronc_commun', self::SPECIALITES);

        // --- Creneaux ------------------------------------------------------

        $creneaux = [
            ['GL101', 'lundi',    '08:00:00', '11:00:00', 'Salle A1'],
            ['GL102', 'mardi',    '08:00:00', '11:00:00', 'Salle A1'],
            ['RT101', 'lundi',    '08:00:00', '11:00:00', 'Salle B2'],
            ['RT102', 'mercredi', '14:00:00', '17:00:00', 'Salle B2'],
            ['TC101', 'jeudi',    '08:00:00', '10:00:00', 'Amphi 1'],
            ['TC102', 'vendredi', '10:00:00', '12:00:00', 'Amphi 1'],
        ];

        foreach ($creneaux as [$code, $jour, $debut, $fin, $salle]) {
            $ue = UniteEnseignement::withoutGlobalScopes()
                ->where('code_ue', $code)
                ->first();

            if (! $ue) {
                continue;
            }

            UeSchedule::updateOrCreate(
                [
                    'unite_enseignement_id' => $ue->id,
                    'jour_semaine' => $jour,
                    'heure_debut' => $debut,
                ],
                [
                    'campus_id' => $campus->id,
                    'heure_fin' => $fin,
                    'salle' => $salle,
                    'is_active' => true,
                ],
            );
        }

        $this->command->info(sprintf(
            'Emploi du temps d\'essai : %d UE, %d creneaux, niveau « %s ».',
            UniteEnseignement::withoutGlobalScopes()->count(),
            UeSchedule::count(),
            self::NIVEAU,
        ));
    }

    private function campus(): Campus
    {
        return Campus::withoutGlobalScopes()->firstOrCreate(
            ['code' => 'INSAM-A'],
            [
                'name' => 'Campus INSAM Bafoussam',
                'address' => 'Bafoussam, Cameroun',
                'latitude' => 5.4781,
                'longitude' => 10.4179,
                'radius' => 200,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  list<string>|null  $groupes  Specialites concernees par un
     *                                      tronc commun.
     */
    private function ue(
        string $code,
        string $matiere,
        ?string $specialite,
        string $type,
        ?array $groupes = null,
    ): UniteEnseignement {
        return UniteEnseignement::withoutGlobalScopes()->updateOrCreate(
            ['code_ue' => $code],
            [
                'nom_matiere' => $matiere,
                'niveau' => self::NIVEAU,
                'specialite' => $specialite,
                'type_ue' => $type,
                'groupes' => $groupes,
                'volume_horaire_total' => 60,
                // Seules les UE activees remontent dans l'emploi du temps.
                'statut' => 'activee',
                'date_attribution' => now(),
                'date_activation' => now(),
            ],
        );
    }
}
