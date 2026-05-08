@extends('layouts.admin')

@section('title', 'Gestion CNPS')
@section('page-title', 'Gestion CNPS')

@section('content')
<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestion CNPS</h2>
            <p class="text-gray-600 mt-1">Suivi des affiliations et cotisations CNPS des employés</p>
        </div>
        <button onclick="openAddRecordModal()"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold">
            <i class="fas fa-plus mr-2"></i>Nouvelle fiche CNPS
        </button>
    </div>

    {{-- Alertes --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Cartes statistiques --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Total employés</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalEmployees }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Affiliés CNPS</p>
                    <p class="text-3xl font-bold text-green-700 mt-1">{{ $totalEnrolled }}</p>
                    @if($totalEmployees > 0)
                    <p class="text-xs text-gray-400 mt-1">
                        {{ round(($totalEnrolled / $totalEmployees) * 100) }}% du personnel
                    </p>
                    @endif
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-id-card text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Cotisations ce mois</p>
                    <p class="text-3xl font-bold text-indigo-700 mt-1">{{ $contributionsThisMonth }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ now()->isoFormat('MMMM YYYY') }}</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-3">
                    <i class="fas fa-file-invoice-dollar text-indigo-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Montant total ce mois</p>
                    <p class="text-2xl font-bold text-orange-700 mt-1">
                        {{ number_format($totalContributionsThisMonth, 0, ',', ' ') }} FCFA
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Part salariale + patronale</p>
                </div>
                <div class="bg-orange-100 rounded-full p-3">
                    <i class="fas fa-coins text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de filtre et recherche --}}
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.cnps.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Nom, prénom, matricule..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut CNPS</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les employés</option>
                    <option value="enrolled"     {{ request('status') === 'enrolled'     ? 'selected' : '' }}>Affiliés</option>
                    <option value="not_enrolled" {{ request('status') === 'not_enrolled' ? 'selected' : '' }}>Non affiliés</option>
                    <option value="active"       {{ request('status') === 'active'       ? 'selected' : '' }}>Actif</option>
                    <option value="inactive"     {{ request('status') === 'inactive'     ? 'selected' : '' }}>Inactif</option>
                    <option value="suspended"    {{ request('status') === 'suspended'    ? 'selected' : '' }}>Suspendu</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.cnps.index') }}"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Tableau principal --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employé</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Numéro CNPS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'affiliation</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cotisations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $employee)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center mr-3 flex-shrink-0">
                                @if($employee->photo)
                                    <img src="{{ asset('storage/' . $employee->photo) }}"
                                        class="h-9 w-9 rounded-full object-cover" alt="">
                                @else
                                    <span class="text-blue-700 font-semibold text-sm">
                                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $employee->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $employee->employee_id }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($employee->cnpsRecord)
                            <span class="font-mono text-sm font-semibold text-gray-800">
                                {{ $employee->cnpsRecord->cnps_number }}
                            </span>
                        @else
                            <span class="text-gray-400 text-sm italic">Non renseigné</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        @if($employee->cnpsRecord && $employee->cnpsRecord->registration_date)
                            {{ $employee->cnpsRecord->registration_date->format('d/m/Y') }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if(!$employee->cnpsRecord)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                Non affilié
                            </span>
                        @elseif($employee->cnpsRecord->status === 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Actif
                            </span>
                        @elseif($employee->cnpsRecord->status === 'inactive')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Inactif
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                Suspendu
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        @if($employee->cnpsRecord)
                            @php
                                $contribCount = $employee->cnpsContributions()->count();
                                $contribTotal = $employee->cnpsContributions()->sum('total_contribution');
                            @endphp
                            <span class="font-semibold">{{ $contribCount }}</span>
                            <span class="text-gray-400"> versement(s)</span>
                            @if($contribTotal > 0)
                            <div class="text-xs text-gray-400">
                                {{ number_format($contribTotal, 0, ',', ' ') }} FCFA cumulés
                            </div>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.cnps.show', $employee->id) }}"
                                class="text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-eye mr-1"></i>Voir
                            </a>
                            @if(!$employee->cnpsRecord)
                            <button onclick="openAddRecordModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}')"
                                class="text-green-600 hover:text-green-800 font-medium">
                                <i class="fas fa-plus mr-1"></i>Affilier
                            </button>
                            @else
                            <button onclick="openAddRecordModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}', '{{ $employee->cnpsRecord->cnps_number }}', '{{ $employee->cnpsRecord->registration_date->format('Y-m-d') }}', '{{ $employee->cnpsRecord->status }}')"
                                class="text-indigo-600 hover:text-indigo-800 font-medium">
                                <i class="fas fa-edit mr-1"></i>Modifier
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-search text-gray-300 text-4xl mb-3 block"></i>
                        Aucun employé trouvé.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $employees->appends(request()->query())->links() }}
        </div>
    </div>
</div>

{{-- Modal : Créer / modifier une fiche CNPS --}}
<div id="addRecordModal"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
        <div class="flex justify-between items-center mb-5">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-900">Nouvelle fiche CNPS</h3>
            <button onclick="closeAddRecordModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="addRecordForm" method="POST" action="{{ route('admin.cnps.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Employé <span class="text-red-500">*</span></label>
                <select id="modalUserId" name="user_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Sélectionner un employé --</option>
                    @foreach($employeesWithoutRecord as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->full_name }} ({{ $emp->employee_id }})
                        </option>
                    @endforeach
                </select>
                {{-- Champ caché utilisé quand on édite depuis le tableau --}}
                <input type="hidden" id="modalUserIdHidden" name="" value="">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Numéro CNPS <span class="text-red-500">*</span></label>
                <input type="text" id="modalCnpsNumber" name="cnps_number" required
                    placeholder="Ex: 0123456789"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono uppercase">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date d'affiliation <span class="text-red-500">*</span></label>
                <input type="date" id="modalRegistrationDate" name="registration_date" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut <span class="text-red-500">*</span></label>
                <select id="modalStatus" name="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                    <option value="suspended">Suspendu</option>
                </select>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeAddRecordModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    Annuler
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Ouvre le modal d'ajout/modification d'une fiche CNPS.
 * Quand employeeId est fourni, on pré-remplit les champs et on verrouille le sélecteur.
 */
function openAddRecordModal(employeeId, employeeName, cnpsNumber, registrationDate, status) {
    const modal       = document.getElementById('addRecordModal');
    const title       = document.getElementById('modalTitle');
    const selectEl    = document.getElementById('modalUserId');
    const hiddenEl    = document.getElementById('modalUserIdHidden');
    const cnpsEl      = document.getElementById('modalCnpsNumber');
    const dateEl      = document.getElementById('modalRegistrationDate');
    const statusEl    = document.getElementById('modalStatus');

    if (employeeId) {
        // Mode édition d'un employé spécifique
        title.textContent = 'Fiche CNPS — ' + employeeName;

        // Masquer le sélecteur et utiliser un champ caché à la place
        selectEl.closest('div').style.display = 'none';
        hiddenEl.name  = 'user_id';
        hiddenEl.value = employeeId;
        selectEl.name  = '';           // désactiver le select pour ne pas soumettre deux fois

        if (cnpsNumber)       cnpsEl.value   = cnpsNumber;
        if (registrationDate) dateEl.value   = registrationDate;
        if (status)           statusEl.value = status;
    } else {
        // Mode ajout générique
        title.textContent  = 'Nouvelle fiche CNPS';
        selectEl.closest('div').style.display = '';
        selectEl.name  = 'user_id';
        hiddenEl.name  = '';
        hiddenEl.value = '';
        cnpsEl.value   = '';
        dateEl.value   = '';
        statusEl.value = 'active';
    }

    modal.classList.remove('hidden');
}

function closeAddRecordModal() {
    document.getElementById('addRecordModal').classList.add('hidden');
}

// Fermer si clic en dehors du contenu
document.getElementById('addRecordModal').addEventListener('click', function (e) {
    if (e.target === this) closeAddRecordModal();
});
</script>
@endsection
