@extends('layouts.admin')

@section('title', 'Rapport Hebdomadaire')
@section('page-title', 'Rapport Hebdomadaire')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $semiPermanent->full_name }}</h2>
            <p class="text-gray-600 mt-1">
                Rapport hebdomadaire du {{ $startOfWeek->format('d/m/Y') }} au {{ $endOfWeek->format('d/m/Y') }}
            </p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-print mr-2"></i> Imprimer
            </button>
            <a href="{{ route('admin.semi-permanents.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
        </div>
    </div>

    <!-- Sélecteur de semaine -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Choisir une semaine :</label>
            <input
                type="week"
                name="week"
                value="{{ $startOfWeek->format('Y') }}-W{{ $startOfWeek->format('W') }}"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
            >
            <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                <i class="fas fa-calendar mr-2"></i> Afficher
            </button>
        </form>
    </div>

    <!-- Stats de la semaine -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <i class="fas fa-clock text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Heures totales</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalHoursWeek, 1) }}h</p>
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
                    <p class="text-2xl font-bold text-gray-900">{{ $daysWorked }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-book text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Sessions UE</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalUeSessions }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                    <i class="fas fa-chalkboard-teacher text-indigo-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">UE actives</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $unitesEnseignement->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rapport par jour -->
    <div class="space-y-4">
        @foreach($weekDays as $day)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- En-tête du jour -->
                <div class="px-6 py-4 {{ $day['is_work_day'] ? 'bg-green-50 border-l-4 border-green-500' : 'bg-gray-50' }}">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <h3 class="text-lg font-bold text-gray-800 capitalize">
                                {{ $day['day_name'] }}
                            </h3>
                            <span class="text-sm text-gray-600">{{ $day['date']->format('d/m/Y') }}</span>
                            @if($day['is_work_day'])
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    <i class="fas fa-briefcase mr-1"></i> Jour de travail
                                </span>
                            @else
                                <span class="px-2 py-1 bg-gray-200 text-gray-600 text-xs rounded-full">
                                    <i class="fas fa-ban mr-1"></i> Jour de repos
                                </span>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold {{ $day['hours_worked'] > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                {{ number_format($day['hours_worked'], 1) }}h
                            </p>
                            <p class="text-xs text-gray-500">heures effectuées</p>
                        </div>
                    </div>
                </div>

                <!-- Détails du jour -->
                <div class="px-6 py-4">
                    @if($day['check_in'] || $day['check_out'])
                        <!-- Présences générales -->
                        <div class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-door-open mr-2 text-blue-500"></i> Présence générale
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                @if($day['check_in'])
                                    <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                                        <p class="text-xs text-gray-600 mb-1">Check-in</p>
                                        <p class="text-lg font-bold text-green-600">{{ $day['check_in']->timestamp->format('H:i') }}</p>
                                        <p class="text-xs text-gray-500">{{ $day['check_in']->campus->name ?? 'N/A' }}</p>
                                        @if($day['check_in']->is_late)
                                            <span class="inline-block mt-1 px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded">
                                                <i class="fas fa-exclamation-triangle"></i> En retard
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <p class="text-xs text-gray-600 mb-1">Check-in</p>
                                        <p class="text-sm text-gray-400">Pas de pointage</p>
                                    </div>
                                @endif

                                @if($day['check_out'])
                                    <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                        <p class="text-xs text-gray-600 mb-1">Check-out</p>
                                        <p class="text-lg font-bold text-blue-600">{{ $day['check_out']->timestamp->format('H:i') }}</p>
                                        <p class="text-xs text-gray-500">{{ $day['check_out']->campus->name ?? 'N/A' }}</p>
                                    </div>
                                @else
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <p class="text-xs text-gray-600 mb-1">Check-out</p>
                                        <p class="text-sm text-gray-400">Pas de pointage</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($day['ue_presences']->isNotEmpty())
                        <!-- Présences UE/Matières -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-book-open mr-2 text-purple-500"></i> Matières enseignées ({{ $day['ue_presences']->count() }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($day['ue_presences'] as $uePresence)
                                    <div class="p-3 bg-purple-50 rounded-lg border border-purple-200">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900">{{ $uePresence->uniteEnseignement->nom_matiere }}</p>
                                                @if($uePresence->uniteEnseignement->code_ue)
                                                    <p class="text-xs text-gray-600">{{ $uePresence->uniteEnseignement->code_ue }}</p>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <div class="flex items-center gap-3">
                                                    <div>
                                                        <p class="text-xs text-gray-600">Entrée</p>
                                                        <p class="text-sm font-bold text-green-600">{{ $uePresence->check_in_time }}</p>
                                                    </div>
                                                    <i class="fas fa-arrow-right text-gray-400"></i>
                                                    <div>
                                                        <p class="text-xs text-gray-600">Sortie</p>
                                                        <p class="text-sm font-bold text-blue-600">{{ $uePresence->check_out_time ?? '-' }}</p>
                                                    </div>
                                                </div>
                                                @if($uePresence->check_out_time)
                                                    @php
                                                        $checkIn = Carbon\Carbon::parse($uePresence->incident_date . ' ' . $uePresence->check_in_time);
                                                        $checkOut = Carbon\Carbon::parse($uePresence->incident_date . ' ' . $uePresence->check_out_time);
                                                        $duration = $checkIn->diffInMinutes($checkOut) / 60;
                                                    @endphp
                                                    <p class="text-xs text-purple-600 font-medium mt-1">
                                                        Durée: {{ number_format($duration, 1) }}h
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!$day['check_in'] && !$day['check_out'] && $day['ue_presences']->isEmpty())
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-calendar-times text-4xl mb-2"></i>
                            <p>Aucune activité enregistrée ce jour</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Résumé des UE actives -->
    @if($unitesEnseignement->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-book mr-2 text-purple-600"></i> Unités d'Enseignement Actives
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($unitesEnseignement as $ue)
                    <div class="p-4 border border-purple-200 rounded-lg hover:bg-purple-50 transition">
                        <p class="font-medium text-gray-900">{{ $ue->nom_matiere }}</p>
                        @if($ue->code_ue)
                            <p class="text-sm text-gray-600">{{ $ue->code_ue }}</p>
                        @endif
                        <div class="mt-2 flex justify-between text-xs">
                            <span class="text-gray-500">{{ $ue->volume_horaire_total }}h total</span>
                            <span class="text-purple-600 font-medium">{{ $ue->heures_effectuees }}h effectuées</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
