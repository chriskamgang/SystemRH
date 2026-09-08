@extends('layouts.admin')

@section('title', 'Paiement KPay')
@section('page-title', 'Configuration KPay')

@section('content')
<div x-data="{ urlPublique: '{{ $config->urlPublique() }}' }">

    {{-- État courant : la clé publique porte l'environnement dans son préfixe. --}}
    <div class="mb-5 flex flex-wrap items-center gap-3 px-5 py-4 rounded-xl border
                {{ $config->estConfigure() ? 'bg-white border-gray-200' : 'bg-orange-50 border-orange-200' }}">
        @if($config->estConfigure())
            <span class="w-10 h-10 rounded-lg {{ $config->estActif() ? 'bg-green-50' : 'bg-gray-100' }} flex items-center justify-center">
                <i class="fas fa-credit-card {{ $config->estActif() ? 'text-green-600' : 'text-gray-400' }}"></i>
            </span>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800">
                    {{ $config->estActif() ? 'Paiements activés' : 'Paiements désactivés' }}
                </p>
                <p class="text-sm text-gray-500">
                    Environnement :
                    <span class="font-medium {{ $config->estModeTest() ? 'text-amber-600' : 'text-green-600' }}">
                        {{ $config->environnement() }}
                    </span>
                </p>
            </div>
            <form method="POST" action="{{ route('admin.bus.kpay.test') }}">
                @csrf
                <button class="px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium">
                    <i class="fas fa-signal mr-1.5"></i> Tester la connexion
                </button>
            </form>
        @else
            <span class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation text-orange-600"></i>
            </span>
            <div>
                <p class="font-semibold text-orange-900">Clés non renseignées</p>
                <p class="text-sm text-orange-700">Tant qu'elles manquent, aucun abonnement ne peut être encaissé.</p>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.bus.kpay.store') }}" class="space-y-5">
        @csrf

        {{-- Identifiants --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-800">Identifiants d'API</h3>
            <p class="text-sm text-gray-500 mt-1 mb-4">
                L'environnement découle du préfixe de la clé publique : <code class="text-xs bg-gray-100 px-1 rounded">kpay_test_</code>
                pour le bac à sable, <code class="text-xs bg-gray-100 px-1 rounded">kpay_live_</code> pour la production.
                Les clés sont chiffrées en base — laissez un champ vide pour ne pas y toucher.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">URL de base *</label>
                    <input name="url_base" required type="url" value="{{ old('url_base', $config->urlBase()) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <p class="text-xs text-gray-400 mt-1">Identique en test et en production.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Clé publique (X-API-Key)
                        @if(filled($config->cleApi()))
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-green-100 text-green-700 text-xs">enregistrée</span>
                        @endif
                    </label>
                    <input name="cle_api" type="password" autocomplete="off" placeholder="kpay_test_… ou kpay_live_…"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Clé secrète (X-Secret-Key)
                        @if(filled($config->cleSecrete()))
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-green-100 text-green-700 text-xs">enregistrée</span>
                        @endif
                    </label>
                    <input name="cle_secrete" type="password" autocomplete="off" placeholder="64 caractères hexadécimaux"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                    <p class="text-xs text-gray-400 mt-1">Sans préfixe : elle ne distingue pas l'environnement.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Secret de signature des webhooks
                        @if(filled($config->secretWebhook()))
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-green-100 text-green-700 text-xs">enregistré</span>
                        @endif
                    </label>
                    <input name="secret_webhook" type="password" autocomplete="off"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                    <p class="text-xs text-gray-400 mt-1">Vérifie le HMAC-SHA256 des notifications reçues.</p>
                </div>
            </div>
        </div>

        {{-- Transactions --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-800 mb-4">Paramètres des transactions</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Opérateur par défaut *</label>
                    <select name="provider_defaut" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @foreach($providers as $code => $libelle)
                            <option value="{{ $code }}" @selected($config->providerDefaut() === $code)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Devise *</label>
                    <input name="devise" required maxlength="5" value="{{ old('devise', $config->devise()) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Minimum de versement *</label>
                    <div class="relative">
                        <input type="number" name="montant_min_payout" required min="100"
                               value="{{ old('montant_min_payout', $config->montantMinimumPayout()) }}"
                               class="w-full px-3 py-2 pr-14 border border-gray-300 rounded-lg text-sm">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">FCFA</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Minimum KPay : 100 XAF (zone Cameroun).</p>
                </div>
            </div>
        </div>

        {{-- Activation --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-800 mb-4">Activation</h3>
            <div class="space-y-3">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="actif" value="1" @checked($config->estActif())
                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-amber-600">
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Activer les paiements KPay</span>
                        <span class="block text-xs text-gray-500">Désactivé, aucun encaissement ni versement n'est émis.</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="payout_auto" value="1" @checked($config->payoutAutomatique())
                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-amber-600">
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Verser les primes automatiquement à la clôture</span>
                        <span class="block text-xs text-gray-500">Sinon, le versement se déclenche manuellement.</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Webhooks --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-800">URLs de notification (webhooks)</h3>
            <p class="text-sm text-gray-500 mt-1 mb-4">
                KPay accepte quatre URLs : une par famille d'événements, plus une générique en repli.
                Recopiez-les dans le tableau de bord KPay. En développement, le serveur n'est joignable
                que par un tunnel — indiquez alors son adresse ci-dessous.
            </p>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Adresse publique de l'application</label>
                <input name="url_publique" type="url" x-model="urlPublique"
                       placeholder="https://mon-tunnel.ngrok-free.dev"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <p class="text-xs text-gray-400 mt-1">Vide = l'URL de l'application. KPay exige HTTPS valide.</p>
            </div>

            {{-- Les URLs suivent la saisie : pas besoin d'enregistrer pour
                 vérifier ce qu'on va recopier chez KPay. --}}
            @php
                $familles = [
                    'generique' => ['Générique (repli)', '/api/webhook/kpay', 'Tout événement sans URL dédiée.'],
                    'depots' => ['Dépôts — payment.*', '/api/webhook/deposit', 'Encaissement des abonnements.'],
                    'retraits' => ['Retraits — payout.*', '/api/webhook/payout', 'Versement des primes chauffeurs.'],
                    'remboursements' => ['Remboursements — refund.*', '/api/webhook/refunds', 'Un remboursement annule le pass.'],
                ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($familles as $cle => [$titre, $chemin, $aide])
                    <div class="px-3 py-2.5 bg-gray-50 rounded-lg">
                        <p class="text-xs font-semibold text-gray-700">{{ $titre }}</p>
                        <p class="text-xs font-mono text-gray-600 break-all mt-0.5"
                           x-text="(urlPublique ? urlPublique.replace(/\/+$/, '') : '{{ rtrim(config('app.url'), '/') }}') + '{{ $chemin }}'"></p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $aide }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex gap-2">
            <button class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">
                <i class="fas fa-check mr-1.5"></i> Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
