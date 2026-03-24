@extends('layouts.admin')

@section('title', 'Ajouter des créneaux')

@section('content')
<div class="max-w-4xl mx-auto" x-data="scheduleForm()">
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.emploi-du-temps.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Ajouter des créneaux</h1>
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

            {{-- UE --}}
            <div class="mb-6">
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

            {{-- Sélection des jours --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sélectionnez les jours de cours</label>
                <div class="flex flex-wrap gap-2">
                    @foreach(['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'] as $jour)
                        <button type="button"
                            @click="toggleJour('{{ $jour }}')"
                            :class="jours['{{ $jour }}'].active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-blue-50'"
                            class="px-4 py-2 border rounded-lg text-sm font-medium transition-colors">
                            {{ ucfirst($jour) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Créneaux par jour --}}
            <div class="space-y-3 mb-6">
                <template x-for="(jour, jourName) in jours" :key="jourName">
                    <div x-show="jour.active" x-transition
                         class="border border-blue-200 bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-xs font-bold mr-3"
                                  x-text="jourName.substring(0,2).toUpperCase()"></span>
                            <h3 class="font-semibold text-blue-800 capitalize" x-text="jourName"></h3>
                            <button type="button" @click="addSlot(jourName)" class="ml-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <i class="fas fa-plus mr-1"></i>Ajouter un créneau
                            </button>
                            <button type="button" @click="toggleJour(jourName)" class="ml-auto text-gray-400 hover:text-red-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <template x-for="(slot, slotIndex) in jour.slots" :key="slotIndex">
                            <div class="mb-3 last:mb-0">
                                <div x-show="jour.slots.length > 1" class="flex items-center mb-2">
                                    <span class="text-xs font-bold text-blue-600" x-text="'Créneau ' + (slotIndex + 1)"></span>
                                    <button type="button" @click="removeSlot(jourName, slotIndex)" class="ml-2 text-red-400 hover:text-red-600 text-xs">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Campus</label>
                                        <select :name="'jours[' + jourName + '][' + slotIndex + '][campus_id]'"
                                                x-model="slot.campus_id"
                                                required
                                                class="w-full border rounded-lg px-3 py-2 text-sm">
                                            <option value="">Campus</option>
                                            @foreach($campuses as $campus)
                                                <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Heure de début</label>
                                        <input type="time"
                                               :name="'jours[' + jourName + '][' + slotIndex + '][heure_debut]'"
                                               x-model="slot.heure_debut"
                                               required
                                               class="w-full border rounded-lg px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Heure de fin</label>
                                        <input type="time"
                                               :name="'jours[' + jourName + '][' + slotIndex + '][heure_fin]'"
                                               x-model="slot.heure_fin"
                                               required
                                               class="w-full border rounded-lg px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Salle (optionnel)</label>
                                        <input type="text"
                                               :name="'jours[' + jourName + '][' + slotIndex + '][salle]'"
                                               x-model="slot.salle"
                                               placeholder="Ex: A204"
                                               class="w-full border rounded-lg px-3 py-2 text-sm">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <div x-show="activeCount === 0" class="text-center py-8 text-gray-400 border-2 border-dashed rounded-lg">
                    <i class="fas fa-calendar-plus text-3xl mb-2"></i>
                    <p class="text-sm">Cliquez sur les jours ci-dessus pour ajouter des créneaux</p>
                </div>
            </div>

            {{-- Période de validité --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-bold text-gray-700 mb-2"><i class="fas fa-calendar-week mr-1"></i> Période de validité</h3>
                <p class="text-xs text-gray-500 mb-3">Laissez vide pour un créneau récurrent (toutes les semaines). Remplissez pour limiter à une période précise.</p>
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

            {{-- Actions --}}
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500" x-show="totalSlots > 0">
                    <i class="fas fa-info-circle mr-1"></i>
                    <span x-text="totalSlots"></span> créneau(x) à créer
                </span>
                <div class="flex space-x-3 ml-auto">
                    <a href="{{ route('admin.emploi-du-temps.index') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Annuler</a>
                    <button type="submit" :disabled="totalSlots === 0"
                            :class="totalSlots === 0 ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                            class="text-white px-6 py-2 rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function scheduleForm() {
    return {
        jours: {
            lundi:    { active: false, slots: [{ campus_id: '', heure_debut: '', heure_fin: '', salle: '' }] },
            mardi:    { active: false, slots: [{ campus_id: '', heure_debut: '', heure_fin: '', salle: '' }] },
            mercredi: { active: false, slots: [{ campus_id: '', heure_debut: '', heure_fin: '', salle: '' }] },
            jeudi:    { active: false, slots: [{ campus_id: '', heure_debut: '', heure_fin: '', salle: '' }] },
            vendredi: { active: false, slots: [{ campus_id: '', heure_debut: '', heure_fin: '', salle: '' }] },
            samedi:   { active: false, slots: [{ campus_id: '', heure_debut: '', heure_fin: '', salle: '' }] },
            dimanche: { active: false, slots: [{ campus_id: '', heure_debut: '', heure_fin: '', salle: '' }] },
        },
        get activeCount() {
            return Object.values(this.jours).filter(j => j.active).length;
        },
        get totalSlots() {
            return Object.values(this.jours).reduce((sum, j) => sum + (j.active ? j.slots.length : 0), 0);
        },
        toggleJour(jour) {
            this.jours[jour].active = !this.jours[jour].active;
            if (!this.jours[jour].active) {
                this.jours[jour].slots = [{ campus_id: '', heure_debut: '', heure_fin: '', salle: '' }];
            }
        },
        addSlot(jour) {
            this.jours[jour].slots.push({ campus_id: '', heure_debut: '', heure_fin: '', salle: '' });
        },
        removeSlot(jour, index) {
            if (this.jours[jour].slots.length > 1) {
                this.jours[jour].slots.splice(index, 1);
            }
        }
    }
}
</script>
@endsection
