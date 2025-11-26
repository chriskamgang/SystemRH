#!/usr/bin/env php
<?php

/**
 * Script de test Firebase pour la production
 * Usage: php test-firebase.php
 */

echo "╔═══════════════════════════════════════════════╗\n";
echo "║     TEST FIREBASE NOTIFICATIONS               ║\n";
echo "╚═══════════════════════════════════════════════╝\n\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "✓ Laravel chargé\n\n";

// Test 1: Vérifier la configuration
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 1: Configuration Firebase\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$credentialsPath = config('firebase.credentials');
echo "Chemin configuré: " . ($credentialsPath ?: "❌ Non configuré") . "\n";

if (!$credentialsPath) {
    echo "\n❌ ERREUR: FIREBASE_CREDENTIALS non configuré dans .env\n";
    echo "   Ajoutez: FIREBASE_CREDENTIALS=/chemin/vers/attendance-6156f-2a1a23ba78dc.json\n";
    exit(1);
}

if (!file_exists($credentialsPath)) {
    echo "\n❌ ERREUR: Fichier credentials introuvable\n";
    echo "   Chemin: $credentialsPath\n";
    echo "   Uploadez le fichier attendance-6156f-2a1a23ba78dc.json\n";
    exit(1);
}

echo "✓ Fichier credentials trouvé\n";
echo "✓ Taille: " . filesize($credentialsPath) . " octets\n";

// Vérifier le contenu JSON
$content = file_get_contents($credentialsPath);
$json = json_decode($content, true);

if (!$json) {
    echo "❌ ERREUR: Fichier JSON invalide\n";
    exit(1);
}

echo "✓ Fichier JSON valide\n";
echo "✓ Project ID: " . ($json['project_id'] ?? 'N/A') . "\n";
echo "✓ Client Email: " . ($json['client_email'] ?? 'N/A') . "\n\n";

// Test 2: Vérifier les utilisateurs avec FCM token
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 2: Utilisateurs avec FCM Token\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$usersWithToken = \App\Models\User::whereNotNull('fcm_token')->get();
echo "Nombre d'utilisateurs avec FCM token: " . $usersWithToken->count() . "\n";

if ($usersWithToken->isEmpty()) {
    echo "\n⚠️  ATTENTION: Aucun utilisateur n'a de FCM token\n";
    echo "   Les utilisateurs doivent se connecter via l'app mobile\n";
    echo "   pour recevoir un FCM token.\n\n";
    exit(0);
}

echo "\nUtilisateurs:\n";
foreach ($usersWithToken->take(5) as $user) {
    echo "  - {$user->full_name} ({$user->email})\n";
    echo "    Token: " . substr($user->fcm_token, 0, 30) . "...\n";
}

if ($usersWithToken->count() > 5) {
    echo "  ... et " . ($usersWithToken->count() - 5) . " autres\n";
}

echo "\n";

// Test 3: Envoyer une notification test
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "TEST 3: Envoi de notification test\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$testUser = $usersWithToken->first();
echo "Utilisateur cible: {$testUser->full_name} ({$testUser->email})\n";

try {
    // Créer une notification de test
    $notification = \App\Models\Notification::create([
        'user_id' => $testUser->id,
        'title' => 'Test Notification',
        'body' => 'Ceci est une notification de test envoyée depuis le serveur de production à ' . now()->format('H:i:s'),
        'type' => 'system',
        'data' => json_encode(['test' => true, 'timestamp' => now()->toIso8601String()]),
    ]);

    echo "✓ Notification créée (ID: {$notification->id})\n";

    // Envoyer via Firebase
    echo "Envoi via Firebase...\n";

    $messaging = app('firebase.messaging');
    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $testUser->fcm_token)
        ->withNotification([
            'title' => 'Test Notification',
            'body' => 'Si vous recevez ceci, Firebase fonctionne correctement !',
        ])
        ->withData([
            'notification_id' => (string) $notification->id,
            'type' => 'system',
            'test' => 'true',
        ]);

    $result = $messaging->send($message);

    echo "✓ Notification envoyée avec succès !\n";
    echo "✓ Message ID: " . $result . "\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ TOUS LES TESTS RÉUSSIS !\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "✓ Firebase est correctement configuré\n";
    echo "✓ La notification a été envoyée\n";
    echo "✓ Vérifiez votre téléphone pour confirmer la réception\n\n";

} catch (\Kreait\Firebase\Exception\MessagingException $e) {
    echo "\n❌ ERREUR Firebase Messaging:\n";
    echo "   " . $e->getMessage() . "\n\n";

    if (str_contains($e->getMessage(), 'registration-token-not-registered')) {
        echo "ℹ️  Le token FCM est invalide ou expiré.\n";
        echo "   L'utilisateur doit se reconnecter à l'application.\n";
    } elseif (str_contains($e->getMessage(), 'Requested entity was not found')) {
        echo "ℹ️  Projet Firebase introuvable. Vérifiez les credentials.\n";
    }

    exit(1);
} catch (\Exception $e) {
    echo "\n❌ ERREUR:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}

echo "Pour surveiller les logs:\n";
echo "  tail -f " . storage_path('logs/laravel.log') . "\n\n";
