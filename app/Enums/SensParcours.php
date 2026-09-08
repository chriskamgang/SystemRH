<?php

namespace App\Enums;

/** Sens dans lequel une ligne est parcourue (CDC §3.2). */
enum SensParcours: string
{
    /** Matin : points de ramassage puis dépose au campus. */
    case Aller = 'aller';

    /** Soir : départ du campus puis dépose aux points de descente. */
    case Retour = 'retour';

    public function libelle(): string
    {
        return match ($this) {
            self::Aller => 'Aller — ramassage vers campus',
            self::Retour => 'Retour — campus vers points de descente',
        };
    }

    public function libelleCourt(): string
    {
        return match ($this) {
            self::Aller => 'Aller',
            self::Retour => 'Retour',
        };
    }
}
