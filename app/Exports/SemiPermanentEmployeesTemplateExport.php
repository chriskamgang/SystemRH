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

class SemiPermanentEmployeesTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    /**
     * Données d'exemple pour les employés SEMI-PERMANENTS
     */
    public function array(): array
    {
        return [
            [
                'Sophie',
                'Leroy',
                'sophie.leroy@university.ga',
                '06 00 00 00 04',
                'password123',
                '150000',
                '20',
                'lundi,mercredi,vendredi',
                'Campus Sciences',
                'Oui',
            ],
            [
                'Thomas',
                'Dubois',
                'thomas.dubois@university.ga',
                '06 00 00 00 05',
                'password123',
                '180000',
                '25',
                'mardi,jeudi',
                'Campus Lettres, Campus Droit',
                'Oui',
            ],
            [
                'Julie',
                'Moreau',
                'julie.moreau@university.ga',
                '06 00 00 00 06',
                'password123',
                '160000',
                '22',
                'lundi,mercredi',
                'Campus Médecine',
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
            'volume_horaire_hebdomadaire',
            'jours_travail',
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
                    'startColor' => ['rgb' => '10B981'], // Green
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
            'G' => 28, // volume_horaire_hebdomadaire
            'H' => 35, // jours_travail
            'I' => 35, // campus
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
                    "Salaire mensuel fixe en FCFA (ex: 150000)"
                );

                $sheet->getComment('G1')->getText()->createTextRun(
                    "Volume horaire hebdomadaire (ex: 20 pour 20h/semaine)"
                );

                $sheet->getComment('H1')->getText()->createTextRun(
                    "Jours de travail séparés par des virgules (ex: lundi,mercredi,vendredi)"
                );

                $sheet->getComment('I1')->getText()->createTextRun(
                    "Nom des campus séparés par des virgules (ex: Campus Sciences, Campus Lettres)"
                );

                $sheet->getComment('J1')->getText()->createTextRun(
                    "Compte actif? Valeurs: Oui ou Non"
                );

                // Geler la première ligne
                $sheet->freezePane('A2');
            },
        ];
    }
}
