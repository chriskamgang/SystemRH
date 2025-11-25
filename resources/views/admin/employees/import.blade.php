@extends('layouts.admin')

@section('title', 'Importer des Employés')
@section('page-title', 'Importer des Employés')

@section('content')
<div class="max-w-5xl mx-auto">
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Section 1: Télécharger le Template -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-4">
                    <i class="fas fa-download text-4xl text-blue-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800">Étape 1 : Télécharger</h3>
                <p class="text-gray-600 mt-2">Obtenez le modèle Excel à remplir</p>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Le fichier contient :</strong><br>
                    • Des colonnes pré-remplies avec explications<br>
                    • 3 exemples de lignes à suivre<br>
                    • Des commentaires sur chaque colonne
                </p>
            </div>

            <a href="{{ route('admin.employees.download-template') }}"
               class="w-full block text-center px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition shadow-lg">
                <i class="fas fa-file-excel text-2xl mr-3"></i>
                Télécharger le Template Excel
            </a>

            <div class="mt-6 text-sm text-gray-600">
                <p class="font-semibold mb-2">📋 Colonnes du fichier :</p>
                <ul class="space-y-1">
                    <li><span class="font-medium">prenom</span> - Prénom de l'employé *</li>
                    <li><span class="font-medium">nom</span> - Nom de l'employé *</li>
                    <li><span class="font-medium">email</span> - Adresse email (unique) *</li>
                    <li><span class="font-medium">telephone</span> - Numéro de téléphone</li>
                    <li><span class="font-medium">mot_de_passe</span> - Mot de passe initial *</li>
                    <li><span class="font-medium">type_employe</span> - Type (Permanent, Vacataire...) *</li>
                    <li><span class="font-medium">salaire_mensuel</span> - Pour permanents (FCFA)</li>
                    <li><span class="font-medium">taux_horaire</span> - Pour vacataires (FCFA/h)</li>
                    <li><span class="font-medium">campus</span> - Campus assignés (séparés par virgule)</li>
                    <li><span class="font-medium">actif</span> - Statut (Oui / Non)</li>
                </ul>
                <p class="mt-2 text-xs text-gray-500">* = champs obligatoires</p>
            </div>
        </div>

        <!-- Section 2: Upload du Fichier -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                    <i class="fas fa-upload text-4xl text-green-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800">Étape 2 : Importer</h3>
                <p class="text-gray-600 mt-2">Téléversez votre fichier rempli</p>
            </div>

            <form method="POST" action="{{ route('admin.employees.import') }}" enctype="multipart/form-data" id="import-form">
                @csrf

                <!-- Zone de Drop -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition"
                     id="drop-zone">
                    <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 mb-4"></i>
                    <p class="text-lg font-semibold text-gray-700 mb-2">
                        Glissez-déposez votre fichier ici
                    </p>
                    <p class="text-sm text-gray-500 mb-4">ou cliquez pour parcourir</p>

                    <input type="file"
                           name="file"
                           id="file-input"
                           accept=".xlsx,.xls,.csv"
                           class="hidden"
                           required>

                    <label for="file-input"
                           class="inline-block px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg cursor-pointer transition">
                        <i class="fas fa-folder-open mr-2"></i>
                        Choisir un fichier
                    </label>

                    <p class="text-xs text-gray-500 mt-4">
                        Formats acceptés : Excel (.xlsx, .xls) ou CSV (.csv)<br>
                        Taille maximale : 5 Mo
                    </p>
                </div>

                <!-- Nom du fichier sélectionné -->
                <div id="file-info" class="hidden mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-file-excel text-2xl text-green-600 mr-3"></i>
                            <div>
                                <p class="font-semibold text-gray-800" id="file-name"></p>
                                <p class="text-sm text-gray-600" id="file-size"></p>
                            </div>
                        </div>
                        <button type="button"
                                onclick="resetFileInput()"
                                class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                @error('file')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror

                <!-- Bouton Submit -->
                <button type="submit"
                        id="submit-btn"
                        class="w-full mt-6 px-6 py-4 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-upload mr-2"></i>
                    <span id="btn-text">Importer les Employés</span>
                    <span id="btn-loading" class="hidden">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Import en cours...
                    </span>
                </button>
            </form>

            <!-- Instructions -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Conseils d'import
                </h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>✓ Supprimez les 3 lignes d'exemple avant d'importer</li>
                    <li>✓ Vérifiez que les emails sont uniques</li>
                    <li>✓ Les types d'employés acceptés : Permanent, Semi-Permanent, Vacataire, Administratif, Technique, Direction</li>
                    <li>✓ Les campus doivent exister dans le système</li>
                    <li>✓ L'ID employé sera généré automatiquement (EMP-2025-XXXX)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Section Info -->
    <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg">
        <h4 class="font-bold text-yellow-800 mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Important
        </h4>
        <ul class="text-sm text-yellow-700 space-y-2">
            <li>• Les employés avec des emails existants seront <strong>ignorés</strong></li>
            <li>• Les mots de passe seront <strong>chiffrés automatiquement</strong></li>
            <li>• Les campus inexistants seront <strong>ignorés</strong></li>
            <li>• Un rapport détaillé sera affiché après l'import</li>
        </ul>
    </div>

    <!-- Bouton Retour -->
    <div class="mt-6 text-center">
        <a href="{{ route('admin.employees.index') }}" class="inline-block px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour à la liste des employés
        </a>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('import-form');

    // Click sur la zone pour ouvrir le sélecteur
    dropZone.addEventListener('click', function(e) {
        if (e.target !== fileInput) {
            fileInput.click();
        }
    });

    // Drag & Drop
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', function(e) {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            displayFileInfo(files[0]);
        }
    });

    // Changement de fichier
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            displayFileInfo(this.files[0]);
        }
    });

    // Afficher les infos du fichier
    function displayFileInfo(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.classList.remove('hidden');
    }

    // Formater la taille du fichier
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        else if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
        else return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    // Réinitialiser le fichier
    window.resetFileInput = function() {
        fileInput.value = '';
        fileInfo.classList.add('hidden');
    };

    // Soumission du formulaire
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        document.getElementById('btn-text').classList.add('hidden');
        document.getElementById('btn-loading').classList.remove('hidden');
    });
});
</script>
@endpush

@endsection
