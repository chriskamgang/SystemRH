<?php

namespace App\Services;

use App\Models\PublicHoliday;
use App\Models\User;
use Carbon\Carbon;

class LeaveCalculationService
{
    /**
     * Jours fériés du Cameroun (récurrents par défaut)
     */
    public const CAMEROON_HOLIDAYS = [
        ['name' => 'Jour de l\'An', 'month' => 1, 'day' => 1],
        ['name' => 'Fête de la Jeunesse', 'month' => 2, 'day' => 11],
        ['name' => 'Fête du Travail', 'month' => 5, 'day' => 1],
        ['name' => 'Fête Nationale', 'month' => 5, 'day' => 20],
        ['name' => 'Assomption', 'month' => 8, 'day' => 15],
        ['name' => 'Noël', 'month' => 12, 'day' => 25],
    ];

    /**
     * Sous-types d'événements familiaux avec jours accordés
     */
    public const FAMILY_EVENT_TYPES = [
        'marriage' => ['label' => 'Mariage du salarié', 'days' => 3],
        'birth' => ['label' => 'Naissance d\'un enfant', 'days' => 3],
        'death_spouse' => ['label' => 'Décès du conjoint', 'days' => 3],
        'death_parent' => ['label' => 'Décès d\'un parent', 'days' => 3],
        'death_child' => ['label' => 'Décès d\'un enfant', 'days' => 3],
        'child_marriage' => ['label' => 'Mariage d\'un enfant', 'days' => 2],
    ];

    /**
     * Calculer le quota annuel de congé pour un employé
     * Basé sur le droit du travail camerounais
     */
    public static function calculateAnnualQuota(User $user): int
    {
        // Base : 1.5 jour/mois = 18 jours/an
        $base = 18;

        // Majoration ancienneté : +2 jours par tranche de 5 ans
        $seniority = self::getSeniorityYears($user);
        $seniorityBonus = floor($seniority / 5) * 2;

        // Majoration mère salariée : +2 jours par enfant < 6 ans
        $motherBonus = 0;
        if ($user->sexe === 'F' && $user->nombre_enfants_charge > 0) {
            $motherBonus = $user->nombre_enfants_charge * 2;
        }

        return (int) ($base + $seniorityBonus + $motherBonus);
    }

    /**
     * Calculer l'ancienneté en années
     */
    public static function getSeniorityYears(User $user): int
    {
        if (!$user->date_embauche) {
            return 0;
        }

        return Carbon::parse($user->date_embauche)->diffInYears(now());
    }

    /**
     * Calculer le nombre de jours ouvrables entre deux dates
     * Exclut dimanches et jours fériés
     * Samedi = 0.5 jour (demi-journée)
     */
    public static function calculateWorkingDays(Carbon $startDate, Carbon $endDate, ?int $year = null): float
    {
        $holidays = PublicHoliday::getForYear($year ?? $startDate->year);
        // Si la période chevauche deux années, ajouter les fériés de l'autre année
        if ($endDate->year !== $startDate->year) {
            $holidays = array_merge($holidays, PublicHoliday::getForYear($endDate->year));
        }

        $days = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dateStr = $current->format('Y-m-d');
            $isHoliday = in_array($dateStr, $holidays);

            if (!$isHoliday) {
                if ($current->isWeekday()) {
                    $days++;
                } elseif ($current->isSaturday()) {
                    $days += 0.5;
                }
                // Dimanche = 0
            }

            $current->addDay();
        }

        return $days;
    }

    /**
     * Vérifier si le quota d'événements familiaux est dépassé (max 10j/an)
     */
    public static function getRemainingFamilyEventDays(int $userId, int $year): int
    {
        $used = \App\Models\LeaveRequest::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('type', 'family_event')
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', $year)
            ->sum('days_count');

        return max(0, 10 - $used);
    }

    /**
     * Obtenir le détail du calcul du quota pour un employé
     */
    public static function getQuotaBreakdown(User $user): array
    {
        $seniority = self::getSeniorityYears($user);
        $seniorityBonus = (int) (floor($seniority / 5) * 2);

        $motherBonus = 0;
        if ($user->sexe === 'F' && $user->nombre_enfants_charge > 0) {
            $motherBonus = $user->nombre_enfants_charge * 2;
        }

        return [
            'base' => 18,
            'anciennete_annees' => $seniority,
            'bonus_anciennete' => $seniorityBonus,
            'nombre_enfants_charge' => $user->nombre_enfants_charge,
            'bonus_mere' => $motherBonus,
            'total' => 18 + $seniorityBonus + $motherBonus,
        ];
    }
}
