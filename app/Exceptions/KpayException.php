<?php

namespace App\Exceptions;

use Exception;

/** Erreur remontee par l'API KPay ou par sa configuration. */
class KpayException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $statutHttp = null,
        public readonly array $reponse = [],
    ) {
        parent::__construct($message, $statutHttp ?? 0);
    }

    /** Le solde du wallet ne couvre pas le retrait demande (422). */
    public function estSoldeInsuffisant(): bool
    {
        return $this->statutHttp === 422;
    }

    /** L'externalId a deja ete utilise (409) : la transaction existe deja. */
    public function estConflitIdempotence(): bool
    {
        return $this->statutHttp === 409;
    }
}
