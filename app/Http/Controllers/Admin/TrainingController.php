<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingEnrollment;
use App\Models\User;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    /**
     * Liste des programmes de formation avec statistiques.
     */
    public function index(Request $request)
    {
        $query = TrainingProgram::withCount(['sessions', 'enrollments'])
            ->with('creator')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $programs = $query->paginate(20)->withQueryString();

        // Statistiques globales
        $totalPrograms   = TrainingProgram::count();
        $activePrograms  = TrainingProgram::where('is_active', true)->count();
        $activeSessions  = TrainingSession::where('status', 'scheduled')
                            ->where('start_date', '>=', now())
                            ->count();
        $totalEnrolled   = TrainingEnrollment::distinct('user_id')->count('user_id');
        $completedCount  = TrainingEnrollment::where('status', 'completed')->count();

        return view('admin.training.index', compact(
            'programs',
            'totalPrograms',
            'activePrograms',
            'activeSessions',
            'totalEnrolled',
            'completedCount'
        ));
    }

    /**
     * Formulaire de création d'un programme.
     */
    public function create()
    {
        $types  = TrainingProgram::TYPE_LABELS;
        $levels = TrainingProgram::LEVEL_LABELS;

        return view('admin.training.create', compact('types', 'levels'));
    }

    /**
     * Enregistrer un nouveau programme.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'type'           => 'required|in:online,presential,hybrid',
            'category'       => 'nullable|string|max:100',
            'duration_hours' => 'required|numeric|min:0',
            'level'          => 'required|in:beginner,intermediate,advanced',
            'is_mandatory'   => 'boolean',
            'is_active'      => 'boolean',
        ]);

        $validated['is_mandatory'] = $request->boolean('is_mandatory');
        $validated['is_active']    = $request->boolean('is_active', true);
        $validated['created_by']   = auth()->id();

        $program = TrainingProgram::create($validated);

        return redirect()
            ->route('admin.training.show', $program->id)
            ->with('success', 'Programme de formation créé avec succès.');
    }

    /**
     * Détails d'un programme : sessions et inscriptions.
     */
    public function show($id)
    {
        $program = TrainingProgram::with([
            'sessions.enrollments.user',
            'enrollments.user',
            'enrollments.session',
            'materials',
            'creator',
        ])->withCount(['sessions', 'enrollments'])->findOrFail($id);

        $types  = TrainingProgram::TYPE_LABELS;
        $levels = TrainingProgram::LEVEL_LABELS;

        $completedEnrollments = $program->enrollments->where('status', 'completed')->count();
        $inProgressEnrollments = $program->enrollments->where('status', 'in_progress')->count();

        return view('admin.training.show', compact(
            'program',
            'types',
            'levels',
            'completedEnrollments',
            'inProgressEnrollments'
        ));
    }

    /**
     * Formulaire d'ajout d'une session à un programme.
     */
    public function createSession($programId)
    {
        $program   = TrainingProgram::findOrFail($programId);
        $trainers  = User::orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.training.create-session', compact('program', 'trainers'));
    }

    /**
     * Enregistrer une nouvelle session.
     */
    public function storeSession(Request $request, $programId)
    {
        $program = TrainingProgram::findOrFail($programId);

        $validated = $request->validate([
            'trainer_name'    => 'required|string|max:255',
            'trainer_id'      => 'nullable|exists:users,id',
            'location'        => 'nullable|string|max:255',
            'meeting_link'    => 'nullable|url|max:500',
            'start_date'      => 'required|date|after_or_equal:today',
            'end_date'        => 'required|date|after:start_date',
            'max_participants' => 'required|integer|min:1',
            'status'          => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        $validated['training_program_id'] = $program->id;

        TrainingSession::create($validated);

        return redirect()
            ->route('admin.training.show', $program->id)
            ->with('success', 'Session créée avec succès.');
    }
}
