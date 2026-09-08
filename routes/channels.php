<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Canal prive des notifications personnelles (etudiant, chauffeur, gestionnaire).
Broadcast::channel('utilisateur.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

// Canaux de supervision, reserves au back-office.
Broadcast::channel('supervision.{sujet}', function (User $user) {
    return (bool) $user->role_bus?->accedeAuBackOffice();
});
