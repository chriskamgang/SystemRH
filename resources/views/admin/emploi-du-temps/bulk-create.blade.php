@extends('layouts.admin')

@section('title', 'Création en lot - Emploi du Temps')

@section('content')
<div class="max-w-5xl mx-auto" x-data="bulkSchedule()">
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.emploi-du-temps.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Création en lot</h1>
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
        <form method="POST" action="{{ route('admin.emploi-du-temps.bulk-store') }}">
            @csrf

            <template x-for="(slot, index) in slots" :key="index">
                <div class="border rounded-lg p-4 mb-4 bg-gray-50">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-medium text-gray-700" x-text="'Créneau #' + (index + 1)"></h3>
                        <button type="button" @click="removeSlot(index)" x-show="slots.length > 1" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">UE</label>
                            <select :name="'slots[' + index + '][unite_enseignement_id]'" required class="w-full border rounded px-2 py-1.5 text-sm">
                                <option value="">Sélectionner</option>
                                @foreach($ues as $ue)
                                    <option value="{{ $ue->id }}">{{ $ue->code_ue }} - {{ $ue->nom_matiere }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Campus</label>
                            <select :name="'slots[' + index + '][campus_id]'" required class="w-full border rounded px-2 py-1.5 text-sm">
                                <option value="">Sélectionner</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Jour</label>
                            <select :name="'slots[' + index + '][jour_semaine]'" required class="w-full border rounded px-2 py-1.5 text-sm">
                                <option value="">Sélectionner</option>
                                <option value="lundi">Lundi</option>
                                <option value="mardi">Mardi</option>
                                <option value="mercredi">Mercredi</option>
                                <option value="jeudi">Jeudi</option>
                                <option value="vendredi">Vendredi</option>
                                <option value="samedi">Samedi</option>
                                <option value="dimanche">Dimanche</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Début</label>
                            <input type="time" :name="'slots[' + index + '][heure_debut]'" required class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Fin</label>
                            <input type="time" :name="'slots[' + index + '][heure_fin]'" required class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Salle</label>
                            <input type="text" :name="'slots[' + index + '][salle]'" placeholder="Ex: A204" class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                    </div>
                </div>
            </template>

            <!-- Période de validité commune à tous les créneaux -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4 mb-4">
                <h3 class="text-sm font-bold text-blue-800 mb-2"><i class="fas fa-calendar-week mr-1"></i> Période de validité (pour tous les créneaux)</h3>
                <p class="text-xs text-blue-600 mb-3">Laissez vide pour des créneaux récurrents. Remplissez pour une semaine précise (ex: semaine prochaine).</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date début</label>
                        <input type="date" name="date_debut_validite" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Date fin</label>
                        <input type="date" name="date_fin_validite" class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mt-4">
                <button type="button" @click="addSlot()" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus mr-1"></i>Ajouter un créneau
                </button>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.emploi-du-temps.index') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Annuler</a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Enregistrer tout
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function bulkSchedule() {
    return {
        slots: [{}],
        addSlot() {
            this.slots.push({});
        },
        removeSlot(index) {
            this.slots.splice(index, 1);
        }
    }
}
</script>
@endsection
