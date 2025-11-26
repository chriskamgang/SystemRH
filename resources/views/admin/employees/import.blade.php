@extends('layouts.admin')

@section('title', 'Importer des Employés')
@section('page-title', 'Importer des Employés')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('warning') }}
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button onclick="showTab('permanent')"
                    class="tab-button border-b-2 border-indigo-500 py-4 px-6 text-center text-indigo-600 font-medium"
                    id="tab-permanent">
                <i class="fas fa-user-tie mr-2"></i>
                Employés Permanents
            </button>
            <button onclick="showTab('semi-permanent')"
                    class="tab-button border-b-2 border-transparent py-4 px-6 text-center text-gray-500 hover:text-gray-700 font-medium"
                    id="tab-semi-permanent">
                <i class="fas fa-user-clock mr-2"></i>
                Employés Semi-Permanents
            </button>
            <button onclick="showTab('vacataire')"
                    class="tab-button border-b-2 border-transparent py-4 px-6 text-center text-gray-500 hover:text-gray-700 font-medium"
                    id="tab-vacataire">
                <i class="fas fa-user-graduate mr-2"></i>
                Employés Vacataires
            </button>
        </nav>
    </div>

    <!-- Tab Content: PERMANENTS -->
    <div id="content-permanent" class="tab-content">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Download Template -->
            <div class="bg-white rounded-lg shadow-lg p-8 border-t-4 border-indigo-500">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-100 rounded-full mb-4">
                        <i class="fas fa-download text-4xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Étape 1 : Télécharger</h3>
                    <p class="text-gray-600 mt-2">Template pour employés PERMANENTS</p>
                </div>

                <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 mb-6">
                    <p class="text-sm text-indigo-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Spécifique aux permanents :</strong><br>
                        • Salaire mensuel fixe<br>
                        • Shifts matin/soir (08h-13h / 14h-19h)<br>
                        • Assignation multi-campus
                    </p>
                </div>

                <a href="{{ route('admin.employees.download-permanent-template') }}"
                   class="w-full block text-center px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition shadow-lg">
                    <i class="fas fa-file-excel text-2xl mr-3"></i>
                    Télécharger Template Permanents
                </a>

                <div class="mt-6 text-sm text-gray-600">
                    <p class="font-semibold mb-2">📋 Colonnes du fichier :</p>
                    <ul class="space-y-1">
                        <li><span class="font-medium">prenom, nom, email</span> *</li>
                        <li><span class="font-medium">telephone, mot_de_passe</span></li>
                        <li><span class="font-medium">salaire_mensuel</span> (FCFA) *</li>
                        <li><span class="font-medium">campus</span> (séparés par virgule)</li>
                        <li><span class="font-medium">travail_matin</span> (Oui/Non)</li>
                        <li><span class="font-medium">travail_soir</span> (Oui/Non)</li>
                        <li><span class="font-medium">actif</span> (Oui/Non)</li>
                    </ul>
                </div>
            </div>

            <!-- Upload File -->
            <div class="bg-white rounded-lg shadow-lg p-8 border-t-4 border-indigo-500">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                        <i class="fas fa-upload text-4xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Étape 2 : Importer</h3>
                    <p class="text-gray-600 mt-2">Téléversez votre fichier rempli</p>
                </div>

                <form method="POST" action="{{ route('admin.employees.import-permanent') }}" enctype="multipart/form-data" class="import-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-500 transition">
                        <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 mb-4"></i>
                        <p class="text-lg font-semibold text-gray-700 mb-2">
                            Glissez-déposez votre fichier ici
                        </p>
                        <p class="text-sm text-gray-500 mb-4">ou cliquez pour parcourir</p>

                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="file-input hidden" required>

                        <button type="button" onclick="this.previousElementSibling.click()"
                                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                            <i class="fas fa-folder-open mr-2"></i>
                            Choisir un fichier
                        </button>

                        <div class="file-info mt-4 hidden">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-file-excel text-green-600 mr-2"></i>
                                <span class="file-name font-medium"></span>
                                <span class="file-size text-gray-500"></span>
                            </p>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full mt-6 px-6 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition shadow-lg disabled:opacity-50"
                            disabled>
                        <i class="fas fa-file-import mr-2"></i>
                        Importer les Permanents
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Content: SEMI-PERMANENTS -->
    <div id="content-semi-permanent" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Download Template -->
            <div class="bg-white rounded-lg shadow-lg p-8 border-t-4 border-green-500">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                        <i class="fas fa-download text-4xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Étape 1 : Télécharger</h3>
                    <p class="text-gray-600 mt-2">Template pour employés SEMI-PERMANENTS</p>
                </div>

                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <p class="text-sm text-green-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Spécifique aux semi-permanents :</strong><br>
                        • Salaire mensuel fixe<br>
                        • Volume horaire hebdomadaire (ex: 20h/semaine)<br>
                        • Jours de travail (ex: lundi,mercredi,vendredi)
                    </p>
                </div>

                <a href="{{ route('admin.employees.download-semi-permanent-template') }}"
                   class="w-full block text-center px-6 py-4 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition shadow-lg">
                    <i class="fas fa-file-excel text-2xl mr-3"></i>
                    Télécharger Template Semi-Permanents
                </a>

                <div class="mt-6 text-sm text-gray-600">
                    <p class="font-semibold mb-2">📋 Colonnes du fichier :</p>
                    <ul class="space-y-1">
                        <li><span class="font-medium">prenom, nom, email</span> *</li>
                        <li><span class="font-medium">telephone, mot_de_passe</span></li>
                        <li><span class="font-medium">salaire_mensuel</span> (FCFA) *</li>
                        <li><span class="font-medium">volume_horaire_hebdomadaire</span> (heures)</li>
                        <li><span class="font-medium">jours_travail</span> (lundi,mercredi...)</li>
                        <li><span class="font-medium">campus</span> (séparés par virgule)</li>
                        <li><span class="font-medium">actif</span> (Oui/Non)</li>
                    </ul>
                </div>
            </div>

            <!-- Upload File -->
            <div class="bg-white rounded-lg shadow-lg p-8 border-t-4 border-green-500">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                        <i class="fas fa-upload text-4xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Étape 2 : Importer</h3>
                    <p class="text-gray-600 mt-2">Téléversez votre fichier rempli</p>
                </div>

                <form method="POST" action="{{ route('admin.employees.import-semi-permanent') }}" enctype="multipart/form-data" class="import-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-green-500 transition">
                        <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 mb-4"></i>
                        <p class="text-lg font-semibold text-gray-700 mb-2">
                            Glissez-déposez votre fichier ici
                        </p>
                        <p class="text-sm text-gray-500 mb-4">ou cliquez pour parcourir</p>

                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="file-input hidden" required>

                        <button type="button" onclick="this.previousElementSibling.click()"
                                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                            <i class="fas fa-folder-open mr-2"></i>
                            Choisir un fichier
                        </button>

                        <div class="file-info mt-4 hidden">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-file-excel text-green-600 mr-2"></i>
                                <span class="file-name font-medium"></span>
                                <span class="file-size text-gray-500"></span>
                            </p>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full mt-6 px-6 py-4 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition shadow-lg disabled:opacity-50"
                            disabled>
                        <i class="fas fa-file-import mr-2"></i>
                        Importer les Semi-Permanents
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Content: VACATAIRES -->
    <div id="content-vacataire" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Download Template -->
            <div class="bg-white rounded-lg shadow-lg p-8 border-t-4 border-amber-500">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-100 rounded-full mb-4">
                        <i class="fas fa-download text-4xl text-amber-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Étape 1 : Télécharger</h3>
                    <p class="text-gray-600 mt-2">Template pour employés VACATAIRES</p>
                </div>

                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6">
                    <p class="text-sm text-amber-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Spécifique aux vacataires :</strong><br>
                        • Taux horaire (FCFA/heure)<br>
                        • Payés selon heures travaillées<br>
                        • Assignation multi-campus
                    </p>
                </div>

                <a href="{{ route('admin.employees.download-vacataire-template') }}"
                   class="w-full block text-center px-6 py-4 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition shadow-lg">
                    <i class="fas fa-file-excel text-2xl mr-3"></i>
                    Télécharger Template Vacataires
                </a>

                <div class="mt-6 text-sm text-gray-600">
                    <p class="font-semibold mb-2">📋 Colonnes du fichier :</p>
                    <ul class="space-y-1">
                        <li><span class="font-medium">prenom, nom, email</span> *</li>
                        <li><span class="font-medium">telephone, mot_de_passe</span></li>
                        <li><span class="font-medium">taux_horaire</span> (FCFA/heure) *</li>
                        <li><span class="font-medium">campus</span> (séparés par virgule)</li>
                        <li><span class="font-medium">actif</span> (Oui/Non)</li>
                    </ul>
                </div>
            </div>

            <!-- Upload File -->
            <div class="bg-white rounded-lg shadow-lg p-8 border-t-4 border-amber-500">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                        <i class="fas fa-upload text-4xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Étape 2 : Importer</h3>
                    <p class="text-gray-600 mt-2">Téléversez votre fichier rempli</p>
                </div>

                <form method="POST" action="{{ route('admin.employees.import-vacataire') }}" enctype="multipart/form-data" class="import-form">
                    @csrf
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-amber-500 transition">
                        <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 mb-4"></i>
                        <p class="text-lg font-semibold text-gray-700 mb-2">
                            Glissez-déposez votre fichier ici
                        </p>
                        <p class="text-sm text-gray-500 mb-4">ou cliquez pour parcourir</p>

                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="file-input hidden" required>

                        <button type="button" onclick="this.previousElementSibling.click()"
                                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                            <i class="fas fa-folder-open mr-2"></i>
                            Choisir un fichier
                        </button>

                        <div class="file-info mt-4 hidden">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-file-excel text-green-600 mr-2"></i>
                                <span class="file-name font-medium"></span>
                                <span class="file-size text-gray-500"></span>
                            </p>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full mt-6 px-6 py-4 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition shadow-lg disabled:opacity-50"
                            disabled>
                        <i class="fas fa-file-import mr-2"></i>
                        Importer les Vacataires
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bouton retour -->
    <div class="mt-8 text-center">
        <a href="{{ route('admin.employees.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour à la liste des employés
        </a>
    </div>
</div>

@push('scripts')
<script>
// Fonction pour changer d'onglet
function showTab(tabName) {
    // Cacher tous les contenus
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Réinitialiser tous les boutons d'onglets
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-indigo-500', 'border-green-500', 'border-amber-500', 'text-indigo-600', 'text-green-600', 'text-amber-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });

    // Afficher le contenu sélectionné
    document.getElementById('content-' + tabName).classList.remove('hidden');

    // Activer le bouton d'onglet correspondant
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500');

    if (tabName === 'permanent') {
        activeButton.classList.add('border-indigo-500', 'text-indigo-600');
    } else if (tabName === 'semi-permanent') {
        activeButton.classList.add('border-green-500', 'text-green-600');
    } else if (tabName === 'vacataire') {
        activeButton.classList.add('border-amber-500', 'text-amber-600');
    }
}

// Gérer les uploads de fichiers
document.querySelectorAll('.file-input').forEach(input => {
    input.addEventListener('change', function(e) {
        const form = this.closest('.import-form');
        const fileInfo = form.querySelector('.file-info');
        const fileName = form.querySelector('.file-name');
        const fileSize = form.querySelector('.file-size');
        const submitButton = form.querySelector('button[type="submit"]');

        if (this.files.length > 0) {
            const file = this.files[0];
            fileName.textContent = file.name;
            fileSize.textContent = ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
            fileInfo.classList.remove('hidden');
            submitButton.disabled = false;
        } else {
            fileInfo.classList.add('hidden');
            submitButton.disabled = true;
        }
    });
});
</script>
@endpush
@endsection
