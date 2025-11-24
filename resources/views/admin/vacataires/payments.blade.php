@extends('layouts.admin')

@section('title', 'Paiements Vacataires')
@section('page-title', 'Gestion des Paiements Vacataires')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Paiements Vacataires</h2>
            <p class="text-gray-600 mt-1">Gestion des Paiements Mensuels</p>
        </div>
        <button
            onclick="generatePayments()"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold">
            <i class="fas fa-magic mr-2"></i> Générer paies du mois
        </button>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Vacataires</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalVacataires }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-users text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Heures</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($totalHours, 2) }}</p>
                    <p class="text-xs text-gray-500">h</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-clock text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Coût Total</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($totalCost, 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">En Attente</p>
                    <p class="text-3xl font-bold text-orange-600">{{ $pendingCount }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-hourglass-half text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">Validés: <span class="font-bold text-blue-600">{{ $validatedCount }}</span></p>
                <p class="text-sm text-gray-600">Payés: <span class="font-bold text-green-600">{{ $paidCount }}</span></p>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.vacataires.payments') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
                <input
                    type="month"
                    name="month"
                    value="{{ $monthFormatted }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les statuts</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="validated" {{ request('status') == 'validated' ? 'selected' : '' }}>Validé</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payé</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-filter mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Info période -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
        <h4 class="font-bold text-blue-800">
            Paiements - {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}
        </h4>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vacataire</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Département</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jours</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heures</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taux</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brut</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Déductions</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bonus</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($vacataires as $index => $vacataire)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>

                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                        <span class="text-purple-600 font-bold">{{ substr($vacataire->first_name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $vacataire->full_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $vacataire->email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $vacataire->department->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($vacataire->days_worked, 1) }}
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            {{ number_format($vacataire->hours_worked, 2) }}h
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($vacataire->hourly_rate, 0, ',', ' ') }} FCFA/h
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            {{ number_format($vacataire->gross_amount, 0, ',', ' ') }} FCFA
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600">
                            {{ number_format($vacataire->late_penalty, 0, ',', ' ') }} FCFA
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm text-green-600">
                            0 FCFA
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                            {{ number_format($vacataire->net_amount, 0, ',', ' ') }} FCFA
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap">
                            @if($vacataire->payment_status == 'pending')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                    En attente
                                </span>
                            @elseif($vacataire->payment_status == 'validated')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Validé
                                </span>
                            @elseif($vacataire->payment_status == 'paid')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Payé
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm space-x-2">
                            @if($vacataire->payment_status == 'pending' && $vacataire->payment_id)
                                <button
                                    onclick="openValidateModal({{ $vacataire->payment_id }}, '{{ $vacataire->full_name }}', {{ $vacataire->net_amount }})"
                                    class="text-blue-600 hover:text-blue-900 font-medium"
                                    title="Valider">
                                    <i class="fas fa-check"></i> Valider
                                </button>
                            @elseif($vacataire->payment_status == 'validated' && $vacataire->payment_id)
                                <button
                                    onclick="openMarkPaidModal({{ $vacataire->payment_id }}, '{{ $vacataire->full_name }}', {{ $vacataire->net_amount }})"
                                    class="text-green-600 hover:text-green-900 font-medium"
                                    title="Marquer comme payé">
                                    <i class="fas fa-money-bill"></i> Payé
                                </button>
                            @elseif($vacataire->payment_status == 'paid')
                                <span class="text-gray-400">
                                    <i class="fas fa-check-circle"></i> Terminé
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <i class="fas fa-money-bill-wave text-6xl mb-4"></i>
                                <p class="text-lg">Aucun paiement trouvé pour cette période.</p>
                                <p class="text-gray-500 mt-2">Cliquez sur "Générer paies du mois" pour créer les paiements</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Valider Paiement -->
<div id="validateModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-2xl rounded-2xl bg-white">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
                <i class="fas fa-check text-3xl text-blue-600"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Valider le Paiement</h3>
            <p class="text-gray-600 mb-6">
                Voulez-vous valider le paiement pour <strong id="validate_name"></strong> ?
            </p>
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-600">Montant Net</p>
                <p class="text-3xl font-bold text-blue-600"><span id="validate_amount"></span> FCFA</p>
            </div>
            <div class="flex space-x-3">
                <button
                    onclick="closeValidateModal()"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    Annuler
                </button>
                <button
                    onclick="confirmValidatePayment()"
                    class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-check mr-2"></i> Valider
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Marquer comme Payé -->
<div id="markPaidModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-8 border w-full max-w-md shadow-2xl rounded-2xl bg-white">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <i class="fas fa-money-bill-wave text-3xl text-green-600"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Marquer comme Payé</h3>
            <p class="text-gray-600 mb-6">
                Confirmez-vous que le paiement a été effectué pour <strong id="paid_name"></strong> ?
            </p>
            <div class="bg-green-50 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-600">Montant Net</p>
                <p class="text-3xl font-bold text-green-600"><span id="paid_amount"></span> FCFA</p>
            </div>
            <div class="flex space-x-3">
                <button
                    onclick="closeMarkPaidModal()"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    Annuler
                </button>
                <button
                    onclick="confirmMarkAsPaid()"
                    class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    <i class="fas fa-check mr-2"></i> Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPaymentId = null;

function generatePayments() {
    if (!confirm('Voulez-vous générer les paies pour tous les vacataires du mois sélectionné ?')) {
        return;
    }

    const monthYear = '{{ $monthFormatted }}';
    const [year, month] = monthYear.split('-');

    fetch('{{ route('admin.vacataires.payments.generate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            year: parseInt(year),
            month: parseInt(month),
            _token: '{{ csrf_token() }}'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la génération des paiements.');
    });
}

function openValidateModal(paymentId, name, amount) {
    currentPaymentId = paymentId;
    document.getElementById('validate_name').textContent = name;
    document.getElementById('validate_amount').textContent = new Intl.NumberFormat('fr-FR').format(amount);
    document.getElementById('validateModal').classList.remove('hidden');
}

function closeValidateModal() {
    document.getElementById('validateModal').classList.add('hidden');
    currentPaymentId = null;
}

function confirmValidatePayment() {
    if (!currentPaymentId) return;

    fetch(`/admin/vacataires/payments/${currentPaymentId}/validate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            _token: '{{ csrf_token() }}'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur lors de la validation.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la validation.');
    });
}

function openMarkPaidModal(paymentId, name, amount) {
    currentPaymentId = paymentId;
    document.getElementById('paid_name').textContent = name;
    document.getElementById('paid_amount').textContent = new Intl.NumberFormat('fr-FR').format(amount);
    document.getElementById('markPaidModal').classList.remove('hidden');
}

function closeMarkPaidModal() {
    document.getElementById('markPaidModal').classList.add('hidden');
    currentPaymentId = null;
}

function confirmMarkAsPaid() {
    if (!currentPaymentId) return;

    fetch(`/admin/vacataires/payments/${currentPaymentId}/mark-paid`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            _token: '{{ csrf_token() }}'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur lors de la mise à jour.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la mise à jour.');
    });
}
</script>
@endpush

@endsection
