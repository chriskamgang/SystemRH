<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VacatairePayment;
use App\Models\VacatairePaymentDetail;
use App\Models\UniteEnseignement;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RapportExport;
use Barryvdh\DomPDF\Facade\Pdf;

class RapportController extends Controller
{
    /**
     * 1. Etat du personnel payé sur une période
     */
    public function personnelPaye(Request $request)
    {
        $query = VacatairePayment::with(['user', 'department'])
            ->where('status', 'paid');

        // Filtres
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('paid_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('paid_at', '<=', $endDate);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $paiements = $query->orderBy('paid_at', 'desc')->paginate(20);

        // Statistiques
        $totalPaye = $query->sum('net_amount');
        $nombreEmployes = $query->distinct('user_id')->count('user_id');

        $departments = Department::orderBy('name')->get();
        $years = VacatairePayment::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');

        return view('admin.rapports.personnel-paye', compact(
            'paiements',
            'totalPaye',
            'nombreEmployes',
            'departments',
            'years'
        ));
    }

    /**
     * Export PDF du rapport personnel payé
     */
    public function personnelPayeExport(Request $request)
    {
        $query = VacatairePayment::with(['user', 'department'])
            ->where('status', 'paid');

        // Appliquer les mêmes filtres
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('paid_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('paid_at', '<=', $endDate);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $paiements = $query->orderBy('paid_at', 'desc')->get();
        $totalPaye = $paiements->sum('net_amount');
        $nombreEmployes = $paiements->unique('user_id')->count();

        $pdf = PDF::loadView('admin.rapports.pdf.personnel-paye', compact('paiements', 'totalPaye', 'nombreEmployes'));
        return $pdf->download('rapport-personnel-paye-' . date('Y-m-d') . '.pdf');
    }

    /**
     * 2. Etat des cours payés
     */
    public function coursPayes(Request $request)
    {
        $query = UniteEnseignement::with(['enseignant', 'paymentDetails'])
            ->whereHas('paymentDetails', function($q) {
                $q->whereHas('payment', function($p) {
                    $p->where('status', 'paid');
                });
            });

        // Filtres
        if ($request->filled('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }

        if ($request->filled('specialite')) {
            $query->where('specialite', $request->specialite);
        }

        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
        }

        $cours = $query->orderBy('code_ue')->paginate(20);

        // Statistiques
        $totalCours = $cours->total();
        $totalMontant = 0;

        foreach ($cours as $ue) {
            $totalMontant += $ue->paymentDetails()
                ->whereHas('payment', function($q) {
                    $q->where('status', 'paid');
                })
                ->sum('montant');
        }

        $specialites = UniteEnseignement::select('specialite')->distinct()->whereNotNull('specialite')->pluck('specialite');
        $niveaux = UniteEnseignement::select('niveau')->distinct()->whereNotNull('niveau')->pluck('niveau');
        $anneesAcademiques = UniteEnseignement::select('annee_academique')->distinct()->orderBy('annee_academique', 'desc')->pluck('annee_academique');

        return view('admin.rapports.cours-payes', compact(
            'cours',
            'totalCours',
            'totalMontant',
            'specialites',
            'niveaux',
            'anneesAcademiques'
        ));
    }

    /**
     * Export PDF du rapport cours payés
     */
    public function coursPayesExport(Request $request)
    {
        $query = UniteEnseignement::with(['enseignant', 'paymentDetails'])
            ->whereHas('paymentDetails', function($q) {
                $q->whereHas('payment', function($p) {
                    $p->where('status', 'paid');
                });
            });

        if ($request->filled('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }
        if ($request->filled('specialite')) {
            $query->where('specialite', $request->specialite);
        }
        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }
        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
        }

        $cours = $query->orderBy('code_ue')->get();

        $pdf = PDF::loadView('admin.rapports.pdf.cours-payes', compact('cours'));
        return $pdf->download('rapport-cours-payes-' . date('Y-m-d') . '.pdf');
    }

    /**
     * 3. Etat des cours non payés
     */
    public function coursNonPayes(Request $request)
    {
        $query = UniteEnseignement::with(['enseignant'])
            ->where('statut', 'activee')
            ->where(function($q) {
                // Cours qui n'ont aucun paiement OU ont des paiements non payés uniquement
                $q->whereDoesntHave('paymentDetails')
                  ->orWhereHas('paymentDetails', function($pd) {
                      $pd->whereHas('payment', function($p) {
                          $p->where('status', '!=', 'paid');
                      });
                  });
            });

        // Filtres
        if ($request->filled('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }

        if ($request->filled('specialite')) {
            $query->where('specialite', $request->specialite);
        }

        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
        }

        $cours = $query->orderBy('code_ue')->paginate(20);

        // Statistiques
        $totalCours = $cours->total();
        $totalMontantEstime = 0;

        foreach ($cours as $ue) {
            if ($ue->enseignant && !$ue->enseignant->isSemiPermanent()) {
                $totalMontantEstime += $ue->volume_horaire_total * ($ue->enseignant->hourly_rate ?? 0);
            }
        }

        $specialites = UniteEnseignement::select('specialite')->distinct()->whereNotNull('specialite')->pluck('specialite');
        $niveaux = UniteEnseignement::select('niveau')->distinct()->whereNotNull('niveau')->pluck('niveau');
        $anneesAcademiques = UniteEnseignement::select('annee_academique')->distinct()->orderBy('annee_academique', 'desc')->pluck('annee_academique');

        return view('admin.rapports.cours-non-payes', compact(
            'cours',
            'totalCours',
            'totalMontantEstime',
            'specialites',
            'niveaux',
            'anneesAcademiques'
        ));
    }

    /**
     * Export PDF du rapport cours non payés
     */
    public function coursNonPayesExport(Request $request)
    {
        $query = UniteEnseignement::with(['enseignant'])
            ->where('statut', 'activee')
            ->where(function($q) {
                $q->whereDoesntHave('paymentDetails')
                  ->orWhereHas('paymentDetails', function($pd) {
                      $pd->whereHas('payment', function($p) {
                          $p->where('status', '!=', 'paid');
                      });
                  });
            });

        if ($request->filled('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }
        if ($request->filled('specialite')) {
            $query->where('specialite', $request->specialite);
        }
        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }
        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
        }

        $cours = $query->orderBy('code_ue')->get();

        $pdf = PDF::loadView('admin.rapports.pdf.cours-non-payes', compact('cours'));
        return $pdf->download('rapport-cours-non-payes-' . date('Y-m-d') . '.pdf');
    }

    /**
     * 4. Masse salariale des enseignements déjà payés par spécialité
     */
    public function massePayesSpecialite(Request $request)
    {
        $query = VacatairePaymentDetail::select(
                'unites_enseignement.specialite',
                DB::raw('SUM(vacataire_payment_details.montant) as total_paye'),
                DB::raw('SUM(vacataire_payment_details.heures_saisies) as total_heures'),
                DB::raw('COUNT(DISTINCT vacataire_payment_details.unite_enseignement_id) as nombre_ue'),
                DB::raw('COUNT(DISTINCT vacataire_payments.user_id) as nombre_enseignants')
            )
            ->join('vacataire_payments', 'vacataire_payment_details.payment_id', '=', 'vacataire_payments.id')
            ->join('unites_enseignement', 'vacataire_payment_details.unite_enseignement_id', '=', 'unites_enseignement.id')
            ->where('vacataire_payments.status', 'paid')
            ->whereNotNull('unites_enseignement.specialite')
            ->groupBy('unites_enseignement.specialite');

        // Filtres
        if ($request->filled('annee_academique')) {
            $query->where('unites_enseignement.annee_academique', $request->annee_academique);
        }

        if ($request->filled('year')) {
            $query->where('vacataire_payments.year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('vacataire_payments.month', $request->month);
        }

        $masseSalariale = $query->orderBy('total_paye', 'desc')->get();

        // Total général
        $totalGeneral = $masseSalariale->sum('total_paye');
        $totalHeures = $masseSalariale->sum('total_heures');

        $anneesAcademiques = UniteEnseignement::select('annee_academique')->distinct()->orderBy('annee_academique', 'desc')->pluck('annee_academique');
        $years = VacatairePayment::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');

        return view('admin.rapports.masse-payes-specialite', compact(
            'masseSalariale',
            'totalGeneral',
            'totalHeures',
            'anneesAcademiques',
            'years'
        ));
    }

    /**
     * Export PDF masse salariale payés par spécialité
     */
    public function massePayesSpecialiteExport(Request $request)
    {
        $query = VacatairePaymentDetail::select(
                'unites_enseignement.specialite',
                DB::raw('SUM(vacataire_payment_details.montant) as total_paye'),
                DB::raw('SUM(vacataire_payment_details.heures_saisies) as total_heures'),
                DB::raw('COUNT(DISTINCT vacataire_payment_details.unite_enseignement_id) as nombre_ue'),
                DB::raw('COUNT(DISTINCT vacataire_payments.user_id) as nombre_enseignants')
            )
            ->join('vacataire_payments', 'vacataire_payment_details.payment_id', '=', 'vacataire_payments.id')
            ->join('unites_enseignement', 'vacataire_payment_details.unite_enseignement_id', '=', 'unites_enseignement.id')
            ->where('vacataire_payments.status', 'paid')
            ->whereNotNull('unites_enseignement.specialite')
            ->groupBy('unites_enseignement.specialite');

        if ($request->filled('annee_academique')) {
            $query->where('unites_enseignement.annee_academique', $request->annee_academique);
        }
        if ($request->filled('year')) {
            $query->where('vacataire_payments.year', $request->year);
        }
        if ($request->filled('month')) {
            $query->where('vacataire_payments.month', $request->month);
        }

        $masseSalariale = $query->orderBy('total_paye', 'desc')->get();
        $totalGeneral = $masseSalariale->sum('total_paye');
        $totalHeures = $masseSalariale->sum('total_heures');

        $pdf = PDF::loadView('admin.rapports.pdf.masse-payes-specialite', compact('masseSalariale', 'totalGeneral', 'totalHeures'));
        return $pdf->download('rapport-masse-payes-specialite-' . date('Y-m-d') . '.pdf');
    }

    /**
     * 5. Masse salariale des enseignements non payés par spécialité
     */
    public function masseNonPayesSpecialite(Request $request)
    {
        $query = UniteEnseignement::select(
                'specialite',
                DB::raw('COUNT(id) as nombre_ue'),
                DB::raw('SUM(volume_horaire_total) as total_heures'),
                DB::raw('COUNT(DISTINCT enseignant_id) as nombre_enseignants')
            )
            ->where('statut', 'activee')
            ->whereNotNull('specialite')
            ->where(function($q) {
                $q->whereDoesntHave('paymentDetails')
                  ->orWhereHas('paymentDetails', function($pd) {
                      $pd->whereHas('payment', function($p) {
                          $p->where('status', '!=', 'paid');
                      });
                  });
            })
            ->groupBy('specialite');

        // Filtres
        if ($request->filled('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }

        $masseSalariale = $query->orderBy('nombre_ue', 'desc')->get();

        // Calculer le montant estimé pour chaque spécialité
        foreach ($masseSalariale as $masse) {
            $ues = UniteEnseignement::where('specialite', $masse->specialite)
                ->where('statut', 'activee')
                ->with('enseignant')
                ->get();

            $montantEstime = 0;
            foreach ($ues as $ue) {
                if ($ue->enseignant && !$ue->enseignant->isSemiPermanent()) {
                    $montantEstime += $ue->volume_horaire_total * ($ue->enseignant->hourly_rate ?? 0);
                }
            }
            $masse->montant_estime = $montantEstime;
        }

        // Total général
        $totalGeneral = $masseSalariale->sum('montant_estime');
        $totalHeures = $masseSalariale->sum('total_heures');

        $anneesAcademiques = UniteEnseignement::select('annee_academique')->distinct()->orderBy('annee_academique', 'desc')->pluck('annee_academique');

        return view('admin.rapports.masse-non-payes-specialite', compact(
            'masseSalariale',
            'totalGeneral',
            'totalHeures',
            'anneesAcademiques'
        ));
    }

    /**
     * Export PDF masse salariale non payés par spécialité
     */
    public function masseNonPayesSpecialiteExport(Request $request)
    {
        $query = UniteEnseignement::select(
                'specialite',
                DB::raw('COUNT(id) as nombre_ue'),
                DB::raw('SUM(volume_horaire_total) as total_heures'),
                DB::raw('COUNT(DISTINCT enseignant_id) as nombre_enseignants')
            )
            ->where('statut', 'activee')
            ->whereNotNull('specialite')
            ->where(function($q) {
                $q->whereDoesntHave('paymentDetails')
                  ->orWhereHas('paymentDetails', function($pd) {
                      $pd->whereHas('payment', function($p) {
                          $p->where('status', '!=', 'paid');
                      });
                  });
            })
            ->groupBy('specialite');

        if ($request->filled('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }

        $masseSalariale = $query->orderBy('nombre_ue', 'desc')->get();

        foreach ($masseSalariale as $masse) {
            $ues = UniteEnseignement::where('specialite', $masse->specialite)
                ->where('statut', 'activee')
                ->with('enseignant')
                ->get();

            $montantEstime = 0;
            foreach ($ues as $ue) {
                if ($ue->enseignant && !$ue->enseignant->isSemiPermanent()) {
                    $montantEstime += $ue->volume_horaire_total * ($ue->enseignant->hourly_rate ?? 0);
                }
            }
            $masse->montant_estime = $montantEstime;
        }

        $totalGeneral = $masseSalariale->sum('montant_estime');
        $totalHeures = $masseSalariale->sum('total_heures');

        $pdf = PDF::loadView('admin.rapports.pdf.masse-non-payes-specialite', compact('masseSalariale', 'totalGeneral', 'totalHeures'));
        return $pdf->download('rapport-masse-non-payes-specialite-' . date('Y-m-d') . '.pdf');
    }

    /**
     * 6. Masse salariale des enseignements déjà payés par cycle
     */
    public function massePayesCycle(Request $request)
    {
        $query = VacatairePaymentDetail::select(
                'unites_enseignement.niveau',
                DB::raw('SUM(vacataire_payment_details.montant) as total_paye'),
                DB::raw('SUM(vacataire_payment_details.heures_saisies) as total_heures'),
                DB::raw('COUNT(DISTINCT vacataire_payment_details.unite_enseignement_id) as nombre_ue'),
                DB::raw('COUNT(DISTINCT vacataire_payments.user_id) as nombre_enseignants')
            )
            ->join('vacataire_payments', 'vacataire_payment_details.payment_id', '=', 'vacataire_payments.id')
            ->join('unites_enseignement', 'vacataire_payment_details.unite_enseignement_id', '=', 'unites_enseignement.id')
            ->where('vacataire_payments.status', 'paid')
            ->whereNotNull('unites_enseignement.niveau')
            ->groupBy('unites_enseignement.niveau');

        // Filtres
        if ($request->filled('annee_academique')) {
            $query->where('unites_enseignement.annee_academique', $request->annee_academique);
        }

        if ($request->filled('year')) {
            $query->where('vacataire_payments.year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('vacataire_payments.month', $request->month);
        }

        $masseSalariale = $query->orderBy('total_paye', 'desc')->get();

        // Total général
        $totalGeneral = $masseSalariale->sum('total_paye');
        $totalHeures = $masseSalariale->sum('total_heures');

        $anneesAcademiques = UniteEnseignement::select('annee_academique')->distinct()->orderBy('annee_academique', 'desc')->pluck('annee_academique');
        $years = VacatairePayment::select('year')->distinct()->orderBy('year', 'desc')->pluck('year');

        return view('admin.rapports.masse-payes-cycle', compact(
            'masseSalariale',
            'totalGeneral',
            'totalHeures',
            'anneesAcademiques',
            'years'
        ));
    }

    /**
     * Export PDF masse salariale payés par cycle
     */
    public function massePayesCycleExport(Request $request)
    {
        $query = VacatairePaymentDetail::select(
                'unites_enseignement.niveau',
                DB::raw('SUM(vacataire_payment_details.montant) as total_paye'),
                DB::raw('SUM(vacataire_payment_details.heures_saisies) as total_heures'),
                DB::raw('COUNT(DISTINCT vacataire_payment_details.unite_enseignement_id) as nombre_ue'),
                DB::raw('COUNT(DISTINCT vacataire_payments.user_id) as nombre_enseignants')
            )
            ->join('vacataire_payments', 'vacataire_payment_details.payment_id', '=', 'vacataire_payments.id')
            ->join('unites_enseignement', 'vacataire_payment_details.unite_enseignement_id', '=', 'unites_enseignement.id')
            ->where('vacataire_payments.status', 'paid')
            ->whereNotNull('unites_enseignement.niveau')
            ->groupBy('unites_enseignement.niveau');

        if ($request->filled('annee_academique')) {
            $query->where('unites_enseignement.annee_academique', $request->annee_academique);
        }
        if ($request->filled('year')) {
            $query->where('vacataire_payments.year', $request->year);
        }
        if ($request->filled('month')) {
            $query->where('vacataire_payments.month', $request->month);
        }

        $masseSalariale = $query->orderBy('total_paye', 'desc')->get();
        $totalGeneral = $masseSalariale->sum('total_paye');
        $totalHeures = $masseSalariale->sum('total_heures');

        $pdf = PDF::loadView('admin.rapports.pdf.masse-payes-cycle', compact('masseSalariale', 'totalGeneral', 'totalHeures'));
        return $pdf->download('rapport-masse-payes-cycle-' . date('Y-m-d') . '.pdf');
    }

    /**
     * 7. Masse salariale des enseignements non payés par cycle
     */
    public function masseNonPayesCycle(Request $request)
    {
        $query = UniteEnseignement::select(
                'niveau',
                DB::raw('COUNT(id) as nombre_ue'),
                DB::raw('SUM(volume_horaire_total) as total_heures'),
                DB::raw('COUNT(DISTINCT enseignant_id) as nombre_enseignants')
            )
            ->where('statut', 'activee')
            ->whereNotNull('niveau')
            ->where(function($q) {
                $q->whereDoesntHave('paymentDetails')
                  ->orWhereHas('paymentDetails', function($pd) {
                      $pd->whereHas('payment', function($p) {
                          $p->where('status', '!=', 'paid');
                      });
                  });
            })
            ->groupBy('niveau');

        // Filtres
        if ($request->filled('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }

        $masseSalariale = $query->orderBy('nombre_ue', 'desc')->get();

        // Calculer le montant estimé pour chaque cycle
        foreach ($masseSalariale as $masse) {
            $ues = UniteEnseignement::where('niveau', $masse->niveau)
                ->where('statut', 'activee')
                ->with('enseignant')
                ->get();

            $montantEstime = 0;
            foreach ($ues as $ue) {
                if ($ue->enseignant && !$ue->enseignant->isSemiPermanent()) {
                    $montantEstime += $ue->volume_horaire_total * ($ue->enseignant->hourly_rate ?? 0);
                }
            }
            $masse->montant_estime = $montantEstime;
        }

        // Total général
        $totalGeneral = $masseSalariale->sum('montant_estime');
        $totalHeures = $masseSalariale->sum('total_heures');

        $anneesAcademiques = UniteEnseignement::select('annee_academique')->distinct()->orderBy('annee_academique', 'desc')->pluck('annee_academique');

        return view('admin.rapports.masse-non-payes-cycle', compact(
            'masseSalariale',
            'totalGeneral',
            'totalHeures',
            'anneesAcademiques'
        ));
    }

    /**
     * Export PDF masse salariale non payés par cycle
     */
    public function masseNonPayesCycleExport(Request $request)
    {
        $query = UniteEnseignement::select(
                'niveau',
                DB::raw('COUNT(id) as nombre_ue'),
                DB::raw('SUM(volume_horaire_total) as total_heures'),
                DB::raw('COUNT(DISTINCT enseignant_id) as nombre_enseignants')
            )
            ->where('statut', 'activee')
            ->whereNotNull('niveau')
            ->where(function($q) {
                $q->whereDoesntHave('paymentDetails')
                  ->orWhereHas('paymentDetails', function($pd) {
                      $pd->whereHas('payment', function($p) {
                          $p->where('status', '!=', 'paid');
                      });
                  });
            })
            ->groupBy('niveau');

        if ($request->filled('annee_academique')) {
            $query->where('annee_academique', $request->annee_academique);
        }

        $masseSalariale = $query->orderBy('nombre_ue', 'desc')->get();

        foreach ($masseSalariale as $masse) {
            $ues = UniteEnseignement::where('niveau', $masse->niveau)
                ->where('statut', 'activee')
                ->with('enseignant')
                ->get();

            $montantEstime = 0;
            foreach ($ues as $ue) {
                if ($ue->enseignant && !$ue->enseignant->isSemiPermanent()) {
                    $montantEstime += $ue->volume_horaire_total * ($ue->enseignant->hourly_rate ?? 0);
                }
            }
            $masse->montant_estime = $montantEstime;
        }

        $totalGeneral = $masseSalariale->sum('montant_estime');
        $totalHeures = $masseSalariale->sum('total_heures');

        $pdf = PDF::loadView('admin.rapports.pdf.masse-non-payes-cycle', compact('masseSalariale', 'totalGeneral', 'totalHeures'));
        return $pdf->download('rapport-masse-non-payes-cycle-' . date('Y-m-d') . '.pdf');
    }
}
