@extends('layouts.admin')

@section('title', 'Rapport sur la paie')
@section('page-title', 'Rapport sur la paie')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tableau de bord</h2>
            <h3 class="text-xl text-gray-600">Paie</h3>
        </div>
        <a href="{{ route('admin.payroll.report.export', request()->query()) }}" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <i class="fas fa-file-export mr-2"></i> Exporter
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <h4 class="font-semibold text-gray-700 mb-4">Rapport Mensuel</h4>
        <form method="GET" action="{{ route('admin.payroll.report') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
                <select name="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->locale('fr')->isoFormat('MMMM') }}
                        </option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Année</label>
                <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @for($y = 2024; $y <= 2030; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Campus</label>
                <select name="campus_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les campus</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type d'employé</label>
                <select name="employee_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="enseignant_titulaire" {{ request('employee_type') == 'enseignant_titulaire' ? 'selected' : '' }}>Permanent</option>
                    <option value="semi_permanent" {{ request('employee_type') == 'semi_permanent' ? 'selected' : '' }}>Semi-permanent</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Info période -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
        <h4 class="font-bold text-blue-800">
            Rapport de paie - {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}
        </h4>
        <p class="text-sm text-blue-700 mt-1">
            Jours ouvrables dans le mois: <strong>{{ number_format($workingDays, 1) }}</strong> (Lundi-Samedi, Samedi = demi-journée)
        </p>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Total Employés</div>
            <div class="text-3xl font-bold text-gray-800">{{ $totalEmployees }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Salaires Bruts</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($totalGrossSalary, 0, ',', ' ') }} FCFA</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Déductions Totales</div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($totalDeductions, 0, ',', ' ') }} FCFA</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Salaires Nets</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($totalNetSalary, 0, ',', ' ') }} FCFA</div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employé</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type employé</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire Mensuel</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours Travaillés</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours Non Travaillés</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retards totaux (min)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pénalités Retard (FCFA)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Déduction Absences (FCFA)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Déductions Manuelles (FCFA)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prêts (FCFA)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire Final (FCFA)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employees as $index => $employee)
                    <tr class="hover:bg-gray-50" data-employee-id="{{ $employee->id }}">
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>

                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-bold">{{ substr($employee->first_name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $employee->email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($employee->employee_type == 'enseignant_titulaire')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Permanent</span>
                            @elseif($employee->employee_type == 'semi_permanent')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">Semi-permanent</span>
                            @else
                                {{ ucfirst($employee->employee_type) }}
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            {{ number_format($employee->monthly_salary, 0, ',', ' ') }} FCFA
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($employee->days_worked, 1) }}
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            <span class="{{ $employee->days_not_worked > 0 ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                {{ number_format($employee->days_not_worked, 1) }}
                            </span>
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            <span class="{{ $employee->total_late_minutes > 0 ? 'text-orange-600 font-semibold' : 'text-gray-900' }}">
                                {{ $employee->total_late_minutes }} min
                            </span>
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                            {{ number_format($employee->late_penalty_amount, 0, ',', ' ') }} FCFA
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                            {{ number_format($employee->absence_deduction, 0, ',', ' ') }} FCFA
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            @if($employee->manual_deductions > 0)
                                <span class="text-red-600 font-semibold cursor-help" title="Voir détails">
                                    {{ number_format($employee->manual_deductions, 0, ',', ' ') }} FCFA
                                    <button onclick="showManualDeductionsDetails({{ $employee->id }})" class="ml-1 text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </span>
                            @else
                                <span class="text-gray-400">0 FCFA</span>
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            @if($employee->loan_deductions > 0)
                                <span class="text-purple-600 font-semibold cursor-help" title="Remboursement prêt">
                                    {{ number_format($employee->loan_deductions, 0, ',', ' ') }} FCFA
                                </span>
                            @else
                                <span class="text-gray-400">0 FCFA</span>
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                            {{ number_format($employee->net_salary, 0, ',', ' ') }} FCFA
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm space-x-2">
                            <button
                                onclick="openJustifyModal({{ $employee->id }}, '{{ $employee->full_name }}', '{{ $employee->email }}', {{ $employee->days_not_worked }}, {{ $employee->total_late_minutes }})"
                                class="text-blue-600 hover:text-blue-900 font-medium"
                                title="Justifier">
                                <i class="fas fa-file-signature"></i> Justifier
                            </button>
                            <button
                                onclick="openApplyModal({{ $employee->id }}, '{{ $employee->full_name }}', {{ $employee->days_not_worked }}, {{ $employee->monthly_salary }}, {{ $employee->daily_rate ?? 0 }}, {{ $employee->absence_deduction }}, {{ $employee->net_salary }})"
                                class="text-green-600 hover:text-green-900 font-medium"
                                title="Appliquer la déduction">
                                <i class="fas fa-check-circle"></i> Appliquer
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <i class="fas fa-file-invoice-dollar text-6xl mb-4"></i>
                                <p class="text-lg">Aucun employé trouvé</p>
                                <p class="text-gray-500 mt-2">Aucun employé n'a de salaire mensuel configuré ou ne correspond aux filtres</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- TOTAUX en bas du tableau -->
        @if($employees->count() > 0)
        <div class="bg-gray-50 px-4 py-3 border-t-2 border-gray-300">
            <div class="grid grid-cols-13 gap-4 font-bold text-sm">
                <div class="col-span-3 text-right">TOTAUX:</div>
                <div class="text-right">{{ number_format($totalGrossSalary, 0, ',', ' ') }} FCFA</div>
                <div></div>
                <div></div>
                <div></div>
                <div class="text-right text-red-600">{{ number_format($employees->sum('late_penalty_amount'), 0, ',', ' ') }} FCFA</div>
                <div class="text-right text-red-600">{{ number_format($employees->sum('absence_deduction'), 0, ',', ' ') }} FCFA</div>
                <div class="text-right text-red-600">{{ number_format($employees->sum('manual_deductions'), 0, ',', ' ') }} FCFA</div>
                <div class="text-right text-purple-600">{{ number_format($employees->sum('loan_deductions'), 0, ',', ' ') }} FCFA</div>
                <div class="text-right text-green-600">{{ number_format($totalNetSalary, 0, ',', ' ') }} FCFA</div>
                <div></div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Justifier -->
<div id="justifyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">
                Justifier les jours non travaillés
            </h3>

            <form id="justifyForm" onsubmit="submitJustification(event)">
                <input type="hidden" id="justify_user_id" name="user_id">

                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="text-sm"><strong>Employé:</strong> <span id="justify_employee_name"></span></p>
                        <p class="text-sm"><strong>Email:</strong> <span id="justify_employee_email"></span></p>
                        <p class="text-sm"><strong>Période:</strong> {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}</p>
                        <p class="text-sm"><strong>Jours non travaillés:</strong> <span id="justify_days_not_worked"></span> jour(s)</p>
                        <p class="text-sm"><strong>Retard total:</strong> <span id="justify_late_minutes"></span> minutes</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre de jours à justifier <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="days_justified"
                            name="days_justified"
                            step="0.5"
                            min="0"
                            required
                            placeholder="Entrez le nombre de jours à justifier (ex: 1, 2, 2.5)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Minutes de retard à justifier
                        </label>
                        <input
                            type="number"
                            id="late_minutes_justified"
                            name="late_minutes_justified"
                            min="0"
                            placeholder="Entrez le nombre de minutes de retard à justifier (ex: 30, 60, 120)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Motif de justification <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="4"
                            required
                            placeholder="Ex: Maladie, Congé autorisé, Formation, etc."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        type="button"
                        onclick="closeJustifyModal()"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                        Annuler
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-save mr-2"></i> Enregistrer la justification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Appliquer la déduction -->
<div id="applyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-yellow-100 rounded-full mb-4">
                <i class="fas fa-exclamation-triangle text-2xl text-yellow-600"></i>
            </div>

            <h3 class="text-lg leading-6 font-bold text-gray-900 text-center mb-4">
                Confirmation d'application
            </h3>

            <form id="applyForm" onsubmit="submitApplyDeduction(event)">
                <input type="hidden" id="apply_user_id" name="user_id">

                <div class="bg-gray-50 p-4 rounded space-y-2 text-sm">
                    <p><strong>Employé:</strong> <span id="apply_employee_name"></span></p>
                    <p class="text-orange-600 font-semibold">
                        Voulez-vous appliquer la déduction pour <span id="apply_days_not_worked"></span> jour(s) non travaillé(s) ?
                    </p>
                    <hr class="my-2">
                    <p><strong>Salaire mensuel:</strong> <span id="apply_monthly_salary"></span> FCFA</p>
                    <p><strong>Taux journalier:</strong> <span id="apply_daily_rate"></span> FCFA</p>
                    <p class="text-red-600"><strong>Déduction:</strong> <span id="apply_deduction"></span> FCFA</p>
                    <p class="text-green-600 font-bold text-lg"><strong>Salaire final:</strong> <span id="apply_net_salary"></span> FCFA</p>
                </div>

                <div class="flex justify-center gap-3 mt-6">
                    <button
                        type="button"
                        onclick="closeApplyModal()"
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                        Annuler
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                        <i class="fas fa-check mr-2"></i> Confirmer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Détails Déductions Manuelles -->
<div id="manualDeductionsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4">
                Détails des Déductions Manuelles
            </h3>

            <div id="manualDeductionsContent" class="space-y-3">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>

            <div class="flex justify-end mt-6">
                <button
                    type="button"
                    onclick="closeManualDeductionsModal()"
                    class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Modal Détails Déductions Manuelles
function showManualDeductionsDetails(userId) {
    const modal = document.getElementById('manualDeductionsModal');
    const content = document.getElementById('manualDeductionsContent');

    content.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</p>';
    modal.classList.remove('hidden');

    fetch(`/admin/manual-deductions?user_id=${userId}&month={{ $month }}&year={{ $year }}`)
        .then(response => response.text())
        .then(html => {
            // Parser le HTML pour extraire les déductions
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Simuler l'affichage des déductions (on va créer un endpoint API plus tard)
            fetch(`/admin/api/manual-deductions/${userId}?month={{ $month }}&year={{ $year }}`)
                .then(response => response.json())
                .then(data => {
                    if (data.deductions && data.deductions.length > 0) {
                        let html = '<div class="space-y-3">';
                        data.deductions.forEach((deduction, index) => {
                            html += `
                                <div class="bg-gray-50 p-4 rounded border-l-4 border-red-500">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-red-600">${index + 1}. Montant: ${parseInt(deduction.amount).toLocaleString('fr-FR')} FCFA</p>
                                            <p class="text-sm text-gray-700 mt-2"><strong>Motif:</strong> ${deduction.reason}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Appliqué par: ${deduction.applied_by} le ${new Date(deduction.created_at).toLocaleDateString('fr-FR')}
                                            </p>
                                        </div>
                                        <span class="px-2 py-1 text-xs rounded ${deduction.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                            ${deduction.status === 'active' ? 'Active' : 'Annulée'}
                                        </span>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = '<p class="text-center text-gray-500">Aucune déduction manuelle pour cette période.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<p class="text-center text-red-500">Erreur lors du chargement des déductions.</p>';
                });
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<p class="text-center text-red-500">Erreur lors du chargement des déductions.</p>';
        });
}

function closeManualDeductionsModal() {
    document.getElementById('manualDeductionsModal').classList.add('hidden');
}

// Modal Justifier
function openJustifyModal(userId, name, email, daysNotWorked, lateMinutes) {
    document.getElementById('justify_user_id').value = userId;
    document.getElementById('justify_employee_name').textContent = name;
    document.getElementById('justify_employee_email').textContent = email;
    document.getElementById('justify_days_not_worked').textContent = daysNotWorked;
    document.getElementById('justify_late_minutes').textContent = lateMinutes;

    // Réinitialiser le formulaire
    document.getElementById('justifyForm').reset();
    document.getElementById('justify_user_id').value = userId;

    document.getElementById('justifyModal').classList.remove('hidden');
}

function closeJustifyModal() {
    document.getElementById('justifyModal').classList.add('hidden');
}

function submitJustification(event) {
    event.preventDefault();

    const formData = {
        user_id: document.getElementById('justify_user_id').value,
        year: {{ $year }},
        month: {{ $month }},
        days_justified: parseFloat(document.getElementById('days_justified').value),
        late_minutes_justified: parseInt(document.getElementById('late_minutes_justified').value) || 0,
        reason: document.getElementById('reason').value,
        _token: '{{ csrf_token() }}'
    };

    fetch('{{ route('admin.payroll.justify') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Justification enregistrée avec succès !');
            closeJustifyModal();
            location.reload();
        } else {
            alert('Erreur lors de l\'enregistrement.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de l\'enregistrement.');
    });
}

// Modal Appliquer
function openApplyModal(userId, name, daysNotWorked, monthlySalary, dailyRate, deduction, netSalary) {
    document.getElementById('apply_user_id').value = userId;
    document.getElementById('apply_employee_name').textContent = name;
    document.getElementById('apply_days_not_worked').textContent = daysNotWorked.toFixed(1);
    document.getElementById('apply_monthly_salary').textContent = Math.round(monthlySalary).toLocaleString('fr-FR');
    document.getElementById('apply_daily_rate').textContent = Math.round(dailyRate).toLocaleString('fr-FR');
    document.getElementById('apply_deduction').textContent = Math.round(deduction).toLocaleString('fr-FR');
    document.getElementById('apply_net_salary').textContent = Math.round(netSalary).toLocaleString('fr-FR');

    document.getElementById('applyModal').classList.remove('hidden');
}

function closeApplyModal() {
    document.getElementById('applyModal').classList.add('hidden');
}

function submitApplyDeduction(event) {
    event.preventDefault();

    const formData = {
        user_id: document.getElementById('apply_user_id').value,
        year: {{ $year }},
        month: {{ $month }},
        _token: '{{ csrf_token() }}'
    };

    fetch('{{ route('admin.payroll.apply-deduction') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Déduction appliquée avec succès !');
            closeApplyModal();
            location.reload();
        } else {
            alert('Erreur lors de l\'application.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de l\'application.');
    });
}

// Fermer les modals en cliquant à l'extérieur
window.onclick = function(event) {
    const justifyModal = document.getElementById('justifyModal');
    const applyModal = document.getElementById('applyModal');
    const manualDeductionsModal = document.getElementById('manualDeductionsModal');

    if (event.target == justifyModal) {
        closeJustifyModal();
    }
    if (event.target == applyModal) {
        closeApplyModal();
    }
    if (event.target == manualDeductionsModal) {
        closeManualDeductionsModal();
    }
}
</script>
@endpush

@endsection
