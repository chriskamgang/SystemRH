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

class PermanentEmployeesTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    /**
     * Données d'exemple pour les employés PERMANENTS
     */
    public function array(): array
    {
        return [
            [
                'Jean',
                'Dupont',
                'jean.dupont@university.ga',
                '06 00 00 00 01',
                'password123',
                '250000',
                'Campus Sciences, Campus Lettres',
                'Oui',
                'Non',
                'Oui',
            ],
            [
                'Marie',
                'Martin',
                'marie.martin@university.ga',
                '06 00 00 00 02',
                'password123',
                '280000',
                'Campus Droit',
                'Non',
                'Oui',
                'Oui',
            ],
            [
                'Pierre',
                'Bernard',
                'pierre.bernard@university.ga',
                '06 00 00 00 03',
                'password123',
                '300000',
                'Campus Médecine',
                'Oui',
                'Oui',
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
            'salaire_mensuel',
            'campus',
            'travail_matin',
            'travail_soir',
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
                    'startColor' => ['rgb' => '4F46E5'], // Indigo
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
            'F' => 18, // salaire_mensuel
            'G' => 35, // campus
            'H' => 15, // travail_matin
            'I' => 15, // travail_soir
            'J' => 10, // actif
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

                // Commentaire pour prenom
                $sheet->getComment('A1')->getText()->createTextRun(
                    "Prénom de l'employé"
                );

                // Commentaire pour nom
                $sheet->getComment('B1')->getText()->createTextRun(
                    "Nom de famille de l'employé"
                );

                // Commentaire pour email
                $sheet->getComment('C1')->getText()->createTextRun(
                    "Email unique (sera utilisé pour la connexion)"
                );

                // Commentaire pour telephone
                $sheet->getComment('D1')->getText()->createTextRun(
                    "Numéro de téléphone (optionnel)"
                );

                // Commentaire pour mot_de_passe
                $sheet->getComment('E1')->getText()->createTextRun(
                    "Mot de passe initial (minimum 6 caractères)"
                );

                // Commentaire pour salaire_mensuel
                $sheet->getComment('F1')->getText()->createTextRun(
                    "Salaire mensuel en FCFA (ex: 250000)"
                );

                // Commentaire pour campus
                $sheet->getComment('G1')->getText()->createTextRun(
                    "Nom des campus séparés par des virgules (ex: Campus Sciences, Campus Lettres)"
                );

                // Commentaire pour travail_matin
                $sheet->getComment('H1')->getText()->createTextRun(
                    "Travaille le matin (08h-13h)? Valeurs: Oui ou Non"
                );

                // Commentaire pour travail_soir
                $sheet->getComment('I1')->getText()->createTextRun(
                    "Travaille le soir (14h-19h)? Valeurs: Oui ou Non"
                );

                // Commentaire pour actif
                $sheet->getComment('J1')->getText()->createTextRun(
                    "Compte actif? Valeurs: Oui ou Non"
                );

                // Geler la première ligne
                $sheet->freezePane('A2');
            },
        ];
    }
}
