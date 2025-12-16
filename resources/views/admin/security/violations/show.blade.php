@extends('admin.layouts.app')

@section('title', 'Détails Violation #' . $violation->id)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
                Violation #{{ $violation->id }}
            </h1>
            <p class="text-gray-600">Détails complets de la tentative de fraude</p>
        </div>
        <a href="{{ route('admin.security.violations.index') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Retour
        </a>
    </div>

    <!-- Alerte si violations récentes multiples -->
    @if($recentViolations >= 3)
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-bold">ALERTE: Violations répétées!</p>
                    <p class="text-sm">Cet utilisateur a <strong>{{ $recentViolations }} violations</strong> dans les dernières 24 heures.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Informations principales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Carte utilisateur -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-user mr-2 text-blue-600"></i>
                Utilisateur
            </h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Nom complet</p>
                    <p class="font-semibold text-gray-800">{{ $violation->user->full_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-semibold text-gray-800">{{ $violation->user->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Téléphone</p>
                    <p class="font-semibold text-gray-800">{{ $violation->user->phone ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Statut du compte</p>
                    @if($violation->user->account_status === 'suspended')
                        <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                            <i class="fas fa-ban mr-1"></i>SUSPENDU
                        </span>
                    @else
                        <span class="bg-green-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                            <i class="fas fa-check mr-1"></i>ACTIF
                        </span>
                    @endif
                </div>

                <!-- Actions utilisateur -->
                <div class="pt-4 border-t">
                    <form method="POST" action="{{ route('admin.security.users.toggle-status', $violation->user->id) }}">
                        @csrf
                        @if($violation->user->account_status === 'suspended')
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                                <i class="fas fa-check-circle mr-2"></i>Réactiver le Compte
                            </button>
                        @else
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition"
                                    onclick="return confirm('Êtes-vous sûr de vouloir suspendre ce compte?')">
                                <i class="fas fa-ban mr-2"></i>Suspendre le Compte
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Carte violation -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-shield-alt mr-2 text-red-600"></i>
                Violation
            </h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Types détectés</p>
                    <p class="font-semibold text-gray-800">{{ $violation->getViolationTypesFormatted() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Sévérité</p>
                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full {{ $violation->getSeverityColorClass() }}">
                        {{ strtoupper($violation->severity) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Statut</p>
                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full {{ $violation->getStatusColorClass() }}">
                        {{ ucfirst($violation->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date/Heure de la tentative</p>
                    <p class="font-semibold text-gray-800">{{ $violation->occurred_at->format('d/m/Y à H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Signalé il y a</p>
                    <p class="font-semibold text-gray-800">{{ $violation->created_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>

        <!-- Carte révision -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-clipboard-check mr-2 text-green-600"></i>
                Révision
            </h3>
            @if($violation->reviewed_at)
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Révisé par</p>
                        <p class="font-semibold text-gray-800">{{ $violation->reviewer->full_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Date de révision</p>
                        <p class="font-semibold text-gray-800">{{ $violation->reviewed_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    @if($violation->admin_notes)
                        <div>
                            <p class="text-sm text-gray-500">Notes admin</p>
                            <p class="font-semibold text-gray-800 bg-gray-50 p-3 rounded">{{ $violation->admin_notes }}</p>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-gray-500 text-center py-4">Non révisé</p>
            @endif
        </div>
    </div>

    <!-- Détails techniques -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Informations réseau -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-network-wired mr-2 text-purple-600"></i>
                Informations Réseau
            </h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Adresse IP</p>
                    <p class="font-mono font-semibold text-gray-800">{{ $violation->ip_address ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">User Agent</p>
                    <p class="font-mono text-xs text-gray-800 bg-gray-50 p-2 rounded break-all">{{ $violation->user_agent ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Informations appareil -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-mobile-alt mr-2 text-indigo-600"></i>
                Informations Appareil
            </h3>
            @if($violation->device_info && count($violation->device_info) > 0)
                <div class="space-y-2">
                    @foreach($violation->device_info as $key => $value)
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                            <span class="text-sm font-semibold text-gray-800">{{ is_array($value) ? json_encode($value) : $value }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-4">Aucune information appareil disponible</p>
            @endif
        </div>
    </div>

    <!-- Historique de l'utilisateur -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-history mr-2 text-orange-600"></i>
            Historique des Violations (10 dernières)
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Types</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sévérité</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($userViolations as $v)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">#{{ $v->id }}</td>
                            <td class="px-4 py-3 text-sm">{{ $v->getViolationTypesFormatted() }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $v->getSeverityColorClass() }}">
                                    {{ strtoupper($v->severity) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $v->occurred_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $v->getStatusColorClass() }}">
                                    {{ ucfirst($v->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    @if($userViolations->isEmpty())
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                Aucune autre violation pour cet utilisateur
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Formulaire de révision -->
    @if($violation->status === 'pending')
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-gavel mr-2 text-blue-600"></i>
                Réviser cette Violation
            </h3>
            <form method="POST" action="{{ route('admin.security.violations.review', $violation->id) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes Administrateur (optionnel)</label>
                    <textarea name="admin_notes" rows="4"
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Ajoutez vos observations sur cette violation..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Action à prendre</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="action" value="dismiss" class="mr-3" required>
                            <div>
                                <span class="font-semibold text-gray-800">Ignorer (Faux positif)</span>
                                <p class="text-sm text-gray-600">La violation n'est pas valide ou est une erreur</p>
                            </div>
                        </label>

                        <label class="flex items-center p-3 border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="action" value="warn" class="mr-3" required>
                            <div>
                                <span class="font-semibold text-gray-800">Avertir uniquement</span>
                                <p class="text-sm text-gray-600">Marquer comme révisé sans action supplémentaire</p>
                            </div>
                        </label>

                        <label class="flex items-center p-3 border border-red-300 rounded-md hover:bg-red-50 cursor-pointer">
                            <input type="radio" name="action" value="suspend" class="mr-3" required>
                            <div>
                                <span class="font-semibold text-red-800">Suspendre le compte</span>
                                <p class="text-sm text-red-600">Bloquer l'accès de l'utilisateur au système</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition">
                        <i class="fas fa-check mr-2"></i>Soumettre la Révision
                    </button>
                    <a href="{{ route('admin.security.violations.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </a>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
