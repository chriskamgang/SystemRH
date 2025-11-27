<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitesEnseignementTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * @return array
     */
    public function array(): array
    {
        // Retourner quelques lignes d'exemple
        return [
            [
                'MTH101',
                'Mathématiques fondamentales',
                '18',
                '2024-2025',
                '1',
                'Informatique',
                'Licence 1',
            ],
            [
                'PHY102',
                'Physique générale',
                '24',
                '2024-2025',
                '1',
                'Informatique',
                'Licence 1',
            ],
            [
                'INFO201',
                'Programmation orientée objet',
                '36',
                '2024-2025',
                '2',
                'Informatique',
                'Licence 2',
            ],
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'code_ue',
            'nom_matiere',
            'volume_horaire_total',
            'annee_academique',
            'semestre',
            'specialite',
            'niveau',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour la ligne d'en-tête
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Unités Enseignement';
    }
}
