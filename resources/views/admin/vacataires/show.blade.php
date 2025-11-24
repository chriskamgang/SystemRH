@extends('layouts.admin')

@section('title', 'Détails Vacataire - ' . $vacataire->full_name)
@section('page-title', 'Détails du Vacataire')

@section('content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div class="flex items-center space-x-4">
                <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center">
                    @if($vacataire->photo)
                        <img src="{{ asset('storage/' . $vacataire->photo) }}" alt="{{ $vacataire->full_name }}" class="w-20 h-20 rounded-full object-cover">
                    @else
                        <span class="text-3xl text-purple-600 font-bold">{{ substr($vacataire->first_name, 0, 1) }}{{ substr($vacataire->last_name, 0, 1) }}</span>
                    @endif
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $vacataire->full_name }}</h2>
                    <p class="text-gray-600">{{ $vacataire->email }}</p>
                    <div class="flex items-center mt-2 space-x-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                            <i class="fas fa-chalkboard-teacher mr-2"></i>
                            Enseignant Vacataire
                        </span>
                        @if($vacataire->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i> Actif
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-times-circle mr-1"></i> Inactif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.vacataires.unites', $vacataire->id) }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    <i class="fas fa-book mr-2"></i> Gérer les UE
                </a>
                <a href="{{ route('admin.vacataires.edit', $vacataire->id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-edit mr-2"></i> Modifier
                </a>
                <a href="{{ route('admin.vacataires.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Taux Horaire</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ number_format($vacataire->hourly_rate, 0, ',', ' ') }}</p>
                    <p class="text-sm text-gray-500">FCFA/heure</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Heures ce mois</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $totalHours }}</p>
                    <p class="text-sm text-gray-500">heures</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Salaire Estimé</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($estimatedPay, 0, ',', ' ') }}</p>
                    <p class="text-sm text-gray-500">FCFA</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coins text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations détaillées -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Informations personnelles -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Informations Personnelles</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-600">Nom complet</p>
                    <p class="text-base font-medium text-gray-800">{{ $vacataire->full_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="text-base font-medium text-gray-800">{{ $vacataire->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Téléphone</p>
                    <p class="text-base font-medium text-gray-800">{{ $vacataire->phone ?? 'Non renseigné' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">ID Employé</p>
                    <p class="text-base font-medium text-gray-800">{{ $vacataire->employee_id ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Campus assignés -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Campus Assignés</h3>
            </div>
            <div class="p-6">
                @if($vacataire->campuses->isEmpty())
                    <p class="text-gray-500 text-sm">Aucun campus assigné</p>
                @else
                    <div class="space-y-2">
                        @foreach($vacataire->campuses as $campus)
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-building text-blue-600 mr-3"></i>
                                    <span class="font-medium text-gray-800">{{ $campus->name }}</span>
                                </div>
                                @if($campus->pivot->is_primary)
                                    <span class="px-2 py-1 bg-blue-600 text-white text-xs rounded-full">Principal</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Historique des pointages -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Historique des Pointages (Mois en cours)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Heure</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($attendances as $attendance)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance->timestamp->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($attendance->type === 'check-in')
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                        <i class="fas fa-sign-in-alt mr-1"></i> Entrée
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                        <i class="fas fa-sign-out-alt mr-1"></i> Sortie
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance->campus->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($attendance->is_late)
                                    <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">
                                        <i class="fas fa-clock mr-1"></i> Retard ({{ $attendance->late_minutes }}min)
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                        <i class="fas fa-check mr-1"></i> À l'heure
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Aucun pointage ce mois-ci</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
