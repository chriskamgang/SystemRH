<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class RecruitmentController extends Controller
{
    /**
     * Offres d'emploi publiees
     */
    public function jobPostings(Request $request)
    {
        $query = JobPosting::where('status', 'published')
            ->with('department:id,name')
            ->withCount('applications');

        if ($request->query('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        if ($request->query('contract_type')) {
            $query->where('contract_type', $request->query('contract_type'));
        }

        $postings = $query->orderBy('published_at', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'description' => $p->description,
                    'department' => $p->department?->name,
                    'location' => $p->location,
                    'contract_type' => $p->contract_type,
                    'contract_label' => $p->contract_label,
                    'salary_range' => $p->salary_range,
                    'positions_count' => $p->positions_count,
                    'applications_count' => $p->applications_count,
                    'is_open' => $p->isOpen(),
                    'published_at' => $p->published_at?->format('d/m/Y'),
                    'closes_at' => $p->closes_at?->format('d/m/Y'),
                ];
            });

        return response()->json(['success' => true, 'postings' => $postings]);
    }

    /**
     * Detail d'une offre
     */
    public function jobPostingDetail($id)
    {
        $posting = JobPosting::with('department:id,name')
            ->withCount('applications')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'posting' => [
                'id' => $posting->id,
                'title' => $posting->title,
                'description' => $posting->description,
                'requirements' => $posting->requirements,
                'responsibilities' => $posting->responsibilities,
                'benefits' => $posting->benefits,
                'department' => $posting->department?->name,
                'location' => $posting->location,
                'contract_type' => $posting->contract_type,
                'contract_label' => $posting->contract_label,
                'salary_range' => $posting->salary_range,
                'positions_count' => $posting->positions_count,
                'applications_count' => $posting->applications_count,
                'is_open' => $posting->isOpen(),
                'published_at' => $posting->published_at?->format('d/m/Y'),
                'closes_at' => $posting->closes_at?->format('d/m/Y'),
            ],
        ]);
    }

    /**
     * Postuler a une offre (candidature interne ou referral)
     */
    public function apply(Request $request, $id)
    {
        $request->validate([
            'candidate_name' => 'required|string|max:255',
            'candidate_email' => 'required|email|max:255',
            'candidate_phone' => 'nullable|string|max:20',
            'cover_letter' => 'nullable|string|max:5000',
        ]);

        $posting = JobPosting::where('status', 'published')->findOrFail($id);

        if (!$posting->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Cette offre est fermee.'], 400);
        }

        // Verifier doublon
        $exists = JobApplication::where('job_posting_id', $id)
            ->where('candidate_email', $request->candidate_email)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Une candidature existe deja avec cet email.'], 409);
        }

        $application = JobApplication::create([
            'job_posting_id' => $id,
            'candidate_name' => $request->candidate_name,
            'candidate_email' => $request->candidate_email,
            'candidate_phone' => $request->candidate_phone,
            'cover_letter' => $request->cover_letter,
            'status' => 'new',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Candidature soumise avec succes.',
            'application_id' => $application->id,
        ], 201);
    }

    /**
     * Pipeline de candidatures (pour managers/RH)
     */
    public function pipeline($postingId)
    {
        $posting = JobPosting::findOrFail($postingId);

        $applications = JobApplication::where('job_posting_id', $postingId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'candidate_name' => $a->candidate_name,
                    'candidate_email' => $a->candidate_email,
                    'candidate_phone' => $a->candidate_phone,
                    'status' => $a->status,
                    'status_label' => $a->status_label,
                    'rating' => $a->rating,
                    'interview_date' => $a->interview_date?->format('d/m/Y H:i'),
                    'notes' => $a->notes,
                    'applied_at' => $a->created_at->format('d/m/Y'),
                ];
            });

        // Stats pipeline
        $stats = [];
        foreach (JobApplication::PIPELINE_ORDER as $status) {
            $stats[$status] = $applications->where('status', $status)->count();
        }
        $stats['rejected'] = $applications->where('status', 'rejected')->count();

        return response()->json([
            'success' => true,
            'posting' => ['id' => $posting->id, 'title' => $posting->title],
            'applications' => $applications,
            'pipeline_stats' => $stats,
            'total' => $applications->count(),
        ]);
    }
}
