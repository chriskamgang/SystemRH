@extends('layouts.admin')

@section('title', 'Lignes')
@section('page-title', 'Lignes de ramassage')

@section('content')
<div x-data="{ formulaire: false, edite: null }">

    <div class="flex items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">
            Les lignes desservies par le réseau. L'étudiant choisit un point de ramassage, qui le rattache à sa ligne.
        </p>
        <button @click="formulaire = !formulaire; edite = null"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium whitespace-nowrap">
            <i class="fas fa-plus mr-1.5"></i> Nouvelle ligne
        </button>
    </div>

    <div x-show="formulaire" x-cloak class="mb-5 bg-white rounded-xl border border-amber-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Nouvelle ligne</h3>
        <form method="POST" action="{{ route('admin.bus.lignes.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Code *</label>
                <input name="code" required maxlength="10" placeholder="L1" value="{{ old('code') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nom *</label>
                <input name="nom" required placeholder="Kamkop — Campus" value="{{ old('nom') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Durée (min) *</label>
                <input type="number" name="duree_trajet_minutes" required min="1" max="600" value="{{ old('duree_trajet_minutes', 45) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tours / jour *</label>
                <input type="number" name="tours_prevus_par_jour" required min="1" max="20" value="{{ old('tours_prevus_par_jour', 4) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="sm:col-span-5">
                <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                <input name="description" value="{{ old('description') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="sm:col-span-5 flex gap-2">
                <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">Créer</button>
                <button type="button" @click="formulaire = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Annuler</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Code</th>
                        <th class="px-5 py-3 text-left font-semibold">Nom</th>
                        <th class="px-5 py-3 text-center font-semibold">Durée</th>
                        <th class="px-5 py-3 text-center font-semibold">Tours/jour</th>
                        <th class="px-5 py-3 text-center font-semibold">Parcours</th>
                        <th class="px-5 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lignes as $l)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded bg-gray-900 text-white text-xs font-bold">{{ $l->code }}</span>
                                @unless($l->actif)
                                    <span class="ml-1.5 px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 text-xs">inactive</span>
                                @endunless
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $l->nom }}</p>
                                @if($l->description)
                                    <p class="text-xs text-gray-500">{{ $l->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $l->duree_trajet_minutes }} min</td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $l->tours_prevus_par_jour }}</td>
                            <td class="px-5 py-3 text-center text-gray-500">{{ $l->parcours_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <button @click="edite = (edite === {{ $l->id }} ? null : {{ $l->id }}); formulaire = false"
                                        class="text-gray-400 hover:text-amber-600 px-2" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>
                        </tr>

                        <tr x-show="edite === {{ $l->id }}" x-cloak class="bg-amber-50/50">
                            <td colspan="6" class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.bus.lignes.update', $l) }}"
                                      class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Code</label>
                                        <input name="code" required value="{{ $l->code }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nom</label>
                                        <input name="nom" required value="{{ $l->nom }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Durée (min)</label>
                                        <input type="number" name="duree_trajet_minutes" required min="1" value="{{ $l->duree_trajet_minutes }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Tours/jour</label>
                                        <input type="number" name="tours_prevus_par_jour" required min="1" value="{{ $l->tours_prevus_par_jour }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                        <input name="description" value="{{ $l->description }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center text-sm text-gray-600">
                                            <input type="checkbox" name="actif" value="1" @checked($l->actif)
                                                   class="h-4 w-4 rounded border-gray-300 text-amber-600">
                                            <span class="ml-1.5">Active</span>
                                        </label>
                                        <button class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm">Enregistrer</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <i class="fas fa-route text-3xl text-gray-300"></i>
                                <p class="mt-3 text-sm text-gray-500">Aucune ligne.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lignes->hasPages())
            <div class="px-5 py-3 border-t border-gray-200">{{ $lignes->links() }}</div>
        @endif
    </div>
</div>
@endsection
