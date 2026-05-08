<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CnpsContribution;
use App\Models\CnpsRecord;
use App\Models\User;
use Illuminate\Http\Request;

class CnpsController extends Controller
{
    /**
     * Liste tous les employés avec leur situation CNPS.
     * Supporte la recherche par nom ou matricule. Paginé à 20.
     */
    public function index(Request $request)
    {
        $query = User::with('cnpsRecord')
            ->where('is_active', true)
            ->where('employee_type', '!=', 'etudiant')
            ->orderBy('last_name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'enrolled') {
                $query->whereHas('cnpsRecord');
            } elseif ($request->status === 'not_enrolled') {
                $query->whereDoesntHave('cnpsRecord');
            } elseif (in_array($request->status, ['active', 'inactive', 'suspended'])) {
                $query->whereHas('cnpsRecord', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }
        }

        $employees = $query->paginate(20);

        // Stats globales
        $totalEmployees = User::where('is_active', true)
            ->where('employee_type', '!=', 'etudiant')
            ->count();

        $totalEnrolled = CnpsRecord::count();

        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $contributionsThisMonth = CnpsContribution::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->count();

        $totalContributionsThisMonth = CnpsContribution::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->sum('total_contribution');

        // Liste des employés sans fiche CNPS (pour le formulaire d'ajout rapide)
        $employeesWithoutRecord = User::whereDoesntHave('cnpsRecord')
            ->where('is_active', true)
            ->where('employee_type', '!=', 'etudiant')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'employee_id']);

        return view('admin.cnps.index', compact(
            'employees',
            'totalEmployees',
            'totalEnrolled',
            'contributionsThisMonth',
            'totalContributionsThisMonth',
            'employeesWithoutRecord'
        ));
    }

    /**
     * Affiche la fiche CNPS détaillée d'un employé avec toutes ses cotisations.
     */
    public function show($id)
    {
        $user = User::with([
            'cnpsRecord',
            'cnpsContributions' => function ($q) {
                $q->orderBy('year', 'desc')->orderBy('month', 'desc');
            },
        ])->findOrFail($id);

        $cnpsRecord = $user->cnpsRecord;

        // Totaux toutes périodes confondues
        $totalEmployeeContrib = $user->cnpsContributions->sum('employee_contribution');
        $totalEmployerContrib = $user->cnpsContributions->sum('employer_contribution');
        $totalContributions   = $user->cnpsContributions->sum('total_contribution');

        // Cotisation du mois courant
        $currentMonth = now()->month;
        $currentYear  = now()->year;
        $currentContribution = $user->cnpsContributions
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->first();

        // Années disponibles pour le filtre
        $availableYears = $user->cnpsContributions
            ->pluck('year')
            ->unique()
            ->sortDesc()
            ->values();

        return view('admin.cnps.show', compact(
            'user',
            'cnpsRecord',
            'totalEmployeeContrib',
            'totalEmployerContrib',
            'totalContributions',
            'currentContribution',
            'availableYears'
        ));
    }

    /**
     * Crée ou met à jour la fiche CNPS d'un employé.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id'           => 'required|exists:users,id',
            'cnps_number'       => 'required|string|max:50',
            'registration_date' => 'required|date',
            'status'            => 'required|in:active,inactive,suspended',
        ]);

        CnpsRecord::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'cnps_number'       => strtoupper(trim($request->cnps_number)),
                'registration_date' => $request->registration_date,
                'status'            => $request->status,
            ]
        );

        $employee = User::findOrFail($request->user_id);

        return redirect()
            ->route('admin.cnps.show', $request->user_id)
            ->with('success', "Fiche CNPS de {$employee->full_name} enregistrée avec succès.");
    }

    /**
     * Ajoute une cotisation mensuelle pour un employé.
     * Calcule automatiquement les parts salariale et patronale si seul
     * le salaire brut est fourni.
     */
    public function addContribution(Request $request, $id)
    {
        $request->validate([
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2000|max:2100',
            'gross_salary' => 'required|numeric|min:0',
            'status'       => 'required|in:paid,pending,late',
        ]);

        $user = User::findOrFail($id);

        // Vérifier qu'une fiche CNPS existe
        if (!$user->cnpsRecord) {
            return back()->with('error', 'Cet employé n\'a pas encore de fiche CNPS. Veuillez d\'abord créer sa fiche.');
        }

        // Vérifier qu'il n'y a pas déjà une cotisation pour ce mois/année
        $existing = CnpsContribution::where('user_id', $id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->first();

        if ($existing) {
            return back()->with('error', "Une cotisation existe déjà pour {$this->monthName($request->month)} {$request->year}.");
        }

        // Calcul automatique des taux CNPS Cameroun
        $calc = CnpsContribution::calculate($request->gross_salary);

        CnpsContribution::create([
            'user_id'               => $id,
            'month'                 => $request->month,
            'year'                  => $request->year,
            'gross_salary'          => $request->gross_salary,
            'employee_contribution' => $calc['employee_contribution'],
            'employer_contribution' => $calc['employer_contribution'],
            'total_contribution'    => $calc['total_contribution'],
            'status'                => $request->status,
        ]);

        return redirect()
            ->route('admin.cnps.show', $id)
            ->with('success', "Cotisation de {$this->monthName($request->month)} {$request->year} ajoutée avec succès.");
    }

    /**
     * Retourne le nom français du mois.
     */
    private function monthName(int $month): string
    {
        $months = [
            1  => 'Janvier',
            2  => 'Février',
            3  => 'Mars',
            4  => 'Avril',
            5  => 'Mai',
            6  => 'Juin',
            7  => 'Juillet',
            8  => 'Août',
            9  => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ];

        return $months[$month] ?? "Mois {$month}";
    }
}
