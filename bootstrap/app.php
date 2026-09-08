<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // Canaux de diffusion du suivi temps reel INSAM BUS.
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // API INSAM BUS, isolee sous son propre prefixe : l'espace
            // transport et celui d'Estuaire RH ne partagent aucune route.
            Route::middleware('api')
                ->prefix('api/bus')
                ->group(base_path('routes/bus.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            // INSAM BUS : filtre les routes mobiles par role de transport
            // (etudiant, chauffeur), sans rapport avec la hierarchie RH.
            'role' => \App\Http\Middleware\AssurerRole::class,
            // Cloisonnement des deux back-offices : l'administrateur du
            // transport ne franchit pas la porte d'Estuaire RH.
            'espace' => \App\Http\Middleware\EspaceAutorise::class,
        ]);

        // Multi-tenant : injecter le company_id dans chaque requete
        $middleware->appendToGroup('web', \App\Http\Middleware\SetCurrentCompany::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\SetCurrentCompanyApi::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
