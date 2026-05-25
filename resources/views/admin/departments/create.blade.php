@extends('layouts.admin')

@section('title', 'Nouveau Département')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">

    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.departments.index') }}" class="text-gray-500 hover:text-gray-700 mr-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Nouveau Département</h1>
            <p class="text-sm text-gray-500 mt-0.5">Remplissez les informations du département</p>
        </div>
    </div>

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
        <form action="{{ route('admin.departments.store') }}" method="POST">
            @csrf

            <div class="space-y-5">

                <!-- Nom -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nom du département <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="ex: Informatique"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror">
                </div>

                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                    <input type="text" name="code" value="{{ old('code') }}"
                           placeholder="ex: DEPT-INFO"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-400 @enderror">
                    <p class="text-xs text-gray-400 mt-1">Code unique, optionnel (max 20 caractères)</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                              placeholder="Description du département..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
                </div>

                <!-- Campus -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campus rattaché</label>
                    <select name="campus_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Aucun campus —</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
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
                    <!-- Recherche filtrante -->
                    <input type="text" id="headSearch" placeholder="Rechercher par nom ou ID..."
                           class="w-full border border-gray-300 rounded-t-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 border-b-0">
                    <select name="head_user_id" id="headSelect"
                            class="w-full border border-gray-300 rounded-b-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" size="5">
                        <option value="">— Aucun chef de département —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                    data-label="{{ strtolower($user->full_name) }} {{ strtolower($user->employee_id ?? '') }}"
                                    {{ old('head_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->full_name }}
                                @if($user->employee_id) ({{ $user->employee_id }}) @endif
                                — {{ ucfirst(str_replace('_', ' ', $user->employee_type)) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Tapez pour filtrer, cliquez pour sélectionner</p>

                    <!-- Affichage du chef sélectionné -->
                    <div id="headPreview" class="hidden mt-2 flex items-center gap-2 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
                        <i class="fas fa-crown text-yellow-500"></i>
                        <span id="headPreviewName" class="text-sm font-semibold text-yellow-800"></span>
                        <button type="button" onclick="clearHead()" class="ml-auto text-gray-400 hover:text-red-500 text-xs">
                            <i class="fas fa-times"></i> Retirer
                        </button>
                    </div>
                </div>

                <!-- Statut -->
                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Département actif</label>
                </div>

            </div>

            <div class="flex justify-end gap-3 mt-8 pt-5 border-t border-gray-100">
                <a href="{{ route('admin.departments.index') }}"
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i> Créer le département
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const headSearch  = document.getElementById('headSearch');
const headSelect  = document.getElementById('headSelect');
const headPreview = document.getElementById('headPreview');
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

// Restore on page load (old input)
window.addEventListener('DOMContentLoaded', function() {
    const opt = headSelect.options[headSelect.selectedIndex];
    if (opt && opt.value) {
        headPreview.classList.remove('hidden');
        headPreviewName.textContent = opt.text.split('—')[0].trim();
    }
});
</script>
@endpush
@endsection
