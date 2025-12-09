@extends('layouts.admin')

@section('title', 'Masse Salariale Non Payée par Cycle')
@section('page-title', 'Masse Salariale Non Payée par Cycle')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Masse Salariale Non Payée par Cycle</h2>
            <p class="text-gray-600 mt-1">Estimation des montants à payer par niveau/cycle</p>
        </div>
        <a href="{{ route('admin.rapports.masse-non-payes-cycle.export', request()->query()) }}" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
            <i class="fas fa-file-pdf mr-2"></i> Exporter PDF
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.rapports.masse-non-payes-cycle') }}" class="grid grid-cols-1 md:grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Année académique</label>
                <select name="annee_academique" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes</option>
                    @foreach($anneesAcademiques as $annee)
                        <option value="{{ $annee }}" {{ request('annee_academique') == $annee ? 'selected' : '' }}>
                            {{ $annee }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
                <a href="{{ route('admin.rapports.masse-non-payes-cycle') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Nombre total d'heures à payer</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($totalHeures, 2) }}h</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-clock text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Montant total estimé</p>
                    <p class="text-3xl font-bold text-red-600">{{ number_format($totalGeneral, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Table détaillée -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Répartition par cycle</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Niveau (Cycle)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nombre UE
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nombre Enseignants
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Heures
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Montant Estimé
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            % du Total
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($masseSalariale as $masse)
                    @php
                        $pourcentage = $totalGeneral > 0 ? ($masse->montant_estime / $totalGeneral) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-100 rounded-full mr-3">
                                    <i class="fas fa-layer-group text-blue-600"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $masse->niveau }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-sm bg-orange-100 text-orange-800 rounded-full">
                                {{ $masse->nombre_ue }} UE
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded-full">
                                {{ $masse->nombre_enseignants }} enseignants
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ number_format($masse->total_heures, 2) }}h</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-red-600">{{ number_format($masse->montant_estime, 0, ',', ' ') }} FCFA</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-full bg-gray-200 rounded-full h-2 mr-2" style="max-width: 100px;">
                                    <div class="bg-red-600 h-2 rounded-full" style="width: {{ $pourcentage }}%"></div>
                                </div>
                                <span class="text-sm text-gray-600">{{ number_format($pourcentage, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            <i class="fas fa-check-circle text-4xl text-green-300 mb-2"></i>
                            <p>Aucune donnée disponible - Tous les cours sont payés !</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($masseSalariale->count() > 0)
                <tfoot class="bg-gray-100 font-bold">
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">TOTAL</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $masseSalariale->sum('nombre_ue') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $masseSalariale->sum('nombre_enseignants') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($totalHeures, 2) }}h</td>
                        <td class="px-6 py-4 text-sm text-red-700">{{ number_format($totalGeneral, 0, ',', ' ') }} FCFA</td>
                        <td class="px-6 py-4 text-sm text-gray-900">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
