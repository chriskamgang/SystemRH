<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class VacataireEmployeesTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    /**
     * Données d'exemple pour les employés VACATAIRES
     */
    public function array(): array
    {
        return [
            [
                'Paul',
                'Petit',
                'paul.petit@university.ga',
                '06 00 00 00 07',
                'password123',
                '5000',
                'Campus Sciences, Campus Lettres',
                'Oui',
            ],
            [
                'Emma',
                'Roux',
                'emma.roux@university.ga',
                '06 00 00 00 08',
                'password123',
                '4500',
                'Campus Droit',
                'Oui',
            ],
            [
                'Lucas',
                'Simon',
                'lucas.simon@university.ga',
                '06 00 00 00 09',
                'password123',
                '6000',
                'Campus Médecine, Campus Technologie',
                'Oui',
            ],
        ];
    }

    /**
     * En-têtes du fichier
     */
    public function headings(): array
    {
        return [
            'prenom',
            'nom',
            'email',
            'telephone',
            'mot_de_passe',
            'taux_horaire',
            'campus',
            'actif',
        ];
    }

    /**
     * Styles pour les en-têtes
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F59E0B'], // Amber
                ],
            ],
        ];
    }

    /**
     * Largeurs des colonnes
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // prenom
            'B' => 15, // nom
            'C' => 30, // email
            'D' => 15, // telephone
            'E' => 15, // mot_de_passe
            'F' => 18, // taux_horaire
            'G' => 40, // campus
            'H' => 10, // actif
        ];
    }

    /**
     * Événements après la création de la feuille
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Ajouter des commentaires explicatifs
                $sheet = $event->sheet->getDelegate();

                $sheet->getComment('A1')->getText()->createTextRun(
                    "Prénom de l'employé"
                );

                $sheet->getComment('B1')->getText()->createTextRun(
                    "Nom de famille de l'employé"
                );

                $sheet->getComment('C1')->getText()->createTextRun(
                    "Email unique (sera utilisé pour la connexion)"
                );

                $sheet->getComment('D1')->getText()->createTextRun(
                    "Numéro de téléphone (optionnel)"
                );

                $sheet->getComment('E1')->getText()->createTextRun(
                    "Mot de passe initial (minimum 6 caractères)"
                );

                $sheet->getComment('F1')->getText()->createTextRun(
                    "Taux horaire en FCFA (ex: 5000 FCFA/heure)"
                );

                $sheet->getComment('G1')->getText()->createTextRun(
                    "Nom des campus séparés par des virgules (ex: Campus Sciences, Campus Lettres)"
                );

                $sheet->getComment('H1')->getText()->createTextRun(
                    "Compte actif? Valeurs: Oui ou Non"
                );

                // Geler la première ligne
                $sheet->freezePane('A2');
            },
        ];
    }
}
