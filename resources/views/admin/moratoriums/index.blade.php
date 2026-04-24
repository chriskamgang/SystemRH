@extends('layouts.admin')

@section('title', 'Gestion des Moratoires')
@section('page-title', 'Demandes de Moratoire')

@section('content')
<div class="space-y-6">
    <!-- Header/Introduction -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Demandes de Moratoire</h2>
            <p class="text-gray-600 mt-1">Gérez les demandes de délai de paiement des étudiants</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.moratoriums.index') }}" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Filtrer par statut</label>
                <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approuvées</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejetées</option>
                </select>
            </div>
            @if(request('status'))
                <a href="{{ route('admin.moratoriums.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    <!-- Liste des Moratoires -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Étudiant</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Matricule</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Motivation</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Décision</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $request)
                        <tr class="hover:bg-gray-50 transition" x-data="{ openApprove: false, openReject: false }">
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $request->created_at->format('d/m/Y') }}
                                <div class="text-xs text-gray-400">{{ $request->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $request->student->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $request->student->specialite }} - {{ $request->student->niveau }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-gray-100 rounded font-mono text-xs text-gray-700">
                                    {{ $request->student->employee_id ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 max-w-xs">
                                <div class="text-sm text-gray-600 line-clamp-2 hover:line-clamp-none transition-all cursor-help" title="{{ $request->reason }}">
                                    {{ $request->reason }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($request->status == 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-yellow-400"></span>
                                        En attente
                                    </span>
                                @elseif($request->status == 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-green-400"></span>
                                        Approuvée
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-red-400"></span>
                                        Rejetée
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($request->observation)
                                    <div class="text-sm italic text-blue-600 font-medium">{{ $request->observation }}</div>
                                    @if($request->validator)
                                        <div class="text-xs text-gray-400 mt-1">Par: {{ $request->validator->full_name }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-xs italic">Aucune observation</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($request->status == 'pending')
                                    <div class="flex justify-end space-x-2">
                                        <button @click="openApprove = true" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded shadow-sm transition">
                                            <i class="fas fa-check mr-1.5"></i> Approuver
                                        </button>
                                        <button @click="openReject = true" class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded shadow-sm transition">
                                            <i class="fas fa-times mr-1.5"></i> Rejeter
                                        </button>
                                    </div>

                                    <!-- Modal Approuver (Alpine.js) -->
                                    <template x-teleport="body">
                                        <div x-show="openApprove" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
                                            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                                <div @click="openApprove = false" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
                                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                                                <div x-show="openApprove" x-transition class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                    <form action="{{ route('admin.moratoriums.approve', $request->id) }}" method="POST">
                                                        @csrf
                                                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                                                            <div class="sm:flex sm:items-start">
                                                                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-green-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                                                    <i class="fas fa-check text-green-600"></i>
                                                                </div>
                                                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Approuver le Moratoire</h3>
                                                                    <div class="mt-2">
                                                                        <p class="text-sm text-gray-500">Approuver la demande de <strong>{{ $request->student->full_name }}</strong> ?</p>
                                                                        <div class="mt-4">
                                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Observation / Date d'échéance :</label>
                                                                            <textarea name="observation" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: Soldez avant le 25/11/2024"></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                                                            <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 sm:w-auto sm:text-sm">
                                                                Confirmer l'Approbation
                                                            </button>
                                                            <button @click="openApprove = false" type="button" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                                                                Annuler
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Modal Rejeter (Alpine.js) -->
                                    <template x-teleport="body">
                                        <div x-show="openReject" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
                                            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                                <div @click="openReject = false" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
                                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                                                <div x-show="openReject" x-transition class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                    <form action="{{ route('admin.moratoriums.reject', $request->id) }}" method="POST">
                                                        @csrf
                                                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                                                            <div class="sm:flex sm:items-start">
                                                                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                                                    <i class="fas fa-times text-red-600"></i>
                                                                </div>
                                                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Rejeter le Moratoire</h3>
                                                                    <div class="mt-2">
                                                                        <p class="text-sm text-gray-500">Rejeter la demande de <strong>{{ $request->student->full_name }}</strong> ?</p>
                                                                        <div class="mt-4">
                                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Motif du rejet (Obligatoire) :</label>
                                                                            <textarea name="observation" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500" placeholder="Ex: Vous n'avez pas atteint le minimum de versement requis."></textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                                                            <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 sm:w-auto sm:text-sm">
                                                                Confirmer le Rejet
                                                            </button>
                                                            <button @click="openReject = false" type="button" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                                                                Annuler
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs font-medium">
                                        <i class="fas fa-history mr-1"></i> Traité le {{ $request->validated_at->format('d/m/Y') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-file-invoice-dollar text-4xl mb-3 text-gray-200"></i>
                                    <p>Aucune demande de moratoire trouvée.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
