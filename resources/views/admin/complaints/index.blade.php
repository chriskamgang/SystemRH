@extends('layouts.admin')

@section('title', 'Gestion des Plaintes')
@section('page-title', 'Gestion des Plaintes')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center text-gray-800">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Plaintes des Étudiants</h2>
            <p class="text-gray-600 mt-1">Consultez et répondez aux plaintes soumises via l'application</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 text-gray-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Étudiant</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sujet</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Statut</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($complaints as $complaint)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $complaint->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">{{ $complaint->user->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $complaint->user->employee_id }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $complaint->subject }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($complaint->status == 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">En attente</span>
                                @elseif($complaint->status == 'in_review')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">En cours</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Résolu</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.complaints.show', $complaint->id) }}" class="p-2 text-blue-600 hover:bg-blue-50 rounded transition">
                                        <i class="fas fa-reply"></i> Répondre
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                                Aucune plainte enregistrée
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($complaints->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $complaints->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
