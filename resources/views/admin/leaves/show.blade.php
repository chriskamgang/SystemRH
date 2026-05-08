@extends('layouts.admin')

@section('title', 'Détail de la demande de congé')
@section('page-title', 'Détail de la demande de congé')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <a href="{{ route('admin.leaves.index') }}" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
    </a>

    <!-- Info employé -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $leave->user->full_name }}</h2>
                <p class="text-gray-500">{{ $leave->user->employee_id }} - {{ $leave->user->employee_type }}</p>
            </div>
            @if($leave->status === 'pending')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">En attente</span>
            @elseif($leave->status === 'approved')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Approuvé</span>
            @elseif($leave->status === 'rejected')
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">Rejeté</span>
            @else
                <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">Annulé</span>
            @endif
        </div>
    </div>

    <!-- Détails de la demande -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails de la demande</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Type de congé</p>
                <p class="font-medium">{{ $leave->getTypeLabel() }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Nombre de jours</p>
                <p class="font-medium">{{ $leave->days_count }} jour(s)</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Date de début</p>
                <p class="font-medium">{{ $leave->start_date->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Date de fin</p>
                <p class="font-medium">{{ $leave->end_date->format('d/m/Y') }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Motif</p>
                <p class="font-medium">{{ $leave->reason }}</p>
            </div>
            @if($leave->attachment)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Justificatif</p>
                <a href="{{ Storage::url($leave->attachment) }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-paperclip mr-1"></i>Voir le document
                </a>
            </div>
            @endif
            <div>
                <p class="text-sm text-gray-500">Demandé le</p>
                <p class="font-medium">{{ $leave->created_at->format('d/m/Y à H:i') }}</p>
            </div>
        </div>

        @if($leave->reviewed_at)
        <hr class="my-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Traité par</p>
                <p class="font-medium">{{ $leave->reviewer?->full_name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Traité le</p>
                <p class="font-medium">{{ $leave->reviewed_at->format('d/m/Y à H:i') }}</p>
            </div>
            @if($leave->review_comment)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Commentaire</p>
                <p class="font-medium">{{ $leave->review_comment }}</p>
            </div>
            @endif
        </div>
        @endif
    </div>

    <!-- Solde de congé -->
    @if(isset($balances[$leave->type]))
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Solde {{ $leave->getTypeLabel() }} ({{ $leave->start_date->year }})</h3>
        @php $bal = $balances[$leave->type]; @endphp
        <div class="grid grid-cols-3 gap-4 text-center">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-2xl font-bold text-blue-700">{{ $bal->total_days }}</p>
                <p class="text-sm text-gray-600">Total</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-4">
                <p class="text-2xl font-bold text-orange-700">{{ $bal->used_days }}</p>
                <p class="text-sm text-gray-600">Utilisés</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-2xl font-bold text-green-700">{{ $bal->remaining_days }}</p>
                <p class="text-sm text-gray-600">Restants</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Actions -->
    @if($leave->isPending())
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
        <div class="flex gap-4">
            <form action="{{ route('admin.leaves.approve', $leave->id) }}" method="POST">
                @csrf
                <input type="text" name="comment" placeholder="Commentaire (optionnel)" class="px-4 py-2 border border-gray-300 rounded-lg mr-2">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold" onclick="return confirm('Approuver cette demande ?')">
                    <i class="fas fa-check mr-2"></i>Approuver
                </button>
            </form>
            <form action="{{ route('admin.leaves.reject', $leave->id) }}" method="POST">
                @csrf
                <input type="text" name="comment" placeholder="Motif du refus *" required class="px-4 py-2 border border-gray-300 rounded-lg mr-2">
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold" onclick="return confirm('Rejeter cette demande ?')">
                    <i class="fas fa-times mr-2"></i>Rejeter
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
