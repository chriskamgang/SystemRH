@extends('layouts.admin')

@section('title', 'Rapport de Pointages')
@section('page-title', 'Rapport de Pointages')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rapport de Pointages</h2>
            <p class="text-gray-600 mt-1">Historique des heures d'entree, sortie et total travaille</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.attendance-report.export-pdf', request()->query()) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm">
                <i class="fas fa-file-pdf mr-2"></i> PDF
            </a>
            <a href="{{ route('admin.attendance-report.export-excel', request()->query()) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition text-sm">
                <i class="fas fa-file-excel mr-2"></i> Excel
            </a>
        </div>
    </div>

    <!-- Onglets Jour / Semaine / Mois -->
    <div class="bg-white rounded-lg shadow">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                @foreach(['day' => 'Jour', 'week' => 'Semaine', 'month' => 'Mois'] as $key => $label)
                <a href="{{ route('admin.attendance-report.index', array_merge(request()->except('period', 'page'), ['period' => $key, 'date' => $date])) }}"
                   class="flex-1 text-center py-4 px-1 border-b-2 font-medium text-sm transition
                   {{ $period === $key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas {{ $key === 'day' ? 'fa-calendar-day' : ($key === 'week' ? 'fa-calendar-week' : 'fa-calendar-alt') }} mr-2"></i>
                    {{ $label }}
                </a>
                @endforeach
            </nav>
        </div>

        <!-- Navigation de periode + Filtres -->
        <div class="p-6 space-y-4">
            <!-- Navigation de periode -->
            <div class="flex items-center justify-center space-x-4">
                @php
                    $prevDate = match($period) {
                        'day' => $startDate->copy()->subDay()->toDateString(),
                        'week' => $startDate->copy()->subWeek()->toDateString(),
                        'month' => $startDate->copy()->subMonth()->toDateString(),
                    };
                    $nextDate = match($period) {
                        'day' => $startDate->copy()->addDay()->toDateString(),
                        'week' => $startDate->copy()->addWeek()->toDateString(),
                        'month' => $startDate->copy()->addMonth()->toDateString(),
                    };
                    $todayDate = today()->toDateString();
                @endphp

                <a href="{{ route('admin.attendance-report.index', array_merge(request()->except('date', 'page'), ['date' => $prevDate])) }}"
                   class="p-2 rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-chevron-left text-gray-600"></i>
                </a>

                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        @if($period === 'day')
                            {{ $startDate->locale('fr')->isoFormat('dddd DD MMMM YYYY') }}
                        @elseif($period === 'week')
                            {{ $startDate->format('d') }} - {{ $endDate->locale('fr')->isoFormat('DD MMMM YYYY') }}
                        @else
                            {{ $startDate->locale('fr')->isoFormat('MMMM YYYY') }}
                        @endif
                    </h3>
                </div>

                @if($startDate->lt(today()))
                <a href="{{ route('admin.attendance-report.index', array_merge(request()->except('date', 'page'), ['date' => $nextDate])) }}"
                   class="p-2 rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-chevron-right text-gray-600"></i>
                </a>
                @else
                <span class="p-2 text-gray-300"><i class="fas fa-chevron-right"></i></span>
                @endif

                <a href="{{ route('admin.attendance-report.index', array_merge(request()->except('date', 'page'), ['date' => $todayDate])) }}"
                   class="ml-2 px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition">
                    Aujourd'hui
                </a>
            </div>

            <!-- Filtres -->
            <form method="GET" action="{{ route('admin.attendance-report.index') }}" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="period" value="{{ $period }}">
                <input type="hidden" name="date" value="{{ $date }}">

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employe</label>
                    <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">Tous les employes</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                                {{ $user->first_name }} {{ $user->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                    <select name="campus_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">Tous les campus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ $campusId == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="date" value="{{ $date }}"
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-filter mr-1"></i> Filtrer
                </button>
                <a href="{{ route('admin.attendance-report.index', ['period' => $period]) }}" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-redo mr-1"></i> Reset
                </a>
            </form>
        </div>
    </div>

    <!-- Stats globales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 rounded-full bg-blue-100">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500">Employes</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $report->count() }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 rounded-full bg-green-100">
                    <i class="fas fa-clock text-green-600"></i>
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500">Total heures</div>
                    <div class="text-2xl font-bold text-gray-800">{{ intdiv($globalMinutes, 60) }}h {{ str_pad($globalMinutes % 60, 2, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 rounded-full bg-indigo-100">
                    <i class="fas fa-calendar-check text-indigo-600"></i>
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500">Total jours pointes</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $globalDays }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div class="ml-4">
                    <div class="text-sm text-gray-500">Retards</div>
                    <div class="text-2xl font-bold text-red-600">{{ $globalLate }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau par employe -->
    @forelse($report as $entry)
    <div class="bg-white rounded-lg shadow overflow-hidden" x-data="{ open: false }">
        <!-- En-tete employe -->
        <div @click="open = !open" class="cursor-pointer hover:bg-gray-50 transition p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 font-bold">{{ substr($entry->user->first_name, 0, 1) }}</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $entry->user->full_name }}</h3>
                        <p class="text-sm text-gray-500">{{ $entry->user->email }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="text-center hidden md:block">
                        <div class="text-lg font-bold text-blue-600">{{ $entry->total_hours }}</div>
                        <div class="text-xs text-gray-500">Total</div>
                    </div>
                    <div class="text-center hidden md:block">
                        <div class="text-lg font-bold text-green-600">{{ $entry->days_worked }}</div>
                        <div class="text-xs text-gray-500">{{ $entry->days_worked <= 1 ? 'Jour' : 'Jours' }}</div>
                    </div>
                    <div class="text-center hidden md:block">
                        <div class="text-lg font-bold text-indigo-600">{{ $entry->avg_hours }}</div>
                        <div class="text-xs text-gray-500">Moy/jour</div>
                    </div>
                    @if($entry->total_late > 0)
                    <div class="text-center hidden md:block">
                        <div class="text-lg font-bold text-red-600">{{ $entry->total_late }}</div>
                        <div class="text-xs text-gray-500">Retard{{ $entry->total_late > 1 ? 's' : '' }}</div>
                    </div>
                    @endif
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                </div>
            </div>
        </div>

        <!-- Detail par jour -->
        <div x-show="open" x-collapse x-cloak>
            <div class="border-t border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entree</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sortie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duree session</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total jour</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($entry->days as $day)
                            @foreach($day['sessions'] as $sIndex => $session)
                            <tr class="hover:bg-gray-50">
                                <!-- Date (only on first session of the day) -->
                                @if($sIndex === 0)
                                <td class="px-6 py-4 whitespace-nowrap" rowspan="{{ count($day['sessions']) }}">
                                    <div class="text-sm font-medium text-gray-900">{{ $day['date']->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $day['date']->locale('fr')->isoFormat('dddd') }}</div>
                                </td>
                                @endif

                                <!-- Campus -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($session->campus)
                                    <div class="text-sm text-gray-900">{{ $session->campus->name }}</div>
                                    @endif
                                    @if($session->ue)
                                    <div class="text-xs text-blue-600">
                                        <i class="fas fa-book mr-1"></i>{{ $session->ue->code_ue }}
                                    </div>
                                    @endif
                                </td>

                                <!-- Entree -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($session->check_in)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium {{ $session->check_in->is_late ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800' }}">
                                        <i class="fas fa-sign-in-alt mr-1.5 text-xs"></i>
                                        {{ $session->check_in->timestamp->format('H:i') }}
                                    </span>
                                    @else
                                    <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>

                                <!-- Sortie -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($session->check_out)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-sign-out-alt mr-1.5 text-xs"></i>
                                        {{ $session->check_out->timestamp->format('H:i') }}
                                    </span>
                                    @elseif($session->check_in && $session->check_in->timestamp->isToday())
                                    <span class="text-yellow-600 text-sm font-medium">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> En cours
                                    </span>
                                    @else
                                    <span class="text-orange-500 text-sm">Pas de sortie</span>
                                    @endif
                                </td>

                                <!-- Duree session -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($session->minutes > 0)
                                    <span class="text-sm font-medium text-gray-900">{{ $session->duration }}</span>
                                    @else
                                    <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>

                                <!-- Total jour (only on first session) -->
                                @if($sIndex === 0)
                                <td class="px-6 py-4 whitespace-nowrap" rowspan="{{ count($day['sessions']) }}">
                                    @if($day['worked_minutes'] > 0)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-50 text-blue-700">
                                        <i class="fas fa-clock mr-1.5 text-xs"></i>
                                        {{ $day['worked_hours'] }}
                                    </span>
                                    @else
                                    <span class="text-gray-400 text-sm">-</span>
                                    @endif
                                </td>
                                @endif

                                <!-- Statut (only on first session) -->
                                @if($sIndex === 0)
                                <td class="px-6 py-4 whitespace-nowrap" rowspan="{{ count($day['sessions']) }}">
                                    @if($day['is_late'])
                                    <span class="px-2 py-1 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-circle mr-1"></i> {{ $day['late_minutes'] }} min
                                    </span>
                                    @else
                                    <span class="px-2 py-1 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i> A l'heure
                                    </span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach

                            <!-- Subtotal row for days with multiple sessions -->
                            @if(count($day['sessions']) > 1)
                            <tr class="bg-gray-50">
                                <td colspan="4" class="px-6 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                                    Sous-total {{ $day['date']->format('d/m') }}
                                </td>
                                <td class="px-6 py-2">
                                    <span class="text-sm font-bold text-gray-700">{{ $day['worked_hours'] }}</span>
                                </td>
                                <td colspan="2"></td>
                            </tr>
                            @endif
                        @endforeach

                        <!-- Total row -->
                        <tr class="bg-blue-50 font-semibold">
                            <td colspan="4" class="px-6 py-3 text-right text-sm text-blue-800 uppercase">
                                Total {{ $period === 'day' ? 'du jour' : ($period === 'week' ? 'de la semaine' : 'du mois') }}
                            </td>
                            <td class="px-6 py-3">
                                <span class="text-blue-800 font-bold">{{ $entry->total_hours }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <span class="text-sm text-blue-600">{{ $entry->days_worked }} jour{{ $entry->days_worked > 1 ? 's' : '' }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <span class="text-sm text-blue-600">Moy: {{ $entry->avg_hours }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-gray-400">
            <i class="fas fa-clipboard-list text-6xl mb-4"></i>
            <p class="text-lg font-medium">Aucun pointage pour cette periode</p>
            <p class="text-gray-500 mt-2">Selectionnez une autre date ou ajustez les filtres</p>
        </div>
    </div>
    @endforelse
</div>
@endsection
