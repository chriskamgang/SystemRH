<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Liste des tickets de l'employé connecté.
     */
    public function index(Request $request)
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->with(['publicComments.user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'category' => $ticket->category,
                    'category_label' => $ticket->getCategoryLabel(),
                    'target_service' => $ticket->target_service,
                    'service_label' => $ticket->getServiceLabel(),
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->getPriorityLabel(),
                    'status' => $ticket->status,
                    'status_label' => $ticket->getStatusLabel(),
                    'was_redirected' => $ticket->was_redirected,
                    'satisfaction_rating' => $ticket->satisfaction_rating,
                    'created_at' => $ticket->created_at->toIso8601String(),
                    'resolved_at' => $ticket->resolved_at?->toIso8601String(),
                    'closed_at' => $ticket->closed_at?->toIso8601String(),
                    'attachment_url' => $ticket->attachment_path ? asset('storage/' . $ticket->attachment_path) : null,
                    'comments' => $ticket->publicComments->map(fn($c) => [
                        'id' => $c->id,
                        'comment' => $c->comment,
                        'user_name' => $c->user->first_name . ' ' . $c->user->last_name,
                        'type' => $c->comment_type,
                        'attachment_url' => $c->attachment_path ? asset('storage/' . $c->attachment_path) : null,
                        'created_at' => $c->created_at->toIso8601String(),
                    ]),
                ];
            });

        return response()->json([
            'tickets' => $tickets,
            'services' => Ticket::getActiveServices(),
            'categories' => Ticket::getActiveCategories(),
        ]);
    }

    /**
     * Créer un nouveau ticket.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|in:' . implode(',', array_keys(Ticket::getActiveCategories())),
            'target_service' => 'required|string|in:' . implode(',', array_keys(Ticket::getActiveServices())),
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:3000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets/attachments', 'public');
        }

        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'user_id' => $request->user()->id,
            'category' => $request->category,
            'target_service' => $request->target_service,
            'subject' => $request->subject,
            'description' => $request->description,
            'status' => 'new',
            'attachment_path' => $attachmentPath,
        ]);

        return response()->json([
            'message' => 'Ticket cree avec succes.',
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'status_label' => $ticket->getStatusLabel(),
            ],
        ], 201);
    }

    /**
     * Ajouter un commentaire (employé).
     */
    public function addComment(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $ticket = Ticket::where('user_id', $request->user()->id)->findOrFail($id);

        if (in_array($ticket->status, ['closed'])) {
            return response()->json(['message' => 'Ce ticket est cloture.'], 400);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets/attachments', 'public');
        }

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'comment' => $request->comment,
            'comment_type' => 'public',
            'attachment_path' => $attachmentPath,
        ]);

        return response()->json(['message' => 'Commentaire ajoute.'], 201);
    }

    /**
     * Noter la satisfaction (après résolution).
     */
    public function rate(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $ticket = Ticket::where('user_id', $request->user()->id)->findOrFail($id);

        if (!in_array($ticket->status, ['resolved', 'closed'])) {
            return response()->json(['message' => 'Le ticket doit etre resolu pour etre note.'], 400);
        }

        $ticket->update([
            'satisfaction_rating' => $request->rating,
            'satisfaction_comment' => $request->comment,
        ]);

        return response()->json(['message' => 'Merci pour votre evaluation.']);
    }
}
