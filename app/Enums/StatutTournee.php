<?php

namespace App\Enums;

enum StatutTournee: string
{
    case EnAttente = 'en_attente';
    case Embarquement = 'embarquement';
    case EnTransit = 'en_transit';
    case Termine = 'termine';
    case Annule = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Embarquement => 'Embarquement',
            self::EnTransit => 'En transit',
            self::Termine => 'Terminé',
            self::Annule => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EnAttente => 'gray',
            self::Embarquement => 'warning',
            self::EnTransit => 'info',
            self::Termine => 'success',
            self::Annule => 'danger',
        };
    }
}
