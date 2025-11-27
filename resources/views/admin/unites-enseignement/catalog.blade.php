@extends('layouts.admin')

@section('title', 'Bibliothèque des UE')
@section('page-title', 'Bibliothèque des Unités d\'Enseignement')

@section('content')
<div class="container mx-auto px-4">
    <!-- Actions rapides -->
    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('admin.unites-enseignement.create-standalone') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <i class="fas fa-plus mr-2"></i> Créer une UE
        </a>
        <a href="{{ route('admin.unites-enseignement.assign') }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <i class="fas fa-user-tag mr-2"></i> Attribuer une UE
        </a>
        <a href="{{ route('admin.unites-enseignement.import') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
            <i class="fas fa-file-import mr-2"></i> Importer des UE
        </a>
    </div>

    <!-- Filtres et recherche -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.unites-enseignement.catalog') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Recherche -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Code UE ou nom matière..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Spécialité -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                <select name="specialite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes</option>
                    @foreach($specialites as $spec)
                        <option value="{{ $spec }}" {{ request('specialite') == $spec ? 'selected' : '' }}>{{ $spec }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Niveau -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
                <select name="niveau" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach($niveaux as $niv)
                        <option value="{{ $niv }}" {{ request('niveau') == $niv ? 'selected' : '' }}>{{ $niv }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Bouton filtrer -->
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des UE -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code UE</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matière</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Spécialité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Niveau</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volume (h)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Année</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enseignant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($unites as $ue)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap font-mono text-sm font-semibold text-blue-600">
                            {{ $ue->code_ue }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $ue->nom_matiere }}</div>
                            @if($ue->semestre)
                                <div class="text-xs text-gray-500">Semestre {{ $ue->semestre }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $ue->specialite ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $ue->niveau ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $ue->volume_horaire_total }} h
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $ue->annee_academique }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ue->enseignant)
                                <div class="text-sm font-medium text-gray-900">{{ $ue->enseignant->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $ue->enseignant->employee_type)) }}</div>
                            @else
                                <span class="text-xs text-gray-400 italic">Non attribuée</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ue->statut === 'activee')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> Activée
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <i class="fas fa-circle mr-1"></i> Non activée
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            @if($ue->enseignant)
                                <a href="{{ route('admin.vacataires.unites', $ue->enseignant_id) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @else
                                <a href="{{ route('admin.unites-enseignement.assign') }}?code_ue={{ $ue->code_ue }}" class="text-green-600 hover:text-green-900" title="Attribuer">
                                    <i class="fas fa-user-tag"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-book text-4xl mb-3 text-gray-400"></i>
                            <p class="text-lg">Aucune UE trouvée</p>
                            <p class="text-sm mt-2">Créez votre première UE ou importez un fichier Excel</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $unites->links() }}
        </div>
    </div>
</div>
@endsection
