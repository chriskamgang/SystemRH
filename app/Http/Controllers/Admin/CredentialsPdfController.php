<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CredentialsPdfController extends Controller
{
    /**
     * Page de téléchargement des identifiants
     */
    public function index()
    {
        $types = [
            'enseignant_vacataire' => 'Enseignants Vacataires',
            'semi_permanent' => 'Semi-Permanents',
            'enseignant_titulaire' => 'Enseignants Titulaires',
            'administratif' => 'Personnel Administratif',
            'technique' => 'Personnel Technique',
            'direction' => 'Direction',
        ];

        $stats = [];
        foreach ($types as $type => $label) {
            $stats[$type] = [
                'label' => $label,
                'count' => User::where('employee_type', $type)->where('is_active', true)->count(),
            ];
        }

        return view('admin.credentials.index', compact('types', 'stats'));
    }

    /**
     * Télécharger le PDF des identifiants
     */
    public function download(Request $request)
    {
        $selectedTypes = $request->input('types', array_keys($this->getTypes()));

        $employees = User::whereIn('employee_type', $selectedTypes)
            ->where('is_active', true)
            ->orderBy('employee_type')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $grouped = $employees->groupBy('employee_type');

        $typeLabels = $this->getTypes();

        $pdf = Pdf::loadView('admin.credentials.pdf', compact('grouped', 'typeLabels'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('identifiants-personnel-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Réinitialiser tous les mots de passe à password123
     */
    public function resetPasswords(Request $request)
    {
        $selectedTypes = $request->input('types', []);

        if (empty($selectedTypes)) {
            return back()->with('error', 'Sélectionnez au moins un type d\'employé.');
        }

        $count = User::whereIn('employee_type', $selectedTypes)
            ->where('is_active', true)
            ->update(['password' => Hash::make('password123')]);

        return back()->with('success', "{$count} mot(s) de passe réinitialisé(s) à password123.");
    }

    private function getTypes(): array
    {
        return [
            'enseignant_vacataire' => 'Enseignants Vacataires',
            'semi_permanent' => 'Semi-Permanents',
            'enseignant_titulaire' => 'Enseignants Titulaires',
            'administratif' => 'Personnel Administratif',
            'technique' => 'Personnel Technique',
            'direction' => 'Direction',
        ];
    }
}
