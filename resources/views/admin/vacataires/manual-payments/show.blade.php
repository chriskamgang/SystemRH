@extends('layouts.admin')

@section('title', 'D\u00e9tails du Paiement')
@section('page-title', 'D\u00e9tails du Paiement Manuel')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- En-tête -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $payment->user->full_name }}</h2>
                <p class="text-gray-600">{{ $payment->user->employee_id }}</p>
                <p class="text-sm text-gray-500 mt-2">
                    <i class="fas fa-calendar mr-2"></i>
                    P\u00e9riode: {{ \Carbon\Carbon::create()->month($payment->month)->translatedFormat('F') }} {{ $payment->year }}
                </p>
                <p class="text-sm text-gray-500">
                    <i class="fas fa-clock mr-2"></i>
                    Cr\u00e9\u00e9 le: {{ $payment->created_at->format('d/m/Y \u00e0 H:i') }}
                </p>
            </div>

            <div class="text-right">
                @if($payment->status == 'pending')
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-2"></i>En attente
                    </span>
                @elseif($payment->status == 'validated')
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                        <i class="fas fa-check mr-2"></i>Valid\u00e9
                    </span>
                @else
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-double mr-2"></i>Pay\u00e9
                    </span>
                @endif

                <div class="mt-4 space-x-2">
                    <a href="{{ route('admin.vacataires.manual-payments.edit', $payment->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-edit mr-2"></i>Modifier
                    </a>
                    <form action="{{ route('admin.vacataires.manual-payments.destroy', $payment->id) }}" method="POST" class="inline" onsubmit="return confirm('Confirmer la suppression de ce paiement ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Résumé -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-blue-50 rounded-lg p-6">
            <p class="text-sm font-medium text-gray-600">Taux Horaire</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($payment->hourly_rate, 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-500">FCFA/heure</p>
        </div>

        <div class="bg-green-50 rounded-lg p-6">
            <p class="text-sm font-medium text-gray-600">Heures Totales</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($payment->hours_worked, 2, ',', ' ') }}</p>
            <p class="text-xs text-gray-500">heures</p>
        </div>

        <div class="bg-purple-50 rounded-lg p-6">
            <p class="text-sm font-medium text-gray-600">Montant Brut</p>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($payment->gross_amount, 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-500">FCFA</p>
        </div>

        <div class="bg-orange-50 rounded-lg p-6">
            <p class="text-sm font-medium text-gray-600">Montant Net</p>
            <p class="text-2xl font-bold text-orange-600">{{ number_format($payment->net_amount, 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-500">FCFA</p>
        </div>
    </div>

    <!-- D\u00e9tails par UE -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h3 class="text-lg font-semibold">D\u00e9tail par mati\u00e8re (UE)</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code UE</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mati\u00e8re</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures Saisies</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Taux Horaire</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($payment->details as $detail)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $detail->code_ue }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $detail->nom_matiere }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($detail->heures_saisies, 2, ',', ' ') }}h
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($detail->taux_horaire, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                {{ number_format($detail->montant, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100">
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-right font-bold text-gray-900">TOTAL:</td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">
                            {{ number_format($payment->hours_worked, 2, ',', ' ') }}h
                        </td>
                        <td class="px-6 py-4"></td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold text-lg text-blue-600">
                            {{ number_format($payment->net_amount, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Notes -->
    @if($payment->notes)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Notes</h3>
            <p class="text-gray-700 whitespace-pre-line">{{ $payment->notes }}</p>
        </div>
    @endif>

    <!-- Historique -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Historique</h3>
        <div class="space-y-2 text-sm">
            <p>
                <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                <strong>Cr\u00e9\u00e9 le:</strong> {{ $payment->created_at->format('d/m/Y \u00e0 H:i') }}
            </p>

            @if($payment->validated_at)
                <p>
                    <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                    <strong>Valid\u00e9 le:</strong> {{ $payment->validated_at->format('d/m/Y \u00e0 H:i') }}
                    @if($payment->validatedBy)
                        par {{ $payment->validatedBy->full_name }}
                    @endif
                </p>
            @endif

            @if($payment->paid_at)
                <p>
                    <i class="fas fa-money-check-alt text-green-500 mr-2"></i>
                    <strong>Pay\u00e9 le:</strong> {{ $payment->paid_at->format('d/m/Y \u00e0 H:i') }}
                </p>
            @endif

            <p>
                <i class="fas fa-edit text-gray-500 mr-2"></i>
                <strong>Derni\u00e8re modification:</strong> {{ $payment->updated_at->format('d/m/Y \u00e0 H:i') }}
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between">
        <a href="{{ route('admin.vacataires.manual-payments.index') }}" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
            <i class="fas fa-arrow-left mr-2"></i>Retour \u00e0 la liste
        </a>
    </div>
</div>
@endsection
