@extends('layouts.admin')

@section('title', 'Congé en masse')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">

    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.leaves.index') }}" class="text-gray-500 hover:text-gray-700 mr-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Assigner un congé en masse</h1>
            <p class="text-sm text-gray-500 mt-0.5">Définissez la période et sélectionnez les employés concernés</p>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.leaves.bulk-assign.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Colonne gauche : paramètres du congé -->
            <div class="space-y-5">
                <div class="bg-white rounded-lg shadow p-6 space-y-5">
                    <h2 class="font-semibold text-gray-700 border-b pb-2">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i> Période de congé
                    </h2>

                    <!-- Type de congé -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Type de congé <span class="text-red-500">*</span>
                        </label>
                        <select name="type" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">— Sélectionner —</option>
                            <option value="annual" {{ old('type') == 'annual' ? 'selected' : '' }}>Congé annuel</option>
                            <option value="sick" {{ old('type') == 'sick' ? 'selected' : '' }}>Congé maladie</option>
                            <option value="maternity" {{ old('type') == 'maternity' ? 'selected' : '' }}>Congé maternité</option>
                            <option value="paternity" {{ old('type') == 'paternity' ? 'selected' : '' }}>Congé paternité</option>
                            <option value="unpaid" {{ old('type') == 'unpaid' ? 'selected' : '' }}>Congé sans solde</option>
                            <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>

                    <!-- Date début -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Date de début <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date"
                               value="{{ old('start_date') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Date fin -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Date de fin <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="end_date"
                               value="{{ old('end_date') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Durée calculée -->
                    <div id="durationBox" class="hidden bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        Durée : <strong id="durationText"></strong>
                    </div>

                    <!-- Motif -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Motif (optionnel)</label>
                        <textarea name="reason" rows="2"
                                  placeholder="Ex: Fermeture annuelle, congés de fin d'année..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('reason') }}</textarea>
                    </div>

                    <!-- Info biométrie -->
                    <div class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-3 text-sm text-orange-700">
                        <i class="fas fa-fingerprint mr-1"></i>
                        La biométrie bloquera automatiquement le pointage des employés sélectionnés durant cette période.
                    </div>
                </div>
            </div>

            <!-- Colonne droite : sélection des employés -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center border-b pb-2 mb-4">
                    <h2 class="font-semibold text-gray-700">
                        <i class="fas fa-users text-green-500 mr-2"></i>
                        Employés concernés
                    </h2>
                    <span id="selectedCount" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-semibold">0 sélectionné(s)</span>
                </div>

                <!-- Recherche + filtre type -->
                <div class="space-y-2 mb-3">
                    <input type="text" id="searchEmployee" placeholder="Rechercher par nom ou matricule..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <select id="filterType"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous les types</option>
                        <option value="administratif">Administratif</option>
                        <option value="enseignant_titulaire">Permanent</option>
                        <option value="semi_permanent">Semi-permanent</option>
                        <option value="enseignant_vacataire">Vacataire</option>
                    </select>
                </div>

                <!-- Tout sélectionner / désélectionner -->
                <div class="flex gap-2 mb-3">
                    <button type="button" onclick="selectAll()"
                            class="flex-1 text-xs px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 rounded-lg transition">
                        <i class="fas fa-check-square mr-1"></i> Tout sélectionner
                    </button>
                    <button type="button" onclick="deselectAll()"
                            class="flex-1 text-xs px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 border border-gray-200 rounded-lg transition">
                        <i class="fas fa-square mr-1"></i> Tout désélectionner
                    </button>
                </div>

                <!-- Liste des employés -->
                <div id="employeeList" class="space-y-1 max-h-96 overflow-y-auto border border-gray-100 rounded-lg p-2">
                    @foreach($users as $user)
                    <label class="employee-row flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 cursor-pointer transition"
                           data-name="{{ strtolower($user->full_name) }}"
                           data-id="{{ strtolower($user->employee_id ?? '') }}"
                           data-type="{{ $user->employee_type }}">
                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                               class="employee-cb w-4 h-4 text-blue-600 border-gray-300 rounded"
                               {{ in_array($user->id, old('user_ids', [])) ? 'checked' : '' }}>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $user->full_name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $user->employee_id ?? '—' }} ·
                                {{ ucfirst(str_replace('_', ' ', $user->employee_type)) }}
                            </p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Boutons -->
        <div class="flex justify-between items-center mt-6">
            <a href="{{ route('admin.leaves.index') }}"
               class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Annuler
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-semibold">
                <i class="fas fa-check mr-2"></i> Valider le congé
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
const startInput = document.getElementById('start_date');
const endInput   = document.getElementById('end_date');
const durationBox  = document.getElementById('durationBox');
const durationText = document.getElementById('durationText');
const selectedCount = document.getElementById('selectedCount');

function updateDuration() {
    const s = new Date(startInput.value);
    const e = new Date(endInput.value);
    if (startInput.value && endInput.value && e >= s) {
        const days = Math.round((e - s) / 86400000) + 1;
        durationText.textContent = days + ' jour(s)';
        durationBox.classList.remove('hidden');
        // Forcer end >= start
        endInput.min = startInput.value;
    } else {
        durationBox.classList.add('hidden');
    }
}

startInput.addEventListener('change', updateDuration);
endInput.addEventListener('change', updateDuration);

// Compteur de sélection
function updateCount() {
    const checked = document.querySelectorAll('.employee-cb:checked').length;
    selectedCount.textContent = checked + ' sélectionné(s)';
    selectedCount.className = checked > 0
        ? 'text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-semibold'
        : 'text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-semibold';
}

document.querySelectorAll('.employee-cb').forEach(cb => cb.addEventListener('change', updateCount));

// Recherche + filtre
function filterEmployees() {
    const q    = document.getElementById('searchEmployee').value.toLowerCase();
    const type = document.getElementById('filterType').value;
    document.querySelectorAll('.employee-row').forEach(row => {
        const matchName = !q || row.dataset.name.includes(q) || row.dataset.id.includes(q);
        const matchType = !type || row.dataset.type === type;
        row.style.display = (matchName && matchType) ? '' : 'none';
    });
}

document.getElementById('searchEmployee').addEventListener('input', filterEmployees);
document.getElementById('filterType').addEventListener('change', filterEmployees);

function selectAll() {
    document.querySelectorAll('.employee-row').forEach(row => {
        if (row.style.display !== 'none') {
            row.querySelector('.employee-cb').checked = true;
        }
    });
    updateCount();
}

function deselectAll() {
    document.querySelectorAll('.employee-cb').forEach(cb => cb.checked = false);
    updateCount();
}

// Init
updateCount();
updateDuration();
</script>
@endpush
@endsection
