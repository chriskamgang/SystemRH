@extends('layouts.admin')

@section('title', 'Alertes de Présence')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Alertes de Présence</h1>
            <p class="text-gray-600 mt-2">Gérez les incidents de présence et validez les pénalités</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.presence-alerts.statistics') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-chart-line mr-2"></i>
                Statistiques
            </a>
            <a href="{{ route('admin.presence-alerts.settings') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-cog mr-2"></i>
                Configuration
            </a>
        </div>
    </div>

    <!-- Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Onglets de Statut -->
    <div class="bg-white rounded-lg shadow-md mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="?status=pending" class="px-6 py-4 text-sm font-medium border-b-2 {{ $status == 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-clock mr-2"></i>
                    En attente
                    @if($pendingCount > 0)
                        <span class="ml-2 px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full">{{ $pendingCount }}</span>
                    @endif
                </a>
                <a href="?status=validated" class="px-6 py-4 text-sm font-medium border-b-2 {{ $status == 'validated' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-check-circle mr-2"></i>
                    Validés
                </a>
                <a href="?status=ignored" class="px-6 py-4 text-sm font-medium border-b-2 {{ $status == 'ignored' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-times-circle mr-2"></i>
                    Ignorés
                </a>
                <a href="?status=all" class="px-6 py-4 text-sm font-medium border-b-2 {{ $status == 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-list mr-2"></i>
                    Tous
                </a>
            </nav>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="status" value="{{ $status }}">

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Rechercher un employé..."
                class="px-4 py-2 border border-gray-300 rounded-lg"
            >

            <select name="campus_id" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">Tous les campus</option>
                @foreach(\App\Models\Campus::all() as $campus)
                    <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                        {{ $campus->name }}
                    </option>
                @endforeach
            </select>

            <input
                type="date"
                name="date_from"
                value="{{ request('date_from') }}"
                placeholder="Date début"
                class="px-4 py-2 border border-gray-300 rounded-lg"
            >

            <div class="flex space-x-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <a href="{{ route('admin.presence-alerts.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des Incidents -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        @if($incidents->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Employé
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Campus
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Notification
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Réponse
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($incidents as $incident)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                                            {{ substr($incident->user->first_name, 0, 1) }}{{ substr($incident->user->last_name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $incident->user->full_name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $incident->user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $incident->campus->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $incident->incident_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ substr($incident->notification_sent_at, 0, 5) }}</div>
                                <div class="text-xs text-gray-500">Deadline: {{ substr($incident->response_deadline, 0, 5) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($incident->has_responded)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i> Répondu
                                    </span>
                                    @if($incident->responded_at)
                                        <div class="text-xs text-gray-500 mt-1">{{ $incident->responded_at->format('H:i') }}</div>
                                    @endif
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-times mr-1"></i> Non répondu
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($incident->status === 'pending')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i> En attente
                                    </span>
                                @elseif($incident->status === 'validated')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Validé ({{ $incident->penalty_hours }}h)
                                    </span>
                                @elseif($incident->status === 'ignored')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <i class="fas fa-ban mr-1"></i> Ignoré
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.presence-alerts.show', $incident->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200">
                {{ $incidents->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">Aucun incident trouvé.</p>
            </div>
        @endif
    </div>
</div>
@endsection
