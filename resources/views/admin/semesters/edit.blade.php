@extends('layouts.admin')

@section('title', 'Modifier le Semestre')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Modifier le Semestre</h2>
        <a href="{{ route('admin.semesters.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.semesters.update', $semester->id) }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Informations générales -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nom du semestre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $semester->name) }}"
                                   placeholder="Ex: Semestre 1, Semestre 2"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                                Code du semestre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="code" id="code" value="{{ old('code', $semester->code) }}"
                                   placeholder="Ex: S1-2024-2025, S2-2024-2025"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror">
                            <p class="mt-1 text-sm text-gray-500">Code unique du semestre</p>
                            @error('code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="annee_academique" class="block text-sm font-medium text-gray-700 mb-2">
                                Année Académique <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="annee_academique" id="annee_academique" value="{{ old('annee_academique', $semester->annee_academique) }}"
                                   placeholder="Ex: 2024-2025"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('annee_academique') border-red-500 @enderror">
                            @error('annee_academique')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="numero_semestre" class="block text-sm font-medium text-gray-700 mb-2">
                                Numéro de semestre <span class="text-red-500">*</span>
                            </label>
                            <select name="numero_semestre" id="numero_semestre"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('numero_semestre') border-red-500 @enderror">
                                <option value="">Sélectionnez...</option>
                                <option value="1" {{ old('numero_semestre', $semester->numero_semestre) == 1 ? 'selected' : '' }}>Semestre 1</option>
                                <option value="2" {{ old('numero_semestre', $semester->numero_semestre) == 2 ? 'selected' : '' }}>Semestre 2</option>
                            </select>
                            @error('numero_semestre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Période -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Période du semestre</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">
                                Date de début <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date_debut" id="date_debut" value="{{ old('date_debut', $semester->date_debut->format('Y-m-d')) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date_debut') border-red-500 @enderror">
                            @error('date_debut')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">
                                Date de fin <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date_fin" id="date_fin" value="{{ old('date_fin', $semester->date_fin->format('Y-m-d')) }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date_fin') border-red-500 @enderror">
                            @error('date_fin')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description (optionnel)
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  placeholder="Description ou notes sur ce semestre..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description', $semester->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Statut -->
                @if($semester->is_active)
                <div class="bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                <strong>Ce semestre est actuellement actif.</strong> Pour le désactiver, utilisez le bouton d'action dans la liste des semestres.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Boutons -->
                <div class="flex justify-end gap-4 pt-6 border-t">
                    <a href="{{ route('admin.semesters.index') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Warning if has UE -->
    @if($semester->unitesEnseignement()->count() > 0)
    <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>Attention:</strong> Ce semestre contient {{ $semester->unitesEnseignement()->count() }} unité(s) d'enseignement.
                    Modifier les dates peut affecter les unités associées.
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
