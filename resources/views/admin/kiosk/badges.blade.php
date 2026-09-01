@extends('layouts.admin')

@section('title', 'Badges QR')
@section('page-title', 'Gestion des badges QR')

@section('content')
<div class="space-y-6">
    {{-- Filtres --}}
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, prenom ou matricule..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select name="campus_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les campus</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Appareil mobile</label>
                <select name="device_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="configured" {{ request('device_status') == 'configured' ? 'selected' : '' }}>Telephone configure</option>
                    <option value="not_configured" {{ request('device_status') == 'not_configured' ? 'selected' : '' }}>Sans telephone configure</option>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Filtrer
            </button>
            <a href="{{ route('admin.kiosk.badges') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                Reinitialiser
            </a>
        </form>
    </div>

    {{-- Actions en masse --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">
                Employes ({{ $employees->total() }})
                <span id="selection-count" class="ml-2 text-sm font-normal text-blue-600 hidden">
                    — <span id="count-value">0</span> selectionne(s)
                    <button onclick="clearSelection()" class="ml-1 text-xs text-red-500 hover:text-red-700 underline">Vider</button>
                </span>
            </h3>
            <div class="flex gap-2">
                <button onclick="generateSelected()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition text-sm">
                    <i class="fas fa-qrcode mr-1"></i>Generer QR (selection)
                </button>
                <div class="flex items-center gap-2">
                    <select id="badges-per-page" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="8">8 / page</option>
                        <option value="10" selected>10 / page</option>
                    </select>
                    <button onclick="downloadSelected()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition text-sm">
                        <i class="fas fa-download mr-1"></i>Telecharger badges (selection)
                    </button>
                </div>
            </div>
        </div>

        <form id="bulk-form-generate" method="POST" action="{{ route('admin.kiosk.badges.generate-bulk') }}">@csrf</form>
        <form id="bulk-form-download" method="POST" action="{{ route('admin.kiosk.badges.download-bulk') }}">@csrf</form>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" id="select-all" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employe</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matricule</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Badge QR</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($employees as $employee)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="employee-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded" value="{{ $employee->id }}">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($employee->photo)
                                    <img src="{{ asset('storage/' . $employee->photo) }}" class="h-8 w-8 rounded-full object-cover">
                                @else
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-sm font-bold">
                                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium text-gray-900">{{ $employee->last_name }} {{ $employee->first_name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $employee->employee_id }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">{{ $employee->employee_type }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($employee->qr_token)
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                    <i class="fas fa-check mr-1"></i>Actif
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">
                                    Non genere
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                @if(!$employee->qr_token)
                                    <form method="POST" action="{{ route('admin.kiosk.badges.generate', $employee->id) }}">
                                        @csrf
                                        <button class="text-sm px-3 py-1 rounded bg-green-100 text-green-700 hover:bg-green-200" title="Generer QR">
                                            <i class="fas fa-qrcode"></i> Generer
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.kiosk.badges.download', $employee->id) }}" class="text-sm px-3 py-1 rounded bg-blue-100 text-blue-700 hover:bg-blue-200" title="Telecharger badge PDF">
                                        <i class="fas fa-download"></i> Badge
                                    </a>
                                    <form method="POST" action="{{ route('admin.kiosk.badges.revoke', $employee->id) }}">
                                        @csrf
                                        <button class="text-sm px-3 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200" onclick="return confirm('Revoquer ce badge ? L\'employe ne pourra plus pointer avec.')" title="Revoquer">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.kiosk.badges.generate', $employee->id) }}">
                                        @csrf
                                        <button class="text-sm px-3 py-1 rounded bg-yellow-100 text-yellow-700 hover:bg-yellow-200" onclick="return confirm('Regenerer ? L\'ancien badge sera invalide.')" title="Regenerer">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $employees->withQueryString()->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Stockage des IDs sélectionnés en sessionStorage
    const STORAGE_KEY = 'badge_selected_ids';

    function getStoredIds() {
        try {
            return new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]'));
        } catch { return new Set(); }
    }

    function saveIds(ids) {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...ids]));
        updateCounter();
    }

    function updateCounter() {
        const ids = getStoredIds();
        const countEl = document.getElementById('selection-count');
        const valueEl = document.getElementById('count-value');
        if (ids.size > 0) {
            countEl.classList.remove('hidden');
            valueEl.textContent = ids.size;
        } else {
            countEl.classList.add('hidden');
        }
    }

    function clearSelection() {
        sessionStorage.removeItem(STORAGE_KEY);
        document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
        updateCounter();
    }

    // Au chargement : restaurer les checkboxes de la page courante
    document.addEventListener('DOMContentLoaded', function() {
        const stored = getStoredIds();
        document.querySelectorAll('.employee-checkbox').forEach(cb => {
            if (stored.has(cb.value)) cb.checked = true;
        });
        updateSelectAllState();
        updateCounter();
    });

    // Quand on coche/décoche une checkbox
    document.querySelectorAll('.employee-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const ids = getStoredIds();
            if (this.checked) {
                ids.add(this.value);
            } else {
                ids.delete(this.value);
            }
            saveIds(ids);
            updateSelectAllState();
        });
    });

    // Select all : ne concerne que la page courante
    document.getElementById('select-all').addEventListener('change', function() {
        const ids = getStoredIds();
        document.querySelectorAll('.employee-checkbox').forEach(cb => {
            cb.checked = this.checked;
            if (this.checked) {
                ids.add(cb.value);
            } else {
                ids.delete(cb.value);
            }
        });
        saveIds(ids);
    });

    function updateSelectAllState() {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
        document.getElementById('select-all').checked = allChecked;
    }

    // Récupérer tous les IDs stockés (toutes pages confondues)
    function getAllSelectedIds() {
        return [...getStoredIds()];
    }

    function generateSelected() {
        const ids = getAllSelectedIds();
        if (ids.length === 0) { alert('Selectionnez au moins un employe.'); return; }
        const form = document.getElementById('bulk-form-generate');
        form.querySelectorAll('input[name="user_ids[]"]').forEach(el => el.remove());
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'user_ids[]'; input.value = id;
            form.appendChild(input);
        });
        sessionStorage.removeItem(STORAGE_KEY);
        form.submit();
    }

    function downloadSelected() {
        const ids = getAllSelectedIds();
        if (ids.length === 0) { alert('Selectionnez au moins un employe.'); return; }
        const form = document.getElementById('bulk-form-download');
        form.querySelectorAll('input[name="user_ids[]"], input[name="per_page"]').forEach(el => el.remove());
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'user_ids[]'; input.value = id;
            form.appendChild(input);
        });
        const perPage = document.createElement('input');
        perPage.type = 'hidden'; perPage.name = 'per_page'; perPage.value = document.getElementById('badges-per-page').value;
        form.appendChild(perPage);
        sessionStorage.removeItem(STORAGE_KEY);
        form.submit();
    }
</script>
@endpush
@endsection
