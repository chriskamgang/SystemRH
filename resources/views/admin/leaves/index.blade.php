@extends('layouts.admin')

@section('title', 'Demandes de Congé')
@section('page-title', 'Demandes de Congé')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Demandes de Congé</h2>
            <p class="text-gray-600 mt-1">Gérez les demandes de congé des employés</p>
        </div>
        <div class="flex gap-2 items-center flex-wrap">
            <a href="{{ route('admin.leaves.assign') }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition text-sm font-semibold">
                <i class="fas fa-plus mr-1"></i>Assigner
            </a>
            <a href="{{ route('admin.leaves.bulk-assign') }}" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition text-sm font-semibold">
                <i class="fas fa-users mr-1"></i>En masse
            </a>
            <a href="{{ route('admin.leaves.balances') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition text-sm font-semibold">
                <i class="fas fa-calculator mr-1"></i>Soldes
            </a>
            <a href="{{ route('admin.leaves.holidays') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition text-sm font-semibold">
                <i class="fas fa-calendar-day mr-1"></i>Jours fériés
            </a>
        </div>
    </div>

    <!-- Compteurs -->
    <div class="grid grid-cols-3 gap-4">
        <a href="{{ route('admin.leaves.index', ['status' => 'awaiting_manager']) }}" class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center hover:bg-purple-100 transition">
            <p class="text-2xl font-bold text-purple-700">{{ $awaitingManagerCount }}</p>
            <p class="text-sm text-purple-600">En attente du supérieur</p>
        </a>
        <a href="{{ route('admin.leaves.index', ['status' => 'awaiting_rh']) }}" class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center hover:bg-blue-100 transition">
            <p class="text-2xl font-bold text-blue-700">{{ $awaitingRhCount }}</p>
            <p class="text-sm text-blue-600">En attente RH</p>
        </a>
        <a href="{{ route('admin.leaves.index', ['status' => 'pending']) }}" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center hover:bg-yellow-100 transition">
            <p class="text-2xl font-bold text-yellow-700">{{ $pendingCount }}</p>
            <p class="text-sm text-yellow-600">Total en attente</p>
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.leaves.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, matricule..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="awaiting_manager" {{ request('status') == 'awaiting_manager' ? 'selected' : '' }}>En attente du supérieur</option>
                    <option value="awaiting_rh" {{ request('status') == 'awaiting_rh' ? 'selected' : '' }}>En attente RH</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Tous en attente</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approuvé</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach(\App\Models\LeaveRequest::TYPES as $key => $label)
                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.leaves.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employé</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Période</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Demandé le</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($leaves as $leave)
                <tr class="{{ $leave->isPending() ? 'bg-yellow-50' : '' }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $leave->user->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $leave->user->employee_id }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm">{{ $leave->getTypeLabel() }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                        {{ $leave->days_count }} j
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $colors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $colors[$leave->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $leave->getStatusLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $leave->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('admin.leaves.show', $leave->id) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">Aucune demande de congé.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $leaves->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
