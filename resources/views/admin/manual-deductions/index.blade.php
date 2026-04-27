@extends('layouts.admin')

@section('title', 'Déductions Manuelles')
@section('page-title', 'Gestion des Déductions Manuelles')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Déductions Manuelles</h2>
            <p class="text-gray-600 mt-1">Appliquer des déductions exceptionnelles sur les salaires</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.manual-deductions.export-pdf', request()->query()) }}" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold">
                <i class="fas fa-file-pdf mr-2"></i> Télécharger PDF
            </a>
            <button onclick="openCreateModal()" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-semibold">
                <i class="fas fa-minus-circle mr-2"></i> Nouvelle Déduction
            </button>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employé</label>
                <select name="user_id" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">Tous les employés</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('user_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">Tous</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
                <input type="month" name="month" value="{{ request('month') }}" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <i class="fas fa-filter mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motif</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Période</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appliqué par</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($deductions as $index => $deduction)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">{{ $deductions->firstItem() + $index }}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $deduction->user->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $deduction->user->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-lg font-bold text-red-600">{{ number_format($deduction->amount, 0, ',', ' ') }} FCFA</span>
                        @if($deduction->num_installments > 1)
                            <div class="text-xs text-gray-500 mt-1">
                                Tranche {{ $deduction->installment_number }}/{{ $deduction->num_installments }}
                            </div>
                            <div class="text-xs text-blue-600">
                                Total: {{ number_format($deduction->total_amount, 0, ',', ' ') }} FCFA
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm text-gray-900">{{ Str::limit($deduction->reason, 50) }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        {{ \Carbon\Carbon::create($deduction->year, $deduction->month)->locale('fr')->isoFormat('MMMM YYYY') }}
                    </td>
                    <td class="px-6 py-4">
                        @if($deduction->status === 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Annulée</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $deduction->appliedBy->full_name }}<br>
                        <span class="text-xs">{{ $deduction->created_at->format('d/m/Y H:i') }}</span>
                    </td>
                    <td class="px-6 py-4 text-right text-sm space-x-2">
                        @if($deduction->status === 'active')
                            <button onclick="editDeduction({{ $deduction->id }}, {{ $deduction->amount }}, '{{ addslashes($deduction->reason) }}')"
                                class="text-blue-600 hover:text-blue-900" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            @if($deduction->num_installments > 1 && $deduction->group_id)
                                <button onclick="cancelDeduction({{ $deduction->id }}, true)"
                                    class="text-orange-600 hover:text-orange-900" title="Annuler toutes les tranches">
                                    <i class="fas fa-ban"></i>
                                </button>
                            @endif
                            <button onclick="cancelDeduction({{ $deduction->id }}, false)"
                                class="text-red-600 hover:text-red-900" title="Annuler cette déduction">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4"></i>
                        <p>Aucune déduction trouvée</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $deductions->links() }}
    </div>
</div>

<!-- Modal Créer/Modifier -->
<div id="deductionModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-2xl shadow-2xl rounded-2xl bg-white">
        <h3 class="text-2xl font-bold text-gray-900 mb-6" id="modalTitle">Nouvelle Déduction</h3>

        <form id="deductionForm">
            <input type="hidden" id="deduction_id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Employé *</label>
                    <select id="user_id" required class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Sélectionner un employé</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} - {{ $emp->employee_id }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Montant (FCFA) *</label>
                    <input type="number" id="amount" required min="0" step="100"
                        class="w-full px-4 py-2 border rounded-lg" placeholder="5000">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motif *</label>
                    <textarea id="reason" required rows="3"
                        class="w-full px-4 py-2 border rounded-lg"
                        placeholder="Ex: Travail non achevé, matériel cassé..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mois de début *</label>
                        <select id="month" required class="w-full px-4 py-2 border rounded-lg">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m)->locale('fr')->isoFormat('MMMM') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Année *</label>
                        <select id="year" required class="w-full px-4 py-2 border rounded-lg">
                            @for($y = now()->year; $y >= now()->year - 2; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div id="installmentSection">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de tranches</label>
                    <div class="flex items-center gap-4">
                        <select id="num_installments" class="w-full px-4 py-2 border rounded-lg" onchange="updateInstallmentPreview()">
                            <option value="1">1 mois (paiement unique)</option>
                            <option value="2">2 mois</option>
                            <option value="3">3 mois</option>
                            <option value="4">4 mois</option>
                            <option value="6">6 mois</option>
                            <option value="9">9 mois</option>
                            <option value="12">12 mois</option>
                        </select>
                    </div>
                    <div id="installmentPreview" class="hidden mt-2 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal()"
                    class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">
                    Annuler
                </button>
                <button type="submit"
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i> Appliquer
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nouvelle Déduction';
    document.getElementById('deduction_id').value = '';
    document.getElementById('deductionForm').reset();
    document.getElementById('user_id').disabled = false;
    document.getElementById('installmentSection').classList.remove('hidden');
    document.getElementById('installmentPreview').classList.add('hidden');
    document.getElementById('num_installments').value = '1';
    document.getElementById('deductionModal').classList.remove('hidden');
}

function editDeduction(id, amount, reason) {
    document.getElementById('modalTitle').textContent = 'Modifier Déduction';
    document.getElementById('deduction_id').value = id;
    document.getElementById('amount').value = amount;
    document.getElementById('reason').value = reason;
    document.getElementById('user_id').disabled = true;
    document.getElementById('installmentSection').classList.add('hidden');
    document.getElementById('deductionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('deductionModal').classList.add('hidden');
}

function updateInstallmentPreview() {
    const num = parseInt(document.getElementById('num_installments').value) || 1;
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const preview = document.getElementById('installmentPreview');

    if (num > 1 && amount > 0) {
        const perMonth = Math.round(amount / num);
        const monthSelect = document.getElementById('month');
        const yearSelect = document.getElementById('year');
        const startMonth = parseInt(monthSelect.value);
        const startYear = parseInt(yearSelect.value);

        const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                       'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        let endMonth = startMonth + num - 1;
        let endYear = startYear;
        while (endMonth > 12) { endMonth -= 12; endYear++; }

        preview.innerHTML = `<strong>${num} tranches de ${perMonth.toLocaleString('fr-FR')} FCFA/mois</strong><br>` +
            `De ${months[startMonth - 1]} ${startYear} à ${months[endMonth - 1]} ${endYear}`;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

// Mettre à jour l'aperçu quand le montant change
document.getElementById('amount').addEventListener('input', updateInstallmentPreview);
document.getElementById('month').addEventListener('change', updateInstallmentPreview);
document.getElementById('year').addEventListener('change', updateInstallmentPreview);

document.getElementById('deductionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const deductionId = document.getElementById('deduction_id').value;
    const url = deductionId ? `/admin/manual-deductions/${deductionId}` : '/admin/manual-deductions';
    const method = deductionId ? 'PUT' : 'POST';

    const data = {
        user_id: document.getElementById('user_id').value,
        amount: document.getElementById('amount').value,
        reason: document.getElementById('reason').value,
        month: document.getElementById('month').value,
        year: document.getElementById('year').value,
        num_installments: document.getElementById('num_installments').value,
        _token: '{{ csrf_token() }}'
    };

    fetch(url, {
        method: method,
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
        }
    });
});

function cancelDeduction(id, cancelAll) {
    const msg = cancelAll
        ? 'Annuler TOUTES les tranches restantes de cette déduction ?'
        : 'Annuler uniquement cette tranche ?';

    if(!confirm(msg)) return;

    fetch(`/admin/manual-deductions/${id}/cancel`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({cancel_all: cancelAll, _token: '{{ csrf_token() }}'})
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            location.reload();
        }
    });
}
</script>
@endpush

@endsection
