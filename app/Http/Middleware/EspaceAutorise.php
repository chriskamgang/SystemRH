<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cloisonne les deux back-offices.
 *
 * L'administrateur d'Estuaire RH entre partout : le transport fait partie
 * de l'ecole. Celui du transport, lui, reste chez lui — les dossiers du
 * personnel, les salaires et les conges ne le regardent pas.
 *
 * Le renvoi vise l'espace dont l'intrus dispose plutot que la page de
 * connexion : un administrateur du transport qui suit un lien vers les RH
 * est deja connecte, le renvoyer au formulaire lui ferait croire que sa
 * session a expire.
 */
class EspaceAutorise
{
    public function handle(Request $request, Closure $next, string $espace): Response
    {
        $utilisateur = $request->user();

        if (! $utilisateur) {
            return redirect('/login');
        }

        $autorise = $espace === 'bus'
            ? $utilisateur->accedeAuBus()
            : $utilisateur->accedeAuRh();

        if (! $autorise) {
            return redirect($espace === 'bus' ? '/admin/dashboard' : '/admin/bus')
                ->with('error', "Votre compte n'a pas accès à cet espace.");
        }

        return $next($request);
    }
}
