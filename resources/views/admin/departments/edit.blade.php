@extends('layouts.admin')

@section('title', 'Modifier le Département')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">

    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.departments.index') }}" class="text-gray-500 hover:text-gray-700 mr-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Modifier : {{ $department->name }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $department->users_count }} employé(s) rattaché(s)</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.departments.update', $department->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-5">

                <!-- Nom -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nom du département <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $department->name) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror">
                </div>

                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                    <input type="text" name="code" value="{{ old('code', $department->code) }}"
                           placeholder="ex: DEPT-INFO"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-400 @enderror">
                    <p class="text-xs text-gray-400 mt-1">Code unique, optionnel (max 20 caractères)</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $department->description) }}</textarea>
                </div>

                <!-- Campus -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campus rattaché</label>
                    <select name="campus_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Aucun campus —</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}"
                                {{ old('campus_id', $department->campus_id) == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Chef de département -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-crown text-yellow-500 mr-1"></i>
                        Chef de département
                    </label>

                    @if($department->head)
                    <div class="flex items-center gap-3 bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 mb-3">
                        <div class="w-9 h-9 rounded-full bg-yellow-200 flex items-center justify-center flex-shrink-0">
                            <span class="font-bold text-yellow-800 text-sm">{{ strtoupper(substr($department->head->first_name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-yellow-900">{{ $department->head->full_name }}</p>
                            <p class="text-xs text-yellow-700">{{ $department->head->employee_id }} — Chef actuel</p>
                        </div>
                        <i class="fas fa-crown text-yellow-500 ml-auto"></i>
                    </div>
                    @endif

                    <!-- Recherche filtrante -->
                    <input type="text" id="headSearch" placeholder="Rechercher un employé par nom ou ID..."
                           class="w-full border border-gray-300 rounded-t-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 border-b-0">
                    <select name="head_user_id" id="headSelect"
                            class="w-full border border-gray-300 rounded-b-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" size="5">
                        <option value="">— Aucun chef de département —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                    data-label="{{ strtolower($user->full_name) }} {{ strtolower($user->employee_id ?? '') }}"
                                    {{ old('head_user_id', $department->head_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->full_name }}
                                @if($user->employee_id) ({{ $user->employee_id }}) @endif
                                — {{ ucfirst(str_replace('_', ' ', $user->employee_type)) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Tapez pour filtrer, cliquez pour sélectionner</p>

                    <!-- Affichage du chef sélectionné -->
                    <div id="headPreview" class="{{ old('head_user_id', $department->head_user_id) ? '' : 'hidden' }} mt-2 flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                        <i class="fas fa-crown text-yellow-500"></i>
                        <span id="headPreviewName" class="text-sm font-semibold text-blue-800">
                            {{ $department->head?->full_name ?? '' }}
                        </span>
                        <button type="button" onclick="clearHead()" class="ml-auto text-gray-400 hover:text-red-500 text-xs">
                            <i class="fas fa-times"></i> Retirer
                        </button>
                    </div>
                </div>

                <!-- Statut -->
                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $department->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Département actif</label>
                </div>

            </div>

            <div class="flex justify-between items-center mt-8 pt-5 border-t border-gray-100">
                <!-- Bouton supprimer HORS du formulaire principal -->
                <button type="button"
                        onclick="document.getElementById('deleteForm').submit()"
                        @if($department->users_count > 0) disabled title="{{ $department->users_count }} employé(s) rattaché(s)" @endif
                        class="px-4 py-2 {{ $department->users_count > 0 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-red-50 hover:bg-red-100 text-red-700' }} rounded-lg transition text-sm">
                    <i class="fas fa-trash mr-1"></i>
                    Supprimer
                    @if($department->users_count > 0)
                        <span class="text-xs">({{ $department->users_count }} employé(s))</span>
                    @endif
                </button>

                <div class="flex gap-3">
                    <a href="{{ route('admin.departments.index') }}"
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Annuler
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-save mr-2"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>

        <!-- Formulaire suppression séparé (hors du form principal) -->
        @if($department->users_count == 0)
        <form id="deleteForm" action="{{ route('admin.departments.destroy', $department->id) }}" method="POST"
              onsubmit="return confirm('Supprimer définitivement le département {{ addslashes($department->name) }} ?')">
            @csrf
            @method('DELETE')
        </form>
        @endif
    </div>

    <!-- Liste des employés du département -->
    @if($department->users_count > 0)
    <div class="bg-white rounded-lg shadow mt-6 overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-users mr-2 text-blue-500"></i>
                Employés de ce département ({{ $department->users_count }})
            </h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($department->users as $emp)
            <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-blue-700 font-bold text-xs">{{ strtoupper(substr($emp->first_name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $emp->full_name }}</p>
                        <p class="text-xs text-gray-500">{{ $emp->employee_id }} — {{ $emp->jobPosition?->name ?? '—' }}</p>
                    </div>
                </div>
                @if($emp->id === $department->head_user_id)
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">
                        <i class="fas fa-crown mr-1"></i> Chef
                    </span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
const headSearch      = document.getElementById('headSearch');
const headSelect      = document.getElementById('headSelect');
const headPreview     = document.getElementById('headPreview');
const headPreviewName = document.getElementById('headPreviewName');

headSearch.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    Array.from(headSelect.options).forEach(opt => {
        opt.hidden = q !== '' && !opt.dataset.label?.includes(q);
    });
});

headSelect.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        headPreview.classList.remove('hidden');
        headPreviewName.textContent = opt.text.split('—')[0].trim();
    } else {
        headPreview.classList.add('hidden');
    }
});

function clearHead() {
    headSelect.value = '';
    headPreview.classList.add('hidden');
    headSearch.value = '';
    Array.from(headSelect.options).forEach(opt => opt.hidden = false);
}
</script>
@endpush
@endsection
