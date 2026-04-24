<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MoratoriumRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MoratoriumController extends Controller
{
    /**
     * Liste des demandes de moratoire de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $requests = MoratoriumRequest::where('user_id', $user->id)
            ->with('validator:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'reason' => $request->reason,
                    'status' => $request->status,
                    'status_label' => $request->status_label,
                    'observation' => $request->observation,
                    'validator_name' => $request->validator ? $request->validator->full_name : null,
                    'validated_at' => $request->validated_at ? $request->validated_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Soumettre une nouvelle demande de moratoire
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Validation
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez fournir une motivation plus détaillée (minimum 10 caractères).',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Création de la demande
        $moratorium = MoratoriumRequest::create([
            'user_id' => $user->id,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Votre demande de moratoire a été soumise avec succès.',
            'data' => [
                'id' => $moratorium->id,
                'created_at' => $moratorium->created_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
