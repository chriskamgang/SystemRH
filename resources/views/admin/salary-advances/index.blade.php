@extends('layouts.admin')

@section('title', 'Demandes d\'Avance sur Salaire')
@section('page-title', 'Demandes d\'Avance sur Salaire')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Demandes d'Avance sur Salaire</h2>
            <p class="text-gray-600 mt-1">Gérez les demandes d'avance des employés</p>
        </div>
        <div class="flex gap-3 items-center">
            <a href="{{ route('admin.salary-advances.export-pdf', request()->query()) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold">
                <i class="fas fa-file-pdf mr-2"></i> Télécharger PDF
            </a>
            @if($pendingCount > 0)
            <span class="px-4 py-2 bg-orange-100 text-orange-800 rounded-lg font-semibold">
                {{ $pendingCount }} en attente
            </span>
            @endif
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.salary-advances.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approuvée</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejetée</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.salary-advances.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motif</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($requests as $req)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $req->user->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $req->user->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-semibold text-gray-900">{{ number_format($req->amount, 0, ',', '.') }} FCFA</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-700 max-w-xs truncate">{{ $req->reason }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $req->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4">
                        @if($req->status == 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">En attente</span>
                        @elseif($req->status == 'approved')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approuvée</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejetée</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($req->status == 'pending')
                        <div class="flex gap-2">
                            <button onclick="openApproveModal({{ $req->id }}, '{{ $req->user->full_name }}', {{ $req->amount }})" class="px-3 py-1 text-xs bg-green-600 text-white rounded-full hover:bg-green-700">
                                Approuver
                            </button>
                            <button onclick="openRejectModal({{ $req->id }})" class="px-3 py-1 text-xs bg-red-600 text-white rounded-full hover:bg-red-700">
                                Rejeter
                            </button>
                        </div>
                        @else
                        <div class="text-xs text-gray-500">
                            @if($req->reviewer)
                                Par {{ $req->reviewer->full_name }}
                            @endif
                            @if($req->admin_note)
                                <br><em>{{ $req->admin_note }}</em>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-money-check-alt text-4xl mb-4 block text-gray-300"></i>
                        Aucune demande trouvée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $requests->links() }}
        </div>
    </div>
</div>

<!-- Modal Approuver -->
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Approuver l'avance</h3>
            <p id="approveInfo" class="text-gray-600 mb-4"></p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant mensuel de remboursement (FCFA) *</label>
                    <input type="number" id="monthlyAmount" min="1000" step="500" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Montant qui sera déduit chaque mois du salaire</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note (optionnel)</label>
                    <textarea id="approveNote" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeApproveModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">Annuler</button>
                <button onclick="submitApprove()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Approuver</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rejeter -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Rejeter la demande</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motif du rejet *</label>
                <textarea id="rejectNote" rows="3" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Expliquez la raison du rejet..."></textarea>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeRejectModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">Annuler</button>
                <button onclick="submitReject()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Rejeter</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentRequestId = null;

    function openApproveModal(id, name, amount) {
        currentRequestId = id;
        document.getElementById('approveInfo').textContent = `Approuver l'avance de ${amount.toLocaleString('fr-FR')} FCFA pour ${name} ?`;
        document.getElementById('monthlyAmount').value = '';
        document.getElementById('approveNote').value = '';
        document.getElementById('approveModal').classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('approveModal').classList.add('hidden');
    }

    function openRejectModal(id) {
        currentRequestId = id;
        document.getElementById('rejectNote').value = '';
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }

    async function submitApprove() {
        const monthlyAmount = document.getElementById('monthlyAmount').value;
        if (!monthlyAmount || monthlyAmount < 1000) {
            alert('Veuillez saisir le montant mensuel de remboursement (minimum 1000 FCFA).');
            return;
        }

        try {
            const response = await fetch(`{{ url('admin/salary-advances') }}/${currentRequestId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    monthly_amount: parseInt(monthlyAmount),
                    admin_note: document.getElementById('approveNote').value,
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
        }
    }

    async function submitReject() {
        const note = document.getElementById('rejectNote').value;
        if (!note.trim()) {
            alert('Veuillez saisir le motif du rejet.');
            return;
        }

        try {
            const response = await fetch(`{{ url('admin/salary-advances') }}/${currentRequestId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ admin_note: note }),
            });
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erreur');
            }
        } catch (e) {
            alert('Erreur réseau.');
        }
    }
</script>
@endpush
