<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualAttendance;
use App\Models\User;
use App\Models\Campus;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualAttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ManualAttendance::with(['user', 'campus', 'uniteEnseignement', 'creator'])
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc');

        // Filtres
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->forMonth($request->year, $request->month);
        }

        if ($request->filled('session_type')) {
            $query->where('session_type', $request->session_type);
        }

        $attendances = $query->paginate(20);

        // Données pour les filtres
        $users = User::orderBy('first_name')->get();
        $campuses = Campus::orderBy('name')->get();

        return view('admin.manual-attendances.index', compact('attendances', 'users', 'campuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $users = User::orderBy('first_name')->get();
        $campuses = Campus::orderBy('name')->get();

        // Si un user_id est passé en paramètre
        $selectedUser = null;
        $unitesEnseignement = collect();

        if ($request->filled('user_id')) {
            $selectedUser = User::with('unitesEnseignement')->find($request->user_id);
            if ($selectedUser) {
                $unitesEnseignement = $selectedUser->unitesEnseignement()
                    ->where('statut', 'activee')
                    ->orderBy('code_ue')
                    ->get();
            }
        }

        return view('admin.manual-attendances.create', compact('users', 'campuses', 'selectedUser', 'unitesEnseignement'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'campus_id' => 'required|exists:campuses,id',
            'date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i|after:check_in_time',
            'session_type' => 'required|in:jour,soir',
            'unite_enseignement_id' => 'nullable|exists:unites_enseignement,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Vérifier si l'employé est enseignant et que l'UE est obligatoire
        $user = User::find($validated['user_id']);
        $isTeacher = in_array($user->employee_type, [
            'enseignant_titulaire',
            'enseignant_vacataire',
            'semi_permanent'
        ]);

        if ($isTeacher && !$request->filled('unite_enseignement_id')) {
            return back()->withErrors([
                'unite_enseignement_id' => 'L\'unité d\'enseignement est obligatoire pour les enseignants.'
            ])->withInput();
        }

        // Créer la présence manuelle
        $validated['created_by'] = Auth::id();
        $manualAttendance = ManualAttendance::create($validated);

        // Si une UE est associée, mettre à jour les heures validées
        if ($manualAttendance->unite_enseignement_id) {
            $ue = UniteEnseignement::find($manualAttendance->unite_enseignement_id);
            if ($ue) {
                $ue->ajouterHeuresValidees($manualAttendance->duration_in_hours);
            }
        }

        return redirect()
            ->route('admin.manual-attendances.index')
            ->with('success', 'Présence enregistrée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ManualAttendance $manualAttendance)
    {
        $manualAttendance->load(['user', 'campus', 'uniteEnseignement', 'creator']);

        return view('admin.manual-attendances.show', compact('manualAttendance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ManualAttendance $manualAttendance)
    {
        $users = User::orderBy('first_name')->get();
        $campuses = Campus::orderBy('name')->get();

        $selectedUser = $manualAttendance->user;
        $unitesEnseignement = collect();

        if ($selectedUser) {
            $unitesEnseignement = $selectedUser->unitesEnseignement()
                ->where('statut', 'activee')
                ->orderBy('code_ue')
                ->get();
        }

        return view('admin.manual-attendances.edit', compact('manualAttendance', 'users', 'campuses', 'selectedUser', 'unitesEnseignement'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ManualAttendance $manualAttendance)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'campus_id' => 'required|exists:campuses,id',
            'date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i|after:check_in_time',
            'session_type' => 'required|in:jour,soir',
            'unite_enseignement_id' => 'nullable|exists:unites_enseignement,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Vérifier si l'employé est enseignant et que l'UE est obligatoire
        $user = User::find($validated['user_id']);
        $isTeacher = in_array($user->employee_type, [
            'enseignant_titulaire',
            'enseignant_vacataire',
            'semi_permanent'
        ]);

        if ($isTeacher && !$request->filled('unite_enseignement_id')) {
            return back()->withErrors([
                'unite_enseignement_id' => 'L\'unité d\'enseignement est obligatoire pour les enseignants.'
            ])->withInput();
        }

        // Sauvegarder les anciennes valeurs pour mettre à jour les UE
        $oldUeId = $manualAttendance->unite_enseignement_id;
        $oldDuration = $manualAttendance->duration_in_hours;

        // Mettre à jour la présence manuelle
        $manualAttendance->update($validated);

        // Rafraîchir pour obtenir la nouvelle durée calculée
        $manualAttendance->refresh();
        $newDuration = $manualAttendance->duration_in_hours;

        // Gérer la mise à jour des heures validées dans les UE
        if ($oldUeId && $oldUeId != $manualAttendance->unite_enseignement_id) {
            // L'UE a changé : soustraire de l'ancien, ajouter au nouveau
            $oldUe = UniteEnseignement::find($oldUeId);
            if ($oldUe) {
                $oldUe->soustraireHeuresValidees($oldDuration);
            }
        } elseif ($oldUeId && $oldUeId == $manualAttendance->unite_enseignement_id && $oldDuration != $newDuration) {
            // Même UE mais durée changée : ajuster
            $oldUe = UniteEnseignement::find($oldUeId);
            if ($oldUe) {
                $oldUe->soustraireHeuresValidees($oldDuration);
            }
        } elseif ($oldUeId && !$manualAttendance->unite_enseignement_id) {
            // UE enlevée : soustraire de l'ancien
            $oldUe = UniteEnseignement::find($oldUeId);
            if ($oldUe) {
                $oldUe->soustraireHeuresValidees($oldDuration);
            }
        }

        // Ajouter les nouvelles heures au nouveau/même UE
        if ($manualAttendance->unite_enseignement_id) {
            $newUe = UniteEnseignement::find($manualAttendance->unite_enseignement_id);
            if ($newUe) {
                $newUe->ajouterHeuresValidees($newDuration);
            }
        }

        return redirect()
            ->route('admin.manual-attendances.index')
            ->with('success', 'Présence mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ManualAttendance $manualAttendance)
    {
        // Si une UE est associée, soustraire les heures validées avant de supprimer
        if ($manualAttendance->unite_enseignement_id) {
            $ue = UniteEnseignement::find($manualAttendance->unite_enseignement_id);
            if ($ue) {
                $ue->soustraireHeuresValidees($manualAttendance->duration_in_hours);
            }
        }

        $manualAttendance->delete();

        return redirect()
            ->route('admin.manual-attendances.index')
            ->with('success', 'Présence supprimée avec succès.');
    }

    /**
     * Get UEs for a specific user (AJAX)
     */
    public function getUserUEs(Request $request)
    {
        $userId = $request->get('user_id');

        if (!$userId) {
            return response()->json([]);
        }

        $user = User::with(['unitesEnseignement' => function ($query) {
            $query->where('statut', 'activee')->orderBy('code_ue');
        }])->find($userId);

        if (!$user) {
            return response()->json([]);
        }

        // Vérifier si c'est un enseignant
        $isTeacher = in_array($user->employee_type, [
            'enseignant_titulaire',
            'enseignant_vacataire',
            'semi_permanent'
        ]);

        return response()->json([
            'is_teacher' => $isTeacher,
            'employee_type' => $user->employee_type,
            'ues' => $user->unitesEnseignement->map(function ($ue) {
                return [
                    'id' => $ue->id,
                    'code' => $ue->code_ue,
                    'intitule' => $ue->nom_matiere,
                    'display' => "{$ue->code_ue} - {$ue->nom_matiere}",
                ];
            }),
        ]);
    }

    /**
     * Rapport mensuel des présences
     */
    public function monthlyReport(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Récupérer toutes les présences du mois
        $attendances = ManualAttendance::with(['user', 'campus', 'uniteEnseignement'])
            ->forMonth($year, $month)
            ->get();

        // Grouper par employé
        $employeeStats = $attendances->groupBy('user_id')->map(function ($userAttendances) use ($month, $year) {
            $user = $userAttendances->first()->user;

            // Calculs généraux
            $totalHours = $userAttendances->sum('duration_in_hours');
            $totalDays = $userAttendances->pluck('date')->unique()->count();

            // Grouper par UE
            $ueBreakdown = $userAttendances->whereNotNull('unite_enseignement_id')
                ->groupBy('unite_enseignement_id')
                ->map(function ($ueAttendances) {
                    $ue = $ueAttendances->first()->uniteEnseignement;
                    return [
                        'ue' => $ue,
                        'hours' => $ueAttendances->sum('duration_in_hours'),
                        'sessions' => $ueAttendances->count(),
                    ];
                });

            // Calcul salaire selon type
            $salary = $this->calculateMonthlySalary($user, $totalHours, $month, $year);

            return [
                'user' => $user,
                'total_hours' => round($totalHours, 2),
                'total_days' => $totalDays,
                'ue_breakdown' => $ueBreakdown,
                'salary' => $salary,
                'attendances' => $userAttendances,
            ];
        });

        $users = User::orderBy('first_name')->get();

        return view('admin.manual-attendances.monthly-report', compact('employeeStats', 'month', 'year', 'users'));
    }

    /**
     * Calculer le salaire mensuel selon le type d'employé
     */
    private function calculateMonthlySalary($user, $totalHours, $month, $year)
    {
        $salary = [
            'type' => 'Salaire fixe',
            'base' => $user->monthly_salary ?? 0,
            'hourly_component' => 0,
            'total' => $user->monthly_salary ?? 0,
            'details' => null,
        ];

        if ($user->employee_type === 'enseignant_vacataire') {
            // Vacataire : heures × taux horaire
            $hourlyRate = $user->hourly_rate ?? 0;
            $salary = [
                'type' => 'Paiement horaire',
                'base' => 0,
                'hourly_component' => $totalHours * $hourlyRate,
                'total' => $totalHours * $hourlyRate,
                'details' => "{$totalHours}h × {$hourlyRate} FCFA/h",
            ];
        } elseif ($user->employee_type === 'semi_permanent') {
            // Semi-permanent : salaire fixe (heures pour suivi uniquement)
            $volumeHebdo = $user->volume_horaire_hebdomadaire ?? 0;
            $weeksInMonth = 4; // Approximation
            $expectedHours = $volumeHebdo * $weeksInMonth;

            $salary = [
                'type' => 'Salaire fixe (suivi horaire)',
                'base' => $user->monthly_salary ?? 0,
                'hourly_component' => 0,
                'total' => $user->monthly_salary ?? 0,
                'details' => "Attendu: {$expectedHours}h/mois - Effectué: {$totalHours}h",
            ];
        } elseif ($user->employee_type === 'enseignant_titulaire') {
            // Permanent qui enseigne : salaire fixe (heures pour suivi uniquement)
            $salary = [
                'type' => 'Salaire fixe (enseignant)',
                'base' => $user->monthly_salary ?? 0,
                'hourly_component' => 0,
                'total' => $user->monthly_salary ?? 0,
                'details' => "Heures enseignées: {$totalHours}h (suivi uniquement)",
            ];
        }

        return $salary;
    }

    /**
     * Export Excel du rapport mensuel
     */
    public function exportMonthlyReport(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Récupérer les données
        $attendances = ManualAttendance::with(['user', 'campus', 'uniteEnseignement'])
            ->forMonth($year, $month)
            ->get();

        $employeeStats = $attendances->groupBy('user_id')->map(function ($userAttendances) use ($month, $year) {
            $user = $userAttendances->first()->user;
            $totalHours = $userAttendances->sum('duration_in_hours');
            $totalDays = $userAttendances->pluck('date')->unique()->count();

            $ueBreakdown = $userAttendances->whereNotNull('unite_enseignement_id')
                ->groupBy('unite_enseignement_id')
                ->map(function ($ueAttendances) {
                    $ue = $ueAttendances->first()->uniteEnseignement;
                    return [
                        'code' => $ue->code_ue,
                        'intitule' => $ue->nom_matiere,
                        'hours' => $ueAttendances->sum('duration_in_hours'),
                        'sessions' => $ueAttendances->count(),
                    ];
                });

            $salary = $this->calculateMonthlySalary($user, $totalHours, $month, $year);

            return [
                'nom' => $user->full_name,
                'type' => ucfirst(str_replace('_', ' ', $user->employee_type)),
                'total_heures' => round($totalHours, 2),
                'total_jours' => $totalDays,
                'ue_breakdown' => $ueBreakdown,
                'salaire' => $salary['total'],
                'details_salaire' => $salary['details'],
            ];
        });

        // Créer le fichier Excel (simplifié pour l'instant)
        $monthName = \Carbon\Carbon::create()->month($month)->translatedFormat('F');
        $filename = "rapport_presences_manuelles_{$monthName}_{$year}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employeeStats) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nom', 'Type Employé', 'Total Heures', 'Total Jours', 'Salaire', 'Détails']);

            foreach ($employeeStats as $stat) {
                fputcsv($file, [
                    $stat['nom'],
                    $stat['type'],
                    $stat['total_heures'],
                    $stat['total_jours'],
                    number_format($stat['salaire'], 0, ',', ' '),
                    $stat['details_salaire'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
