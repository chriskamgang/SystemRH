@extends('layouts.admin')

@section('title', 'Portefeuilles')
@section('page-title', 'Portefeuilles')

@section('content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestion des Portefeuilles</h2>
            <p class="text-gray-600 mt-1">Consultez et gérez les portefeuilles des employés</p>
        </div>
        <a href="{{ route('admin.wallets.export-pdf', request()->query()) }}" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold">
            <i class="fas fa-file-pdf mr-2"></i> Télécharger PDF
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-wallet text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Portefeuilles actifs</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $walletCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-coins text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Solde total des portefeuilles</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($totalBalance, 0, ',', '.') }} FCFA</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 cursor-pointer hover:shadow-lg transition" id="elgiopayCard" onclick="openTopupModal()">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-university text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Solde ElgioPay</p>
                    <p class="text-2xl font-bold text-gray-800" id="elgiopayBalance">
                        <i class="fas fa-spinner fa-spin text-gray-400"></i>
                    </p>
                </div>
            </div>
            <div class="mt-3 text-center">
                <span class="text-xs text-purple-600 font-semibold"><i class="fas fa-plus-circle mr-1"></i>Cliquez pour recharger</span>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.wallets.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email, matricule..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type d'employé</label>
                <select name="employee_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="enseignant_titulaire" {{ request('employee_type') == 'enseignant_titulaire' ? 'selected' : '' }}>Permanent</option>
                    <option value="semi_permanent" {{ request('employee_type') == 'semi_permanent' ? 'selected' : '' }}>Semi-permanent</option>
                    <option value="enseignant_vacataire" {{ request('employee_type') == 'enseignant_vacataire' ? 'selected' : '' }}>Vacataire</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.wallets.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Actions groupées -->
    <div class="flex gap-3" id="bulkActions" style="display: none;">
        <button onclick="openBulkCreditModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-money-bill-wave mr-2"></i>Payer la sélection (<span id="selectedCount">0</span>)
        </button>
        <button onclick="clearSelection()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
            <i class="fas fa-times mr-2"></i>Tout désélectionner
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Département</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Solde</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $employee)
                <tr class="hover:bg-gray-50 cursor-pointer" onclick="showTransactions({{ $employee->id }}, '{{ addslashes($employee->full_name) }}')">
                    <td class="px-4 py-4" onclick="event.stopPropagation()">
                        <input type="checkbox" class="employee-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            value="{{ $employee->id }}" onchange="updateBulkActions()">
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $employee->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($employee->employee_type == 'enseignant_titulaire')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Permanent</span>
                        @elseif($employee->employee_type == 'semi_permanent')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Semi-permanent</span>
                        @elseif($employee->employee_type == 'enseignant_vacataire')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Vacataire</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ $employee->employee_type }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $employee->department->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="text-sm font-bold {{ ($employee->wallet->balance ?? 0) > 0 ? 'text-green-600' : 'text-gray-500' }}">
                            {{ number_format($employee->wallet->balance ?? 0, 0, ',', '.') }} FCFA
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center" onclick="event.stopPropagation()">
                        <button onclick="openCreditModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}', {{ $employee->wallet->balance ?? 0 }})"
                            class="px-3 py-1 text-xs bg-green-600 text-white rounded-full hover:bg-green-700">
                            <i class="fas fa-plus mr-1"></i>Créditer
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-wallet text-4xl mb-4 block text-gray-300"></i>
                        Aucun employé trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $employees->links() }}
        </div>
    </div>
</div>

<!-- Modal Créditer un employé -->
<div id="creditModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Créditer le portefeuille</h3>
            <p id="creditInfo" class="text-gray-600 mb-4"></p>

            <input type="hidden" id="creditUserId">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant (FCFA) *</label>
                    <input type="number" id="creditAmount" min="1" step="1" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: 50000">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (optionnel)</label>
                    <input type="text" id="creditDescription"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Paiement salaire mars 2026">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeCreditModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">Annuler</button>
                <button onclick="submitCredit()" id="creditSubmitBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Créditer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Créditer en masse -->
<div id="bulkCreditModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Paiement groupé</h3>
            <p id="bulkCreditInfo" class="text-gray-600 mb-4"></p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant par employé (FCFA) *</label>
                    <input type="number" id="bulkCreditAmount" min="1" step="1" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: 50000">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (optionnel)</label>
                    <input type="text" id="bulkCreditDescription"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Paiement salaire mars 2026">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeBulkCreditModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">Annuler</button>
                <button onclick="submitBulkCredit()" id="bulkCreditSubmitBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Créditer la sélection</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transactions -->
<div id="transactionsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[80vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800" id="transactionsTitle">Historique des transactions</h3>
            <button onclick="closeTransactionsModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-y-auto" id="transactionsContent">
            <div class="text-center text-gray-400 py-8">
                <i class="fas fa-spinner fa-spin text-2xl"></i>
                <p class="mt-2">Chargement...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Recharger ElgioPay -->
<div id="topupModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-university text-purple-600 mr-2"></i>Recharger ElgioPay
                </h3>
                <button onclick="closeTopupModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <p class="text-sm text-gray-600 mb-4">
                L'argent sera collecté depuis votre numéro mobile money et ajouté à votre compte ElgioPay.
            </p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de téléphone *</label>
                    <input type="text" id="topupPhone" placeholder="Ex: 6XXXXXXXX"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        oninput="detectTopupMethod()">
                    <p class="text-xs text-gray-500 mt-1" id="topupMethodDetected"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant (FCFA) *</label>
                    <input type="number" id="topupAmount" min="100" max="1000000" step="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Ex: 100000">
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-4">
                <div class="flex items-start gap-2">
                    <i class="fas fa-info-circle text-yellow-600 mt-0.5"></i>
                    <p class="text-xs text-yellow-800">
                        Après confirmation, vous recevrez une demande de paiement sur votre téléphone. Validez-la pour finaliser le rechargement.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeTopupModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">Annuler</button>
                <button onclick="submitTopup()" id="topupSubmitBtn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <i class="fas fa-paper-plane mr-2"></i>Envoyer la demande
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Charger le solde ElgioPay au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        loadElgioPayBalance();
    });

    function loadElgioPayBalance() {
        fetch('{{ route("admin.wallets.elgiopay-balance") }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('elgiopayBalance').textContent =
                    parseInt(data.balance).toLocaleString('fr-FR') + ' ' + (data.currency || 'FCFA');
            } else {
                document.getElementById('elgiopayBalance').innerHTML = '<span class="text-sm text-red-500">Indisponible</span>';
            }
        })
        .catch(() => {
            document.getElementById('elgiopayBalance').innerHTML = '<span class="text-sm text-red-500">Erreur</span>';
        });
    }

    // ===== Selection / Bulk =====
    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = checked);
        updateBulkActions();
    }

    function updateBulkActions() {
        const checked = document.querySelectorAll('.employee-checkbox:checked');
        const bulkDiv = document.getElementById('bulkActions');
        document.getElementById('selectedCount').textContent = checked.length;
        bulkDiv.style.display = checked.length > 0 ? 'flex' : 'none';
    }

    function clearSelection() {
        document.getElementById('selectAll').checked = false;
        document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
        updateBulkActions();
    }

    function getSelectedUserIds() {
        return Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => parseInt(cb.value));
    }

    // ===== Modal Crédit individuel =====
    function openCreditModal(userId, name, balance) {
        document.getElementById('creditUserId').value = userId;
        document.getElementById('creditInfo').textContent = `Créditer le portefeuille de ${name} (solde actuel: ${parseInt(balance).toLocaleString('fr-FR')} FCFA)`;
        document.getElementById('creditAmount').value = '';
        document.getElementById('creditDescription').value = '';
        document.getElementById('creditModal').classList.remove('hidden');
    }

    function closeCreditModal() {
        document.getElementById('creditModal').classList.add('hidden');
    }

    async function submitCredit() {
        const userId = document.getElementById('creditUserId').value;
        const amount = document.getElementById('creditAmount').value;
        const description = document.getElementById('creditDescription').value;

        if (!amount || amount < 1) {
            alert('Veuillez saisir un montant valide.');
            return;
        }

        const btn = document.getElementById('creditSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'En cours...';

        try {
            const response = await fetch(`{{ url('admin/wallets') }}/${userId}/credit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ amount: parseInt(amount), description }),
            });
            const data = await response.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Erreur');
            }
        } catch (e) {
            alert('Erreur réseau.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Créditer';
        }
    }

    // ===== Modal Crédit en masse =====
    function openBulkCreditModal() {
        const ids = getSelectedUserIds();
        document.getElementById('bulkCreditInfo').textContent = `Créditer ${ids.length} employé(s) sélectionné(s)`;
        document.getElementById('bulkCreditAmount').value = '';
        document.getElementById('bulkCreditDescription').value = '';
        document.getElementById('bulkCreditModal').classList.remove('hidden');
    }

    function closeBulkCreditModal() {
        document.getElementById('bulkCreditModal').classList.add('hidden');
    }

    async function submitBulkCredit() {
        const userIds = getSelectedUserIds();
        const amount = document.getElementById('bulkCreditAmount').value;
        const description = document.getElementById('bulkCreditDescription').value;

        if (!amount || amount < 1) {
            alert('Veuillez saisir un montant valide.');
            return;
        }

        if (!confirm(`Vous allez créditer ${userIds.length} employé(s) de ${parseInt(amount).toLocaleString('fr-FR')} FCFA chacun. Total: ${(parseInt(amount) * userIds.length).toLocaleString('fr-FR')} FCFA. Confirmer ?`)) {
            return;
        }

        const btn = document.getElementById('bulkCreditSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'En cours...';

        try {
            const response = await fetch('{{ route("admin.wallets.credit-multiple") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    user_ids: userIds,
                    amount: parseInt(amount),
                    description,
                }),
            });
            const data = await response.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Erreur');
            }
        } catch (e) {
            alert('Erreur réseau.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Créditer la sélection';
        }
    }

    // ===== Modal Transactions =====
    function showTransactions(userId, name) {
        document.getElementById('transactionsTitle').textContent = `Transactions - ${name}`;
        document.getElementById('transactionsContent').innerHTML = '<div class="text-center text-gray-400 py-8"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Chargement...</p></div>';
        document.getElementById('transactionsModal').classList.remove('hidden');

        fetch(`{{ url('admin/wallets') }}/${userId}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('transactionsContent').innerHTML = '<p class="text-red-500 text-center">Erreur de chargement.</p>';
                return;
            }

            let html = `<div class="mb-4 p-4 bg-blue-50 rounded-lg">
                <p class="text-lg font-bold text-blue-800">Solde: ${parseInt(data.wallet.balance).toLocaleString('fr-FR')} FCFA</p>
            </div>`;

            if (data.transactions.length === 0) {
                html += '<p class="text-center text-gray-500 py-4">Aucune transaction</p>';
            } else {
                html += '<div class="space-y-3">';
                data.transactions.forEach(t => {
                    const isCredit = t.type === 'credit';
                    const icon = isCredit ? 'fa-arrow-down text-green-500' : (t.type === 'transfer' ? 'fa-paper-plane text-purple-500' : 'fa-arrow-up text-red-500');
                    const sign = isCredit ? '+' : '-';
                    const color = isCredit ? 'text-green-600' : 'text-red-600';
                    const date = new Date(t.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

                    html += `<div class="flex items-center justify-between p-3 border rounded-lg">
                        <div class="flex items-center gap-3">
                            <i class="fas ${icon} text-lg"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-800">${t.description || t.source_type}</p>
                                <p class="text-xs text-gray-500">${date} - ${t.reference || ''}</p>
                                ${t.transfer_phone ? `<p class="text-xs text-gray-500"><i class="fas fa-phone"></i> ${t.transfer_phone}</p>` : ''}
                                ${t.elgiopay_status ? `<span class="text-xs px-1 py-0.5 rounded bg-gray-100">${t.elgiopay_status}</span>` : ''}
                            </div>
                        </div>
                        <span class="font-bold ${color}">${sign}${parseInt(t.amount).toLocaleString('fr-FR')} FCFA</span>
                    </div>`;
                });
                html += '</div>';
            }

            document.getElementById('transactionsContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('transactionsContent').innerHTML = '<p class="text-red-500 text-center">Erreur réseau.</p>';
        });
    }

    function closeTransactionsModal() {
        document.getElementById('transactionsModal').classList.add('hidden');
    }

    // ===== Rechargement ElgioPay =====
    function openTopupModal() {
        document.getElementById('topupPhone').value = '';
        document.getElementById('topupAmount').value = '';
        document.getElementById('topupMethodDetected').textContent = '';
        document.getElementById('topupModal').classList.remove('hidden');
    }

    function closeTopupModal() {
        document.getElementById('topupModal').classList.add('hidden');
    }

    function detectTopupMethod() {
        const phone = document.getElementById('topupPhone').value.replace(/\s/g, '');
        const el = document.getElementById('topupMethodDetected');
        if (phone.match(/^(67|650|651|652|653|654|68)/)) {
            el.innerHTML = '<i class="fas fa-check-circle text-yellow-500"></i> MTN Mobile Money détecté';
        } else if (phone.match(/^(69|655|656|657|658|659)/)) {
            el.innerHTML = '<i class="fas fa-check-circle text-orange-500"></i> Orange Money détecté';
        } else {
            el.textContent = '';
        }
    }

    async function pollTopupStatus(transactionId, btn) {
        let attempts = 0;
        const maxAttempts = 12; // 60 secondes max (12 x 5s)

        const interval = setInterval(async () => {
            attempts++;
            try {
                const response = await fetch(`{{ url('admin/wallets-elgiopay-payment-status') }}/${transactionId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (data.success) {
                    const status = (data.status || '').toLowerCase();
                    if (status === 'successful' || status === 'completed' || status === 'success') {
                        clearInterval(interval);
                        closeTopupModal();
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Envoyer la demande';
                        alert('Rechargement réussi ! Votre compte ElgioPay a été crédité.');
                        loadElgioPayBalance();
                    } else if (status === 'failed' || status === 'cancelled' || status === 'rejected') {
                        clearInterval(interval);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Envoyer la demande';
                        alert('Le paiement a échoué ou a été annulé. Statut: ' + status);
                    } else {
                        btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>En attente... (${attempts * 5}s)`;
                    }
                }
            } catch (e) {
                // Ignorer les erreurs réseau temporaires
            }

            if (attempts >= maxAttempts) {
                clearInterval(interval);
                closeTopupModal();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Envoyer la demande';
                alert('Le délai d\'attente est dépassé. Vérifiez votre téléphone et le solde ElgioPay.');
                loadElgioPayBalance();
            }
        }, 5000);
    }

    async function submitTopup() {
        const phone = document.getElementById('topupPhone').value.trim();
        const amount = document.getElementById('topupAmount').value;

        if (!phone || phone.length < 9) {
            alert('Veuillez saisir un numéro de téléphone valide.');
            return;
        }
        if (!amount || amount < 100) {
            alert('Le montant minimum est de 100 FCFA.');
            return;
        }

        if (!confirm(`Vous allez recharger ${parseInt(amount).toLocaleString('fr-FR')} FCFA depuis le numéro ${phone}. Une demande de paiement sera envoyée sur ce numéro. Confirmer ?`)) {
            return;
        }

        const btn = document.getElementById('topupSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>En cours...';

        try {
            const response = await fetch('{{ route("admin.wallets.elgiopay-topup") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ phone, amount: parseInt(amount) }),
            });

            const data = await response.json();
            if (data.success) {
                // Afficher le suivi dans le modal
                const transactionId = data.transaction_id;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>En attente de confirmation...';

                if (transactionId) {
                    pollTopupStatus(transactionId, btn);
                } else {
                    closeTopupModal();
                    alert(data.message);
                    setTimeout(loadElgioPayBalance, 5000);
                }
            } else {
                alert(data.message || 'Erreur lors du rechargement.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Envoyer la demande';
            }
        } catch (e) {
            alert('Erreur réseau.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Envoyer la demande';
        }
    }
</script>
@endpush
