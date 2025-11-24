@extends('layouts.admin')

@section('title', 'Rapport Vacataires')
@section('page-title', 'Rapport des Vacataires')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rapport des Vacataires</h2>
            <p class="text-gray-600 mt-1">Statistiques et performances des vacataires</p>
        </div>
        <a href="{{ route('admin.vacataires.report.export', request()->query()) }}" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <i class="fas fa-file-export mr-2"></i> Exporter
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.vacataires.report') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                <input
                    type="date"
                    name="start_date"
                    value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                <input
                    type="date"
                    name="end_date"
                    value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Campus</label>
                <select name="campus_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les campus</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Vacataires</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $vacataires->count() }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-users text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Jours</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $vacataires->sum('total_days') }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-calendar text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Heures</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($vacataires->sum('total_hours'), 0) }}h</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-clock text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Retards</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $vacataires->sum('total_late') }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Table détaillée -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Vacataire
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campus
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Jours travaillés
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Heures totales
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Retards
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Montant gagné
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($vacataires as $vacataire)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                    <span class="text-purple-600 font-bold">{{ substr($vacataire->first_name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $vacataire->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $vacataire->email }}</div>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            @foreach($vacataire->campuses as $campus)
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded mr-1 mb-1">
                                    {{ $campus->name }}
                                </span>
                            @endforeach
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $vacataire->total_days }} jours</div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">{{ number_format($vacataire->total_hours, 1) }}h</div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($vacataire->total_late > 0)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                {{ $vacataire->total_late }} retard(s)
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Aucun retard
                            </span>
                        @endif
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-green-600">{{ number_format($vacataire->total_pay, 0, ',', ' ') }} FCFA</div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.vacataires.show', $vacataire->id) }}" class="text-blue-600 hover:text-blue-900" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-gray-400">
                            <i class="fas fa-chart-bar text-6xl mb-4"></i>
                            <p class="text-lg">Aucune donnée disponible</p>
                            <p class="text-gray-500 mt-2">Aucun vacataire n'a travaillé sur cette période</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
