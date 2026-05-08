<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JustificationRequest;
use App\Models\Absence;
use App\Models\Tardiness;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class JustificationRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = JustificationRequest::with(['user', 'reviewer'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(20);
        $pendingCount = JustificationRequest::where('status', 'pending')->count();

        return view('admin.justifications.index', compact('requests', 'pendingCount'));
    }

    public function approve(Request $request, $id)
    {
        $justification = JustificationRequest::findOrFail($id);

        if (!$justification->isPending()) {
            return back()->with('error', 'Cette demande a deja ete traitee.');
        }

        $justification->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_comment' => $request->comment,
        ]);

        // Marquer l'absence ou le retard comme justifie
        if ($justification->type === 'absence') {
            Absence::where('user_id', $justification->user_id)
                ->whereDate('date', $justification->date)
                ->update([
                    'is_justified' => true,
                    'justification' => $justification->reason,
                    'justified_by' => auth()->id(),
                    'justified_at' => now(),
                ]);
        } else {
            Tardiness::where('user_id', $justification->user_id)
                ->whereDate('date', $justification->date)
                ->update([
                    'status' => 'justified',
                    'justification' => $justification->reason,
                ]);
        }

        $this->notifyEmployee($justification, 'approved');

        return back()->with('success', 'Justification approuvee. Le ' . ($justification->type === 'absence' ? "l'absence" : 'retard') . ' a ete marque comme justifie.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['comment' => 'required|string|max:500']);

        $justification = JustificationRequest::findOrFail($id);

        if (!$justification->isPending()) {
            return back()->with('error', 'Cette demande a deja ete traitee.');
        }

        $justification->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_comment' => $request->comment,
        ]);

        $this->notifyEmployee($justification, 'rejected');

        return back()->with('success', 'Demande rejetee.');
    }

    private function notifyEmployee(JustificationRequest $justification, string $decision)
    {
        try {
            $user = $justification->user;
            if (!$user->fcm_token) return;

            $typeLabel = $justification->type === 'absence' ? 'absence' : 'retard';
            $dateFormatted = $justification->date->format('d/m/Y');

            $title = $decision === 'approved'
                ? 'Justification approuvee'
                : 'Justification refusee';

            $body = $decision === 'approved'
                ? "Votre justification de $typeLabel du $dateFormatted a ete approuvee."
                : "Votre justification de $typeLabel du $dateFormatted a ete refusee.";

            $pushService = new PushNotificationService();
            $pushService->sendToUser($user, $title, $body, [
                'type' => 'justification_decision',
                'justification_id' => (string) $justification->id,
                'decision' => $decision,
            ], 'justification');
        } catch (\Exception $e) {
            \Log::warning('Erreur notification justification: ' . $e->getMessage());
        }
    }
}
