<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\KioskDevice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    /**
     * Page de gestion des bornes et badges
     */
    public function index()
    {
        $kiosks = KioskDevice::with('campus')->orderBy('name')->get();
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        return view('admin.kiosk.index', compact('kiosks', 'campuses'));
    }

    /**
     * Creer une nouvelle borne
     */
    public function storeDevice(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'campus_id' => 'required|exists:campuses,id',
        ]);

        $kiosk = KioskDevice::create([
            'name' => $request->name,
            'campus_id' => $request->campus_id,
            'device_token' => KioskDevice::generateToken(),
            'is_active' => true,
        ]);

        return redirect()->route('admin.kiosk.index')
            ->with('success', "Borne \"{$kiosk->name}\" creee. Token : {$kiosk->device_token}");
    }

    /**
     * Activer/desactiver une borne
     */
    public function toggleDevice($id)
    {
        $kiosk = KioskDevice::findOrFail($id);
        $kiosk->update(['is_active' => !$kiosk->is_active]);

        $status = $kiosk->is_active ? 'activee' : 'desactivee';
        return redirect()->route('admin.kiosk.index')
            ->with('success', "Borne \"{$kiosk->name}\" {$status}.");
    }

    /**
     * Supprimer une borne
     */
    public function destroyDevice($id)
    {
        $kiosk = KioskDevice::findOrFail($id);
        $kiosk->delete();

        return redirect()->route('admin.kiosk.index')
            ->with('success', "Borne supprimee.");
    }

    /**
     * Regenerer le token d'une borne
     */
    public function regenerateDeviceToken($id)
    {
        $kiosk = KioskDevice::findOrFail($id);
        $kiosk->update(['device_token' => KioskDevice::generateToken()]);

        return redirect()->route('admin.kiosk.index')
            ->with('success', "Nouveau token genere pour \"{$kiosk->name}\" : {$kiosk->device_token}");
    }

    /**
     * Page de gestion des badges QR
     */
    public function badges(Request $request)
    {
        $query = User::where('is_active', true)->orderBy('last_name');

        if ($request->campus_id) {
            $query->whereHas('campuses', fn($q) => $q->where('campuses.id', $request->campus_id));
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(20);
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        return view('admin.kiosk.badges', compact('employees', 'campuses'));
    }

    /**
     * Generer le QR token pour un employe
     */
    public function generateToken($userId)
    {
        $user = User::findOrFail($userId);
        $user->generateQrToken();

        return redirect()->back()->with('success', "Badge QR genere pour {$user->first_name} {$user->last_name}.");
    }

    /**
     * Generer les QR tokens pour plusieurs employes
     */
    public function generateTokensBulk(Request $request)
    {
        $request->validate(['user_ids' => 'required|array', 'user_ids.*' => 'exists:users,id']);

        $count = 0;
        foreach ($request->user_ids as $userId) {
            $user = User::find($userId);
            if ($user && !$user->qr_token) {
                $user->generateQrToken();
                $count++;
            }
        }

        return redirect()->back()->with('success', "{$count} badge(s) QR genere(s).");
    }

    /**
     * Revoquer le badge d'un employe
     */
    public function revokeToken($userId)
    {
        $user = User::findOrFail($userId);
        $user->revokeQrToken();

        return redirect()->back()->with('success', "Badge revoque pour {$user->first_name} {$user->last_name}.");
    }

    /**
     * Telecharger le badge PDF d'un employe
     */
    public function downloadBadge($userId)
    {
        $user = User::with(['department', 'company'])->findOrFail($userId);

        if (!$user->qr_token) {
            $user->generateQrToken();
        }

        $qrBase64 = $this->generateQrBase64($user->qr_token);
        $logoBase64 = $this->getCompanyLogoBase64($user->company);

        $pdf = Pdf::loadView('admin.kiosk.badge-pdf', [
            'user' => $user,
            'qrBase64' => $qrBase64,
            'logoBase64' => $logoBase64,
            'companyName' => $user->company->name ?? 'IUEs/INSAM',
        ])->setPaper([0, 0, 255, 160], 'landscape');

        return $pdf->download("badge-{$user->employee_id}.pdf");
    }

    /**
     * Telecharger les badges PDF de plusieurs employes
     */
    public function downloadBadgesBulk(Request $request)
    {
        $request->validate(['user_ids' => 'required|array', 'user_ids.*' => 'exists:users,id']);

        $users = User::with(['department', 'company'])->whereIn('id', $request->user_ids)->get();

        $badgesData = [];
        foreach ($users as $user) {
            if (!$user->qr_token) {
                $user->generateQrToken();
            }
            $badgesData[] = [
                'user' => $user,
                'qrBase64' => $this->generateQrBase64($user->qr_token),
                'logoBase64' => $this->getCompanyLogoBase64($user->company),
                'companyName' => $user->company->name ?? 'IUEs/INSAM',
            ];
        }

        $pdf = Pdf::loadView('admin.kiosk.badges-pdf', ['badges' => $badgesData])
            ->setPaper('a4', 'portrait');

        return $pdf->download('badges-employes.pdf');
    }

    /**
     * Generer un QR code en PNG base64 (compatible DomPDF)
     */
    private function generateQrBase64(string $data): string
    {
        $qrCode = \BaconQrCode\Encoder\Encoder::encode($data, \BaconQrCode\Common\ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $size = $matrix->getWidth();
        $cellSize = (int) (280 / $size);
        $offset = (int) ((300 - $cellSize * $size) / 2);

        $im = imagecreatetruecolor(300, 300);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $im,
                        $offset + $x * $cellSize,
                        $offset + $y * $cellSize,
                        $offset + ($x + 1) * $cellSize - 1,
                        $offset + ($y + 1) * $cellSize - 1,
                        $black
                    );
                }
            }
        }

        ob_start();
        imagepng($im);
        $pngData = ob_get_clean();
        imagedestroy($im);

        return base64_encode($pngData);
    }

    /**
     * Recuperer le logo de l'entreprise en base64 pour DomPDF
     */
    private function getCompanyLogoBase64($company): ?string
    {
        if (!$company || !$company->logo) {
            return null;
        }

        $path = storage_path('app/public/' . $company->logo);
        if (!file_exists($path)) {
            return null;
        }

        $mime = mime_content_type($path);
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}
