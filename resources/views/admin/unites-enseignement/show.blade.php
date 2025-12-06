@extends('layouts.admin')

@section('title', 'Détails de l\'UE')
@section('page-title', 'Détails de l\'Unité d\'Enseignement')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Actions en haut -->
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('admin.vacataires.unites', $ue->enseignant_id) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour à la liste
        </a>

        <div class="flex space-x-3">
            @if($ue->isNonActivee())
                <form action="{{ route('admin.unites-enseignement.activer', $ue->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                        <i class="fas fa-check-circle mr-2"></i>
                        Activer l'UE
                    </button>
                </form>
            @else
                <form action="{{ route('admin.unites-enseignement.desactiver', $ue->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
                        <i class="fas fa-pause-circle mr-2"></i>
                        Désactiver l'UE
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.unites-enseignement.edit', $ue->id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-edit mr-2"></i>
                Modifier
            </a>
        </div>
    </div>

    <!-- Informations principales -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $ue->code_ue }}</h2>
                <p class="text-xl text-gray-600 mt-1">{{ $ue->nom_matiere }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $ue->isActivee() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                <i class="fas fa-circle text-xs mr-1"></i>
                {{ $ue->isActivee() ? 'Activée' : 'Non Activée' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Enseignant -->
            <div class="p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-user-tie text-blue-600 mr-2"></i>
                    Enseignant
                </p>
                <p class="font-semibold text-gray-800">
                    @if($ue->enseignant)
                        {{ $ue->enseignant->full_name }}
                    @else
                        <span class="text-gray-400">Non attribué</span>
                    @endif
                </p>
                @if($ue->enseignant && $ue->enseignant->isVacataire())
                    <p class="text-xs text-gray-600 mt-1">
                        Taux: {{ number_format($ue->enseignant->hourly_rate, 0, ',', ' ') }} FCFA/h
                    </p>
                @endif
            </div>

            <!-- Année académique -->
            <div class="p-4 bg-purple-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-calendar-alt text-purple-600 mr-2"></i>
                    Année Académique
                </p>
                <p class="font-semibold text-gray-800">{{ $ue->annee_academique ?? 'N/A' }}</p>
                @if($ue->semestre)
                    <p class="text-xs text-gray-600 mt-1">Semestre {{ $ue->semestre }}</p>
                @endif
            </div>

            <!-- Spécialité et Niveau -->
            <div class="p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">
                    <i class="fas fa-graduation-cap text-green-600 mr-2"></i>
                    Spécialité / Niveau
                </p>
                <p class="font-semibold text-gray-800">{{ $ue->specialite ?? 'N/A' }}</p>
                @if($ue->niveau)
                    <p class="text-xs text-gray-600 mt-1">{{ $ue->niveau }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Progression des heures -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-chart-line text-blue-600 mr-2"></i>
            Suivi des Heures
        </h3>

        <!-- Barre de progression -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">Progression</span>
                <span class="text-sm font-bold text-blue-600">
                    {{ number_format($ue->pourcentage_progression_validees, 1) }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div
                    class="h-4 rounded-full transition-all duration-500 {{ $ue->pourcentage_progression_validees >= 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                    style="width: {{ min(100, $ue->pourcentage_progression_validees) }}%"
                ></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Volume total -->
            <div class="p-4 bg-gray-50 rounded-lg text-center">
                <p class="text-sm text-gray-600 mb-1">Volume Total</p>
                <p class="text-2xl font-bold text-gray-800">{{ $ue->volume_horaire_total }}h</p>
            </div>

            <!-- Heures validées -->
            <div class="p-4 bg-blue-50 rounded-lg text-center">
                <p class="text-sm text-gray-600 mb-1">Heures Validées</p>
                <p class="text-2xl font-bold text-blue-600">{{ $ue->heures_effectuees_validees }}h</p>
                @if($ue->derniere_mise_a_jour_heures)
                    <p class="text-xs text-gray-500 mt-1">
                        MAJ: {{ $ue->derniere_mise_a_jour_heures->format('d/m/Y') }}
                    </p>
                @endif
            </div>

            <!-- Heures restantes -->
            <div class="p-4 bg-orange-50 rounded-lg text-center">
                <p class="text-sm text-gray-600 mb-1">Heures Restantes</p>
                <p class="text-2xl font-bold {{ $ue->heures_restantes_validees > 0 ? 'text-orange-600' : 'text-green-600' }}">
                    {{ $ue->heures_restantes_validees }}h
                </p>
            </div>

            <!-- Montant total payé -->
            @if($ue->enseignant && $ue->enseignant->isVacataire())
                <div class="p-4 bg-green-50 rounded-lg text-center">
                    <p class="text-sm text-gray-600 mb-1">Montant Payé</p>
                    <p class="text-xl font-bold text-green-600">
                        {{ number_format($ue->total_paye, 0, ',', ' ') }}
                    </p>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
            @endif
        </div>

        @if($ue->enseignant && $ue->enseignant->isSemiPermanent())
            <div class="mt-4 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Semi-permanent:</strong> Les heures sont suivies pour monitoring uniquement (salaire fixe).
                </p>
            </div>
        @endif
    </div>

    <!-- Détails des paiements manuels -->
    @if($ue->paymentDetails && $ue->paymentDetails->count() > 0)
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>
            Historique des Paiements Manuels
        </h3>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Période</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Taux</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($ue->paymentDetails as $detail)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $detail->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ str_pad($detail->payment->month, 2, '0', STR_PAD_LEFT) }}/{{ $detail->payment->year }}
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-blue-600">
                                {{ $detail->heures_saisies }}h
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ number_format($detail->taux_horaire, 0, ',', ' ') }} FCFA/h
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-green-600">
                                {{ number_format($detail->montant, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $detail->payment->status === 'paid' ? 'bg-green-100 text-green-800' :
                                       ($detail->payment->status === 'validated' ? 'bg-blue-100 text-blue-800' :
                                       'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($detail->payment->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100">
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-right font-bold text-gray-700">TOTAL:</td>
                        <td class="px-4 py-3 text-sm font-bold text-blue-600">
                            {{ $ue->paymentDetails->sum('heures_saisies') }}h
                        </td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-sm font-bold text-green-600">
                            {{ number_format($ue->paymentDetails->sum('montant'), 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    <!-- Incidents de présence -->
    @if($ue->presenceIncidents && $ue->presenceIncidents->count() > 0)
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-exclamation-triangle text-orange-600 mr-2"></i>
            Incidents de Présence ({{ $ue->presenceIncidents->count() }})
        </h3>

        <div class="space-y-3">
            @foreach($ue->presenceIncidents as $incident)
                <div class="p-4 border-l-4 rounded {{ $incident->incident_type === 'retard' ? 'border-yellow-500 bg-yellow-50' : 'border-red-500 bg-red-50' }}">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-gray-800">
                                <i class="fas fa-{{ $incident->incident_type === 'retard' ? 'clock' : 'times-circle' }} mr-2"></i>
                                {{ ucfirst($incident->incident_type) }}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <i class="fas fa-calendar mr-1"></i>
                                {{ \Carbon\Carbon::parse($incident->incident_date)->format('d/m/Y à H:i') }}
                            </p>
                            @if($incident->campus)
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    {{ $incident->campus->name }}
                                </p>
                            @endif
                        </div>
                        @if($incident->incident_type === 'retard' && $incident->minutes_retard)
                            <div class="text-right">
                                <p class="text-lg font-bold text-orange-600">
                                    {{ $incident->minutes_retard }} min
                                </p>
                                <p class="text-xs text-gray-600">de retard</p>
                            </div>
                        @endif
                    </div>
                    @if($incident->description)
                        <p class="text-sm text-gray-700 mt-2 italic">{{ $incident->description }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Informations administratives -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-info-circle text-gray-600 mr-2"></i>
            Informations Administratives
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            @if($ue->date_attribution)
                <div class="p-3 bg-gray-50 rounded">
                    <p class="text-gray-600 mb-1">Date d'attribution</p>
                    <p class="font-semibold text-gray-800">
                        {{ $ue->date_attribution->format('d/m/Y à H:i') }}
                    </p>
                </div>
            @endif

            @if($ue->date_activation)
                <div class="p-3 bg-gray-50 rounded">
                    <p class="text-gray-600 mb-1">Date d'activation</p>
                    <p class="font-semibold text-gray-800">
                        {{ $ue->date_activation->format('d/m/Y à H:i') }}
                    </p>
                </div>
            @endif

            @if($ue->creator)
                <div class="p-3 bg-gray-50 rounded">
                    <p class="text-gray-600 mb-1">Créé par</p>
                    <p class="font-semibold text-gray-800">{{ $ue->creator->full_name }}</p>
                </div>
            @endif

            @if($ue->activator)
                <div class="p-3 bg-gray-50 rounded">
                    <p class="text-gray-600 mb-1">Activé par</p>
                    <p class="font-semibold text-gray-800">{{ $ue->activator->full_name }}</p>
                </div>
            @endif

            <div class="p-3 bg-gray-50 rounded">
                <p class="text-gray-600 mb-1">Créé le</p>
                <p class="font-semibold text-gray-800">
                    {{ $ue->created_at->format('d/m/Y à H:i') }}
                </p>
            </div>

            <div class="p-3 bg-gray-50 rounded">
                <p class="text-gray-600 mb-1">Dernière modification</p>
                <p class="font-semibold text-gray-800">
                    {{ $ue->updated_at->format('d/m/Y à H:i') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
