@extends('layouts.admin')

@section('title', 'Paiement Manuel Vacataires')
@section('page-title', 'Paiement Manuel Vacataires')

@section('content')
<div class="space-y-6">
    <!-- Statistiques du mois -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-coins text-3xl text-blue-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Pay\u00e9</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_paye'], 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500">FCFA</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-3xl text-green-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Vacataires</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['nb_vacataires'] }}</p>
                    <p class="text-xs text-gray-500">Pay\u00e9s ce mois</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calculator text-3xl text-purple-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Moyenne</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['moyenne'], 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500">FCFA/vacataire</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock text-3xl text-orange-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Heures</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_heures'], 2, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500">Heures totales</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.vacataires.manual-payments.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
                <select name="month" class="w-full border-gray-300 rounded-lg">
                    <option value="">Tous</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ann\u00e9e</label>
                <select name="year" class="w-full border-gray-300 rounded-lg">
                    <option value="">Toutes</option>
                    @for($y = date('Y'); $y >= date('Y') - 2; $y--)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Vacataire</label>
                <select name="vacataire_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">Tous</option>
                    @foreach($vacataires as $vac)
                        <option value="{{ $vac->id }}" {{ request('vacataire_id') == $vac->id ? 'selected' : '' }}>
                            {{ $vac->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full border-gray-300 rounded-lg">
                    <option value="">Tous</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="validated" {{ request('status') == 'validated' ? 'selected' : '' }}>Valid\u00e9</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Pay\u00e9</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.vacataires.manual-payments.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    <i class="fas fa-redo mr-2"></i>R\u00e9initialiser
                </a>
            </div>
        </form>

        <div class="mt-4 flex justify-end">
            <a href="{{ route('admin.vacataires.manual-payments.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Nouveau Paiement
            </a>
        </div>
    </div>

    <!-- Table des paiements -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vacataire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">P\u00e9riode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UE</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $payment->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $payment->user->full_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ sprintf('%02d/%d', $payment->month, $payment->year) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $payment->details->count() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($payment->hours_worked, 2) }}h
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            {{ number_format($payment->net_amount, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payment->status == 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">En attente</span>
                            @elseif($payment->status == 'validated')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Valid\u00e9</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pay\u00e9</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('admin.vacataires.manual-payments.show', $payment->id) }}" class="text-blue-600 hover:text-blue-900" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.vacataires.manual-payments.edit', $payment->id) }}" class="text-green-600 hover:text-green-900" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.vacataires.manual-payments.destroy', $payment->id) }}" method="POST" class="inline" onsubmit="return confirm('Confirmer la suppression ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            Aucun paiement trouv\u00e9
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
