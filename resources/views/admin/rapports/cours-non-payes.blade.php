@extends('layouts.admin')

@section('title', 'Cours Non Payés')
@section('page-title', 'Etat des Cours Non Payés')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Etat des Cours Non Payés</h2>
            <p class="text-gray-600 mt-1">Liste des unités d'enseignement (UE) en attente de paiement</p>
        </div>
        <a href="{{ route('admin.rapports.cours-non-payes.export', request()->query()) }}" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
            <i class="fas fa-file-pdf mr-2"></i> Exporter PDF
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.rapports.cours-non-payes') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                <select name="specialite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes</option>
                    @foreach($specialites as $spec)
                        <option value="{{ $spec }}" {{ request('specialite') == $spec ? 'selected' : '' }}>
                            {{ $spec }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Niveau (Cycle)</label>
                <select name="niveau" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach($niveaux as $niveau)
                        <option value="{{ $niveau }}" {{ request('niveau') == $niveau ? 'selected' : '' }}>
                            {{ $niveau }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Semestre</label>
                <select name="semestre" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="1" {{ request('semestre') == '1' ? 'selected' : '' }}>Semestre 1</option>
                    <option value="2" {{ request('semestre') == '2' ? 'selected' : '' }}>Semestre 2</option>
                </select>
            </div>

            <div class="flex items-end gap-2 md:col-span-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
                <a href="{{ route('admin.rapports.cours-non-payes') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
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
                    <p class="text-sm text-gray-600">Nombre de cours non payés</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalCours }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-exclamation-triangle text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Montant estimé à payer</p>
                    <p class="text-3xl font-bold text-red-600">{{ number_format($totalMontantEstime, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-money-bill-wave text-2xl text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Table détaillée -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Liste des cours non payés</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Code UE
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Matière
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Enseignant
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Spécialité
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Niveau
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Volume horaire
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Montant estimé
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($cours as $ue)
                    @php
                        $montantEstime = 0;
                        if ($ue->enseignant && !$ue->enseignant->isSemiPermanent()) {
                            $montantEstime = $ue->volume_horaire_total * ($ue->enseignant->hourly_rate ?? 0);
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-blue-600">{{ $ue->code_ue }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $ue->nom_matiere }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ue->enseignant)
                                <div class="text-sm text-gray-900">{{ $ue->enseignant->full_name }}</div>
                                <div class="text-sm text-gray-500">
                                    @if($ue->enseignant->isSemiPermanent())
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Semi-permanent</span>
                                    @else
                                        {{ number_format($ue->enseignant->hourly_rate, 0) }} FCFA/h
                                    @endif
                                </div>
                            @else
                                <span class="text-sm text-gray-400">Non attribué</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">
                                {{ $ue->specialite ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                {{ $ue->niveau ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ number_format($ue->volume_horaire_total, 2) }}h</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ue->enseignant && $ue->enseignant->isSemiPermanent())
                                <span class="text-sm text-gray-400 italic">Salaire fixe</span>
                            @else
                                <span class="text-sm font-bold text-orange-600">{{ number_format($montantEstime, 0, ',', ' ') }} FCFA</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                <i class="fas fa-clock mr-1"></i> En attente
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            <i class="fas fa-check-circle text-4xl text-green-300 mb-2"></i>
                            <p>Aucun cours non payé trouvé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $cours->links() }}
        </div>
    </div>
</div>
@endsection
