@extends('layouts.admin')

@section('title', 'Fiche CNPS — ' . $user->full_name)
@section('page-title', 'Fiche CNPS')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Fil d'Ariane --}}
    <a href="{{ route('admin.cnps.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste CNPS
    </a>

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

    {{-- Carte identité employé + fiche CNPS --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">

            {{-- Infos employé --}}
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                    @if($user->photo)
                        <img src="{{ asset('storage/' . $user->photo) }}"
                            class="h-16 w-16 object-cover" alt="{{ $user->full_name }}">
                    @else
                        <span class="text-blue-700 font-bold text-2xl">
                            {{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                        </span>
                    @endif
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $user->full_name }}</h2>
                    <p class="text-gray-500 text-sm">{{ $user->employee_id }}</p>
                    @if($user->department)
                        <p class="text-gray-500 text-sm">{{ $user->department->name }}</p>
                    @endif
                    <p class="text-gray-400 text-xs mt-1">
                        @switch($user->employee_type)
                            @case('permanent')       Permanent @break
                            @case('semi_permanent')  Semi-permanent @break
                            @case('enseignant_vacataire') Vacataire @break
                            @case('enseignant_titulaire') Titulaire @break
                            @default {{ $user->employee_type }}
                        @endswitch
                    </p>
                </div>
            </div>

            {{-- Fiche CNPS --}}
            @if($cnpsRecord)
            <div class="flex-1 grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Numéro CNPS</p>
                    <p class="font-mono font-bold text-gray-900 mt-0.5">{{ $cnpsRecord->cnps_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Date d'affiliation</p>
                    <p class="font-medium text-gray-800 mt-0.5">{{ $cnpsRecord->registration_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Statut</p>
                    <div class="mt-0.5">
                        @if($cnpsRecord->status === 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actif</span>
                        @elseif($cnpsRecord->status === 'inactive')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Inactif</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Suspendu</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-start">
                <button onclick="openEditRecordModal()"
                    class="px-3 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition text-sm font-medium">
                    <i class="fas fa-edit mr-1"></i>Modifier la fiche
                </button>
            </div>
            @else
            <div class="flex items-center">
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm mr-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Cet employé n'est pas encore affilié à la CNPS.
                </div>
                <button onclick="openEditRecordModal()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    <i class="fas fa-plus mr-1"></i>Créer la fiche
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- Cartes de synthèse des cotisations --}}
    @if($cnpsRecord)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500">Part salariale totale</p>
            <p class="text-2xl font-bold text-blue-700 mt-1">
                {{ number_format($totalEmployeeContrib, 0, ',', ' ') }} FCFA
            </p>
            <p class="text-xs text-gray-400 mt-1">Taux : {{ (App\Models\CnpsContribution::EMPLOYEE_RATE * 100) }}%</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500">Part patronale totale</p>
            <p class="text-2xl font-bold text-purple-700 mt-1">
                {{ number_format($totalEmployerContrib, 0, ',', ' ') }} FCFA
            </p>
            <p class="text-xs text-gray-400 mt-1">
                PF {{ (App\Models\CnpsContribution::EMPLOYER_RATE_PF * 100) }}% +
                AT {{ (App\Models\CnpsContribution::EMPLOYER_RATE_AT * 100) }}% +
                Vieil. {{ (App\Models\CnpsContribution::EMPLOYER_RATE_OLD_AGE * 100) }}%
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
            <p class="text-sm text-gray-500">Total global versé</p>
            <p class="text-2xl font-bold text-green-700 mt-1">
                {{ number_format($totalContributions, 0, ',', ' ') }} FCFA
            </p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $user->cnpsContributions->count() }} mois enregistré(s)
            </p>
        </div>
    </div>

    {{-- Bouton + titre tableau cotisations --}}
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800">Historique des cotisations</h3>
        <button onclick="openAddContributionModal()"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-semibold text-sm">
            <i class="fas fa-plus mr-2"></i>Ajouter une cotisation
        </button>
    </div>

    {{-- Tableau des cotisations --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Période</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Salaire brut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Part salariale</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Part patronale</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                $monthNames = [
                    1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',
                    5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Août',
                    9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'
                ];
                @endphp
                @forelse($user->cnpsContributions as $contrib)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                        {{ $monthNames[$contrib->month] ?? $contrib->month }} {{ $contrib->year }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ number_format($contrib->gross_salary, 0, ',', ' ') }} FCFA
                        @if($contrib->gross_salary > App\Models\CnpsContribution::SALARY_CEILING)
                            <span class="ml-1 text-xs text-orange-600" title="Plafond appliqué">
                                <i class="fas fa-info-circle"></i>
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-700 font-medium">
                        {{ number_format($contrib->employee_contribution, 0, ',', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-700 font-medium">
                        {{ number_format($contrib->employer_contribution, 0, ',', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-700">
                        {{ number_format($contrib->total_contribution, 0, ',', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($contrib->status === 'paid')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Versé</span>
                        @elseif($contrib->status === 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">En attente</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">En retard</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                        <i class="fas fa-file-invoice-dollar text-gray-300 text-3xl mb-2 block"></i>
                        Aucune cotisation enregistrée pour le moment.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Info taux CNPS --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        <p class="font-semibold mb-1"><i class="fas fa-info-circle mr-1"></i>Taux CNPS Cameroun en vigueur</p>
        <p>Plafond mensuel : <strong>{{ number_format(App\Models\CnpsContribution::SALARY_CEILING, 0, ',', ' ') }} FCFA</strong></p>
        <p class="mt-1">
            Part salariale : <strong>{{ (App\Models\CnpsContribution::EMPLOYEE_RATE * 100) }}%</strong> (vieillesse) —
            Part patronale : <strong>{{ ((App\Models\CnpsContribution::EMPLOYER_RATE_PF + App\Models\CnpsContribution::EMPLOYER_RATE_AT + App\Models\CnpsContribution::EMPLOYER_RATE_OLD_AGE) * 100) }}%</strong>
            (PF {{ (App\Models\CnpsContribution::EMPLOYER_RATE_PF * 100) }}% +
            AT {{ (App\Models\CnpsContribution::EMPLOYER_RATE_AT * 100) }}% +
            Vieil. {{ (App\Models\CnpsContribution::EMPLOYER_RATE_OLD_AGE * 100) }}%)
        </p>
    </div>
    @endif

</div>

{{-- Modal : Modifier / créer la fiche CNPS --}}
<div id="editRecordModal"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-lg font-bold text-gray-900">
                {{ $cnpsRecord ? 'Modifier la fiche CNPS' : 'Créer la fiche CNPS' }}
            </h3>
            <button onclick="closeEditRecordModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.cnps.store') }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Numéro CNPS <span class="text-red-500">*</span></label>
                <input type="text" name="cnps_number" required
                    value="{{ $cnpsRecord ? $cnpsRecord->cnps_number : '' }}"
                    placeholder="Ex: 0123456789"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono uppercase">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date d'affiliation <span class="text-red-500">*</span></label>
                <input type="date" name="registration_date" required
                    value="{{ $cnpsRecord ? $cnpsRecord->registration_date->format('Y-m-d') : '' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut <span class="text-red-500">*</span></label>
                <select name="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="active"    {{ ($cnpsRecord && $cnpsRecord->status === 'active')    ? 'selected' : '' }}>Actif</option>
                    <option value="inactive"  {{ ($cnpsRecord && $cnpsRecord->status === 'inactive')  ? 'selected' : '' }}>Inactif</option>
                    <option value="suspended" {{ ($cnpsRecord && $cnpsRecord->status === 'suspended') ? 'selected' : '' }}>Suspendu</option>
                </select>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeEditRecordModal()"
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

{{-- Modal : Ajouter une cotisation --}}
@if($cnpsRecord)
<div id="addContributionModal"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4">
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-lg font-bold text-gray-900">Ajouter une cotisation</h3>
            <button onclick="closeAddContributionModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.cnps.add-contribution', $user->id) }}">
            @csrf

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mois <span class="text-red-500">*</span></label>
                    <select name="month" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'] as $i => $label)
                            <option value="{{ $i + 1 }}" {{ (now()->month === $i + 1) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Année <span class="text-red-500">*</span></label>
                    <select name="year" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ (now()->year === $y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Salaire brut (FCFA) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="gross_salary" id="grossSalaryInput" required
                    min="0" step="100"
                    value="{{ $user->monthly_salary ?? '' }}"
                    placeholder="Ex: 450000"
                    oninput="previewContribution(this.value)"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1">
                    Plafond CNPS : {{ number_format(App\Models\CnpsContribution::SALARY_CEILING, 0, ',', ' ') }} FCFA
                </p>
            </div>

            {{-- Aperçu du calcul --}}
            <div id="contributionPreview" class="mb-4 bg-gray-50 rounded-lg p-4 text-sm hidden">
                <p class="font-semibold text-gray-700 mb-2">Aperçu du calcul :</p>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-blue-50 rounded p-2">
                        <p class="text-xs text-blue-500">Part salariale</p>
                        <p id="previewEmployee" class="font-bold text-blue-700">0 FCFA</p>
                    </div>
                    <div class="bg-purple-50 rounded p-2">
                        <p class="text-xs text-purple-500">Part patronale</p>
                        <p id="previewEmployer" class="font-bold text-purple-700">0 FCFA</p>
                    </div>
                    <div class="bg-green-50 rounded p-2">
                        <p class="text-xs text-green-500">Total</p>
                        <p id="previewTotal" class="font-bold text-green-700">0 FCFA</p>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut <span class="text-red-500">*</span></label>
                <select name="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="paid">Versé</option>
                    <option value="pending" selected>En attente</option>
                    <option value="late">En retard</option>
                </select>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeAddContributionModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    Annuler
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    <i class="fas fa-plus mr-2"></i>Ajouter
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
function openEditRecordModal() {
    document.getElementById('editRecordModal').classList.remove('hidden');
}
function closeEditRecordModal() {
    document.getElementById('editRecordModal').classList.add('hidden');
}

function openAddContributionModal() {
    document.getElementById('addContributionModal').classList.remove('hidden');
    // Déclencher l'aperçu si un salaire est déjà rempli
    const grossInput = document.getElementById('grossSalaryInput');
    if (grossInput && grossInput.value) {
        previewContribution(grossInput.value);
    }
}
function closeAddContributionModal() {
    document.getElementById('addContributionModal').classList.add('hidden');
}

// Fermer les modals au clic en dehors
['editRecordModal', 'addContributionModal'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
    }
});

// Calcul en temps réel des cotisations CNPS Cameroun
const EMPLOYEE_RATE = {{ App\Models\CnpsContribution::EMPLOYEE_RATE }};
const EMPLOYER_RATE = {{ App\Models\CnpsContribution::EMPLOYER_RATE_PF + App\Models\CnpsContribution::EMPLOYER_RATE_AT + App\Models\CnpsContribution::EMPLOYER_RATE_OLD_AGE }};
const SALARY_CEILING = {{ App\Models\CnpsContribution::SALARY_CEILING }};

function previewContribution(grossSalary) {
    const preview = document.getElementById('contributionPreview');
    if (!preview) return;

    const salary = parseFloat(grossSalary) || 0;
    if (salary <= 0) {
        preview.classList.add('hidden');
        return;
    }

    const base      = Math.min(salary, SALARY_CEILING);
    const employee  = Math.round(base * EMPLOYEE_RATE);
    const employer  = Math.round(base * EMPLOYER_RATE);
    const total     = employee + employer;

    document.getElementById('previewEmployee').textContent = formatNumber(employee) + ' FCFA';
    document.getElementById('previewEmployer').textContent = formatNumber(employer) + ' FCFA';
    document.getElementById('previewTotal').textContent    = formatNumber(total)    + ' FCFA';

    preview.classList.remove('hidden');
}

function formatNumber(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}
</script>
@endsection
