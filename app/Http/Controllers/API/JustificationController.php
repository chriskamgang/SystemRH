<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Tardiness;
use App\Models\JustificationRequest;
use Illuminate\Http\Request;

class JustificationController extends Controller
{
    /**
     * Liste des absences de l'employe
     */
    public function absences(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $absences = Absence::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('campus')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($absence) {
                // Verifier si une demande de justification existe
                $justificationRequest = JustificationRequest::where('user_id', $absence->user_id)
                    ->where('type', 'absence')
                    ->where('date', $absence->date)
                    ->latest()
                    ->first();

                return [
                    'id' => $absence->id,
                    'date' => $absence->date->format('Y-m-d'),
                    'type' => $absence->type,
                    'type_label' => match($absence->type) {
                        'no_check_in' => 'Pas de pointage',
                        'early_checkout' => 'Depart anticipe',
                        'full_day' => 'Journee complete',
                        default => $absence->type,
                    },
                    'campus' => $absence->campus?->name,
                    'is_justified' => $absence->is_justified,
                    'justification' => $absence->justification,
                    'justification_status' => $justificationRequest?->status,
                ];
            });

        return response()->json([
            'success' => true,
            'absences' => $absences,
            'month' => (int) $month,
            'year' => (int) $year,
        ]);
    }

    /**
     * Liste des retards de l'employe
     */
    public function tardiness(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $tardiness = Tardiness::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('campus')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($t) {
                $justificationRequest = JustificationRequest::where('user_id', $t->user_id)
                    ->where('type', 'tardiness')
                    ->where('date', $t->date)
                    ->latest()
                    ->first();

                return [
                    'id' => $t->id,
                    'date' => $t->date instanceof \Carbon\Carbon ? $t->date->format('Y-m-d') : $t->date,
                    'campus' => $t->campus?->name,
                    'scheduled_time' => $t->scheduled_time,
                    'actual_time' => $t->actual_time,
                    'late_minutes' => $t->late_minutes,
                    'status' => $t->status,
                    'justification_status' => $justificationRequest?->status,
                ];
            });

        return response()->json([
            'success' => true,
            'tardiness' => $tardiness,
            'month' => (int) $month,
            'year' => (int) $year,
        ]);
    }

    /**
     * Resume absences + retards du mois
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $totalAbsences = Absence::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)->count();
        $justifiedAbsences = Absence::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('is_justified', true)->count();

        $totalTardiness = Tardiness::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)->count();
        $totalLateMinutes = Tardiness::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->sum('late_minutes');
        $justifiedTardiness = Tardiness::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('status', 'justified')->count();

        $pendingRequests = JustificationRequest::where('user_id', $user->id)
            ->where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'absences' => [
                    'total' => $totalAbsences,
                    'justified' => $justifiedAbsences,
                    'unjustified' => $totalAbsences - $justifiedAbsences,
                ],
                'tardiness' => [
                    'total' => $totalTardiness,
                    'justified' => $justifiedTardiness,
                    'total_late_minutes' => (int) $totalLateMinutes,
                ],
                'pending_requests' => $pendingRequests,
            ],
            'month' => (int) $month,
            'year' => (int) $year,
        ]);
    }

    /**
     * Liste des demandes de justification de l'employe
     */
    public function myRequests(Request $request)
    {
        $user = $request->user();

        $requests = JustificationRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'type' => $r->type,
                    'type_label' => $r->type === 'absence' ? 'Absence' : 'Retard',
                    'date' => $r->date->format('Y-m-d'),
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'has_attachment' => !empty($r->attachment),
                    'review_comment' => $r->review_comment,
                    'reviewed_at' => $r->reviewed_at?->format('d/m/Y H:i'),
                    'created_at' => $r->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'requests' => $requests,
        ]);
    }

    /**
     * Soumettre une demande de justification
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:absence,tardiness',
            'date' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = $request->user();

        // Verifier qu'il y a bien une absence ou un retard a cette date
        if ($request->type === 'absence') {
            $exists = Absence::where('user_id', $user->id)
                ->whereDate('date', $request->date)->exists();
        } else {
            $exists = Tardiness::where('user_id', $user->id)
                ->whereDate('date', $request->date)->exists();
        }

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun(e) ' . ($request->type === 'absence' ? 'absence' : 'retard') . ' trouve(e) a cette date.',
            ], 422);
        }

        // Verifier qu'il n'y a pas deja une demande en attente
        $pending = JustificationRequest::where('user_id', $user->id)
            ->where('type', $request->type)
            ->whereDate('date', $request->date)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return response()->json([
                'success' => false,
                'message' => 'Une demande est deja en attente pour cette date.',
            ], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('justification_attachments', 'public');
        }

        $justification = JustificationRequest::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'date' => $request->date,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de justification soumise.',
        ], 201);
    }
}
