<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    /**
     * Mes evaluations
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $evaluations = Evaluation::where('employee_id', $user->id)
            ->with(['campaign', 'evaluator:id,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($eval) {
                return [
                    'id' => $eval->id,
                    'campaign' => [
                        'id' => $eval->campaign->id,
                        'title' => $eval->campaign->title,
                        'year' => $eval->campaign->year,
                    ],
                    'status' => $eval->status,
                    'overall_score' => $eval->overall_score,
                    'evaluator' => $eval->evaluator ? $eval->evaluator->full_name : null,
                    'employee_comments' => $eval->employee_comments,
                    'evaluator_comments' => $eval->evaluator_comments,
                    'objectives_next_year' => $eval->objectives_next_year,
                    'training_needs' => $eval->training_needs,
                    'self_evaluated_at' => $eval->self_evaluated_at?->format('d/m/Y'),
                    'evaluated_at' => $eval->evaluated_at?->format('d/m/Y'),
                    'created_at' => $eval->created_at->format('d/m/Y'),
                ];
            });

        return response()->json(['success' => true, 'evaluations' => $evaluations]);
    }

    /**
     * Detail d'une evaluation avec criteres et scores
     */
    public function show(Request $request, $id)
    {
        $eval = Evaluation::where('employee_id', $request->user()->id)
            ->with(['campaign.criteria', 'scores.criteria', 'evaluator:id,first_name,last_name'])
            ->findOrFail($id);

        $criteria = $eval->campaign->criteria->map(function ($c) use ($eval) {
            $score = $eval->scores->firstWhere('criteria_id', $c->id);
            return [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'max_score' => $c->max_score,
                'weight' => $c->weight,
                'employee_score' => $score?->employee_score,
                'evaluator_score' => $score?->evaluator_score,
                'comment' => $score?->comment,
            ];
        });

        return response()->json([
            'success' => true,
            'evaluation' => [
                'id' => $eval->id,
                'campaign' => ['title' => $eval->campaign->title, 'year' => $eval->campaign->year],
                'status' => $eval->status,
                'overall_score' => $eval->overall_score,
                'evaluator' => $eval->evaluator?->full_name,
                'employee_comments' => $eval->employee_comments,
                'evaluator_comments' => $eval->evaluator_comments,
                'objectives_next_year' => $eval->objectives_next_year,
                'training_needs' => $eval->training_needs,
            ],
            'criteria' => $criteria,
        ]);
    }

    /**
     * Auto-evaluation par l'employe
     */
    public function selfEvaluate(Request $request, $id)
    {
        $request->validate([
            'scores' => 'required|array',
            'scores.*.criteria_id' => 'required|exists:evaluation_criteria,id',
            'scores.*.score' => 'required|integer|min:0',
            'comments' => 'nullable|string|max:2000',
        ]);

        $eval = Evaluation::where('employee_id', $request->user()->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        // Verifier que la campagne est active
        if (!$eval->campaign->isActive()) {
            return response()->json(['success' => false, 'message' => 'La campagne n\'est plus active.'], 400);
        }

        foreach ($request->scores as $scoreData) {
            EvaluationScore::updateOrCreate(
                ['evaluation_id' => $eval->id, 'criteria_id' => $scoreData['criteria_id']],
                ['employee_score' => $scoreData['score']]
            );
        }

        $eval->update([
            'status' => 'self_evaluated',
            'employee_comments' => $request->comments,
            'self_evaluated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Auto-evaluation soumise avec succes.',
        ]);
    }
}
