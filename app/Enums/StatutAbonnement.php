<?php

namespace App\Enums;

enum StatutAbonnement: string
{
    case EnAttente = 'en_attente';
    case Actif = 'actif';
    case Expire = 'expire';
    case Annule = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de paiement',
            self::Actif => 'Actif',
            self::Expire => 'Expiré',
            self::Annule => 'Annulé',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Actif => 'success',
            self::Expire => 'gray',
            self::Annule => 'danger',
        };
    }
}
