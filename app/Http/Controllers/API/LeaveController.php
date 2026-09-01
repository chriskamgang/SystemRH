<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Services\LeaveCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    /**
     * Liste des demandes de congé de l'utilisateur
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');

        $query = LeaveRequest::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $leaves = $query->get()->map(function ($leave) {
            return [
                'id' => $leave->id,
                'type' => $leave->type,
                'type_label' => $leave->getTypeLabel(),
                'family_event_type' => $leave->family_event_type,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days_count' => $leave->days_count,
                'reason' => $leave->reason,
                'status' => $leave->status,
                'status_label' => $leave->getStatusLabel(),
                'has_attachment' => !empty($leave->attachment),
                'interim_name' => $leave->interim_name,
                'interim_function' => $leave->interim_function,
                'manager_status' => $leave->manager_status,
                'manager_comment' => $leave->manager_comment,
                'review_comment' => $leave->review_comment,
                'reviewed_by' => $leave->reviewer?->full_name,
                'reviewed_at' => $leave->reviewed_at?->format('d/m/Y H:i'),
                'created_at' => $leave->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'leaves' => $leaves,
        ]);
    }

    /**
     * Soldes de congés de l'utilisateur
     */
    public function balances(Request $request)
    {
        $user = $request->user();
        $year = $request->query('year', now()->year);

        $balances = [];
        foreach (LeaveRequest::TYPES as $type => $label) {
            // Pour le congé annuel, calculer le quota dynamiquement
            $defaultDays = $type === 'annual'
                ? LeaveCalculationService::calculateAnnualQuota($user)
                : (LeaveRequest::DEFAULT_BALANCES[$type] ?? 0);

            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $user->id, 'year' => $year, 'type' => $type],
                ['total_days' => $defaultDays, 'used_days' => 0]
            );

            // Mettre à jour le quota annuel si changé
            if ($type === 'annual' && $balance->total_days !== $defaultDays) {
                $balance->update(['total_days' => $defaultDays]);
            }

            $balances[] = [
                'type' => $type,
                'label' => $label,
                'total_days' => $balance->total_days,
                'used_days' => $balance->used_days,
                'remaining_days' => $balance->remaining_days,
            ];
        }

        // Ajouter le détail du calcul du quota annuel
        $quotaBreakdown = LeaveCalculationService::getQuotaBreakdown($user);

        return response()->json([
            'success' => true,
            'year' => (int) $year,
            'balances' => $balances,
            'quota_breakdown' => $quotaBreakdown,
        ]);
    }

    /**
     * Soumettre une demande de congé
     */
    public function store(Request $request)
    {
        $rules = [
            'type' => 'required|string|in:' . implode(',', array_keys(LeaveRequest::TYPES)),
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'interim_name' => 'required|string|max:255',
            'interim_function' => 'required|string|max:255',
            'interim_tasks' => 'nullable|string|max:2000',
        ];

        // Sous-type obligatoire pour événement familial
        if ($request->type === 'family_event') {
            $rules['family_event_type'] = 'required|string|in:' . implode(',', array_keys(LeaveCalculationService::FAMILY_EVENT_TYPES));
        }

        $request->validate($rules);

        $user = $request->user();
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Calculer les jours ouvrables (excl. dimanches + jours fériés)
        $daysCount = LeaveCalculationService::calculateWorkingDays($startDate, $endDate);
        $daysCountRounded = (int) ceil($daysCount);

        if ($daysCountRounded <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'La période sélectionnée ne contient aucun jour ouvrable.',
            ], 422);
        }

        // Pour événements familiaux, limiter au nombre de jours autorisés
        if ($request->type === 'family_event' && $request->family_event_type) {
            $maxDays = LeaveCalculationService::FAMILY_EVENT_TYPES[$request->family_event_type]['days'] ?? 3;
            if ($daysCountRounded > $maxDays) {
                return response()->json([
                    'success' => false,
                    'message' => "Ce type d'événement familial est limité à {$maxDays} jour(s).",
                ], 422);
            }

            // Vérifier le plafond annuel de 10 jours
            $remaining = LeaveCalculationService::getRemainingFamilyEventDays($user->id, $startDate->year);
            if ($daysCountRounded > $remaining) {
                return response()->json([
                    'success' => false,
                    'message' => "Quota annuel d'événements familiaux dépassé. Il vous reste {$remaining} jour(s) sur 10.",
                ], 422);
            }
        }

        // Vérifier le solde (sauf pour congé sans solde, autre, accident travail)
        if (!in_array($request->type, ['unpaid', 'other', 'work_accident'])) {
            $defaultDays = $request->type === 'annual'
                ? LeaveCalculationService::calculateAnnualQuota($user)
                : (LeaveRequest::DEFAULT_BALANCES[$request->type] ?? 0);

            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $user->id, 'year' => $startDate->year, 'type' => $request->type],
                ['total_days' => $defaultDays, 'used_days' => 0]
            );

            if ($balance->remaining_days < $daysCountRounded) {
                return response()->json([
                    'success' => false,
                    'message' => "Solde insuffisant. Il vous reste {$balance->remaining_days} jour(s) pour ce type de congé.",
                ], 422);
            }
        }

        // Vérifier les chevauchements
        $overlap = LeaveRequest::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une demande de congé sur cette période.',
            ], 422);
        }

        // Upload du justificatif
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        // Déterminer si le workflow manager est actif
        $hasManager = $user->manager_id !== null;

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'family_event_type' => $request->family_event_type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $daysCountRounded,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
            'interim_name' => $request->interim_name,
            'interim_function' => $request->interim_function,
            'interim_tasks' => $request->interim_tasks,
            'manager_status' => $hasManager ? 'pending' : null,
        ]);

        // Notifier le manager si applicable
        if ($hasManager) {
            $this->notifyManager($leave);
        }

        return response()->json([
            'success' => true,
            'message' => $hasManager
                ? 'Demande soumise. En attente de l\'avis de votre supérieur hiérarchique.'
                : 'Demande de congé soumise avec succès.',
            'leave' => [
                'id' => $leave->id,
                'type' => $leave->type,
                'type_label' => $leave->getTypeLabel(),
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days_count' => $leave->days_count,
                'status' => $leave->status,
                'status_label' => $leave->getStatusLabel(),
                'manager_status' => $leave->manager_status,
            ],
        ], 201);
    }

    /**
     * Annuler une demande de congé en attente
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $leave = LeaveRequest::withoutGlobalScopes()->where('user_id', $user->id)->findOrFail($id);

        if (!$leave->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes en attente peuvent être annulées.',
            ], 422);
        }

        $leave->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Demande de congé annulée.',
        ]);
    }

    /**
     * Infos pré-remplies pour le formulaire de demande
     */
    public function formData(Request $request)
    {
        $user = $request->user()->load(['department', 'company']);

        return response()->json([
            'success' => true,
            'employee' => [
                'full_name' => $user->full_name,
                'employee_id' => $user->employee_id,
                'department' => $user->department?->name,
                'company' => $user->company?->name,
                'phone' => $user->phone,
                'manager' => $user->manager?->full_name,
            ],
            'types' => LeaveRequest::TYPES,
            'family_event_types' => collect(LeaveCalculationService::FAMILY_EVENT_TYPES)->map(fn($v) => $v['label']),
        ]);
    }

    /**
     * Notifier le manager d'une nouvelle demande
     */
    private function notifyManager(LeaveRequest $leave)
    {
        try {
            $user = $leave->user;
            $manager = $user->manager;
            if (!$manager || !$manager->fcm_token) return;

            $pushService = new \App\Services\PushNotificationService();
            $pushService->sendToUser($manager, 'Demande de congé à valider', "{$user->full_name} demande un {$leave->getTypeLabel()} du {$leave->start_date->format('d/m')} au {$leave->end_date->format('d/m')}.", [
                'type' => 'leave_manager_review',
                'leave_id' => (string) $leave->id,
            ], 'leave');
        } catch (\Exception $e) {
            \Log::warning('Erreur notification manager congé: ' . $e->getMessage());
        }
    }
}
