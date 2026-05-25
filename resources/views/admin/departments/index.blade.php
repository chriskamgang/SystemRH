@extends('layouts.admin')

@section('title', 'Gestion des Départements')

@section('content')
<div class="container mx-auto px-4 py-6">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Départements</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $departments->count() }} département(s) au total</p>
        </div>
        <a href="{{ route('admin.departments.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center transition">
            <i class="fas fa-plus mr-2"></i>
            Nouveau Département
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Département</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsable</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Employés</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($departments as $dept)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $dept->name }}</div>
                        @if($dept->description)
                            <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ $dept->description }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($dept->code)
                            <span class="px-2 py-1 text-xs font-mono bg-gray-100 rounded text-gray-700">{{ $dept->code }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        {{ $dept->campus?->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        {{ $dept->head?->full_name ?? '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="text-lg font-bold text-blue-600">{{ $dept->users_count }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        @if($dept->is_active)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 font-medium">Actif</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 font-medium">Inactif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.departments.edit', $dept->id) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded text-sm transition">
                                <i class="fas fa-edit mr-1"></i> Modifier
                            </a>
                            <form action="{{ route('admin.departments.destroy', $dept->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Supprimer le département {{ addslashes($dept->name) }} ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 rounded text-sm transition">
                                    <i class="fas fa-trash mr-1"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <i class="fas fa-sitemap text-5xl text-gray-300 mb-4 block"></i>
                        <p class="text-gray-500">Aucun département créé</p>
                        <a href="{{ route('admin.departments.create') }}" class="text-blue-600 hover:underline mt-2 inline-block">
                            Créer le premier département
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
