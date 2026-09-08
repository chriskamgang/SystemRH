<?php

namespace App\Enums;

/** Nature d'un lieu desservi par le réseau. */
enum TypeLieu: string
{
    /** Point de ramassage urbain : l'étudiant y monte le matin. */
    case Ramassage = 'ramassage';

    /** Campus INSAM : destination du matin, départ du soir. */
    case Campus = 'campus';

    public function libelle(): string
    {
        return match ($this) {
            self::Ramassage => 'Point de ramassage',
            self::Campus => 'Campus',
        };
    }
}
