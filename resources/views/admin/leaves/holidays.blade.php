@extends('layouts.admin')

@section('title', 'Jours Fériés')
@section('page-title', 'Gestion des Jours Fériés')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <a href="{{ route('admin.leaves.index') }}" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-2"></i>Retour aux congés
    </a>

    <!-- Ajouter un jour férié -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ajouter un jour férié</h3>
        <form method="POST" action="{{ route('admin.leaves.holidays.store') }}" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                <input type="text" name="name" required placeholder="Ex: Fête du Travail" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_recurring" value="1" id="is_recurring" checked class="h-4 w-4 text-blue-600 rounded">
                <label for="is_recurring" class="text-sm text-gray-700">Récurrent (chaque année)</label>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>Ajouter
            </button>
        </form>
    </div>

    <!-- Charger les fériés camerounais -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex justify-between items-center">
        <div>
            <p class="font-semibold text-yellow-800">Jours fériés du Cameroun</p>
            <p class="text-sm text-yellow-600">Ajouter automatiquement les jours fériés officiels (1er Janvier, 11 Février, 1er Mai, 20 Mai, 15 Août, 25 Décembre)</p>
        </div>
        <form method="POST" action="{{ route('admin.leaves.holidays.seed-cameroon') }}">
            @csrf
            <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm">
                <i class="fas fa-flag mr-1"></i>Ajouter les fériés camerounais
            </button>
        </form>
    </div>

    <!-- Liste -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Récurrent</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($holidays as $holiday)
                <tr>
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $holiday->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $holiday->date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4">
                        @if($holiday->is_recurring)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Oui</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Non</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <form method="POST" action="{{ route('admin.leaves.holidays.destroy', $holiday->id) }}" onsubmit="return confirm('Supprimer ce jour férié ?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-800 text-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Aucun jour férié configuré.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
