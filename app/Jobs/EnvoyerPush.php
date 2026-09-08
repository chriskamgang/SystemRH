<?php

namespace App\Jobs;

use App\Models\NotificationApp;
use App\Services\PushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Pousse une notification vers FCM en dehors du cycle de la requete.
 *
 * Un tour dessert des dizaines d'etudiants : l'aller-retour Firebase ne
 * doit pas retenir le chauffeur qui vient de pointer son depart.
 */
class EnvoyerPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $notificationId) {}

    public function handle(PushService $push): void
    {
        $notification = NotificationApp::find($this->notificationId);

        if ($notification === null) {
            // Notification supprimee par son destinataire avant l'envoi :
            // il n'y a plus rien a pousser.
            return;
        }

        $push->envoyer($notification);
    }
}
