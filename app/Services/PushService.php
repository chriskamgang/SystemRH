<?php

namespace App\Services;

use App\Models\Appareil;
use App\Models\NotificationApp;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as PushNotification;
use Throwable;

/**
 * Envoi des notifications push via Firebase Cloud Messaging.
 *
 * La notification vit d'abord en base : le push n'en est que la copie
 * poussee a l'appareil. Un envoi qui echoue ne doit donc jamais faire
 * echouer l'action metier qui l'a declenche — le contenu reste lisible
 * dans l'ecran des notifications.
 */
class PushService
{
    public function __construct(private readonly Messaging $messaging) {}

    /** Pousse une notification deja enregistree vers les appareils de son destinataire. */
    public function envoyer(NotificationApp $notification): void
    {
        $appareils = Appareil::where('user_id', $notification->user_id)->get();

        if ($appareils->isEmpty()) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(PushNotification::create($notification->titre, $notification->message))
            // Les donnees FCM ne transportent que des chaines : l'identifiant
            // et le contexte metier sont donc encodes tels quels.
            ->withData([
                'notification_id' => (string) $notification->id,
                'type' => $notification->type,
                'donnees' => json_encode($notification->donnees ?? [], JSON_UNESCAPED_UNICODE),
            ])
            ->withAndroidConfig(AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'insam_bus_alertes',
                    'sound' => 'default',
                ],
            ]))
            ->withApnsConfig(ApnsConfig::fromArray([
                'payload' => ['aps' => ['sound' => 'default', 'badge' => $this->nonLues($notification->user_id)]],
            ]));

        try {
            $rapport = $this->messaging->sendMulticast($message, $appareils->pluck('token_fcm')->all());
        } catch (FirebaseException|Throwable $e) {
            Log::warning('Push FCM impossible', [
                'notification_id' => $notification->id,
                'erreur' => $e->getMessage(),
            ]);

            return;
        }

        // Un jeton devenu invalide — application desinstallee, cache vide —
        // ne sera plus jamais joignable : on le retire pour ne pas le
        // rejouer a chaque alerte.
        $perimes = array_merge($rapport->invalidTokens(), $rapport->unknownTokens());

        if ($perimes !== []) {
            Appareil::whereIn('token_fcm', $perimes)->delete();
        }
    }

    /** Enregistre ou reattribue le jeton d'un appareil. */
    public function enregistrerAppareil(int $userId, string $token, ?string $plateforme = null, ?string $modele = null): Appareil
    {
        return Appareil::updateOrCreate(
            ['token_fcm' => $token],
            [
                // Le meme appareil peut changer de compte : le jeton suit le
                // dernier utilisateur connecte, sinon l'ancien recevrait les
                // alertes du nouveau.
                'user_id' => $userId,
                'plateforme' => $plateforme,
                'modele' => $modele,
                'vu_le' => now(),
            ],
        );
    }

    /**
     * Detache un jeton, a la deconnexion de l'appareil.
     *
     * Le filtre ne porte que sur le jeton, jamais sur le compte : une
     * deconnexion hors ligne laisse le jeton attache a l'utilisateur
     * precedent, et c'est le compte suivant qui rejoue le detachement
     * depuis ce meme telephone. Filtrer sur user_id ne supprimerait rien
     * et l'ancien utilisateur continuerait d'y recevoir ses alertes.
     *
     * Un jeton FCM designe un appareil, pas un compte : le presenter
     * prouve qu'on l'a en main.
     */
    public function oublierAppareil(string $token): void
    {
        Appareil::where('token_fcm', $token)->delete();
    }

    private function nonLues(int $userId): int
    {
        return NotificationApp::where('user_id', $userId)->whereNull('lue_le')->count();
    }
}
