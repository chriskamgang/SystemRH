@extends('layouts.admin')
@section('title', 'Séances TP - ' . $ue->code_ue)
@section('page-title', 'Configurer les séances TP')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800">{{ $ue->code_ue }} — {{ $ue->nom_matiere }}</h2>
            <p class="text-sm text-gray-500">Définissez les séances et leurs objectifs. Les étudiants pourront valider chaque séance.</p>
        </div>

        <form method="POST" action="{{ route('admin.unites-enseignement.seances.update', $ue) }}" x-data="{
            seances: {{ json_encode($ue->seances->count() > 0
                ? $ue->seances->map(fn($s) => ['titre' => $s->titre, 'objectif' => $s->objectif])->values()
                : [['titre' => '', 'objectif' => '']]
            ) }}
        }">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <template x-for="(seance, index) in seances" :key="index">
                    <div class="border rounded-lg p-4 bg-gray-50 relative">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-bold text-blue-600" x-text="'Séance ' + (index + 1)"></span>
                            <button type="button" @click="seances.splice(index, 1)" x-show="seances.length > 1"
                                class="text-red-400 hover:text-red-600 text-sm">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Titre de la séance *</label>
                                <input type="text" :name="'seances[' + index + '][titre]'" x-model="seance.titre"
                                    required placeholder="Ex: Appareil locomoteur"
                                    class="w-full px-3 py-2 border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Objectif *</label>
                                <input type="text" :name="'seances[' + index + '][objectif]'" x-model="seance.objectif"
                                    required placeholder="Ex: Localiser les organes et structures anatomiques"
                                    class="w-full px-3 py-2 border rounded-lg text-sm">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="seances.push({titre: '', objectif: ''})"
                class="mt-4 px-4 py-2 text-sm bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100">
                <i class="fas fa-plus mr-1"></i> Ajouter une séance
            </button>

            <div class="flex justify-end gap-4 mt-6 pt-6 border-t">
                <a href="{{ route('admin.unites-enseignement.seances.show', $ue) }}"
                    class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Annuler</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i> Enregistrer les séances
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
