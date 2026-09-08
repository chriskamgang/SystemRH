<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Parametrage applicatif edite depuis le back-office plutot que dans le .env.
 * Les valeurs marquees "chiffre" sont stockees chiffrees en base.
 */
class Parametre extends Model
{
    protected $table = 'parametres';

    protected $fillable = ['cle', 'valeur', 'groupe', 'chiffre'];

    protected function casts(): array
    {
        return ['chiffre' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Le cache evite de relire la table a chaque appel API.
        static::saved(fn (self $p) => Cache::forget("parametre.{$p->cle}"));
        static::deleted(fn (self $p) => Cache::forget("parametre.{$p->cle}"));
    }

    /** Valeur en clair : dechiffre a la volee si le parametre est sensible. */
    public function getValeurClaireAttribute(): ?string
    {
        if (blank($this->valeur)) {
            return null;
        }

        if (! $this->chiffre) {
            return $this->valeur;
        }

        try {
            return Crypt::decryptString($this->valeur);
        } catch (\Throwable) {
            // Valeur illisible (cle applicative changee) : on ne fait pas echouer l'appelant.
            return null;
        }
    }

    /** Lit un parametre, avec valeur de repli. */
    public static function lire(string $cle, ?string $defaut = null): ?string
    {
        $valeur = Cache::rememberForever(
            "parametre.{$cle}",
            fn () => static::where('cle', $cle)->first()?->valeur_claire,
        );

        return filled($valeur) ? $valeur : $defaut;
    }

    /** Ecrit un parametre, en chiffrant si demande. */
    public static function ecrire(string $cle, ?string $valeur, string $groupe = 'general', bool $chiffre = false): self
    {
        return static::updateOrCreate(
            ['cle' => $cle],
            [
                'valeur' => $chiffre && filled($valeur) ? Crypt::encryptString($valeur) : $valeur,
                'groupe' => $groupe,
                'chiffre' => $chiffre,
            ],
        );
    }
}
