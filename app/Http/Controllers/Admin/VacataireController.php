<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campus;
use App\Models\Role;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class VacataireController extends Controller
{
    /**
     * Display a listing of vacataires.
     */
    public function index(Request $request)
    {
        // Récupérer tous les vacataires (enseignant_vacataire)
        $query = User::where('employee_type', 'enseignant_vacataire')
            ->with(['department', 'campuses']);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $vacataires = $query->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(20);

        $campuses = Campus::orderBy('name')->get();

        return view('admin.vacataires.index', compact('vacataires', 'campuses'));
    }

    /**
     * Show the form for creating a new vacataire.
     */
    public function create()
    {
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        return view('admin.vacataires.create', compact('campuses'));
    }

    /**
     * Store a newly created vacataire.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'hourly_rate' => 'required|numeric|min:0',
            'campuses' => 'required|array|min:1',
            'campuses.*' => 'exists:campuses,id',
        ]);

        // Récupérer un role par défaut (ou créer)
        $defaultRole = Role::first();
        if (!$defaultRole) {
            $defaultRole = Role::create([
                'name' => 'employee',
                'display_name' => 'Employé',
                'description' => 'Employé standard',
            ]);
        }

        $vacataire = User::create([
            'employee_id' => $this->generateVacataireEmployeeId(),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make('password123'), // Mot de passe par défaut
            'role_id' => $defaultRole->id,
            'employee_type' => 'enseignant_vacataire', // IMPORTANT !
            'hourly_rate' => $request->hourly_rate,
            'is_active' => true,
        ]);

        // Attacher les campus
        $vacataire->campuses()->sync($request->campuses);

        return redirect()->route('admin.vacataires.index')
            ->with('success', 'Vacataire créé avec succès.');
    }

    /**
     * Display the specified vacataire.
     */
    public function show(string $id)
    {
        $vacataire = User::with(['campuses', 'department'])->findOrFail($id);

        // Statistiques du mois en cours
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('timestamp', [$startOfMonth, $endOfMonth])
            ->orderBy('timestamp', 'desc')
            ->get();

        // Calculer les heures travaillées
        $groupedAttendances = $attendances->groupBy(function ($attendance) {
            return $attendance->timestamp->format('Y-m-d');
        });

        $totalHours = 0;
        foreach ($groupedAttendances as $date => $dayAttendances) {
            $checkIn = $dayAttendances->where('type', 'check-in')->first();
            $checkOut = $dayAttendances->where('type', 'check-out')->first();

            if ($checkIn && $checkOut) {
                $hoursWorked = $checkIn->timestamp->diffInHours($checkOut->timestamp);
                $totalHours += $hoursWorked;
            }
        }

        $estimatedPay = $totalHours * ($vacataire->hourly_rate ?? 0);

        return view('admin.vacataires.show', compact('vacataire', 'totalHours', 'estimatedPay', 'attendances'));
    }

    /**
     * Show the form for editing the specified vacataire.
     */
    public function edit(string $id)
    {
        $vacataire = User::with('campuses')->findOrFail($id);
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        return view('admin.vacataires.edit', compact('vacataire', 'campuses'));
    }

    /**
     * Update the specified vacataire.
     */
    public function update(Request $request, string $id)
    {
        $vacataire = User::findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'hourly_rate' => 'required|numeric|min:0',
            'campuses' => 'required|array|min:1',
            'campuses.*' => 'exists:campuses,id',
            'is_active' => 'boolean',
        ]);

        $vacataire->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'hourly_rate' => $request->hourly_rate,
            'is_active' => $request->has('is_active'),
        ]);

        // Mettre à jour les campus
        $vacataire->campuses()->sync($request->campuses);

        return redirect()->route('admin.vacataires.index')
            ->with('success', 'Vacataire mis à jour avec succès.');
    }

    /**
     * Remove the specified vacataire.
     */
    public function destroy(string $id)
    {
        $vacataire = User::findOrFail($id);
        $vacataire->delete();

        return redirect()->route('admin.vacataires.index')
            ->with('success', 'Vacataire supprimé avec succès.');
    }

    /**
     * Display payments management page.
     */
    public function payments(Request $request)
    {
        $vacataireRole = Role::where('name', 'vacataire')->first();

        if (!$vacataireRole) {
            return redirect()->back()->with('error', 'Le rôle vacataire n\'existe pas.');
        }

        // Filtres de période
        $month = $request->filled('month') ? (int) explode('-', $request->month)[1] : Carbon::now()->month;
        $year = $request->filled('month') ? (int) explode('-', $request->month)[0] : Carbon::now()->year;

        // Filtre par statut
        $statusFilter = $request->filled('status') ? $request->status : '';

        $query = User::where('role_id', $vacataireRole->id)
            ->whereNotNull('hourly_rate')
            ->where('hourly_rate', '>', 0)
            ->with(['department', 'campuses']);

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        $vacataires = $query->get()->map(function ($vacataire) use ($year, $month, $statusFilter) {
            // Utiliser le PayrollCalculator
            $payrollData = \App\Helpers\PayrollCalculator::calculateVacatairePayroll($vacataire, $year, $month);

            // Ajouter les données calculées
            foreach ($payrollData as $key => $value) {
                $vacataire->$key = $value;
            }

            // Vérifier si un paiement existe déjà
            $existingPayment = \App\Models\VacatairePayment::where('user_id', $vacataire->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            $vacataire->payment_status = $existingPayment ? $existingPayment->status : 'pending';
            $vacataire->payment_id = $existingPayment ? $existingPayment->id : null;

            return $vacataire;
        });

        // Filtrer par statut si nécessaire
        if ($statusFilter) {
            $vacataires = $vacataires->filter(function ($vacataire) use ($statusFilter) {
                return $vacataire->payment_status === $statusFilter;
            });
        }

        // Statistiques globales
        $totalVacataires = $vacataires->count();
        $totalHours = $vacataires->sum('hours_worked');
        $totalCost = $vacataires->sum('net_amount');
        $pendingCount = $vacataires->where('payment_status', 'pending')->count();
        $validatedCount = $vacataires->where('payment_status', 'validated')->count();
        $paidCount = $vacataires->where('payment_status', 'paid')->count();

        $campuses = Campus::orderBy('name')->get();
        $monthFormatted = Carbon::create($year, $month)->format('Y-m');

        return view('admin.vacataires.payments', compact(
            'vacataires',
            'campuses',
            'monthFormatted',
            'year',
            'month',
            'totalVacataires',
            'totalHours',
            'totalCost',
            'pendingCount',
            'validatedCount',
            'paidCount'
        ));
    }

    /**
     * Générer les paies du mois pour tous les vacataires.
     */
    public function generateMonthlyPayments(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = $request->year;
        $month = $request->month;

        $vacataireRole = Role::where('name', 'vacataire')->first();

        if (!$vacataireRole) {
            return response()->json([
                'success' => false,
                'message' => 'Le rôle vacataire n\'existe pas.',
            ], 404);
        }

        $vacataires = User::where('role_id', $vacataireRole->id)
            ->whereNotNull('hourly_rate')
            ->where('hourly_rate', '>', 0)
            ->get();

        $generated = 0;
        $skipped = 0;

        foreach ($vacataires as $vacataire) {
            // Vérifier si un paiement existe déjà
            $existingPayment = \App\Models\VacatairePayment::where('user_id', $vacataire->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if ($existingPayment) {
                $skipped++;
                continue;
            }

            // Calculer la paie
            $payrollData = \App\Helpers\PayrollCalculator::calculateVacatairePayroll($vacataire, $year, $month);

            // Créer le paiement seulement si des heures ont été travaillées
            if ($payrollData['hours_worked'] > 0) {
                \App\Models\VacatairePayment::create([
                    'user_id' => $vacataire->id,
                    'department_id' => $vacataire->department_id,
                    'year' => $year,
                    'month' => $month,
                    'hourly_rate' => $payrollData['hourly_rate'],
                    'days_worked' => $payrollData['days_worked'],
                    'hours_worked' => $payrollData['hours_worked'],
                    'total_late_minutes' => $payrollData['total_late_minutes'],
                    'gross_amount' => $payrollData['gross_amount'],
                    'late_penalty' => $payrollData['late_penalty'],
                    'bonus' => 0,
                    'net_amount' => $payrollData['net_amount'],
                    'status' => 'pending',
                ]);

                $generated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$generated} paiement(s) généré(s), {$skipped} ignoré(s) (déjà existants).",
            'generated' => $generated,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Valider un paiement vacataire.
     */
    public function validatePayment(Request $request, string $id)
    {
        $payment = \App\Models\VacatairePayment::findOrFail($id);

        $payment->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement validé avec succès.',
        ]);
    }

    /**
     * Marquer un paiement comme payé.
     */
    public function markAsPaid(Request $request, string $id)
    {
        $payment = \App\Models\VacatairePayment::findOrFail($id);

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement marqué comme payé.',
        ]);
    }

    /**
     * Display vacataires report.
     */
    public function report(Request $request)
    {
        $vacataireRole = Role::where('name', 'vacataire')->first();

        // Filtres
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $query = User::where('role_id', $vacataireRole->id)
            ->with(['campuses']);

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        $vacataires = $query->get()->map(function ($vacataire) use ($startDate, $endDate) {
            // Statistiques
            $attendances = Attendance::where('user_id', $vacataire->id)
                ->whereBetween('timestamp', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($attendance) {
                    return $attendance->timestamp->format('Y-m-d');
                });

            $totalDays = $attendances->count();
            $totalHours = 0;
            $totalLate = 0;

            foreach ($attendances as $date => $dayAttendances) {
                $checkIn = $dayAttendances->where('type', 'check-in')->first();
                $checkOut = $dayAttendances->where('type', 'check-out')->first();

                if ($checkIn && $checkOut) {
                    $hoursWorked = $checkIn->timestamp->diffInHours($checkOut->timestamp);
                    $totalHours += $hoursWorked;
                }

                if ($checkIn && $checkIn->is_late) {
                    $totalLate++;
                }
            }

            $vacataire->total_days = $totalDays;
            $vacataire->total_hours = $totalHours;
            $vacataire->total_late = $totalLate;
            $vacataire->total_pay = $totalHours * ($vacataire->hourly_rate ?? 0);

            return $vacataire;
        });

        $campuses = Campus::orderBy('name')->get();

        return view('admin.vacataires.report', compact('vacataires', 'campuses', 'startDate', 'endDate'));
    }

    /**
     * Export vacataires report.
     */
    public function exportReport(Request $request)
    {
        // TODO: Implémenter l'export en PDF ou Excel
        return redirect()->route('admin.vacataires.report')
            ->with('info', 'Fonctionnalité d\'export en cours de développement.');
    }

    /**
     * Generate a unique vacataire employee ID
     * Format: VACXXXX (ex: VAC0001, VAC0002)
     */
    private function generateVacataireEmployeeId()
    {
        $prefix = "VAC";

        // Boucle jusqu'à trouver un ID unique
        $attempts = 0;
        $maxAttempts = 10000; // Limite de sécurité

        do {
            // Trouver le dernier employee_id VAC (incluant les soft deleted)
            $lastVacataire = User::withTrashed()
                ->where('employee_id', 'like', "{$prefix}%")
                ->orderBy('employee_id', 'desc')
                ->first();

            if ($lastVacataire) {
                // Extraire le numéro et l'incrémenter
                $lastNumber = intval(substr($lastVacataire->employee_id, 3)); // Après "VAC"
                $newNumber = $lastNumber + 1;
            } else {
                // Premier vacataire
                $newNumber = 1;
            }

            // Formater avec des zéros devant (0001, 0002, etc.)
            $employeeId = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            // Vérifier si cet ID existe déjà (incluant les soft deleted)
            $exists = User::withTrashed()->where('employee_id', $employeeId)->exists();

            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new \Exception("Impossible de générer un employee_id vacataire unique après {$maxAttempts} tentatives");
            }

        } while ($exists);

        return $employeeId;
    }
}
