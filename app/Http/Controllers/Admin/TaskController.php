<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

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
            'due_date' => 'nullable|date',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'created_by' => auth()->id(),
        ]);

        $task->users()->attach($request->user_ids);

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
            'due_date' => 'nullable|date',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $task = Task::findOrFail($id);
        $task->update([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
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
}
