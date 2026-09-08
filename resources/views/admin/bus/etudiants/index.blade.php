@extends('layouts.admin')

@section('title', 'Étudiants')
@section('page-title', 'Étudiants inscrits au transport')

@section('content')
<div x-data="{ edite: null }">

    <div class="mb-5 flex items-start gap-2.5 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
        <i class="fas fa-circle-info text-blue-500 mt-0.5"></i>
        <p class="text-sm text-blue-800">
            Ces comptes ne se créent pas ici : l'étudiant s'inscrit lui-même depuis l'application.
            Vous pouvez corriger son point de ramassage s'il n'y parvient pas.
        </p>
    </div>

    <form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
        <input type="text" name="recherche" value="{{ request('recherche') }}" placeholder="Nom, email, matricule…"
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-60 focus:outline-none focus:ring-2 focus:ring-amber-500">

        <select name="lieu" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Tous les points</option>
            @foreach($lieux as $l)
                <option value="{{ $l->id }}" @selected(request('lieu') == $l->id)>{{ $l->nom }}</option>
            @endforeach
        </select>

        {{-- Un profil sans point de ramassage n'est pas allé au bout de son inscription. --}}
        <label class="flex items-center gap-1.5 px-3 py-2 border rounded-lg text-sm cursor-pointer
                      {{ request()->boolean('sans_lieu') ? 'bg-orange-50 border-orange-300 text-orange-700' : 'bg-white border-gray-300 text-gray-600' }}">
            <input type="checkbox" name="sans_lieu" value="1" @checked(request()->boolean('sans_lieu'))
                   onchange="this.form.submit()" class="h-4 w-4 rounded border-gray-300 text-orange-600">
            Profil incomplet
        </label>

        <button class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm">
            <i class="fas fa-search"></i>
        </button>
        @if(request()->hasAny(['recherche', 'lieu', 'sans_lieu']))
            <a href="{{ route('admin.bus.etudiants') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Réinitialiser</a>
        @endif
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Étudiant</th>
                        <th class="px-5 py-3 text-left font-semibold">Matricule</th>
                        <th class="px-5 py-3 text-left font-semibold">Scolarité</th>
                        <th class="px-5 py-3 text-left font-semibold">Point de ramassage</th>
                        <th class="px-5 py-3 text-center font-semibold">Pass</th>
                        <th class="px-5 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($etudiants as $e)
                        @php $abo = $abonnements[$e->id] ?? null; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ mb_substr($e->user->first_name ?? '?', 0, 1) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900">{{ $e->user->full_name ?: '— profil incomplet —' }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $e->user->email ?? $e->user->telephone_bus ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $e->matricule_insam ?: '—' }}</td>
                            <td class="px-5 py-3">
                                @if($e->user->niveau || $e->user->specialite)
                                    <p class="text-gray-700">{{ $e->user->niveau ?: '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ $e->user->specialite ?: '' }}</p>
                                @else
                                    <span class="text-xs text-gray-400">non renseignée</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($e->lieuRamassage)
                                    <span class="inline-flex items-center gap-1.5 text-gray-700">
                                        <i class="fas fa-map-pin text-gray-400 text-xs"></i>
                                        {{ $e->lieuRamassage->nom }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-xs font-semibold">
                                        à définir
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($abo)
                                    <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold"
                                          title="Expire le {{ $abo->date_fin?->format('d/m/Y') }}">
                                        {{ $abo->trajets_restants !== null ? $abo->trajets_restants.' trajets' : 'actif' }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">aucun</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button @click="edite = (edite === {{ $e->id }} ? null : {{ $e->id }})"
                                        class="text-gray-400 hover:text-amber-600 px-2" title="Changer le point de ramassage">
                                    <i class="fas fa-map-location-dot"></i>
                                </button>
                            </td>
                        </tr>

                        <tr x-show="edite === {{ $e->id }}" x-cloak class="bg-amber-50/50">
                            <td colspan="6" class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.bus.etudiants.lieu', $e) }}"
                                      class="flex flex-wrap items-end gap-3">
                                    @csrf @method('PUT')
                                    <div class="flex-1 min-w-[240px]">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Point de ramassage</label>
                                        <select name="lieu_ramassage_id" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                            @foreach($lieux as $l)
                                                <option value="{{ $l->id }}" @selected($e->lieu_ramassage_id == $l->id)>{{ $l->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm">Enregistrer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <i class="fas fa-user-graduate text-3xl text-gray-300"></i>
                                <p class="mt-3 text-sm text-gray-500">Aucun étudiant inscrit.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($etudiants->hasPages())
            <div class="px-5 py-3 border-t border-gray-200">{{ $etudiants->links() }}</div>
        @endif
    </div>
</div>
@endsection
