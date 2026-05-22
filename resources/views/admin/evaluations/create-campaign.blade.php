@extends('layouts.admin')

@section('title', 'Nouvelle Campagne d\'Evaluation')
@section('page-title', 'Nouvelle Campagne d\'Evaluation')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">Creer une Campagne d'Evaluation</h2>
        <a href="{{ route('admin.evaluations.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition text-sm">
            <i class="fas fa-arrow-left mr-2"></i> Retour
        </a>
    </div>

    <form method="POST" action="{{ route('admin.evaluations.store-campaign') }}" id="campaignForm">
        @csrf

        {{-- Informations generales --}}
        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Informations Generales</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre de la campagne <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Evaluation annuelle 2026">
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Description de la campagne...">{{ old('description') }}</textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Annee <span class="text-red-500">*</span></label>
                    <input type="number" name="year" value="{{ old('year', date('Y')) }}" required min="2000" max="2100"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut <span class="text-red-500">*</span></label>
                    <select name="status" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    </select>
                    @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de debut <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" value="{{ old('end_date', date('Y-m-d', strtotime('+30 days'))) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Criteres d'evaluation --}}
        <div class="bg-white rounded-lg shadow p-6 space-y-4 mt-6">
            <div class="flex justify-between items-center border-b pb-2">
                <h3 class="text-lg font-semibold text-gray-700">Criteres d'Evaluation</h3>
                <button type="button" onclick="addCriteria()"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-sm">
                    <i class="fas fa-plus mr-1"></i> Ajouter un critere
                </button>
            </div>

            @error('criteria') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

            <div id="criteriaContainer">
                <div class="criteria-item border border-gray-200 rounded-lg p-4 space-y-3" data-index="0">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-600">Critere #1</span>
                        <button type="button" onclick="removeCriteria(this)" class="text-red-500 hover:text-red-700 text-sm hidden">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-2">
                            <input type="text" name="criteria[0][name]" required placeholder="Nom du critere"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <input type="number" name="criteria[0][max_score]" required placeholder="Note max" min="1" max="100" value="5"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <input type="number" name="criteria[0][weight]" required placeholder="Poids (%)" min="1" max="100" value="20"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <input type="text" name="criteria[0][description]" placeholder="Description (optionnel)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- Boutons --}}
        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.evaluations.index') }}" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-save mr-2"></i> Creer la campagne
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let criteriaIndex = 1;

function addCriteria() {
    const container = document.getElementById('criteriaContainer');
    const html = `
        <div class="criteria-item border border-gray-200 rounded-lg p-4 space-y-3 mt-3" data-index="${criteriaIndex}">
            <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-600">Critere #${criteriaIndex + 1}</span>
                <button type="button" onclick="removeCriteria(this)" class="text-red-500 hover:text-red-700 text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <input type="text" name="criteria[${criteriaIndex}][name]" required placeholder="Nom du critere"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <input type="number" name="criteria[${criteriaIndex}][max_score]" required placeholder="Note max" min="1" max="100" value="5"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <input type="number" name="criteria[${criteriaIndex}][weight]" required placeholder="Poids (%)" min="1" max="100" value="20"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <input type="text" name="criteria[${criteriaIndex}][description]" placeholder="Description (optionnel)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    criteriaIndex++;
}

function removeCriteria(btn) {
    const items = document.querySelectorAll('.criteria-item');
    if (items.length <= 1) {
        alert('Il faut au moins un critere.');
        return;
    }
    btn.closest('.criteria-item').remove();
}
</script>
@endpush
