<?php

namespace App\Enums;

enum StatutMissionSecours: string
{
    case Affectee = 'affectee';
    case Acceptee = 'acceptee';
    case EnRoute = 'en_route';
    case Terminee = 'terminee';
    case Annulee = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::Affectee => 'Affectée',
            self::Acceptee => 'Acceptée',
            self::EnRoute => 'En route',
            self::Terminee => 'Terminée',
            self::Annulee => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::Affectee => 'warning',
            self::Acceptee, self::EnRoute => 'info',
            self::Terminee => 'success',
            self::Annulee => 'gray',
        };
    }
}
