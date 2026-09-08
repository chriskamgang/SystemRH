{{-- Menu de la régulation du transport.

     L'espace bus n'a pas de permissions propres : il suit celles d'Estuaire
     RH, dont il partage la session et les comptes administrateurs. --}}

@php
    $lien = fn (string $route) => request()->routeIs($route)
        ? 'bg-amber-600'
        : 'hover:bg-gray-800';

    // Compteurs de la barre : ce qui demande une intervention.
    $busEnPanne = $toursAnomalie = 0;
    try { $busEnPanne = \App\Models\Bus::where('statut', \App\Enums\StatutBus::EnPanne)->count(); } catch (\Exception $e) {}
    try {
        $toursAnomalie = \App\Models\Tournee::where('anomalie_duree', true)
            ->whereHas('affectation', fn ($a) => $a->whereDate('date_service', today()))
            ->count();
    } catch (\Exception $e) {}
@endphp

<p class="px-4 pt-4 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Régulation</p>

<a href="{{ route('admin.bus.dashboard') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.dashboard') }}">
    <i class="fas fa-gauge-high w-5"></i>
    <span class="ml-3">Tableau de bord</span>
</a>

<p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Flotte</p>

<a href="{{ route('admin.bus.vehicules') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.vehicules') }}">
    <i class="fas fa-bus w-5"></i>
    <span class="ml-3">Véhicules</span>
    @if($busEnPanne > 0)
        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $busEnPanne }}</span>
    @endif
</a>

<a href="{{ route('admin.bus.chauffeurs') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.chauffeurs') }}">
    <i class="fas fa-id-card w-5"></i>
    <span class="ml-3">Chauffeurs</span>
</a>

<p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Réseau</p>

<a href="{{ route('admin.bus.lignes') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.lignes') }}">
    <i class="fas fa-route w-5"></i>
    <span class="ml-3">Lignes</span>
</a>

<a href="{{ route('admin.bus.lieux') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.lieux') }}">
    <i class="fas fa-map-pin w-5"></i>
    <span class="ml-3">Points & campus</span>
</a>

<a href="{{ route('admin.bus.parcours') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.parcours') }}">
    <i class="fas fa-diagram-project w-5"></i>
    <span class="ml-3">Parcours</span>
</a>

<p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Exploitation</p>

<a href="{{ route('admin.bus.affectations') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.affectations') }}">
    <i class="fas fa-calendar-day w-5"></i>
    <span class="ml-3">Affectations</span>
</a>

<a href="{{ route('admin.bus.tournees') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.tournees') }}">
    <i class="fas fa-clipboard-list w-5"></i>
    <span class="ml-3">Tournées</span>
    @if($toursAnomalie > 0)
        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-orange-500 rounded-full">{{ $toursAnomalie }}</span>
    @endif
</a>

<p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Étudiants</p>

<a href="{{ route('admin.bus.etudiants') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.etudiants') }}">
    <i class="fas fa-user-graduate w-5"></i>
    <span class="ml-3">Inscrits</span>
</a>

<a href="{{ route('admin.bus.abonnements') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.abonnements') }}">
    <i class="fas fa-ticket w-5"></i>
    <span class="ml-3">Abonnements</span>
</a>

<p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Paramètres</p>

<a href="{{ route('admin.bus.tarifs') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.tarifs') }}">
    <i class="fas fa-tags w-5"></i>
    <span class="ml-3">Formules & tarifs</span>
</a>

<a href="{{ route('admin.bus.kpay') }}"
   class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ $lien('admin.bus.kpay') }}">
    <i class="fas fa-credit-card w-5"></i>
    <span class="ml-3">Paiement KPay</span>
    @if(! app(\App\Services\Kpay\KpayConfig::class)->estConfigure())
        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-orange-500 rounded-full">!</span>
    @endif
</a>
