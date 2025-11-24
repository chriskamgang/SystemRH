@extends('layouts.admin')

@section('title', 'Rapports')
@section('page-title', 'Rapports de Présence')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rapports de Présence</h2>
            <p class="text-gray-600 mt-1">Analyse détaillée des pointages et statistiques</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Date de début -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                <input
                    type="date"
                    name="start_date"
                    value="{{ $startDate->format('Y-m-d') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Date de fin -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                <input
                    type="date"
                    name="end_date"
                    value="{{ $endDate->format('Y-m-d') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Boutons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
                <a href="{{ route('admin.reports.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i> Réinitialiser
                </a>
                <a href="{{ route('admin.reports.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                   class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                    <i class="fas fa-download mr-2"></i> Exporter CSV
                </a>
            </div>
        </form>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total pointages</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($overallStats['total_checkins']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Retards</div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($overallStats['total_late']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Employés uniques</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($overallStats['unique_employees']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Taux de ponctualité</div>
            <div class="text-2xl font-bold text-gray-800">{{ $overallStats['punctuality_rate'] }}%</div>
        </div>
    </div>

    <!-- Section des rapports -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Statistiques par employé -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-medium text-gray-800">Statistiques par Employé</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Employé
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Présences
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ponctualité
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Retards
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Heures
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($employeeStats as $stat)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $stat['user']->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $stat['user']->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $stat['total_days'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium {{ $stat['punctuality_rate'] >= 90 ? 'text-green-600' : ($stat['punctuality_rate'] >= 70 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $stat['punctuality_rate'] }}%
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-red-600">{{ $stat['late_days'] }}</div>
                                <div class="text-xs text-gray-500">Moy: {{ $stat['avg_late_minutes'] }} min</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $stat['total_work_hours'] }}h</div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                Aucune donnée de présence trouvée pour cette période
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Statistiques par campus -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-medium text-gray-800">Statistiques par Campus</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Campus
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pointages
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Retards
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ponctualité
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($campusStats as $stat)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $stat['campus']->name }}</div>
                                <div class="text-sm text-gray-500">{{ $stat['campus']->code }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $stat['total_checkins'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-red-600">{{ $stat['late_checkins'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium {{ $stat['punctuality_rate'] >= 90 ? 'text-green-600' : ($stat['punctuality_rate'] >= 70 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $stat['punctuality_rate'] }}%
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                Aucun campus trouvé
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Graphiques d'Analyse</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-2">Répartition des pointages par campus</h4>
                <canvas id="campusChart" height="200"></canvas>
            </div>
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-2">Taux de ponctualité par campus</h4>
                <canvas id="punctualityChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Charger Chart.js si ce n'est pas déjà fait
    if (typeof Chart !== 'undefined') {
        // Graphique de répartition des pointages par campus
        const campusData = @json($campusStats->map(function($stat) {
            return [
                'label' => $stat['campus']->name,
                'value' => $stat['total_checkins']
            ];
        }));
        
        if (campusData.length > 0) {
            new Chart(document.getElementById('campusChart'), {
                type: 'pie',
                data: {
                    labels: campusData.map(item => item.label),
                    datasets: [{
                        data: campusData.map(item => item.value),
                        backgroundColor: [
                            '#36a2eb', '#ff6384', '#ffcd56', '#4bc0c0', 
                            '#9966ff', '#ff9f40', '#ff6b6b', '#4ecdc4'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Graphique de taux de ponctualité par campus
        const punctualityData = @json($campusStats->map(function($stat) {
            return [
                'label' => $stat['campus']->name,
                'value' => $stat['punctuality_rate']
            ];
        }));
        
        if (punctualityData.length > 0) {
            new Chart(document.getElementById('punctualityChart'), {
                type: 'bar',
                data: {
                    labels: punctualityData.map(item => item.label),
                    datasets: [{
                        label: 'Taux de ponctualité (%)',
                        data: punctualityData.map(item => item.value),
                        backgroundColor: '#36a2eb',
                        borderColor: '#36a2eb',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    }
</script>
@endpush