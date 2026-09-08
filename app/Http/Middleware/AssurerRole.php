<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restreint une route a un ou plusieurs roles metier. */
class AssurerRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // `role_bus` porte le role de transport. Cote Estuaire RH, `role`
        // designe une relation vers la table des roles : la lire ici
        // renverrait un modele, jamais une valeur comparable.
        if (! $user || ! in_array($user->role_bus?->value, $roles, true)) {
            return response()->json([
                'message' => 'Vous n’avez pas les droits nécessaires pour cette action.',
            ], 403);
        }

        return $next($request);
    }
}
