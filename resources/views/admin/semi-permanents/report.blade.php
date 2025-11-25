@extends('layouts.admin')

@section('title', 'Rapport Semi-permanents')
@section('page-title', 'Rapport d\'activité - Semi-permanents')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rapport d'activité</h2>
            <p class="text-gray-600 mt-1">Suivi détaillé des heures et performances des semi-permanents</p>
        </div>
        <button onclick="window.print()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <i class="fas fa-print mr-2"></i> Imprimer
        </button>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Campus</label>
                <select name="campus_id" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">Tous</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Table Rapport -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semi-permanent</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures réalisées</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures attendues</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Taux</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Retards</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UE assignées</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures UE</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($semiPermanents as $sp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                    <span class="text-green-600 font-bold text-sm">{{ substr($sp->first_name, 0, 1) }}</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $sp->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $sp->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $sp->total_days }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                            {{ number_format($sp->total_hours, 1) }}h
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ number_format($sp->expected_hours, 0) }}h
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 rounded {{ $sp->realization_rate >= 100 ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ number_format($sp->realization_rate, 0) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($sp->total_late > 0)
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded">
                                    {{ $sp->total_late }}
                                </span>
                            @else
                                <span class="text-green-600">
                                    <i class="fas fa-check-circle"></i> 0
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded">
                                {{ $sp->total_ue }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-600 font-medium">
                            {{ number_format($sp->total_heures_ue ?? 0, 1) }}h
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <i class="fas fa-chart-bar text-6xl mb-4"></i>
                            <p class="text-lg">Aucune donnée pour cette période</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($semiPermanents->isNotEmpty())
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">TOTAL</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $semiPermanents->sum('total_days') }}</td>
                        <td class="px-6 py-4 text-sm text-green-600">{{ number_format($semiPermanents->sum('total_hours'), 1) }}h</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($semiPermanents->sum('expected_hours'), 0) }}h</td>
                        <td class="px-6 py-4 text-sm">
                            {{ number_format($semiPermanents->avg('realization_rate'), 0) }}%
                        </td>
                        <td class="px-6 py-4 text-sm text-red-600">{{ $semiPermanents->sum('total_late') }}</td>
                        <td class="px-6 py-4 text-sm text-purple-600">{{ $semiPermanents->sum('total_ue') }}</td>
                        <td class="px-6 py-4 text-sm text-purple-600">{{ number_format($semiPermanents->sum('total_heures_ue'), 1) }}h</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <!-- Légende -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-semibold text-blue-900 mb-2">
            <i class="fas fa-info-circle mr-2"></i> Notes importantes
        </h4>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>Salaire fixe:</strong> Les semi-permanents reçoivent un salaire mensuel fixe, quel que soit le nombre d'heures travaillées.</li>
            <li><strong>Suivi horaire:</strong> Le suivi des heures sert uniquement au monitoring et à l'évaluation des performances.</li>
            <li><strong>Taux de réalisation:</strong> Pourcentage des heures contractuelles effectivement travaillées.</li>
            <li><strong>Heures UE:</strong> Heures de cours dispensées dans le cadre des unités d'enseignement assignées.</li>
        </ul>
    </div>
</div>
@endsection
