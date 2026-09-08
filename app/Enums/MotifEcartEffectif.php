<?php

namespace App\Enums;

/**
 * Justification exigee du chauffeur quand l'effectif compte depasse le nombre
 * de tickets scannes : l'ecart doit toujours porter une explication (3.2).
 */
enum MotifEcartEffectif: string
{
    case EspecesBord = 'especes_bord';
    case Invite = 'invite';
    case ErreurComptage = 'erreur_comptage';
    case Autre = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::EspecesBord => 'Paiement en espèces à bord',
            self::Invite => 'Invité / personnel accompagnant',
            self::ErreurComptage => 'Erreur de comptage',
            self::Autre => 'Autre motif',
        };
    }

    /** Un motif libre n'a de valeur pour la supervision que commente. */
    public function exigeCommentaire(): bool
    {
        return $this === self::Autre;
    }
}
