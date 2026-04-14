<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['creator', 'users'])->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhereHas('users', function($uq) use ($searchTerm) {
                      $uq->where('first_name', 'like', "%{$searchTerm}%")
                         ->orWhere('last_name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $tasks = $query->paginate(20);
        $employees = User::where('role_id', '!=', 1)->orderBy('last_name')->get();
        $jobPositions = \App\Models\JobPosition::orderBy('name')->get();

        return view('admin.tasks.index', compact('tasks', 'employees', 'jobPositions'));
    }

    public function exportPdf(Request $request)
    {
        $query = Task::with(['creator', 'users'])->latest();

        $filterParts = [];

        if ($request->filled('status')) {
            $query->where('status', $request->status);
            $filterParts[] = 'Statut: ' . ucfirst($request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
            $filterParts[] = 'Priorité: ' . ucfirst($request->priority);
        }

        $tasks = $query->get();
        $filters = !empty($filterParts) ? implode(' | ', $filterParts) : null;

        $pdf = Pdf::loadView('admin.tasks.pdf.report', compact('tasks', 'filters'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('rapport-taches.pdf');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'job_position_id' => 'nullable|exists:job_positions,id',
        ]);

        // Déterminer les utilisateurs finaux (individuels + ceux du poste)
        $finalUserIds = $request->user_ids ?? [];
        
        if ($request->job_position_id) {
            $positionUserIds = User::where('job_position_id', $request->job_position_id)
                ->pluck('id')
                ->toArray();
            $finalUserIds = array_unique(array_merge($finalUserIds, $positionUserIds));
        }

        if (empty($finalUserIds)) {
            return response()->json(['success' => false, 'message' => 'Veuillez sélectionner au moins un employé ou un poste.'], 422);
        }

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'created_by' => auth()->id(),
        ]);

        $task->users()->attach($finalUserIds);

        // Envoyer une notification push aux employés assignés
        try {
            $pushService = new PushNotificationService();
            $assignedUsers = User::whereIn('id', $finalUserIds)->get();
            $pushService->sendToMultipleUsers(
                $assignedUsers,
                'Nouvelle tâche assignée',
                "Tâche : {$task->title}" . ($task->due_date ? " - Échéance : {$task->due_date->format('d/m/Y')}" : ''),
                [
                    'type' => 'task_assigned',
                    'task_id' => (string) $task->id,
                    'task_title' => $task->title,
                ],
                'task'
            );
        } catch (\Exception $e) {
            \Log::warning('Notification tâche échouée: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Tâche créée avec succès.']);
    }

    public function show($id)
    {
        $task = Task::with(['creator', 'users'])->findOrFail($id);
        return response()->json(['task' => $task]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'job_position_id' => 'nullable|exists:job_positions,id',
        ]);

        // Déterminer les utilisateurs finaux
        $finalUserIds = $request->user_ids ?? [];
        
        if ($request->job_position_id) {
            $positionUserIds = User::where('job_position_id', $request->job_position_id)
                ->pluck('id')
                ->toArray();
            $finalUserIds = array_unique(array_merge($finalUserIds, $positionUserIds));
        }

        if (empty($finalUserIds)) {
            return response()->json(['success' => false, 'message' => 'Veuillez sélectionner au moins un employé ou un poste.'], 422);
        }

        $task = Task::findOrFail($id);
        $task->update([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
        ]);

        $task->users()->sync($finalUserIds);

        return response()->json(['success' => true, 'message' => 'Tâche mise à jour avec succès.']);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['success' => true, 'message' => 'Tâche supprimée avec succès.']);
    }

    /**
     * Approuver la coupure/pénalité pour un employé qui n'a pas fait sa tâche.
     */
    public function approvePenalty(Request $request, $taskId, $userId)
    {
        $pivot = DB::table('task_user')
            ->where('task_id', $taskId)
            ->where('user_id', $userId)
            ->first();

        $penaltyAmount = $pivot->penalty_amount ?? 0;

        DB::table('task_user')
            ->where('task_id', $taskId)
            ->where('user_id', $userId)
            ->update([
                'penalty_approved' => true,
                'penalty_approved_at' => now(),
                'penalty_approved_by' => auth()->id(),
            ]);

        $user = User::findOrFail($userId);

        return response()->json([
            'success' => true,
            'message' => "Coupure de " . number_format($penaltyAmount, 0, ',', '.') . " FCFA approuvée pour {$user->full_name}.",
        ]);
    }

    /**
     * Annuler une pénalité approuvée.
     */
    public function cancelPenalty(Request $request, $taskId, $userId)
    {
        DB::table('task_user')
            ->where('task_id', $taskId)
            ->where('user_id', $userId)
            ->update([
                'penalty_approved' => false,
                'penalty_approved_at' => null,
                'penalty_approved_by' => null,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Coupure annulée.',
        ]);
    }
}
