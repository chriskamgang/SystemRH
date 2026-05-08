<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = Complaint::with('user')->latest()->paginate(20);
        return view('admin.complaints.index', compact('complaints'));
    }

    public function show($id)
    {
        $complaint = Complaint::with('user')->findOrFail($id);
        return view('admin.complaints.show', compact('complaint'));
    }

    public function respond(Request $request, $id)
    {
        $request->validate([
            'admin_response' => 'required|string',
            'status' => 'required|in:in_review,resolved',
        ]);

        $complaint = Complaint::findOrFail($id);
        $complaint->update([
            'admin_response' => $request->admin_response,
            'status' => $request->status,
        ]);

        // Notifier l'employe
        try {
            $user = $complaint->user;
            if ($user) {
                $statusLabel = $request->status === 'resolved' ? 'resolue' : 'en cours de traitement';
                $pushService = new PushNotificationService();
                $pushService->sendToUser(
                    $user,
                    'Reponse a votre reclamation',
                    "Votre reclamation est $statusLabel. Consultez la reponse dans l'application.",
                    ['type' => 'complaint_response', 'complaint_id' => (string) $id],
                    'system'
                );
            }
        } catch (\Exception $e) {
            \Log::warning('Notification reclamation echouee: ' . $e->getMessage());
        }

        return redirect()->route('admin.complaints.show', $id)->with('success', 'Réponse envoyée avec succès.');
    }

    public function destroy($id)
    {
        $complaint = Complaint::findOrFail($id);
        $complaint->delete();

        return redirect()->route('admin.complaints.index')->with('success', 'Plainte supprimée.');
    }
}
