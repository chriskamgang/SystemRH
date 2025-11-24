@extends('layouts.admin')

@section('title', 'Détails Employé')
@section('page-title', 'Profil de l\'Employé')

@section('content')
<div class="space-y-6">
    <!-- Header avec actions -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $employee->full_name }}</h2>
            <p class="text-gray-600 mt-1">{{ $employee->employee_id }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.employees.edit', $employee->id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-edit mr-2"></i> Modifier
            </a>
            <a href="{{ route('admin.employees.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
        </div>
    </div>

    <!-- Informations Principales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profil et Informations -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <!-- Photo -->
                <div class="text-center mb-6">
                    @if($employee->photo_url)
                        <img src="{{ asset('storage/' . $employee->photo_url) }}" alt="{{ $employee->full_name }}" class="w-32 h-32 rounded-full mx-auto object-cover shadow-lg">
                    @else
                        <div class="w-32 h-32 rounded-full mx-auto bg-blue-100 flex items-center justify-center shadow-lg">
                            <span class="text-4xl text-blue-600 font-bold">{{ substr($employee->first_name, 0, 1) }}</span>
                        </div>
                    @endif
                </div>

                <!-- Statut -->
                <div class="text-center mb-6">
                    @if($employee->is_active)
                        <span class="inline-flex items-center px-4 py-2 rounded-full bg-green-100 text-green-800 font-semibold">
                            <i class="fas fa-check-circle mr-2"></i> Actif
                        </span>
                    @else
                        <span class="inline-flex items-center px-4 py-2 rounded-full bg-red-100 text-red-800 font-semibold">
                            <i class="fas fa-times-circle mr-2"></i> Inactif
                        </span>
                    @endif
                </div>

                <!-- Informations -->
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="font-medium">{{ $employee->email }}</p>
                    </div>

                    @if($employee->phone)
                    <div>
                        <p class="text-sm text-gray-600">Téléphone</p>
                        <p class="font-medium">{{ $employee->phone }}</p>
                    </div>
                    @endif

                    <div>
                        <p class="text-sm text-gray-600">Rôle</p>
                        <p class="font-medium">{{ $employee->role->display_name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Date de création</p>
                        <p class="font-medium">{{ $employee->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>

                <!-- Appareil lié -->
                @if($employee->device_id)
                <div class="mt-6 pt-6 border-t">
                    <h4 class="font-semibold mb-3">
                        <i class="fas fa-mobile-alt mr-2"></i> Appareil Lié
                    </h4>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-sm font-medium">{{ $employee->device_model }}</p>
                        <p class="text-xs text-gray-600">{{ $employee->device_os }}</p>
                        <form method="POST" action="{{ route('admin.employees.reset-device', $employee->id) }}" class="mt-3" onsubmit="return confirm('Réinitialiser l\'appareil ?');">
                            @csrf
                            <button type="submit" class="text-xs px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white rounded transition">
                                <i class="fas fa-redo mr-1"></i> Réinitialiser
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Campus assignés -->
                @if($employee->campuses->count() > 0)
                <div class="mt-6 pt-6 border-t">
                    <h4 class="font-semibold mb-3">
                        <i class="fas fa-building mr-2"></i> Campus Assignés
                    </h4>
                    <div class="space-y-2">
                        @foreach($employee->campuses as $campus)
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <span class="text-sm">{{ $campus->name }}</span>
                                <span class="text-xs px-2 py-1 rounded {{ $campus->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $campus->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Statistiques et Activités -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Check-ins</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['total_checkins'] }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-clock text-2xl text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Ce Mois</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['this_month_checkins'] }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-calendar text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Retards Total</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['late_count'] }}</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-2xl text-orange-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Retard Moyen</p>
                            <p class="text-3xl font-bold text-gray-800">{{ round($stats['avg_late_minutes']) }} min</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-hourglass-half text-2xl text-red-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historique des présences -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">Historique des Présences (20 dernières)</h3>
                </div>
                <div class="p-6">
                    @if($employee->attendances->count() > 0)
                        <div class="space-y-3">
                            @foreach($employee->attendances as $attendance)
                                <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            @if($attendance->type === 'check_in')
                                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-sign-in-alt text-green-600"></i>
                                                </div>
                                            @else
                                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-sign-out-alt text-blue-600"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium">
                                                {{ $attendance->type === 'check_in' ? 'Check-in' : 'Check-out' }}
                                                @if($attendance->is_late)
                                                    <span class="ml-2 text-xs px-2 py-1 bg-red-100 text-red-800 rounded">
                                                        <i class="fas fa-exclamation-circle"></i> +{{ $attendance->late_minutes }} min
                                                    </span>
                                                @endif
                                            </p>
                                            <p class="text-sm text-gray-600">{{ $attendance->campus->name }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium">{{ $attendance->timestamp->format('H:i') }}</p>
                                        <p class="text-sm text-gray-600">{{ $attendance->timestamp->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($stats['total_checkins'] > 20)
                            <p class="text-center text-gray-500 mt-4">
                                Affichage de 20 sur {{ $stats['total_checkins'] }} entrées
                            </p>
                        @endif
                    @else
                        <div class="text-center py-12 text-gray-400">
                            <i class="fas fa-clock text-6xl mb-4"></i>
                            <p class="text-lg">Aucune présence enregistrée</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
