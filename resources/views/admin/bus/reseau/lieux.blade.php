@extends('layouts.admin')

@section('title', 'Points & campus')
@section('page-title', 'Points de ramassage & campus')

@section('content')
<div x-data="{ formulaire: false, edite: null }">

    <div class="mb-5 flex items-start gap-2.5 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
        <i class="fas fa-circle-info text-blue-500 mt-0.5"></i>
        <p class="text-sm text-blue-800">
            Seuls les points <strong>actifs</strong> de type « ramassage » sont proposés à l'étudiant dans l'application.
            Le rayon décide de la distance à laquelle le bus prévient de son approche.
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="recherche" value="{{ request('recherche') }}" placeholder="Nom du lieu…"
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-52 focus:outline-none focus:ring-2 focus:ring-amber-500">
            <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Tous les types</option>
                @foreach($types as $t)
                    <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ ucfirst($t->value) }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm"><i class="fas fa-search"></i></button>
            @if(request()->hasAny(['recherche', 'type']))
                <a href="{{ route('admin.bus.lieux') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Réinitialiser</a>
            @endif
        </form>

        <button @click="formulaire = !formulaire; edite = null"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">
            <i class="fas fa-plus mr-1.5"></i> Nouveau lieu
        </button>
    </div>

    <div x-show="formulaire" x-cloak class="mb-5 bg-white rounded-xl border border-amber-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Nouveau lieu</h3>
        <form method="POST" action="{{ route('admin.bus.lieux.store') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-4">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nom *</label>
                <input name="nom" required value="{{ old('nom') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Adresse</label>
                <input name="adresse" value="{{ old('adresse') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type *</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    @foreach($types as $t)
                        <option value="{{ $t->value }}">{{ ucfirst($t->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Rayon (m) *</label>
                <input type="number" name="rayon_validation_metres" required min="20" max="2000" value="{{ old('rayon_validation_metres', 150) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Latitude *</label>
                <input name="latitude" required type="number" step="any" placeholder="5.4781" value="{{ old('latitude') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Longitude *</label>
                <input name="longitude" required type="number" step="any" placeholder="10.4178" value="{{ old('longitude') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="sm:col-span-6 flex gap-2">
                <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">Enregistrer</button>
                <button type="button" @click="formulaire = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Annuler</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Lieu</th>
                        <th class="px-5 py-3 text-center font-semibold">Type</th>
                        <th class="px-5 py-3 text-left font-semibold">Coordonnées</th>
                        <th class="px-5 py-3 text-center font-semibold">Rayon</th>
                        <th class="px-5 py-3 text-center font-semibold">Étudiants</th>
                        <th class="px-5 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lieux as $lieu)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">
                                    {{ $lieu->nom }}
                                    @unless($lieu->actif)
                                        <span class="ml-1.5 px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 text-xs">inactif</span>
                                    @endunless
                                </p>
                                @if($lieu->adresse)
                                    <p class="text-xs text-gray-500">{{ $lieu->adresse }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $lieu->estCampus() ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">
                                    {{ ucfirst($lieu->type->value) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-xs font-mono">
                                {{ number_format((float) $lieu->latitude, 4) }}, {{ number_format((float) $lieu->longitude, 4) }}
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $lieu->rayon_validation_metres }} m</td>
                            <td class="px-5 py-3 text-center text-gray-500">{{ $lieu->etudiants_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <button @click="edite = (edite === {{ $lieu->id }} ? null : {{ $lieu->id }}); formulaire = false"
                                        class="text-gray-400 hover:text-amber-600 px-2" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>
                        </tr>

                        <tr x-show="edite === {{ $lieu->id }}" x-cloak class="bg-amber-50/50">
                            <td colspan="6" class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.bus.lieux.update', $lieu) }}"
                                      class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end">
                                    @csrf @method('PUT')
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nom</label>
                                        <input name="nom" required value="{{ $lieu->nom }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Adresse</label>
                                        <input name="adresse" value="{{ $lieu->adresse }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            @foreach($types as $t)
                                                <option value="{{ $t->value }}" @selected($lieu->type === $t)>{{ ucfirst($t->value) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Rayon (m)</label>
                                        <input type="number" name="rayon_validation_metres" required min="20" max="2000"
                                               value="{{ $lieu->rayon_validation_metres }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Latitude</label>
                                        <input name="latitude" required type="number" step="any" value="{{ $lieu->latitude }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Longitude</label>
                                        <input name="longitude" required type="number" step="any" value="{{ $lieu->longitude }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="sm:col-span-4 flex items-center gap-3">
                                        <label class="flex items-center text-sm text-gray-600">
                                            <input type="checkbox" name="actif" value="1" @checked($lieu->actif)
                                                   class="h-4 w-4 rounded border-gray-300 text-amber-600">
                                            <span class="ml-1.5">Actif (visible dans l'application)</span>
                                        </label>
                                        <button class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm">Enregistrer</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <i class="fas fa-map-pin text-3xl text-gray-300"></i>
                                <p class="mt-3 text-sm text-gray-500">Aucun lieu.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lieux->hasPages())
            <div class="px-5 py-3 border-t border-gray-200">{{ $lieux->links() }}</div>
        @endif
    </div>
</div>
@endsection
