<?php

namespace App\Enums;

enum StatutBus: string
{
    case Disponible = 'disponible';
    case EnService = 'en_service';
    case EnPanne = 'en_panne';
    case Maintenance = 'maintenance';

    public function libelle(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::EnService => 'En service',
            self::EnPanne => 'En panne',
            self::Maintenance => 'Maintenance',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::Disponible => 'success',
            self::EnService => 'info',
            self::EnPanne => 'danger',
            self::Maintenance => 'warning',
        };
    }
}
