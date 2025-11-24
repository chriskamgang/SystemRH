@extends('layouts.admin')

@section('title', 'Vacataires')
@section('page-title', 'Liste des Vacataires')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestion des Vacataires</h2>
            <p class="text-gray-600 mt-1">Personnel vacataire et temporaire</p>
        </div>
        <a href="{{ route('admin.vacataires.create') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <i class="fas fa-plus mr-2"></i> Nouveau Vacataire
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.vacataires.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Nom, email..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Campus</label>
                <select name="campus_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Actif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
                <a href="{{ route('admin.vacataires.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
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
                        Vacataire
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Contact
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campus
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Taux horaire
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
                @forelse($vacataires as $vacataire)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                    <span class="text-purple-600 font-bold">{{ substr($vacataire->first_name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $vacataire->full_name }}</div>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $vacataire->email }}</div>
                        <div class="text-sm text-gray-500">{{ $vacataire->phone ?? '-' }}</div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            @foreach($vacataire->campuses as $campus)
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded mr-1 mb-1">
                                    {{ $campus->name }}
                                </span>
                            @endforeach
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">{{ number_format($vacataire->hourly_rate ?? 0, 0, ',', ' ') }} FCFA/h</div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($vacataire->is_active)
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
                        @if($vacataire->employee_type === 'enseignant_vacataire')
                            <a href="{{ route('admin.vacataires.unites', $vacataire->id) }}" class="text-purple-600 hover:text-purple-900 mr-3" title="Gérer les UE">
                                <i class="fas fa-book"></i>
                            </a>
                        @endif
                        <a href="{{ route('admin.vacataires.show', $vacataire->id) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Voir">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.vacataires.edit', $vacataire->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.vacataires.destroy', $vacataire->id) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce vacataire ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="text-gray-400">
                            <i class="fas fa-user-clock text-6xl mb-4"></i>
                            <p class="text-lg">Aucun vacataire trouvé</p>
                            <p class="text-gray-500 mt-2">Créez un nouveau vacataire pour commencer</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($vacataires->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $vacataires->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
