<?php

namespace App\Enums;

enum RoleUtilisateur: string
{
    case Etudiant = 'etudiant';
    case Chauffeur = 'chauffeur';
    case Gestionnaire = 'gestionnaire';
    case Admin = 'admin';

    public function libelle(): string
    {
        return match ($this) {
            self::Etudiant => 'Étudiant',
            self::Chauffeur => 'Chauffeur',
            self::Gestionnaire => 'Gestionnaire de flotte',
            self::Admin => 'Administrateur',
        };
    }

    /** Les roles autorises a se connecter au back-office Filament. */
    public function accedeAuBackOffice(): bool
    {
        return in_array($this, [self::Gestionnaire, self::Admin], true);
    }
}
