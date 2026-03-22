<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
                return redirect()->route('admin.firebase.index')->with('error', 'Le fichier JSON n\'est pas un fichier de credentials Firebase valide.');
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
            $envUpdated = false;
            try {
                $this->updateEnvFile('FIREBASE_CREDENTIALS', $fullPath);
                $envUpdated = true;
            } catch (\Exception $e) {
                Log::warning('Could not update .env file', ['error' => $e->getMessage()]);
            }

            Log::info('Firebase credentials uploaded successfully', [
                'project_id' => $json['project_id'],
                'uploaded_by' => auth()->id(),
            ]);

            $message = 'Fichier Firebase uploadé avec succès ! ';
            if ($envUpdated) {
                $message .= 'Le fichier .env a été mis à jour automatiquement. Veuillez redémarrer le serveur Laravel pour appliquer les changements.';
            } else {
                $message .= 'Veuillez ajouter manuellement cette ligne dans votre .env : FIREBASE_CREDENTIALS=' . $fullPath;
            }

            return redirect()->route('admin.firebase.index')->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to upload Firebase credentials', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.firebase.index')->with('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
        }
    }

    public function test()
    {
        try {
            $credentialsPath = config('firebase.credentials');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                return redirect()->route('admin.firebase.index')->with('error', 'Aucun fichier Firebase configuré.');
            }

            // Récupérer tous les utilisateurs avec un FCM token
            $users = User::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();

            if ($users->isEmpty()) {
                return redirect()->route('admin.firebase.index')->with('warning', 'Configuration Firebase valide, mais aucun utilisateur avec FCM token trouvé pour tester l\'envoi.');
            }

            // Envoyer à tous les utilisateurs via PushNotificationService
            $pushService = new PushNotificationService();

            if (!$pushService->isConfigured()) {
                return redirect()->route('admin.firebase.index')->with('error', 'Le service Firebase n\'est pas correctement configuré.');
            }

            $title = 'Test Notification';
            $body = 'Ceci est une notification de test envoyée depuis le dashboard admin à ' . now()->format('H:i:s');
            $data = ['type' => 'test', 'test' => 'true'];

            $successCount = $pushService->sendToMultipleUsers($users, $title, $body, $data, 'system');

            Log::info('Firebase test notifications sent', [
                'total_users' => $users->count(),
                'success' => $successCount,
                'failed' => $users->count() - $successCount,
            ]);

            $message = "Notification de test envoyée à {$successCount}/{$users->count()} utilisateur(s) : ";
            $names = $users->pluck('full_name')->implode(', ');
            $message .= $names;

            if ($successCount === $users->count()) {
                return redirect()->route('admin.firebase.index')->with('success', $message);
            } elseif ($successCount > 0) {
                return redirect()->route('admin.firebase.index')->with('warning', $message);
            } else {
                return redirect()->route('admin.firebase.index')->with('error', 'Échec de l\'envoi à tous les utilisateurs.');
            }

        } catch (\Exception $e) {
            Log::error('Firebase test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.firebase.index')->with('error', 'Erreur lors du test : ' . $e->getMessage());
        }
    }

    public function download()
    {
        $credentialsPath = config('firebase.credentials');

        if (!$credentialsPath || !file_exists($credentialsPath)) {
            return redirect()->route('admin.firebase.index')->with('error', 'Aucun fichier Firebase configuré.');
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
