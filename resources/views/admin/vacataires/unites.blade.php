@extends('layouts.admin')

@section('title', 'Unités d\'Enseignement - ' . $vacataire->full_name)
@section('page-title', 'Unités d\'Enseignement')

@section('content')
<div class="space-y-6">
    <!-- Header avec info vacataire -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                    @if($vacataire->photo)
                        <img src="{{ asset('storage/' . $vacataire->photo) }}" alt="{{ $vacataire->full_name }}" class="w-16 h-16 rounded-full object-cover">
                    @else
                        <i class="fas fa-user text-2xl text-blue-600"></i>
                    @endif
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $vacataire->full_name }}</h2>
                    <p class="text-gray-600">{{ $vacataire->email }}</p>
                    <div class="flex items-center mt-2 space-x-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-chalkboard-teacher mr-2"></i>
                            Enseignant Vacataire
                        </span>
                        <span class="text-sm text-gray-600">
                            <i class="fas fa-money-bill-wave mr-1"></i>
                            Taux: <strong>{{ number_format($vacataire->hourly_rate, 0, ',', ' ') }} FCFA/h</strong>
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.vacataires.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
                <a href="{{ route('admin.unites-enseignement.create', ['vacataire_id' => $vacataire->id]) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i> Attribuer UE
                </a>
            </div>
        </div>
    </div>

    <!-- Messages de succès/erreur -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Statistiques globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total UE Activées</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">{{ $unitesActivees->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-book text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Heures Effectuées</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($totalHeuresEffectuees, 1) }}h</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Montant Payé</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ number_format($totalMontantPaye, 0, ',', ' ') }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-purple-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">FCFA</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">UE Non Activées</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2">{{ $unitesNonActivees->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-hourglass-half text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- UE ACTIVÉES -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Unités d'Enseignement Activées ({{ $unitesActivees->count() }})
            </h3>
            <p class="text-sm text-gray-600 mt-1">Ces UE sont actives et comptent pour le paiement</p>
        </div>

        @if($unitesActivees->isEmpty())
            <div class="p-8 text-center">
                <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">Aucune UE activée pour le moment</p>
            </div>
        @else
            <div class="p-6 space-y-4">
                @foreach($unitesActivees as $ue)
                    <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <h4 class="text-xl font-semibold text-gray-800">{{ $ue->nom_matiere }}</h4>
                                    @if($ue->code_ue)
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">{{ $ue->code_ue }}</span>
                                    @endif
                                    <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                        <i class="fas fa-check mr-1"></i> Activée
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Volume horaire</p>
                                        <p class="text-lg font-semibold text-gray-800">{{ number_format($ue->volume_horaire_total, 1) }}h</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Heures effectuées</p>
                                        <p class="text-lg font-semibold text-blue-600">{{ number_format($ue->heures_effectuees, 1) }}h</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Reste à faire</p>
                                        <p class="text-lg font-semibold text-orange-600">{{ number_format($ue->heures_restantes, 1) }}h</p>
                                    </div>
                                </div>

                                <!-- Barre de progression -->
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-700">Progression</span>
                                        <span class="text-sm font-semibold text-blue-600">{{ number_format($ue->pourcentage_progression, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: {{ $ue->pourcentage_progression }}%"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-green-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-600">Montant payé</p>
                                        <p class="text-lg font-bold text-green-600">{{ number_format($ue->montant_paye, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                    <div class="bg-orange-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-600">Montant restant</p>
                                        <p class="text-lg font-bold text-orange-600">{{ number_format($ue->montant_restant, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                    <div class="bg-purple-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-600">Montant maximum</p>
                                        <p class="text-lg font-bold text-purple-600">{{ number_format($ue->montant_max, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                </div>

                                @if($ue->annee_academique || $ue->semestre)
                                    <div class="mt-3 flex items-center space-x-4 text-sm text-gray-600">
                                        @if($ue->annee_academique)
                                            <span><i class="fas fa-calendar-alt mr-1"></i> {{ $ue->annee_academique }}</span>
                                        @endif
                                        @if($ue->semestre)
                                            <span><i class="fas fa-book-open mr-1"></i> Semestre {{ $ue->semestre }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="ml-4 flex flex-col space-y-2">
                                <a href="{{ route('admin.unites-enseignement.show', $ue->id) }}" class="px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm rounded-lg transition" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.unites-enseignement.edit', $ue->id) }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($ue->heures_effectuees == 0)
                                    <form action="{{ route('admin.unites-enseignement.desactiver', $ue->id) }}" method="POST" onsubmit="return confirm('Désactiver cette UE ?')">
                                        @csrf
                                        <button type="submit" class="w-full px-3 py-2 bg-orange-100 hover:bg-orange-200 text-orange-700 text-sm rounded-lg transition" title="Désactiver">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- UE NON ACTIVÉES -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-hourglass-half text-orange-500 mr-2"></i>
                Unités d'Enseignement Non Activées ({{ $unitesNonActivees->count() }})
            </h3>
            <p class="text-sm text-gray-600 mt-1">Ces UE sont attribuées mais pas encore actives</p>
        </div>

        @if($unitesNonActivees->isEmpty())
            <div class="p-8 text-center">
                <i class="fas fa-check-double text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">Toutes les UE sont activées</p>
            </div>
        @else
            <div class="p-6 space-y-4">
                @foreach($unitesNonActivees as $ue)
                    <div class="border border-orange-200 bg-orange-50 rounded-lg p-6">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <h4 class="text-xl font-semibold text-gray-800">{{ $ue->nom_matiere }}</h4>
                                    @if($ue->code_ue)
                                        <span class="px-2 py-1 bg-white text-gray-600 text-xs rounded">{{ $ue->code_ue }}</span>
                                    @endif
                                    <span class="px-3 py-1 bg-orange-200 text-orange-800 text-xs font-medium rounded-full">
                                        <i class="fas fa-clock mr-1"></i> En attente
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                    <div>
                                        <p class="text-sm text-gray-600">Volume horaire</p>
                                        <p class="text-lg font-semibold text-gray-800">{{ number_format($ue->volume_horaire_total, 1) }}h</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Montant potentiel</p>
                                        <p class="text-lg font-semibold text-purple-600">{{ number_format($ue->montant_max, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Date d'attribution</p>
                                        <p class="text-sm text-gray-700">{{ $ue->date_attribution->format('d/m/Y à H:i') }}</p>
                                    </div>
                                </div>

                                @if($ue->annee_academique || $ue->semestre)
                                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                                        @if($ue->annee_academique)
                                            <span><i class="fas fa-calendar-alt mr-1"></i> {{ $ue->annee_academique }}</span>
                                        @endif
                                        @if($ue->semestre)
                                            <span><i class="fas fa-book-open mr-1"></i> Semestre {{ $ue->semestre }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="ml-4 flex flex-col space-y-2">
                                <form action="{{ route('admin.unites-enseignement.activer', $ue->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium">
                                        <i class="fas fa-check mr-2"></i> Activer
                                    </button>
                                </form>
                                <a href="{{ route('admin.unites-enseignement.edit', $ue->id) }}" class="px-3 py-2 bg-white hover:bg-gray-50 text-gray-700 text-sm rounded-lg transition text-center border border-gray-300">
                                    <i class="fas fa-edit mr-1"></i> Modifier
                                </a>
                                <form action="{{ route('admin.unites-enseignement.destroy', $ue->id) }}" method="POST" onsubmit="return confirm('Supprimer cette UE ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 text-sm rounded-lg transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
