@extends('layouts.admin')

@section('title', 'Salaires par Banque')
@section('page-title', 'Salaires par Banque')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Salaires par Banque</h2>
            <p class="text-gray-600">{{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.payroll.by-bank.export-pdf', request()->query()) }}" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm">
                <i class="fas fa-file-pdf mr-2"></i> PDF Global
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.payroll.by-bank.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Annee</label>
                <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @for($y = 2024; $y <= 2030; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Banque</label>
                <select name="banque" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes les banques</option>
                    <option value="__none__" {{ request('banque') === '__none__' ? 'selected' : '' }}>Sans banque</option>
                    @foreach(\App\Models\User::whereNotNull('banque')->where('banque', '!=', '')->distinct()->orderBy('banque')->pluck('banque') as $bank)
                        <option value="{{ $bank }}" {{ request('banque') === $bank ? 'selected' : '' }}>{{ $bank }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Campus</label>
                <select name="campus_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email, N compte..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Banques</div>
            <div class="text-3xl font-bold text-blue-600">{{ $totalBanks }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Employes</div>
            <div class="text-3xl font-bold text-gray-800">{{ $totalEmployees }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Salaires Bruts</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($totalGrossSalary, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Salaires Nets a Virer</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($totalNetSalary, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Virements Valides</div>
            <div class="text-3xl font-bold {{ $totalPaid == $totalEmployees && $totalEmployees > 0 ? 'text-green-600' : 'text-orange-500' }}">
                {{ $totalPaid }}/{{ $totalEmployees }}
            </div>
        </div>
    </div>

    <!-- Recap par banque -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">Recapitulatif par Banque</h3>
            <p class="text-sm text-gray-500 mt-1">Validez les virements banque par banque. Les fiches de paie seront visibles par les employes dans l'application.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Banque</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Employes</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Salaires Bruts</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Deductions</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Montant a Virer</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($bankGroups as $index => $group)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $group['is_unassigned'] ? 'bg-red-100' : 'bg-blue-100' }}">
                                    <i class="fas {{ $group['is_unassigned'] ? 'fa-exclamation-triangle text-red-500' : 'fa-university text-blue-600' }} text-sm"></i>
                                </div>
                                <span class="ml-3 text-sm font-medium {{ $group['is_unassigned'] ? 'text-red-600 italic' : 'text-gray-900' }}">
                                    {{ $group['bank_name'] }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-semibold">{{ $group['count'] }}</td>
                        <td class="px-4 py-3 text-right text-sm">{{ number_format($group['total_gross'], 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-3 text-right text-sm text-red-600">{{ number_format($group['total_deductions'], 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-green-600">{{ number_format($group['total_net'], 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-3 text-center">
                            @if($group['all_paid'])
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> Vire
                                </span>
                            @elseif($group['paid_count'] > 0)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i> {{ $group['paid_count'] }}/{{ $group['count'] }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <i class="fas fa-hourglass-half mr-1"></i> En attente
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center space-x-1">
                            <button onclick="toggleBankDetail('bank-{{ $index }}')" class="text-blue-600 hover:text-blue-800 text-sm" title="Voir detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if(!$group['is_unassigned'])
                            <a href="{{ route('admin.payroll.by-bank.export-pdf', array_merge(request()->query(), ['banque' => $group['bank_name']])) }}" class="text-red-600 hover:text-red-800 text-sm" title="PDF cette banque">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            @endif
                            @if(!$group['all_paid'])
                                <button onclick="confirmMarkPaid('{{ $group['bank_key'] }}', '{{ addslashes($group['bank_name']) }}', {{ $group['count'] }}, '{{ number_format($group['total_net'], 0, ',', ' ') }}')"
                                    class="text-green-600 hover:text-green-800 text-sm" title="Valider le virement">
                                    <i class="fas fa-check-double"></i>
                                </button>
                            @else
                                <button onclick="confirmCancelPayment('{{ $group['bank_key'] }}', '{{ addslashes($group['bank_name']) }}')"
                                    class="text-orange-500 hover:text-orange-700 text-sm" title="Annuler le virement">
                                    <i class="fas fa-undo"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    <!-- Detail employes de cette banque -->
                    <tr id="bank-{{ $index }}" class="hidden">
                        <td colspan="8" class="px-0 py-0">
                            <div class="bg-gray-50 border-t border-b border-gray-200">
                                <div class="px-6 py-3 flex justify-between items-center bg-blue-50">
                                    <span class="text-sm font-semibold text-blue-800">
                                        <i class="fas fa-university mr-1"></i> {{ $group['bank_name'] }} - {{ $group['count'] }} employe(s)
                                    </span>
                                    <span class="text-sm font-bold text-green-700">
                                        Total: {{ number_format($group['total_net'], 0, ',', ' ') }} FCFA
                                    </span>
                                </div>
                                <table class="min-w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">#</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Employe</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Type</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">N Compte</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Salaire Brut</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Deductions</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Salaire Net</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($group['employees'] as $empIndex => $employee)
                                        <tr class="hover:bg-white {{ $employee->is_paid ? 'bg-green-50' : '' }}">
                                            <td class="px-4 py-2 text-xs text-gray-600">{{ $empIndex + 1 }}</td>
                                            <td class="px-4 py-2">
                                                <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                                                <div class="text-xs text-gray-500">{{ $employee->employee_id }}</div>
                                            </td>
                                            <td class="px-4 py-2">
                                                @if($employee->employee_type == 'enseignant_titulaire')
                                                    <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">Permanent</span>
                                                @elseif($employee->employee_type == 'semi_permanent')
                                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs">Semi-perm.</span>
                                                @else
                                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-800 rounded text-xs">{{ ucfirst($employee->employee_type) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700 font-mono">{{ $employee->numero_compte ?: '-' }}</td>
                                            <td class="px-4 py-2 text-right text-sm">{{ number_format($employee->gross_salary, 0, ',', ' ') }}</td>
                                            <td class="px-4 py-2 text-right text-sm text-red-600">{{ number_format($employee->total_deductions, 0, ',', ' ') }}</td>
                                            <td class="px-4 py-2 text-right text-sm font-bold text-green-600">{{ number_format($employee->net_salary, 0, ',', ' ') }}</td>
                                            <td class="px-4 py-2 text-center">
                                                @if($employee->is_paid)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                        <i class="fas fa-check mr-1"></i> Paye
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                                        En attente
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if($bankGroups->count() > 0)
                <tfoot>
                    <tr class="bg-gray-100 font-bold">
                        <td class="px-4 py-3 text-sm" colspan="2">TOTAL</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $totalEmployees }}</td>
                        <td class="px-4 py-3 text-right text-sm">{{ number_format($totalGrossSalary, 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-3 text-right text-sm text-red-600">{{ number_format($totalGrossSalary - $totalNetSalary, 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-3 text-right text-sm text-green-600">{{ number_format($totalNetSalary, 0, ',', ' ') }} FCFA</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $totalPaid }}/{{ $totalEmployees }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        @if($bankGroups->count() === 0)
        <div class="px-6 py-12 text-center">
            <div class="text-gray-400">
                <i class="fas fa-university text-6xl mb-4"></i>
                <p class="text-lg">Aucun resultat</p>
                <p class="text-gray-500 mt-2">Aucun employe avec salaire ne correspond aux filtres selectionnes</p>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Confirmation Virement -->
<div id="markPaidModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                <i class="fas fa-check-double text-2xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Valider le virement</h3>
            <div class="bg-gray-50 p-4 rounded text-left text-sm space-y-2 mb-4">
                <p><strong>Banque :</strong> <span id="modal_bank_name"></span></p>
                <p><strong>Employes :</strong> <span id="modal_count"></span></p>
                <p><strong>Montant total :</strong> <span id="modal_amount" class="text-green-600 font-bold"></span> FCFA</p>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Les fiches de paie seront enregistrees et visibles par les employes dans l'application mobile.
            </p>
            <div class="flex justify-center gap-3">
                <button onclick="closeMarkPaidModal()" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Annuler
                </button>
                <button id="confirmMarkPaidBtn" onclick="submitMarkPaid()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-check mr-2"></i> Confirmer le virement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation Annulation -->
<div id="cancelPaymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-orange-100 rounded-full mb-4">
                <i class="fas fa-undo text-2xl text-orange-600"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Annuler le virement</h3>
            <p class="text-sm text-gray-600 mb-2">
                Banque : <strong id="cancel_bank_name"></strong>
            </p>
            <p class="text-sm text-red-600 mb-4">
                Les fiches de paie ne seront plus visibles comme "payees" pour les employes.
            </p>
            <div class="flex justify-center gap-3">
                <button onclick="closeCancelModal()" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Annuler
                </button>
                <button id="confirmCancelBtn" onclick="submitCancelPayment()" class="px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition">
                    <i class="fas fa-undo mr-2"></i> Confirmer l'annulation
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentBankKey = null;

function toggleBankDetail(id) {
    document.getElementById(id).classList.toggle('hidden');
}

// Mark as paid
function confirmMarkPaid(bankKey, bankName, count, amount) {
    currentBankKey = bankKey;
    document.getElementById('modal_bank_name').textContent = bankName;
    document.getElementById('modal_count').textContent = count;
    document.getElementById('modal_amount').textContent = amount;
    document.getElementById('markPaidModal').classList.remove('hidden');
}

function closeMarkPaidModal() {
    document.getElementById('markPaidModal').classList.add('hidden');
}

function submitMarkPaid() {
    const btn = document.getElementById('confirmMarkPaidBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';

    fetch('{{ route("admin.payroll.by-bank.mark-paid") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            banque: currentBankKey,
            year: {{ $year }},
            month: {{ $month }}
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeMarkPaidModal();
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-2"></i> Confirmer le virement';
        }
    })
    .catch(() => {
        alert('Erreur de connexion');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check mr-2"></i> Confirmer le virement';
    });
}

// Cancel payment
function confirmCancelPayment(bankKey, bankName) {
    currentBankKey = bankKey;
    document.getElementById('cancel_bank_name').textContent = bankName;
    document.getElementById('cancelPaymentModal').classList.remove('hidden');
}

function closeCancelModal() {
    document.getElementById('cancelPaymentModal').classList.add('hidden');
}

function submitCancelPayment() {
    const btn = document.getElementById('confirmCancelBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';

    fetch('{{ route("admin.payroll.by-bank.cancel-payment") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            banque: currentBankKey,
            year: {{ $year }},
            month: {{ $month }}
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeCancelModal();
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-undo mr-2"></i> Confirmer l\'annulation';
        }
    })
    .catch(() => {
        alert('Erreur de connexion');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-undo mr-2"></i> Confirmer l\'annulation';
    });
}

// Close modals on outside click
window.onclick = function(event) {
    if (event.target === document.getElementById('markPaidModal')) closeMarkPaidModal();
    if (event.target === document.getElementById('cancelPaymentModal')) closeCancelModal();
}
</script>
@endpush
@endsection
