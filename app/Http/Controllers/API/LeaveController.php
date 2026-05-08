<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
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
        $status = $request->query('status'); // pending, approved, rejected, cancelled

        $query = LeaveRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $leaves = $query->get()->map(function ($leave) {
            return [
                'id' => $leave->id,
                'type' => $leave->type,
                'type_label' => $leave->getTypeLabel(),
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days_count' => $leave->days_count,
                'reason' => $leave->reason,
                'status' => $leave->status,
                'has_attachment' => !empty($leave->attachment),
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
            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $user->id, 'year' => $year, 'type' => $type],
                ['total_days' => LeaveRequest::DEFAULT_BALANCES[$type] ?? 0, 'used_days' => 0]
            );

            $balances[] = [
                'type' => $type,
                'label' => $label,
                'total_days' => $balance->total_days,
                'used_days' => $balance->used_days,
                'remaining_days' => $balance->remaining_days,
            ];
        }

        return response()->json([
            'success' => true,
            'year' => (int) $year,
            'balances' => $balances,
        ]);
    }

    /**
     * Soumettre une demande de congé
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(LeaveRequest::TYPES)),
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = $request->user();
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);

        // Calculer les jours ouvrés (lundi-vendredi + samedi matin = 0.5)
        $daysCount = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->isWeekday()) {
                $daysCount++;
            } elseif ($current->isSaturday()) {
                $daysCount += 0.5;
            }
            $current->addDay();
        }
        $daysCount = (int) ceil($daysCount);

        if ($daysCount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'La période sélectionnée ne contient aucun jour ouvré.',
            ], 422);
        }

        // Vérifier le solde (sauf pour congé sans solde et autre)
        if (!in_array($request->type, ['unpaid', 'other'])) {
            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $user->id, 'year' => $startDate->year, 'type' => $request->type],
                ['total_days' => LeaveRequest::DEFAULT_BALANCES[$request->type] ?? 0, 'used_days' => 0]
            );

            if ($balance->remaining_days < $daysCount) {
                return response()->json([
                    'success' => false,
                    'message' => "Solde insuffisant. Il vous reste {$balance->remaining_days} jour(s) pour ce type de congé.",
                ], 422);
            }
        }

        // Vérifier les chevauchements avec des demandes existantes
        $overlap = LeaveRequest::where('user_id', $user->id)
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

        // Upload du justificatif si fourni
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $daysCount,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de congé soumise avec succès.',
            'leave' => [
                'id' => $leave->id,
                'type' => $leave->type,
                'type_label' => $leave->getTypeLabel(),
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days_count' => $leave->days_count,
                'status' => $leave->status,
            ],
        ], 201);
    }

    /**
     * Annuler une demande de congé en attente
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $leave = LeaveRequest::where('user_id', $user->id)->findOrFail($id);

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
}
