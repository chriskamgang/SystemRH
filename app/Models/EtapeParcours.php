<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Position d'un lieu dans un parcours. */
class EtapeParcours extends Model
{
    use HasFactory;

    protected $table = 'etapes_parcours';

    protected $fillable = [
        'parcours_id', 'lieu_id', 'ordre', 'est_terminus',
        'minutes_depuis_depart', 'passages_suivants',
    ];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'est_terminus' => 'boolean',
            'minutes_depuis_depart' => 'integer',
            'passages_suivants' => 'array',
        ];
    }

    /**
     * Heures de passage annoncees a l'etudiant, tous tours confondus.
     *
     * Le premier tour se deduit de l'heure de depart du parcours et du
     * decalage de l'etape ; les suivants sont notes tels quels sur la
     * feuille de service.
     *
     * @return list<string> ["06:35", "07:15"]
     */
    public function heuresPassage(): array
    {
        $depart = $this->parcours?->heure_depart;

        $premier = $depart
            ? [Carbon::parse($depart)
                ->addMinutes($this->minutes_depuis_depart ?? 0)
                ->format('H:i')]
            : [];

        return [...$premier, ...($this->passages_suivants ?? [])];
    }

    public function parcours(): BelongsTo
    {
        return $this->belongsTo(Parcours::class);
    }

    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }
}
