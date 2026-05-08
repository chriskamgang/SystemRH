<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WorkCertificate;
use App\Models\Attendance;
use App\Helpers\PayrollCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkCertificateController extends Controller
{
    /**
     * Liste des attestations de l'employe
     */
    public function index(Request $request)
    {
        $certificates = WorkCertificate::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'type' => $cert->type,
                    'type_label' => $cert->type_label,
                    'status' => $cert->status,
                    'purpose' => $cert->purpose,
                    'created_at' => $cert->created_at->format('d/m/Y H:i'),
                    'generated_at' => $cert->generated_at?->format('d/m/Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'certificates' => $certificates,
        ]);
    }

    /**
     * Demander une attestation
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:work,salary,employment',
            'purpose' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        // Verifier qu'il n'y a pas deja une demande en attente du meme type
        $pending = WorkCertificate::where('user_id', $user->id)
            ->where('type', $request->type)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez deja une demande en attente pour ce type d\'attestation.',
            ], 400);
        }

        $cert = WorkCertificate::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'purpose' => $request->purpose,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'attestation soumise avec succes.',
            'certificate' => [
                'id' => $cert->id,
                'type' => $cert->type,
                'type_label' => $cert->type_label,
                'status' => $cert->status,
                'created_at' => $cert->created_at->format('d/m/Y H:i'),
            ],
        ], 201);
    }

    /**
     * Telecharger une attestation generee
     */
    public function download($id, Request $request)
    {
        $cert = WorkCertificate::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->where('status', 'generated')
            ->firstOrFail();

        $user = $cert->user;
        $user->load(['department', 'role', 'campuses']);

        // Calculer l'anciennete
        $hireDate = $user->created_at;
        $years = $hireDate->diffInYears(now());
        $months = $hireDate->diffInMonths(now()) % 12;

        // Infos supplementaires selon le type
        $extraData = [];
        if ($cert->type === 'salary') {
            $payroll = PayrollCalculator::calculatePayroll($user, now()->year, now()->month);
            $extraData['monthly_salary'] = $payroll['net_salary'] ?? $user->monthly_salary ?? 0;
        }

        $typeLabels = WorkCertificate::TYPE_LABELS;

        $pdf = Pdf::loadView('admin.rapports.pdf.attestation-travail', compact(
            'cert', 'user', 'years', 'months', 'typeLabels', 'extraData'
        ));

        $filename = 'attestation-' . $cert->type . '-' . strtolower(str_replace(' ', '-', $user->full_name)) . '.pdf';

        return $pdf->download($filename);
    }
}
