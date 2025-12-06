@extends('layouts.admin')

@section('title', 'Modifier le Paiement')
@section('page-title', 'Modifier le Paiement Manuel')

@section('content')
<div x-data="editPaymentForm()" x-init="init()" class="max-w-6xl mx-auto">
    <form action="{{ route('admin.vacataires.manual-payments.update', $payment->id) }}" method="POST" @submit="validateForm">
        @csrf
        @method('PUT')

        <!-- Alerte -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <i class="fas fa-exclamation-triangle text-yellow-400 mr-3"></i>
                <div>
                    <p class="text-sm text-yellow-700">
                        <strong>Attention:</strong> La modification de ce paiement recalculera les heures valid\u00e9es des unit\u00e9s d'enseignement concern\u00e9es.
                    </p>
                </div>
            </div>
        </div>

        <!-- Informations du vacataire -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Informations</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vacataire</label>
                    <p class="p-2 bg-gray-100 rounded-lg">{{ $payment->user->full_name }}</p>
                    <p class="text-xs text-gray-500 mt-1">Taux: {{ number_format($payment->user->hourly_rate, 0) }} FCFA/h</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Mois <span class="text-red-500">*</span>
                    </label>
                    <select name="month" x-model="month" class="w-full border-gray-300 rounded-lg" required>
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}" {{ $payment->month == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ann\u00e9e <span class="text-red-500">*</span>
                    </label>
                    <select name="year" x-model="year" class="w-full border-gray-300 rounded-lg" required>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $payment->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Saisie des heures par UE -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Heures par mati\u00e8re (UE)</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code UE</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mati\u00e8re</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vol. Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures ce mois <span class="text-red-500">*</span></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(ue, index) in ues" :key="ue.id">
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="ue.code_ue"></td>
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="ue.nom_matiere"></td>
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="ue.volume_horaire_total + 'h'"></td>
                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :name="'ue_details[' + index + '][heures_saisies]'"
                                        x-model="ue.heures_saisies"
                                        @input="calculateMontant(ue); calculateTotal()"
                                        class="w-24 border-gray-300 rounded-lg text-sm"
                                        placeholder="0.00"
                                    />
                                    <input type="hidden" :name="'ue_details[' + index + '][unite_enseignement_id]'" :value="ue.id" />
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900" x-text="formatNumber(ue.montant) + ' FCFA'"></td>
                                <td class="px-4 py-3">
                                    <button type="button" @click="removeUE(index)" class="text-red-600 hover:text-red-800" title="Retirer">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-bold">TOTAL:</td>
                            <td class="px-4 py-3 font-bold" x-text="formatNumber(totalHeures) + 'h'"></td>
                            <td class="px-4 py-3 font-bold text-lg text-blue-600" x-text="formatNumber(totalMontant) + ' FCFA'"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optionnel)</label>
                <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-lg" placeholder="Remarques ou commentaires...">{{ $payment->notes }}</textarea>
            </div>
        </div>

        <!-- R\u00e9sum\u00e9 et actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-lg"><strong>Total heures:</strong> <span x-text="formatNumber(totalHeures)"></span>h</p>
                    <p class="text-2xl font-bold text-blue-600"><strong>Montant total:</strong> <span x-text="formatNumber(totalMontant)"></span> FCFA</p>
                </div>

                <div class="space-x-4">
                    <a href="{{ route('admin.vacataires.manual-payments.show', $payment->id) }}" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700" :disabled="totalHeures == 0">
                        <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function editPaymentForm() {
    return {
        month: {{ $payment->month }},
        year: {{ $payment->year }},
        tauxHoraire: {{ $payment->user->hourly_rate }},
        ues: @json($payment->details->map(function($detail) use ($payment) {
            return [
                'id' => $detail->unite_enseignement_id,
                'code_ue' => $detail->code_ue,
                'nom_matiere' => $detail->nom_matiere,
                'volume_horaire_total' => $detail->uniteEnseignement->volume_horaire_total ?? 0,
                'heures_saisies' => $detail->heures_saisies,
                'montant' => $detail->montant,
            ];
        })),
        totalHeures: 0,
        totalMontant: 0,

        init() {
            this.calculateTotal();
        },

        calculateMontant(ue) {
            const heures = parseFloat(ue.heures_saisies) || 0;
            ue.montant = heures * this.tauxHoraire;
        },

        calculateTotal() {
            this.totalHeures = this.ues.reduce((sum, ue) => sum + (parseFloat(ue.heures_saisies) || 0), 0);
            this.totalMontant = this.ues.reduce((sum, ue) => sum + (parseFloat(ue.montant) || 0), 0);
        },

        removeUE(index) {
            if (confirm('Retirer cette mati\u00e8re du paiement ?')) {
                this.ues.splice(index, 1);
                this.calculateTotal();
            }
        },

        formatNumber(num) {
            return new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(num || 0);
        },

        validateForm(e) {
            if (this.totalHeures == 0) {
                e.preventDefault();
                alert('Veuillez saisir au moins une heure pour une matière');
                return false;
            }

            return confirm('Confirmer la modification de ce paiement ?');
        }
    }
}
</script>
@endpush
@endsection
