@extends('layouts.admin')

@section('title', 'Bibliothèque des UE')
@section('page-title', 'Bibliothèque des Unités d\'Enseignement')

@section('content')
<div class="container mx-auto px-4" x-data="{ openEmployeeSearch: false, employeeSearch: '' }">
    <!-- Actions rapides et Recherche Employé -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.unites-enseignement.create-standalone') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition shadow-sm font-semibold">
                <i class="fas fa-plus mr-2"></i> Créer une UE
            </a>
            <a href="{{ route('admin.unites-enseignement.assign') }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition shadow-sm font-semibold">
                <i class="fas fa-user-tag mr-2"></i> Attribuer une UE
            </a>
            <a href="{{ route('admin.unites-enseignement.import') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition shadow-sm font-semibold">
                <i class="fas fa-file-import mr-2"></i> Importer des UE
            </a>
        </div>

        <!-- Recherche Employé type "Mise en avant" -->
        <div class="relative w-full md:w-64">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Rechercher un employé...</label>
            <div class="relative">
                <input 
                    type="text" 
                    x-model="employeeSearch" 
                    @focus="openEmployeeSearch = true"
                    placeholder="Nom de l'enseignant..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                >
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <i class="fas fa-user-search"></i>
                </span>
            </div>

            <!-- Dropdown resultats -->
            <div 
                x-show="openEmployeeSearch && employeeSearch.length > 0" 
                @click.away="openEmployeeSearch = false"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto"
                x-cloak
            >
                @foreach($allEmployees as $emp)
                    <a 
                        x-show="'{{ strtolower($emp->full_name) }}'.includes(employeeSearch.toLowerCase())"
                        href="{{ route('admin.vacataires.unites', $emp->id) }}" 
                        class="block px-4 py-3 hover:bg-blue-50 border-b border-gray-50 last:border-0 transition"
                    >
                        <div class="text-sm font-bold text-gray-800">{{ $emp->full_name }}</div>
                        <div class="text-[10px] text-gray-500 uppercase font-semibold">{{ ucfirst(str_replace('_', ' ', $emp->employee_type)) }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Filtres de recherche</h3>
        <form method="GET" action="{{ route('admin.unites-enseignement.catalog') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Recherche -->
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Recherche</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Code UE ou nom matière..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </div>

            <!-- Spécialité -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Spécialité</label>
                <select name="specialite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes</option>
                    @foreach($specialties_list as $spec)
                        <option value="{{ $spec->name }}" {{ request('specialite') == $spec->name ? 'selected' : '' }}>{{ $spec->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Niveau -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Niveau</label>
                <select name="niveau" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach($levels_list as $niv)
                        <option value="{{ $niv->name }}" {{ request('niveau') == $niv->name ? 'selected' : '' }}>{{ $niv->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Bouton filtrer -->
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg transition font-bold shadow-sm">
                    <i class="fas fa-filter mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des UE -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Code UE</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Matière</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Spécialité</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Niveau</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Volume (h)</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Année</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Enseignant</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Statut</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($unites as $ue)
                        <tr class="hover:bg-blue-50/30 transition">
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-xs font-bold text-blue-600">
                                <span class="bg-blue-50 px-2 py-1 rounded">{{ $ue->code_ue }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-800">{{ $ue->nom_matiere }}</div>
                                @if($ue->semestre)
                                    <div class="text-[10px] font-bold text-gray-400 uppercase mt-0.5">Semestre {{ $ue->semestre }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-gray-600 uppercase">
                                {{ $ue->specialite ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-gray-600 uppercase">
                                {{ $ue->niveau ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 text-center">
                                {{ number_format($ue->volume_horaire_total, 2) }} h
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-gray-500">
                                {{ $ue->annee_academique ?? '—' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($ue->enseignant)
                                    <div class="flex items-center">
                                        <div class="h-7 w-7 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-600 border border-white shadow-sm mr-2">
                                            {{ substr($ue->enseignant->first_name, 0, 1) }}{{ substr($ue->enseignant->last_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-gray-800">{{ $ue->enseignant->full_name }}</div>
                                            <div class="text-[9px] text-gray-400 uppercase font-bold">{{ ucfirst(str_replace('_', ' ', $ue->enseignant->employee_type)) }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[10px] text-gray-300 italic font-medium">Non attribuée</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($ue->statut === 'activee')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                                        <span class="h-1 w-1 bg-green-500 rounded-full mr-1.5"></span>
                                        ACTIVÉE
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-50 text-gray-400 border border-gray-100">
                                        NON ACTIVÉE
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex justify-end gap-1">
                                    @if($ue->enseignant)
                                        <a href="{{ route('admin.vacataires.unites', $ue->enseignant_id) }}" class="p-1.5 text-gray-400 hover:text-blue-600 transition" title="Voir détails">
                                            <i class="fas fa-eye text-sm"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.unites-enseignement.edit', $ue->id) }}" class="p-1.5 text-gray-400 hover:text-yellow-600 transition" title="Modifier">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <div class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                                        <i class="fas fa-book-open text-2xl text-gray-200"></i>
                                    </div>
                                    <p class="text-lg font-medium">Aucune UE trouvée</p>
                                    <p class="text-sm mt-1">Ajustez vos filtres ou créez une nouvelle unité d'enseignement.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($unites->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                {{ $unites->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
