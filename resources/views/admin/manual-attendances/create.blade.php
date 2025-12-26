@extends('layouts.admin')

@section('title', 'Nouvelle Présence')
@section('page-title', 'Enregistrer une Présence Manuelle')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('admin.manual-attendances.index') }}" class="text-gray-700 hover:text-blue-600">
                    <i class="fas fa-clipboard-list mr-2"></i>
                    Présences Manuelles
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Nouvelle présence</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Formulaire -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.manual-attendances.store') }}" id="attendanceForm">
            @csrf

            <!-- Sélection Employé -->
            <div class="mb-6">
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Employé <span class="text-red-500">*</span>
                </label>
                <select name="user_id" id="user_id" required class="w-full border-gray-300 rounded-lg @error('user_id') border-red-500 @enderror">
                    <option value="">-- Sélectionner un employé --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id', $selectedUser?->id) == $user->id ? 'selected' : '' }}>
                            {{ $user->full_name }} ({{ ucfirst(str_replace('_', ' ', $user->employee_type)) }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info employé -->
            <div id="employeeInfo" class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded hidden">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span id="employeeTypeText"></span>
                </p>
            </div>

            <!-- Date -->
            <div class="mb-6">
                <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                    Date <span class="text-red-500">*</span>
                </label>
                <input type="date" name="date" id="date" required value="{{ old('date', now()->format('Y-m-d')) }}" class="w-full border-gray-300 rounded-lg @error('date') border-red-500 @enderror">
                @error('date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Campus -->
            <div class="mb-6">
                <label for="campus_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Campus <span class="text-red-500">*</span>
                </label>
                <select name="campus_id" id="campus_id" required class="w-full border-gray-300 rounded-lg @error('campus_id') border-red-500 @enderror">
                    <option value="">-- Sélectionner un campus --</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }} ({{ $campus->code }})
                        </option>
                    @endforeach
                </select>
                @error('campus_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Session Type -->
            <div class="mb-6">
                <label for="session_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Type de session <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 @error('session_type') border-red-500 @enderror">
                        <input type="radio" name="session_type" value="jour" required {{ old('session_type', 'jour') == 'jour' ? 'checked' : '' }} class="mr-3">
                        <div>
                            <i class="fas fa-sun text-yellow-500 text-xl mr-2"></i>
                            <span class="font-medium">Jour</span>
                            <p class="text-xs text-gray-500">8h - 17h</p>
                        </div>
                    </label>
                    <label class="relative flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:border-blue-500">
                        <input type="radio" name="session_type" value="soir" {{ old('session_type') == 'soir' ? 'checked' : '' }} class="mr-3">
                        <div>
                            <i class="fas fa-moon text-indigo-500 text-xl mr-2"></i>
                            <span class="font-medium">Soir</span>
                            <p class="text-xs text-gray-500">17h - 21h</p>
                        </div>
                    </label>
                </div>
                @error('session_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Horaires -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="check_in_time" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sign-in-alt text-green-500 mr-1"></i>
                        Heure d'arrivée <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="check_in_time" id="check_in_time" required value="{{ old('check_in_time') }}" class="w-full border-gray-300 rounded-lg @error('check_in_time') border-red-500 @enderror">
                    @error('check_in_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="check_out_time" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sign-out-alt text-red-500 mr-1"></i>
                        Heure de départ <span class="text-red-500">*</span>
                    </label>
                    <input type="time" name="check_out_time" id="check_out_time" required value="{{ old('check_out_time') }}" class="w-full border-gray-300 rounded-lg @error('check_out_time') border-red-500 @enderror">
                    @error('check_out_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Unité d'Enseignement -->
            <div id="ueContainer" class="mb-6 {{ $selectedUser ? '' : 'hidden' }}">
                <label for="unite_enseignement_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Unité d'Enseignement <span id="ueRequired" class="text-red-500 hidden">*</span>
                </label>
                <select name="unite_enseignement_id" id="unite_enseignement_id" class="w-full border-gray-300 rounded-lg @error('unite_enseignement_id') border-red-500 @enderror">
                    <option value="">-- Aucune UE (personnel non-enseignant) --</option>
                    @foreach($unitesEnseignement as $ue)
                        <option value="{{ $ue->id }}" {{ old('unite_enseignement_id') == $ue->id ? 'selected' : '' }}>
                            {{ $ue->code_ue }} - {{ $ue->nom_matiere }}
                        </option>
                    @endforeach
                </select>
                @error('unite_enseignement_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Pour les enseignants, la sélection d'une UE est obligatoire
                </p>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Notes (optionnel)
                </label>
                <textarea name="notes" id="notes" rows="3" class="w-full border-gray-300 rounded-lg @error('notes') border-red-500 @enderror" placeholder="Remarques ou observations...">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="{{ route('admin.manual-attendances.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times mr-2"></i>Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById('user_id');
    const ueContainer = document.getElementById('ueContainer');
    const ueSelect = document.getElementById('unite_enseignement_id');
    const ueRequired = document.getElementById('ueRequired');
    const employeeInfo = document.getElementById('employeeInfo');
    const employeeTypeText = document.getElementById('employeeTypeText');

    userSelect.addEventListener('change', function() {
        const userId = this.value;

        if (!userId) {
            ueContainer.classList.add('hidden');
            employeeInfo.classList.add('hidden');
            return;
        }

        // Récupérer les UEs de l'employé via AJAX
        fetch(`{{ route('admin.manual-attendances.get-user-ues') }}?user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                // Vider le select
                ueSelect.innerHTML = '<option value="">-- Aucune UE (personnel non-enseignant) --</option>';

                // Ajouter les UEs
                data.ues.forEach(ue => {
                    const option = document.createElement('option');
                    option.value = ue.id;
                    option.textContent = ue.display;
                    ueSelect.appendChild(option);
                });

                // Afficher/masquer le container
                ueContainer.classList.remove('hidden');

                // Si c'est un enseignant, rendre l'UE obligatoire
                if (data.is_teacher) {
                    ueSelect.required = true;
                    ueRequired.classList.remove('hidden');
                    employeeTypeText.textContent = 'Cet employé est enseignant. La sélection d\'une unité d\'enseignement est obligatoire.';
                    employeeInfo.classList.remove('hidden');
                } else {
                    ueSelect.required = false;
                    ueRequired.classList.add('hidden');
                    employeeTypeText.textContent = 'Cet employé est du personnel non-enseignant. L\'unité d\'enseignement n\'est pas requise.';
                    employeeInfo.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    });

    // Calcul automatique de la durée
    const checkInTime = document.getElementById('check_in_time');
    const checkOutTime = document.getElementById('check_out_time');

    function calculateDuration() {
        if (checkInTime.value && checkOutTime.value) {
            const start = new Date('2000-01-01 ' + checkInTime.value);
            const end = new Date('2000-01-01 ' + checkOutTime.value);
            const diff = (end - start) / 1000 / 60; // minutes

            if (diff > 0) {
                const hours = Math.floor(diff / 60);
                const minutes = diff % 60;
                console.log(`Durée: ${hours}h ${minutes}min`);
            }
        }
    }

    checkInTime.addEventListener('change', calculateDuration);
    checkOutTime.addEventListener('change', calculateDuration);
});
</script>
@endpush
@endsection
