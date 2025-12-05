@extends('layouts.admin')
@section('title', 'Attribuer une UE')
@section('page-title', 'Attribuer une UE à un Enseignant')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700">
            <p class="text-sm"><i class="fas fa-info-circle mr-2"></i> Saisissez un ou plusieurs codes UE (un par ligne ou séparés par des virgules)</p>
        </div>
        <form method="POST" action="{{ route('admin.unites-enseignement.assign.store') }}" id="assignForm">
            @csrf
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Codes UE *</label>
                    <textarea name="codes_ue_input" id="codes_ue_input" rows="3" required placeholder="Ex: MTH101, PHY102, INFO201 ou un code par ligne" class="w-full px-4 py-2 border rounded-lg @error('codes_ue') border-red-500 @enderror">{{ request('code_ue') }}</textarea>
                    @error('codes_ue')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    <div class="flex gap-2 mt-2">
                        <button type="button" onclick="searchUEs()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">
                            <i class="fas fa-search mr-1"></i> Rechercher les UE
                        </button>
                        <p class="text-xs text-gray-500 self-center">Cliquez pour vérifier les codes</p>
                    </div>
                </div>

                <div id="notFoundCodes" class="hidden p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                    <p class="text-sm font-semibold mb-2"><i class="fas fa-exclamation-triangle mr-2"></i> Codes non trouvés ou déjà attribués :</p>
                    <ul id="notFoundList" class="text-sm ml-6 list-disc"></ul>
                </div>

                <div id="ueInfo" class="hidden">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold">
                            UE trouvées (<span id="ueCount">0</span>)
                            <span id="selectedCount" class="text-blue-600 ml-2"></span>
                        </h3>
                        <label class="inline-flex items-center cursor-pointer" id="selectAllContainer">
                            <input type="checkbox" id="selectAllCheckbox" class="form-checkbox h-4 w-4 text-blue-600 mr-2">
                            <span class="text-sm text-gray-700">Tout sélectionner (disponibles)</span>
                        </label>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-20">Sélection</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Code UE</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Matière</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Volume (h)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Spécialité</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Niveau</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Année</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Semestre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                </tr>
                            </thead>
                            <tbody id="ueTableBody" class="bg-white divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Enseignant *</label>
                    <select name="enseignant_id" required class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Sélectionner un enseignant</option>
                        @foreach($enseignants as $ens)
                            <option value="{{ $ens->id }}">{{ $ens->full_name }} - {{ ucfirst(str_replace('_', ' ', $ens->employee_type)) }} @if($ens->isVacataire()) - Taux horaire: {{ number_format($ens->hourly_rate, 0, ',', ' ') }} FCFA/h @endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="activer_immediatement" value="1" class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Activer immédiatement</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-7">Si coché, l'enseignant pourra commencer à pointer pour cette matière</p>
                </div>
            </div>
            <div class="flex justify-end gap-4 mt-6 pt-6 border-t">
                <a href="{{ route('admin.unites-enseignement.catalog') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Annuler</a>
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg"><i class="fas fa-user-tag mr-2"></i> Attribuer</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let foundUEs = [];

function searchUEs() {
    const input = document.getElementById('codes_ue_input').value.trim();
    if (!input) {
        alert('Veuillez saisir au moins un code UE');
        return;
    }

    // Parse codes (supports comma-separated or line-separated)
    const codes = input
        .split(/[\n,]+/)
        .map(code => code.trim().toUpperCase())
        .filter(code => code.length > 0);

    if (codes.length === 0) {
        alert('Veuillez saisir au moins un code UE valide');
        return;
    }

    // Call API to search multiple codes
    fetch(`{{ route('admin.unites-enseignement.search-multiple-codes') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ codes: codes })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            foundUEs = data.ues || [];
            displayResults(foundUEs, data.not_found || []);
        } else {
            alert('Erreur lors de la recherche des UE');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la recherche des UE');
    });
}

function displayResults(ues, notFound) {
    // Show/hide not found section
    const notFoundDiv = document.getElementById('notFoundCodes');
    const notFoundList = document.getElementById('notFoundList');

    if (notFound.length > 0) {
        notFoundList.innerHTML = notFound.map(code => `<li>${code}</li>`).join('');
        notFoundDiv.classList.remove('hidden');
    } else {
        notFoundDiv.classList.add('hidden');
    }

    // Show/hide UE table
    const ueInfoDiv = document.getElementById('ueInfo');
    const ueTableBody = document.getElementById('ueTableBody');
    const ueCount = document.getElementById('ueCount');

    if (ues.length > 0) {
        const availableCount = ues.filter(ue => !ue.is_assigned).length;
        const assignedCount = ues.filter(ue => ue.is_assigned).length;

        ueCount.textContent = `${ues.length} (${availableCount} disponible${availableCount > 1 ? 's' : ''}, ${assignedCount} déjà attribuée${assignedCount > 1 ? 's' : ''})`;

        ueTableBody.innerHTML = ues.map(ue => {
            const isAssigned = ue.is_assigned;
            const checkboxHtml = isAssigned
                ? `<input type="checkbox" disabled class="form-checkbox h-4 w-4 text-gray-300 cursor-not-allowed" title="UE déjà attribuée">`
                : `<input type="checkbox" class="ue-checkbox form-checkbox h-4 w-4 text-blue-600 cursor-pointer" data-ue-id="${ue.id}" onchange="updateSelectedCount()">`;

            const statusHtml = isAssigned
                ? `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                     <i class="fas fa-lock mr-1"></i> Attribuée à ${ue.enseignant.full_name}
                   </span>`
                : `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                     <i class="fas fa-check-circle mr-1"></i> Disponible
                   </span>`;

            const rowClass = isAssigned ? 'bg-gray-50' : 'hover:bg-blue-50';

            return `
                <tr class="${rowClass}">
                    <td class="px-4 py-2 text-center">${checkboxHtml}</td>
                    <td class="px-4 py-2 text-sm font-mono font-semibold text-blue-600">${ue.code_ue}</td>
                    <td class="px-4 py-2 text-sm ${isAssigned ? 'text-gray-500' : ''}">${ue.nom_matiere}</td>
                    <td class="px-4 py-2 text-sm ${isAssigned ? 'text-gray-500' : ''}">${ue.volume_horaire_total}h</td>
                    <td class="px-4 py-2 text-sm ${isAssigned ? 'text-gray-500' : ''}">${ue.specialite || '-'}</td>
                    <td class="px-4 py-2 text-sm ${isAssigned ? 'text-gray-500' : ''}">${ue.niveau || '-'}</td>
                    <td class="px-4 py-2 text-sm ${isAssigned ? 'text-gray-500' : ''}">${ue.annee_academique}</td>
                    <td class="px-4 py-2 text-sm ${isAssigned ? 'text-gray-500' : ''}">${ue.semestre ? 'S' + ue.semestre : '-'}</td>
                    <td class="px-4 py-2 text-sm">${statusHtml}</td>
                </tr>
            `;
        }).join('');

        ueInfoDiv.classList.remove('hidden');

        // Show/hide "Select All" checkbox
        const selectAllContainer = document.getElementById('selectAllContainer');
        if (availableCount > 0) {
            selectAllContainer.classList.remove('hidden');
        } else {
            selectAllContainer.classList.add('hidden');
        }

        updateSelectedCount();
    } else {
        ueInfoDiv.classList.add('hidden');
        if (notFound.length > 0) {
            alert('Aucune UE trouvée parmi les codes saisis');
        }
    }
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.ue-checkbox');
    const selectedCheckboxes = document.querySelectorAll('.ue-checkbox:checked');
    const selectedCount = document.getElementById('selectedCount');

    if (selectedCheckboxes.length > 0) {
        selectedCount.textContent = `(${selectedCheckboxes.length} sélectionnée${selectedCheckboxes.length > 1 ? 's' : ''})`;
    } else {
        selectedCount.textContent = '';
    }

    // Update "Select All" checkbox state
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (checkboxes.length > 0) {
        selectAllCheckbox.checked = selectedCheckboxes.length === checkboxes.length;
        selectAllCheckbox.indeterminate = selectedCheckboxes.length > 0 && selectedCheckboxes.length < checkboxes.length;
    }
}

// Handle "Select All" checkbox
document.getElementById('selectAllCheckbox').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.ue-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
    });
    updateSelectedCount();
});

// Intercept form submission to add hidden inputs for selected UE IDs
document.getElementById('assignForm').addEventListener('submit', function(e) {
    const selectedCheckboxes = document.querySelectorAll('.ue-checkbox:checked');

    if (foundUEs.length === 0) {
        e.preventDefault();
        alert('Veuillez d\'abord rechercher et vérifier les codes UE');
        return false;
    }

    if (selectedCheckboxes.length === 0) {
        e.preventDefault();
        alert('Veuillez sélectionner au moins une UE disponible à attribuer');
        return false;
    }

    // Remove any existing hidden ue_ids inputs
    document.querySelectorAll('input[name="ue_ids[]"]').forEach(el => el.remove());

    // Add hidden input for each selected UE ID
    selectedCheckboxes.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ue_ids[]';
        input.value = checkbox.dataset.ueId;
        this.appendChild(input);
    });
});
</script>
@endpush
@endsection
