<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ManualPayrollController extends Controller
{
    /**
     * Afficher la page de calcul de paie manuelle
     */
    public function index()
    {
        return view('admin.manual-payroll.index');
    }

    /**
     * Calculer la paie
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'salaire_mensuel' => 'required|numeric|min:0',
            'jours_travailles' => 'required|numeric|min:0|max:31',
            'jours_total' => 'required|numeric|min:1|max:31',
            'prime' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'heures_retard' => 'nullable|numeric|min:0',
            'minutes_retard' => 'nullable|numeric|min:0|max:59',
        ], [
            'salaire_mensuel.required' => 'Le salaire mensuel est obligatoire',
            'salaire_mensuel.numeric' => 'Le salaire doit être un nombre',
            'jours_travailles.required' => 'Le nombre de jours travaillés est obligatoire',
            'jours_travailles.max' => 'Le nombre de jours ne peut pas dépasser 31',
            'jours_total.required' => 'Le nombre total de jours est obligatoire',
            'minutes_retard.max' => 'Les minutes ne peuvent pas dépasser 59',
        ]);

        // Calculs
        $salaireMensuel = (float) $validated['salaire_mensuel'];
        $joursTravailles = (float) $validated['jours_travailles'];
        $joursTotal = (float) $validated['jours_total'];
        $prime = (float) ($validated['prime'] ?? 0);
        $deduction = (float) ($validated['deduction'] ?? 0);
        $heuresRetard = (int) ($validated['heures_retard'] ?? 0);
        $minutesRetard = (int) ($validated['minutes_retard'] ?? 0);

        // Calcul du salaire journalier
        $salaireJournalier = $salaireMensuel / $joursTotal;

        // Calcul du salaire brut pour les jours travaillés
        $salaireBrut = $salaireJournalier * $joursTravailles;

        // Ajout des primes
        $salaireAvecPrime = $salaireBrut + $prime;

        // Calcul de la pénalité de retard (0,50 FCFA par seconde)
        $totalSecondesRetard = ($heuresRetard * 3600) + ($minutesRetard * 60);
        $penaliteRetard = $totalSecondesRetard * 0.50;
        $tempsRetardFormate = sprintf('%dh %dmin', $heuresRetard, $minutesRetard);

        // Application des déductions (retards + déductions manuelles)
        $salaireNet = $salaireAvecPrime - $penaliteRetard - $deduction;

        // Pourcentage de présence
        $pourcentagePresence = ($joursTravailles / $joursTotal) * 100;

        // Jours d'absence
        $joursAbsence = $joursTotal - $joursTravailles;

        // Montant perdu (absences)
        $montantPerdu = $salaireJournalier * $joursAbsence;

        return response()->json([
            'success' => true,
            'calcul' => [
                'salaire_mensuel' => number_format($salaireMensuel, 0, ',', ' '),
                'jours_total' => $joursTotal,
                'jours_travailles' => $joursTravailles,
                'jours_absence' => $joursAbsence,
                'salaire_journalier' => number_format($salaireJournalier, 2, ',', ' '),
                'salaire_brut' => number_format($salaireBrut, 0, ',', ' '),
                'prime' => number_format($prime, 0, ',', ' '),
                'salaire_avec_prime' => number_format($salaireAvecPrime, 0, ',', ' '),
                'penalite_retard' => number_format($penaliteRetard, 0, ',', ' '),
                'temps_retard_formate' => $tempsRetardFormate,
                'deduction' => number_format($deduction, 0, ',', ' '),
                'salaire_net' => number_format($salaireNet, 0, ',', ' '),
                'montant_perdu' => number_format($montantPerdu, 0, ',', ' '),
                'pourcentage_presence' => number_format($pourcentagePresence, 2, ',', ' '),
            ],
        ]);
    }

    /**
     * Calculer pour plusieurs employés
     */
    public function calculateBulk(Request $request)
    {
        $validated = $request->validate([
            'employes' => 'required|array',
            'employes.*.nom' => 'required|string',
            'employes.*.salaire_mensuel' => 'required|numeric|min:0',
            'employes.*.jours_travailles' => 'required|numeric|min:0|max:31',
            'employes.*.jours_total' => 'required|numeric|min:1|max:31',
            'employes.*.prime' => 'nullable|numeric|min:0',
            'employes.*.deduction' => 'nullable|numeric|min:0',
        ]);

        $resultats = [];

        foreach ($validated['employes'] as $employe) {
            $salaireMensuel = (float) $employe['salaire_mensuel'];
            $joursTravailles = (float) $employe['jours_travailles'];
            $joursTotal = (float) $employe['jours_total'];
            $prime = (float) ($employe['prime'] ?? 0);
            $deduction = (float) ($employe['deduction'] ?? 0);

            $salaireJournalier = $salaireMensuel / $joursTotal;
            $salaireBrut = $salaireJournalier * $joursTravailles;
            $salaireAvecPrime = $salaireBrut + $prime;
            $salaireNet = $salaireAvecPrime - $deduction;

            $resultats[] = [
                'nom' => $employe['nom'],
                'salaire_net' => $salaireNet,
                'salaire_net_formate' => number_format($salaireNet, 0, ',', ' '),
                'jours_travailles' => $joursTravailles,
                'salaire_mensuel' => $salaireMensuel,
            ];
        }

        return response()->json([
            'success' => true,
            'resultats' => $resultats,
            'total' => number_format(array_sum(array_column($resultats, 'salaire_net')), 0, ',', ' '),
        ]);
    }
}
