<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use App\Models\TrainingEnrollment;
use App\Models\TrainingMaterial;
use App\Models\TrainingMaterialProgress;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    /**
     * Catalogue des formations disponibles
     */
    public function catalog(Request $request)
    {
        $query = TrainingProgram::where('is_active', true);

        if ($request->query('category')) {
            $query->where('category', $request->query('category'));
        }
        if ($request->query('type')) {
            $query->where('type', $request->query('type'));
        }
        if ($request->query('level')) {
            $query->where('level', $request->query('level'));
        }

        $programs = $query->withCount(['materials', 'enrollments'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($p) use ($request) {
                $enrollment = $p->enrollments->firstWhere('user_id', $request->user()->id);
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'description' => $p->description,
                    'type' => $p->type,
                    'type_label' => TrainingProgram::TYPE_LABELS[$p->type] ?? $p->type,
                    'category' => $p->category,
                    'level' => $p->level,
                    'level_label' => TrainingProgram::LEVEL_LABELS[$p->level] ?? $p->level,
                    'duration_hours' => $p->duration_hours,
                    'is_mandatory' => $p->is_mandatory,
                    'materials_count' => $p->materials_count,
                    'enrolled_count' => $p->enrollments_count,
                    'my_enrollment' => $enrollment ? [
                        'status' => $enrollment->status,
                        'progress' => $enrollment->progress,
                    ] : null,
                ];
            });

        // Categories distinctes
        $categories = TrainingProgram::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json([
            'success' => true,
            'programs' => $programs,
            'categories' => $categories,
        ]);
    }

    /**
     * Mes formations (inscriptions)
     */
    public function myEnrollments(Request $request)
    {
        $enrollments = TrainingEnrollment::where('user_id', $request->user()->id)
            ->with(['program:id,title,type,category,level,duration_hours', 'session'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'program' => [
                        'id' => $e->program->id,
                        'title' => $e->program->title,
                        'type' => $e->program->type,
                        'type_label' => TrainingProgram::TYPE_LABELS[$e->program->type] ?? $e->program->type,
                        'category' => $e->program->category,
                        'duration_hours' => $e->program->duration_hours,
                    ],
                    'status' => $e->status,
                    'progress' => $e->progress,
                    'score' => $e->score,
                    'started_at' => $e->started_at?->format('d/m/Y'),
                    'completed_at' => $e->completed_at?->format('d/m/Y'),
                    'session' => $e->session ? [
                        'start_date' => $e->session->start_date->format('d/m/Y H:i'),
                        'location' => $e->session->location,
                        'meeting_link' => $e->session->meeting_link,
                    ] : null,
                ];
            });

        return response()->json(['success' => true, 'enrollments' => $enrollments]);
    }

    /**
     * Detail d'une formation avec contenus
     */
    public function programDetail(Request $request, $id)
    {
        $program = TrainingProgram::with(['materials', 'sessions' => function ($q) {
            $q->where('status', '!=', 'cancelled')->orderBy('start_date');
        }])->findOrFail($id);

        $enrollment = TrainingEnrollment::where('user_id', $request->user()->id)
            ->where('training_program_id', $id)
            ->first();

        // Progression par materiau
        $materialProgress = [];
        if ($enrollment) {
            $materialProgress = TrainingMaterialProgress::where('user_id', $request->user()->id)
                ->whereIn('training_material_id', $program->materials->pluck('id'))
                ->get()
                ->keyBy('training_material_id');
        }

        $materials = $program->materials->map(function ($m) use ($materialProgress) {
            $progress = $materialProgress[$m->id] ?? null;
            return [
                'id' => $m->id,
                'title' => $m->title,
                'description' => $m->description,
                'type' => $m->type,
                'duration_minutes' => $m->duration_minutes,
                'is_required' => $m->is_required,
                'is_completed' => $progress?->is_completed ?? false,
                'score' => $progress?->score,
            ];
        });

        $sessions = $program->sessions->map(function ($s) {
            return [
                'id' => $s->id,
                'trainer_name' => $s->trainer_name,
                'location' => $s->location,
                'meeting_link' => $s->meeting_link,
                'start_date' => $s->start_date->format('d/m/Y H:i'),
                'end_date' => $s->end_date->format('d/m/Y H:i'),
                'max_participants' => $s->max_participants,
                'status' => $s->status,
            ];
        });

        return response()->json([
            'success' => true,
            'program' => [
                'id' => $program->id,
                'title' => $program->title,
                'description' => $program->description,
                'type' => $program->type,
                'type_label' => TrainingProgram::TYPE_LABELS[$program->type] ?? $program->type,
                'category' => $program->category,
                'level' => $program->level,
                'level_label' => TrainingProgram::LEVEL_LABELS[$program->level] ?? $program->level,
                'duration_hours' => $program->duration_hours,
                'is_mandatory' => $program->is_mandatory,
            ],
            'enrollment' => $enrollment ? [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'progress' => $enrollment->progress,
                'score' => $enrollment->score,
            ] : null,
            'materials' => $materials,
            'sessions' => $sessions,
        ]);
    }

    /**
     * S'inscrire a une formation
     */
    public function enroll(Request $request, $id)
    {
        $program = TrainingProgram::where('is_active', true)->findOrFail($id);

        $exists = TrainingEnrollment::where('user_id', $request->user()->id)
            ->where('training_program_id', $id)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Vous etes deja inscrit.'], 409);
        }

        $enrollment = TrainingEnrollment::create([
            'user_id' => $request->user()->id,
            'training_program_id' => $id,
            'training_session_id' => $request->session_id,
            'status' => 'enrolled',
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inscription reussie.',
            'enrollment_id' => $enrollment->id,
        ], 201);
    }

    /**
     * Marquer un materiau comme termine
     */
    public function completeMaterial(Request $request, $programId, $materialId)
    {
        $material = TrainingMaterial::where('training_program_id', $programId)
            ->findOrFail($materialId);

        $enrollment = TrainingEnrollment::where('user_id', $request->user()->id)
            ->where('training_program_id', $programId)
            ->whereNotIn('status', ['cancelled'])
            ->firstOrFail();

        TrainingMaterialProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'training_material_id' => $materialId],
            ['is_completed' => true, 'score' => $request->score, 'completed_at' => now()]
        );

        // Recalculer la progression
        $totalMaterials = TrainingMaterial::where('training_program_id', $programId)
            ->where('is_required', true)->count();
        $completedMaterials = TrainingMaterialProgress::where('user_id', $request->user()->id)
            ->where('is_completed', true)
            ->whereHas('material', function ($q) use ($programId) {
                $q->where('training_program_id', $programId)->where('is_required', true);
            })->count();

        $progress = $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100) : 0;

        $enrollment->update([
            'progress' => $progress,
            'status' => $progress >= 100 ? 'completed' : 'in_progress',
            'completed_at' => $progress >= 100 ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'progress' => $progress,
            'program_completed' => $progress >= 100,
        ]);
    }
}
