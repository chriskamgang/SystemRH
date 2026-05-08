@extends('layouts.admin')

@section('title', 'Utilisation de l\'Application')
@section('page-title', 'Utilisation de l\'Application Mobile')

@section('content')
<div class="space-y-6">
    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.app-usage') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date debut</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? $date_from }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? $date_to }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departement</label>
                <select name="department_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Tous</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type employe</label>
                <select name="employee_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Tous</option>
                    <option value="permanent" {{ ($filters['employee_type'] ?? '') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                    <option value="semi_permanent" {{ ($filters['employee_type'] ?? '') == 'semi_permanent' ? 'selected' : '' }}>Semi-Permanent</option>
                    <option value="enseignant_vacataire" {{ ($filters['employee_type'] ?? '') == 'enseignant_vacataire' ? 'selected' : '' }}>Vacataire</option>
                    <option value="enseignant_titulaire" {{ ($filters['employee_type'] ?? '') == 'enseignant_titulaire' ? 'selected' : '' }}>Titulaire</option>
                    <option value="administratif" {{ ($filters['employee_type'] ?? '') == 'administratif' ? 'selected' : '' }}>Administratif</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jours min.</label>
                <div class="flex gap-2">
                    <input type="number" name="min_days" value="{{ $filters['min_days'] ?? 0 }}" min="0"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        placeholder="0">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm whitespace-nowrap">
                        <i class="fas fa-filter mr-1"></i> Filtrer
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Utilisateurs actifs</p>
                    <p class="text-3xl font-bold text-emerald-600">{{ $total_users }}</p>
                    <p class="text-xs text-gray-500 mt-1">Sur {{ $total_active_employees }} employes</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-mobile-alt text-2xl text-emerald-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Taux d'adoption</p>
                    <p class="text-3xl font-bold text-blue-600">
                        {{ $total_active_employees > 0 ? round(($total_users / $total_active_employees) * 100, 1) : 0 }}%
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Employes utilisant l'app</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-pie text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Moy. jours check-in</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $avg_checkin_days }}</p>
                    <p class="text-xs text-gray-500 mt-1">Par employe sur la periode</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-check text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total jours complets</p>
                    <p class="text-3xl font-bold text-amber-600">{{ $total_complete_days }}</p>
                    <p class="text-xs text-gray-500 mt-1">Check-in + Check-out</p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-double text-2xl text-amber-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.app-usage.export-excel', request()->query()) }}"
            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium shadow">
            <i class="fas fa-file-excel mr-2"></i> Telecharger Excel
        </a>
        <a href="{{ route('admin.app-usage.export-pdf', request()->query()) }}"
            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium shadow">
            <i class="fas fa-file-pdf mr-2"></i> Telecharger PDF
        </a>
        <span class="inline-flex items-center px-3 py-2 text-sm text-gray-500">
            <i class="fas fa-info-circle mr-1"></i> Periode: {{ \Carbon\Carbon::parse($date_from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($date_to)->format('d/m/Y') }}
        </span>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold">
                <i class="fas fa-trophy text-amber-500 mr-2"></i>
                Classement des utilisateurs ({{ $total_users }} employes)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employe</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departement</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jours Check-in</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jours Check-out</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jours Complets</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Retards</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ponctualite</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Dernier usage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($employees as $index => $emp)
                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-indigo-50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-500">
                            @if($index < 3)
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-white text-xs font-bold
                                    {{ $index === 0 ? 'bg-amber-400' : ($index === 1 ? 'bg-gray-400' : 'bg-amber-700') }}">
                                    {{ $index + 1 }}
                                </span>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-indigo-600 font-bold text-xs">{{ substr($emp['full_name'], 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $emp['full_name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $emp['employee_id'] ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 text-xs rounded-full
                                {{ $emp['employee_type'] === 'permanent' ? 'bg-blue-100 text-blue-800' :
                                   ($emp['employee_type'] === 'enseignant_vacataire' ? 'bg-orange-100 text-orange-800' :
                                   'bg-gray-100 text-gray-800') }}">
                                {{ $emp['employee_type_label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $emp['department'] ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-emerald-600">{{ $emp['checkin_days'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-blue-600">{{ $emp['checkout_days'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-bold text-indigo-600">{{ $emp['complete_days'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($emp['late_count'] > 0)
                                <span class="inline-flex px-2 py-1 text-xs rounded-full bg-red-100 text-red-700 font-medium">
                                    {{ $emp['late_count'] }}
                                </span>
                            @else
                                <span class="text-xs text-green-600">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $emp['punctuality_rate'] >= 90 ? 'bg-emerald-500' : ($emp['punctuality_rate'] >= 70 ? 'bg-amber-500' : 'bg-red-500') }}"
                                        style="width: {{ $emp['punctuality_rate'] }}%"></div>
                                </div>
                                <span class="text-xs font-medium {{ $emp['punctuality_rate'] >= 90 ? 'text-emerald-600' : ($emp['punctuality_rate'] >= 70 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ $emp['punctuality_rate'] }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $emp['last_usage'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-mobile-alt text-4xl mb-3"></i>
                            <p class="text-lg font-medium">Aucun utilisateur trouve</p>
                            <p class="text-sm">Modifiez les filtres ou la periode pour voir les resultats.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
