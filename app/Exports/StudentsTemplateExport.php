<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'prenom' => 'Jean',
                'nom' => 'Dupont',
                'email' => 'jean.dupont@exemple.com',
                'matricule' => 'STUD001',
                'telephone' => '0102030405',
                'specialite' => 'Informatique',
                'niveau' => 'Licence 3',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'prenom',
            'nom',
            'email',
            'matricule',
            'telephone',
            'specialite',
            'niveau',
        ];
    }
}
