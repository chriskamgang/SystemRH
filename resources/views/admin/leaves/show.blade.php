@extends('layouts.admin')

@section('title', 'Détail de la demande de congé')
@section('page-title', 'Détail de la demande de congé')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <a href="{{ route('admin.leaves.index') }}" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
    </a>

    <!-- Info employé -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $leave->user->full_name }}</h2>
                <p class="text-gray-500">{{ $leave->user->employee_id }} - {{ $leave->user->employee_type }}</p>
                @if($leave->user->department)
                    <p class="text-gray-500 text-sm">{{ $leave->user->department->name }}</p>
                @endif
            </div>
            <div class="text-right">
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'cancelled' => 'bg-gray-100 text-gray-800',
                    ];
                @endphp
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $statusColors[$leave->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $leave->getStatusLabel() }}
                </span>
            </div>
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
                <p class="text-sm text-gray-500">Nombre de jours ouvrables</p>
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
    </div>

    <!-- Intérimaire -->
    @if($leave->interim_name)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-user-friends mr-2 text-blue-600"></i>Intérimaire désigné</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Nom</p>
                <p class="font-medium">{{ $leave->interim_name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Fonction</p>
                <p class="font-medium">{{ $leave->interim_function }}</p>
            </div>
            @if($leave->interim_tasks)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Dossiers / activités transmis</p>
                <p class="font-medium">{{ $leave->interim_tasks }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Workflow manager -->
    @if($leave->manager_status)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-sitemap mr-2 text-purple-600"></i>Avis du supérieur hiérarchique</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Statut</p>
                @if($leave->manager_status === 'pending')
                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">En attente</span>
                @elseif($leave->manager_status === 'approved')
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Favorable</span>
                @else
                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Défavorable</span>
                @endif
            </div>
            @if($leave->managerReviewer)
            <div>
                <p class="text-sm text-gray-500">Par</p>
                <p class="font-medium">{{ $leave->managerReviewer->full_name }}</p>
            </div>
            @endif
            @if($leave->manager_reviewed_at)
            <div>
                <p class="text-sm text-gray-500">Date</p>
                <p class="font-medium">{{ $leave->manager_reviewed_at->format('d/m/Y à H:i') }}</p>
            </div>
            @endif
            @if($leave->manager_comment)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Commentaire</p>
                <p class="font-medium">{{ $leave->manager_comment }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Validation RH -->
    @if($leave->reviewed_at)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-user-shield mr-2 text-green-600"></i>Validation RH</h3>
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
    </div>
    @endif

    <!-- Quota annuel (détail calcul) -->
    @if($quotaBreakdown)
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-calculator mr-2 text-indigo-600"></i>Détail du calcul des droits</h3>
        <table class="w-full text-sm">
            <tr class="border-b"><td class="py-2 text-gray-600">Base légale</td><td class="py-2 text-right font-medium">{{ $quotaBreakdown['base'] }} jours</td></tr>
            <tr class="border-b"><td class="py-2 text-gray-600">Ancienneté ({{ $quotaBreakdown['anciennete_annees'] }} ans)</td><td class="py-2 text-right font-medium">+{{ $quotaBreakdown['bonus_anciennete'] }} jours</td></tr>
            @if($quotaBreakdown['bonus_mere'] > 0)
            <tr class="border-b"><td class="py-2 text-gray-600">Bonus mère ({{ $quotaBreakdown['nombre_enfants_charge'] }} enfant(s) < 6 ans)</td><td class="py-2 text-right font-medium">+{{ $quotaBreakdown['bonus_mere'] }} jours</td></tr>
            @endif
            <tr class="bg-blue-50"><td class="py-2 font-semibold">Total congé annuel</td><td class="py-2 text-right font-bold text-blue-700">{{ $quotaBreakdown['total'] }} jours</td></tr>
        </table>
    </div>
    @endif

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

        {{-- Étape 1 : Avis du supérieur --}}
        @if($leave->manager_status === 'pending')
        <div class="mb-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
            <p class="text-sm font-semibold text-purple-800 mb-3"><i class="fas fa-sitemap mr-1"></i> Avis du supérieur hiérarchique (Étape 1)</p>
            <div class="flex gap-3">
                <form action="{{ route('admin.leaves.manager-approve', $leave->id) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="comment" placeholder="Commentaire (optionnel)" class="px-3 py-2 border rounded-lg text-sm">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm" onclick="return confirm('Donner un avis favorable ?')">
                        <i class="fas fa-check mr-1"></i>Favorable
                    </button>
                </form>
                <form action="{{ route('admin.leaves.manager-reject', $leave->id) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="comment" placeholder="Motif *" required class="px-3 py-2 border rounded-lg text-sm">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm" onclick="return confirm('Donner un avis défavorable ?')">
                        <i class="fas fa-times mr-1"></i>Défavorable
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Étape 2 : Validation RH --}}
        @if($leave->manager_status === 'approved' || $leave->manager_status === null)
        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm font-semibold text-blue-800 mb-3"><i class="fas fa-user-shield mr-1"></i> Validation RH (Étape 2)</p>
            <div class="flex gap-3">
                <form action="{{ route('admin.leaves.approve', $leave->id) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="comment" placeholder="Commentaire (optionnel)" class="px-3 py-2 border rounded-lg text-sm">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-semibold" onclick="return confirm('Approuver cette demande ?')">
                        <i class="fas fa-check mr-1"></i>Approuver
                    </button>
                </form>
                <form action="{{ route('admin.leaves.reject', $leave->id) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="comment" placeholder="Motif du refus *" required class="px-3 py-2 border rounded-lg text-sm">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-semibold" onclick="return confirm('Rejeter cette demande ?')">
                        <i class="fas fa-times mr-1"></i>Rejeter
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
    @elseif($leave->isApproved())
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Actions</h3>
        <div class="flex gap-3">
            <a href="{{ route('admin.leaves.letter', $leave->id) }}" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                <i class="fas fa-file-pdf mr-2"></i>Télécharger la lettre de congé
            </a>
            <form action="{{ route('admin.leaves.cancel', $leave->id) }}" method="POST"
                  onsubmit="return confirm('Annuler ce congé ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold transition">
                    <i class="fas fa-ban mr-2"></i>Annuler ce congé
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
