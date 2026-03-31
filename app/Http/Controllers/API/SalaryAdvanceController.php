<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SalaryAdvanceRequest;
use Illuminate\Http\Request;

class SalaryAdvanceController extends Controller
{
    /**
     * Liste des demandes d'avance de l'employé connecté.
     */
    public function index(Request $request)
    {
        $requests = SalaryAdvanceRequest::where('user_id', $request->user()->id)
            ->with('reviewer:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'amount' => $r->amount,
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'admin_note' => $r->admin_note,
                    'reviewer_name' => $r->reviewer ? $r->reviewer->full_name : null,
                    'reviewed_at' => $r->reviewed_at?->toIso8601String(),
                    'created_at' => $r->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Soumettre une nouvelle demande d'avance.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'reason' => 'required|string|max:1000',
        ]);

        $user = $request->user();

        // Vérifier s'il y a déjà une demande en attente
        $pending = SalaryAdvanceRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une demande en attente. Veuillez patienter.',
            ], 400);
        }

        $advance = SalaryAdvanceRequest::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'avance soumise avec succès. Elle sera examinée par l\'administration.',
            'data' => [
                'id' => $advance->id,
                'amount' => $advance->amount,
                'reason' => $advance->reason,
                'status' => $advance->status,
                'created_at' => $advance->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
