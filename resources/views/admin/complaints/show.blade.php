@extends('layouts.admin')

@section('title', 'Détail de la Plainte')
@section('page-title', 'Détail de la Plainte')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-4 text-gray-800">
        <a href="{{ route('admin.complaints.index') }}" class="p-2 bg-white border rounded-lg hover:bg-gray-50 transition shadow-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-2xl font-bold">Plainte #{{ $complaint->id }}</h2>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 p-8 text-gray-800">
        <div class="mb-6 border-b pb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $complaint->subject }}</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Soumis par <strong>{{ $complaint->user->full_name }}</strong> ({{ $complaint->user->employee_id }}) 
                        le {{ $complaint->created_at->format('d/m/Y à H:i') }}
                    </p>
                </div>
                <div>
                    @if($complaint->status == 'pending')
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">En attente</span>
                    @elseif($complaint->status == 'in_review')
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">En cours</span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">Résolu</span>
                    @endif
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 italic text-gray-700">
                "{{ $complaint->content }}"
            </div>
        </div>

        <form action="{{ route('admin.complaints.respond', $complaint->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Votre Réponse :</label>
                <textarea 
                    name="admin_response" 
                    rows="6" 
                    required 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="Ecrivez votre réponse ici..."
                >{{ $complaint->admin_response }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Mettre à jour le statut :</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="in_review" {{ $complaint->status == 'in_review' ? 'selected' : '' }}>En cours d'examen</option>
                        <option value="resolved" {{ $complaint->status == 'resolved' ? 'selected' : '' }}>Résolu</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition shadow-md">
                    <i class="fas fa-paper-plane mr-2"></i> Envoyer la Réponse
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
