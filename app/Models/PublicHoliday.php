<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class PublicHoliday extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'date',
        'is_recurring',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    /**
     * Récupérer tous les jours fériés pour une année donnée
     */
    public static function getForYear(int $year): array
    {
        $holidays = [];

        $all = self::all();
        foreach ($all as $holiday) {
            if ($holiday->is_recurring) {
                // Même jour/mois chaque année
                $holidays[] = sprintf('%04d-%02d-%02d', $year, $holiday->date->month, $holiday->date->day);
            } elseif ($holiday->date->year === $year) {
                $holidays[] = $holiday->date->format('Y-m-d');
            }
        }

        return $holidays;
    }
}
