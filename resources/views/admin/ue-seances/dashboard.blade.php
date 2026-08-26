@extends('layouts.admin')
@section('title', 'Compétences TP')
@section('page-title', 'Tableau de bord — Compétences TP')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Stats globales --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800">{{ $totalSeances }}</div>
                    <div class="text-sm text-gray-500">Séances TP configurées</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-double text-green-600 text-xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800">{{ $totalValidations }}</div>
                    <div class="text-sm text-gray-500">TP effectués (validés)</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bullseye text-purple-600 text-xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-800">{{ $totalObjectifs }}</div>
                    <div class="text-sm text-gray-500">Objectifs atteints</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Spécialité</label>
                <select name="specialite" class="px-3 py-2 border rounded-lg text-sm">
                    <option value="">Toutes</option>
                    @foreach($specialites as $spec)
                        <option value="{{ $spec }}" {{ request('specialite') == $spec ? 'selected' : '' }}>{{ $spec }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Niveau</label>
                <select name="niveau" class="px-3 py-2 border rounded-lg text-sm">
                    <option value="">Tous</option>
                    @foreach($niveaux as $niv)
                        <option value="{{ $niv }}" {{ request('niveau') == $niv ? 'selected' : '' }}>{{ $niv }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
            @if(request()->hasAny(['specialite', 'niveau']))
                <a href="{{ route('admin.tp-competences.dashboard') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">Effacer</a>
            @endif
        </form>
    </div>

    {{-- Liste des UE avec séances --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700"><i class="fas fa-book-open mr-2"></i> UE avec séances TP</h3>
        </div>

        @if($ues->count() === 0)
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                <p>Aucune UE avec des séances TP configurées.</p>
                <p class="text-sm mt-2">Allez dans une UE du catalogue et cliquez sur "Configurer les séances TP".</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Code</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Matière</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Spécialité</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Niveau</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Séances</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($ues as $ue)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-blue-600 font-bold">{{ $ue->code_ue }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $ue->nom_matiere }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if($ue->type_ue === 'tronc_commun')
                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-bold">TC</span>
                                        <span class="text-xs text-gray-400">{{ implode(', ', $ue->groupes ?? []) }}</span>
                                    @else
                                        {{ $ue->specialite ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">{{ $ue->niveau ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">{{ $ue->seances_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.unites-enseignement.seances.show', $ue) }}"
                                        class="px-3 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 text-xs font-medium">
                                        <i class="fas fa-eye mr-1"></i> Voir
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $ues->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
