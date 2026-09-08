<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Code a usage unique envoye par email lors d'une connexion etudiante.
 *
 * Le code n'est jamais stocke en clair : seule son empreinte est conservee,
 * de la meme maniere qu'un mot de passe.
 */
class CodeOtp extends Model
{
    protected $table = 'codes_otp';

    protected $fillable = ['email', 'code_hash', 'expire_a', 'consomme_a', 'tentatives', 'ip'];

    protected function casts(): array
    {
        return [
            'expire_a' => 'datetime',
            'consomme_a' => 'datetime',
        ];
    }

    public function estExpire(): bool
    {
        return $this->expire_a->isPast();
    }

    public function estConsomme(): bool
    {
        return $this->consomme_a !== null;
    }

    /** Un code encore utilisable : ni expire, ni deja echange contre un token. */
    public function estUtilisable(): bool
    {
        return ! $this->estExpire() && ! $this->estConsomme();
    }
}
