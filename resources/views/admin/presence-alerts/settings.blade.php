@extends('layouts.admin')

@section('title', 'Configuration des Alertes de Présence')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Configuration des Alertes de Présence</h1>
            <p class="text-gray-600 mt-2">Configurez les notifications push et les heures d'envoi</p>
        </div>
        <a href="{{ route('admin.presence-alerts.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour à la liste
        </a>
    </div>

    <!-- Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.presence-alerts.settings.update') }}" class="space-y-6">
        @csrf

        <!-- Firebase Configuration -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-fire text-orange-500 mr-2"></i>
                Configuration Firebase
            </h2>

            <div class="space-y-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
                        <div>
                            <p class="text-green-800 font-semibold">Firebase API V1 Configuré</p>
                            <p class="text-sm text-green-700 mt-1">
                                Fichier: <code class="bg-green-100 px-2 py-1 rounded">storage/firebase-credentials.json</code>
                            </p>
                            <p class="text-xs text-green-600 mt-2">
                                <i class="fas fa-info-circle"></i>
                                Projet: attendance-6156f | Account: firebase-adminsdk@attendance-6156f.iam.gserviceaccount.com
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Note:</strong> Vous utilisez maintenant l'API Firebase Cloud Messaging V1 (la plus récente).
                        Le fichier JSON de compte de service a été automatiquement configuré.
                    </p>
                </div>
            </div>
        </div>

        <!-- Heures d'Envoi -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-clock text-blue-500 mr-2"></i>
                Heures d'Envoi des Notifications
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Permanents & Semi-permanents -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Permanents & Semi-permanents
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="time"
                        name="permanent_semi_permanent_time"
                        value="{{ substr($settings->permanent_semi_permanent_time, 0, 5) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Heure d'envoi pour les employés permanents et semi-permanents</p>
                </div>

                <!-- Temporaires / Vacataires -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Temporaires / Vacataires
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="time"
                        name="temporary_time"
                        value="{{ substr($settings->temporary_time, 0, 5) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Heure d'envoi pour les employés temporaires (vacataires)</p>
                </div>
            </div>
        </div>

        <!-- Paramètres de Réponse -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-hourglass-half text-yellow-500 mr-2"></i>
                Paramètres de Réponse
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Délai de Réponse -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Délai de Réponse (minutes)
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="response_delay_minutes"
                        value="{{ $settings->response_delay_minutes }}"
                        min="5"
                        max="180"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Temps accordé à l'employé pour répondre (5-180 minutes)</p>
                </div>

                <!-- Pénalité -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pénalité (heures)
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="penalty_hours"
                        value="{{ $settings->penalty_hours }}"
                        min="0.25"
                        max="24"
                        step="0.25"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Heures de salaire coupées en cas de non-réponse validée</p>
                </div>
            </div>
        </div>

        <!-- Activation du Système -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-toggle-on text-green-500 mr-2"></i>
                Activation du Système
            </h2>

            <div class="flex items-center space-x-4">
                <label class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_active"
                        {{ $settings->is_active ? 'checked' : '' }}
                        class="w-6 h-6 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <span class="ml-3 text-sm font-medium text-gray-700">
                        Système actif (les notifications seront envoyées)
                    </span>
                </label>
            </div>

            @if($settings->is_active)
                <p class="text-sm text-green-600 mt-2">
                    <i class="fas fa-check-circle"></i>
                    Le système est actuellement <strong>actif</strong>. Les notifications sont envoyées aux heures configurées.
                </p>
            @else
                <p class="text-sm text-orange-600 mt-2">
                    <i class="fas fa-pause-circle"></i>
                    Le système est actuellement <strong>inactif</strong>. Aucune notification ne sera envoyée.
                </p>
            @endif
        </div>

        <!-- Résumé -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">
                <i class="fas fa-info-circle mr-2"></i>
                Résumé de la Configuration Actuelle
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-700"><strong>Notification Permanents:</strong> {{ substr($settings->permanent_semi_permanent_time, 0, 5) }}</p>
                    <p class="text-gray-700"><strong>Notification Temporaires:</strong> {{ substr($settings->temporary_time, 0, 5) }}</p>
                </div>
                <div>
                    <p class="text-gray-700"><strong>Délai de réponse:</strong> {{ $settings->response_delay_minutes }} minutes</p>
                    <p class="text-gray-700"><strong>Pénalité:</strong> {{ $settings->penalty_hours }} heure(s)</p>
                </div>
            </div>
        </div>

        <!-- Boutons d'Action -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.presence-alerts.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg">
                Annuler
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg">
                <i class="fas fa-save mr-2"></i>
                Enregistrer la Configuration
            </button>
        </div>
    </form>

    <!-- Guide Rapide -->
    <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">
            <i class="fas fa-question-circle mr-2"></i>
            Guide Rapide
        </h3>
        <div class="space-y-2 text-sm text-gray-700">
            <p><strong>1. Firebase Server Key:</strong> Clé nécessaire pour envoyer les notifications push. Récupérez-la depuis Firebase Console.</p>
            <p><strong>2. Heures d'Envoi:</strong> Définissez à quelle heure les notifications "Êtes-vous en place ?" seront envoyées.</p>
            <p><strong>3. Délai de Réponse:</strong> Temps accordé à l'employé pour répondre à la notification avant création d'un incident.</p>
            <p><strong>4. Pénalité:</strong> Nombre d'heures de salaire coupées si l'admin valide l'incident de non-réponse.</p>
            <p><strong>5. Activation:</strong> Désactivez temporairement le système sans perdre la configuration.</p>
        </div>
    </div>
</div>
@endsection
