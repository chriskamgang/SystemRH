@extends('layouts.admin')

@section('title', 'Paramètres')
@section('page-title', 'Paramètres du Système')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Paramètres du Système</h2>
            <p class="text-gray-600 mt-1">Gérez les paramètres globaux de l'application</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configuration des Cartes -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-map-marked-alt text-blue-600 mr-2"></i>
                Configuration des Cartes
            </h3>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <!-- Choix du fournisseur -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fournisseur de cartes</label>
                        <select name="map_provider" id="map_provider"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="openstreetmap" {{ \App\Models\Setting::get('map_provider', 'openstreetmap') == 'openstreetmap' ? 'selected' : '' }}>
                                OpenStreetMap (Gratuit, recommandé)
                            </option>
                            <option value="google" {{ \App\Models\Setting::get('map_provider') == 'google' ? 'selected' : '' }}>
                                Google Maps (Nécessite une clé API)
                            </option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            OpenStreetMap est gratuit, sans limite et fonctionne très bien au Cameroun.
                        </p>
                    </div>

                    <!-- Clé Google Maps (conditionnelle) -->
                    <div id="google-maps-section" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Clé API Google Maps</label>
                        <input type="text" name="google_maps_api_key"
                               value="{{ \App\Models\Setting::get('google_maps_api_key', '') }}"
                               placeholder="AIzaSy..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Pour obtenir une clé API:
                        </p>
                        <ol class="mt-2 ml-6 text-sm text-gray-600 list-decimal space-y-1">
                            <li>Allez sur <a href="https://console.cloud.google.com" target="_blank" class="text-blue-600 hover:underline">Google Cloud Console</a></li>
                            <li>Créez ou sélectionnez un projet</li>
                            <li>Activez "Maps JavaScript API"</li>
                            <li>Allez dans "Identifiants" → "Créer des identifiants" → "Clé API"</li>
                            <li>Copiez la clé et collez-la ci-dessus</li>
                        </ol>
                    </div>

                        @if(\App\Models\Setting::get('google_maps_api_key'))
                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg mt-2">
                                <p class="text-sm text-green-700">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    La clé API est configurée!
                                </p>
                            </div>
                        @else
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mt-2">
                                <p class="text-sm text-yellow-700">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Clé API requise pour Google Maps.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Info OpenStreetMap -->
                    <div id="osm-info" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 class="font-medium text-blue-800 mb-2">
                            <i class="fas fa-globe-africa mr-2"></i>
                            OpenStreetMap est configuré
                        </h4>
                        <p class="text-sm text-blue-700 mb-2">
                            ✅ Gratuit et sans limite d'utilisation<br>
                            ✅ Excellente couverture en Afrique<br>
                            ✅ Pas de clé API nécessaire<br>
                            ✅ Données mises à jour par la communauté
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer la configuration
                    </button>
                </div>
            </form>
        </div>

        <script>
            // Toggle Google Maps section visibility
            document.getElementById('map_provider').addEventListener('change', function() {
                const googleSection = document.getElementById('google-maps-section');
                const osmInfo = document.getElementById('osm-info');

                if (this.value === 'google') {
                    googleSection.style.display = 'block';
                    osmInfo.style.display = 'none';
                } else {
                    googleSection.style.display = 'none';
                    osmInfo.style.display = 'block';
                }
            });

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                const mapProvider = document.getElementById('map_provider').value;
                const googleSection = document.getElementById('google-maps-section');
                const osmInfo = document.getElementById('osm-info');

                if (mapProvider === 'google') {
                    googleSection.style.display = 'block';
                    osmInfo.style.display = 'none';
                } else {
                    googleSection.style.display = 'none';
                    osmInfo.style.display = 'block';
                }
            });
        </script>

        <!-- Configuration des Notifications de Présence -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-bell text-purple-600 mr-2"></i>
                Notifications de Vérification de Présence
            </h3>

            <form method="POST" action="{{ route('admin.settings.update') }}" id="presence-form">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <!-- Heures de vérification -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Heures d'envoi des notifications "Êtes-vous en place?"
                        </label>

                        <div id="presence-hours-container" class="space-y-2">
                            @php
                                $hours = \App\Models\Setting::get('presence_check_hours', ['10:00', '15:00', '18:30', '20:45', '21:00']);
                            @endphp

                            @foreach($hours as $index => $hour)
                            <div class="flex items-center gap-2 hour-row">
                                <input type="time"
                                       name="presence_check_hours[]"
                                       value="{{ $hour }}"
                                       class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                       required>
                                <button type="button"
                                        onclick="removeHourRow(this)"
                                        class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>

                        <button type="button"
                                onclick="addHourRow()"
                                class="mt-3 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter une heure
                        </button>

                        <p class="mt-3 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Les employés qui ont un check-in actif recevront une notification aux heures configurées.
                        </p>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition">
                            <i class="fas fa-save mr-2"></i>
                            Enregistrer les heures
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Configuration du Géofencing -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-map-marker-alt text-indigo-600 mr-2"></i>
                Notifications de Géofencing (Entrée en Zone)
            </h3>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <!-- Activer le géofencing -->
                    <div class="flex items-center justify-between p-4 bg-indigo-50 rounded-lg">
                        <div>
                            <h4 class="font-medium text-gray-900">Activer les notifications d'entrée en zone</h4>
                            <p class="text-sm text-gray-600 mt-1">
                                Envoyer une notification quand un employé entre dans la zone d'un campus
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="geofence_notification_enabled"
                                   value="1"
                                   {{ \App\Models\Setting::get('geofence_notification_enabled', true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-14 h-8 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <!-- Cooldown -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Délai anti-spam (minutes)
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="geofence_notification_cooldown_minutes"
                               value="{{ \App\Models\Setting::get('geofence_notification_cooldown_minutes', 360) }}"
                               min="30"
                               max="1440"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               required>
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Temps minimum entre deux notifications pour le même campus (recommandé: 360 minutes = 6 heures)
                        </p>
                    </div>

                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h4 class="font-medium text-yellow-800 mb-2">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Comment ça marche ?
                        </h4>
                        <ul class="text-sm text-yellow-700 space-y-1">
                            <li>✓ L'application détecte automatiquement l'entrée dans la zone d'un campus</li>
                            <li>✓ Une notification "Vous êtes dans le Campus X" apparaît</li>
                            <li>✓ L'employé peut faire check-in en 1 clic depuis la notification</li>
                            <li>✓ Fonctionne même quand l'application est fermée (géofencing natif)</li>
                        </ul>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                            <i class="fas fa-save mr-2"></i>
                            Enregistrer la configuration
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <script>
            // Gestion des heures de vérification de présence
            function addHourRow() {
                const container = document.getElementById('presence-hours-container');
                const newRow = document.createElement('div');
                newRow.className = 'flex items-center gap-2 hour-row';
                newRow.innerHTML = `
                    <input type="time"
                           name="presence_check_hours[]"
                           value="09:00"
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                           required>
                    <button type="button"
                            onclick="removeHourRow(this)"
                            class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                container.appendChild(newRow);
            }

            function removeHourRow(button) {
                const container = document.getElementById('presence-hours-container');
                const rows = container.querySelectorAll('.hour-row');

                // Ne pas supprimer s'il ne reste qu'une seule heure
                if (rows.length <= 1) {
                    alert('Vous devez conserver au moins une heure de vérification.');
                    return;
                }

                button.closest('.hour-row').remove();
            }
        </script>

        <!-- Paramètres de Paie -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                Paramètres de Paie
            </h3>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Pénalité par seconde de retard -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Pénalité par seconde de retard (FCFA)
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" name="penalty_per_second"
                               value="{{ \App\Models\Setting::get('penalty_per_second', '0.50') }}"
                               placeholder="0.50"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Montant déduit pour chaque seconde de retard
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Exemple : 0.50 FCFA/seconde = 30 FCFA/minute = 1,800 FCFA/heure
                        </p>
                    </div>

                    <!-- Heures de travail par jour (Lun-Ven) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Heures de travail/jour (Lun-Ven)
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.5" name="working_hours_per_day"
                               value="{{ \App\Models\Setting::get('working_hours_per_day', '8') }}"
                               placeholder="8"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Nombre d'heures standards du lundi au vendredi
                        </p>
                    </div>

                    <!-- Heures de travail le samedi -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Heures de travail le samedi
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.5" name="saturday_working_hours"
                               value="{{ \App\Models\Setting::get('saturday_working_hours', '4') }}"
                               placeholder="4"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                        <p class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Nombre d'heures le samedi (demi-journée)
                        </p>
                    </div>
                </div>

                <!-- Info box -->
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="font-medium text-blue-800 mb-2">
                        <i class="fas fa-calculator mr-2"></i>
                        Calcul automatique
                    </h4>
                    <p class="text-sm text-blue-700">
                        Le système calculera automatiquement :
                    </p>
                    <ul class="mt-2 ml-6 text-sm text-blue-700 list-disc space-y-1">
                        <li>Jours ouvrables = (Lun-Ven × 1) + (Sam × 0.5)</li>
                        <li>Taux journalier = Salaire mensuel ÷ Jours ouvrables</li>
                        <li>Taux horaire = Taux journalier ÷ Heures par jour</li>
                        <li>Taux par minute = Taux horaire ÷ 60</li>
                        <li>Taux par seconde = Taux par minute ÷ 60</li>
                        <li>Pénalité retard = Secondes de retard × Pénalité configurée</li>
                    </ul>
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer les paramètres de paie
                    </button>
                </div>
            </form>
        </div>

        <!-- Taux Horaires par Niveau (Vacataires) -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-graduation-cap text-indigo-600 mr-2"></i>
                Taux Horaires par Niveau (Vacataires)
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Définissez les taux horaires par défaut pour les UE de Licence et Master.
                Pour les BTS, le taux horaire du vacataire (dans sa fiche employé) est utilisé.
            </p>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Taux Licence -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-book text-blue-500 mr-1"></i>
                            Taux horaire Licence (FCFA/h)
                        </label>
                        <input type="number" step="100" min="0" name="taux_horaire_licence"
                               value="{{ \App\Models\Setting::get('taux_horaire_licence', '5000') }}"
                               placeholder="5000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Ce taux sera appliqué automatiquement aux UE de niveau Licence
                        </p>
                    </div>

                    <!-- Taux Master -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user-graduate text-purple-500 mr-1"></i>
                            Taux horaire Master (FCFA/h)
                        </label>
                        <input type="number" step="100" min="0" name="taux_horaire_master"
                               value="{{ \App\Models\Setting::get('taux_horaire_master', '7500') }}"
                               placeholder="7500"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            Ce taux sera appliqué automatiquement aux UE de niveau Master
                        </p>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                    <h4 class="font-medium text-indigo-800 mb-2">
                        <i class="fas fa-info-circle mr-1"></i> Fonctionnement
                    </h4>
                    <ul class="text-sm text-indigo-700 list-disc ml-5 space-y-1">
                        <li><strong>BTS 1 & 2</strong> : Utilise le taux horaire personnel du vacataire (fiche employé)</li>
                        <li><strong>Licence</strong> : Utilise le taux configuré ci-dessus (pré-rempli à la création de la UE)</li>
                        <li><strong>Master</strong> : Utilise le taux configuré ci-dessus (pré-rempli à la création de la UE)</li>
                        <li>Le taux peut être modifié individuellement sur chaque UE si nécessaire</li>
                    </ul>
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer les taux horaires
                    </button>
                </div>
            </form>
        </div>

        <!-- Configuration des Jours de Travail -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-calendar-alt text-purple-600 mr-2"></i>
                Configuration des Jours de Travail
            </h3>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <!-- Mode de calcul des jours -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Mode de calcul des jours travaillés
                            <span class="text-red-500">*</span>
                        </label>

                        <div class="space-y-3">
                            <!-- Option 1: 30 jours fixes -->
                            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition
                                {{ \App\Models\Setting::get('working_days_mode', 'fixed_30') == 'fixed_30' ? 'border-purple-500 bg-purple-50' : 'border-gray-200' }}">
                                <input type="radio" name="working_days_mode" value="fixed_30"
                                       class="mt-1 mr-3"
                                       {{ \App\Models\Setting::get('working_days_mode', 'fixed_30') == 'fixed_30' ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">
                                        📅 30 jours fixes par mois
                                        <span class="ml-2 px-2 py-0.5 text-xs bg-purple-100 text-purple-800 rounded">RECOMMANDÉ</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Tous les mois comptent exactement <strong>30 jours</strong>, peu importe le nombre réel de jours.
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        ✅ Simple et cohérent<br>
                                        ✅ Même salaire de base chaque mois<br>
                                        ✅ Calcul facile pour les employés
                                    </p>
                                </div>
                            </label>

                            <!-- Option 2: Tous les jours du mois -->
                            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition
                                {{ \App\Models\Setting::get('working_days_mode') == 'all_days' ? 'border-purple-500 bg-purple-50' : 'border-gray-200' }}">
                                <input type="radio" name="working_days_mode" value="all_days"
                                       class="mt-1 mr-3"
                                       {{ \App\Models\Setting::get('working_days_mode') == 'all_days' ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">
                                        📆 Tous les jours du mois (28-31 jours)
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Le nombre de jours varie selon le mois : <strong>28, 29, 30 ou 31 jours</strong>.
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        ⚠️ Salaire de base varie légèrement selon le mois<br>
                                        ✅ Reflète le calendrier réel
                                    </p>
                                </div>
                            </label>

                            <!-- Option 3: Jours ouvrables -->
                            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition
                                {{ \App\Models\Setting::get('working_days_mode', 'fixed_30') == 'business_days' ? 'border-purple-500 bg-purple-50' : 'border-gray-200' }}">
                                <input type="radio" name="working_days_mode" value="business_days"
                                       class="mt-1 mr-3"
                                       {{ \App\Models\Setting::get('working_days_mode', 'fixed_30') == 'business_days' ? 'checked' : '' }}>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">
                                        📊 Jours ouvrables (Lun-Ven + Samedi)
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Calcul selon les jours ouvrés : <strong>Lun-Ven = 1 jour</strong>, <strong>Samedi = configurable</strong>, Dimanche = 0 ou 1.
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        ℹ️ Généralement ~22-23 jours/mois<br>
                                        ✅ Idéal si weekend non travaillé
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Options pour mode jours ouvrables -->
                    <div id="business-days-options" style="display: none;" class="pl-4 border-l-4 border-purple-300 space-y-4">
                        <h4 class="font-medium text-gray-800">Options pour le mode "Jours ouvrables"</h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Valeur du samedi -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Valeur du samedi
                                </label>
                                <select name="saturday_working_value"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="0" {{ \App\Models\Setting::get('saturday_working_value', '0.5') == '0' ? 'selected' : '' }}>
                                        Non travaillé (0 jour)
                                    </option>
                                    <option value="0.5" {{ \App\Models\Setting::get('saturday_working_value', '0.5') == '0.5' ? 'selected' : '' }}>
                                        Demi-journée (0.5 jour)
                                    </option>
                                    <option value="1" {{ \App\Models\Setting::get('saturday_working_value', '0.5') == '1' ? 'selected' : '' }}>
                                        Journée complète (1 jour)
                                    </option>
                                </select>
                            </div>

                            <!-- Dimanche travaillé -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Dimanche travaillé ?
                                </label>
                                <select name="sunday_working"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="0" {{ !\App\Models\Setting::get('sunday_working', false) ? 'selected' : '' }}>
                                        Non (0 jour)
                                    </option>
                                    <option value="1" {{ \App\Models\Setting::get('sunday_working', false) ? 'selected' : '' }}>
                                        Oui (1 jour)
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Info box -->
                    <div class="p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <h4 class="font-medium text-purple-800 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>
                            Impact du mode choisi
                        </h4>
                        <ul class="text-sm text-purple-700 space-y-1">
                            <li><strong>30 jours fixes :</strong> Salaire mensuel ÷ 30 = taux journalier constant</li>
                            <li><strong>Tous les jours :</strong> Salaire mensuel ÷ jours du mois (28-31) = taux varie légèrement</li>
                            <li><strong>Jours ouvrables :</strong> Salaire mensuel ÷ ~22.5 jours = taux plus élevé (weekends non payés)</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer la configuration
                    </button>
                </div>
            </form>
        </div>

        <script>
            // Toggle business days options visibility
            document.querySelectorAll('input[name="working_days_mode"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const businessDaysOptions = document.getElementById('business-days-options');
                    if (this.value === 'business_days') {
                        businessDaysOptions.style.display = 'block';
                    } else {
                        businessDaysOptions.style.display = 'none';
                    }
                });
            });

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                const selectedMode = document.querySelector('input[name="working_days_mode"]:checked');
                const businessDaysOptions = document.getElementById('business-days-options');
                if (selectedMode && selectedMode.value === 'business_days') {
                    businessDaysOptions.style.display = 'block';
                }
            });
        </script>

        <!-- Configuration des Plages Horaires (Matin/Soir) -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-clock text-indigo-600 mr-2"></i>
                Configuration des Plages Horaires (Matin/Soir)
            </h3>

            <div class="mb-4 p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                <p class="text-sm text-indigo-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Pour les permanents enseignants:</strong> Ils peuvent travailler le matin ET/OU le soir. Chaque plage est enregistrée séparément.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Plage MATIN -->
                    <div class="p-4 border-2 border-blue-200 rounded-lg bg-blue-50">
                        <h4 class="font-medium text-blue-900 mb-4 flex items-center">
                            <i class="fas fa-sun mr-2"></i>
                            Plage MATIN
                        </h4>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Heure de début (retard après cette heure)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="time" name="morning_start_time"
                                       value="{{ \App\Models\Setting::get('morning_start_time', '08:15') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       required>
                                <p class="mt-1 text-xs text-gray-600">
                                    Défaut: 8h15 - Si arrivée après = RETARD
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Heure de fin
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="time" name="morning_end_time"
                                       value="{{ \App\Models\Setting::get('morning_end_time', '17:00') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       required>
                                <p class="mt-1 text-xs text-gray-600">
                                    Défaut: 17h00
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Plage SOIR -->
                    <div class="p-4 border-2 border-orange-200 rounded-lg bg-orange-50">
                        <h4 class="font-medium text-orange-900 mb-4 flex items-center">
                            <i class="fas fa-moon mr-2"></i>
                            Plage SOIR
                        </h4>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Heure de début (retard après cette heure)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="time" name="evening_start_time"
                                       value="{{ \App\Models\Setting::get('evening_start_time', '17:30') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                                       required>
                                <p class="mt-1 text-xs text-gray-600">
                                    Défaut: 17h30 - Si arrivée après = RETARD
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Heure de fin
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="time" name="evening_end_time"
                                       value="{{ \App\Models\Setting::get('evening_end_time', '21:00') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                                       required>
                                <p class="mt-1 text-xs text-gray-600">
                                    Défaut: 21h00
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Heure de séparation -->
                <div class="mt-6 p-4 border-2 border-purple-200 rounded-lg bg-purple-50">
                    <h4 class="font-medium text-purple-900 mb-3 flex items-center">
                        <i class="fas fa-exchange-alt mr-2"></i>
                        Heure de séparation automatique
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Check-in avant cette heure = MATIN / Après = SOIR
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="shift_separator_time"
                                   value="{{ \App\Models\Setting::get('shift_separator_time', '17:00') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   required>
                            <p class="mt-1 text-xs text-gray-600">
                                Défaut: 17h00
                            </p>
                        </div>
                        <div class="text-sm text-purple-700">
                            <i class="fas fa-lightbulb mr-2"></i>
                            <strong>Exemple:</strong> Si réglé sur 17h00<br>
                            • Check-in à 16h59 → Plage MATIN<br>
                            • Check-in à 17h01 → Plage SOIR
                        </div>
                    </div>
                </div>

                <!-- Info box -->
                <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h4 class="font-medium text-green-800 mb-2">
                        <i class="fas fa-check-circle mr-2"></i>
                        Comment ça fonctionne
                    </h4>
                    <ul class="text-sm text-green-700 space-y-1">
                        <li><strong>Détection automatique:</strong> Le système détecte automatiquement si c'est matin ou soir selon l'heure du check-in</li>
                        <li><strong>Deux présences séparées:</strong> Un permanent qui travaille matin + soir aura 2 enregistrements distincts</li>
                        <li><strong>Retards indépendants:</strong> Le retard du matin et du soir sont calculés séparément</li>
                        <li><strong>Exemple:</strong> Arrivée matin à 8h30 (retard 15 min) + Arrivée soir à 17h45 (retard 15 min) = 30 min de retard total</li>
                    </ul>
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer les horaires
                    </button>
                </div>
            </form>
        </div>

        <!-- Paramètres généraux -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres Généraux</h3>
            
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'application</label>
                        <input type="text" name="app_name" value="{{ config('app.name', 'Attendance System') }}" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email de contact</label>
                        <input type="email" name="contact_email" value="{{ config('mail.from.address', '') }}" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fuseau horaire</label>
                        <select name="timezone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Africa/Douala" {{ config('app.timezone') === 'Africa/Douala' ? 'selected' : '' }}>Douala/Yaoundé (GMT+1)</option>
                            <option value="UTC" {{ config('app.timezone') === 'UTC' ? 'selected' : '' }}>UTC</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" 
                               {{ config('app.maintenance_mode') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="maintenance_mode" class="ml-2 block text-sm text-gray-900">
                            Mode maintenance
                        </label>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>

        <!-- Paramètres de notification -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres de Notification</h3>
            
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Intervalle des vérifications (minutes)</label>
                        <input type="number" name="check_interval" value="{{ config('app.check_interval', 180) }}" min="30" max="720"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500">Ex: 180 = toutes les 3 heures</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Heure de check-out automatique</label>
                        <input type="time" name="auto_checkout_time" value="{{ config('app.auto_checkout_time', '19:00') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-sm text-gray-500">Heure à laquelle un check-out automatique est effectué si oublié</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="send_reminders" id="send_reminders" 
                               {{ config('app.send_reminders', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="send_reminders" class="ml-2 block text-sm text-gray-900">
                            Envoyer des rappels de check-out
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="send_late_alerts" id="send_late_alerts" 
                               {{ config('app.send_late_alerts', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="send_late_alerts" class="ml-2 block text-sm text-gray-900">
                            Envoyer des alertes de retard
                        </label>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        Enregistrer les notifications
                    </button>
                </div>
            </form>
        </div>

        <!-- Informations du système -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du Système</h3>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Version de l'application</span>
                    <span class="text-sm text-gray-900">1.0.0</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Version de Laravel</span>
                    <span class="text-sm text-gray-900">{{ app()->version() }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Version de PHP</span>
                    <span class="text-sm text-gray-900">{{ PHP_VERSION }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Base de données</span>
                    <span class="text-sm text-gray-900">{{ config('database.default') }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Serveur</span>
                    <span class="text-sm text-gray-900">{{ request()->server('SERVER_SOFTWARE') ?: 'Inconnu' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Dernière mise à jour</span>
                    <span class="text-sm text-gray-900">{{ now()->format('d/m/Y H:i:s') }}</span>
                </div>
            </div>
        </div>

        <!-- Gestion des Rôles -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-user-tag text-purple-600 mr-2"></i>
                    Gestion des Rôles
                </h3>
                <button type="button" onclick="openRoleModal()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded transition">
                    <i class="fas fa-plus mr-1"></i>
                    Ajouter un rôle
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom affiché</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($roles as $role)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                    {{ $role->name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $role->display_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $role->description ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if($role->id != 1)
                                <button type="button" onclick="editRole({{ $role->id }}, '{{ $role->display_name }}', '{{ $role->description }}')" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.settings.roles.delete', $role->id) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @else
                                <span class="text-gray-400">
                                    <i class="fas fa-lock"></i>
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter/Modifier Rôle -->
<div id="roleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Ajouter un rôle</h3>
            <button type="button" onclick="closeRoleModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="roleForm" method="POST" action="{{ route('admin.settings.roles.store') }}">
            @csrf
            <input type="hidden" id="roleMethod" name="_method" value="POST">

            <div class="space-y-4">
                <div id="roleNameField">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom du rôle (technique) *</label>
                    <input type="text" name="name" id="roleName" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                           placeholder="ex: manager, supervisor">
                    <p class="text-xs text-gray-500 mt-1">Lettres minuscules et underscores uniquement</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom affiché *</label>
                    <input type="text" name="display_name" id="roleDisplayName" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                           placeholder="ex: Manager, Superviseur">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="roleDescription" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="Description du rôle"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeRoleModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRoleModal() {
    document.getElementById('roleModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Ajouter un rôle';
    document.getElementById('roleForm').action = '{{ route('admin.settings.roles.store') }}';
    document.getElementById('roleMethod').value = 'POST';
    document.getElementById('roleNameField').classList.remove('hidden');
    document.getElementById('roleName').value = '';
    document.getElementById('roleDisplayName').value = '';
    document.getElementById('roleDescription').value = '';
}

function editRole(id, displayName, description) {
    document.getElementById('roleModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Modifier le rôle';
    document.getElementById('roleForm').action = '/admin/settings/roles/' + id;
    document.getElementById('roleMethod').value = 'PUT';
    document.getElementById('roleNameField').classList.add('hidden');
    document.getElementById('roleDisplayName').value = displayName;
    document.getElementById('roleDescription').value = description || '';
}

function closeRoleModal() {
    document.getElementById('roleModal').classList.add('hidden');
}

// Fermer le modal en cliquant en dehors
document.getElementById('roleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRoleModal();
    }
});
</script>
@endsection