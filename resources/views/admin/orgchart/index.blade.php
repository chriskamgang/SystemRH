@extends('layouts.admin')

@section('title', 'Organigramme')
@section('page-title', 'Organigramme')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Organigramme</h2>
            <p class="text-gray-600 mt-1">Structure hiérarchique des départements et des employés</p>
        </div>
        <div class="flex gap-3">
            <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-semibold text-sm">
                <i class="fas fa-sitemap mr-2"></i>{{ $totalDepartments }} département{{ $totalDepartments > 1 ? 's' : '' }}
            </span>
            <span class="px-4 py-2 bg-green-100 text-green-800 rounded-lg font-semibold text-sm">
                <i class="fas fa-users mr-2"></i>{{ $totalEmployees }} employé{{ $totalEmployees > 1 ? 's' : '' }}
            </span>
            <span class="px-4 py-2 bg-purple-100 text-purple-800 rounded-lg font-semibold text-sm">
                <i class="fas fa-building mr-2"></i>{{ $totalCampuses }} campus
            </span>
        </div>
    </div>

    {{-- Employee type summary --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-tie text-indigo-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Permanents</p>
                <p class="text-2xl font-bold text-gray-800">{{ $typeStats['permanent'] ?? 0 }}</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Semi-permanents</p>
                <p class="text-2xl font-bold text-gray-800">{{ $typeStats['semi_permanent'] ?? 0 }}</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chalkboard-teacher text-orange-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Vacataires</p>
                <p class="text-2xl font-bold text-gray-800">{{ $typeStats['vacataire'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    {{-- Org chart: one card per department --}}
    @forelse($departments as $dept)
    <div class="bg-white rounded-xl shadow overflow-hidden">

        {{-- Department header --}}
        <div class="bg-gradient-to-r from-blue-700 to-blue-500 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-sitemap text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">{{ $dept->name }}</h3>
                    <p class="text-blue-100 text-sm">
                        Code: <span class="font-mono font-semibold">{{ $dept->code ?? '—' }}</span>
                        @if($dept->campus)
                            &nbsp;·&nbsp; Campus: {{ $dept->campus->name }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-white text-2xl font-bold">{{ $dept->users->count() }}</span>
                <p class="text-blue-100 text-xs">employé{{ $dept->users->count() > 1 ? 's' : '' }}</p>
            </div>
        </div>

        {{-- Department head --}}
        @if($dept->head)
        <div class="bg-blue-50 border-b border-blue-100 px-6 py-3 flex items-center gap-3">
            <i class="fas fa-crown text-yellow-500"></i>
            <span class="text-sm font-semibold text-blue-800">Responsable :</span>
            <span class="text-sm text-blue-700">{{ $dept->head->first_name }} {{ $dept->head->last_name }}</span>
            @if($dept->head->jobPosition)
                <span class="text-xs text-blue-500">— {{ $dept->head->jobPosition->name }}</span>
            @endif
        </div>
        @endif

        {{-- Description --}}
        @if($dept->description)
        <div class="px-6 py-2 bg-gray-50 border-b border-gray-100">
            <p class="text-sm text-gray-500 italic">{{ $dept->description }}</p>
        </div>
        @endif

        {{-- Employee grid --}}
        @if($dept->users->count() > 0)
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($dept->users as $employee)
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md hover:border-blue-300 transition group">
                    {{-- Avatar --}}
                    <div class="flex items-center gap-3 mb-3">
                        @if($employee->photo)
                            <img src="{{ asset('storage/' . $employee->photo) }}"
                                 alt="{{ $employee->first_name }}"
                                 class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 group-hover:border-blue-400 transition">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gray-200 group-hover:bg-blue-100 transition flex items-center justify-center flex-shrink-0">
                                <span class="text-gray-600 group-hover:text-blue-700 font-bold text-sm transition">
                                    {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 text-sm truncate">
                                {{ $employee->first_name }} {{ $employee->last_name }}
                            </p>
                            <p class="text-xs text-gray-400 font-mono">{{ $employee->employee_id }}</p>
                        </div>
                    </div>

                    {{-- Position & type --}}
                    <div class="space-y-1">
                        @if($employee->jobPosition)
                        <p class="text-xs text-gray-600 flex items-center gap-1">
                            <i class="fas fa-briefcase text-gray-400 w-3"></i>
                            {{ $employee->jobPosition->name }}
                        </p>
                        @endif

                        @php
                            $typeBadge = match($employee->employee_type) {
                                'permanent'     => ['bg-indigo-100', 'text-indigo-700', 'Permanent'],
                                'semi_permanent'=> ['bg-yellow-100', 'text-yellow-700', 'Semi-permanent'],
                                'vacataire'     => ['bg-orange-100', 'text-orange-700', 'Vacataire'],
                                default         => ['bg-gray-100', 'text-gray-600', ucfirst($employee->employee_type ?? '—')],
                            };
                        @endphp
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $typeBadge[0] }} {{ $typeBadge[1] }}">
                            {{ $typeBadge[2] }}
                        </span>

                        @if($employee->role)
                        <p class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                            <i class="fas fa-shield-alt text-gray-400 w-3"></i>
                            {{ $employee->role->name }}
                        </p>
                        @endif
                    </div>

                    {{-- Link to employee page --}}
                    @if(Route::has('admin.employees.show'))
                    <a href="{{ route('admin.employees.show', $employee->id) }}"
                       class="mt-3 block text-center text-xs text-blue-600 hover:text-blue-800 hover:underline">
                        Voir le profil
                    </a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="px-6 py-8 text-center text-gray-400">
            <i class="fas fa-user-slash text-3xl mb-2"></i>
            <p class="text-sm">Aucun employé actif dans ce département.</p>
        </div>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-12 text-center text-gray-400">
        <i class="fas fa-sitemap text-5xl mb-4"></i>
        <p class="text-lg font-semibold">Aucun département actif trouvé.</p>
        <p class="text-sm mt-1">Créez des départements pour voir l'organigramme.</p>
    </div>
    @endforelse

    {{-- Unassigned employees --}}
    @if($unassigned->count() > 0)
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="bg-gradient-to-r from-gray-600 to-gray-500 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-user-question text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Sans département</h3>
                    <p class="text-gray-200 text-sm">Employés actifs non rattachés à un département</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-white text-2xl font-bold">{{ $unassigned->count() }}</span>
                <p class="text-gray-200 text-xs">employé{{ $unassigned->count() > 1 ? 's' : '' }}</p>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($unassigned as $employee)
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                            <span class="text-gray-600 font-bold text-sm">
                                {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 text-sm truncate">
                                {{ $employee->first_name }} {{ $employee->last_name }}
                            </p>
                            <p class="text-xs text-gray-400 font-mono">{{ $employee->employee_id }}</p>
                        </div>
                    </div>
                    @if($employee->jobPosition)
                    <p class="text-xs text-gray-500">{{ $employee->jobPosition->name }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
