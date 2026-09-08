@extends('layouts.admin')

@section('title', 'Véhicules')
@section('page-title', 'Véhicules')

@section('content')
<div x-data="{ formulaire: false, edite: null }">

    {{-- Barre d'outils --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="recherche" value="{{ request('recherche') }}"
                   placeholder="Immatriculation, modèle…"
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-56 focus:outline-none focus:ring-2 focus:ring-amber-500">
            <select name="statut" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">Tous les états</option>
                @foreach($statuts as $s)
                    <option value="{{ $s->value }}" @selected(request('statut') === $s->value)>{{ $s->libelle() }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm">
                <i class="fas fa-search"></i>
            </button>
            @if(request()->hasAny(['recherche', 'statut']))
                <a href="{{ route('admin.bus.vehicules') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Réinitialiser</a>
            @endif
        </form>

        <button @click="formulaire = true; edite = null"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">
            <i class="fas fa-plus mr-1.5"></i> Ajouter un bus
        </button>
    </div>

    {{-- Formulaire d'ajout --}}
    <div x-show="formulaire && !edite" x-cloak class="mb-5 bg-white rounded-xl border border-amber-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Nouveau véhicule</h3>
        <form method="POST" action="{{ route('admin.bus.vehicules.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Immatriculation *</label>
                <input name="immatriculation" required value="{{ old('immatriculation') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Modèle</label>
                <input name="modele" value="{{ old('modele') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Places *</label>
                <input type="number" name="capacite" required min="1" max="200" value="{{ old('capacite', 30) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">État *</label>
                <select name="statut" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    @foreach($statuts as $s)
                        <option value="{{ $s->value }}">{{ $s->libelle() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-4 flex gap-2">
                <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">Enregistrer</button>
                <button type="button" @click="formulaire = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Annuler</button>
            </div>
        </form>
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Immatriculation</th>
                        <th class="px-5 py-3 text-left font-semibold">Modèle</th>
                        <th class="px-5 py-3 text-center font-semibold">Places</th>
                        <th class="px-5 py-3 text-center font-semibold">État</th>
                        <th class="px-5 py-3 text-center font-semibold">Services</th>
                        <th class="px-5 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bus as $v)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-semibold text-gray-900">
                                {{ $v->immatriculation }}
                                @unless($v->actif)
                                    <span class="ml-1.5 px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 text-xs">inactif</span>
                                @endunless
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $v->modele ?: '—' }}</td>
                            <td class="px-5 py-3 text-center text-gray-700">{{ $v->capacite }}</td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $teintes = ['success' => 'bg-green-100 text-green-700', 'info' => 'bg-blue-100 text-blue-700',
                                                'danger' => 'bg-red-100 text-red-700', 'warning' => 'bg-amber-100 text-amber-700'];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $teintes[$v->statut->couleur()] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $v->statut->libelle() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-500">{{ $v->affectations_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <button @click="edite = (edite === {{ $v->id }} ? null : {{ $v->id }}); formulaire = false"
                                        class="text-gray-400 hover:text-amber-600 px-2" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.bus.vehicules.destroy', $v) }}" class="inline"
                                      onsubmit="return confirm('Retirer {{ $v->immatriculation }} de la flotte ?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-400 hover:text-red-600 px-2" title="Retirer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        {{-- Édition en ligne : la ligne se déplie sous celle qu'on modifie. --}}
                        <tr x-show="edite === {{ $v->id }}" x-cloak class="bg-amber-50/50">
                            <td colspan="6" class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.bus.vehicules.update', $v) }}"
                                      class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Immatriculation</label>
                                        <input name="immatriculation" required value="{{ $v->immatriculation }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Modèle</label>
                                        <input name="modele" value="{{ $v->modele }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Places</label>
                                        <input type="number" name="capacite" required min="1" max="200" value="{{ $v->capacite }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">État</label>
                                        <select name="statut" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            @foreach($statuts as $s)
                                                <option value="{{ $s->value }}" @selected($v->statut === $s)>{{ $s->libelle() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center text-sm text-gray-600">
                                            <input type="checkbox" name="actif" value="1" @checked($v->actif)
                                                   class="h-4 w-4 rounded border-gray-300 text-amber-600">
                                            <span class="ml-1.5">Actif</span>
                                        </label>
                                        <button class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm">
                                            Enregistrer
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <i class="fas fa-bus text-3xl text-gray-300"></i>
                                <p class="mt-3 text-sm text-gray-500">Aucun véhicule.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bus->hasPages())
            <div class="px-5 py-3 border-t border-gray-200">{{ $bus->links() }}</div>
        @endif
    </div>
</div>
@endsection
