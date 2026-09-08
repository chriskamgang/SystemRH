@extends('layouts.admin')

@section('title', 'Régulation')
@section('page-title', 'Régulation du transport')

@section('content')

{{-- Ce qui roule maintenant --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">En circulation</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $nbBusEnLigne }}</p>
                <p class="text-xs text-gray-500 mt-1">sur {{ $nbAffectations }} affecté{{ $nbAffectations > 1 ? 's' : '' }} ce jour</p>
            </div>
            <span class="w-11 h-11 rounded-lg bg-green-50 flex items-center justify-center">
                <i class="fas fa-bus text-green-600"></i>
            </span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tours du jour</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $toursTermines }}<span class="text-lg text-gray-400">/{{ $toursPrevus }}</span></p>
                <p class="text-xs text-gray-500 mt-1">{{ $toursEnCours }} en cours</p>
            </div>
            <span class="w-11 h-11 rounded-lg bg-blue-50 flex items-center justify-center">
                <i class="fas fa-flag-checkered text-blue-600"></i>
            </span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Abonnés actifs</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $abonnesActifs }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($recetteDuMois, 0, ',', ' ') }} FCFA ce mois</p>
            </div>
            <span class="w-11 h-11 rounded-lg bg-amber-50 flex items-center justify-center">
                <i class="fas fa-ticket text-amber-600"></i>
            </span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">À surveiller</p>
                <p class="mt-1 text-3xl font-bold {{ ($nbBusPanne + $anomalies) > 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $nbBusPanne + $anomalies }}
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ $nbBusPanne }} panne{{ $nbBusPanne > 1 ? 's' : '' }} · {{ $anomalies }} écart{{ $anomalies > 1 ? 's' : '' }}</p>
            </div>
            <span class="w-11 h-11 rounded-lg {{ ($nbBusPanne + $anomalies) > 0 ? 'bg-red-50' : 'bg-gray-100' }} flex items-center justify-center">
                <i class="fas fa-triangle-exclamation {{ ($nbBusPanne + $anomalies) > 0 ? 'text-red-600' : 'text-gray-400' }}"></i>
            </span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Service du jour --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-800">Service du jour</h2>
                <p class="text-xs text-gray-500">{{ now()->translatedFormat('l j F Y') }}</p>
            </div>
            <a href="{{ route('admin.bus.affectations') }}" class="text-sm text-amber-600 hover:text-amber-700 font-medium">
                Gérer <i class="fas fa-arrow-right text-xs ml-0.5"></i>
            </a>
        </div>

        @if($affectations->isEmpty())
            <div class="px-5 py-12 text-center">
                <i class="fas fa-calendar-xmark text-3xl text-gray-300"></i>
                <p class="mt-3 text-sm text-gray-500">Aucun service planifié aujourd'hui.</p>
                <a href="{{ route('admin.bus.affectations') }}"
                   class="inline-block mt-3 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
                    Planifier le service
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-2.5 text-left font-semibold">Ligne</th>
                            <th class="px-5 py-2.5 text-left font-semibold">Bus</th>
                            <th class="px-5 py-2.5 text-left font-semibold">Chauffeur</th>
                            <th class="px-5 py-2.5 text-center font-semibold">Tours</th>
                            <th class="px-5 py-2.5 text-center font-semibold">État</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($affectations as $a)
                            @php
                                $faits = $a->tournees->where('statut', \App\Enums\StatutTournee::Termine)->count();
                                $roule = $busEnLigne->contains(fn ($p) => $p->bus_id === $a->bus_id);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-900 text-white text-xs font-bold">
                                        {{ $a->ligne->code ?? '—' }}
                                    </span>
                                    <span class="ml-2 text-gray-600 text-xs">{{ $a->ligne->nom ?? '' }}</span>
                                </td>
                                <td class="px-5 py-3 font-medium text-gray-800">{{ $a->bus->immatriculation ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $a->chauffeur->user->full_name ?? '—' }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="font-semibold text-gray-800">{{ $faits }}</span>
                                    <span class="text-gray-400">/{{ $a->tours_prevus }}</span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($roule)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                            En ligne
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs">Hors ligne</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Colonne de droite --}}
    <div class="space-y-6">

        {{-- Pannes ouvertes : ce qui immobilise un véhicule. --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-800">Pannes en cours</h2>
            </div>
            @if($pannesOuvertes->isEmpty())
                <div class="px-5 py-8 text-center">
                    <i class="fas fa-circle-check text-2xl text-green-400"></i>
                    <p class="mt-2 text-sm text-gray-500">Aucune panne signalée.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach($pannesOuvertes as $panne)
                        <li class="px-5 py-3">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-wrench text-red-600 text-xs"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800">{{ $panne->bus->immatriculation ?? 'Bus inconnu' }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $panne->description ?? $panne->statut->libelle() }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $panne->created_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Le réseau en un coup d'œil --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-4">Le réseau</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-gray-600"><i class="fas fa-bus w-5 text-gray-400"></i> Véhicules actifs</dt>
                    <dd class="font-semibold text-gray-900">{{ $nbBus }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-600"><i class="fas fa-id-card w-5 text-gray-400"></i> Chauffeurs</dt>
                    <dd class="font-semibold text-gray-900">{{ $nbChauffeurs }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-600"><i class="fas fa-route w-5 text-gray-400"></i> Lignes</dt>
                    <dd class="font-semibold text-gray-900">{{ $nbLignes }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-600"><i class="fas fa-user-graduate w-5 text-gray-400"></i> Étudiants inscrits</dt>
                    <dd class="font-semibold text-gray-900">{{ $nbEtudiants }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

{{-- Derniers tours bouclés --}}
<div class="mt-6 bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Derniers tours terminés</h2>
        <a href="{{ route('admin.bus.tournees') }}" class="text-sm text-amber-600 hover:text-amber-700 font-medium">
            Tout voir <i class="fas fa-arrow-right text-xs ml-0.5"></i>
        </a>
    </div>

    @if($derniersTours->isEmpty())
        <p class="px-5 py-10 text-center text-sm text-gray-500">Aucun tour terminé pour le moment.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-2.5 text-left font-semibold">Ligne</th>
                        <th class="px-5 py-2.5 text-left font-semibold">Bus</th>
                        <th class="px-5 py-2.5 text-left font-semibold">Chauffeur</th>
                        <th class="px-5 py-2.5 text-center font-semibold">Tour</th>
                        <th class="px-5 py-2.5 text-center font-semibold">Embarqués</th>
                        <th class="px-5 py-2.5 text-center font-semibold">Durée</th>
                        <th class="px-5 py-2.5 text-right font-semibold">Terminé</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($derniersTours as $t)
                        <tr class="hover:bg-gray-50 {{ $t->anomalie_duree ? 'bg-orange-50/40' : '' }}">
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded bg-gray-900 text-white text-xs font-bold">
                                    {{ $t->affectation->ligne->code ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-800">{{ $t->affectation->bus->immatriculation ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $t->affectation->chauffeur->user->full_name ?? '—' }}</td>
                            <td class="px-5 py-3 text-center text-gray-600">#{{ $t->numero_tour }}</td>
                            <td class="px-5 py-3 text-center font-medium text-gray-800">{{ $t->effectif_embarque ?? '—' }}</td>
                            <td class="px-5 py-3 text-center">
                                @if($t->duree_reelle_minutes)
                                    <span class="{{ $t->anomalie_duree ? 'text-orange-600 font-semibold' : 'text-gray-600' }}">
                                        {{ $t->duree_reelle_minutes }} min
                                        @if($t->anomalie_duree)
                                            <i class="fas fa-triangle-exclamation text-xs ml-0.5" title="Durée inhabituelle"></i>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right text-gray-500 text-xs">{{ $t->termine_le?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
