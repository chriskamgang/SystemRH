@extends('layouts.admin')

@section('title', 'Gestion des Spécialités')
@section('page-title', 'Gestion des Spécialités')

@section('content')
<div class="space-y-6" x-data="{ openCreate: false }">
    <!-- Header -->
    <div class="flex justify-between items-center text-gray-800">
        <div>
            <h2 class="text-2xl font-bold">Liste des Spécialités</h2>
            <p class="text-gray-600 mt-1">Gérez les filières et spécialités</p>
        </div>
        <button @click="openCreate = true" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">
            <i class="fas fa-plus mr-2"></i>
            Nouvelle Spécialité
        </a>
    </div>

    <!-- Specialties List -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Département</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($specialties as $specialty)
                    <tr class="hover:bg-gray-50 transition" x-data="{ openEdit: false }">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $specialty->name }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $specialty->code ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $specialty->department->name ?? 'Aucun' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($specialty->is_active)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100 uppercase">Actif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-100 uppercase">Inactif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button @click="openEdit = true" class="p-1 text-gray-400 hover:text-yellow-600 transition">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.specialties.destroy', $specialty->id) }}" method="POST" onsubmit="return confirm('Supprimer cette spécialité ?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-gray-400 hover:text-red-600 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Edit Modal -->
                            <template x-teleport="body">
                                <div x-show="openEdit" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
                                    <div class="flex items-center justify-center min-h-screen px-4">
                                        <div @click="openEdit = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                            <h3 class="text-lg font-bold mb-4">Modifier la spécialité</h3>
                                            <form action="{{ route('admin.specialties.update', $specialty->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Nom de la spécialité</label>
                                                        <input type="text" name="name" value="{{ $specialty->name }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Code</label>
                                                        <input type="text" name="code" value="{{ $specialty->code }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Département</label>
                                                        <select name="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                            <option value="">Sélectionner un département</option>
                                                            @foreach($departments as $dept)
                                                                <option value="{{ $dept->id }}" {{ $specialty->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <input type="checkbox" name="is_active" {{ $specialty->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        <label class="ml-2 block text-sm text-gray-700 font-medium">Spécialité active</label>
                                                    </div>
                                                </div>
                                                <div class="mt-6 flex justify-end gap-3">
                                                    <button type="button" @click="openEdit = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Annuler</button>
                                                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Enregistrer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            Aucune spécialité définie.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create Modal -->
    <template x-teleport="body">
        <div x-show="openCreate" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div @click="openCreate = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold mb-4">Nouvelle spécialité</h3>
                    <form action="{{ route('admin.specialties.store') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nom de la spécialité</label>
                                <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="ex: Génie Logiciel">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Code</label>
                                <input type="text" name="code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="ex: GL">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Département</label>
                                <select name="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Sélectionner un département</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="openCreate = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Annuler</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Créer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
