<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VacatairePayment;
use App\Models\VacatairePaymentDetail;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VacataireManualPaymentController extends Controller
{
    /**
     * Liste des paiements manuels
     */
    public function index(Request $request)
    {
        $query = VacatairePayment::with(['user', 'details']);

        // Filtres
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('vacataire_id')) {
            $query->where('user_id', $request->vacataire_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        // Statistiques du mois en cours
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $stats = [
            'total_paye' => VacatairePayment::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->sum('net_amount'),
            'nb_vacataires' => VacatairePayment::where('year', $currentYear)
                ->where('month', $currentMonth)
                ->distinct('user_id')
                ->count('user_id'),
            'total_heures' => VacatairePaymentDetail::whereHas('payment', function ($q) use ($currentYear, $currentMonth) {
                $q->where('year', $currentYear)->where('month', $currentMonth);
            })->sum('heures_saisies'),
        ];

        $stats['moyenne'] = $stats['nb_vacataires'] > 0 ? $stats['total_paye'] / $stats['nb_vacataires'] : 0;

        // Liste des vacataires pour le filtre
        $vacataires = User::where('employee_type', 'enseignant_vacataire')
            ->orderBy('first_name')
            ->get();

        return view('admin.vacataires.manual-payments.index', compact('payments', 'stats', 'vacataires'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $vacataires = User::where('employee_type', 'enseignant_vacataire')
            ->orderBy('first_name')
            ->get();

        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $years = range(date('Y') - 1, date('Y') + 1);

        return view('admin.vacataires.manual-payments.create', compact('vacataires', 'months', 'years'));
    }

    /**
     * Récupérer les UE d'un vacataire (AJAX)
     */
    public function selectUE(Request $request)
    {
        $request->validate([
            'vacataire_id' => 'required|exists:users,id',
        ]);

        $vacataire = User::findOrFail($request->vacataire_id);

        $ues = UniteEnseignement::where('enseignant_id', $vacataire->id)
            ->where('statut', 'activee')
            ->get()
            ->map(function ($ue) use ($vacataire) {
                return [
                    'id' => $ue->id,
                    'code_ue' => $ue->code_ue,
                    'nom_matiere' => $ue->nom_matiere,
                    'volume_horaire_total' => $ue->volume_horaire_total,
                    'heures_effectuees_validees' => $ue->heures_effectuees_validees,
                    'heures_restantes' => $ue->heures_restantes_validees,
                    'taux_horaire' => $vacataire->hourly_rate,
                ];
            });

        return response()->json([
            'success' => true,
            'ues' => $ues,
            'vacataire' => [
                'id' => $vacataire->id,
                'nom' => $vacataire->full_name,
                'taux_horaire' => $vacataire->hourly_rate,
            ],
        ]);
    }

    /**
     * Enregistrer le paiement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vacataire_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'ue_details' => 'required|array|min:1',
            'ue_details.*.unite_enseignement_id' => 'required|exists:unites_enseignement,id',
            'ue_details.*.heures_saisies' => 'required|numeric|min:0.01',
            'appliquer_impot' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $vacataire = User::findOrFail($validated['vacataire_id']);

            // Calculer le total
            $totalHeures = 0;
            $totalMontant = 0;

            foreach ($validated['ue_details'] as $ueData) {
                $heures = (float) $ueData['heures_saisies'];
                $montant = $heures * $vacataire->hourly_rate;

                $totalHeures += $heures;
                $totalMontant += $montant;
            }

            // Calculer l'impôt si demandé (5% du montant brut)
            $impotRetenu = 0;
            if ($request->has('appliquer_impot') && $request->appliquer_impot) {
                $impotRetenu = $totalMontant * 0.05;
            }

            // Créer le paiement
            $payment = VacatairePayment::create([
                'user_id' => $vacataire->id,
                'department_id' => $vacataire->department_id,
                'year' => $validated['year'],
                'month' => $validated['month'],
                'hourly_rate' => $vacataire->hourly_rate,
                'hours_worked' => $totalHeures,
                'gross_amount' => $totalMontant,
                'impot_retenu' => $impotRetenu,
                'net_amount' => $totalMontant - $impotRetenu,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Créer les détails pour chaque UE
            foreach ($validated['ue_details'] as $ueData) {
                $ue = UniteEnseignement::findOrFail($ueData['unite_enseignement_id']);
                $heures = (float) $ueData['heures_saisies'];
                $montant = $heures * $vacataire->hourly_rate;

                VacatairePaymentDetail::create([
                    'payment_id' => $payment->id,
                    'unite_enseignement_id' => $ue->id,
                    'code_ue' => $ue->code_ue,
                    'nom_matiere' => $ue->nom_matiere,
                    'heures_saisies' => $heures,
                    'taux_horaire' => $vacataire->hourly_rate,
                    'montant' => $montant,
                    'notes' => $ueData['notes'] ?? null,
                ]);

                // Mettre à jour les heures validées de l'UE
                $ue->ajouterHeuresValidees($heures);
            }

            DB::commit();

            return redirect()
                ->route('admin.vacataires.manual-payments.show', $payment->id)
                ->with('success', 'Paiement créé avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création du paiement : ' . $e->getMessage());
        }
    }

    /**
     * Afficher un paiement
     */
    public function show($id)
    {
        $payment = VacatairePayment::with(['user', 'details.uniteEnseignement', 'validatedBy'])
            ->findOrFail($id);

        return view('admin.vacataires.manual-payments.show', compact('payment'));
    }

    /**
     * Formulaire de modification
     */
    public function edit($id)
    {
        $payment = VacatairePayment::with(['user', 'details.uniteEnseignement'])
            ->findOrFail($id);

        $vacataires = User::where('employee_type', 'enseignant_vacataire')
            ->orderBy('first_name')
            ->get();

        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $years = range(date('Y') - 1, date('Y') + 1);

        // Récupérer toutes les UE activées du vacataire
        $ues = UniteEnseignement::where('enseignant_id', $payment->user_id)
            ->where('statut', 'activee')
            ->get();

        return view('admin.vacataires.manual-payments.edit', compact('payment', 'vacataires', 'months', 'years', 'ues'));
    }

    /**
     * Mettre à jour un paiement
     */
    public function update(Request $request, $id)
    {
        $payment = VacatairePayment::findOrFail($id);

        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'ue_details' => 'required|array|min:1',
            'ue_details.*.unite_enseignement_id' => 'required|exists:unites_enseignement,id',
            'ue_details.*.heures_saisies' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $vacataire = $payment->user;

            // Soustraire les anciennes heures des UE
            foreach ($payment->details as $detail) {
                if ($detail->uniteEnseignement) {
                    $detail->uniteEnseignement->soustraireHeuresValidees($detail->heures_saisies);
                }
            }

            // Supprimer les anciens détails
            $payment->details()->delete();

            // Recalculer le total
            $totalHeures = 0;
            $totalMontant = 0;

            foreach ($validated['ue_details'] as $ueData) {
                $heures = (float) $ueData['heures_saisies'];
                $montant = $heures * $vacataire->hourly_rate;

                $totalHeures += $heures;
                $totalMontant += $montant;
            }

            // Mettre à jour le paiement
            $payment->update([
                'month' => $validated['month'],
                'year' => $validated['year'],
                'hours_worked' => $totalHeures,
                'gross_amount' => $totalMontant,
                'net_amount' => $totalMontant,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Créer les nouveaux détails
            foreach ($validated['ue_details'] as $ueData) {
                $ue = UniteEnseignement::findOrFail($ueData['unite_enseignement_id']);
                $heures = (float) $ueData['heures_saisies'];
                $montant = $heures * $vacataire->hourly_rate;

                VacatairePaymentDetail::create([
                    'payment_id' => $payment->id,
                    'unite_enseignement_id' => $ue->id,
                    'code_ue' => $ue->code_ue,
                    'nom_matiere' => $ue->nom_matiere,
                    'heures_saisies' => $heures,
                    'taux_horaire' => $vacataire->hourly_rate,
                    'montant' => $montant,
                    'notes' => $ueData['notes'] ?? null,
                ]);

                // Ajouter les nouvelles heures
                $ue->ajouterHeuresValidees($heures);
            }

            DB::commit();

            return redirect()
                ->route('admin.vacataires.manual-payments.show', $payment->id)
                ->with('success', 'Paiement mis à jour avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un paiement
     */
    public function destroy($id)
    {
        $payment = VacatairePayment::findOrFail($id);

        DB::beginTransaction();

        try {
            // Soustraire les heures des UE
            foreach ($payment->details as $detail) {
                if ($detail->uniteEnseignement) {
                    $detail->uniteEnseignement->soustraireHeuresValidees($detail->heures_saisies);
                }
            }

            // Supprimer le paiement (cascade supprimera les détails)
            $payment->delete();

            DB::commit();

            return redirect()
                ->route('admin.vacataires.manual-payments.index')
                ->with('success', 'Paiement supprimé avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Statistiques
     */
    public function statistics()
    {
        // Données pour les statistiques
        $currentYear = now()->year;

        // Total par mois de l'année
        $paymentsParMois = VacatairePayment::where('year', $currentYear)
            ->selectRaw('month, SUM(net_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        // Top 10 vacataires
        $topVacataires = VacatairePayment::with('user')
            ->where('year', $currentYear)
            ->selectRaw('user_id, SUM(net_amount) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Répartition par département
        $parDepartement = VacatairePayment::with('department')
            ->where('year', $currentYear)
            ->selectRaw('department_id, SUM(net_amount) as total')
            ->groupBy('department_id')
            ->get();

        return view('admin.vacataires.manual-payments.statistics', compact(
            'paymentsParMois',
            'topVacataires',
            'parDepartement'
        ));
    }

    /**
     * Vérifier si un paiement existe déjà (AJAX)
     */
    public function checkExistingPayment(Request $request)
    {
        $exists = VacatairePayment::where('user_id', $request->vacataire_id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Un paiement existe déjà pour cette période' : null,
        ]);
    }
}
