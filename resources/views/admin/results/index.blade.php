@extends('layouts.admin')

@section('title', 'Gestion des Résultats')
@section('page-title', 'Gestion des Résultats')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center text-gray-800">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Résultats Académiques</h2>
            <p class="text-gray-600 mt-1">Publiez les notes de CC et d'Examens pour les étudiants</p>
        </div>
        <a href="{{ route('admin.results.create') }}" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition shadow-md">
            <i class="fas fa-plus mr-2"></i> Ajouter un Résultat
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 text-gray-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Étudiant</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Titre</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($results as $res)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $res->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">{{ $res->user->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $res->user->employee_id }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 font-medium">
                                {{ $res->title }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ $res->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ asset('storage/' . $res->file_path) }}" target="_blank" class="p-2 text-blue-600 hover:bg-blue-50 rounded transition" title="Voir le document">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <form action="{{ route('admin.results.destroy', $res->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce résultat ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded transition" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                                Aucun résultat publié
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
