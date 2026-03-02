@extends('layouts.admin')

@section('title', 'Calculateur Générique')
@section('page-title', 'Calculateur Générique')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
        <p class="text-blue-800">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Calculateur Générique :</strong> Entrez le salaire mensuel et le nombre de jours travaillés pour calculer automatiquement le montant à payer.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Formulaire de Calcul -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-calculator text-blue-600 mr-2"></i>
                Calculer la Paie
            </h3>

            <form id="payroll-form">
                @csrf

                <!-- Sélecteur d'Employé -->
                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-blue-600 mr-2"></i>
                        Sélectionner un Employé (optionnel)
                    </label>

                    <!-- Champ de recherche -->
                    <div class="mb-3 relative">
                        <input
                            type="text"
                            id="employee_search"
                            placeholder="Rechercher un employé par nom, prénom ou matricule..."
                            class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                    </div>

                    <select
                        id="employee_id"
                        name="employee_id"
                        size="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">-- Calcul Générique (sans employé) --</option>
                        @foreach($employees as $employee)
                            <option
                                value="{{ $employee->id }}"
                                data-salary="{{ $employee->monthly_salary }}"
                                data-type="{{ $employee->employee_type }}"
                                data-search="{{ strtolower($employee->full_name . ' ' . $employee->employee_id) }}"
                            >
                                {{ $employee->full_name }} - {{ $employee->employee_id }} ({{ number_format($employee->monthly_salary, 0, ',', ' ') }} FCFA)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Si vous sélectionnez un employé, son salaire sera automatiquement rempli et vous pourrez appliquer le calcul.
                    </p>
                </div>

                <!-- Salaire Mensuel -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Salaire Mensuel (FCFA) *
                    </label>
                    <input
                        type="number"
                        id="salaire_mensuel"
                        name="salaire_mensuel"
                        value="300000"
                        min="0"
                        step="1000"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg"
                        placeholder="300000"
                    >
                </div>

                <!-- Jours -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Jours Travaillés *
                        </label>
                        <input
                            type="number"
                            id="jours_travailles"
                            name="jours_travailles"
                            value="25"
                            min="0"
                            max="31"
                            step="0.5"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Total Jours du Mois *
                        </label>
                        <input
                            type="number"
                            id="jours_total"
                            name="jours_total"
                            value="30"
                            min="1"
                            max="31"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg"
                        >
                    </div>
                </div>

                <!-- Retards -->
                <div class="mb-6 p-4 bg-orange-50 rounded-lg border border-orange-200">
                    <h4 class="font-semibold text-gray-700 mb-3">
                        <i class="fas fa-clock text-orange-600 mr-2"></i>
                        Retards du Mois
                    </h4>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Minutes de Retard Total
                        </label>
                        <input
                            type="number"
                            id="minutes_retard"
                            name="minutes_retard"
                            value="0"
                            min="0"
                            step="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                            placeholder="Exemple: 90 (pour 1h30)"
                        >
                        <input type="hidden" id="heures_retard" name="heures_retard" value="0">
                    </div>

                    <div class="mt-3 p-3 bg-white rounded border border-orange-200">
                        <p class="text-xs text-gray-600">
                            <i class="fas fa-info-circle text-orange-500 mr-1"></i>
                            <strong>Pénalité :</strong> 0,50 FCFA par seconde de retard
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Exemple : 30 minutes = 1 800 secondes × 0,50 = 900 FCFA déduits
                        </p>
                    </div>
                </div>

                <!-- Primes et Déductions -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <h4 class="font-semibold text-gray-700 mb-3">
                        <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                        Ajustements (Optionnels)
                    </h4>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Prime / Bonus (FCFA)
                            </label>
                            <input
                                type="number"
                                id="prime"
                                name="prime"
                                value="0"
                                min="0"
                                step="1000"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Déduction Manuelle (FCFA)
                            </label>
                            <input
                                type="number"
                                id="deduction"
                                name="deduction"
                                value="0"
                                min="0"
                                step="1000"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Bouton Calculer -->
                    <button
                        type="submit"
                        class="px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-lg rounded-lg transition shadow-lg"
                    >
                        <i class="fas fa-calculator mr-2"></i>
                        Calculer la Paie
                    </button>

                    <!-- Bouton Appliquer (visible uniquement si employé sélectionné) -->
                    <button
                        type="button"
                        id="apply-button"
                        onclick="applyCalculation()"
                        class="px-6 py-4 bg-green-600 hover:bg-green-700 text-white font-bold text-lg rounded-lg transition shadow-lg hidden"
                    >
                        <i class="fas fa-check-circle mr-2"></i>
                        Appliquer le Calcul
                    </button>
                </div>

                <p class="text-xs text-gray-500 mt-3 text-center" id="apply-info" style="display: none;">
                    <i class="fas fa-info-circle text-green-600 mr-1"></i>
                    Le calcul sera enregistré pour cet employé et apparaîtra dans le rapport de paie
                </p>
            </form>

            <!-- Info -->
            <div class="mt-6 text-sm text-gray-600 bg-gray-50 p-4 rounded-lg">
                <p class="font-semibold mb-2">💡 Comment ça marche ?</p>
                <ul class="space-y-1">
                    <li>• Le salaire est proratisé selon les jours travaillés</li>
                    <li>• Les retards sont pénalisés à <strong>0,50 FCFA/seconde</strong></li>
                    <li>• Les primes s'ajoutent au salaire brut</li>
                    <li>• Les déductions se soustraient du salaire</li>
                </ul>
            </div>
        </div>

        <!-- Résultats -->
        <div class="bg-white rounded-lg shadow-lg p-8" id="resultat-container" style="display: none;">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-file-invoice-dollar text-green-600 mr-2"></i>
                Résultats du Calcul
            </h3>

            <!-- Carte Résumé -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg p-6 mb-6">
                <p class="text-sm opacity-90 mb-1">Salaire Net à Payer</p>
                <p class="text-4xl font-bold" id="salaire_net_principal">0 FCFA</p>
            </div>

            <!-- Détails -->
            <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">💰 Salaire Mensuel</span>
                    <span class="font-semibold" id="res_salaire_mensuel">0 FCFA</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">📅 Jours Travaillés / Total</span>
                    <span class="font-semibold" id="res_jours">0 / 0</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">📊 Taux de Présence</span>
                    <span class="font-semibold text-blue-600" id="res_pourcentage">0%</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-700">💵 Salaire Journalier</span>
                    <span class="font-semibold" id="res_salaire_journalier">0 FCFA</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                    <span class="text-gray-700">💼 Salaire Brut</span>
                    <span class="font-bold text-blue-600" id="res_salaire_brut">0 FCFA</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg" id="prime_row" style="display: none;">
                    <span class="text-gray-700">➕ Prime / Bonus</span>
                    <span class="font-semibold text-green-600" id="res_prime">0 FCFA</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg border-l-4 border-orange-500" id="retard_row" style="display: none;">
                    <span class="text-gray-700">⏰ Pénalité Retards (<span id="res_temps_retard">0h 0min</span>)</span>
                    <span class="font-semibold text-orange-600" id="res_penalite_retard">0 FCFA</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg" id="deduction_row" style="display: none;">
                    <span class="text-gray-700">➖ Déduction Manuelle</span>
                    <span class="font-semibold text-red-600" id="res_deduction">0 FCFA</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg border-l-4 border-red-500">
                    <span class="text-gray-700">❌ Jours d'Absence</span>
                    <span class="font-semibold text-red-600"><span id="res_jours_absence">0</span> jours</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                    <span class="text-gray-700">💸 Montant Perdu (absences)</span>
                    <span class="font-semibold text-red-600" id="res_montant_perdu">0 FCFA</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-3">
                <!-- Bouton Appliquer (visible uniquement si un employé est sélectionné) -->
                <button
                    type="button"
                    id="apply-button-results"
                    onclick="applyCalculation()"
                    class="hidden w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-md"
                >
                    <i class="fas fa-check-circle mr-2"></i>
                    Appliquer le Calcul
                </button>

                <div class="grid grid-cols-2 gap-3">
                    <button
                        onclick="imprimerResultat()"
                        class="px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition"
                    >
                        <i class="fas fa-print mr-2"></i>
                        Imprimer
                    </button>
                    <button
                        onclick="nouveauCalcul()"
                        class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                    >
                        <i class="fas fa-redo mr-2"></i>
                        Nouveau
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payroll-form');
    const resultatContainer = document.getElementById('resultat-container');
    const employeeSelect = document.getElementById('employee_id');
    const employeeSearch = document.getElementById('employee_search');
    const salaryInput = document.getElementById('salaire_mensuel');
    const applyButton = document.getElementById('apply-button');
    const applyInfo = document.getElementById('apply-info');

    // Filtrage de la liste d'employés
    employeeSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const options = employeeSelect.options;

        for (let i = 0; i < options.length; i++) {
            const option = options[i];
            const searchData = option.getAttribute('data-search') || '';

            if (searchTerm === '' || searchData.includes(searchTerm)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        }

        // Toujours afficher la première option (calcul générique)
        if (options.length > 0) {
            options[0].style.display = '';
        }
    });

    // Gérer le changement d'employé
    employeeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const salary = selectedOption.dataset.salary;

        if (this.value) {
            // Remplir automatiquement le salaire
            salaryInput.value = salary || '';
            // Afficher le bouton "Appliquer"
            applyButton.classList.remove('hidden');
            applyInfo.style.display = 'block';
        } else {
            // Masquer le bouton "Appliquer"
            applyButton.classList.add('hidden');
            applyInfo.style.display = 'none';
        }
    });

    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('{{ route('admin.generic-calculator.calculate') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                afficherResultats(data.calcul);
                resultatContainer.style.display = 'block';

                // Afficher le bouton "Appliquer" dans les résultats si un employé est sélectionné
                const applyButtonResults = document.getElementById('apply-button-results');
                if (employeeSelect.value) {
                    applyButtonResults.classList.remove('hidden');
                } else {
                    applyButtonResults.classList.add('hidden');
                }

                resultatContainer.scrollIntoView({ behavior: 'smooth' });
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du calcul. Veuillez réessayer.');
        });
    });
});

function afficherResultats(calcul) {
    // Salaire net principal
    document.getElementById('salaire_net_principal').textContent = calcul.salaire_net + ' FCFA';

    // Détails
    document.getElementById('res_salaire_mensuel').textContent = calcul.salaire_mensuel + ' FCFA';
    document.getElementById('res_jours').textContent = calcul.jours_travailles + ' / ' + calcul.jours_total;
    document.getElementById('res_pourcentage').textContent = calcul.pourcentage_presence + '%';
    document.getElementById('res_salaire_journalier').textContent = calcul.salaire_journalier + ' FCFA';
    document.getElementById('res_salaire_brut').textContent = calcul.salaire_brut + ' FCFA';
    document.getElementById('res_jours_absence').textContent = calcul.jours_absence;
    document.getElementById('res_montant_perdu').textContent = calcul.montant_perdu + ' FCFA';

    // Prime (afficher si > 0)
    if (parseFloat(calcul.prime.replace(/\s/g, '')) > 0) {
        document.getElementById('prime_row').style.display = 'flex';
        document.getElementById('res_prime').textContent = calcul.prime + ' FCFA';
    } else {
        document.getElementById('prime_row').style.display = 'none';
    }

    // Retards (afficher si > 0)
    if (calcul.penalite_retard && parseFloat(calcul.penalite_retard.replace(/\s/g, '')) > 0) {
        document.getElementById('retard_row').style.display = 'flex';
        document.getElementById('res_temps_retard').textContent = calcul.temps_retard_formate;
        document.getElementById('res_penalite_retard').textContent = '- ' + calcul.penalite_retard + ' FCFA';
    } else {
        document.getElementById('retard_row').style.display = 'none';
    }

    // Déduction (afficher si > 0)
    if (parseFloat(calcul.deduction.replace(/\s/g, '')) > 0) {
        document.getElementById('deduction_row').style.display = 'flex';
        document.getElementById('res_deduction').textContent = calcul.deduction + ' FCFA';
    } else {
        document.getElementById('deduction_row').style.display = 'none';
    }
}

function nouveauCalcul() {
    document.getElementById('resultat-container').style.display = 'none';
    document.getElementById('apply-button-results').classList.add('hidden');
    document.getElementById('payroll-form').scrollIntoView({ behavior: 'smooth' });
}

function imprimerResultat() {
    window.print();
}

function applyCalculation() {
    const employeeId = document.getElementById('employee_id').value;

    if (!employeeId) {
        alert('Veuillez sélectionner un employé pour appliquer le calcul.');
        return;
    }

    // Vérifier si un calcul a été effectué
    const resultatContainer = document.getElementById('resultat-container');
    if (resultatContainer.style.display === 'none') {
        alert('Veuillez d\'abord calculer la paie avant de l\'appliquer.');
        return;
    }

    if (!confirm('Êtes-vous sûr de vouloir appliquer ce calcul ? Il sera enregistré dans le rapport de paie.')) {
        return;
    }

    const formData = new FormData(document.getElementById('payroll-form'));
    formData.append('user_id', employeeId);

    fetch('{{ route('admin.generic-calculator.apply') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            // Réinitialiser le formulaire
            document.getElementById('payroll-form').reset();
            document.getElementById('resultat-container').style.display = 'none';
            document.getElementById('apply-button').classList.add('hidden');
            document.getElementById('apply-info').style.display = 'none';
        } else {
            alert('❌ Erreur: ' + (data.message || 'Une erreur est survenue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Erreur lors de l\'application du calcul. Veuillez réessayer.');
    });
}
</script>
@endpush

@endsection
