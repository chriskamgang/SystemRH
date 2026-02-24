<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BrochureController extends Controller
{
    /**
     * Télécharger la brochure de présentation de l'application
     */
    public function downloadBrochure()
    {
        $data = [
            'title' => 'Brochure Application de Pointage',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('admin.brochure.template', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0);

        return $pdf->download('Brochure_Application_Pointage_Vacataires.pdf');
    }

    /**
     * Afficher la brochure dans le navigateur (preview)
     */
    public function previewBrochure()
    {
        $data = [
            'title' => 'Brochure Application de Pointage',
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('admin.brochure.template', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0);

        return $pdf->stream('Brochure_Application_Pointage_Vacataires.pdf');
    }

    /**
     * Afficher la page HTML (sans PDF)
     */
    public function showBrochure()
    {
        $data = [
            'title' => 'Brochure Application de Pointage',
            'date' => now()->format('d/m/Y'),
        ];

        return view('admin.brochure.template', $data);
    }
}
