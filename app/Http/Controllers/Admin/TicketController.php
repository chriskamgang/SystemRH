<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Liste des tickets avec filtres.
     */
    public function index(Request $request)
    {
        $query = Ticket::with(['user', 'assignedToUser', 'assignedByUser'])
            ->withCount('comments')
            ->orderByRaw("CASE WHEN status = 'new' THEN 0 WHEN status = 'responded' THEN 1 WHEN status = 'in_progress' THEN 2 WHEN status = 'assigned' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('service')) {
            $query->where(function ($q) use ($request) {
                $q->where('target_service', $request->service)
                  ->orWhere('assigned_to_service', $request->service);
            });
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('employee_id', 'like', "%{$search}%");
                  });
            });
        }

        $tickets = $query->paginate(20);

        $stats = [
            'new' => Ticket::where('status', 'new')->count(),
            'assigned' => Ticket::where('status', 'assigned')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'responded' => Ticket::where('status', 'responded')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
            'total' => Ticket::count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'stats'));
    }

    /**
     * Détail d'un ticket avec tous les commentaires.
     */
    public function show($id)
    {
        $ticket = Ticket::with([
            'user',
            'assignedToUser',
            'assignedByUser',
            'comments.user',
        ])->findOrFail($id);

        return view('admin.tickets.show', compact('ticket'));
    }

    /**
     * Réceptionniste : valider et assigner le ticket au service.
     */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'assigned_to_service' => 'required|string|in:' . implode(',', array_keys(Ticket::getActiveServices())),
            'priority' => 'required|string|in:' . implode(',', array_keys(Ticket::PRIORITIES)),
            'comment' => 'nullable|string|max:1000',
        ]);

        $ticket = Ticket::findOrFail($id);

        $wasRedirected = $request->assigned_to_service !== $ticket->target_service;

        $ticket->update([
            'assigned_to_service' => $request->assigned_to_service,
            'assigned_by' => auth()->id(),
            'priority' => $request->priority,
            'status' => 'assigned',
            'assigned_at' => now(),
            'was_redirected' => $wasRedirected,
            'redirect_reason' => $wasRedirected ? $request->comment : null,
        ]);

        if ($request->filled('comment')) {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'comment' => $request->comment,
                'comment_type' => 'internal',
            ]);
        }

        // Notifier l'employé
        $this->notifyEmployee($ticket, 'Votre ticket ' . $ticket->ticket_number . ' a ete pris en charge par la reception et transmis au service ' . $ticket->getServiceLabel() . '.');

        $services = Ticket::getActiveServices();
        return back()->with('success', 'Ticket assigne au service ' . ($services[$request->assigned_to_service] ?? $request->assigned_to_service) . '.');
    }

    /**
     * Service : ajouter un commentaire interne (pas visible par l'employé).
     */
    public function addInternalComment(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
            'attachment' => 'nullable|file|max:5120',
        ]);

        $ticket = Ticket::findOrFail($id);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets/comments', 'public');
        }

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
            'attachment_path' => $attachmentPath,
            'comment_type' => 'internal',
        ]);

        if ($ticket->status === 'assigned') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Commentaire interne ajoute.');
    }

    /**
     * Service : soumettre une réponse → la réceptionniste la transmettra à l'employé.
     */
    public function submitResponse(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
            'attachment' => 'nullable|file|max:5120',
        ]);

        $ticket = Ticket::findOrFail($id);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets/comments', 'public');
        }

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
            'attachment_path' => $attachmentPath,
            'comment_type' => 'internal', // Reste interne tant que la réceptionniste n'a pas transmis
        ]);

        $ticket->update(['status' => 'responded']);

        return back()->with('success', 'Reponse soumise. En attente de transmission par la reception.');
    }

    /**
     * Réceptionniste : transmettre la réponse du service à l'employé.
     */
    public function forwardToEmployee(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $ticket = Ticket::findOrFail($id);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
            'comment_type' => 'response', // Visible par l'employé
        ]);

        // Notifier l'employé
        $this->notifyEmployee($ticket, 'Vous avez recu une reponse pour votre ticket ' . $ticket->ticket_number . '.');

        return back()->with('success', 'Reponse transmise a l\'employe.');
    }

    /**
     * Clôturer un ticket.
     */
    public function close(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        if ($request->filled('comment')) {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'comment' => $request->comment,
                'comment_type' => 'response',
            ]);
        }

        $this->notifyEmployee($ticket, 'Votre ticket ' . $ticket->ticket_number . ' a ete cloture.');

        return back()->with('success', 'Ticket cloture.');
    }

    /**
     * Marquer comme résolu.
     */
    public function resolve(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        if ($request->filled('comment')) {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'comment' => $request->comment,
                'comment_type' => 'response',
            ]);
        }

        $this->notifyEmployee($ticket, 'Votre ticket ' . $ticket->ticket_number . ' a ete marque comme resolu.');

        return back()->with('success', 'Ticket marque comme resolu.');
    }

    private function notifyEmployee(Ticket $ticket, string $message)
    {
        try {
            $user = $ticket->user;
            if (!$user->fcm_token) return;

            $pushService = new PushNotificationService();
            $pushService->sendToUser($user, 'Ticket ' . $ticket->ticket_number, $message, [
                'type' => 'ticket_update',
                'ticket_id' => (string) $ticket->id,
            ], 'ticket');
        } catch (\Exception $e) {
            \Log::warning('Erreur notification ticket: ' . $e->getMessage());
        }
    }
}
