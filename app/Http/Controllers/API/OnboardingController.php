<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OnboardingProcess;
use App\Models\OnboardingTask;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Mes processus onboarding/offboarding
     */
    public function index(Request $request)
    {
        $processes = OnboardingProcess::where('user_id', $request->user()->id)
            ->with(['template:id,name,type', 'tasks'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) {
                $totalTasks = $p->tasks->count();
                $completedTasks = $p->tasks->where('status', 'completed')->count();

                return [
                    'id' => $p->id,
                    'type' => $p->type,
                    'type_label' => $p->type === 'onboarding' ? 'Integration' : 'Depart',
                    'template_name' => $p->template->name,
                    'status' => $p->status,
                    'start_date' => $p->start_date->format('d/m/Y'),
                    'target_date' => $p->target_date?->format('d/m/Y'),
                    'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                ];
            });

        return response()->json(['success' => true, 'processes' => $processes]);
    }

    /**
     * Detail d'un processus avec taches
     */
    public function show(Request $request, $id)
    {
        $process = OnboardingProcess::where('user_id', $request->user()->id)
            ->with(['template:id,name,type', 'tasks' => function ($q) {
                $q->orderBy('sort_order');
            }])
            ->findOrFail($id);

        $tasks = $process->tasks->map(function ($t) {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'assigned_to' => $t->assigned_to,
                'assigned_label' => match($t->assigned_to) {
                    'employee' => 'Vous',
                    'hr' => 'RH',
                    'manager' => 'Manager',
                    'it' => 'Informatique',
                    default => $t->assigned_to,
                },
                'status' => $t->status,
                'due_date' => $t->due_date?->format('d/m/Y'),
                'completed_date' => $t->completed_date?->format('d/m/Y'),
                'notes' => $t->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'process' => [
                'id' => $process->id,
                'type' => $process->type,
                'template_name' => $process->template->name,
                'status' => $process->status,
                'start_date' => $process->start_date->format('d/m/Y'),
                'progress' => $process->progress,
            ],
            'tasks' => $tasks,
        ]);
    }

    /**
     * Marquer une tache comme terminee (taches assignees a l'employe)
     */
    public function completeTask(Request $request, $processId, $taskId)
    {
        $process = OnboardingProcess::where('user_id', $request->user()->id)->findOrFail($processId);

        $task = OnboardingTask::where('process_id', $process->id)
            ->where('id', $taskId)
            ->where('assigned_to', 'employee')
            ->where('status', '!=', 'completed')
            ->firstOrFail();

        $task->update([
            'status' => 'completed',
            'completed_date' => now(),
            'completed_by' => $request->user()->id,
            'notes' => $request->notes,
        ]);

        // Verifier si le processus est termine
        $remaining = OnboardingTask::where('process_id', $process->id)
            ->whereNotIn('status', ['completed', 'skipped'])
            ->count();

        if ($remaining === 0) {
            $process->update(['status' => 'completed', 'completed_date' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tache completee.',
            'process_completed' => $remaining === 0,
        ]);
    }
}
