@extends('layouts.admin')

@section('title', 'Chauffeurs')
@section('page-title', 'Chauffeurs')

@section('content')
<div x-data="{ formulaire: false, edite: null }">

    {{-- Le chauffeur ne s'inscrit pas depuis l'application : son compte est
         créé ici, et il se connecte ensuite par téléphone + mot de passe. --}}
    <div class="mb-5 flex items-start gap-2.5 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
        <i class="fas fa-circle-info text-blue-500 mt-0.5"></i>
        <p class="text-sm text-blue-800">
            Les chauffeurs ne s'inscrivent pas depuis l'application : leur compte est créé ici.
            Ils se connectent avec leur <strong>numéro de téléphone</strong> et le <strong>code à 4 chiffres</strong> que vous leur communiquez.
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex gap-2">
            <input type="text" name="recherche" value="{{ request('recherche') }}"
                   placeholder="Nom, matricule, téléphone…"
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:outline-none focus:ring-2 focus:ring-amber-500">
            <button class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm">
                <i class="fas fa-search"></i>
            </button>
            @if(request('recherche'))
                <a href="{{ route('admin.bus.chauffeurs') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Réinitialiser</a>
            @endif
        </form>

        <button @click="formulaire = !formulaire; edite = null"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">
            <i class="fas fa-plus mr-1.5"></i> Nouveau chauffeur
        </button>
    </div>

    <div x-show="formulaire" x-cloak class="mb-5 bg-white rounded-xl border border-amber-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Nouveau chauffeur</h3>
        <form method="POST" action="{{ route('admin.bus.chauffeurs.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Prénom *</label>
                <input name="prenom" required value="{{ old('prenom') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nom *</label>
                <input name="nom" required value="{{ old('nom') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone * <span class="text-gray-400">(identifiant)</span></label>
                <input name="telephone" required value="{{ old('telephone') }}" placeholder="+237…"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Code à 4 chiffres *</label>
                <input type="text" name="code" required inputmode="numeric" pattern="\d{4}" maxlength="4"
                       placeholder="1234" value="{{ old('code') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono tracking-widest">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Matricule *</label>
                <input name="matricule" required value="{{ old('matricule') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">N° de permis</label>
                <input name="numero_permis" value="{{ old('numero_permis') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Permis expire le</label>
                <input type="date" name="permis_expire_le" value="{{ old('permis_expire_le') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="sm:col-span-3 flex gap-2">
                <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">Créer le compte</button>
                <button type="button" @click="formulaire = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Annuler</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Chauffeur</th>
                        <th class="px-5 py-3 text-left font-semibold">Téléphone</th>
                        <th class="px-5 py-3 text-left font-semibold">Matricule</th>
                        <th class="px-5 py-3 text-left font-semibold">Permis</th>
                        <th class="px-5 py-3 text-center font-semibold">Services</th>
                        <th class="px-5 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($chauffeurs as $c)
                        @php
                            // Un permis périmé interdit la route : la régulation
                            // doit le voir sans avoir à ouvrir la fiche.
                            $perime = $c->permis_expire_le && $c->permis_expire_le->isPast();
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ mb_substr($c->user->first_name ?? '?', 0, 1) }}
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $c->user->full_name ?? '—' }}</p>
                                        @unless($c->user?->actif_bus)
                                            <span class="text-xs text-gray-400">compte désactivé</span>
                                        @endunless
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $c->user->telephone_bus ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $c->matricule }}</td>
                            <td class="px-5 py-3">
                                @if($c->permis_expire_le)
                                    <span class="{{ $perime ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                        {{ $c->permis_expire_le->format('d/m/Y') }}
                                        @if($perime)<i class="fas fa-triangle-exclamation text-xs ml-0.5" title="Permis expiré"></i>@endif
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                                @if($c->disponible_secours)
                                    <span class="ml-1.5 px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 text-xs font-medium">secours</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center text-gray-500">{{ $c->affectations_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <button @click="edite = (edite === {{ $c->id }} ? null : {{ $c->id }}); formulaire = false"
                                        class="text-gray-400 hover:text-amber-600 px-2" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>
                        </tr>

                        <tr x-show="edite === {{ $c->id }}" x-cloak class="bg-amber-50/50">
                            <td colspan="6" class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.bus.chauffeurs.update', $c) }}"
                                      class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Prénom</label>
                                        <input name="prenom" required value="{{ $c->user->first_name }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nom</label>
                                        <input name="nom" required value="{{ $c->user->last_name }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                                        <input name="telephone" required value="{{ $c->user->telephone_bus }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                            Code <span class="text-gray-400">(vide = inchangé)</span>
                                        </label>
                                        <input type="text" name="code" inputmode="numeric" pattern="\d{4}" maxlength="4"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono tracking-widest">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Matricule</label>
                                        <input name="matricule" required value="{{ $c->matricule }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">N° permis</label>
                                        <input name="numero_permis" value="{{ $c->numero_permis }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Expire le</label>
                                        <input type="date" name="permis_expire_le"
                                               value="{{ $c->permis_expire_le?->format('Y-m-d') }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="flex flex-col justify-end gap-2">
                                        <div class="flex gap-3">
                                            <label class="flex items-center text-sm text-gray-600">
                                                <input type="checkbox" name="actif" value="1" @checked($c->user->actif_bus)
                                                       class="h-4 w-4 rounded border-gray-300 text-amber-600">
                                                <span class="ml-1.5">Actif</span>
                                            </label>
                                            <label class="flex items-center text-sm text-gray-600">
                                                <input type="checkbox" name="disponible_secours" value="1" @checked($c->disponible_secours)
                                                       class="h-4 w-4 rounded border-gray-300 text-amber-600">
                                                <span class="ml-1.5">Secours</span>
                                            </label>
                                        </div>
                                        <button class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm">Enregistrer</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <i class="fas fa-id-card text-3xl text-gray-300"></i>
                                <p class="mt-3 text-sm text-gray-500">Aucun chauffeur enregistré.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($chauffeurs->hasPages())
            <div class="px-5 py-3 border-t border-gray-200">{{ $chauffeurs->links() }}</div>
        @endif
    </div>
</div>
@endsection
