<?php

namespace App\Enums;

enum StatutPrime: string
{
    case EnAttente = 'en_attente';
    case Validee = 'validee';
    case Payee = 'payee';
    case Annulee = 'annulee';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de validation',
            self::Validee => 'Validée',
            self::Payee => 'Payée',
            self::Annulee => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Validee => 'info',
            self::Payee => 'success',
            self::Annulee => 'danger',
        };
    }
}
