@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Employés -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Employés</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['total_employees'] }}</p>
                    <p class="text-sm text-green-600 mt-2">
                        <i class="fas fa-arrow-up"></i> {{ $stats['active_employees'] }} actifs
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Présents Aujourd'hui -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Présents Aujourd'hui</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['present_today'] }}</p>
                    <p class="text-sm text-gray-600 mt-2">
                        Sur {{ $stats['total_employees'] }} employés
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-check text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Retards du Mois -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Retards ce Mois</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['late_this_month'] }}</p>
                    <p class="text-sm text-orange-600 mt-2">
                        {{ $stats['late_rate'] }}% de retard
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>

        <!-- Campus Actifs -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Campus Actifs</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $stats['active_campuses'] }}</p>
                    <p class="text-sm text-gray-600 mt-2">
                        Sur {{ $stats['total_campuses'] }} campus
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-building text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Graphique Présences -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Présences des 7 Derniers Jours</h3>
            <canvas id="attendanceChart" height="200"></canvas>
        </div>

        <!-- Graphique Par Campus -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Présences par Campus</h3>
            <canvas id="campusChart" height="200"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Derniers Check-ins -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold">Derniers Check-ins</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($recent_checkins as $checkin)
                    <div class="flex items-center justify-between py-3 border-b last:border-0">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 font-bold">{{ substr($checkin->user->first_name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="font-medium">{{ $checkin->user->full_name }}</p>
                                <p class="text-sm text-gray-600">{{ $checkin->campus->name }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium">{{ $checkin->timestamp->format('H:i') }}</p>
                            @if($checkin->is_late)
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">
                                <i class="fas fa-exclamation-circle mr-1"></i> Retard
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                                <i class="fas fa-check-circle mr-1"></i> À l'heure
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('admin.attendances.index') }}" class="block mt-4 text-center text-blue-600 hover:text-blue-800">
                    Voir tout →
                </a>
            </div>
        </div>

        <!-- Employés en Retard -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold">Retards d'Aujourd'hui</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($late_today as $late)
                    <div class="flex items-center justify-between py-3 border-b last:border-0">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                <span class="text-orange-600 font-bold">{{ substr($late->user->first_name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="font-medium">{{ $late->user->full_name }}</p>
                                <p class="text-sm text-gray-600">{{ $late->campus->name }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-orange-600">+{{ $late->late_minutes }} min</p>
                            <p class="text-xs text-gray-600">{{ $late->timestamp->format('H:i') }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-gray-500 py-8">
                        <i class="fas fa-check-circle text-4xl mb-2"></i><br>
                        Aucun retard aujourd'hui !
                    </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Campus Overview -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Aperçu des Campus</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($campuses as $campus)
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold">{{ $campus->name }}</h4>
                        <span class="px-2 py-1 text-xs rounded {{ $campus->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $campus->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">{{ $campus->address }}</p>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <p class="text-gray-600">Présents</p>
                            <p class="font-bold text-blue-600">{{ $campus->present_count }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Total Employés</p>
                            <p class="font-bold">{{ $campus->total_employees }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Graphique des présences
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chart_data['labels']) !!},
            datasets: [{
                label: 'Check-ins',
                data: {!! json_encode($chart_data['checkins']) !!},
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }, {
                label: 'Retards',
                data: {!! json_encode($chart_data['late']) !!},
                borderColor: 'rgb(249, 115, 22)',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
    });

    // Graphique par campus
    const campusCtx = document.getElementById('campusChart').getContext('2d');
    new Chart(campusCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($campus_chart['labels']) !!},
            datasets: [{
                label: 'Présences',
                data: {!! json_encode($campus_chart['data']) !!},
                backgroundColor: 'rgba(59, 130, 246, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>
@endpush
@endsection
