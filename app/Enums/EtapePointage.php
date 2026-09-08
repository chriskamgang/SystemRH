<?php

namespace App\Enums;

/**
 * Les 5 etapes du cycle de pointage decrit en 3.2 du cahier des charges.
 * L'ordre de la casse fait foi : une etape n'est pointable que si la precedente l'est.
 */
enum EtapePointage: string
{
    case Demarrage = 'demarrage';
    case ArrivePoint = 'arrive_point';
    case Effectif = 'effectif';
    case Depart = 'depart';
    case Termine = 'termine';

    public function libelle(): string
    {
        return match ($this) {
            self::Demarrage => 'Démarrage service',
            self::ArrivePoint => 'Arrivé au point',
            self::Effectif => 'Saisie effectif',
            self::Depart => 'Départ vers campus',
            self::Termine => 'Arrivée campus',
        };
    }

    /** Etape qui doit avoir ete pointee juste avant celle-ci. */
    public function precedente(): ?self
    {
        return match ($this) {
            self::Demarrage => null,
            self::ArrivePoint => self::Demarrage,
            self::Effectif => self::ArrivePoint,
            self::Depart => self::Effectif,
            self::Termine => self::Depart,
        };
    }

    /** Statut de tournee resultant du pointage de cette etape. */
    public function statutResultant(): StatutTournee
    {
        return match ($this) {
            self::Demarrage => StatutTournee::EnAttente,
            self::ArrivePoint, self::Effectif => StatutTournee::Embarquement,
            self::Depart => StatutTournee::EnTransit,
            self::Termine => StatutTournee::Termine,
        };
    }

    /** Seules ces etapes exigent que le chauffeur soit physiquement dans la zone (regle 3.4). */
    public function exigeControleGeographique(): bool
    {
        return in_array($this, [self::ArrivePoint, self::Depart, self::Termine], true);
    }
}
