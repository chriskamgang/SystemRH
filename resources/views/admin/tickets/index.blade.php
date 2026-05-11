@extends('layouts.admin')

@section('title', 'Tickets')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Tickets de Plainte</h1>
        <a href="{{ route('admin.tickets.settings') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700">
            <i class="fas fa-cog mr-1"></i> Parametres
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500">Total</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-red-500">
            <p class="text-2xl font-bold text-red-600">{{ $stats['new'] }}</p>
            <p class="text-xs text-gray-500">Nouveaux</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-yellow-500">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['assigned'] }}</p>
            <p class="text-xs text-gray-500">Assignes</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-blue-500">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['in_progress'] }}</p>
            <p class="text-xs text-gray-500">En cours</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-purple-500">
            <p class="text-2xl font-bold text-purple-600">{{ $stats['responded'] }}</p>
            <p class="text-xs text-gray-500">Repondus</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-green-500">
            <p class="text-2xl font-bold text-green-600">{{ $stats['resolved'] }}</p>
            <p class="text-xs text-gray-500">Resolus</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-gray-400">
            <p class="text-2xl font-bold text-gray-600">{{ $stats['closed'] }}</p>
            <p class="text-xs text-gray-500">Clotures</p>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="N° ticket, nom, objet..." class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Statut</label>
                <select name="status" class="px-3 py-2 border rounded-lg text-sm">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Ticket::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Service</label>
                <select name="service" class="px-3 py-2 border rounded-lg text-sm">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Ticket::getActiveServices() as $key => $label)
                        <option value="{{ $key }}" {{ request('service') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Priorite</label>
                <select name="priority" class="px-3 py-2 border rounded-lg text-sm">
                    <option value="">Toutes</option>
                    @foreach(\App\Models\Ticket::PRIORITIES as $key => $label)
                        <option value="{{ $key }}" {{ request('priority') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                <i class="fas fa-search mr-1"></i> Filtrer
            </button>
            <a href="{{ route('admin.tickets.index') }}" class="px-3 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-100">&times;</a>
        </form>
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Ticket</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Emetteur</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Objet</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Service</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Priorite</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Statut</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($tickets as $ticket)
                <tr class="hover:bg-gray-50 {{ $ticket->status === 'new' ? 'bg-red-50' : '' }}">
                    <td class="px-4 py-3 font-mono font-bold text-blue-600">
                        <a href="{{ route('admin.tickets.show', $ticket->id) }}">{{ $ticket->ticket_number }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</div>
                        <div class="text-xs text-gray-400">{{ $ticket->user->employee_id }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div>{{ Str::limit($ticket->subject, 40) }}</div>
                        <div class="text-xs text-gray-400">{{ $ticket->getCategoryLabel() }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs">{{ $ticket->getServiceLabel() }}</span>
                        @if($ticket->was_redirected)
                            <span class="text-xs text-orange-500" title="Redirige depuis {{ \App\Models\Ticket::getActiveServices()[$ticket->target_service] ?? $ticket->target_service }}"><i class="fas fa-exchange-alt"></i></span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $pColors = ['low' => 'bg-gray-100 text-gray-700', 'medium' => 'bg-blue-100 text-blue-700', 'high' => 'bg-orange-100 text-orange-700', 'critical' => 'bg-red-100 text-red-700'];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $pColors[$ticket->priority] ?? '' }}">
                            {{ $ticket->getPriorityLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $sColors = ['new' => 'bg-red-100 text-red-700', 'assigned' => 'bg-yellow-100 text-yellow-700', 'in_progress' => 'bg-blue-100 text-blue-700', 'responded' => 'bg-purple-100 text-purple-700', 'resolved' => 'bg-green-100 text-green-700', 'closed' => 'bg-gray-100 text-gray-700'];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $sColors[$ticket->status] ?? '' }}">
                            {{ $ticket->getStatusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $ticket->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="text-blue-600 hover:underline text-sm">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">Aucun ticket pour le moment.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 border-t">
            {{ $tickets->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
