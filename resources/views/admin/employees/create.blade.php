@extends('layouts.admin')

@section('title', 'Nouvel Employé')
@section('page-title', 'Créer un Employé')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <form method="POST" action="{{ route('admin.employees.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Informations Personnelles -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-user mr-2"></i> Informations Personnelles
                </h3>

                <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700">
                    <p class="text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>L'ID employé sera généré automatiquement</strong> au format: EMP-ANNÉE-XXXX (ex: EMP-2025-0001)
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Photo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                        <input
                            type="file"
                            name="photo"
                            accept="image/jpeg,image/png,image/jpg"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('photo') border-red-500 @enderror"
                        >
                        @error('photo')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prénom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                        <input
                            type="text"
                            name="first_name"
                            value="{{ old('first_name') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('first_name') border-red-500 @enderror"
                        >
                        @error('first_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                        <input
                            type="text"
                            name="last_name"
                            value="{{ old('last_name') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('last_name') border-red-500 @enderror"
                        >
                        @error('last_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                        >
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Téléphone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input
                            type="tel"
                            name="phone"
                            value="{{ old('phone') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror"
                        >
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Authentification -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-lock mr-2"></i> Authentification
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Mot de passe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe *</label>
                        <input
                            type="password"
                            name="password"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror"
                        >
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirmation mot de passe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe *</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                </div>
            </div>

            <!-- Affectation -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-briefcase mr-2"></i> Affectation
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Type d'employé -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type d'employé *</label>
                        <select
                            name="employee_type"
                            id="employee_type"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('employee_type') border-red-500 @enderror"
                        >
                            <option value="">Sélectionner un type</option>
                            <option value="enseignant_titulaire" {{ old('employee_type') == 'enseignant_titulaire' ? 'selected' : '' }}>Personnel Permanent (Enseignant Titulaire)</option>
                            <option value="semi_permanent" {{ old('employee_type') == 'semi_permanent' ? 'selected' : '' }}>Personnel Semi-Permanent</option>
                            <option value="enseignant_vacataire" {{ old('employee_type') == 'enseignant_vacataire' ? 'selected' : '' }}>Vacataire</option>
                            <option value="administratif" {{ old('employee_type') == 'administratif' ? 'selected' : '' }}>Administratif</option>
                            <option value="technique" {{ old('employee_type') == 'technique' ? 'selected' : '' }}>Technique</option>
                            <option value="direction" {{ old('employee_type') == 'direction' ? 'selected' : '' }}>Direction</option>
                        </select>
                        @error('employee_type')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            <strong>Vacataire:</strong> Peut faire plusieurs CHECK-IN/OUT par jour dans différents campus<br>
                            <strong>Permanent/Semi-permanent:</strong> Un seul CHECK-IN/OUT par jour
                        </p>
                    </div>

                    <!-- Salaire Mensuel (Permanent/Semi-permanent) -->
                    <div id="monthly_salary_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salaire Mensuel (FCFA) *</label>
                        <input
                            type="number"
                            name="monthly_salary"
                            id="monthly_salary"
                            value="{{ old('monthly_salary') }}"
                            min="0"
                            step="1000"
                            placeholder="300000"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('monthly_salary') border-red-500 @enderror"
                        >
                        @error('monthly_salary')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            Salaire mensuel fixe pour le personnel permanent et semi-permanent
                        </p>
                    </div>

                    <!-- Taux Horaire (Vacataire) -->
                    <div id="hourly_rate_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taux Horaire (FCFA/heure) *</label>
                        <input
                            type="number"
                            name="hourly_rate"
                            id="hourly_rate"
                            value="{{ old('hourly_rate') }}"
                            min="0"
                            step="100"
                            placeholder="2500"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('hourly_rate') border-red-500 @enderror"
                        >
                        @error('hourly_rate')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            Montant payé par heure de travail effectif
                        </p>
                    </div>

                    <!-- Volume Horaire Hebdomadaire (Semi-permanent) -->
                    <div id="volume_horaire_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Volume Horaire Hebdomadaire (h) *</label>
                        <input
                            type="number"
                            name="volume_horaire_hebdomadaire"
                            id="volume_horaire_hebdomadaire"
                            value="{{ old('volume_horaire_hebdomadaire') }}"
                            min="0"
                            max="168"
                            step="0.5"
                            placeholder="20"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('volume_horaire_hebdomadaire') border-red-500 @enderror"
                        >
                        @error('volume_horaire_hebdomadaire')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            Nombre d'heures contractuelles par semaine (ex: 20h, 25h). Maximum: 168h (heures dans une semaine)
                        </p>
                    </div>

                    <!-- Jours de Travail (Semi-permanent) -->
                    <div id="jours_travail_field" class="md:col-span-2" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jours de Travail *</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @php
                                $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                                $oldJours = old('jours_travail', []);
                            @endphp
                            @foreach($jours as $jour)
                                <label class="inline-flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="jours_travail[]"
                                        value="{{ $jour }}"
                                        {{ in_array($jour, $oldJours) ? 'checked' : '' }}
                                        class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    >
                                    <span class="ml-2 text-gray-700 capitalize">{{ ucfirst($jour) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('jours_travail')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            Sélectionnez les jours de travail de la semaine (généralement 3 jours pour les semi-permanents)
                        </p>
                    </div>

                    <!-- Horaires Personnalisés (Pour agents, gardiens, etc.) -->
                    <div id="custom_hours_section" class="md:col-span-2" style="display: none;">
                        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-500 mb-4">
                            <p class="text-sm text-yellow-700">
                                <i class="fas fa-clock mr-2"></i>
                                <strong>Horaires Personnalisés</strong> - Pour les agents avec des horaires spécifiques (gardiens de nuit, personnel d'entretien, etc.)
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Heure de Début -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Heure de Début</label>
                                <input
                                    type="time"
                                    name="custom_start_time"
                                    id="custom_start_time"
                                    value="{{ old('custom_start_time') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('custom_start_time') border-red-500 @enderror"
                                >
                                @error('custom_start_time')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">
                                    Ex: 22:00 pour gardien de nuit
                                </p>
                            </div>

                            <!-- Heure de Fin -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Heure de Fin</label>
                                <input
                                    type="time"
                                    name="custom_end_time"
                                    id="custom_end_time"
                                    value="{{ old('custom_end_time') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('custom_end_time') border-red-500 @enderror"
                                >
                                @error('custom_end_time')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">
                                    Ex: 06:00 pour gardien de nuit
                                </p>
                            </div>

                            <!-- Tolérance de Retard -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tolérance Retard (min)</label>
                                <input
                                    type="number"
                                    name="custom_late_tolerance"
                                    id="custom_late_tolerance"
                                    value="{{ old('custom_late_tolerance', 15) }}"
                                    min="0"
                                    max="60"
                                    placeholder="15"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('custom_late_tolerance') border-red-500 @enderror"
                                >
                                @error('custom_late_tolerance')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">
                                    Minutes de tolérance avant retard
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-700">
                                <i class="fas fa-info-circle mr-1"></i>
                                Si défini, ces horaires <strong>prennent priorité</strong> sur les horaires du campus pour la détection de retard et le calcul de salaire.
                            </p>
                        </div>
                    </div>

                    <!-- Statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                        <div class="flex items-center h-full pt-8">
                            <label class="inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}
                                    class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                >
                                <span class="ml-2 text-gray-700">Compte actif</span>
                            </label>
                        </div>
                    </div>

                    <!-- Campus -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campus assignés</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach($campuses as $campus)
                                <label class="inline-flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="campuses[]"
                                        value="{{ $campus->id }}"
                                        {{ in_array($campus->id, old('campuses', [])) ? 'checked' : '' }}
                                        class="form-checkbox h-4 w-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    >
                                    <span class="ml-2 text-sm text-gray-700">{{ $campus->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('campuses')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Plages Horaires (Matin/Soir) - Pour permanents enseignants -->
            <div class="mb-8" id="shift_assignment_section" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-clock text-indigo-600 mr-2"></i> Plages Horaires (Matin/Soir)
                </h3>

                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-indigo-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Pour les permanents enseignants:</strong> Choisissez les plages horaires pour chaque campus.
                        Un employé peut travailler le matin, le soir, ou les deux.
                    </p>
                </div>

                <div class="space-y-4" id="shifts_container">
                    @foreach($campuses as $campus)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 campus-shift-item" data-campus-id="{{ $campus->id }}" style="display: none;">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-800">
                                    <i class="fas fa-map-marker-alt text-gray-500 mr-2"></i>
                                    {{ $campus->name }}
                                </h4>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Matin -->
                                <label class="flex items-center p-3 border-2 border-blue-200 rounded-lg cursor-pointer hover:bg-blue-50 transition">
                                    <input
                                        type="checkbox"
                                        name="shifts[{{ $campus->id }}][morning]"
                                        value="1"
                                        class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    >
                                    <span class="ml-3">
                                        <i class="fas fa-sun text-blue-600 mr-2"></i>
                                        <strong>Matin</strong>
                                        <span class="block text-xs text-gray-600 mt-1">
                                            {{ \App\Models\Setting::get('morning_start_time', '08:15') }} -
                                            {{ \App\Models\Setting::get('morning_end_time', '17:00') }}
                                        </span>
                                    </span>
                                </label>

                                <!-- Soir -->
                                <label class="flex items-center p-3 border-2 border-orange-200 rounded-lg cursor-pointer hover:bg-orange-50 transition">
                                    <input
                                        type="checkbox"
                                        name="shifts[{{ $campus->id }}][evening]"
                                        value="1"
                                        class="form-checkbox h-5 w-5 text-orange-600 rounded focus:ring-2 focus:ring-orange-500"
                                    >
                                    <span class="ml-3">
                                        <i class="fas fa-moon text-orange-600 mr-2"></i>
                                        <strong>Soir</strong>
                                        <span class="block text-xs text-gray-600 mt-1">
                                            {{ \App\Models\Setting::get('evening_start_time', '17:30') }} -
                                            {{ \App\Models\Setting::get('evening_end_time', '21:00') }}
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Boutons -->
            <div class="flex justify-end gap-4 pt-6 border-t">
                <a href="{{ route('admin.employees.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i> Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i> Créer l'employé
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeTypeSelect = document.getElementById('employee_type');
    const monthlySalaryField = document.getElementById('monthly_salary_field');
    const hourlyRateField = document.getElementById('hourly_rate_field');
    const volumeHoraireField = document.getElementById('volume_horaire_field');
    const joursTravailField = document.getElementById('jours_travail_field');
    const customHoursSection = document.getElementById('custom_hours_section');
    const monthlySalaryInput = document.getElementById('monthly_salary');
    const hourlyRateInput = document.getElementById('hourly_rate');
    const volumeHoraireInput = document.getElementById('volume_horaire_hebdomadaire');

    function toggleSalaryFields() {
        const employeeType = employeeTypeSelect.value;

        // Cacher tous les champs d'abord
        monthlySalaryField.style.display = 'none';
        hourlyRateField.style.display = 'none';
        volumeHoraireField.style.display = 'none';
        joursTravailField.style.display = 'none';
        customHoursSection.style.display = 'none';
        monthlySalaryInput.removeAttribute('required');
        hourlyRateInput.removeAttribute('required');
        volumeHoraireInput.removeAttribute('required');

        // Afficher le champ approprié selon le type
        if (employeeType === 'enseignant_vacataire') {
            // Vacataire : taux horaire
            hourlyRateField.style.display = 'block';
            hourlyRateInput.setAttribute('required', 'required');
        } else if (employeeType === 'semi_permanent') {
            // Semi-permanent : salaire mensuel + volume horaire + jours de travail
            monthlySalaryField.style.display = 'block';
            volumeHoraireField.style.display = 'block';
            joursTravailField.style.display = 'block';
            monthlySalaryInput.setAttribute('required', 'required');
            volumeHoraireInput.setAttribute('required', 'required');
        } else if (employeeType === 'technique' || employeeType === 'administratif') {
            // Technique ou Administratif : salaire mensuel + horaires personnalisés (optionnels)
            monthlySalaryField.style.display = 'block';
            customHoursSection.style.display = 'block';
            monthlySalaryInput.setAttribute('required', 'required');
        } else if (employeeType && employeeType !== '') {
            // Autres types : salaire mensuel
            monthlySalaryField.style.display = 'block';
            monthlySalaryInput.setAttribute('required', 'required');
        }
    }

    // Écouter les changements sur le select
    employeeTypeSelect.addEventListener('change', function() {
        toggleSalaryFields();
        toggleShiftAssignment();
    });

    // Initialiser au chargement de la page
    toggleSalaryFields();
    toggleShiftAssignment();

    // Écouter les changements sur les checkboxes des campus
    document.querySelectorAll('input[name="campuses[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', toggleShiftAssignment);
    });
});

function toggleShiftAssignment() {
    const employeeType = document.getElementById('employee_type').value;
    const shiftSection = document.getElementById('shift_assignment_section');
    const selectedCampuses = Array.from(document.querySelectorAll('input[name="campuses[]"]:checked')).map(cb => cb.value);

    // Afficher la section uniquement pour enseignant_titulaire (permanent enseignant)
    if (employeeType === 'enseignant_titulaire' && selectedCampuses.length > 0) {
        shiftSection.style.display = 'block';

        // Afficher/masquer les campus selon leur sélection
        document.querySelectorAll('.campus-shift-item').forEach(item => {
            const campusId = item.getAttribute('data-campus-id');
            if (selectedCampuses.includes(campusId)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    } else {
        shiftSection.style.display = 'none';
    }
}
</script>
@endpush

@endsection
