@extends('layouts.admin')

@section('title', 'Analytics RH')
@section('page-title', 'Analytics RH')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Analytics RH</h2>
            <p class="text-gray-600 mt-1">Indicateurs clés des ressources humaines — données en temps réel</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 bg-green-100 text-green-800 text-sm rounded-lg font-semibold">
                <i class="fas fa-circle text-green-500 text-xs mr-1"></i> Temps réel
            </span>
            @if($latestSnapshot)
            <span class="px-3 py-1.5 bg-gray-100 text-gray-600 text-sm rounded-lg">
                <i class="fas fa-database mr-1"></i>
                Dernier snapshot : {{ \Carbon\Carbon::create($latestSnapshot->year, $latestSnapshot->month)->translatedFormat('F Y') }}
            </span>
            @endif
        </div>
    </div>

    {{-- ======================== KPI CARDS ROW 1 ======================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        {{-- Total employees --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4 border-l-4 border-blue-500">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total employés actifs</p>
                <p class="text-3xl font-bold text-gray-800">{{ $totalEmployees }}</p>
            </div>
        </div>

        {{-- Attendance rate --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4 border-l-4 border-green-500">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-check text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Taux de présence (30j)</p>
                <p class="text-3xl font-bold text-gray-800">{{ $attendanceRate }}<span class="text-lg font-medium text-gray-400">%</span></p>
            </div>
        </div>

        {{-- Late rate --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4 border-l-4 border-yellow-500">
            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Taux de retards (30j)</p>
                <p class="text-3xl font-bold text-gray-800">{{ $lateRate }}<span class="text-lg font-medium text-gray-400">%</span></p>
            </div>
        </div>

        {{-- Pending leaves --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4 border-l-4 border-orange-500">
            <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-umbrella-beach text-orange-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Congés en attente</p>
                <p class="text-3xl font-bold text-gray-800">{{ $pendingLeaves }}</p>
            </div>
        </div>
    </div>

    {{-- ======================== KPI CARDS ROW 2 ======================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        {{-- Permanent --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-tie text-indigo-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Permanents</p>
                <p class="text-3xl font-bold text-indigo-700">{{ $permanentCount }}</p>
            </div>
        </div>

        {{-- Semi-permanent --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Semi-permanents</p>
                <p class="text-3xl font-bold text-yellow-700">{{ $semiPermanentCount }}</p>
            </div>
        </div>

        {{-- Vacataires --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chalkboard-teacher text-orange-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Vacataires</p>
                <p class="text-3xl font-bold text-orange-700">{{ $vacataireCount }}</p>
            </div>
        </div>

        {{-- Turnover --}}
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-exchange-alt text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Turnover (90j)</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-green-600 font-bold text-lg">+{{ $newHires }}</span>
                    <span class="text-gray-400 text-sm">/</span>
                    <span class="text-red-600 font-bold text-lg">-{{ $departures }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================== CHARTS ROW ======================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Bar chart: department headcount --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Effectif par département</h3>
                    <p class="text-sm text-gray-500">Nombre d'employés actifs par département</p>
                </div>
                <i class="fas fa-chart-bar text-gray-300 text-2xl"></i>
            </div>
            @if(count($deptLabels) > 0)
            <div class="relative" style="height: 280px;">
                <canvas id="deptChart"></canvas>
            </div>
            @else
            <div class="flex items-center justify-center h-48 text-gray-400">
                <p class="text-sm">Aucun département actif.</p>
            </div>
            @endif
        </div>

        {{-- Pie chart: employee type distribution --}}
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Répartition des types</h3>
                    <p class="text-sm text-gray-500">Distribution par contrat</p>
                </div>
                <i class="fas fa-chart-pie text-gray-300 text-2xl"></i>
            </div>
            @if($totalEmployees > 0)
            <div class="relative" style="height: 220px;">
                <canvas id="typeChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-indigo-500 inline-block"></span> Permanents</span>
                    <span class="font-semibold">{{ $permanentCount }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span> Semi-permanents</span>
                    <span class="font-semibold">{{ $semiPermanentCount }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-orange-400 inline-block"></span> Vacataires</span>
                    <span class="font-semibold">{{ $vacataireCount }}</span>
                </div>
            </div>
            @else
            <div class="flex items-center justify-center h-48 text-gray-400">
                <p class="text-sm">Aucune donnée disponible.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Line chart: attendance trend --}}
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Tendance des présences</h3>
                <p class="text-sm text-gray-500">Nombre de check-in mensuels sur les 12 derniers mois</p>
            </div>
            <i class="fas fa-chart-line text-gray-300 text-2xl"></i>
        </div>
        <div class="relative" style="height: 260px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    {{-- ======================== LEAVES SUMMARY ======================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Leave requests breakdown --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-500 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Demandes de congé</h3>
                <p class="text-purple-200 text-sm">Statut global de toutes les demandes</p>
            </div>
            <div class="p-6 space-y-4">
                {{-- Pending --}}
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">En attente</span>
                        <span class="text-sm font-bold text-yellow-600">{{ $pendingLeaves }}</span>
                    </div>
                    @php
                        $totalLeaves = max(1, $pendingLeaves + $approvedLeaves + $rejectedLeaves);
                        $pendingPct  = round(($pendingLeaves  / $totalLeaves) * 100);
                        $approvedPct = round(($approvedLeaves / $totalLeaves) * 100);
                        $rejectedPct = round(($rejectedLeaves / $totalLeaves) * 100);
                    @endphp
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-yellow-400 h-2 rounded-full" style="width: {{ $pendingPct }}%"></div>
                    </div>
                </div>
                {{-- Approved --}}
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">Approuvées</span>
                        <span class="text-sm font-bold text-green-600">{{ $approvedLeaves }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $approvedPct }}%"></div>
                    </div>
                </div>
                {{-- Rejected --}}
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">Rejetées</span>
                        <span class="text-sm font-bold text-red-600">{{ $rejectedLeaves }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: {{ $rejectedPct }}%"></div>
                    </div>
                </div>
                <div class="pt-3 border-t border-gray-100 flex justify-between text-sm text-gray-500">
                    <span>Ce mois-ci</span>
                    <span class="font-semibold text-gray-700">{{ $leavesThisMonth }} nouvelle{{ $leavesThisMonth > 1 ? 's' : '' }} demande{{ $leavesThisMonth > 1 ? 's' : '' }}</span>
                </div>
            </div>
        </div>

        {{-- Turnover / Hiring summary --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Mouvements du personnel</h3>
                <p class="text-teal-200 text-sm">Embauches et départs sur les 90 derniers jours</p>
            </div>
            <div class="p-6 grid grid-cols-2 gap-6">
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <i class="fas fa-user-plus text-green-500 text-3xl mb-2"></i>
                    <p class="text-4xl font-bold text-green-600">{{ $newHires }}</p>
                    <p class="text-sm text-gray-500 mt-1">Nouvelles embauches</p>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <i class="fas fa-user-minus text-red-500 text-3xl mb-2"></i>
                    <p class="text-4xl font-bold text-red-600">{{ $departures }}</p>
                    <p class="text-sm text-gray-500 mt-1">Départs</p>
                </div>
                <div class="col-span-2 pt-4 border-t border-gray-100 text-center">
                    @php
                        $netChange = $newHires - $departures;
                    @endphp
                    <p class="text-sm text-gray-500">Variation nette</p>
                    <p class="text-2xl font-bold {{ $netChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $netChange >= 0 ? '+' : '' }}{{ $netChange }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Latest snapshot data (if exists) --}}
    @if($latestSnapshot)
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-600 px-6 py-4">
            <h3 class="text-lg font-bold text-white">
                Snapshot mensuel —
                {{ \Carbon\Carbon::create($latestSnapshot->year, $latestSnapshot->month)->translatedFormat('F Y') }}
            </h3>
            <p class="text-gray-300 text-sm">Données archivées du dernier snapshot RH</p>
        </div>
        <div class="p-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-800">{{ $latestSnapshot->total_employees ?? '—' }}</p>
                <p class="text-xs text-gray-500 mt-1">Effectif total</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600">{{ $latestSnapshot->new_hires ?? '—' }}</p>
                <p class="text-xs text-gray-500 mt-1">Embauches</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-red-600">{{ $latestSnapshot->departures ?? '—' }}</p>
                <p class="text-xs text-gray-500 mt-1">Départs</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600">
                    {{ $latestSnapshot->avg_attendance_rate !== null ? number_format($latestSnapshot->avg_attendance_rate, 1) . '%' : '—' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Taux présence moy.</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-yellow-600">
                    {{ $latestSnapshot->avg_late_rate !== null ? number_format($latestSnapshot->avg_late_rate, 1) . '%' : '—' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Taux retard moy.</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600">
                    {{ $latestSnapshot->turnover_rate !== null ? number_format($latestSnapshot->turnover_rate, 1) . '%' : '—' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Taux de turnover</p>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- ======================== CHART.JS SCRIPTS ======================== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---- Department headcount bar chart ----
    @if(count($deptLabels) > 0)
    const deptCtx = document.getElementById('deptChart').getContext('2d');
    new Chart(deptCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($deptLabels) !!},
            datasets: [{
                label: 'Employés',
                data: {!! json_encode($deptCounts) !!},
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor:     'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.y + ' employé' + (ctx.parsed.y > 1 ? 's' : '')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        maxRotation: 30,
                        font: { size: 11 }
                    }
                }
            }
        }
    });
    @endif

    // ---- Employee type pie chart ----
    @if($totalEmployees > 0)
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Permanents', 'Semi-permanents', 'Vacataires'],
            datasets: [{
                data: [{{ $permanentCount }}, {{ $semiPermanentCount }}, {{ $vacataireCount }}],
                backgroundColor: [
                    'rgba(99, 102, 241, 0.85)',
                    'rgba(251, 191, 36, 0.85)',
                    'rgba(249, 115, 22, 0.85)',
                ],
                borderColor: ['#6366f1', '#fbbf24', '#f97316'],
                borderWidth: 2,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.label + ' : ' + ctx.parsed + ' (' +
                            Math.round(ctx.parsed / {{ $totalEmployees }} * 100) + '%)'
                    }
                }
            },
            cutout: '60%',
        }
    });
    @endif

    // ---- Attendance trend line chart ----
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($trendLabels) !!},
            datasets: [{
                label: 'Check-ins',
                data: {!! json_encode($trendValues) !!},
                borderColor:     'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2.5,
                pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.35,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.y + ' check-in' + (ctx.parsed.y > 1 ? 's' : '')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: Math.max(1, Math.ceil(Math.max(...{!! json_encode($trendValues) !!}) / 6)) },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });

});
</script>
@endsection
