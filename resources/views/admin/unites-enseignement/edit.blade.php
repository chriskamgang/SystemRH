@extends('layouts.admin')

@section('title', 'Modifier UE - ' . $ue->nom_matiere)
@section('page-title', 'Modifier l\'Unité d\'Enseignement')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Modifier {{ $ue->nom_matiere }}</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Vacataire: {{ $ue->vacataire->full_name }}
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium
                    {{ $ue->statut === 'activee' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                    {{ $ue->statut === 'activee' ? 'Activée' : 'Non activée' }}
                </span>
            </div>
        </div>

        <form action="{{ route('admin.unites-enseignement.update', $ue->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Niveau -->
                <div>
                    <label for="niveau" class="block text-sm font-medium text-gray-700 mb-2">
                        Niveau
                    </label>
                    <select
                        name="niveau"
                        id="niveau"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onchange="onNiveauChange()"
                    >
                        <option value="">Sélectionner</option>
                        @foreach(['BTS 1', 'BTS 2', 'Licence 1', 'Licence 2', 'Licence 3', 'Master 1', 'Master 2'] as $niv)
                            <option value="{{ $niv }}" {{ old('niveau', $ue->niveau) == $niv ? 'selected' : '' }}>{{ $niv }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Taux horaire UE -->
                <div>
                    <label for="taux_horaire" class="block text-sm font-medium text-gray-700 mb-2">
                        Taux horaire UE (FCFA/h)
                    </label>
                    <input
                        type="number"
                        name="taux_horaire"
                        id="taux_horaire"
                        value="{{ old('taux_horaire', $ue->taux_horaire) }}"
                        placeholder="Taux du vacataire si vide"
                        step="100"
                        min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        oninput="calculerMontantMax()"
                    >
                    <p class="text-xs text-gray-500 mt-1" id="tauxHoraireHint">
                        @if($ue->taux_horaire)
                            Taux spécifique à cette UE
                        @else
                            Vide = taux du vacataire ({{ number_format($ue->vacataire->hourly_rate, 0, ',', ' ') }} FCFA/h)
                        @endif
                    </p>
                </div>

                <!-- Taux vacataire (info) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Taux vacataire (BTS)
                    </label>
                    <div class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600">
                        {{ number_format($ue->vacataire->hourly_rate, 0, ',', ' ') }} FCFA/h
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Taux personnel du vacataire (non modifiable ici)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Code UE -->
                <div>
                    <label for="code_ue" class="block text-sm font-medium text-gray-700 mb-2">
                        Code UE
                    </label>
                    <input
                        type="text"
                        name="code_ue"
                        id="code_ue"
                        value="{{ old('code_ue', $ue->code_ue) }}"
                        placeholder="Ex: MTH101"
                        maxlength="50"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code_ue') border-red-500 @enderror"
                    >
                    @error('code_ue')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Volume horaire -->
                <div>
                    <label for="volume_horaire_total" class="block text-sm font-medium text-gray-700 mb-2">
                        Volume horaire (heures) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="volume_horaire_total"
                        id="volume_horaire_total"
                        value="{{ old('volume_horaire_total', $ue->volume_horaire_total) }}"
                        placeholder="Ex: 18"
                        step="0.5"
                        min="0.5"
                        max="999"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('volume_horaire_total') border-red-500 @enderror"
                        required
                        oninput="calculerMontantMax()"
                    >
                    @error('volume_horaire_total')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @if($ue->heures_effectuees > 0)
                        <p class="text-xs text-orange-600 mt-1">
                            <i class="fas fa-exclamation-triangle"></i>
                            Attention: {{ number_format($ue->heures_effectuees, 1) }}h déjà effectuées
                        </p>
                    @endif
                </div>
            </div>

            <!-- Nom de la matière -->
            <div>
                <label for="nom_matiere" class="block text-sm font-medium text-gray-700 mb-2">
                    Nom de la matière <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="nom_matiere"
                    id="nom_matiere"
                    value="{{ old('nom_matiere', $ue->nom_matiere) }}"
                    placeholder="Ex: Mathématiques"
                    maxlength="255"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nom_matiere') border-red-500 @enderror"
                    required
                >
                @error('nom_matiere')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Montant maximum calculé -->
            <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Montant maximum</p>
                        <p class="text-2xl font-bold text-purple-600" id="montantMaxValue">
                            {{ number_format($ue->montant_max, 0, ',', ' ') }} FCFA
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Volume × Taux horaire effectif</p>
                    </div>
                    <i class="fas fa-calculator text-purple-300 text-3xl"></i>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Année académique -->
                <div>
                    <label for="annee_academique" class="block text-sm font-medium text-gray-700 mb-2">
                        Année académique
                    </label>
                    <input
                        type="text"
                        name="annee_academique"
                        id="annee_academique"
                        value="{{ old('annee_academique', $ue->annee_academique) }}"
                        placeholder="Ex: 2024-2025"
                        maxlength="20"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('annee_academique') border-red-500 @enderror"
                    >
                    @error('annee_academique')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Semestre -->
                <div>
                    <label for="semestre" class="block text-sm font-medium text-gray-700 mb-2">
                        Semestre
                    </label>
                    <select
                        name="semestre"
                        id="semestre"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('semestre') border-red-500 @enderror"
                    >
                        <option value="">Aucun</option>
                        @for($i = 1; $i <= 9; $i++)
                            <option value="{{ $i }}" {{ old('semestre', $ue->semestre) == $i ? 'selected' : '' }}>Semestre {{ $i }}</option>
                        @endfor
                    </select>
                    @error('semestre')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Boutons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.vacataires.unites', $ue->vacataire_id) }}" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const tauxVacataire = {{ $ue->vacataire->hourly_rate }};
const tauxParNiveau = {
    'licence': {{ \App\Models\Setting::get('taux_horaire_licence', 5000) }},
    'master': {{ \App\Models\Setting::get('taux_horaire_master', 7500) }},
};

function onNiveauChange() {
    const niveau = document.getElementById('niveau').value.toLowerCase();
    const tauxInput = document.getElementById('taux_horaire');
    const hint = document.getElementById('tauxHoraireHint');

    // Ne pas écraser si l'utilisateur a déjà un taux personnalisé
    if (tauxInput.value && parseFloat(tauxInput.value) > 0) {
        calculerMontantMax();
        return;
    }

    if (niveau.includes('licence')) {
        tauxInput.value = tauxParNiveau.licence;
        hint.textContent = 'Taux Licence pré-rempli. Modifiable.';
    } else if (niveau.includes('master')) {
        tauxInput.value = tauxParNiveau.master;
        hint.textContent = 'Taux Master pré-rempli. Modifiable.';
    } else if (niveau.includes('bts')) {
        tauxInput.value = '';
        hint.textContent = 'BTS : taux du vacataire (' + new Intl.NumberFormat('fr-FR').format(tauxVacataire) + ' FCFA/h)';
    }

    calculerMontantMax();
}

function calculerMontantMax() {
    const tauxUe = parseFloat(document.getElementById('taux_horaire').value) || 0;
    const taux = tauxUe > 0 ? tauxUe : tauxVacataire;
    const volume = parseFloat(document.getElementById('volume_horaire_total').value) || 0;
    const montantMaxValue = document.getElementById('montantMaxValue');

    if (volume > 0 && taux > 0) {
        const montantMax = taux * volume;
        montantMaxValue.textContent = new Intl.NumberFormat('fr-FR').format(montantMax) + ' FCFA';
    }
}
</script>
@endpush
@endsection
