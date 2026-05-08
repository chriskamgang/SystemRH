<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationCampaign;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationScore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    /**
     * List evaluation campaigns with stats.
     * Supports filter by status and year. Paginated to 20.
     */
    public function index(Request $request)
    {
        $query = EvaluationCampaign::withCount('evaluations')
            ->with('evaluations')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(20);

        // Enrich each campaign with computed stats
        foreach ($campaigns as $campaign) {
            $campaign->computed_stats = $this->getCampaignStats($campaign);
        }

        // Global stats across all campaigns
        $globalStats = [
            'total_campaigns'       => EvaluationCampaign::count(),
            'active_campaigns'      => EvaluationCampaign::where('status', 'active')->count(),
            'draft_campaigns'       => EvaluationCampaign::where('status', 'draft')->count(),
            'closed_campaigns'      => EvaluationCampaign::where('status', 'closed')->count(),
            'total_evaluations'     => Evaluation::count(),
            'completed_evaluations' => Evaluation::where('status', 'validated')->count(),
            'avg_score'             => round(Evaluation::whereNotNull('overall_score')->avg('overall_score') ?? 0, 2),
        ];

        $availableYears = EvaluationCampaign::select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('admin.evaluations.index', compact('campaigns', 'globalStats', 'availableYears'));
    }

    /**
     * Show form to create a new evaluation campaign.
     */
    public function createCampaign()
    {
        $employees = User::where('is_active', true)
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'employee_id']);

        return view('admin.evaluations.create-campaign', compact('employees'));
    }

    /**
     * Store a new evaluation campaign (with criteria).
     */
    public function storeCampaign(Request $request)
    {
        $request->validate([
            'title'                      => 'required|string|max:255',
            'description'                => 'nullable|string|max:1000',
            'year'                       => 'required|integer|min:2000|max:2100',
            'start_date'                 => 'required|date',
            'end_date'                   => 'required|date|after_or_equal:start_date',
            'status'                     => 'required|in:draft,active,closed',
            'criteria'                   => 'required|array|min:1',
            'criteria.*.name'            => 'required|string|max:255',
            'criteria.*.description'     => 'nullable|string|max:500',
            'criteria.*.max_score'       => 'required|integer|min:1|max:100',
            'criteria.*.weight'          => 'required|integer|min:1|max:100',
        ]);

        DB::transaction(function () use ($request) {
            $campaign = EvaluationCampaign::create([
                'title'       => $request->title,
                'description' => $request->description,
                'year'        => $request->year,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'status'      => $request->status,
            ]);

            foreach ($request->criteria as $index => $criteriaData) {
                EvaluationCriteria::create([
                    'campaign_id' => $campaign->id,
                    'name'        => $criteriaData['name'],
                    'description' => $criteriaData['description'] ?? null,
                    'max_score'   => (int) $criteriaData['max_score'],
                    'weight'      => (int) $criteriaData['weight'],
                    'sort_order'  => $index + 1,
                ]);
            }
        });

        return redirect()->route('admin.evaluations.index')
            ->with('success', 'Campagne d\'évaluation créée avec succès.');
    }

    /**
     * Show campaign details with all evaluations and scores.
     */
    public function show($id)
    {
        $campaign = EvaluationCampaign::with([
            'criteria',
            'evaluations.employee',
            'evaluations.evaluator',
            'evaluations.scores.criteria',
        ])->findOrFail($id);

        $stats = $this->getCampaignStats($campaign);

        // Score distribution buckets
        $scoreDistribution = $campaign->evaluations
            ->filter(fn($e) => $e->overall_score !== null)
            ->groupBy(function ($e) {
                $score = (float) $e->overall_score;
                if ($score >= 4.5) return 'Excellent (4.5–5)';
                if ($score >= 3.5) return 'Bien (3.5–4.5)';
                if ($score >= 2.5) return 'Satisfaisant (2.5–3.5)';
                if ($score >= 1.5) return 'À améliorer (1.5–2.5)';
                return 'Insuffisant (<1.5)';
            })
            ->map->count();

        return view('admin.evaluations.show', compact('campaign', 'stats', 'scoreDistribution'));
    }

    /**
     * Show individual evaluation detail.
     */
    public function showEvaluation($id)
    {
        $evaluation = Evaluation::with([
            'campaign.criteria',
            'employee',
            'evaluator',
            'scores.criteria',
        ])->findOrFail($id);

        return view('admin.evaluations.detail', compact('evaluation'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Compute per-campaign statistics from already-loaded evaluations.
     */
    private function getCampaignStats(EvaluationCampaign $campaign): array
    {
        $evaluations = $campaign->evaluations;

        $totalEvaluations      = $evaluations->count();
        $pendingEvaluations    = $evaluations->where('status', 'pending')->count();
        $inProgressEvaluations = $evaluations->whereIn('status', ['self_evaluated', 'evaluated'])->count();
        $completedEvaluations  = $evaluations->where('status', 'validated')->count();

        $scores    = $evaluations->whereNotNull('overall_score')->pluck('overall_score')->map(fn($s) => (float) $s);
        $avgScore  = $scores->isNotEmpty() ? round($scores->avg(), 2) : null;
        $maxScore  = $scores->isNotEmpty() ? round($scores->max(), 2) : null;
        $minScore  = $scores->isNotEmpty() ? round($scores->min(), 2) : null;

        $progressPct = $totalEvaluations > 0
            ? round(($completedEvaluations / $totalEvaluations) * 100)
            : 0;

        return compact(
            'totalEvaluations',
            'pendingEvaluations',
            'inProgressEvaluations',
            'completedEvaluations',
            'avgScore',
            'maxScore',
            'minScore',
            'progressPct'
        );
    }
}
