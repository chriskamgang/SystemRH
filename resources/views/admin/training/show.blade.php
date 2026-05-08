@extends('layouts.admin')

@section('title', 'Formation : ' . $program->title)
@section('page-title', 'Détails de la formation')

@section('content')
<div class="space-y-6">

    {{-- ===== EN-TÊTE ===== --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.training.index') }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $program->title }}</h2>
                <p class="text-gray-500 text-sm mt-0.5">
                    @if($program->category)
                        <span class="mr-2"><i class="fas fa-tag mr-1"></i>{{ $program->category }}</span>
                    @endif
                    Créé le {{ $program->created_at->format('d/m/Y') }}
                    @if($program->creator)
                        par {{ $program->creator->full_name }}
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('admin.training.create-session', $program->id) }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-semibold shadow text-sm">
            <i class="fas fa-plus mr-2"></i> Ajouter une session
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    {{-- ===== RÉSUMÉ DU PROGRAMME ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Infos principales --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-3">Informations du programme</h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Type</p>
                    @php
                        $typeColors = ['online' => 'bg-cyan-100 text-cyan-800', 'presential' => 'bg-purple-100 text-purple-800', 'hybrid' => 'bg-orange-100 text-orange-800'];
                    @endphp
                    <span class="mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full {{ $typeColors[$program->type] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ \App\Models\TrainingProgram::TYPE_LABELS[$program->type] ?? $program->type }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Niveau</p>
                    @php
                        $levelColors = ['beginner' => 'bg-green-100 text-green-800', 'intermediate' => 'bg-yellow-100 text-yellow-800', 'advanced' => 'bg-red-100 text-red-800'];
                    @endphp
                    <span class="mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full {{ $levelColors[$program->level] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ \App\Models\TrainingProgram::LEVEL_LABELS[$program->level] ?? $program->level }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Durée</p>
                    <p class="mt-1 font-semibold text-gray-800"><i class="fas fa-clock text-gray-400 mr-1"></i>{{ $program->duration_hours }}h</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Statut</p>
                    @if($program->is_active)
                        <span class="mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actif</span>
                    @else
                        <span class="mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Inactif</span>
                    @endif
                </div>
            </div>

            @if($program->description)
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-1">Description</p>
                <p class="text-sm text-gray-700 leading-relaxed">{{ $program->description }}</p>
            </div>
            @endif

            @if($program->is_mandatory)
            <div class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                <i class="fas fa-exclamation-circle"></i>
                <span>Ce programme est <strong>obligatoire</strong> pour tous les employés concernés.</span>
            </div>
            @endif
        </div>

        {{-- Stats rapides --}}
        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-calendar-alt text-indigo-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Sessions</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $program->sessions_count }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-users text-yellow-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Inscrits</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $program->enrollments_count }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-trophy text-teal-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Complétées</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $completedEnrollments }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== SESSIONS ===== --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700"><i class="fas fa-calendar-alt text-indigo-500 mr-2"></i>Sessions ({{ $program->sessions->count() }})</h3>
            <a href="{{ route('admin.training.create-session', $program->id) }}"
               class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                <i class="fas fa-plus mr-1"></i> Ajouter
            </a>
        </div>

        @if($program->sessions->isEmpty())
        <div class="px-6 py-10 text-center text-gray-400">
            <i class="fas fa-calendar-times text-4xl mb-3"></i>
            <p>Aucune session planifiée pour ce programme.</p>
            <a href="{{ route('admin.training.create-session', $program->id) }}"
               class="mt-3 inline-block text-indigo-600 hover:underline text-sm">
                Créer la première session
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Formateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lieu / Lien</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Début</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Places max</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscrits</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($program->sessions as $session)
                    @php
                        $sessionStatusColors = [
                            'scheduled'  => 'bg-blue-100 text-blue-800',
                            'ongoing'    => 'bg-green-100 text-green-800',
                            'completed'  => 'bg-gray-100 text-gray-600',
                            'cancelled'  => 'bg-red-100 text-red-800',
                        ];
                        $sessionStatusLabels = [
                            'scheduled'  => 'Planifiée',
                            'ongoing'    => 'En cours',
                            'completed'  => 'Terminée',
                            'cancelled'  => 'Annulée',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-800 font-medium">{{ $session->trainer_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            @if($session->location)
                                <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>{{ $session->location }}
                            @elseif($session->meeting_link)
                                <a href="{{ $session->meeting_link }}" target="_blank" class="text-blue-600 hover:underline">
                                    <i class="fas fa-video mr-1"></i>Lien en ligne
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                            {{ $session->start_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                            {{ $session->end_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-center font-semibold text-gray-700">
                            {{ $session->max_participants }}
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            <span class="font-semibold {{ $session->enrollments->count() >= $session->max_participants ? 'text-red-600' : 'text-gray-700' }}">
                                {{ $session->enrollments->count() }}
                            </span>
                            <span class="text-gray-400"> / {{ $session->max_participants }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sessionStatusColors[$session->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $sessionStatusLabels[$session->status] ?? $session->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ===== INSCRIPTIONS ===== --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-users text-yellow-500 mr-2"></i>Inscriptions ({{ $program->enrollments_count }})
            </h3>
        </div>

        @if($program->enrollments->isEmpty())
        <div class="px-6 py-10 text-center text-gray-400">
            <i class="fas fa-user-slash text-4xl mb-3"></i>
            <p>Aucun employé inscrit à ce programme.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Session</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progression</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscrit le</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($program->enrollments as $enrollment)
                    @php
                        $enrollStatusColors = [
                            'enrolled'    => 'bg-blue-100 text-blue-800',
                            'in_progress' => 'bg-yellow-100 text-yellow-800',
                            'completed'   => 'bg-green-100 text-green-800',
                            'dropped'     => 'bg-red-100 text-red-800',
                        ];
                        $enrollStatusLabels = [
                            'enrolled'    => 'Inscrit',
                            'in_progress' => 'En cours',
                            'completed'   => 'Terminé',
                            'dropped'     => 'Abandonné',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 text-sm">{{ $enrollment->user->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $enrollment->user->employee_id ?? '—' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            @if($enrollment->session)
                                {{ $enrollment->session->start_date->format('d/m/Y') }}
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $enrollStatusColors[$enrollment->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $enrollStatusLabels[$enrollment->status] ?? $enrollment->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($enrollment->progress !== null)
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 w-24">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         style="width: {{ min(100, $enrollment->progress) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600 whitespace-nowrap">{{ $enrollment->progress }}%</span>
                            </div>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $enrollment->score !== null ? number_format($enrollment->score, 1) . ' / 20' : '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                            {{ $enrollment->created_at->format('d/m/Y') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ===== SUPPORTS DE FORMATION ===== --}}
    @if($program->materials->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-file-alt text-teal-500 mr-2"></i>Supports de formation ({{ $program->materials->count() }})
            </h3>
        </div>
        <ul class="divide-y divide-gray-200">
            @foreach($program->materials as $material)
            <li class="px-6 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    @php
                        $matIcons = ['pdf' => 'fa-file-pdf text-red-500', 'video' => 'fa-video text-blue-500', 'document' => 'fa-file-word text-indigo-500', 'link' => 'fa-link text-green-500'];
                        $icon = $matIcons[$material->type] ?? 'fa-file text-gray-400';
                    @endphp
                    <i class="fas {{ $icon }} text-lg w-6 text-center"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $material->title }}</p>
                        @if($material->description)
                            <p class="text-xs text-gray-500">{{ $material->description }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    @if($material->duration_minutes)
                        <span class="text-xs text-gray-500"><i class="fas fa-clock mr-1"></i>{{ $material->duration_minutes }} min</span>
                    @endif
                    @if($material->is_required)
                        <span class="px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 rounded">Requis</span>
                    @endif
                    @if($material->external_url)
                        <a href="{{ $material->external_url }}" target="_blank"
                           class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

</div>
@endsection
