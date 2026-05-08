@extends('layouts.admin')

@section('title', 'Nouvelle session — ' . $program->title)
@section('page-title', 'Nouvelle session de formation')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.training.show', $program->id) }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Nouvelle session</h2>
            <p class="text-gray-600 mt-1">Programme : <span class="font-semibold text-gray-800">{{ $program->title }}</span></p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <p class="font-semibold mb-1"><i class="fas fa-exclamation-circle mr-2"></i>Erreurs de validation :</p>
        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.training.store-session', $program->id) }}"
          class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Nom du formateur --}}
            <div class="md:col-span-2">
                <label for="trainer_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Nom du formateur <span class="text-red-500">*</span>
                </label>
                <input type="text" id="trainer_name" name="trainer_name"
                       value="{{ old('trainer_name') }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="Nom complet du formateur">
            </div>

            {{-- Formateur (utilisateur interne) --}}
            <div class="md:col-span-2">
                <label for="trainer_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Formateur interne (optionnel)
                </label>
                <select id="trainer_id" name="trainer_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">-- Aucun --</option>
                    @foreach($trainers as $trainer)
                        <option value="{{ $trainer->id }}" {{ old('trainer_id') == $trainer->id ? 'selected' : '' }}>
                            {{ $trainer->first_name }} {{ $trainer->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Lieu --}}
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Lieu</label>
                <input type="text" id="location" name="location" value="{{ old('location') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="Ex. Salle A, Campus principal">
            </div>

            {{-- Lien de réunion --}}
            <div>
                <label for="meeting_link" class="block text-sm font-medium text-gray-700 mb-1">Lien de réunion</label>
                <input type="url" id="meeting_link" name="meeting_link" value="{{ old('meeting_link') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="https://meet.example.com/...">
            </div>

            {{-- Date de début --}}
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">
                    Date et heure de début <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" id="start_date" name="start_date"
                       value="{{ old('start_date') }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            {{-- Date de fin --}}
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
                    Date et heure de fin <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" id="end_date" name="end_date"
                       value="{{ old('end_date') }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            {{-- Places max --}}
            <div>
                <label for="max_participants" class="block text-sm font-medium text-gray-700 mb-1">
                    Nombre maximum de participants <span class="text-red-500">*</span>
                </label>
                <input type="number" id="max_participants" name="max_participants"
                       value="{{ old('max_participants', 20) }}" min="1" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            {{-- Statut --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                    Statut <span class="text-red-500">*</span>
                </label>
                <select id="status" name="status" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="scheduled" {{ old('status', 'scheduled') === 'scheduled' ? 'selected' : '' }}>Planifiée</option>
                    <option value="ongoing"   {{ old('status') === 'ongoing'    ? 'selected' : '' }}>En cours</option>
                    <option value="completed" {{ old('status') === 'completed'  ? 'selected' : '' }}>Terminée</option>
                    <option value="cancelled" {{ old('status') === 'cancelled'  ? 'selected' : '' }}>Annulée</option>
                </select>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.training.show', $program->id) }}"
               class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                Annuler
            </a>
            <button type="submit"
                    class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-semibold">
                <i class="fas fa-save mr-2"></i>Créer la session
            </button>
        </div>
    </form>
</div>
@endsection
