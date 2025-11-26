@extends('layouts.admin')

@section('title', 'Configuration Firebase')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-fire text-warning"></i>
            Configuration Firebase
        </h1>
    </div>

    <!-- Messages de feedback -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Colonne gauche: Statut -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i>
                        Statut de la Configuration
                    </h5>
                </div>
                <div class="card-body">
                    @if($fileExists && $config)
                        <!-- Configuration active -->
                        <div class="alert alert-success" role="alert">
                            <h6 class="alert-heading">
                                <i class="fas fa-check-circle"></i>
                                Firebase Configuré
                            </h6>
                            <p class="mb-0">Les notifications push sont actives et fonctionnelles.</p>
                        </div>

                        <div class="mt-4">
                            <h6 class="fw-bold mb-3">Informations du Projet</h6>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold" style="width: 40%;">Project ID:</td>
                                        <td><code>{{ $config['project_id'] ?? 'N/A' }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Client Email:</td>
                                        <td><small class="text-muted">{{ $config['client_email'] ?? 'N/A' }}</small></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Chemin du fichier:</td>
                                        <td><small class="text-muted">{{ $credentialsPath }}</small></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Utilisateurs avec token:</td>
                                        <td>
                                            <span class="badge bg-info">{{ $usersWithToken }}</span>
                                            @if($usersWithToken == 0)
                                                <small class="text-muted d-block mt-1">
                                                    Les utilisateurs doivent se connecter via l'app mobile.
                                                </small>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('admin.firebase.download') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                                Télécharger le fichier actuel
                            </a>
                        </div>
                    @else
                        <!-- Configuration manquante -->
                        <div class="alert alert-warning" role="alert">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle"></i>
                                Firebase Non Configuré
                            </h6>
                            <p class="mb-0">Aucun fichier de credentials Firebase n'est configuré. Les notifications push ne fonctionneront pas.</p>
                        </div>

                        <div class="mt-3">
                            <p class="text-muted mb-2">
                                <strong>Pour configurer Firebase:</strong>
                            </p>
                            <ol class="small text-muted">
                                <li>Allez sur <a href="https://console.firebase.google.com" target="_blank">Firebase Console</a></li>
                                <li>Sélectionnez votre projet</li>
                                <li>Paramètres du projet → Comptes de service</li>
                                <li>Générez une nouvelle clé privée (fichier JSON)</li>
                                <li>Uploadez le fichier ci-contre →</li>
                            </ol>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Colonne droite: Actions -->
        <div class="col-lg-6 mb-4">
            <!-- Upload du fichier -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-upload"></i>
                        {{ $fileExists ? 'Mettre à jour' : 'Uploader' }} le fichier Firebase
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.firebase.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        <div class="mb-3">
                            <label for="firebase_credentials" class="form-label">
                                Fichier de credentials Firebase (JSON)
                            </label>
                            <input
                                type="file"
                                class="form-control @error('firebase_credentials') is-invalid @enderror"
                                id="firebase_credentials"
                                name="firebase_credentials"
                                accept=".json"
                                required
                            >
                            @error('firebase_credentials')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-text">
                                <i class="fas fa-info-circle text-primary"></i>
                                Fichier JSON du service account Firebase. Exemple: <code>attendance-6156f-2a1a23ba78dc.json</code>
                            </div>
                        </div>

                        <div class="alert alert-info small mb-3" role="alert">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Note:</strong> L'upload va automatiquement mettre à jour la configuration et nettoyer le cache Laravel.
                            @if($fileExists)
                                L'ancien fichier sera sauvegardé automatiquement.
                            @endif
                        </div>

                        <button type="submit" class="btn btn-success w-100" id="uploadBtn">
                            <i class="fas fa-upload"></i>
                            {{ $fileExists ? 'Mettre à jour' : 'Uploader' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Test de la configuration -->
            @if($fileExists)
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-flask"></i>
                            Tester la Configuration
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Envoyer une notification de test pour vérifier que Firebase fonctionne correctement.
                        </p>

                        @if($usersWithToken > 0)
                            <form action="{{ route('admin.firebase.test') }}" method="POST" id="testForm">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100" id="testBtn">
                                    <i class="fas fa-paper-plane"></i>
                                    Envoyer une notification test
                                </button>
                            </form>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i>
                                La notification sera envoyée au premier utilisateur avec un token FCM.
                            </small>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle"></i>
                                Aucun utilisateur avec un token FCM disponible. Connectez-vous via l'application mobile d'abord.
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Guide rapide -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle"></i>
                        Guide Rapide
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="fw-bold">
                                <span class="badge bg-primary rounded-circle me-2">1</span>
                                Obtenir le fichier
                            </h6>
                            <p class="small text-muted">
                                Téléchargez le fichier JSON depuis
                                <a href="https://console.firebase.google.com" target="_blank">Firebase Console</a>
                                → Paramètres → Comptes de service → Générer une nouvelle clé privée
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold">
                                <span class="badge bg-success rounded-circle me-2">2</span>
                                Uploader
                            </h6>
                            <p class="small text-muted">
                                Utilisez le formulaire ci-dessus pour uploader le fichier JSON.
                                La configuration sera mise à jour automatiquement.
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold">
                                <span class="badge bg-warning text-dark rounded-circle me-2">3</span>
                                Tester
                            </h6>
                            <p class="small text-muted">
                                Envoyez une notification de test pour vérifier que tout fonctionne.
                                Un utilisateur doit être connecté via l'app mobile.
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="small text-muted">
                        <p class="mb-2"><strong>Notes importantes:</strong></p>
                        <ul class="mb-0">
                            <li>Le même fichier Firebase fonctionne pour le développement et la production</li>
                            <li>Le fichier est stocké de manière sécurisée dans <code>storage/app/firebase/</code></li>
                            <li>Les anciens fichiers sont automatiquement sauvegardés lors d'un remplacement</li>
                            <li>Le cache Laravel est automatiquement nettoyé après chaque upload</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Désactiver le bouton pendant l'upload
document.getElementById('uploadForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Upload en cours...';
});

// Désactiver le bouton pendant le test
document.getElementById('testForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('testBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
});

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>
@endpush
@endsection
