<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->is_super_admin) {
                // Super admin : utiliser la company selectionnee en session, ou null (voir tout)
                $companyId = session('current_company_id');
                if (!$companyId) {
                    // En mode super admin sans company selectionnee, ne pas filtrer
                    // Le scope ne s'appliquera pas car current_company_id est null
                }
            } else {
                // Utilisateur normal : toujours filtrer par sa company
                session(['current_company_id' => $user->company_id]);
            }
        }

        return $next($request);
    }
}
