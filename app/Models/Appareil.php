<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Appareil mobile enregistre pour recevoir les notifications push. */
class Appareil extends Model
{
    use HasFactory;

    protected $table = 'appareils';

    protected $fillable = ['user_id', 'token_fcm', 'plateforme', 'modele', 'vu_le'];

    protected function casts(): array
    {
        return [
            'vu_le' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
