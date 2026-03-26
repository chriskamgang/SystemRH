<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CredentialsPdfController extends Controller
{
    // 3 catégories pour le PDF
    private function getCategories(): array
    {
        return [
            'vacataires' => [
                'label' => 'Enseignants Vacataires',
                'types' => ['enseignant_vacataire'],
            ],
            'semi_permanents' => [
                'label' => 'Semi-Permanents',
                'types' => ['semi_permanent'],
            ],
            'permanents' => [
                'label' => 'Personnel Permanent',
                'types' => ['enseignant_titulaire', 'administratif', 'technique', 'direction'],
            ],
        ];
    }

    /**
     * Page de téléchargement des identifiants
     */
    public function index()
    {
        $categories = $this->getCategories();

        $stats = [];
        foreach ($categories as $key => $cat) {
            $stats[$key] = [
                'label' => $cat['label'],
                'count' => User::whereIn('employee_type', $cat['types'])
                    ->where('is_active', true)
                    ->where('role_id', '!=', 1)
                    ->count(),
            ];
        }

        return view('admin.credentials.index', compact('categories', 'stats'));
    }

    /**
     * Télécharger le PDF des identifiants
     */
    public function download(Request $request)
    {
        $selectedCategories = $request->input('categories', array_keys($this->getCategories()));
        $categories = $this->getCategories();

        // Collecter les types sélectionnés
        $selectedTypes = [];
        foreach ($selectedCategories as $catKey) {
            if (isset($categories[$catKey])) {
                $selectedTypes = array_merge($selectedTypes, $categories[$catKey]['types']);
            }
        }

        $employees = User::whereIn('employee_type', $selectedTypes)
            ->where('is_active', true)
            ->where('role_id', '!=', 1)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Grouper par catégorie
        $grouped = collect();
        foreach ($selectedCategories as $catKey) {
            if (isset($categories[$catKey])) {
                $catEmployees = $employees->whereIn('employee_type', $categories[$catKey]['types']);
                if ($catEmployees->count() > 0) {
                    $grouped[$catKey] = [
                        'label' => $categories[$catKey]['label'],
                        'employees' => $catEmployees->values(),
                    ];
                }
            }
        }

        $pdf = Pdf::loadView('admin.credentials.pdf', compact('grouped'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('identifiants-personnel-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Réinitialiser tous les mots de passe à password123
     */
    public function resetPasswords(Request $request)
    {
        $selectedCategories = $request->input('categories', []);
        $categories = $this->getCategories();

        if (empty($selectedCategories)) {
            return back()->with('error', 'Sélectionnez au moins une catégorie.');
        }

        $selectedTypes = [];
        foreach ($selectedCategories as $catKey) {
            if (isset($categories[$catKey])) {
                $selectedTypes = array_merge($selectedTypes, $categories[$catKey]['types']);
            }
        }

        $count = User::whereIn('employee_type', $selectedTypes)
            ->where('is_active', true)
            ->where('role_id', '!=', 1)
            ->update(['password' => Hash::make('password123')]);

        return back()->with('success', "{$count} mot(s) de passe réinitialisé(s) à password123.");
    }
}
