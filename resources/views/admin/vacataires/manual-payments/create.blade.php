@extends('layouts.admin')

@section('title', 'Nouveau Paiement Manuel')
@section('page-title', 'Nouveau Paiement Manuel Vacataire')

@section('content')
<div x-data="paymentForm()" x-init="init()" class="max-w-6xl mx-auto">
    <form action="{{ route('admin.vacataires.manual-payments.store') }}" method="POST" @submit="validateForm">
        @csrf

        <!-- Étape 1: Sélection du vacataire et de la période -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">1. S\u00e9lection du vacataire et de la p\u00e9riode</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Vacataire <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="vacataire_id"
                        x-model="vacataireId"
                        @change="loadUEs"
                        class="w-full border-gray-300 rounded-lg"
                        required
                    >
                        <option value="">-- Choisir un vacataire --</option>
                        @foreach($vacataires as $vac)
                            <option value="{{ $vac->id }}">{{ $vac->full_name }} ({{ number_format($vac->hourly_rate, 0) }} FCFA/h)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Mois <span class="text-red-500">*</span>
                    </label>
                    <select name="month" x-model="month" class="w-full border-gray-300 rounded-lg" required>
                        <option value="">-- Choisir un mois --</option>
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ann\u00e9e <span class="text-red-500">*</span>
                    </label>
                    <select name="year" x-model="year" class="w-full border-gray-300 rounded-lg" required>
                        <option value="">-- Choisir une ann\u00e9e --</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div x-show="vacataireInfo" class="mt-4 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm"><strong>Vacataire:</strong> <span x-text="vacataireInfo?.nom"></span></p>
                <p class="text-sm"><strong>Taux horaire:</strong> <span x-text="formatNumber(vacataireInfo?.taux_horaire)"></span> FCFA/h</p>
            </div>
        </div>

        <!-- Étape 2: Saisie des heures par UE -->
        <div x-show="ues.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">2. Saisie des heures par mati\u00e8re (UE)</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code UE</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mati\u00e8re</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vol. Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">D\u00e9j\u00e0 Valid\u00e9</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Restant</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heures ce mois <span class="text-red-500">*</span></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(ue, index) in ues" :key="ue.id">
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="ue.code_ue"></td>
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="ue.nom_matiere"></td>
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="ue.volume_horaire_total + 'h'"></td>
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="ue.heures_effectuees_validees + 'h'"></td>
                                <td class="px-4 py-3 text-sm font-medium" :class="ue.heures_restantes > 0 ? 'text-green-600' : 'text-red-600'" x-text="ue.heures_restantes + 'h'"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            type="button"
                                            @click="decrementHeures(ue)"
                                            class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm font-bold"
                                            title="Diminuer"
                                        >
                                            <i class="fas fa-minus"></i>
                                        </button>

                                        <input
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            :name="'ue_details[' + index + '][heures_saisies]'"
                                            x-model="ue.heures_saisies"
                                            @input="calculateMontant(ue); calculateTotal()"
                                            class="w-20 border-2 rounded-lg text-center text-sm font-bold"
                                            :class="ue.heures_saisies > 0 ? 'border-green-500 bg-green-50' : 'border-gray-300'"
                                            placeholder="0"
                                        />

                                        <button
                                            type="button"
                                            @click="incrementHeures(ue)"
                                            class="px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-sm font-bold"
                                            title="Augmenter"
                                        >
                                            <i class="fas fa-plus"></i>
                                        </button>

                                        <button
                                            type="button"
                                            @click="setHeuresMax(ue)"
                                            class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs"
                                            title="Remplir avec le maximum disponible"
                                        >
                                            MAX
                                        </button>
                                    </div>
                                    <input type="hidden" :name="'ue_details[' + index + '][unite_enseignement_id]'" :value="ue.id" />
                                    <div x-show="ue.heures_saisies > ue.heures_restantes" class="text-xs text-red-500 mt-1 font-semibold">
                                        <i class="fas fa-exclamation-triangle"></i> D\u00e9passe les heures restantes !
                                    </div>
                                    <div x-show="ue.heures_saisies > 0 && ue.heures_saisies <= ue.heures_restantes" class="text-xs text-green-600 mt-1">
                                        <i class="fas fa-check"></i> <span x-text="formatNumber(ue.heures_saisies)"></span>h saisies
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900" x-text="formatNumber(ue.montant) + ' FCFA'"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-bold">TOTAL:</td>
                            <td class="px-4 py-3 font-bold" x-text="formatNumber(totalHeures) + 'h'"></td>
                            <td class="px-4 py-3 font-bold text-lg text-blue-600" x-text="formatNumber(totalMontant) + ' FCFA'"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optionnel)</label>
                <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-lg" placeholder="Remarques ou commentaires..."></textarea>
            </div>
        </div>

        <!-- Résumé et actions -->
        <div x-show="ues.length > 0" class="bg-gradient-to-r from-blue-50 to-green-50 rounded-lg shadow-lg p-6 border-2 border-blue-200">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <!-- Résumé -->
                <div class="flex-1">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg p-4 shadow">
                            <p class="text-sm text-gray-600 mb-1">
                                <i class="fas fa-clock text-blue-500 mr-2"></i>Total Heures
                            </p>
                            <p class="text-3xl font-bold text-blue-600" x-text="formatNumber(totalHeures) + 'h'"></p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow">
                            <p class="text-sm text-gray-600 mb-1">
                                <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Montant Total
                            </p>
                            <p class="text-3xl font-bold text-green-600" x-text="formatNumber(totalMontant) + ' FCFA'"></p>
                        </div>
                    </div>

                    <!-- Alerte si aucune heure -->
                    <div x-show="totalHeures == 0" class="mt-4 p-3 bg-yellow-100 border-l-4 border-yellow-500 rounded">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Attention :</strong> Aucune heure saisie. Utilisez les boutons <strong>+</strong> ou <strong>MAX</strong> pour ajouter des heures.
                        </p>
                    </div>

                    <!-- Confirmation visuelle -->
                    <div x-show="totalHeures > 0" class="mt-4 p-3 bg-green-100 border-l-4 border-green-500 rounded">
                        <p class="text-sm text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Prêt à enregistrer :</strong> <span x-text="formatNumber(totalHeures)"></span>h pour un montant de <span x-text="formatNumber(totalMontant)"></span> FCFA
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col space-y-3">
                    <button
                        type="submit"
                        class="px-8 py-4 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-lg transform hover:scale-105 transition disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="totalHeures == 0"
                        :class="totalHeures > 0 ? 'animate-pulse' : ''"
                    >
                        <i class="fas fa-save mr-2"></i>
                        <span class="font-bold">Enregistrer le paiement</span>
                    </button>

                    <a href="{{ route('admin.vacataires.manual-payments.index') }}" class="px-8 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 text-center">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </a>
                </div>
            </div>
        </div>

        <div x-show="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
                <p class="mt-4 text-lg">Chargement...</p>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function paymentForm() {
    return {
        vacataireId: '',
        month: '',
        year: '',
        vacataireInfo: null,
        ues: [],
        totalHeures: 0,
        totalMontant: 0,
        loading: false,

        init() {
            // Initialisation
        },

        async loadUEs() {
            if (!this.vacataireId) {
                this.ues = [];
                this.vacataireInfo = null;
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('{{ route('admin.vacataires.manual-payments.select-ue') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        vacataire_id: this.vacataireId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.vacataireInfo = data.vacataire;
                    this.ues = data.ues.map(ue => ({
                        ...ue,
                        heures_saisies: 0,
                        montant: 0
                    }));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors du chargement des UE');
            } finally {
                this.loading = false;
            }
        },

        calculateMontant(ue) {
            const heures = parseFloat(ue.heures_saisies) || 0;
            ue.montant = heures * (this.vacataireInfo?.taux_horaire || 0);
        },

        calculateTotal() {
            this.totalHeures = this.ues.reduce((sum, ue) => sum + (parseFloat(ue.heures_saisies) || 0), 0);
            this.totalMontant = this.ues.reduce((sum, ue) => sum + (parseFloat(ue.montant) || 0), 0);
        },

        // Incrémenter les heures (+0.5h)
        incrementHeures(ue) {
            const current = parseFloat(ue.heures_saisies) || 0;
            ue.heures_saisies = current + 0.5;
            this.calculateMontant(ue);
            this.calculateTotal();
        },

        // Décrémenter les heures (-0.5h)
        decrementHeures(ue) {
            const current = parseFloat(ue.heures_saisies) || 0;
            if (current > 0) {
                ue.heures_saisies = Math.max(0, current - 0.5);
                this.calculateMontant(ue);
                this.calculateTotal();
            }
        },

        // Définir au maximum disponible
        setHeuresMax(ue) {
            ue.heures_saisies = ue.heures_restantes;
            this.calculateMontant(ue);
            this.calculateTotal();
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

            return confirm('Confirmer la création de ce paiement de ' + this.formatNumber(this.totalMontant) + ' FCFA ?');
        }
    }
}
</script>
@endpush
@endsection
