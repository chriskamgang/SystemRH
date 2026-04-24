<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MoratoriumRequest;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MoratoriumController extends Controller
{
    protected $notificationService;

    public function __construct(PushNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste toutes les demandes de moratoire
     */
    public function index(Request $request)
    {
        $query = MoratoriumRequest::with(['student', 'validator'])
            ->orderBy('created_at', 'desc');

        // Filtres optionnels
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15);

        return view('admin.moratoriums.index', compact('requests'));
    }

    /**
     * Approuver une demande
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'observation' => 'nullable|string',
        ]);

        $moratorium = MoratoriumRequest::findOrFail($id);
        $moratorium->update([
            'status' => 'approved',
            'observation' => $request->observation,
            'validated_by' => Auth::id(),
            'validated_at' => now(),
        ]);

        // Envoyer une notification push
        $this->notificationService->sendToUser(
            $moratorium->student,
            "Moratoire Approuvé",
            "Votre demande de moratoire a été approuvée." . ($request->observation ? " Note: " . $request->observation : ""),
            ['type' => 'moratorium_status', 'status' => 'approved']
        );

        return redirect()->back()->with('success', 'La demande de moratoire a été approuvée.');
    }

    /**
     * Rejeter une demande
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'observation' => 'required|string',
        ]);

        $moratorium = MoratoriumRequest::findOrFail($id);
        $moratorium->update([
            'status' => 'rejected',
            'observation' => $request->observation,
            'validated_by' => Auth::id(),
            'validated_at' => now(),
        ]);

        // Envoyer une notification push
        $this->notificationService->sendToUser(
            $moratorium->student,
            "Moratoire Rejeté",
            "Votre demande de moratoire a été rejetée. Motif: " . $request->observation,
            ['type' => 'moratorium_status', 'status' => 'rejected']
        );

        return redirect()->back()->with('success', 'La demande de moratoire a été rejetée.');
    }
}
