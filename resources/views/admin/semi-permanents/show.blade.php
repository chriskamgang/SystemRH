@extends('layouts.admin')

@section('title', 'Détails Semi-permanent')
@section('page-title', 'Détails du Semi-permanent')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $semiPermanent->full_name }}</h2>
            <p class="text-gray-600 mt-1">Semi-permanent - {{ $semiPermanent->email }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.semi-permanents.unites', $semiPermanent->id) }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                <i class="fas fa-book mr-2"></i> Gérer les UE
            </a>
            <a href="{{ route('admin.employees.edit', $semiPermanent->id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-edit mr-2"></i> Modifier
            </a>
            <a href="{{ route('admin.semi-permanents.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <i class="fas fa-clock text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Heures ce mois</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalHours, 1) }}h</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Jours travaillés</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalDays }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-target text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Heures attendues</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($expectedHours, 0) }}h</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 {{ $realizationRate >= 100 ? 'bg-green-100' : 'bg-orange-100' }} rounded-lg p-3">
                    <i class="fas fa-chart-line {{ $realizationRate >= 100 ? 'text-green-600' : 'text-orange-600' }} text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Taux réalisation</p>
                    <p class="text-2xl font-bold {{ $realizationRate >= 100 ? 'text-green-600' : 'text-orange-600' }}">{{ number_format($realizationRate, 0) }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations détaillées -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Info personnelles -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informations personnelles</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Email</span>
                    <span class="text-gray-900 font-medium">{{ $semiPermanent->email }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Téléphone</span>
                    <span class="text-gray-900 font-medium">{{ $semiPermanent->phone ?? 'Non renseigné' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Salaire mensuel</span>
                    <span class="text-green-600 font-bold">{{ number_format($semiPermanent->monthly_salary ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Volume horaire</span>
                    <span class="text-gray-900 font-medium">{{ number_format($semiPermanent->volume_horaire_hebdomadaire ?? 0, 0) }}h/semaine</span>
                </div>
                <div class="flex justify-between items-start">
                    <span class="text-gray-600">Jours de travail</span>
                    <div class="flex flex-wrap gap-1 justify-end">
                        @if($semiPermanent->jours_travail && is_array($semiPermanent->jours_travail))
                            @foreach($semiPermanent->jours_travail as $jour)
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">{{ ucfirst($jour) }}</span>
                            @endforeach
                        @else
                            <span class="text-gray-400">Non défini</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Campus -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Campus assignés</h3>
            <div class="space-y-2">
                @forelse($semiPermanent->campuses as $campus)
                    <div class="p-3 bg-green-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ $campus->name }}</p>
                                <p class="text-sm text-gray-600">{{ $campus->code }}</p>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">
                                <i class="fas fa-map-marker-alt mr-1"></i> Actif
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-center py-4">Aucun campus assigné</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Historique des présences récentes -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Présences récentes (ce mois)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($attendances as $attendance)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance->timestamp->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($attendance->type === 'check-in')
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Entrée</span>
                                @else
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Sortie</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance->timestamp->format('H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $attendance->campus->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($attendance->is_late)
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">En retard</span>
                                @else
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">À l'heure</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                Aucune présence enregistrée ce mois
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
