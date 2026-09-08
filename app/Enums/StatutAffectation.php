<?php

namespace App\Enums;

enum StatutAffectation: string
{
    case Planifiee = 'planifiee';
    case Active = 'active';
    case Terminee = 'terminee';
    case Annulee = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::Planifiee => 'Planifiée',
            self::Active => 'Active',
            self::Terminee => 'Terminée',
            self::Annulee => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::Planifiee => 'gray',
            self::Active => 'info',
            self::Terminee => 'success',
            self::Annulee => 'danger',
        };
    }
}
