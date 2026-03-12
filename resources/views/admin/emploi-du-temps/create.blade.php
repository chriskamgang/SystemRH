@extends('layouts.admin')

@section('title', 'Ajouter un créneau')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.emploi-du-temps.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Ajouter un créneau</h1>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.emploi-du-temps.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Unité d'Enseignement</label>
                <select name="unite_enseignement_id" required class="w-full border rounded-lg px-3 py-2">
                    <option value="">Sélectionner une UE</option>
                    @foreach($ues as $ue)
                        <option value="{{ $ue->id }}" {{ old('unite_enseignement_id') == $ue->id ? 'selected' : '' }}>
                            {{ $ue->code_ue }} - {{ $ue->nom_matiere }}
                            @if($ue->enseignant) ({{ $ue->enseignant->last_name }} {{ $ue->enseignant->first_name }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select name="campus_id" required class="w-full border rounded-lg px-3 py-2">
                    <option value="">Sélectionner un campus</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Jour(s) de la semaine</label>
                <div class="flex flex-wrap gap-3">
                    @foreach(['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'] as $jour)
                        <label class="flex items-center px-4 py-2 border rounded-lg cursor-pointer hover:bg-blue-50 has-[:checked]:bg-blue-100 has-[:checked]:border-blue-500">
                            <input type="checkbox" name="jours[]" value="{{ $jour }}"
                                {{ is_array(old('jours')) && in_array($jour, old('jours')) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 mr-2">
                            <span class="text-sm">{{ ucfirst($jour) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('jours')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de début</label>
                    <input type="time" name="heure_debut" value="{{ old('heure_debut') }}" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fin</label>
                    <input type="time" name="heure_fin" value="{{ old('heure_fin') }}" required class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Salle (optionnel)</label>
                <input type="text" name="salle" value="{{ old('salle') }}" placeholder="Ex: A204" class="w-full border rounded-lg px-3 py-2">
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-bold text-blue-800 mb-2"><i class="fas fa-calendar-week mr-1"></i> Période de validité</h3>
                <p class="text-xs text-blue-600 mb-3">Laissez vide pour un créneau récurrent (toutes les semaines). Remplissez pour limiter à une période précise.</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date début</label>
                        <input type="date" name="date_debut_validite" value="{{ old('date_debut_validite') }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date fin</label>
                        <input type="date" name="date_fin_validite" value="{{ old('date_fin_validite') }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.emploi-du-temps.index') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Annuler</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
