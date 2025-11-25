<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EmployeesTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * Retourner les en-têtes du fichier
     */
    public function headings(): array
    {
        return [
            'prenom',
            'nom',
            'email',
            'telephone',
            'mot_de_passe',
            'type_employe',
            'salaire_mensuel',
            'taux_horaire',
            'campus',
            'travail_matin',
            'travail_soir',
            'actif',
        ];
    }

    /**
     * Retourner des exemples de données
     */
    public function array(): array
    {
        return [
            [
                'Jean',
                'Dupont',
                'jean.dupont@example.com',
                '+237600000001',
                'password123',
                'Permanent',
                '500000',
                '',
                'Campus A, Campus B',
                'Oui',
                'Oui',
                'Oui',
            ],
            [
                'Marie',
                'Martin',
                'marie.martin@example.com',
                '+237600000002',
                'password123',
                'Vacataire',
                '',
                '2500',
                'Campus C',
                'Non',
                'Non',
                'Oui',
            ],
            [
                'Pierre',
                'Kouam',
                'pierre.kouam@example.com',
                '+237600000003',
                'password123',
                'Administratif',
                '300000',
                '',
                'Campus A',
                'Oui',
                'Non',
                'Oui',
            ],
        ];
    }

    /**
     * Largeur des colonnes
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // prenom
            'B' => 15,  // nom
            'C' => 30,  // email
            'D' => 15,  // telephone
            'E' => 15,  // mot_de_passe
            'F' => 20,  // type_employe
            'G' => 18,  // salaire_mensuel
            'H' => 15,  // taux_horaire
            'I' => 25,  // campus
            'J' => 15,  // travail_matin
            'K' => 15,  // travail_soir
            'L' => 10,  // actif
        ];
    }

    /**
     * Styles du fichier
     */
    public function styles(Worksheet $sheet)
    {
        // Style de l'en-tête (ligne 1)
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'], // Indigo
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Style des lignes d'exemples (lignes 2-4)
        $sheet->getStyle('A2:L4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEF2FF'], // Indigo très clair
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Hauteur de la ligne d'en-tête
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Ajouter des commentaires/notes
        $sheet->getComment('F1')->getText()->createTextRun(
            "Types valides:\n- Permanent\n- Semi-Permanent\n- Vacataire\n- Administratif\n- Technique\n- Direction"
        );

        $sheet->getComment('G1')->getText()->createTextRun(
            "Remplir UNIQUEMENT pour les permanents et semi-permanents"
        );

        $sheet->getComment('H1')->getText()->createTextRun(
            "Remplir UNIQUEMENT pour les vacataires"
        );

        $sheet->getComment('I1')->getText()->createTextRun(
            "Noms des campus séparés par des virgules\nExemple: Campus A, Campus B"
        );

        $sheet->getComment('J1')->getText()->createTextRun(
            "Travaille le MATIN ?\nUniquement pour les Permanents\nValeurs: Oui ou Non"
        );

        $sheet->getComment('K1')->getText()->createTextRun(
            "Travaille le SOIR ?\nUniquement pour les Permanents\nValeurs: Oui ou Non"
        );

        $sheet->getComment('L1')->getText()->createTextRun(
            "Compte actif ?\nValeurs: Oui ou Non"
        );

        return [];
    }
}
