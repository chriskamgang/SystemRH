@extends('layouts.admin')

@section('title', 'Nouveau programme de formation')
@section('page-title', 'Nouveau programme de formation')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.training.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Nouveau programme</h2>
            <p class="text-gray-600 mt-1">Créer un nouveau programme de formation</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <p class="font-semibold mb-1"><i class="fas fa-exclamation-circle mr-2"></i>Erreurs de validation :</p>
        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.training.store') }}" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Titre --}}
            <div class="md:col-span-2">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Titre du programme <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="Ex. Formation en leadership et management">
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                          placeholder="Décrivez le contenu et les objectifs de cette formation...">{{ old('description') }}</textarea>
            </div>

            {{-- Type --}}
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                    Type <span class="text-red-500">*</span>
                </label>
                <select id="type" name="type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">-- Sélectionner --</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Niveau --}}
            <div>
                <label for="level" class="block text-sm font-medium text-gray-700 mb-1">
                    Niveau <span class="text-red-500">*</span>
                </label>
                <select id="level" name="level" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">-- Sélectionner --</option>
                    @foreach($levels as $key => $label)
                        <option value="{{ $key }}" {{ old('level') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Catégorie --}}
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <input type="text" id="category" name="category" value="{{ old('category') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="Ex. Management, Technique, Soft skills...">
            </div>

            {{-- Durée --}}
            <div>
                <label for="duration_hours" class="block text-sm font-medium text-gray-700 mb-1">
                    Durée (heures) <span class="text-red-500">*</span>
                </label>
                <input type="number" id="duration_hours" name="duration_hours"
                       value="{{ old('duration_hours') }}" min="0" step="0.5" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="Ex. 8">
            </div>

            {{-- Options --}}
            <div class="md:col-span-2 flex flex-wrap gap-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_mandatory" value="0">
                    <input type="checkbox" name="is_mandatory" value="1" {{ old('is_mandatory') ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Formation obligatoire</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Programme actif</span>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.training.index') }}"
               class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                Annuler
            </a>
            <button type="submit"
                    class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                <i class="fas fa-save mr-2"></i>Créer le programme
            </button>
        </div>
    </form>
</div>
@endsection
