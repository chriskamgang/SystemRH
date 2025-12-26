@extends('layouts.admin')

@section('title', 'Rapport Mensuel Présences')
@section('page-title', 'Rapport Mensuel des Présences Manuelles')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('admin.manual-attendances.index') }}" class="text-gray-700 hover:text-blue-600">
                    <i class="fas fa-clipboard-list mr-2"></i>
                    Présences Manuelles
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500">Rapport Mensuel</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Filtres mois/année -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.manual-attendances.monthly-report') }}" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
                <select name="month" class="w-full border-gray-300 rounded-lg">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Année</label>
                <select name="year" class="w-full border-gray-300 rounded-lg">
                    @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>Afficher
                </button>
                <a href="{{ route('admin.manual-attendances.monthly-report.export', ['month' => $month, 'year' => $year]) }}" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-file-excel mr-2"></i>Exporter
                </a>
            </div>
        </form>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-3xl text-blue-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Employés</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $employeeStats->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock text-3xl text-green-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Heures</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($employeeStats->sum('total_hours'), 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calendar-day text-3xl text-purple-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Jours Travaillés</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $employeeStats->sum('total_days') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-money-bill-wave text-3xl text-orange-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Masse Salariale</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($employeeStats->sum('salary.total'), 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rapport par employé -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Détails par Employé</h3>
            <p class="text-sm text-gray-600">
                Rapport pour {{ \Carbon\Carbon::create()->month($month)->translatedFormat('F') }} {{ $year }}
            </p>
        </div>

        @forelse($employeeStats as $stat)
            <div class="border-b border-gray-200 last:border-b-0">
                <div class="px-6 py-4">
                    <!-- En-tête employé -->
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">{{ $stat['user']->full_name }}</h4>
                            <p class="text-sm text-gray-600">
                                {{ ucfirst(str_replace('_', ' ', $stat['user']->employee_type)) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($stat['salary']['total'], 0, ',', ' ') }} FCFA</p>
                            <p class="text-xs text-gray-500">{{ $stat['salary']['type'] }}</p>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="bg-blue-50 rounded-lg p-3">
                            <p class="text-xs text-gray-600 mb-1">Total Heures</p>
                            <p class="text-xl font-bold text-blue-700">{{ $stat['total_hours'] }}h</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3">
                            <p class="text-xs text-gray-600 mb-1">Jours Travaillés</p>
                            <p class="text-xl font-bold text-green-700">{{ $stat['total_days'] }}</p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-3">
                            <p class="text-xs text-gray-600 mb-1">Sessions</p>
                            <p class="text-xl font-bold text-purple-700">{{ $stat['attendances']->count() }}</p>
                        </div>
                    </div>

                    <!-- Détails salaire -->
                    @if($stat['salary']['details'])
                        <div class="bg-gray-50 rounded-lg p-3 mb-4">
                            <p class="text-sm text-gray-700">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                {{ $stat['salary']['details'] }}
                            </p>
                        </div>
                    @endif

                    <!-- Détails par UE -->
                    @if($stat['ue_breakdown']->isNotEmpty())
                        <div class="mt-4">
                            <h5 class="text-sm font-semibold text-gray-700 mb-2">Détails par Unité d'Enseignement</h5>
                            <div class="space-y-2">
                                @foreach($stat['ue_breakdown'] as $ueData)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $ueData['ue']->code_ue }}</p>
                                            <p class="text-xs text-gray-600">{{ $ueData['ue']->nom_matiere }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-gray-900">{{ round($ueData['hours'], 2) }}h</p>
                                            <p class="text-xs text-gray-500">{{ $ueData['sessions'] }} sessions</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Détails des présences (collapsible) -->
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm text-blue-600 hover:text-blue-800 font-medium">
                            <i class="fas fa-chevron-down mr-1"></i>
                            Voir le détail des {{ $stat['attendances']->count() }} présences
                        </summary>
                        <div class="mt-2 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Horaires</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Session</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">UE</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Durée</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($stat['attendances'] as $attendance)
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ $attendance->date->format('d/m/Y') }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ $attendance->campus->name }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                @if($attendance->session_type === 'jour')
                                                    <span class="text-yellow-600"><i class="fas fa-sun"></i> Jour</span>
                                                @else
                                                    <span class="text-indigo-600"><i class="fas fa-moon"></i> Soir</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-900">
                                                {{ $attendance->uniteEnseignement ? $attendance->uniteEnseignement->code_ue : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-sm font-medium text-blue-600">{{ $attendance->formatted_duration }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-2"></i>
                <p>Aucune présence enregistrée pour cette période</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
