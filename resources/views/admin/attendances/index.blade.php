@extends('layouts.admin')

@section('title', 'Présences')
@section('page-title', 'Gestion des Présences')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Historique des Présences</h2>
            <p class="text-gray-600 mt-1">Historique groupé par employé - Cliquez pour voir les détails</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.attendances.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Date de début -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                <input
                    type="date"
                    name="start_date"
                    value="{{ request('start_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Date de fin -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                <input
                    type="date"
                    name="end_date"
                    value="{{ request('end_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- Employé -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employé</label>
                <select name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les employés</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->first_name }} {{ $user->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Campus -->
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

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="check-in" {{ request('type') === 'check-in' ? 'selected' : '' }}>Pointage entrée</option>
                    <option value="check-out" {{ request('type') === 'check-out' ? 'selected' : '' }}>Pointage sortie</option>
                </select>
            </div>

            <!-- Filtre retard -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Retard</label>
                <select name="is_late" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="1" {{ request('is_late') === '1' ? 'selected' : '' }}>Avec retard</option>
                    <option value="0" {{ request('is_late') === '0' ? 'selected' : '' }}>Sans retard</option>
                </select>
            </div>

            <!-- Boutons -->
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
                <a href="{{ route('admin.attendances.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Liste des employés -->
    <div class="space-y-4">
        @forelse($employees as $employee)
        <div class="bg-white rounded-lg shadow overflow-hidden" x-data="{ open: false }">
            <!-- En-tête de l'employé (cliquable) -->
            <div @click="open = !open" class="cursor-pointer hover:bg-gray-50 transition p-6">
                <div class="flex items-center justify-between">
                    <!-- Info employé -->
                    <div class="flex items-center space-x-4 flex-1">
                        <!-- Avatar -->
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-bold text-lg">{{ substr($employee->user->first_name, 0, 1) }}</span>
                            </div>
                        </div>

                        <!-- Nom et email -->
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $employee->user->full_name }}</h3>
                                @if($employee->user->employee_type === 'enseignant_vacataire')
                                    <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded">Vacataire</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">{{ $employee->user->email }}</p>
                        </div>

                        <!-- Statistiques -->
                        <div class="hidden md:flex items-center space-x-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $employee->total_days }}</div>
                                <div class="text-xs text-gray-500">Jours</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ $employee->total_check_ins }}</div>
                                <div class="text-xs text-gray-500">Entrées</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600">{{ $employee->total_check_outs }}</div>
                                <div class="text-xs text-gray-500">Sorties</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-600">{{ $employee->total_late }}</div>
                                <div class="text-xs text-gray-500">Retards ({{ $employee->late_percentage }}%)</div>
                            </div>
                        </div>

                        <!-- Icône d'expansion -->
                        <div class="flex-shrink-0">
                            <i class="fas fa-chevron-down transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails des présences (accordéon) -->
            <div x-show="open" x-collapse x-cloak>
                <div class="border-t border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                                @if($employee->user->employee_type === 'enseignant_vacataire')
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UE</th>
                                @endif
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entrée</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sortie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durée</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($employee->attendances as $attendance)
                            <tr class="hover:bg-gray-50">
                                <!-- Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $attendance->date->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $attendance->date->locale('fr')->isoFormat('dddd') }}</div>
                                </td>

                                <!-- Campus -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($attendance->campus)
                                    <div class="text-sm text-gray-900">{{ $attendance->campus->name }}</div>
                                    @endif
                                </td>

                                <!-- UE (vacataires uniquement) -->
                                @if($employee->user->employee_type === 'enseignant_vacataire')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($attendance->unite_enseignement)
                                    <div class="flex items-center space-x-1">
                                        <i class="fas fa-book text-blue-500 text-xs"></i>
                                        <span class="text-sm text-gray-900">{{ $attendance->unite_enseignement->code_ue }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $attendance->unite_enseignement->nom_matiere }}</div>
                                    @else
                                    <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                                @endif

                                <!-- Heure d'entrée -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($attendance->check_in_time)
                                    <div class="text-sm text-gray-900">{{ $attendance->check_in_time->format('H:i:s') }}</div>
                                    <div class="text-xs text-gray-500">{{ $attendance->check_in_time->diffForHumans() }}</div>
                                    @else
                                    <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>

                                <!-- Heure de sortie -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($attendance->check_out_time)
                                    <div class="text-sm text-gray-900">{{ $attendance->check_out_time->format('H:i:s') }}</div>
                                    <div class="text-xs text-gray-500">{{ $attendance->check_out_time->diffForHumans() }}</div>
                                    @else
                                    <span class="text-xs text-yellow-600 font-medium">En cours...</span>
                                    @endif
                                </td>

                                <!-- Durée -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($attendance->check_in_time && $attendance->check_out_time)
                                    @php
                                        $duration = $attendance->check_in_time->diff($attendance->check_out_time);
                                        $hours = $duration->h + ($duration->days * 24);
                                    @endphp
                                    <div class="text-sm font-medium text-gray-900">{{ $hours }}h {{ $duration->i }}min</div>
                                    @else
                                    <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>

                                <!-- Statut -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($attendance->is_late)
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            <i class="fas fa-exclamation-circle mr-1"></i> {{ $attendance->late_minutes }} min
                                        </span>
                                    @else
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> À l'heure
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($attendance->check_in)
                                    <a href="{{ route('admin.attendances.show', $attendance->check_in->id) }}" class="text-blue-600 hover:text-blue-900 mr-2" title="Voir détails entrée">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </a>
                                    @endif
                                    @if($attendance->check_out)
                                    <a href="{{ route('admin.attendances.show', $attendance->check_out->id) }}" class="text-green-600 hover:text-green-900" title="Voir détails sortie">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <div class="text-gray-400">
                <i class="fas fa-users text-6xl mb-4"></i>
                <p class="text-lg font-medium">Aucun employé trouvé</p>
                <p class="text-gray-500 mt-2">Ajustez vos filtres ou attendez que des employés fassent des pointages</p>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($employees->hasPages())
    <div class="bg-white rounded-lg shadow px-6 py-4">
        {{ $employees->links() }}
    </div>
    @endif

    <!-- Statistiques globales -->
    @if($employees->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total employés</div>
            <div class="text-2xl font-bold text-gray-800">{{ $employees->total() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Sur cette page</div>
            <div class="text-2xl font-bold text-gray-800">{{ $employees->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Page</div>
            <div class="text-2xl font-bold text-gray-800">{{ $employees->currentPage() }} / {{ $employees->lastPage() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total retards</div>
            <div class="text-2xl font-bold text-red-600">
                {{ $employees->sum('total_late') }}
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
