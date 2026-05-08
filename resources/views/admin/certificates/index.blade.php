@extends('layouts.admin')

@section('title', 'Attestations de Travail')
@section('page-title', 'Attestations de Travail')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Demandes d'Attestation</h2>
            <p class="text-gray-600 mt-1">Generer et gerer les attestations des employes</p>
        </div>
        @if($pendingCount > 0)
        <span class="px-4 py-2 bg-orange-100 text-orange-800 rounded-lg font-semibold">
            {{ $pendingCount }} en attente
        </span>
        @endif
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, matricule..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tous</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="generated" {{ request('status') == 'generated' ? 'selected' : '' }}>Generee</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejetee</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.certificates.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motif</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date demande</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($requests as $req)
                <tr class="{{ $req->status === 'pending' ? 'bg-yellow-50' : '' }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $req->user->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $req->user->employee_id }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $req->type_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ $req->purpose ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($req->status === 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">En attente</span>
                        @elseif($req->status === 'generated')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Generee</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejetee</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($req->status === 'pending')
                        <form action="{{ route('admin.certificates.generate', $req->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-800 mr-2" onclick="return confirm('Generer cette attestation ?')">
                                <i class="fas fa-file-pdf"></i> Generer
                            </button>
                        </form>
                        <button onclick="openRejectCertModal({{ $req->id }})" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i> Rejeter
                        </button>
                        @elseif($req->status === 'generated' && $req->file_path)
                        <a href="{{ Storage::url($req->file_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-download"></i> Telecharger
                        </a>
                        <span class="text-gray-400 text-xs ml-2">
                            {{ $req->generator?->full_name }} - {{ $req->generated_at?->format('d/m/Y') }}
                        </span>
                        @else
                        <span class="text-gray-400 text-xs">Rejetee</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">Aucune demande d'attestation.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4">{{ $requests->appends(request()->query())->links() }}</div>
    </div>
</div>

<!-- Modal rejet -->
<div id="rejectCertModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Rejeter la demande</h3>
        <form id="rejectCertForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motif du refus *</label>
                <textarea name="comment" rows="3" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="Expliquez la raison du refus..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeRejectCertModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg">Annuler</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Rejeter</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectCertModal(id) {
    document.getElementById('rejectCertForm').action = '/admin/certificates/' + id + '/reject';
    document.getElementById('rejectCertModal').classList.remove('hidden');
}
function closeRejectCertModal() {
    document.getElementById('rejectCertModal').classList.add('hidden');
}
</script>
@endsection
