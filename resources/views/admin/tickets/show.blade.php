@extends('layouts.admin')

@section('title', 'Ticket ' . $ticket->ticket_number)

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.tickets.index') }}" class="text-blue-600 hover:underline text-sm"><i class="fas fa-arrow-left"></i> Retour</a>
            <h1 class="text-2xl font-bold mt-1">{{ $ticket->ticket_number }}</h1>
        </div>
        @php
            $sColors = ['new' => 'bg-red-100 text-red-700', 'assigned' => 'bg-yellow-100 text-yellow-700', 'in_progress' => 'bg-blue-100 text-blue-700', 'responded' => 'bg-purple-100 text-purple-700', 'resolved' => 'bg-green-100 text-green-700', 'closed' => 'bg-gray-100 text-gray-700'];
        @endphp
        <span class="px-4 py-2 rounded-full text-sm font-bold {{ $sColors[$ticket->status] ?? '' }}">
            {{ $ticket->getStatusLabel() }}
        </span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Ticket original --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-2">{{ $ticket->subject }}</h2>
                <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                    <span><i class="fas fa-user"></i> {{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</span>
                    <span><i class="fas fa-clock"></i> {{ $ticket->created_at->format('d/m/Y a H:i') }}</span>
                    <span><i class="fas fa-tag"></i> {{ $ticket->getCategoryLabel() }}</span>
                </div>
                <div class="prose prose-sm max-w-none text-gray-700 bg-gray-50 rounded-lg p-4">
                    {!! nl2br(e($ticket->description)) !!}
                </div>
                @if($ticket->attachment_path)
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg border">
                        <p class="text-xs text-gray-500 mb-2"><i class="fas fa-paperclip"></i> Piece jointe</p>
                        @if(Str::endsWith($ticket->attachment_path, ['.jpg', '.jpeg', '.png', '.gif', '.webp']))
                            <a href="{{ Storage::url($ticket->attachment_path) }}" target="_blank">
                                <img src="{{ Storage::url($ticket->attachment_path) }}" alt="Piece jointe" class="max-h-64 rounded-lg border shadow-sm hover:opacity-90 transition">
                            </a>
                        @else
                            <a href="{{ Storage::url($ticket->attachment_path) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 text-sm font-medium">
                                <i class="fas fa-download"></i> Telecharger le fichier
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Fil de commentaires --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-bold">Fil de discussion</h3>
                </div>
                <div class="p-6 space-y-4">
                    @forelse($ticket->comments as $comment)
                        @php
                            $isInternal = $comment->comment_type === 'internal';
                            $isResponse = $comment->comment_type === 'response';
                        @endphp
                        <div class="flex gap-3 {{ $isInternal ? 'opacity-80' : '' }}">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0
                                {{ $isResponse ? 'bg-green-500' : ($isInternal ? 'bg-gray-400' : 'bg-blue-500') }}">
                                {{ strtoupper(substr($comment->user->first_name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-sm">{{ $comment->user->first_name }} {{ $comment->user->last_name }}</span>
                                    <span class="text-xs text-gray-400">{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                                    @if($isInternal)
                                        <span class="px-2 py-0.5 bg-gray-200 text-gray-600 rounded text-xs">Interne</span>
                                    @elseif($isResponse)
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">Reponse a l'employe</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-700 bg-{{ $isResponse ? 'green' : ($isInternal ? 'gray' : 'blue') }}-50 rounded-lg p-3">
                                    {!! nl2br(e($comment->comment)) !!}
                                </div>
                                @if($comment->attachment_path)
                                    <a href="{{ Storage::url($comment->attachment_path) }}" target="_blank" class="text-blue-600 text-xs mt-1 inline-block">
                                        <i class="fas fa-paperclip"></i> Piece jointe
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 text-center py-4">Aucun commentaire pour le moment.</p>
                    @endforelse
                </div>
            </div>

            {{-- Actions selon le statut --}}
            @if($ticket->status !== 'closed')
            <div class="space-y-4">

                {{-- Réceptionniste : Assigner (si ticket nouveau) --}}
                @if($ticket->status === 'new')
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-red-600 mb-3"><i class="fas fa-hand-point-right"></i> Valider et assigner ce ticket</h3>
                    <form method="POST" action="{{ route('admin.tickets.assign', $ticket->id) }}">
                        @csrf
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service destinataire</label>
                                <select name="assigned_to_service" class="w-full px-3 py-2 border rounded-lg text-sm" required>
                                    @foreach(\App\Models\Ticket::getActiveServices() as $key => $label)
                                        <option value="{{ $key }}" {{ $ticket->target_service === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-400 mt-1">L'employe a choisi : <strong>{{ \App\Models\Ticket::getActiveServices()[$ticket->target_service] ?? $ticket->target_service }}</strong></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priorite</label>
                                <select name="priority" class="w-full px-3 py-2 border rounded-lg text-sm" required>
                                    @foreach(\App\Models\Ticket::PRIORITIES as $key => $label)
                                        <option value="{{ $key }}" {{ $key === 'medium' ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Note interne (optionnel)</label>
                            <textarea name="comment" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Raison de la redirection, instructions..."></textarea>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-check"></i> Valider et assigner
                        </button>
                    </form>
                </div>
                @endif

                {{-- Service : Répondre (réponse qui passera par la réceptionniste) --}}
                @if(in_array($ticket->status, ['assigned', 'in_progress']))
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-blue-600 mb-3"><i class="fas fa-reply"></i> Soumettre une reponse (sera transmise par la reception)</h3>
                    <form method="POST" action="{{ route('admin.tickets.submit-response', $ticket->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <textarea name="comment" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Votre reponse au ticket..." required></textarea>
                        </div>
                        <div class="flex items-center gap-4">
                            <input type="file" name="attachment" class="text-sm">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-paper-plane"></i> Soumettre la reponse
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                {{-- Réceptionniste : Transmettre la réponse à l'employé --}}
                @if($ticket->status === 'responded')
                <div class="bg-white rounded-lg shadow p-6 border-2 border-green-300">
                    <h3 class="font-bold text-green-600 mb-3"><i class="fas fa-share"></i> Transmettre la reponse a l'employe</h3>
                    <p class="text-sm text-gray-500 mb-3">Le service a repondu. Redigez le message qui sera envoye a l'employe :</p>
                    <form method="POST" action="{{ route('admin.tickets.forward', $ticket->id) }}">
                        @csrf
                        <div class="mb-4">
                            <textarea name="comment" rows="4" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Message a transmettre a l'employe..." required></textarea>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fas fa-share"></i> Envoyer a l'employe
                        </button>
                    </form>
                </div>
                @endif

                {{-- Commentaire interne (toujours disponible) --}}
                @if(in_array($ticket->status, ['assigned', 'in_progress', 'responded']))
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-gray-600 mb-3"><i class="fas fa-comment"></i> Ajouter un commentaire interne</h3>
                    <form method="POST" action="{{ route('admin.tickets.internal-comment', $ticket->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <textarea name="comment" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Note interne (non visible par l'employe)..." required></textarea>
                        </div>
                        <div class="flex items-center gap-4">
                            <input type="file" name="attachment" class="text-sm">
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
                                <i class="fas fa-plus"></i> Ajouter note interne
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                {{-- Résoudre / Clôturer --}}
                @if(in_array($ticket->status, ['assigned', 'in_progress', 'responded']))
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold mb-3">Actions finales</h3>
                    <div class="flex gap-4">
                        <form method="POST" action="{{ route('admin.tickets.resolve', $ticket->id) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="comment" value="">
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" onclick="return confirm('Marquer ce ticket comme resolu ?')">
                                <i class="fas fa-check-circle"></i> Marquer resolu
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="comment" value="">
                            <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700" onclick="return confirm('Cloturer ce ticket ?')">
                                <i class="fas fa-times-circle"></i> Cloturer
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if($ticket->status === 'resolved')
                <div class="bg-white rounded-lg shadow p-6">
                    <form method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}">
                        @csrf
                        <div class="mb-3">
                            <textarea name="comment" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Message de cloture (optionnel)..."></textarea>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                            <i class="fas fa-times-circle"></i> Cloturer definitivement
                        </button>
                    </form>
                </div>
                @endif

            </div>
            @endif
        </div>

        {{-- Sidebar info --}}
        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="font-bold mb-3 text-sm text-gray-500 uppercase">Informations</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Emetteur</dt>
                        <dd class="font-medium">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</dd>
                        <dd class="text-xs text-gray-400">{{ $ticket->user->employee_id }} · {{ $ticket->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Categorie</dt>
                        <dd>{{ $ticket->getCategoryLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Service demande par l'employe</dt>
                        <dd class="font-medium">{{ \App\Models\Ticket::getActiveServices()[$ticket->target_service] ?? $ticket->target_service }}</dd>
                    </div>
                    @if($ticket->assigned_to_service)
                    <div>
                        <dt class="text-gray-500">Service assigne</dt>
                        <dd class="font-medium">
                            {{ $ticket->getServiceLabel() }}
                            @if($ticket->was_redirected)
                                <span class="text-orange-500 text-xs"><i class="fas fa-exchange-alt"></i> Redirige</span>
                            @endif
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">Priorite</dt>
                        @php $pColors = ['low' => 'text-gray-600', 'medium' => 'text-blue-600', 'high' => 'text-orange-600', 'critical' => 'text-red-600 font-bold']; @endphp
                        <dd class="{{ $pColors[$ticket->priority] ?? '' }}">{{ $ticket->getPriorityLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cree le</dt>
                        <dd>{{ $ticket->created_at->format('d/m/Y a H:i') }}</dd>
                    </div>
                    @if($ticket->assigned_at)
                    <div>
                        <dt class="text-gray-500">Assigne le</dt>
                        <dd>{{ $ticket->assigned_at->format('d/m/Y a H:i') }}</dd>
                    </div>
                    @endif
                    @if($ticket->assignedByUser)
                    <div>
                        <dt class="text-gray-500">Assigne par</dt>
                        <dd>{{ $ticket->assignedByUser->first_name }} {{ $ticket->assignedByUser->last_name }}</dd>
                    </div>
                    @endif
                    @if($ticket->resolved_at)
                    <div>
                        <dt class="text-gray-500">Resolu le</dt>
                        <dd class="text-green-600">{{ $ticket->resolved_at->format('d/m/Y a H:i') }}</dd>
                    </div>
                    @endif
                    @if($ticket->closed_at)
                    <div>
                        <dt class="text-gray-500">Cloture le</dt>
                        <dd>{{ $ticket->closed_at->format('d/m/Y a H:i') }}</dd>
                    </div>
                    @endif
                    @if($ticket->satisfaction_rating)
                    <div>
                        <dt class="text-gray-500">Satisfaction</dt>
                        <dd>
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= $ticket->satisfaction_rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                            @endfor
                        </dd>
                        @if($ticket->satisfaction_comment)
                            <dd class="text-xs text-gray-500 mt-1">{{ $ticket->satisfaction_comment }}</dd>
                        @endif
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Timeline --}}
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="font-bold mb-3 text-sm text-gray-500 uppercase">Chronologie</h3>
                <div class="space-y-3 text-xs">
                    <div class="flex gap-2">
                        <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium">Ticket cree</p>
                            <p class="text-gray-400">{{ $ticket->created_at->format('d/m H:i') }}</p>
                        </div>
                    </div>
                    @if($ticket->assigned_at)
                    <div class="flex gap-2">
                        <div class="w-2 h-2 rounded-full bg-yellow-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium">Assigne → {{ $ticket->getServiceLabel() }}</p>
                            <p class="text-gray-400">{{ $ticket->assigned_at->format('d/m H:i') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($ticket->resolved_at)
                    <div class="flex gap-2">
                        <div class="w-2 h-2 rounded-full bg-green-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium">Resolu</p>
                            <p class="text-gray-400">{{ $ticket->resolved_at->format('d/m H:i') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($ticket->closed_at)
                    <div class="flex gap-2">
                        <div class="w-2 h-2 rounded-full bg-gray-400 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium">Cloture</p>
                            <p class="text-gray-400">{{ $ticket->closed_at->format('d/m H:i') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
