@extends('layouts.admin')

@section('title', 'Gestion des Prêts')
@section('page-title', 'Gestion des Prêts')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Prêts</h2>
            <p class="text-gray-600 mt-1">Gérer les prêts accordés aux employés</p>
        </div>
        <button onclick="openCreateModal()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold">
            <i class="fas fa-plus-circle mr-2"></i> Nouveau Prêt
        </button>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Terminé</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                </select>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mensualité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Déjà Payé</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reste</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progression</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Début</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($loans as $index => $loan)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">{{ $loans->firstItem() + $index }}</td>

                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $loan->user->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $loan->user->email }}</div>
                    </td>

                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                        {{ number_format($loan->total_amount, 0, ',', ' ') }} FCFA
                    </td>

                    <td class="px-6 py-4 text-sm text-blue-600 font-semibold">
                        {{ number_format($loan->monthly_amount, 0, ',', ' ') }} FCFA
                    </td>

                    <td class="px-6 py-4 text-sm text-green-600 font-semibold">
                        {{ number_format($loan->amount_paid, 0, ',', ' ') }} FCFA
                    </td>

                    <td class="px-6 py-4 text-sm text-orange-600 font-semibold">
                        {{ number_format($loan->remaining_amount, 0, ',', ' ') }} FCFA
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2">
                                <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $loan->progress_percentage }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-gray-700">{{ number_format($loan->progress_percentage, 1) }}%</span>
                        </div>
                    </td>

                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $loan->start_date->format('d/m/Y') }}
                    </td>

                    <td class="px-6 py-4">
                        @if($loan->status === 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actif</span>
                        @elseif($loan->status === 'completed')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Terminé</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Annulé</span>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-right text-sm space-x-2">
                        @if($loan->status === 'active')
                            <button onclick="editLoan({{ $loan->id }}, {{ $loan->monthly_amount }}, '{{ addslashes($loan->reason ?? '') }}')"
                                class="text-blue-600 hover:text-blue-900" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="markAsCompleted({{ $loan->id }})"
                                class="text-green-600 hover:text-green-900" title="Marquer comme terminé">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <button onclick="cancelLoan({{ $loan->id }})"
                                class="text-red-600 hover:text-red-900" title="Annuler">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        @endif
                        <button onclick="showLoanDetails({{ json_encode($loan) }})"
                            class="text-gray-600 hover:text-gray-900" title="Détails">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-hand-holding-usd text-4xl mb-4"></i>
                        <p>Aucun prêt trouvé</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $loans->links() }}
    </div>
</div>

<!-- Modal Créer/Modifier -->
<div id="loanModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-2xl shadow-2xl rounded-2xl bg-white">
        <h3 class="text-2xl font-bold text-gray-900 mb-6" id="modalTitle">Nouveau Prêt</h3>

        <form id="loanForm">
            <input type="hidden" id="loan_id">

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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Montant Total (FCFA) *</label>
                        <input type="number" id="total_amount" required min="1" step="1000"
                            class="w-full px-4 py-2 border rounded-lg" placeholder="100000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mensualité (FCFA) *</label>
                        <input type="number" id="monthly_amount" required min="1" step="1000"
                            class="w-full px-4 py-2 border rounded-lg" placeholder="10000">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date de Début des Déductions *</label>
                    <input type="date" id="start_date" required
                        class="w-full px-4 py-2 border rounded-lg" value="{{ now()->format('Y-m-d') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motif/Raison (optionnel)</label>
                    <textarea id="reason" rows="3"
                        class="w-full px-4 py-2 border rounded-lg"
                        placeholder="Ex: Prêt pour achat de matériel, urgence familiale..."></textarea>
                </div>

                <div id="calculatedInfo" class="hidden bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm font-semibold text-blue-900">Informations calculées:</p>
                    <p class="text-sm text-blue-700 mt-1">Nombre de mensualités: <span id="calc_months">-</span></p>
                    <p class="text-sm text-blue-700">Date fin estimée: <span id="calc_end_date">-</span></p>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal()"
                    class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">
                    Annuler
                </button>
                <button type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Détails -->
<div id="detailsModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-2xl shadow-2xl rounded-2xl bg-white">
        <h3 class="text-2xl font-bold text-gray-900 mb-6">Détails du Prêt</h3>
        <div id="detailsContent"></div>
        <div class="flex justify-end mt-6">
            <button type="button" onclick="closeDetailsModal()"
                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">
                Fermer
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Calculer les informations automatiquement
document.getElementById('total_amount')?.addEventListener('input', calculateInfo);
document.getElementById('monthly_amount')?.addEventListener('input', calculateInfo);
document.getElementById('start_date')?.addEventListener('change', calculateInfo);

function calculateInfo() {
    const total = parseFloat(document.getElementById('total_amount').value) || 0;
    const monthly = parseFloat(document.getElementById('monthly_amount').value) || 0;
    const startDate = document.getElementById('start_date').value;

    if (total > 0 && monthly > 0) {
        const months = Math.ceil(total / monthly);
        document.getElementById('calc_months').textContent = months;

        if (startDate) {
            const start = new Date(startDate);
            start.setMonth(start.getMonth() + months);
            document.getElementById('calc_end_date').textContent = start.toLocaleDateString('fr-FR');
        }

        document.getElementById('calculatedInfo').classList.remove('hidden');
    } else {
        document.getElementById('calculatedInfo').classList.add('hidden');
    }
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nouveau Prêt';
    document.getElementById('loan_id').value = '';
    document.getElementById('loanForm').reset();
    document.getElementById('user_id').disabled = false;
    document.getElementById('total_amount').disabled = false;
    document.getElementById('start_date').disabled = false;
    document.getElementById('loanModal').classList.remove('hidden');
}

function editLoan(id, monthlyAmount, reason) {
    document.getElementById('modalTitle').textContent = 'Modifier Prêt';
    document.getElementById('loan_id').value = id;
    document.getElementById('monthly_amount').value = monthlyAmount;
    document.getElementById('reason').value = reason;
    document.getElementById('user_id').disabled = true;
    document.getElementById('total_amount').disabled = true;
    document.getElementById('start_date').disabled = true;
    document.getElementById('loanModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('loanModal').classList.add('hidden');
}

document.getElementById('loanForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const loanId = document.getElementById('loan_id').value;
    const url = loanId ? `/admin/loans/${loanId}` : '/admin/loans';
    const method = loanId ? 'PUT' : 'POST';

    const data = {
        user_id: document.getElementById('user_id').value,
        total_amount: document.getElementById('total_amount').value,
        monthly_amount: document.getElementById('monthly_amount').value,
        start_date: document.getElementById('start_date').value,
        reason: document.getElementById('reason').value,
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

function markAsCompleted(id) {
    if(!confirm('Voulez-vous vraiment marquer ce prêt comme terminé (remboursement anticipé)?')) return;

    fetch(`/admin/loans/${id}/mark-completed`, {
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

function cancelLoan(id) {
    if(!confirm('Voulez-vous vraiment annuler ce prêt?')) return;

    fetch(`/admin/loans/${id}/cancel`, {
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

function showLoanDetails(loan) {
    const content = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Employé</p>
                    <p class="font-semibold">${loan.user.full_name}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Statut</p>
                    <p class="font-semibold">${loan.status === 'active' ? 'Actif' : loan.status === 'completed' ? 'Terminé' : 'Annulé'}</p>
                </div>
            </div>
            <hr>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Montant Total</p>
                    <p class="font-semibold text-lg">${parseInt(loan.total_amount).toLocaleString('fr-FR')} FCFA</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Mensualité</p>
                    <p class="font-semibold text-blue-600">${parseInt(loan.monthly_amount).toLocaleString('fr-FR')} FCFA</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Déjà Payé</p>
                    <p class="font-semibold text-green-600">${parseInt(loan.amount_paid).toLocaleString('fr-FR')} FCFA</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Reste à Payer</p>
                    <p class="font-semibold text-orange-600">${parseInt(loan.remaining_amount).toLocaleString('fr-FR')} FCFA</p>
                </div>
            </div>
            <hr>
            <div>
                <p class="text-sm text-gray-600">Date de Début</p>
                <p class="font-semibold">${new Date(loan.start_date).toLocaleDateString('fr-FR')}</p>
            </div>
            ${loan.reason ? `
                <div>
                    <p class="text-sm text-gray-600">Motif</p>
                    <p class="text-gray-800">${loan.reason}</p>
                </div>
            ` : ''}
            <div>
                <p class="text-sm text-gray-600">Créé par</p>
                <p class="font-semibold">${loan.created_by ? loan.created_by.full_name : 'N/A'} le ${new Date(loan.created_at).toLocaleDateString('fr-FR')}</p>
            </div>
        </div>
    `;

    document.getElementById('detailsContent').innerHTML = content;
    document.getElementById('detailsModal').classList.remove('hidden');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

// Fermer les modals en cliquant à l'extérieur
window.onclick = function(event) {
    const loanModal = document.getElementById('loanModal');
    const detailsModal = document.getElementById('detailsModal');

    if (event.target == loanModal) {
        closeModal();
    }
    if (event.target == detailsModal) {
        closeDetailsModal();
    }
}
</script>
@endpush

@endsection
