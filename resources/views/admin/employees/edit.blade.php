@extends('layouts.admin')

@section('title', 'Modifier Employé')
@section('page-title', 'Modifier l\'Employé')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Erreurs de validation -->
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
            <p class="font-bold">Erreurs de validation:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-lg p-8">
        <form method="POST" action="{{ route('admin.employees.update', $employee->id) }}" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')

            <!-- Informations Personnelles -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-user mr-2"></i> Informations Personnelles
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- ID Employé (lecture seule) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ID Employé</label>
                        <input
                            type="text"
                            value="{{ $employee->employee_id }}"
                            readonly
                            class="w-full px-4 py-2 border border-gray-300 bg-gray-100 rounded-lg cursor-not-allowed"
                        >
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-lock mr-1"></i>
                            L'ID employé ne peut pas être modifié
                        </p>
                    </div>

                    <!-- Photo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                        @if($employee->photo_url)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $employee->photo_url) }}" alt="Photo actuelle" class="h-20 w-20 rounded-full object-cover">
                            </div>
                        @endif
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
                            value="{{ old('first_name', $employee->first_name) }}"
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
                            value="{{ old('last_name', $employee->last_name) }}"
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
                            value="{{ old('email', $employee->email) }}"
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
                            value="{{ old('phone', $employee->phone) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror"
                        >
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Appareil lié -->
            @if($employee->device_id)
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-mobile-alt mr-2"></i> Appareil Lié
                </h3>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $employee->device_model }}</p>
                            <p class="text-xs text-gray-600">{{ $employee->device_os }}</p>
                            <p class="text-xs text-gray-500 mt-1">ID: {{ substr($employee->device_id, 0, 20) }}...</p>
                        </div>
                        <form method="POST" action="{{ route('admin.employees.reset-device', $employee->id) }}" class="inline" onsubmit="return confirm('Réinitialiser l\'appareil ? L\'employé devra se reconnecter.');">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition text-sm">
                                <i class="fas fa-redo mr-2"></i> Réinitialiser
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Authentification -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-lock mr-2"></i> Authentification
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Mot de passe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                        <input
                            type="password"
                            name="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror"
                        >
                        <p class="text-xs text-gray-500 mt-1">Laisser vide pour conserver l'actuel</p>
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirmation mot de passe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le nouveau mot de passe</label>
                        <input
                            type="password"
                            name="password_confirmation"
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

                        <!-- Input caché pour garantir l'envoi -->
                        <input type="hidden" name="employee_type" id="employee_type_hidden" value="{{ old('employee_type', $employee->employee_type) }}">

                        <select
                            id="employee_type"
                            required
                            onchange="document.getElementById('employee_type_hidden').value = this.value; toggleSalaryFields();"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('employee_type') border-red-500 @enderror"
                        >
                            <option value="">Sélectionner un type</option>
                            <option value="enseignant_titulaire" {{ old('employee_type', $employee->employee_type) == 'enseignant_titulaire' ? 'selected' : '' }}>Personnel Permanent (Enseignant Titulaire)</option>
                            <option value="semi_permanent" {{ old('employee_type', $employee->employee_type) == 'semi_permanent' ? 'selected' : '' }}>Personnel Semi-Permanent</option>
                            <option value="enseignant_vacataire" {{ old('employee_type', $employee->employee_type) == 'enseignant_vacataire' ? 'selected' : '' }}>Vacataire</option>
                            <option value="administratif" {{ old('employee_type', $employee->employee_type) == 'administratif' ? 'selected' : '' }}>Administratif</option>
                            <option value="technique" {{ old('employee_type', $employee->employee_type) == 'technique' ? 'selected' : '' }}>Technique</option>
                            <option value="direction" {{ old('employee_type', $employee->employee_type) == 'direction' ? 'selected' : '' }}>Direction</option>
                        </select>
                        @error('employee_type')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            <strong>Vacataire:</strong> Peut faire plusieurs CHECK-IN/OUT par jour dans différents campus<br>
                            <strong>Permanent/Semi-permanent:</strong> Un seul CHECK-IN/OUT par jour
                        </p>
                    </div>

                    <!-- Salaire Mensuel (Personnel Permanent/Semi-Permanent) -->
                    <div id="monthly_salary_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Salaire Mensuel (FCFA)</label>
                        <input
                            type="number"
                            name="monthly_salary"
                            id="monthly_salary"
                            step="1000"
                            min="0"
                            value="{{ old('monthly_salary', $employee->monthly_salary) }}"
                            placeholder="300000"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('monthly_salary') border-red-500 @enderror"
                        >
                        @error('monthly_salary')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Salaire de base mensuel</p>
                    </div>

                    <!-- Taux Horaire (Vacataire) -->
                    <div id="hourly_rate_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Taux Horaire (FCFA/h)</label>
                        <input
                            type="number"
                            name="hourly_rate"
                            id="hourly_rate"
                            step="100"
                            min="0"
                            value="{{ old('hourly_rate', $employee->hourly_rate) }}"
                            placeholder="2500"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('hourly_rate') border-red-500 @enderror"
                        >
                        @error('hourly_rate')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Tarif horaire pour les vacataires</p>
                    </div>

                    <!-- Volume Horaire Hebdomadaire (Semi-permanent UNIQUEMENT) -->
                    <div id="volume_horaire_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Volume Horaire Hebdomadaire (h) *</label>
                        <input
                            type="number"
                            name="volume_horaire_hebdomadaire"
                            id="volume_horaire_hebdomadaire"
                            value="{{ old('volume_horaire_hebdomadaire', $employee->volume_horaire_hebdomadaire) }}"
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

                    <!-- Jours de Travail (Semi-permanent UNIQUEMENT) -->
                    <div id="jours_travail_field" class="md:col-span-2" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jours de Travail *</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @php
                                $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                                $joursTravailEmployee = old('jours_travail', $employee->jours_travail ?? []);
                            @endphp
                            @foreach($jours as $jour)
                                <label class="inline-flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="jours_travail[]"
                                        value="{{ $jour }}"
                                        {{ in_array($jour, $joursTravailEmployee) ? 'checked' : '' }}
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
                            Sélectionnez les jours de travail (généralement 3 jours pour les semi-permanents)
                        </p>
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
                                    {{ old('is_active', $employee->is_active) ? 'checked' : '' }}
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
                                        {{ in_array($campus->id, old('campuses', $employee->campuses->pluck('id')->toArray())) ? 'checked' : '' }}
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
                                        {{ $employee->worksShift($campus->id, 'morning') ? 'checked' : '' }}
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
                                        {{ $employee->worksShift($campus->id, 'evening') ? 'checked' : '' }}
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
                <button type="submit" id="submit-btn" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleSalaryFields() {
    const employeeType = document.getElementById('employee_type').value;
    const monthlySalaryField = document.getElementById('monthly_salary_field');
    const hourlyRateField = document.getElementById('hourly_rate_field');
    const volumeHoraireField = document.getElementById('volume_horaire_field');
    const joursTravailField = document.getElementById('jours_travail_field');
    const monthlySalaryInput = document.getElementById('monthly_salary');
    const hourlyRateInput = document.getElementById('hourly_rate');
    const volumeHoraireInput = document.getElementById('volume_horaire_hebdomadaire');

    console.log('Employee type:', employeeType);

    // Masquer tous les champs et retirer required
    monthlySalaryField.style.display = 'none';
    hourlyRateField.style.display = 'none';
    volumeHoraireField.style.display = 'none';
    joursTravailField.style.display = 'none';
    monthlySalaryInput.removeAttribute('required');
    hourlyRateInput.removeAttribute('required');
    volumeHoraireInput.removeAttribute('required');

    // Afficher les champs selon le type
    if (employeeType === 'enseignant_vacataire') {
        // Vacataire : taux horaire uniquement
        hourlyRateField.style.display = 'block';
        hourlyRateInput.setAttribute('required', 'required');
        console.log('Showing hourly rate field');
    } else if (employeeType === 'semi_permanent') {
        // Semi-permanent : salaire mensuel + volume horaire + jours travail
        monthlySalaryField.style.display = 'block';
        volumeHoraireField.style.display = 'block';
        joursTravailField.style.display = 'block';
        monthlySalaryInput.setAttribute('required', 'required');
        volumeHoraireInput.setAttribute('required', 'required');
        console.log('Showing monthly salary, volume horaire and jours travail fields for semi-permanent');
    } else if (employeeType && employeeType !== '') {
        // Autres types : salaire mensuel uniquement
        monthlySalaryField.style.display = 'block';
        monthlySalaryInput.setAttribute('required', 'required');
        console.log('Showing monthly salary field');
    }
}

// Appeler la fonction au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== PAGE CHARGEE ===');
    console.log('Initializing salary fields...');
    toggleSalaryFields();
    console.log('Salary fields initialized');

    // Déboguer la soumission du formulaire
    const form = document.querySelector('form[method="POST"]');
    console.log('Form trouvé:', form ? 'OUI' : 'NON');

    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('=== FORM SOUMIS ===');
            // Ne rien bloquer, laisser soumettre
        });
    }

    // Test du bouton
    const btn = document.getElementById('submit-btn');
    console.log('Bouton trouvé:', btn ? 'OUI' : 'NON');
    if (btn) {
        btn.addEventListener('click', function(e) {
            console.log('=== BOUTON SUBMIT CLIQUE ===');
            console.log('Laissant le formulaire se soumettre normalement...');
            // NE PAS faire e.preventDefault() - laisser le bouton soumettre normalement
        });
    }

    // Gestion des plages horaires
    toggleShiftAssignment();

    // Écouter les changements sur les checkboxes des campus
    document.querySelectorAll('input[name="campuses[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', toggleShiftAssignment);
    });

    // Écouter les changements sur le type d'employé
    document.getElementById('employee_type').addEventListener('change', toggleShiftAssignment);
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
