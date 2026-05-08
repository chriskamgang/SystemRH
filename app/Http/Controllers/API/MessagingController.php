<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessagingController extends Controller
{
    /**
     * Liste des conversations de l'utilisateur
     */
    public function conversations(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with(['participants' => function ($q) {
            $q->select('users.id', 'first_name', 'last_name', 'photo', 'employee_type');
        }, 'latestMessage.sender'])
        ->get()
        ->map(function ($conv) use ($user) {
            $otherParticipants = $conv->participants->where('id', '!=', $user->id);
            $unread = $conv->unreadCountFor($user->id);

            return [
                'id' => $conv->id,
                'subject' => $conv->subject,
                'is_group' => $conv->is_group,
                'participants' => $otherParticipants->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'full_name' => $p->full_name,
                        'photo' => $p->photo,
                        'employee_type' => $p->employee_type,
                    ];
                })->values(),
                'latest_message' => $conv->latestMessage ? [
                    'body' => $conv->latestMessage->body,
                    'sender_name' => $conv->latestMessage->sender->full_name,
                    'sender_id' => $conv->latestMessage->sender_id,
                    'created_at' => $conv->latestMessage->created_at->format('d/m H:i'),
                    'timestamp' => $conv->latestMessage->created_at->toIso8601String(),
                ] : null,
                'unread_count' => $unread,
                'updated_at' => $conv->updated_at->toIso8601String(),
            ];
        })
        ->sortByDesc('updated_at')
        ->values();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Messages d'une conversation
     */
    public function messages(Request $request, $conversationId)
    {
        $user = $request->user();

        $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // Marquer comme lu
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        $messages = $conversation->messages()
            ->with(['sender:id,first_name,last_name,photo'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($user) {
                return [
                    'id' => $msg->id,
                    'body' => $msg->body,
                    'attachment' => $msg->attachment ? Storage::url($msg->attachment) : null,
                    'sender' => [
                        'id' => $msg->sender->id,
                        'full_name' => $msg->sender->full_name,
                        'photo' => $msg->sender->photo,
                    ],
                    'is_mine' => $msg->sender_id === $user->id,
                    'created_at' => $msg->created_at->format('d/m H:i'),
                    'timestamp' => $msg->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'conversation' => [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
            ],
        ]);
    }

    /**
     * Envoyer un message
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'body' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:5120',
        ]);

        $user = $request->user();

        $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('messages', 'public');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->body,
            'attachment' => $attachmentPath,
        ]);

        $conversation->touch();

        // Notifier les autres participants
        $otherParticipants = $conversation->participants->where('id', '!=', $user->id);
        foreach ($otherParticipants as $participant) {
            if ($participant->fcm_token) {
                try {
                    PushNotificationService::sendToUser(
                        $participant,
                        $user->full_name,
                        mb_substr($request->body, 0, 100),
                        ['type' => 'message', 'conversation_id' => (string) $conversation->id]
                    );
                } catch (\Exception $e) {
                    // Notification silencieuse
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'attachment' => $attachmentPath ? Storage::url($attachmentPath) : null,
                'sender' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'photo' => $user->photo,
                ],
                'is_mine' => true,
                'created_at' => $message->created_at->format('d/m H:i'),
                'timestamp' => $message->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Creer une nouvelle conversation (ou retrouver l'existante)
     */
    public function createConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
        ]);

        $user = $request->user();
        $recipientId = $request->recipient_id;

        if ($user->id == $recipientId) {
            return response()->json(['success' => false, 'message' => 'Impossible de s\'envoyer un message a soi-meme.'], 400);
        }

        // Chercher une conversation existante entre les deux
        $existing = Conversation::where('is_group', false)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('participants', function ($q) use ($recipientId) {
                $q->where('user_id', $recipientId);
            })
            ->first();

        if ($existing) {
            // Envoyer le message dans la conversation existante
            $message = Message::create([
                'conversation_id' => $existing->id,
                'sender_id' => $user->id,
                'body' => $request->message,
            ]);
            $existing->touch();

            // Push notification
            $recipient = User::find($recipientId);
            if ($recipient?->fcm_token) {
                try {
                    PushNotificationService::sendToUser(
                        $recipient,
                        $user->full_name,
                        mb_substr($request->message, 0, 100),
                        ['type' => 'message', 'conversation_id' => (string) $existing->id]
                    );
                } catch (\Exception $e) {}
            }

            return response()->json([
                'success' => true,
                'conversation_id' => $existing->id,
                'message' => 'Message envoye.',
            ], 201);
        }

        // Creer une nouvelle conversation
        $conversation = Conversation::create(['is_group' => false]);
        $conversation->participants()->attach([$user->id, $recipientId]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->message,
        ]);

        // Push notification
        $recipient = User::find($recipientId);
        if ($recipient?->fcm_token) {
            try {
                PushNotificationService::sendToUser(
                    $recipient,
                    'Nouveau message de ' . $user->full_name,
                    mb_substr($request->message, 0, 100),
                    ['type' => 'message', 'conversation_id' => (string) $conversation->id]
                );
            } catch (\Exception $e) {}
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'message' => 'Conversation creee.',
        ], 201);
    }

    /**
     * Liste des utilisateurs contactables
     */
    public function contacts(Request $request)
    {
        $user = $request->user();

        $users = User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->select('id', 'first_name', 'last_name', 'photo', 'employee_type', 'department_id')
            ->with('department:id,name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'full_name' => $u->full_name,
                    'photo' => $u->photo,
                    'employee_type' => $u->employee_type,
                    'department' => $u->department?->name,
                ];
            });

        return response()->json([
            'success' => true,
            'contacts' => $users,
        ]);
    }
}
