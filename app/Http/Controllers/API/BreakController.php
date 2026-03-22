<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BreakLog;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BreakController extends Controller
{
    /**
     * Démarrer la pause
     * POST /api/break/start
     */
    public function start(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();
        $today = $now->toDateString();

        // Vérifier que c'est un employé éligible à la pause (pas les vacataires)
        if (!in_array($user->employee_type, ['semi_permanent', 'enseignant_titulaire', 'administratif', 'technique', 'direction'])) {
            return response()->json([
                'success' => false,
                'message' => 'La pause n\'est disponible que pour le personnel permanent.',
            ], 403);
        }

        // Vérifier qu'il a un check-in actif
        $activeCheckIn = Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->whereDate('timestamp', $today)
            ->latest('timestamp')
            ->first();

        if (!$activeCheckIn) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez avoir un check-in actif pour prendre une pause.',
            ], 400);
        }

        // Vérifier qu'il n'a pas déjà une pause en cours
        $activeBreak = BreakLog::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('break_end')
            ->first();

        if ($activeBreak) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une pause en cours.',
                'data' => [
                    'break_id' => $activeBreak->id,
                    'break_start' => $activeBreak->break_start->format('H:i'),
                ],
            ], 400);
        }

        // Créer la pause
        $breakLog = BreakLog::create([
            'user_id' => $user->id,
            'campus_id' => $activeCheckIn->campus_id,
            'break_start' => $now,
            'date' => $today,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pause commencée. Bon appétit !',
            'data' => [
                'break_id' => $breakLog->id,
                'break_start' => $now->format('H:i'),
            ],
        ]);
    }

    /**
     * Terminer la pause
     * POST /api/break/end
     */
    public function end(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();
        $today = $now->toDateString();

        // Trouver la pause active
        $activeBreak = BreakLog::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('break_end')
            ->first();

        if (!$activeBreak) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune pause en cours.',
            ], 400);
        }

        // Calculer la durée
        $durationMinutes = $activeBreak->break_start->diffInMinutes($now);

        $activeBreak->update([
            'break_end' => $now,
            'duration_minutes' => $durationMinutes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bon retour ! Pause terminée.',
            'data' => [
                'break_id' => $activeBreak->id,
                'break_start' => $activeBreak->break_start->format('H:i'),
                'break_end' => $now->format('H:i'),
                'duration_minutes' => $durationMinutes,
            ],
        ]);
    }

    /**
     * Statut de la pause actuelle
     * GET /api/break/status
     */
    public function status()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $activeBreak = BreakLog::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNull('break_end')
            ->first();

        $todayBreaks = BreakLog::where('user_id', $user->id)
            ->where('date', $today)
            ->orderBy('break_start')
            ->get();

        $totalBreakMinutes = $todayBreaks->sum('duration_minutes');

        return response()->json([
            'success' => true,
            'data' => [
                'on_break' => $activeBreak !== null,
                'active_break' => $activeBreak ? [
                    'break_id' => $activeBreak->id,
                    'break_start' => $activeBreak->break_start->format('H:i'),
                    'elapsed_minutes' => $activeBreak->break_start->diffInMinutes(now()),
                ] : null,
                'today_breaks' => $todayBreaks->map(function ($b) {
                    return [
                        'break_start' => $b->break_start->format('H:i'),
                        'break_end' => $b->break_end?->format('H:i'),
                        'duration_minutes' => $b->duration_minutes,
                    ];
                }),
                'total_break_minutes' => $totalBreakMinutes,
            ],
        ]);
    }
}
