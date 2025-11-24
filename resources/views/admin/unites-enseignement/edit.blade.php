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

            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Taux horaire du vacataire: <strong>{{ number_format($ue->vacataire->hourly_rate, 0, ',', ' ') }} FCFA/h</strong>
                </p>
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
                        <p class="text-xs text-gray-500 mt-1">Volume × Taux horaire du vacataire</p>
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
                        <option value="1" {{ old('semestre', $ue->semestre) == 1 ? 'selected' : '' }}>Semestre 1</option>
                        <option value="2" {{ old('semestre', $ue->semestre) == 2 ? 'selected' : '' }}>Semestre 2</option>
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
const tauxHoraire = {{ $ue->vacataire->hourly_rate }};

function calculerMontantMax() {
    const volumeInput = document.getElementById('volume_horaire_total');
    const volume = parseFloat(volumeInput.value) || 0;
    const montantMaxValue = document.getElementById('montantMaxValue');

    if (volume > 0) {
        const montantMax = tauxHoraire * volume;
        montantMaxValue.textContent = new Intl.NumberFormat('fr-FR').format(montantMax) + ' FCFA';
    }
}
</script>
@endpush
@endsection
