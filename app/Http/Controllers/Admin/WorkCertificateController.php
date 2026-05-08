<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkCertificate;
use App\Services\PushNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\PayrollCalculator;
use Illuminate\Http\Request;

class WorkCertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkCertificate::with(['user', 'generator'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $pendingCount = WorkCertificate::where('status', 'pending')->count();
        $requests = $query->paginate(20);

        return view('admin.certificates.index', compact('requests', 'pendingCount'));
    }

    public function generate($id)
    {
        $cert = WorkCertificate::findOrFail($id);
        $user = $cert->user;
        $user->load(['department', 'role', 'campuses', 'jobPosition']);

        $years = $user->created_at->diffInYears(now());
        $months = $user->created_at->diffInMonths(now()) % 12;
        $typeLabels = WorkCertificate::TYPE_LABELS;

        $extraData = [];
        if ($cert->type === 'salary') {
            try {
                $payroll = PayrollCalculator::calculatePayroll($user, now()->year, now()->month);
                $extraData['monthly_salary'] = $payroll['net_salary'] ?? $user->monthly_salary ?? 0;
            } catch (\Exception $e) {
                $extraData['monthly_salary'] = $user->monthly_salary ?? 0;
            }
        }

        // Generer le PDF
        $pdf = Pdf::loadView('admin.rapports.pdf.attestation-travail', compact(
            'cert', 'user', 'years', 'months', 'typeLabels', 'extraData'
        ));

        $filename = 'attestation-' . $cert->type . '-' . $user->employee_id . '-' . now()->format('Ymd') . '.pdf';
        $path = 'certificates/' . $filename;

        \Storage::disk('public')->put($path, $pdf->output());

        $cert->update([
            'status' => 'generated',
            'file_path' => $path,
            'generated_by' => auth()->id(),
            'generated_at' => now(),
        ]);

        // Notifier l'employe
        if ($user->fcm_token) {
            try {
                PushNotificationService::sendToUser(
                    $user,
                    'Attestation prete',
                    'Votre ' . ($typeLabels[$cert->type] ?? 'attestation') . ' est disponible au telechargement.',
                    ['type' => 'certificate', 'id' => (string) $cert->id]
                );
            } catch (\Exception $e) {}
        }

        return redirect()->route('admin.certificates.index')
            ->with('success', 'Attestation generee et disponible pour ' . $user->full_name);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['comment' => 'required|string']);

        $cert = WorkCertificate::findOrFail($id);
        $cert->update(['status' => 'rejected']);

        $user = $cert->user;
        if ($user->fcm_token) {
            try {
                PushNotificationService::sendToUser(
                    $user,
                    'Demande d\'attestation rejetee',
                    $request->comment,
                    ['type' => 'certificate_rejected']
                );
            } catch (\Exception $e) {}
        }

        return redirect()->route('admin.certificates.index')
            ->with('success', 'Demande rejetee.');
    }
}
