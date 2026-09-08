@extends('layouts.admin')

@section('title', 'Abonnements')
@section('page-title', 'Abonnements')

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Recette du mois</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($recetteMois, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FCFA</span></p>
        <p class="text-xs text-gray-500 mt-1">{{ now()->translatedFormat('F Y') }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:col-span-2 flex items-center">
        <p class="text-sm text-gray-600">
            <i class="fas fa-circle-info text-blue-500 mr-1.5"></i>
            Les abonnements sont souscrits et payés depuis l'application (Mobile Money).
            Cette page en donne le suivi.
        </p>
    </div>
</div>

<form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
    <select name="statut" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <option value="">Tous les états</option>
        @foreach($statuts as $s)
            <option value="{{ $s->value }}" @selected(request('statut') === $s->value)>{{ $s->libelle() }}</option>
        @endforeach
    </select>

    <label class="flex items-center gap-1.5 px-3 py-2 border rounded-lg text-sm cursor-pointer
                  {{ request()->boolean('expires') ? 'bg-orange-50 border-orange-300 text-orange-700' : 'bg-white border-gray-300 text-gray-600' }}">
        <input type="checkbox" name="expires" value="1" @checked(request()->boolean('expires'))
               onchange="this.form.submit()" class="h-4 w-4 rounded border-gray-300 text-orange-600">
        Échus seulement
    </label>

    <button class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm">
        <i class="fas fa-filter mr-1"></i> Filtrer
    </button>
    @if(request()->hasAny(['statut', 'expires']))
        <a href="{{ route('admin.bus.abonnements') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Réinitialiser</a>
    @endif
</form>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold">Étudiant</th>
                    <th class="px-5 py-3 text-left font-semibold">Formule</th>
                    <th class="px-5 py-3 text-left font-semibold">Validité</th>
                    <th class="px-5 py-3 text-center font-semibold">Trajets</th>
                    <th class="px-5 py-3 text-right font-semibold">Payé</th>
                    <th class="px-5 py-3 text-center font-semibold">État</th>
                    <th class="px-5 py-3 text-left font-semibold">Paiement</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($abonnements as $a)
                    @php
                        $teintes = [
                            'en_attente' => 'bg-amber-100 text-amber-700',
                            'actif' => 'bg-green-100 text-green-700',
                            'expire' => 'bg-gray-100 text-gray-600',
                            'annule' => 'bg-red-100 text-red-700',
                        ];
                        $echu = $a->date_fin && $a->date_fin->isPast();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <p class="font-medium text-gray-900">{{ $a->etudiant->user->full_name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $a->etudiant->matricule_insam ?: '' }}</p>
                        </td>
                        <td class="px-5 py-3 text-gray-700">{{ $a->tarif->libelle ?? $a->tarif->nom ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600 text-xs">
                            {{ $a->date_debut?->format('d/m/Y') ?? '—' }}
                            <i class="fas fa-arrow-right text-gray-300 mx-0.5"></i>
                            <span class="{{ $echu ? 'text-orange-600 font-semibold' : '' }}">
                                {{ $a->date_fin?->format('d/m/Y') ?? '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($a->trajets_restants !== null)
                                <span class="font-semibold text-gray-800">{{ $a->trajets_restants }}</span>
                                @if($a->trajets_en_attente)
                                    <span class="text-xs text-amber-600" title="En attente de confirmation">
                                        (+{{ $a->trajets_en_attente }})
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-400">illimité</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-medium text-gray-800">
                            {{ number_format((int) $a->montant_paye_fcfa, 0, ',', ' ') }}
                            @if($a->montant_du_fcfa > 0)
                                <p class="text-xs text-red-600">reste {{ number_format((int) $a->montant_du_fcfa, 0, ',', ' ') }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $teintes[$a->statut->value] ?? 'bg-gray-100' }}">
                                {{ $a->statut->libelle() }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <p class="text-xs text-gray-600">{{ $a->moyen_paiement ?: '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $a->paye_le?->format('d/m/Y H:i') ?? '' }}</p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center">
                            <i class="fas fa-ticket text-3xl text-gray-300"></i>
                            <p class="mt-3 text-sm text-gray-500">Aucun abonnement.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($abonnements->hasPages())
        <div class="px-5 py-3 border-t border-gray-200">{{ $abonnements->links() }}</div>
    @endif
</div>
@endsection
