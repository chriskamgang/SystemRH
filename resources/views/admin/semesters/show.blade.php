@extends('layouts.admin')

@section('title', 'Détails du Semestre')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $semester->name }}</h1>
            <p class="text-gray-600 mt-1">
                <span class="font-medium">{{ $semester->code }}</span>
                •
                <span>{{ $semester->annee_academique }}</span>
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.semesters.edit', $semester->id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-edit mr-2"></i>
                Modifier
            </a>
            <a href="{{ route('admin.semesters.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </a>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informations du semestre -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Informations</h2>

                <!-- Statut -->
                <div class="mb-4">
                    @if($semester->is_active)
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                            <i class="fas fa-check-circle mr-1"></i>
                            Semestre Actif
                        </span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-semibold">
                            <i class="fas fa-circle mr-1"></i>
                            Inactif
                        </span>
                    @endif
                </div>

                <!-- Details -->
                <div class="space-y-3 text-sm">
                    <div class="border-b pb-3">
                        <p class="text-gray-600 mb-1">Année Académique</p>
                        <p class="font-semibold text-gray-900">{{ $semester->annee_academique }}</p>
                    </div>

                    <div class="border-b pb-3">
                        <p class="text-gray-600 mb-1">Numéro de semestre</p>
                        <p class="font-semibold text-gray-900">Semestre {{ $semester->numero_semestre }}</p>
                    </div>

                    <div class="border-b pb-3">
                        <p class="text-gray-600 mb-1">Date de début</p>
                        <p class="font-semibold text-gray-900">
                            <i class="fas fa-calendar-alt mr-1 text-gray-400"></i>
                            {{ $semester->date_debut->format('d/m/Y') }}
                        </p>
                    </div>

                    <div class="border-b pb-3">
                        <p class="text-gray-600 mb-1">Date de fin</p>
                        <p class="font-semibold text-gray-900">
                            <i class="fas fa-calendar-alt mr-1 text-gray-400"></i>
                            {{ $semester->date_fin->format('d/m/Y') }}
                        </p>
                    </div>

                    <div class="border-b pb-3">
                        <p class="text-gray-600 mb-1">Durée</p>
                        <p class="font-semibold text-gray-900">
                            {{ $semester->date_debut->diffInDays($semester->date_fin) }} jours
                        </p>
                    </div>

                    @if($semester->description)
                    <div class="pt-2">
                        <p class="text-gray-600 mb-1">Description</p>
                        <p class="text-gray-900">{{ $semester->description }}</p>
                    </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="mt-6 space-y-2">
                    @if(!$semester->is_active)
                        <form action="{{ route('admin.semesters.activate', $semester->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                                <i class="fas fa-check-circle mr-2"></i>
                                Activer ce semestre
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.semesters.deactivate', $semester->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition">
                                <i class="fas fa-pause-circle mr-2"></i>
                                Désactiver ce semestre
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Stats Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-4">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Statistiques</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Unités d'enseignement</span>
                        <span class="text-2xl font-bold text-blue-600">{{ $semester->unitesEnseignement->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">UE Activées</span>
                        <span class="text-2xl font-bold text-green-600">{{ $semester->unitesEnseignement->where('is_active', true)->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Vacataires assignés</span>
                        <span class="text-2xl font-bold text-purple-600">{{ $semester->unitesEnseignement->unique('vacataire_id')->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unités d'enseignement -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-gray-800">
                        Unités d'Enseignement ({{ $semester->unitesEnseignement->count() }})
                    </h2>
                    @if($semester->is_active)
                        <a href="{{ route('admin.unites-enseignement.create', ['semester_id' => $semester->id]) }}" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
                            <i class="fas fa-plus mr-1"></i>
                            Ajouter UE
                        </a>
                    @endif
                </div>

                @if($semester->unitesEnseignement->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vacataire</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($semester->unitesEnseignement as $ue)
                                    <tr class="{{ $ue->is_active ? '' : 'bg-gray-50' }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm font-mono text-gray-900">{{ $ue->code }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $ue->nom }}</div>
                                            @if($ue->matiere)
                                                <div class="text-xs text-gray-500">{{ $ue->matiere }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($ue->vacataire)
                                                <div class="text-sm text-gray-900">{{ $ue->vacataire->full_name }}</div>
                                            @else
                                                <span class="text-sm text-gray-400 italic">Non assigné</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-sm text-gray-900">{{ $ue->nombre_heures }}h</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($ue->is_active)
                                                <span class="px-2 text-xs rounded-full bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle mr-1"></i>Actif
                                                </span>
                                            @else
                                                <span class="px-2 text-xs rounded-full bg-gray-100 text-gray-800">
                                                    Inactif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                            <a href="{{ route('admin.unites-enseignement.show', $ue->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.unites-enseignement.edit', $ue->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <i class="fas fa-book text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">Aucune unité d'enseignement pour ce semestre</p>
                        @if($semester->is_active)
                            <a href="{{ route('admin.unites-enseignement.create', ['semester_id' => $semester->id]) }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                Ajouter la première unité d'enseignement
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
