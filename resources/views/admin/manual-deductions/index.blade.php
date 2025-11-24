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
        <button onclick="openCreateModal()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold">
            <i class="fas fa-minus-circle mr-2"></i> Nouvelle Déduction
        </button>
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
                                class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="cancelDeduction({{ $deduction->id }})"
                                class="text-red-600 hover:text-red-900">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mois *</label>
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
    document.getElementById('deductionModal').classList.remove('hidden');
}

function editDeduction(id, amount, reason) {
    document.getElementById('modalTitle').textContent = 'Modifier Déduction';
    document.getElementById('deduction_id').value = id;
    document.getElementById('amount').value = amount;
    document.getElementById('reason').value = reason;
    document.getElementById('user_id').disabled = true;
    document.getElementById('deductionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('deductionModal').classList.add('hidden');
}

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
            alert('Erreur: ' + data.message);
        }
    });
});

function cancelDeduction(id) {
    if(!confirm('Voulez-vous vraiment annuler cette déduction?')) return;

    fetch(`/admin/manual-deductions/${id}/cancel`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({_token: '{{ csrf_token() }}'})
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
