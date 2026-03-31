<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = $user->tasks()
            ->with('creator:id,first_name,last_name')
            ->orderByRaw("CASE WHEN task_user.status = 'pending' THEN 0 WHEN task_user.status = 'in_progress' THEN 1 ELSE 2 END")
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'my_status' => $task->pivot->status,
                    'my_note' => $task->pivot->note,
                    'completed_at' => $task->pivot->completed_at,
                    'due_date' => $task->due_date?->format('Y-m-d'),
                    'creator_name' => $task->creator ? $task->creator->full_name : null,
                    'created_at' => $task->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $task = $user->tasks()->with('creator:id,first_name,last_name')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority,
                'status' => $task->status,
                'my_status' => $task->pivot->status,
                'my_note' => $task->pivot->note,
                'completed_at' => $task->pivot->completed_at,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'creator_name' => $task->creator ? $task->creator->full_name : null,
                'created_at' => $task->created_at->toIso8601String(),
            ],
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'note' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $task = $user->tasks()->findOrFail($id);

        $pivotData = [
            'status' => $request->status,
            'note' => $request->note,
        ];

        if ($request->status === 'completed') {
            $pivotData['completed_at'] = now();
        }

        $user->tasks()->updateExistingPivot($id, $pivotData);

        // Mettre à jour le statut global de la tâche si tous les assignés ont terminé
        $task->refresh();
        $allCompleted = $task->users()->wherePivot('status', '!=', 'completed')->count() === 0;
        if ($allCompleted && $task->users()->count() > 0) {
            $task->update(['status' => 'completed']);
        } elseif ($request->status === 'in_progress' && $task->status === 'pending') {
            $task->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut de la tâche mis à jour.',
        ]);
    }
}
