@extends('layouts.admin')

@section('title', 'Créer un Semestre')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Créer un nouveau Semestre</h2>
        <a href="{{ route('admin.semesters.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.semesters.store') }}">
            @csrf

            <div class="space-y-6">
                <!-- Informations générales -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nom du semestre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
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
                            <input type="text" name="code" id="code" value="{{ old('code') }}"
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
                            <input type="text" name="annee_academique" id="annee_academique" value="{{ old('annee_academique') }}"
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
                                <option value="1" {{ old('numero_semestre') == 1 ? 'selected' : '' }}>Semestre 1</option>
                                <option value="2" {{ old('numero_semestre') == 2 ? 'selected' : '' }}>Semestre 2</option>
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
                            <input type="date" name="date_debut" id="date_debut" value="{{ old('date_debut') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('date_debut') border-red-500 @enderror">
                            @error('date_debut')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">
                                Date de fin <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date_fin" id="date_fin" value="{{ old('date_fin') }}"
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
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Boutons -->
                <div class="flex justify-end gap-4 pt-6 border-t">
                    <a href="{{ route('admin.semesters.index') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Créer le semestre
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Helper text -->
    <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Note:</strong> Le semestre sera créé avec le statut "Inactif" par défaut. Vous pourrez l'activer depuis la liste des semestres.
                    Un seul semestre peut être actif à la fois.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-generate code based on other fields
    document.addEventListener('DOMContentLoaded', function() {
        const anneeInput = document.getElementById('annee_academique');
        const numeroInput = document.getElementById('numero_semestre');
        const codeInput = document.getElementById('code');
        const nameInput = document.getElementById('name');

        function generateCode() {
            const annee = anneeInput.value;
            const numero = numeroInput.value;

            if (annee && numero && !codeInput.value) {
                codeInput.value = `S${numero}-${annee}`;
            }
        }

        function generateName() {
            const numero = numeroInput.value;
            const annee = anneeInput.value;

            if (numero && !nameInput.value) {
                nameInput.value = `Semestre ${numero}`;
                if (annee) {
                    nameInput.value += ` (${annee})`;
                }
            }
        }

        anneeInput.addEventListener('blur', function() {
            generateCode();
            generateName();
        });

        numeroInput.addEventListener('change', function() {
            generateCode();
            generateName();
        });
    });
</script>
@endpush
