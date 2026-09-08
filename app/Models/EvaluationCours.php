<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avis d'un etudiant sur un cours.
 *
 * Volontairement hors de `BelongsToCompany` : l'evaluation suit l'unite
 * d'enseignement, qui porte deja son entreprise, et l'etudiant du transport
 * n'en a aucune — l'y soumettre ferait disparaitre ses avis de toute requete
 * ouverte depuis le back-office.
 */
class EvaluationCours extends Model
{
    protected $table = 'evaluations_cours';

    public const NOTE_MIN = 1;
    public const NOTE_MAX = 5;

    protected $fillable = [
        'user_id',
        'unite_enseignement_id',
        'note',
        'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'note' => 'integer',
        ];
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }

    public function uniteEnseignement(): BelongsTo
    {
        return $this->belongsTo(UniteEnseignement::class, 'unite_enseignement_id');
    }
}
