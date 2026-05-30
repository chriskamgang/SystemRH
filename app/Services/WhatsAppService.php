<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $baseUrl;
    private string $secret;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp.url', 'http://localhost:3001');
        $this->secret  = config('services.whatsapp.secret', '');
    }

    /**
     * Envoyer un message WhatsApp (fire-and-forget, n'échoue pas silencieusement)
     */
    public function send(string $phone, string $message): void
    {
        if (empty($phone)) {
            return;
        }

        // Normaliser le numéro : garder uniquement les chiffres
        $normalized = preg_replace('/\D/', '', $phone);

        // Ajouter l'indicatif Cameroun (237) si absent
        if (!str_starts_with($normalized, '237')) {
            $normalized = '237' . $normalized;
        }

        try {
            Http::timeout(5)
                ->withHeaders(['x-api-secret' => $this->secret])
                ->post("{$this->baseUrl}/send-message", [
                    'phone'   => $normalized,
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::warning('[WhatsApp] Envoi échoué : ' . $e->getMessage(), [
                'phone' => $normalized,
            ]);
        }
    }

    /**
     * Message de check-in
     */
    public function sendCheckIn(string $phone, string $firstName, string $campusName, string $time, bool $isLate = false, int $lateMinutes = 0): void
    {
        if ($isLate && $lateMinutes > 0) {
            $retardMsg = "\n⚠️ Retard enregistré : {$lateMinutes} min.";
        } else {
            $retardMsg = '';
        }

        $message = "Bonjour {$firstName} 👋\n\n"
            . "✅ Votre arrivée a été enregistrée.\n"
            . "🕐 Heure : {$time}\n"
            . "📍 Campus : {$campusName}"
            . $retardMsg
            . "\n\nBonne journée !";

        $this->send($phone, $message);
    }

    /**
     * Message de check-out
     */
    public function sendCheckOut(string $phone, string $firstName, string $campusName, string $time, float $hoursWorked): void
    {
        $heures  = floor($hoursWorked);
        $minutes = round(($hoursWorked - $heures) * 60);
        $duree   = $heures > 0 ? "{$heures}h{$minutes}min" : "{$minutes}min";

        $message = "Bonsoir {$firstName} 👋\n\n"
            . "✅ Votre départ a été enregistré.\n"
            . "🕐 Heure : {$time}\n"
            . "📍 Campus : {$campusName}\n"
            . "⏱️ Durée travaillée : {$duree}\n\n"
            . "Bonne soirée et à bientôt !";

        $this->send($phone, $message);
    }
}
