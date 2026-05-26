@extends('layouts.admin')

@section('title', 'Employés')
@section('page-title', 'Gestion des Employés')

@section('content')
<div class="space-y-6">
    <!-- Header avec boutons d'action -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Liste des Employés</h2>
            <p class="text-gray-600 mt-1">Gérez les employés et leurs accès</p>
        </div>
        <div class="flex gap-3">
            <form method="POST" action="{{ route('admin.employees.clear-device-locks') }}" class="inline" onsubmit="return confirm('Libérer TOUS les verrous d\'appareils du jour ?\n\nCela permettra à tous les employés de se reconnecter sur n\'importe quel téléphone.\n\nContinuer ?');">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition">
                    <i class="fas fa-unlock mr-2"></i>
                    Libérer tous les appareils
                </button>
            </form>
            <a href="{{ route('admin.employees.export-pdf', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-file-pdf mr-2"></i>
                Télécharger PDF
            </a>
            <a href="{{ route('admin.employees.print-by-bank') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-university mr-2"></i>
                Imprimer par Banque
            </a>
            <a href="{{ route('admin.employees.import-form') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-file-import mr-2"></i>
                Importer CSV/Excel
            </a>
            <button onclick="document.getElementById('bulkCampusModal').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-building mr-2"></i>
                Attribuer Campus
            </button>
            <button onclick="document.getElementById('bulkDepartmentModal').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-sitemap mr-2"></i>
                Attribuer Département
            </button>
            <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>
                Nouvel Employé
            </a>
        </div>
    </div>

    <!-- Filtres et Recherche -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.employees.index') }}" id="searchForm" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Recherche -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input
                    type="text"
                    name="search"
                    id="searchInput"
                    value="{{ request('search') }}"
                    placeholder="Nom, email, ID employé..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    autocomplete="off"
                >
            </div>

            <!-- Filtre Type d'employé -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type d'employé</label>
                <select name="employee_type" class="filter-select w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les types</option>
                    <option value="enseignant_titulaire" {{ request('employee_type') == 'enseignant_titulaire' ? 'selected' : '' }}>Personnel Permanent</option>
                    <option value="semi_permanent" {{ request('employee_type') == 'semi_permanent' ? 'selected' : '' }}>Personnel Semi-Permanent</option>
                    <option value="enseignant_vacataire" {{ request('employee_type') == 'enseignant_vacataire' ? 'selected' : '' }}>Vacataire</option>
                    <option value="administratif" {{ request('employee_type') == 'administratif' ? 'selected' : '' }}>Administratif</option>
                    <option value="technique" {{ request('employee_type') == 'technique' ? 'selected' : '' }}>Technique</option>
                    <option value="direction" {{ request('employee_type') == 'direction' ? 'selected' : '' }}>Direction</option>
                </select>
            </div>

            <!-- Filtre Campus -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Campus</label>
                <select name="campus" class="filter-select w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les campus</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtre Banque -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Banque</label>
                <select name="banque" class="filter-select w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes les banques</option>
                    @foreach($banques as $banque)
                        <option value="{{ $banque }}" {{ request('banque') == $banque ? 'selected' : '' }}>
                            {{ $banque }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtre Statut -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="filter-select w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Actifs</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactifs</option>
                </select>
            </div>

            <!-- Boutons -->
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Rechercher
                </button>
                <a href="{{ route('admin.employees.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Table des employés -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Employé
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Poste
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type d'employé
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campus
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Appareil
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $employee)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                @if($employee->photo_url)
                                    <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/' . $employee->photo_url) }}" alt="">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-bold">{{ substr($employee->first_name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $employee->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">{{ $employee->employee_id }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">{{ $employee->jobPosition->name ?? '-' }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $employeeTypeLabels = [
                                'enseignant_titulaire' => 'Personnel Permanent',
                                'semi_permanent' => 'Personnel Semi-Permanent',
                                'enseignant_vacataire' => 'Vacataire',
                                'administratif' => 'Administratif',
                                'technique' => 'Technique',
                                'direction' => 'Direction',
                            ];
                            $typeLabel = $employeeTypeLabels[$employee->employee_type] ?? 'Non défini';

                            $badgeColors = [
                                'enseignant_titulaire' => 'bg-green-100 text-green-800',
                                'semi_permanent' => 'bg-blue-100 text-blue-800',
                                'enseignant_vacataire' => 'bg-purple-100 text-purple-800',
                                'administratif' => 'bg-yellow-100 text-yellow-800',
                                'technique' => 'bg-orange-100 text-orange-800',
                                'direction' => 'bg-red-100 text-red-800',
                            ];
                            $badgeColor = $badgeColors[$employee->employee_type] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeColor }}">
                            {{ $typeLabel }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            @if($employee->campuses->count() > 0)
                                @foreach($employee->campuses as $campus)
                                    <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded mr-1 mb-1">
                                        {{ $campus->name }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-gray-400">Aucun campus</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($employee->device_id)
                            <div class="text-xs">
                                <div class="text-gray-900">{{ $employee->device_model }}</div>
                                <div class="text-gray-500">{{ $employee->device_os }}</div>
                            </div>
                        @else
                            <span class="text-xs text-gray-400">Non configuré</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($employee->is_active)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Actif
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Inactif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.employees.show', $employee->id) }}" class="text-blue-600 hover:text-blue-900" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.employees.edit', $employee->id) }}" class="text-green-600 hover:text-green-900" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($employee->device_id)
                            <form method="POST" action="{{ route('admin.employees.reset-device', $employee->id) }}" class="inline" onsubmit="return confirm('Réinitialiser l\'appareil de cet employé ?');">
                                @csrf
                                <button type="submit" class="text-orange-600 hover:text-orange-900" title="Réinitialiser appareil">
                                    <i class="fas fa-mobile-alt"></i>
                                </button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.employees.destroy', $employee->id) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-gray-400">
                            <i class="fas fa-users text-6xl mb-4"></i>
                            <p class="text-lg">Aucun employé trouvé</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($employees->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $employees->links() }}
        </div>
        @endif
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total Employés</div>
            <div class="text-2xl font-bold text-gray-800">{{ $employees->total() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Sur cette page</div>
            <div class="text-2xl font-bold text-gray-800">{{ $employees->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Page</div>
            <div class="text-2xl font-bold text-gray-800">{{ $employees->currentPage() }} / {{ $employees->lastPage() }}</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialiser Choices.js sur les select pour les rendre searchable
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier si Choices.js est chargé
        if (typeof Choices === 'undefined') {
            console.error('❌ Choices.js not loaded!');
            return;
        }

        const filterSelects = document.querySelectorAll('.filter-select');
        console.log('🔍 Found', filterSelects.length, 'filter selects');

        filterSelects.forEach(function(select) {
            try {
                const choices = new Choices(select, {
                    searchEnabled: true,
                    searchPlaceholderValue: 'Rechercher...',
                    noResultsText: 'Aucun résultat',
                    itemSelectText: 'Cliquer pour sélectionner',
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: select.querySelector('option[value=""]')?.textContent || 'Sélectionner...'
                });

                console.log('✅ Choices initialized on', select.name);

                // Auto-submit quand on change la sélection
                select.addEventListener('change', function() {
                    document.getElementById('searchForm').submit();
                });
            } catch (error) {
                console.error('❌ Error initializing Choices on', select.name, ':', error);
            }
        });
    });

    // Recherche en temps réel avec debounce (champ texte)
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);

            // Attendre 500ms après que l'utilisateur arrête de taper
            searchTimeout = setTimeout(function() {
                searchForm.submit();
            }, 500);
        });
    }
</script>
@endpush

<!-- Modal : Attribuer un campus à tout le personnel -->
@push('scripts')
<script>
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('bulkCampusModal').classList.add('hidden');
        document.getElementById('bulkDepartmentModal').classList.add('hidden');
    }
});
</script>
@endpush

<!-- Modal : Attribuer un campus à tout le personnel -->
<div id="bulkCampusModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Attribuer un campus à tout le personnel</h3>
                <p class="text-xs text-gray-500 mt-0.5">Le campus sera ajouté à tous les employés actifs</p>
            </div>
            <button onclick="document.getElementById('bulkCampusModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form action="{{ route('admin.employees.bulk-assign-campus') }}" method="POST">
            @csrf
            <div class="px-6 py-5 space-y-4">

                <!-- Sélection du campus -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Campus à attribuer <span class="text-red-500">*</span>
                    </label>
                    <select name="campus_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">— Sélectionner un campus —</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtre type d'employé (optionnel) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Appliquer à (optionnel)
                    </label>
                    <select name="employee_type"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">Tous les types d'employés</option>
                        <option value="administratif">Administratif</option>
                        <option value="technique">Technique</option>
                        <option value="direction">Direction</option>
                        <option value="enseignant_titulaire">Enseignant Titulaire</option>
                        <option value="enseignant_vacataire">Enseignant Vacataire</option>
                        <option value="semi_permanent">Semi-permanent</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Laisser vide pour appliquer à tout le personnel actif</p>
                </div>

                <!-- Option : définir comme campus principal -->
                <div class="flex items-center">
                    <input type="checkbox" name="set_as_primary" id="set_as_primary" value="1"
                           class="w-4 h-4 text-teal-600 border-gray-300 rounded">
                    <label for="set_as_primary" class="ml-2 text-sm text-gray-700">
                        Définir comme campus principal pour les employés qui n'en ont pas encore
                    </label>
                </div>
            </div>

            <div class="px-6 py-4 border-t bg-gray-50 rounded-b-xl flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('bulkCampusModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                    Annuler
                </button>
                <button type="submit"
                        onclick="return confirm('Attribuer ce campus à tous les employés sélectionnés ?\n\nLes employés qui l\'ont déjà ne seront pas affectés.')"
                        class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition font-semibold">
                    <i class="fas fa-building mr-2"></i> Attribuer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal : Attribuer un département à tout le personnel -->
<div id="bulkDepartmentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Attribuer un département en masse</h3>
                <p class="text-xs text-gray-500 mt-0.5">Le département sera attribué à tous les employés actifs sélectionnés</p>
            </div>
            <button onclick="document.getElementById('bulkDepartmentModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form action="{{ route('admin.employees.bulk-assign-department') }}" method="POST">
            @csrf
            <div class="px-6 py-5 space-y-4">

                <!-- Sélection du département -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Département à attribuer <span class="text-red-500">*</span>
                    </label>
                    <select name="department_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">— Sélectionner un département —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}{{ $dept->code ? ' ('.$dept->code.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtre type d'employé (optionnel) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Appliquer à (optionnel)
                    </label>
                    <select name="employee_type"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Tous les types d'employés</option>
                        <option value="administratif">Administratif</option>
                        <option value="technique">Technique</option>
                        <option value="direction">Direction</option>
                        <option value="enseignant_titulaire">Enseignant Titulaire</option>
                        <option value="enseignant_vacataire">Enseignant Vacataire</option>
                        <option value="semi_permanent">Semi-permanent</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Laisser vide pour appliquer à tout le personnel actif</p>
                </div>

                <!-- Option : écraser le département existant -->
                <div class="flex items-center">
                    <input type="checkbox" name="overwrite" id="overwrite_dept" value="1"
                           class="w-4 h-4 text-purple-600 border-gray-300 rounded">
                    <label for="overwrite_dept" class="ml-2 text-sm text-gray-700">
                        Écraser le département des employés qui en ont déjà un
                    </label>
                </div>
                <p class="text-xs text-orange-500">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Sans cette option, seuls les employés sans département seront affectés.
                </p>
            </div>

            <div class="px-6 py-4 border-t bg-gray-50 rounded-b-xl flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('bulkDepartmentModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                    Annuler
                </button>
                <button type="submit"
                        onclick="return confirm('Attribuer ce département à tous les employés sélectionnés ?')"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition font-semibold">
                    <i class="fas fa-sitemap mr-2"></i> Attribuer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
