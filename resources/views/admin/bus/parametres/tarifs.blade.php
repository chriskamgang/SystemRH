@extends('layouts.admin')

@section('title', 'Formules & tarifs')
@section('page-title', 'Formules & tarifs')

@section('content')
<div x-data="{ formulaire: false, edite: null }">

    {{-- Le modèle économique : le montant saisi est celui d'UNE journée.
         Le prix du pass et les trajets qu'il ouvre en découlent. --}}
    <div class="mb-5 flex items-start gap-2.5 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
        <i class="fas fa-circle-info text-blue-500 mt-0.5"></i>
        <div class="text-sm text-blue-800">
            <p class="font-semibold mb-0.5">Comment se calcule un pass</p>
            <p>
                Le montant est celui d'<strong>une journée</strong>.
                Prix du pass = <em>montant × jours couverts</em>.
                Trajets inclus = <em>jours couverts × trajets par jour</em>.
            </p>
            <p class="text-xs mt-1 text-blue-700">
                Exemple : 500 FCFA/jour × 5 jours = 2 500 FCFA pour 10 trajets (2 par jour).
            </p>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">Ces formules sont celles que l'étudiant voit dans l'application.</p>
        <button @click="formulaire = !formulaire; edite = null"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium whitespace-nowrap">
            <i class="fas fa-plus mr-1.5"></i> Nouvelle formule
        </button>
    </div>

    <div x-show="formulaire" x-cloak class="mb-5 bg-white rounded-xl border border-amber-200 p-5"
         x-data="{ montant: 500, jours: 5, trajets: 2 }">
        <h3 class="font-semibold text-gray-800 mb-4">Nouvelle formule</h3>
        <form method="POST" action="{{ route('admin.bus.tarifs.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Code *</label>
                <input name="code" required maxlength="30" placeholder="pass_semaine" value="{{ old('code') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                <p class="text-xs text-gray-400 mt-1">Identifiant technique</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Libellé *</label>
                <input name="libelle" required placeholder="Pass semaine" value="{{ old('libelle') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Montant / jour *</label>
                <input type="number" name="montant_fcfa" required min="0" x-model.number="montant"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jours couverts *</label>
                <input type="number" name="jours_couverts" required min="1" max="365" x-model.number="jours"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Trajets / jour *</label>
                <input type="number" name="trajets_par_jour" required min="1" max="10" x-model.number="trajets"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            {{-- Aperçu vivant : ce que l'étudiant paiera réellement. --}}
            <div class="sm:col-span-5 flex flex-wrap items-center gap-4 px-4 py-3 bg-gray-50 rounded-lg">
                <span class="text-sm text-gray-600">L'étudiant paiera</span>
                <span class="text-lg font-bold text-gray-900" x-text="(montant * jours).toLocaleString('fr-FR') + ' FCFA'"></span>
                <span class="text-sm text-gray-600">pour</span>
                <span class="text-lg font-bold text-gray-900" x-text="(jours * trajets) + ' trajets'"></span>
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
                        <th class="px-5 py-3 text-left font-semibold">Formule</th>
                        <th class="px-5 py-3 text-right font-semibold">Prix / jour</th>
                        <th class="px-5 py-3 text-center font-semibold">Jours</th>
                        <th class="px-5 py-3 text-right font-semibold">Total à payer</th>
                        <th class="px-5 py-3 text-center font-semibold">Trajets</th>
                        <th class="px-5 py-3 text-center font-semibold">Souscrits</th>
                        <th class="px-5 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tarifs as $t)
                        <tr class="hover:bg-gray-50 {{ $t->actif ? '' : 'opacity-60' }}">
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs font-mono">{{ $t->code }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ $t->libelle }}</p>
                                @unless($t->actif)
                                    <span class="text-xs text-gray-400">retirée du catalogue</span>
                                @endunless
                            </td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ number_format($t->montant_fcfa, 0, ',', ' ') }}</td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $t->jours_couverts }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">
                                {{ number_format($t->montant_fcfa * $t->jours_couverts, 0, ',', ' ') }} <span class="text-xs font-normal text-gray-500">FCFA</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 text-xs font-semibold">
                                    {{ $t->trajetsTotal() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-500">{{ $t->abonnements_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <button @click="edite = (edite === {{ $t->id }} ? null : {{ $t->id }}); formulaire = false"
                                        class="text-gray-400 hover:text-amber-600 px-2" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.bus.tarifs.destroy', $t) }}" class="inline"
                                      onsubmit="return confirm('Retirer la formule « {{ $t->libelle }} » ?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-400 hover:text-red-600 px-2" title="Retirer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <tr x-show="edite === {{ $t->id }}" x-cloak class="bg-amber-50/50">
                            <td colspan="8" class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.bus.tarifs.update', $t) }}"
                                      class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end"
                                      x-data="{ m: {{ $t->montant_fcfa }}, j: {{ $t->jours_couverts }}, tr: {{ $t->trajets_par_jour }} }">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Code</label>
                                        <input name="code" required value="{{ $t->code }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Libellé</label>
                                        <input name="libelle" required value="{{ $t->libelle }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Montant / jour</label>
                                        <input type="number" name="montant_fcfa" required min="0" x-model.number="m"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Jours</label>
                                        <input type="number" name="jours_couverts" required min="1" max="365" x-model.number="j"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Trajets / jour</label>
                                        <input type="number" name="trajets_par_jour" required min="1" max="10" x-model.number="tr"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center text-sm text-gray-600">
                                            <input type="checkbox" name="actif" value="1" @checked($t->actif)
                                                   class="h-4 w-4 rounded border-gray-300 text-amber-600">
                                            <span class="ml-1.5">Proposée</span>
                                        </label>
                                        <button class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm">Enregistrer</button>
                                    </div>
                                    <div class="sm:col-span-6 flex items-center gap-3 px-3 py-2 bg-white rounded-lg border border-gray-200">
                                        <span class="text-xs text-gray-500">Aperçu :</span>
                                        <span class="text-sm font-bold text-gray-900" x-text="(m * j).toLocaleString('fr-FR') + ' FCFA'"></span>
                                        <span class="text-xs text-gray-500">pour</span>
                                        <span class="text-sm font-bold text-gray-900" x-text="(j * tr) + ' trajets'"></span>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <i class="fas fa-tags text-3xl text-gray-300"></i>
                                <p class="mt-3 text-sm text-gray-500">Aucune formule. L'étudiant ne peut rien souscrire.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
