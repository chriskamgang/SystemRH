@extends('layouts.admin')

@section('title', 'Semi-permanents')
@section('page-title', 'Liste des Semi-permanents')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestion des Semi-permanents</h2>
            <p class="text-gray-600 mt-1">Personnel semi-permanent avec salaire fixe et horaires suivis</p>
        </div>
        <a href="{{ route('admin.employees.create') }}" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <i class="fas fa-plus mr-2"></i> Nouvel Employé
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <i class="fas fa-user-tie text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $semiPermanents->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-check-circle text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Actifs</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $semiPermanents->where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-clock text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Horaire moyen</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($semiPermanents->avg('volume_horaire_hebdomadaire'), 0) }}h/sem</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                    <i class="fas fa-building text-indigo-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Campus</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $campuses->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.semi-permanents.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Nom, email..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Campus</label>
                <select name="campus_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Tous les campus</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Tous</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Actif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
                <a href="{{ route('admin.semi-permanents.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Semi-permanent
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Contact
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campus
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Horaire / Salaire
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Jours
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($semiPermanents as $semiPermanent)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <span class="text-green-600 font-bold">{{ substr($semiPermanent->first_name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <a href="{{ route('admin.semi-permanents.weekly-report', $semiPermanent->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-900 hover:underline">
                                    {{ $semiPermanent->full_name }}
                                </a>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $semiPermanent->email }}</div>
                        <div class="text-sm text-gray-500">{{ $semiPermanent->phone ?? '-' }}</div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            @foreach($semiPermanent->campuses as $campus)
                                <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs rounded mr-1 mb-1">
                                    {{ $campus->name }}
                                </span>
                            @endforeach
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-green-600">{{ number_format($semiPermanent->volume_horaire_hebdomadaire ?? 0, 0) }}h/semaine</div>
                        <div class="text-sm text-gray-900">{{ number_format($semiPermanent->monthly_salary ?? 0, 0, ',', ' ') }} FCFA/mois</div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-xs text-gray-600">
                            @if($semiPermanent->jours_travail && is_array($semiPermanent->jours_travail))
                                @foreach($semiPermanent->jours_travail as $jour)
                                    <span class="inline-block px-1 py-0.5 bg-blue-50 text-blue-700 rounded mr-1">
                                        {{ ucfirst(substr($jour, 0, 3)) }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-gray-400">Non défini</span>
                            @endif
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($semiPermanent->is_active)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Actif
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Inactif
                            </span>
                        @endif
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.semi-permanents.unites', $semiPermanent->id) }}" class="text-purple-600 hover:text-purple-900 mr-3" title="Gérer les UE">
                            <i class="fas fa-book"></i>
                        </a>
                        <a href="{{ route('admin.semi-permanents.show', $semiPermanent->id) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Voir">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.employees.edit', $semiPermanent->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-gray-400">
                            <i class="fas fa-user-tie text-6xl mb-4"></i>
                            <p class="text-lg">Aucun semi-permanent trouvé</p>
                            <p class="text-gray-500 mt-2">Créez un nouvel employé de type semi-permanent pour commencer</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($semiPermanents->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $semiPermanents->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
