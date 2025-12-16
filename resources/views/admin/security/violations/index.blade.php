@extends('admin.layouts.app')

@section('title', 'Violations de Sécurité')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
                Violations de Sécurité
            </h1>
            <p class="text-gray-600">Historique complet des tentatives de fraude détectées</p>
        </div>
        <a href="{{ route('admin.security.dashboard') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition">
            <i class="fas fa-chart-line mr-2"></i>
            Dashboard
        </a>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <p class="text-gray-500 text-sm">Total</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-yellow-500">
            <p class="text-gray-500 text-sm">En Attente</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-red-500">
            <p class="text-gray-500 text-sm">Haute Sévérité</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['high_severity'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-blue-500">
            <p class="text-gray-500 text-sm">Aujourd'hui</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['today'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-purple-500">
            <p class="text-gray-500 text-sm">Comptes Suspendus</p>
            <p class="text-2xl font-bold text-purple-600">{{ $stats['suspended_users'] }}</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.security.violations.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Statut -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En Attente</option>
                    <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Révisé</option>
                    <option value="dismissed" {{ request('status') == 'dismissed' ? 'selected' : '' }}>Ignoré</option>
                    <option value="action_taken" {{ request('status') == 'action_taken' ? 'selected' : '' }}>Action Prise</option>
                </select>
            </div>

            <!-- Sévérité -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sévérité</label>
                <select name="severity" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Toutes</option>
                    <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Faible</option>
                    <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Moyenne</option>
                    <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>Haute</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critique</option>
                </select>
            </div>

            <!-- Utilisateur -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Utilisateur</label>
                <select name="user_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date de début -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Date de fin -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Boutons -->
            <div class="md:col-span-5 flex space-x-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-filter mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.security.violations.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau des violations -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sévérité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($violations as $violation)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                #{{ $violation->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="bg-gray-200 rounded-full p-2 mr-3">
                                        <i class="fas fa-user text-gray-600"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $violation->user->full_name }}</div>
                                        <div class="text-sm text-gray-500">{{ $violation->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $violation->getViolationTypesFormatted() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $violation->getSeverityColorClass() }}">
                                    {{ strtoupper($violation->severity) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $violation->getStatusColorClass() }}">
                                    {{ ucfirst($violation->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $violation->occurred_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.security.violations.show', $violation->id) }}"
                                   class="text-blue-600 hover:text-blue-900">
                                    Détails <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    @if($violations->isEmpty())
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-gray-400"></i>
                                <p>Aucune violation trouvée avec ces filtres</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $violations->links() }}
        </div>
    </div>
</div>
@endsection
