<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use App\Models\ManualAttendance;
use App\Observers\ManualAttendanceObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Firebase Messaging service
        $this->app->singleton('firebase.messaging', function ($app) {
            $credentialsPath = config('firebase.credentials');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                throw new \RuntimeException('Firebase credentials file not found. Please configure FIREBASE_CREDENTIALS in your .env file.');
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);

            return $factory->createMessaging();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer l'Observer pour ManualAttendance
        ManualAttendance::observe(ManualAttendanceObserver::class);
    }
}
