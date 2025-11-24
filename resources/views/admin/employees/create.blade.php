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
    const monthlySalaryInput = document.getElementById('monthly_salary');
    const hourlyRateInput = document.getElementById('hourly_rate');

    function toggleSalaryFields() {
        const employeeType = employeeTypeSelect.value;

        // Cacher tous les champs d'abord
        monthlySalaryField.style.display = 'none';
        hourlyRateField.style.display = 'none';
        monthlySalaryInput.removeAttribute('required');
        hourlyRateInput.removeAttribute('required');

        // Afficher le champ approprié selon le type
        if (employeeType === 'enseignant_vacataire') {
            // Vacataire : taux horaire
            hourlyRateField.style.display = 'block';
            hourlyRateInput.setAttribute('required', 'required');
        } else if (employeeType && employeeType !== '') {
            // Autres types : salaire mensuel
            monthlySalaryField.style.display = 'block';
            monthlySalaryInput.setAttribute('required', 'required');
        }
    }

    // Écouter les changements sur le select
    employeeTypeSelect.addEventListener('change', toggleSalaryFields);

    // Initialiser au chargement de la page
    toggleSalaryFields();
});
</script>
@endpush

@endsection
