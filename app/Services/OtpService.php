<?php

namespace App\Services;

use App\Exceptions\OtpInvalideException;
use App\Mail\CodeConnexionMail;
use App\Models\CodeOtp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Codes a usage unique envoyes par email (3.1).
 *
 * L'etudiant se connecte sans mot de passe : le code prouve a la fois son
 * identite et la possession de l'adresse. Les chauffeurs, crees au
 * back-office, ne passent pas par ce service.
 */
class OtpService
{
    /** Duree de validite d'un code. */
    public const DUREE_VALIDITE_MINUTES = 10;

    /** Delai minimal entre deux demandes pour une meme adresse. */
    public const DELAI_RENVOI_SECONDES = 45;

    /** Nombre d'essais avant qu'un code ne soit brule. */
    public const TENTATIVES_MAX = 5;

    /**
     * Genere un code, l'envoie par email, et renvoie la date d'expiration.
     *
     * Les codes precedents encore valides sont invalides : une adresse n'a
     * jamais deux codes utilisables en meme temps.
     */
    public function demander(string $email, ?string $ip = null): Carbon
    {
        $email = $this->normaliser($email);

        $this->verifierDelaiDeRenvoi($email);

        // Un seul code vivant par adresse : les anciens sont clos.
        CodeOtp::where('email', $email)
            ->whereNull('consomme_a')
            ->update(['consomme_a' => now()]);

        $code = $this->genererCode();
        $expireA = now()->addMinutes(self::DUREE_VALIDITE_MINUTES);

        CodeOtp::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expire_a' => $expireA,
            'ip' => $ip,
        ]);

        Mail::to($email)->send(new CodeConnexionMail($code, self::DUREE_VALIDITE_MINUTES));

        return $expireA;
    }

    /**
     * Verifie un code et le consomme.
     *
     * @throws OtpInvalideException si le code est expire, epuise ou faux.
     */
    public function verifier(string $email, string $code): void
    {
        $email = $this->normaliser($email);

        $enregistrement = CodeOtp::where('email', $email)
            ->whereNull('consomme_a')
            ->where('expire_a', '>', now())
            ->latest('id')
            ->first();

        if (! $enregistrement) {
            throw OtpInvalideException::expire();
        }

        if ($enregistrement->tentatives >= self::TENTATIVES_MAX) {
            // Le code est brule : il ne servira plus, meme si le prochain
            // essai est le bon.
            $enregistrement->update(['consomme_a' => now()]);

            throw OtpInvalideException::incorrect(0);
        }

        if (! Hash::check($code, $enregistrement->code_hash)) {
            $enregistrement->increment('tentatives');

            throw OtpInvalideException::incorrect(
                max(0, self::TENTATIVES_MAX - $enregistrement->tentatives),
            );
        }

        $enregistrement->update(['consomme_a' => now()]);
    }

    /** Empeche l'envoi en rafale de codes vers une meme adresse. */
    private function verifierDelaiDeRenvoi(string $email): void
    {
        $dernier = CodeOtp::where('email', $email)->latest('id')->first();

        if (! $dernier) {
            return;
        }

        $ecoule = $dernier->created_at->diffInSeconds(now());

        if ($ecoule < self::DELAI_RENVOI_SECONDES) {
            throw OtpInvalideException::tropDeDemandes(
                (int) ceil(self::DELAI_RENVOI_SECONDES - $ecoule),
            );
        }
    }

    /** Six chiffres, tirage cryptographique, zeros de tete conserves. */
    private function genererCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function normaliser(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
