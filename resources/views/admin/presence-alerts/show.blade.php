@extends('layouts.admin')

@section('title', 'Détails de l\'Incident')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Détails de l'Incident #{{ $incident->id }}</h1>
            <p class="text-gray-600 mt-2">{{ $incident->incident_date->format('d/m/Y') }} - {{ $incident->campus->name }}</p>
        </div>
        <a href="{{ route('admin.presence-alerts.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informations Principales -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Informations Employé -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-user mr-2"></i>
                    Employé
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Nom complet</p>
                        <p class="font-semibold">{{ $incident->user->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="font-semibold">{{ $incident->user->email }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Type d'employé</p>
                        <p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $incident->user->employee_type)) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Campus principal</p>
                        <p class="font-semibold">{{ $incident->campus->name }}</p>
                    </div>
                </div>
            </div>

            <!-- Timeline de l'Incident -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-history mr-2"></i>
                    Timeline
                </h2>
                <div class="space-y-4">
                    <div class="flex">
                        <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2 mr-4"></div>
                        <div>
                            <p class="font-semibold">Check-in</p>
                            <p class="text-sm text-gray-600">
                                @if($incident->attendance)
                                    {{ $incident->attendance->timestamp->format('H:i') }} - Pointage effectué
                                @else
                                    Non disponible
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex">
                        <div class="flex-shrink-0 w-2 h-2 bg-yellow-500 rounded-full mt-2 mr-4"></div>
                        <div>
                            <p class="font-semibold">Notification envoyée</p>
                            <p class="text-sm text-gray-600">{{ substr($incident->notification_sent_at, 0, 5) }}</p>
                        </div>
                    </div>

                    @if($incident->has_responded)
                        <div class="flex">
                            <div class="flex-shrink-0 w-2 h-2 bg-green-500 rounded-full mt-2 mr-4"></div>
                            <div>
                                <p class="font-semibold">Réponse reçue</p>
                                <p class="text-sm text-gray-600">
                                    {{ $incident->responded_at->format('H:i') }}
                                    @if($incident->was_in_zone)
                                        <span class="text-green-600">(Dans la zone)</span>
                                    @else
                                        <span class="text-orange-600">(Hors zone)</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="flex">
                            <div class="flex-shrink-0 w-2 h-2 bg-red-500 rounded-full mt-2 mr-4"></div>
                            <div>
                                <p class="font-semibold">Pas de réponse</p>
                                <p class="text-sm text-gray-600">Deadline: {{ substr($incident->response_deadline, 0, 5) }}</p>
                            </div>
                        </div>
                    @endif

                    @if($incident->validated_at)
                        <div class="flex">
                            <div class="flex-shrink-0 w-2 h-2 bg-purple-500 rounded-full mt-2 mr-4"></div>
                            <div>
                                <p class="font-semibold">{{ $incident->status === 'validated' ? 'Validé' : 'Ignoré' }} par l'admin</p>
                                <p class="text-sm text-gray-600">
                                    {{ $incident->validated_at->format('d/m/Y H:i') }}
                                    par {{ $incident->validator->full_name ?? 'Admin' }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes Admin -->
            @if($incident->admin_notes)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-2">
                        <i class="fas fa-sticky-note mr-2"></i>
                        Notes de l'Admin
                    </h3>
                    <p class="text-gray-700">{{ $incident->admin_notes }}</p>
                </div>
            @endif
        </div>

        <!-- Actions et Statut -->
        <div class="space-y-6">
            <!-- Statut -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Statut</h2>
                @if($incident->status === 'pending')
                    <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4 mb-4">
                        <p class="text-yellow-800 font-semibold"><i class="fas fa-clock mr-2"></i>En attente de décision</p>
                    </div>
                @elseif($incident->status === 'validated')
                    <div class="bg-red-100 border border-red-300 rounded-lg p-4 mb-4">
                        <p class="text-red-800 font-semibold"><i class="fas fa-check-circle mr-2"></i>Pénalité validée</p>
                        <p class="text-sm text-red-700 mt-2">{{ $incident->penalty_hours }} heure(s) de salaire</p>
                    </div>
                @elseif($incident->status === 'ignored')
                    <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 mb-4">
                        <p class="text-gray-800 font-semibold"><i class="fas fa-ban mr-2"></i>Incident ignoré</p>
                    </div>
                @endif

                @if($incident->isPending())
                    <!-- Formulaire de Validation -->
                    <form method="POST" action="{{ route('admin.presence-alerts.validate', $incident->id) }}" class="mb-4">
                        @csrf
                        <textarea
                            name="admin_notes"
                            rows="3"
                            placeholder="Notes (optionnel)..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-3"
                        ></textarea>
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg mb-2">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Valider la Pénalité
                        </button>
                    </form>

                    <!-- Formulaire pour Ignorer -->
                    <form method="POST" action="{{ route('admin.presence-alerts.ignore', $incident->id) }}">
                        @csrf
                        <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-times mr-2"></i>
                            Ignorer l'Incident
                        </button>
                    </form>
                @endif
            </div>

            <!-- Informations Supplémentaires -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Détails</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600">Date de l'incident</p>
                        <p class="font-semibold">{{ $incident->incident_date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Notification envoyée</p>
                        <p class="font-semibold">{{ substr($incident->notification_sent_at, 0, 5) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Deadline de réponse</p>
                        <p class="font-semibold">{{ substr($incident->response_deadline, 0, 5) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">A répondu</p>
                        <p class="font-semibold">{{ $incident->has_responded ? 'Oui' : 'Non' }}</p>
                    </div>
                    @if($incident->has_responded && $incident->was_in_zone !== null)
                        <div>
                            <p class="text-gray-600">Dans la zone</p>
                            <p class="font-semibold">{{ $incident->was_in_zone ? 'Oui' : 'Non' }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-gray-600">Pénalité</p>
                        <p class="font-semibold">{{ $incident->penalty_hours }} heure(s)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
