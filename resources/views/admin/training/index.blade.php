@extends('layouts.admin')

@section('title', 'Formations')
@section('page-title', 'Formations')

@section('content')
<div class="space-y-6">

    {{-- ===== EN-TÊTE ===== --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Programmes de Formation</h2>
            <p class="text-gray-600 mt-1">Gérez les programmes, sessions et inscriptions</p>
        </div>
        <a href="{{ route('admin.training.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold shadow">
            <i class="fas fa-plus mr-2"></i> Nouveau programme
        </a>
    </div>

    {{-- ===== ALERTES SESSION ===== --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    {{-- ===== CARTES STATISTIQUES ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-graduation-cap text-blue-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total programmes</p>
                <p class="text-2xl font-bold text-gray-800">{{ $totalPrograms }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-circle text-green-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Actifs</p>
                <p class="text-2xl font-bold text-gray-800">{{ $activePrograms }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-alt text-indigo-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Sessions à venir</p>
                <p class="text-2xl font-bold text-gray-800">{{ $activeSessions }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users text-yellow-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Employés inscrits</p>
                <p class="text-2xl font-bold text-gray-800">{{ $totalEnrolled }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-trophy text-teal-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Complétées</p>
                <p class="text-2xl font-bold text-gray-800">{{ $completedCount }}</p>
            </div>
        </div>
    </div>

    {{-- ===== FILTRES ===== --}}
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.training.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Titre, catégorie..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Tous les types</option>
                    @foreach(\App\Models\TrainingProgram::TYPE_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Niveau</label>
                <select name="level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Tous les niveaux</option>
                    @foreach(\App\Models\TrainingProgram::LEVEL_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ request('level') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Tous</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition text-sm">
                    <i class="fas fa-search mr-1"></i> Filtrer
                </button>
                <a href="{{ route('admin.training.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- ===== TABLEAU DES PROGRAMMES ===== --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">
                {{ $programs->total() }} programme(s) trouvé(s)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type / Niveau</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inscrits</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($programs as $program)
                    <tr class="hover:bg-gray-50 transition">
                        {{-- Programme --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    @if($program->type === 'online')
                                        <i class="fas fa-laptop text-blue-600"></i>
                                    @elseif($program->type === 'presential')
                                        <i class="fas fa-chalkboard-teacher text-blue-600"></i>
                                    @else
                                        <i class="fas fa-blender text-blue-600"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm">{{ $program->title }}</div>
                                    @if($program->category)
                                        <div class="text-xs text-gray-500">{{ $program->category }}</div>
                                    @endif
                                    @if($program->is_mandatory)
                                        <span class="inline-block mt-1 px-1.5 py-0.5 text-xs font-semibold bg-red-100 text-red-700 rounded">
                                            Obligatoire
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Type / Niveau --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $typeColors = [
                                    'online'      => 'bg-cyan-100 text-cyan-800',
                                    'presential'  => 'bg-purple-100 text-purple-800',
                                    'hybrid'      => 'bg-orange-100 text-orange-800',
                                ];
                                $levelColors = [
                                    'beginner'     => 'bg-green-100 text-green-800',
                                    'intermediate' => 'bg-yellow-100 text-yellow-800',
                                    'advanced'     => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $typeColors[$program->type] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ \App\Models\TrainingProgram::TYPE_LABELS[$program->type] ?? $program->type }}
                            </span>
                            <br>
                            <span class="mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full {{ $levelColors[$program->level] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ \App\Models\TrainingProgram::LEVEL_LABELS[$program->level] ?? $program->level }}
                            </span>
                        </td>

                        {{-- Durée --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <i class="fas fa-clock text-gray-400 mr-1"></i>
                            {{ $program->duration_hours }}h
                        </td>

                        {{-- Sessions --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-800">{{ $program->sessions_count }}</span>
                            <span class="text-xs text-gray-500 ml-1">session(s)</span>
                        </td>

                        {{-- Inscrits --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-800">{{ $program->enrollments_count }}</span>
                            <span class="text-xs text-gray-500 ml-1">inscrit(s)</span>
                        </td>

                        {{-- Statut --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($program->is_active)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-circle text-green-500 mr-1" style="font-size:6px;vertical-align:middle;"></i>Actif
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                    <i class="fas fa-circle text-gray-400 mr-1" style="font-size:6px;vertical-align:middle;"></i>Inactif
                                </span>
                            @endif
                        </td>

                        {{-- Date --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $program->created_at->format('d/m/Y') }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.training.show', $program->id) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium"
                                   title="Voir les détails">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                                <a href="{{ route('admin.training.create-session', $program->id) }}"
                                   class="text-indigo-600 hover:text-indigo-800 font-medium"
                                   title="Ajouter une session">
                                    <i class="fas fa-plus"></i> Session
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <i class="fas fa-graduation-cap text-5xl"></i>
                                <p class="text-lg font-medium">Aucun programme de formation trouvé</p>
                                @if(request()->hasAny(['search', 'type', 'level', 'status']))
                                    <a href="{{ route('admin.training.index') }}" class="text-blue-600 hover:underline text-sm">
                                        Réinitialiser les filtres
                                    </a>
                                @else
                                    <a href="{{ route('admin.training.create') }}"
                                       class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                                        Créer le premier programme
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($programs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $programs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
