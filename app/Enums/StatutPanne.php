<?php

namespace App\Enums;

enum StatutPanne: string
{
    case Declaree = 'declaree';
    case PriseEnCharge = 'prise_en_charge';
    case Resolue = 'resolue';
    case Annulee = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::Declaree => 'Déclarée',
            self::PriseEnCharge => 'Prise en charge',
            self::Resolue => 'Résolue',
            self::Annulee => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::Declaree => 'danger',
            self::PriseEnCharge => 'warning',
            self::Resolue => 'success',
            self::Annulee => 'gray',
        };
    }
}
