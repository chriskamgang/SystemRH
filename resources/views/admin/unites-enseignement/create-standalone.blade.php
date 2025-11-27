@extends('layouts.admin')
@section('title', 'Créer une UE')
@section('page-title', 'Créer une Unité d\'Enseignement')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <form method="POST" action="{{ route('admin.unites-enseignement.store-standalone') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Code UE *</label>
                    <input type="text" name="code_ue" value="{{ old('code_ue') }}" required placeholder="Ex: MTH101" class="w-full px-4 py-2 border rounded-lg @error('code_ue') border-red-500 @enderror">
                    @error('code_ue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Volume horaire (heures) *</label>
                    <input type="number" name="volume_horaire_total" value="{{ old('volume_horaire_total') }}" required placeholder="Ex: 18" step="0.5" class="w-full px-4 py-2 border rounded-lg @error('volume_horaire_total') border-red-500 @enderror">
                    @error('volume_horaire_total')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom de la matière *</label>
                    <input type="text" name="nom_matiere" value="{{ old('nom_matiere') }}" required placeholder="Ex: Mathématiques" class="w-full px-4 py-2 border rounded-lg @error('nom_matiere') border-red-500 @enderror">
                    @error('nom_matiere')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Année académique *</label>
                    <input type="text" name="annee_academique" value="{{ old('annee_academique', date('Y').'-'.(date('Y')+1)) }}" required placeholder="2024-2025" class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Semestre</label>
                    <select name="semestre" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Aucun</option>
                        <option value="1">Semestre 1</option>
                        <option value="2">Semestre 2</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                    <input type="text" name="specialite" value="{{ old('specialite') }}" placeholder="Ex: Informatique" class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
                    <input type="text" name="niveau" value="{{ old('niveau') }}" placeholder="Ex: Licence 1" class="w-full px-4 py-2 border rounded-lg">
                </div>
            </div>
            <div class="flex justify-end gap-4 mt-6 pt-6 border-t">
                <a href="{{ route('admin.unites-enseignement.catalog') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Annuler</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"><i class="fas fa-save mr-2"></i> Créer l'UE</button>
            </div>
        </form>
    </div>
</div>
@endsection
