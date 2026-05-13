<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnpsContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'month', 'year',
        'gross_salary', 'employee_contribution', 'employer_contribution',
        'total_contribution', 'status',
    ];

    // Taux CNPS Cameroun
    const EMPLOYEE_RATE = 0.042;  // 4.2% part salariale (vieillesse)
    const EMPLOYER_RATE_PF = 0.042;  // 4.2% prestations familiales
    const EMPLOYER_RATE_AT = 0.0175; // 1.75% accidents de travail
    const EMPLOYER_RATE_OLD_AGE = 0.042; // 4.2% vieillesse patronale
    const SALARY_CEILING = 750000; // Plafond mensuel CNPS

    public function user()
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public static function calculate($grossSalary)
    {
        $base = min($grossSalary, self::SALARY_CEILING);
        $employeeContrib = round($base * self::EMPLOYEE_RATE);
        $employerContrib = round($base * (self::EMPLOYER_RATE_PF + self::EMPLOYER_RATE_AT + self::EMPLOYER_RATE_OLD_AGE));

        return [
            'base' => $base,
            'employee_contribution' => $employeeContrib,
            'employer_contribution' => $employerContrib,
            'total_contribution' => $employeeContrib + $employerContrib,
        ];
    }
}
