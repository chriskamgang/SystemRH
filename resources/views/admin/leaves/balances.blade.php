@extends('layouts.admin')

@section('title', 'Soldes de Congés')
@section('page-title', 'Soldes de Congés')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Soldes de Congés - {{ $year }}</h2>
            <p class="text-gray-600 mt-1">Gérez les quotas de congé par employé</p>
        </div>
        <a href="{{ route('admin.leaves.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition font-semibold">
            <i class="fas fa-arrow-left mr-2"></i> Demandes
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom..."
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Année</label>
                <select name="year" class="px-4 py-2 border border-gray-300 rounded-lg">
                    @for($y = now()->year; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                <i class="fas fa-search mr-2"></i>Filtrer
            </button>
        </form>
    </div>

    <!-- Tableau des soldes -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                    @foreach(\App\Models\LeaveRequest::TYPES as $key => $label)
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($users as $user)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $user->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $user->employee_id }}</div>
                    </td>
                    @foreach(\App\Models\LeaveRequest::TYPES as $key => $label)
                    @php
                        $bal = $user->leave_balances[$key] ?? null;
                        $total = $bal ? $bal->total_days : (\App\Models\LeaveRequest::DEFAULT_BALANCES[$key] ?? 0);
                        $used = $bal ? $bal->used_days : 0;
                        $remaining = $total - $used;
                    @endphp
                    <td class="px-4 py-3 text-center">
                        <span class="text-sm {{ $remaining <= 0 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                            {{ $remaining }}/{{ $total }}
                        </span>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">
            {{ $users->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
