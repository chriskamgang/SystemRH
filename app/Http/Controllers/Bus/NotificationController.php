<?php

namespace App\Http\Controllers\Bus;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bus\NotificationResource;
use App\Models\NotificationApp;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Notifications d'information de l'utilisateur connecte (section 3.1). */
class NotificationController extends Controller
{
    public function __construct(private readonly PushService $push) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notificationsApp()
            ->latest()
            ->paginate(30);

        return response()->json($notifications);
    }

    public function nonLues(Request $request): JsonResponse
    {
        return response()->json([
            'total' => $request->user()->notificationsApp()->whereNull('lue_le')->count(),
        ]);
    }

    public function marquerLue(Request $request, NotificationApp $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['lue_le' => now()]);

        return response()->json([
            'notification' => new NotificationResource($notification->refresh()),
        ]);
    }

    public function toutMarquerLu(Request $request): JsonResponse
    {
        $request->user()->notificationsApp()->whereNull('lue_le')->update(['lue_le' => now()]);

        return response()->json(['message' => 'Toutes les notifications sont marquées comme lues.']);
    }

    /** Supprime une notification de la boite de son destinataire. */
    public function supprimer(Request $request, NotificationApp $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();

        return response()->json(['message' => 'Notification supprimée.']);
    }

    /** Vide la boite : toutes les notifications de l'utilisateur. */
    public function toutSupprimer(Request $request): JsonResponse
    {
        $supprimees = $request->user()->notificationsApp()->delete();

        return response()->json([
            'message' => 'Toutes les notifications ont été supprimées.',
            'supprimees' => $supprimees,
        ]);
    }

    /**
     * Enregistre le jeton FCM de l'appareil courant.
     *
     * Appele a chaque ouverture de session et a chaque rotation du jeton
     * par Firebase : sans cela, l'appareil cesse silencieusement de
     * recevoir les alertes.
     */
    public function enregistrerAppareil(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'token_fcm' => ['required', 'string', 'max:255'],
            'plateforme' => ['nullable', 'string', 'max:20'],
            'modele' => ['nullable', 'string', 'max:255'],
        ]);

        $this->push->enregistrerAppareil(
            $request->user()->id,
            $valide['token_fcm'],
            $valide['plateforme'] ?? null,
            $valide['modele'] ?? null,
        );

        return response()->json(['message' => 'Appareil enregistré.']);
    }

    /** Detache le jeton de l'appareil, a la deconnexion. */
    public function oublierAppareil(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'token_fcm' => ['required', 'string', 'max:255'],
        ]);

        $this->push->oublierAppareil($valide['token_fcm']);

        return response()->json(['message' => 'Appareil retiré.']);
    }
}
