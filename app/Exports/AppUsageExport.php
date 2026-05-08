<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AppUsageExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $data;
    protected string $period;
    protected array $filters;

    public function __construct(array $data, string $period, array $filters = [])
    {
        $this->data = $data;
        $this->period = $period;
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Utilisation App';
    }

    public function headings(): array
    {
        return [
            '#',
            'Matricule',
            'Nom complet',
            'Type employe',
            'Departement',
            'Campus',
            'Jours check-in',
            'Jours check-out',
            'Jours complets (in+out)',
            'Total pointages',
            'Retards',
            'Taux ponctualite',
            'Premier usage',
            'Dernier usage',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $i = 1;

        foreach ($this->data as $employee) {
            $rows[] = [
                $i++,
                $employee['employee_id'] ?? '-',
                $employee['full_name'],
                $employee['employee_type_label'],
                $employee['department'] ?? '-',
                $employee['campuses'] ?? '-',
                $employee['checkin_days'],
                $employee['checkout_days'],
                $employee['complete_days'],
                $employee['total_attendances'],
                $employee['late_count'],
                $employee['punctuality_rate'] . '%',
                $employee['first_usage'] ?? '-',
                $employee['last_usage'] ?? '-',
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // #
            'B' => 14,  // Matricule
            'C' => 28,  // Nom complet
            'D' => 20,  // Type employe
            'E' => 20,  // Departement
            'F' => 22,  // Campus
            'G' => 16,  // Jours check-in
            'H' => 16,  // Jours check-out
            'I' => 20,  // Jours complets
            'J' => 16,  // Total pointages
            'K' => 10,  // Retards
            'L' => 16,  // Taux ponctualite
            'M' => 14,  // Premier usage
            'N' => 14,  // Dernier usage
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->data) + 1;

        // En-tete
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Emerald
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);

        // Bordures sur tout le tableau
        if ($lastRow > 1) {
            $sheet->getStyle("A1:N{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);

            // Alternance de couleurs
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F0FDF4'], // Green very light
                        ],
                    ]);
                }
            }

            // Colonnes numeriques centrees
            $sheet->getStyle("A2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G2:N{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Titre du rapport
        $sheet->mergeCells('A' . ($lastRow + 2) . ':N' . ($lastRow + 2));
        $sheet->setCellValue('A' . ($lastRow + 2), 'Rapport utilisation application - Periode: ' . $this->period);
        $sheet->getStyle('A' . ($lastRow + 2))->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280'], 'size' => 9],
        ]);

        return [];
    }
}
