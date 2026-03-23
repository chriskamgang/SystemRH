@extends('layouts.admin')

@section('title', 'Rapport Vacataires')
@section('page-title', 'Rapport des Vacataires')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rapport des Vacataires</h2>
            <p class="text-gray-600 mt-1">Suivi des heures et paiements par période</p>
        </div>
        <a href="{{ route('admin.vacataires.report.export', request()->query()) }}" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <i class="fas fa-file-export mr-2"></i> Exporter
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.vacataires.report') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
                <select name="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Année</label>
                <select name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
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
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Vacataires</p>
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
                    <p class="text-sm text-gray-600">UE Actives</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $vacataires->sum('total_ues') }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-book text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Heures ce mois</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($vacataires->sum('heures_mois'), 1) }}h</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-clock text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Montant ce mois</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($vacataires->sum('montant_mois'), 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total {{ $year }}</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($vacataires->sum('montant_total'), 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-chart-line text-2xl text-blue-600"></i>
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
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        UE Actives
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Progression
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Heures ce mois
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Montant ce mois
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Statut
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

                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-semibold text-gray-900">{{ $vacataire->total_ues }}</span>
                    </td>

                    <td class="px-6 py-4">
                        @if($vacataire->total_volume_horaire > 0)
                            @php
                                $progression = round(($vacataire->total_heures_validees / $vacataire->total_volume_horaire) * 100);
                            @endphp
                            <div class="w-full">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>{{ number_format($vacataire->total_heures_validees, 1) }}h / {{ number_format($vacataire->total_volume_horaire, 1) }}h</span>
                                    <span>{{ $progression }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $progression >= 100 ? 'bg-green-500' : ($progression >= 50 ? 'bg-blue-500' : 'bg-orange-400') }}"
                                         style="width: {{ min($progression, 100) }}%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">Restant: {{ number_format($vacataire->total_heures_restantes, 1) }}h</div>
                            </div>
                        @else
                            <span class="text-xs text-gray-400">Aucune UE</span>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-center">
                        <div class="text-sm font-semibold text-gray-900">{{ number_format($vacataire->heures_mois, 1) }}h</div>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <div class="text-sm font-bold text-green-600">{{ number_format($vacataire->montant_mois, 0, ',', ' ') }} FCFA</div>
                        @if($vacataire->montant_total > 0)
                            <div class="text-xs text-gray-500">Total {{ $year }}: {{ number_format($vacataire->montant_total, 0, ',', ' ') }} FCFA</div>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-center">
                        @if($vacataire->statut_paiement === 'paye')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Payé
                            </span>
                        @elseif($vacataire->statut_paiement === 'valide')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-check mr-1"></i>Validé
                            </span>
                        @elseif($vacataire->statut_paiement === 'en_attente')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-hourglass-half mr-1"></i>En attente
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                <i class="fas fa-minus-circle mr-1"></i>Non payé
                            </span>
                        @endif
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <a href="{{ route('admin.vacataires.show', $vacataire->id) }}" class="text-blue-600 hover:text-blue-900" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.vacataires.manual-payments.create') }}" class="text-green-600 hover:text-green-900" title="Nouveau paiement">
                            <i class="fas fa-plus-circle"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="text-gray-400">
                            <i class="fas fa-chart-bar text-6xl mb-4"></i>
                            <p class="text-lg">Aucun vacataire trouvé</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
