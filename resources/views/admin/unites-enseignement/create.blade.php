@extends('layouts.admin')

@section('title', 'Attribuer une UE')
@section('page-title', 'Attribuer une Unité d\'Enseignement')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Nouvelle Unité d'Enseignement</h3>
            <p class="text-sm text-gray-600 mt-1">Attribuer une UE à un enseignant (vacataire ou semi-permanent)</p>
        </div>

        <form action="{{ route('admin.unites-enseignement.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Sélection de l'enseignant -->
            <div>
                <label for="vacataire_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Enseignant <span class="text-red-500">*</span>
                </label>
                <select
                    name="vacataire_id"
                    id="vacataire_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('vacataire_id') border-red-500 @enderror"
                    required
                    onchange="updateEnseignantInfo()"
                >
                    <option value="">Sélectionner un enseignant</option>
                    @foreach($vacataires as $vac)
                        <option
                            value="{{ $vac->id }}"
                            data-taux="{{ $vac->hourly_rate ?? 0 }}"
                            data-type="{{ $vac->employee_type }}"
                            {{ old('vacataire_id', $vacataireId) == $vac->id ? 'selected' : '' }}
                        >
                            {{ $vac->full_name }} -
                            @if($vac->employee_type === 'enseignant_vacataire')
                                <span class="text-blue-600">Vacataire</span>
                            @else
                                <span class="text-green-600">Semi-permanent</span>
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('vacataire_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror

                <!-- Info pour vacataire -->
                <div id="tauxInfo" class="hidden mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-money-bill-wave mr-1"></i>
                        <strong>Vacataire</strong> - Taux horaire: <strong id="tauxValue">0</strong> FCFA/h
                        <br><small>Les heures seront payées selon ce taux</small>
                    </p>
                </div>

                <!-- Info pour semi-permanent -->
                <div id="semiPermanentInfo" class="hidden mt-2 p-3 bg-green-50 rounded-lg border border-green-200">
                    <p class="text-sm text-green-800">
                        <i class="fas fa-user-check mr-1"></i>
                        <strong>Semi-permanent</strong> - Salaire fixe mensuel
                        <br><small>Les heures sont suivies pour le monitoring uniquement (pas de paiement horaire)</small>
                    </p>
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
                        value="{{ old('code_ue') }}"
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
                        value="{{ old('volume_horaire_total') }}"
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
                    value="{{ old('nom_matiere') }}"
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
            <div id="montantMaxInfo" class="hidden p-4 bg-purple-50 rounded-lg border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Montant maximum</p>
                        <p class="text-2xl font-bold text-purple-600" id="montantMaxValue">0 FCFA</p>
                        <p class="text-xs text-gray-500 mt-1">Volume × Taux horaire du vacataire</p>
                    </div>
                    <i class="fas fa-calculator text-purple-300 text-3xl"></i>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Niveau -->
                <div>
                    <label for="niveau" class="block text-sm font-medium text-gray-700 mb-2">
                        Niveau
                    </label>
                    <select
                        name="niveau"
                        id="niveau"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('niveau') border-red-500 @enderror"
                        onchange="onNiveauChange()"
                    >
                        <option value="">Sélectionner un niveau</option>
                        <option value="BTS 1" {{ old('niveau') == 'BTS 1' ? 'selected' : '' }}>BTS 1</option>
                        <option value="BTS 2" {{ old('niveau') == 'BTS 2' ? 'selected' : '' }}>BTS 2</option>
                        <option value="Licence 1" {{ old('niveau') == 'Licence 1' ? 'selected' : '' }}>Licence 1</option>
                        <option value="Licence 2" {{ old('niveau') == 'Licence 2' ? 'selected' : '' }}>Licence 2</option>
                        <option value="Licence 3" {{ old('niveau') == 'Licence 3' ? 'selected' : '' }}>Licence 3</option>
                        <option value="Master 1" {{ old('niveau') == 'Master 1' ? 'selected' : '' }}>Master 1</option>
                        <option value="Master 2" {{ old('niveau') == 'Master 2' ? 'selected' : '' }}>Master 2</option>
                    </select>
                    @error('niveau')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Taux horaire de la UE -->
                <div>
                    <label for="taux_horaire" class="block text-sm font-medium text-gray-700 mb-2">
                        Taux horaire UE (FCFA/h)
                    </label>
                    <input
                        type="number"
                        name="taux_horaire"
                        id="taux_horaire"
                        value="{{ old('taux_horaire') }}"
                        placeholder="Auto selon niveau"
                        step="100"
                        min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('taux_horaire') border-red-500 @enderror"
                        oninput="calculerMontantMax()"
                    >
                    @error('taux_horaire')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1" id="tauxHoraireHint">
                        Licence/Master : taux spécifique. BTS : taux du vacataire.
                    </p>
                </div>

                <!-- Année académique -->
                <div>
                    <label for="annee_academique" class="block text-sm font-medium text-gray-700 mb-2">
                        Année académique
                    </label>
                    <input
                        type="text"
                        name="annee_academique"
                        id="annee_academique"
                        value="{{ old('annee_academique', '2024-2025') }}"
                        placeholder="Ex: 2024-2025"
                        maxlength="20"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('annee_academique') border-red-500 @enderror"
                    >
                    @error('annee_academique')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                            <option value="{{ $i }}" {{ old('semestre') == $i ? 'selected' : '' }}>Semestre {{ $i }}</option>
                        @endfor
                    </select>
                    @error('semestre')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Activation immédiate -->
            <div class="border border-gray-200 rounded-lg p-4">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        name="activer_immediatement"
                        id="activer_immediatement"
                        value="1"
                        {{ old('activer_immediatement') ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                    >
                    <span class="ml-3">
                        <span class="text-sm font-medium text-gray-900">Activer immédiatement</span>
                        <p class="text-xs text-gray-600 mt-1">
                            Si coché, l'UE sera activée et le vacataire pourra commencer à pointer pour cette matière.
                            Sinon, vous devrez l'activer manuellement plus tard.
                        </p>
                    </span>
                </label>
            </div>

            <!-- Boutons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.vacataires.index') }}" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                    <i class="fas fa-save mr-2"></i> Attribuer l'UE
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Taux configurés par l'admin dans les paramètres
const tauxParNiveau = {
    'licence': {{ \App\Models\Setting::get('taux_horaire_licence', 5000) }},
    'master': {{ \App\Models\Setting::get('taux_horaire_master', 7500) }},
};

function updateEnseignantInfo() {
    const select = document.getElementById('vacataire_id');
    const selectedOption = select.options[select.selectedIndex];
    const taux = selectedOption.getAttribute('data-taux');
    const type = selectedOption.getAttribute('data-type');

    const tauxInfo = document.getElementById('tauxInfo');
    const semiPermanentInfo = document.getElementById('semiPermanentInfo');
    const tauxValue = document.getElementById('tauxValue');

    tauxInfo.classList.add('hidden');
    semiPermanentInfo.classList.add('hidden');

    if (type === 'enseignant_vacataire' && taux && taux > 0) {
        tauxValue.textContent = new Intl.NumberFormat('fr-FR').format(taux);
        tauxInfo.classList.remove('hidden');
        onNiveauChange();
    } else if (type === 'semi_permanent') {
        semiPermanentInfo.classList.remove('hidden');
        document.getElementById('montantMaxInfo')?.classList.add('hidden');
    }
}

function onNiveauChange() {
    const niveau = document.getElementById('niveau').value.toLowerCase();
    const tauxInput = document.getElementById('taux_horaire');
    const hint = document.getElementById('tauxHoraireHint');

    if (niveau.includes('licence')) {
        tauxInput.value = tauxParNiveau.licence;
        hint.textContent = 'Taux Licence pré-rempli (' + new Intl.NumberFormat('fr-FR').format(tauxParNiveau.licence) + ' FCFA/h). Modifiable.';
        hint.className = 'text-xs text-blue-600 mt-1';
    } else if (niveau.includes('master')) {
        tauxInput.value = tauxParNiveau.master;
        hint.textContent = 'Taux Master pré-rempli (' + new Intl.NumberFormat('fr-FR').format(tauxParNiveau.master) + ' FCFA/h). Modifiable.';
        hint.className = 'text-xs text-purple-600 mt-1';
    } else if (niveau.includes('bts')) {
        tauxInput.value = '';
        hint.textContent = 'BTS : le taux horaire du vacataire sera utilisé.';
        hint.className = 'text-xs text-green-600 mt-1';
    } else {
        tauxInput.value = '';
        hint.textContent = 'Licence/Master : taux spécifique. BTS : taux du vacataire.';
        hint.className = 'text-xs text-gray-500 mt-1';
    }

    calculerMontantMax();
}

function calculerMontantMax() {
    const select = document.getElementById('vacataire_id');
    const selectedOption = select.options[select.selectedIndex];
    const tauxVacataire = parseFloat(selectedOption.getAttribute('data-taux')) || 0;

    const tauxUeInput = document.getElementById('taux_horaire');
    const tauxUe = parseFloat(tauxUeInput.value) || 0;

    // Utiliser le taux UE si défini, sinon le taux du vacataire
    const taux = tauxUe > 0 ? tauxUe : tauxVacataire;

    const volumeInput = document.getElementById('volume_horaire_total');
    const volume = parseFloat(volumeInput.value) || 0;

    const montantMaxInfo = document.getElementById('montantMaxInfo');
    const montantMaxValue = document.getElementById('montantMaxValue');

    if (taux > 0 && volume > 0) {
        const montantMax = taux * volume;
        montantMaxValue.textContent = new Intl.NumberFormat('fr-FR').format(montantMax) + ' FCFA';
        montantMaxInfo.classList.remove('hidden');
    } else {
        montantMaxInfo.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const vacataireSelect = document.getElementById('vacataire_id');
    if (vacataireSelect.value) {
        updateEnseignantInfo();
    }
});
</script>
@endpush
@endsection
