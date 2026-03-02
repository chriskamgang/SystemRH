<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ManualPayrollAdjustment;
use Illuminate\Http\Request;

class ManualPayrollController extends Controller
{
    /**
     * Afficher la page de calcul de paie manuelle
     */
    public function index()
    {
        // Récupérer uniquement les employés permanents et semi-permanents
        $employees = User::whereIn('employee_type', ['enseignant_titulaire', 'semi_permanent', 'administratif', 'technique', 'direction'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'employee_id', 'employee_type', 'monthly_salary']);

        return view('admin.manual-payroll.index', compact('employees'));
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
            'minutes_retard' => 'nullable|numeric|min:0',
        ], [
            'salaire_mensuel.required' => 'Le salaire mensuel est obligatoire',
            'salaire_mensuel.numeric' => 'Le salaire doit être un nombre',
            'jours_travailles.required' => 'Le nombre de jours travaillés est obligatoire',
            'jours_travailles.max' => 'Le nombre de jours ne peut pas dépasser 31',
            'jours_total.required' => 'Le nombre total de jours est obligatoire',
        ]);

        // Calculs
        $salaireMensuel = (float) $validated['salaire_mensuel'];
        $joursTravailles = (float) $validated['jours_travailles'];
        $joursTotal = (float) $validated['jours_total'];
        $prime = (float) ($validated['prime'] ?? 0);
        $deduction = (float) ($validated['deduction'] ?? 0);
        $minutesRetard = (int) ($validated['minutes_retard'] ?? 0);
        $heuresRetard = floor($minutesRetard / 60);
        $minutesRetard = $minutesRetard % 60;

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

    /**
     * Appliquer le calcul et sauvegarder l'ajustement
     */
    public function applyCalculation(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'salaire_mensuel' => 'required|numeric|min:0',
            'jours_travailles' => 'required|numeric|min:0|max:31',
            'jours_total' => 'required|numeric|min:1|max:31',
            'prime' => 'nullable|numeric|min:0',
            'deduction' => 'nullable|numeric|min:0',
            'minutes_retard' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // Calculs
        $salaireMensuel = (float) $validated['salaire_mensuel'];
        $joursTravailles = (float) $validated['jours_travailles'];
        $joursTotal = (float) $validated['jours_total'];
        $prime = (float) ($validated['prime'] ?? 0);
        $deduction = (float) ($validated['deduction'] ?? 0);
        $minutesRetardTotal = (int) ($validated['minutes_retard'] ?? 0);
        $heuresRetard = floor($minutesRetardTotal / 60);
        $minutesRetard = $minutesRetardTotal % 60;

        // Calcul du salaire journalier
        $salaireJournalier = $salaireMensuel / $joursTotal;

        // Calcul du salaire brut pour les jours travaillés
        $salaireBrut = $salaireJournalier * $joursTravailles;

        // Calcul de la pénalité de retard (0,50 FCFA par seconde)
        $totalSecondesRetard = ($heuresRetard * 3600) + ($minutesRetard * 60);
        $penaliteRetard = $totalSecondesRetard * 0.50;

        // Application des déductions (primes + retards + déductions manuelles)
        $salaireNet = $salaireBrut + $prime - $penaliteRetard - $deduction;

        // Pourcentage de présence
        $pourcentagePresence = ($joursTravailles / $joursTotal) * 100;

        // Jours d'absence
        $joursAbsence = $joursTotal - $joursTravailles;

        // Montant perdu (absences)
        $montantPerdu = $salaireJournalier * $joursAbsence;

        // Créer l'ajustement
        $adjustment = ManualPayrollAdjustment::create([
            'user_id' => $validated['user_id'],
            'applied_by' => auth()->id(),
            'year' => now()->year,
            'month' => now()->month,
            'salaire_mensuel' => $salaireMensuel,
            'jours_travailles' => $joursTravailles,
            'jours_total' => $joursTotal,
            'heures_retard' => $heuresRetard,
            'minutes_retard' => $minutesRetard,
            'prime' => $prime,
            'deduction_manuelle' => $deduction,
            'salaire_journalier' => $salaireJournalier,
            'salaire_brut' => $salaireBrut,
            'penalite_retard' => $penaliteRetard,
            'salaire_net' => $salaireNet,
            'montant_perdu' => $montantPerdu,
            'pourcentage_presence' => $pourcentagePresence,
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ajustement appliqué avec succès pour ' . $adjustment->user->full_name,
            'adjustment_id' => $adjustment->id,
        ]);
    }
}
