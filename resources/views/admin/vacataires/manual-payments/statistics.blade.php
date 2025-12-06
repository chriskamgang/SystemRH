@extends('layouts.admin')

@section('title', 'Statistiques Paiements Vacataires')
@section('page-title', 'Statistiques Paiements Manuels Vacataires')

@section('content')
<div class="space-y-6">
    <!-- Cartes résumé -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Total Pay\u00e9 {{ now()->year }}</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($paymentsParMois->sum(), 0, ',', ' ') }}</p>
                    <p class="text-xs opacity-75 mt-1">FCFA</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-coins text-4xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Vacataires Distincts</p>
                    <p class="text-3xl font-bold mt-2">{{ $topVacataires->count() }}</p>
                    <p class="text-xs opacity-75 mt-1">Cette ann\u00e9e</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-users text-4xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Moyenne / Vacataire</p>
                    <p class="text-3xl font-bold mt-2">
                        {{ $topVacataires->count() > 0 ? number_format($paymentsParMois->sum() / $topVacataires->count(), 0, ',', ' ') : '0' }}
                    </p>
                    <p class="text-xs opacity-75 mt-1">FCFA</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-calculator text-4xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique: \u00c9volution des paiements par mois -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">\u00c9volution des paiements par mois ({{ now()->year }})</h3>
        <canvas id="paymentsChart" height="80"></canvas>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Top 10 Vacataires -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Top 10 Vacataires ({{ now()->year }})</h3>
            <div class="space-y-3">
                @foreach($topVacataires as $index => $top)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-600">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $top->user->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $top->user->employee_id }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">{{ number_format($top->total, 0, ',', ' ') }}</p>
                            <p class="text-xs text-gray-500">FCFA</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- R\u00e9partition par d\u00e9partement -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">R\u00e9partition par d\u00e9partement</h3>
            <canvas id="departmentChart" height="200"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Donn\u00e9es pour le graphique d'\u00e9volution
const paymentsData = @json($paymentsParMois);
const months = ['Jan', 'F\u00e9v', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Ao\u00fb', 'Sep', 'Oct', 'Nov', 'D\u00e9c'];
const monthlyData = months.map((month, index) => paymentsData[index + 1] || 0);

// Graphique d'\u00e9volution
const ctx1 = document.getElementById('paymentsChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Paiements (FCFA)',
            data: monthlyData,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Montant: ' + new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('fr-FR').format(value);
                    }
                }
            }
        }
    }
});

// Donn\u00e9es pour le graphique par d\u00e9partement
const departmentData = @json($parDepartement);
const deptLabels = departmentData.map(d => d.department?.name || 'Non assign\u00e9');
const deptValues = departmentData.map(d => d.total);

// Graphique par d\u00e9partement
const ctx2 = document.getElementById('departmentChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: deptLabels,
        datasets: [{
            data: deptValues,
            backgroundColor: [
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)',
                'rgb(139, 92, 246)',
                'rgb(236, 72, 153)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = new Intl.NumberFormat('fr-FR').format(context.parsed);
                        return label + ': ' + value + ' FCFA';
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endsection
