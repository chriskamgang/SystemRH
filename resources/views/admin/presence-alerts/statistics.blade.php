@extends('layouts.admin')

@section('title', 'Statistiques des Alertes')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Statistiques des Alertes de Présence</h1>
            <p class="text-gray-600 mt-2">Analyse des incidents et taux de réponse</p>
        </div>
        <a href="{{ route('admin.presence-alerts.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour
        </a>
    </div>

    <!-- Filtres de Période -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" class="flex items-center space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="pt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Statistiques Globales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Incidents -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <i class="fas fa-bell text-white text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Incidents</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_incidents'] }}</p>
                </div>
            </div>
        </div>

        <!-- En Attente -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">En Attente</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <!-- Validés -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                    <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Validés</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['validated'] }}</p>
                </div>
            </div>
        </div>

        <!-- Ignorés -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gray-500 rounded-md p-3">
                    <i class="fas fa-ban text-white text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Ignorés</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['ignored'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Taux de Réponse et Pénalités -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Taux de Réponse -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-pie mr-2"></i>
                Taux de Réponse
            </h2>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Ont répondu</span>
                        <span class="text-sm font-medium text-gray-700">{{ $stats['responded'] }} / {{ $stats['total_incidents'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        @php
                            $responseRate = $stats['total_incidents'] > 0 ? ($stats['responded'] / $stats['total_incidents']) * 100 : 0;
                        @endphp
                        <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $responseRate }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ round($responseRate, 1) }}% de taux de réponse</p>
                </div>

                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">N'ont pas répondu</span>
                        <span class="text-sm font-medium text-gray-700">{{ $stats['not_responded'] }} / {{ $stats['total_incidents'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        @php
                            $noResponseRate = $stats['total_incidents'] > 0 ? ($stats['not_responded'] / $stats['total_incidents']) * 100 : 0;
                        @endphp
                        <div class="bg-red-600 h-2.5 rounded-full" style="width: {{ $noResponseRate }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ round($noResponseRate, 1) }}% sans réponse</p>
                </div>
            </div>
        </div>

        <!-- Pénalités Totales -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-dollar-sign mr-2"></i>
                Pénalités Appliquées
            </h2>
            <div class="text-center py-8">
                <p class="text-5xl font-bold text-red-600">{{ $stats['total_penalty_hours'] }}</p>
                <p class="text-gray-600 mt-2">Heures de salaire coupées</p>
                <p class="text-sm text-gray-500 mt-4">Sur la période du {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Top 10 Employés -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-trophy mr-2"></i>
            Top 10 - Employés avec le Plus d'Incidents
        </h2>
        @if($topUsers->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre d'Incidents</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($topUsers as $index => $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $item->user->full_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $item->user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        {{ $item->incident_count }} incident(s)
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-center text-gray-500 py-8">Aucun incident trouvé pour cette période.</p>
        @endif
    </div>
</div>
@endsection
