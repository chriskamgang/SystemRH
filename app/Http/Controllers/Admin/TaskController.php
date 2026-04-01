<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $query->where('title', 'like', "%{$request->search}%");
        }

        $tasks = $query->paginate(20);
        $employees = User::where('role_id', '!=', 1)->orderBy('last_name')->get();

        return view('admin.tasks.index', compact('tasks', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'created_by' => auth()->id(),
        ]);

        $task->users()->attach($request->user_ids);

        // Envoyer une notification push aux employés assignés
        try {
            $pushService = new PushNotificationService();
            $assignedUsers = User::whereIn('id', $request->user_ids)->get();
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
            // Ne pas bloquer la création si la notification échoue
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
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $task = Task::findOrFail($id);
        $task->update([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
        ]);

        $task->users()->sync($request->user_ids);

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
