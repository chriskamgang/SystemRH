<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\MessagingException;

class FirebaseSettingsController extends Controller
{
    public function index()
    {
        $credentialsPath = config('firebase.credentials');
        $fileExists = $credentialsPath && file_exists($credentialsPath);

        $config = null;
        $usersWithToken = User::whereNotNull('fcm_token')->count();

        if ($fileExists) {
            $content = file_get_contents($credentialsPath);
            $config = json_decode($content, true);
        }

        return view('admin.firebase-settings.index', compact('fileExists', 'config', 'credentialsPath', 'usersWithToken'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'firebase_credentials' => 'required|file|mimes:json|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('firebase_credentials');

            // Vérifier que c'est un JSON valide
            $content = file_get_contents($file->getRealPath());
            $json = json_decode($content, true);

            if (!$json || !isset($json['project_id']) || !isset($json['private_key'])) {
                return back()->with('error', 'Le fichier JSON n\'est pas un fichier de credentials Firebase valide.');
            }

            // Créer le répertoire storage si nécessaire
            $storagePath = storage_path('app/firebase');
            if (!File::exists($storagePath)) {
                File::makeDirectory($storagePath, 0755, true);
            }

            // Sauvegarder le fichier
            $filename = 'firebase-credentials.json';
            $fullPath = $storagePath . '/' . $filename;

            // Backup de l'ancien fichier si existe
            if (file_exists($fullPath)) {
                $backupPath = $storagePath . '/firebase-credentials-backup-' . time() . '.json';
                copy($fullPath, $backupPath);
            }

            file_put_contents($fullPath, $content);

            // Mettre à jour le .env
            try {
                $this->updateEnvFile('FIREBASE_CREDENTIALS', $fullPath);
            } catch (\Exception $e) {
                Log::warning('Could not update .env file', ['error' => $e->getMessage()]);
                // Continue anyway, manual update required
            }

            // Nettoyer le cache
            try {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
            } catch (\Exception $e) {
                Log::warning('Could not clear config cache', ['error' => $e->getMessage()]);
            }

            Log::info('Firebase credentials uploaded successfully', [
                'project_id' => $json['project_id'],
                'uploaded_by' => auth()->id(),
            ]);

            return back()->with('success', 'Fichier Firebase uploadé avec succès ! Configuration mise à jour.');

        } catch (\Exception $e) {
            Log::error('Failed to upload Firebase credentials', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
        }
    }

    public function test()
    {
        try {
            $credentialsPath = config('firebase.credentials');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                return back()->with('error', 'Aucun fichier Firebase configuré.');
            }

            // Tester en récupérant un utilisateur avec FCM token
            $user = User::whereNotNull('fcm_token')->first();

            if (!$user) {
                return back()->with('warning', 'Configuration Firebase valide, mais aucun utilisateur avec FCM token trouvé pour tester l\'envoi.');
            }

            // Créer une notification de test
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => 'Test Notification',
                'body' => 'Ceci est une notification de test envoyée depuis le dashboard admin à ' . now()->format('H:i:s'),
                'type' => 'system',
                'data' => json_encode(['test' => true, 'timestamp' => now()->toIso8601String()]),
            ]);

            // Envoyer via Firebase
            $messaging = app('firebase.messaging');
            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $user->fcm_token)
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

            Log::info('Firebase test notification sent successfully', [
                'user_id' => $user->id,
                'message_id' => $result,
            ]);

            return back()->with('success', "Notification de test envoyée avec succès à {$user->full_name} ! Vérifiez le téléphone.");

        } catch (MessagingException $e) {
            Log::error('Firebase test failed (Messaging)', [
                'error' => $e->getMessage(),
            ]);

            $errorMessage = 'Erreur Firebase : ' . $e->getMessage();

            if (str_contains($e->getMessage(), 'registration-token-not-registered')) {
                $errorMessage .= ' (Le token FCM est invalide ou expiré)';
            }

            return back()->with('error', $errorMessage);

        } catch (\Exception $e) {
            Log::error('Firebase test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erreur lors du test : ' . $e->getMessage());
        }
    }

    public function download()
    {
        $credentialsPath = config('firebase.credentials');

        if (!$credentialsPath || !file_exists($credentialsPath)) {
            return back()->with('error', 'Aucun fichier Firebase configuré.');
        }

        return response()->download($credentialsPath, 'firebase-credentials.json');
    }

    /**
     * Mettre à jour une variable dans le fichier .env
     */
    private function updateEnvFile($key, $value)
    {
        $envFile = base_path('.env');

        if (!file_exists($envFile)) {
            return;
        }

        $content = file_get_contents($envFile);
        $lines = explode("\n", $content);
        $updated = false;

        foreach ($lines as $index => $line) {
            if (strpos($line, $key . '=') === 0) {
                $lines[$index] = $key . '=' . $value;
                $updated = true;
                break;
            }
        }

        // Si la clé n'existe pas, l'ajouter
        if (!$updated) {
            $lines[] = '';
            $lines[] = '# Firebase Configuration';
            $lines[] = $key . '=' . $value;
        }

        file_put_contents($envFile, implode("\n", $lines));
    }
}
