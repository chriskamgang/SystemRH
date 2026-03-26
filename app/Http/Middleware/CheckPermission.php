<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        // Les admins (role_id=1) ont toujours accès
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Vérifier la permission
        if (!$user->hasPermission($permission)) {
            abort(403, 'Vous n\'avez pas la permission d\'accéder à cette ressource.');
        }

        return $next($request);
    }
}
