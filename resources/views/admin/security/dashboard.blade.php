@extends('admin.layouts.app')

@section('title', 'Dashboard Sécurité Anti-Fraude')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            <i class="fas fa-shield-alt mr-2 text-red-600"></i>
            Dashboard Sécurité Anti-Fraude
        </h1>
        <p class="text-gray-600">Surveillance des tentatives de fraude et violations de sécurité</p>
    </div>

    <!-- Statistiques principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total violations -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Violations</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($stats['total_violations']) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Aujourd'hui -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Aujourd'hui</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['today'] }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-calendar-day text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Critiques (en attente) -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Violations Critiques</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['critical'] }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-fire text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Comptes suspendus -->
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Comptes Suspendus</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['suspended_users'] }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-ban text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques cette semaine et ce mois -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Cette Semaine</h3>
            <p class="text-4xl font-bold text-blue-600">{{ $stats['this_week'] }}</p>
            <p class="text-gray-500 text-sm mt-1">violations détectées</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Ce Mois</h3>
            <p class="text-4xl font-bold text-indigo-600">{{ $stats['this_month'] }}</p>
            <p class="text-gray-500 text-sm mt-1">violations détectées</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">En Attente</h3>
            <p class="text-4xl font-bold text-orange-600">{{ $stats['pending'] }}</p>
            <p class="text-gray-500 text-sm mt-1">à réviser</p>
        </div>
    </div>

    <!-- Violations par type (30 derniers jours) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-pie mr-2 text-indigo-600"></i>
                Violations par Type (30 derniers jours)
            </h3>
            <div class="space-y-3">
                @foreach($violationsByType as $type => $count)
                    @php
                        $typeLabels = [
                            'vpn' => ['label' => 'VPN détecté', 'icon' => 'fa-shield-alt', 'color' => 'blue'],
                            'mock' => ['label' => 'Fake GPS', 'icon' => 'fa-map-marker-alt', 'color' => 'red'],
                            'root' => ['label' => 'Root/Jailbreak', 'icon' => 'fa-mobile-alt', 'color' => 'orange'],
                            'emulator' => ['label' => 'Émulateur', 'icon' => 'fa-desktop', 'color' => 'yellow'],
                            'gps_inconsistent' => ['label' => 'GPS Incohérent', 'icon' => 'fa-satellite-dish', 'color' => 'purple'],
                        ];
                        $info = $typeLabels[$type] ?? ['label' => $type, 'icon' => 'fa-exclamation', 'color' => 'gray'];
                        $percentage = $violationsByType->sum() > 0 ? round(($count / $violationsByType->sum()) * 100, 1) : 0;
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="fas {{ $info['icon'] }} text-{{ $info['color'] }}-600"></i>
                            <span class="text-gray-700">{{ $info['label'] }}</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-32 bg-gray-200 rounded-full h-2">
                                <div class="bg-{{ $info['color'] }}-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-600 w-12 text-right">{{ $count }}</span>
                        </div>
                    </div>
                @endforeach

                @if($violationsByType->isEmpty())
                    <p class="text-gray-500 text-center py-4">Aucune violation dans les 30 derniers jours</p>
                @endif
            </div>
        </div>

        <!-- Top utilisateurs avec violations -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-users mr-2 text-red-600"></i>
                Top 10 Utilisateurs (30 derniers jours)
            </h3>
            <div class="space-y-2">
                @foreach($topOffenders as $user)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex items-center space-x-3">
                            <div class="bg-red-100 rounded-full p-2">
                                <i class="fas fa-user text-red-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $user->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                        <span class="bg-red-600 text-white text-sm font-bold px-3 py-1 rounded-full">
                            {{ $user->security_violations_count }}
                        </span>
                    </div>
                @endforeach

                @if($topOffenders->isEmpty())
                    <p class="text-gray-500 text-center py-4">Aucune violation dans les 30 derniers jours</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Violations critiques récentes -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-exclamation-circle mr-2 text-red-600"></i>
                Violations Critiques en Attente
            </h3>
            <a href="{{ route('admin.security.violations.index', ['status' => 'pending', 'severity' => 'critical']) }}"
               class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                Voir toutes <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Violations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sévérité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($criticalViolations as $violation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $violation->user->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $violation->user->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700">{{ $violation->getViolationTypesFormatted() }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $violation->getSeverityColorClass() }}">
                                    {{ strtoupper($violation->severity) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $violation->occurred_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.security.violations.show', $violation->id) }}"
                                   class="text-blue-600 hover:text-blue-800 font-semibold">
                                    Réviser <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    @if($criticalViolations->isEmpty())
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                                <p>Aucune violation critique en attente</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="mt-8 flex justify-center space-x-4">
        <a href="{{ route('admin.security.violations.index') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition">
            <i class="fas fa-list mr-2"></i>
            Toutes les Violations
        </a>
        <a href="{{ route('admin.security.violations.index', ['status' => 'pending']) }}"
           class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition">
            <i class="fas fa-clock mr-2"></i>
            En Attente de Révision
        </a>
    </div>
</div>
@endsection
