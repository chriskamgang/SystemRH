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

        <!-- Horaires de Travail -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-business-time text-indigo-500 mr-2"></i>
                Horaires de Travail
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Heure d'arrivée -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Heure d'arrivée
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="time"
                        name="morning_start_time"
                        value="{{ $workSchedule['morning_start_time'] }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Heure officielle de début de travail</p>
                </div>

                <!-- Heure de fin -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Heure de fin
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="time"
                        name="morning_end_time"
                        value="{{ $workSchedule['morning_end_time'] }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Heure officielle de fin de travail</p>
                </div>

                <!-- Tolérance de retard -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tolérance de retard (minutes)
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="late_tolerance"
                        value="{{ $workSchedule['late_tolerance'] }}"
                        min="0"
                        max="60"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Minutes de retard autorisées avant d'être marqué en retard</p>
                </div>
            </div>

            <div class="mt-4 bg-indigo-50 border border-indigo-200 rounded-lg p-3">
                <p class="text-xs text-indigo-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    Exemple : Arrivée à {{ $workSchedule['morning_start_time'] }} avec {{ $workSchedule['late_tolerance'] }} min de tolérance = retard compté à partir de
                    {{ \Carbon\Carbon::parse($workSchedule['morning_start_time'])->addMinutes((int)$workSchedule['late_tolerance'])->format('H:i') }}.
                    Fin de journée à {{ $workSchedule['morning_end_time'] }}.
                </p>
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

        <!-- Pause Déjeuner -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-utensils text-green-500 mr-2"></i>
                Pause Déjeuner
            </h2>

            <div class="flex items-center mb-4">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="break_enabled" value="1" class="sr-only peer" {{ $settings->break_enabled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    <span class="ms-3 text-sm font-medium text-gray-700">Activer la pause déjeuner</span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Début de la pause
                    </label>
                    <input
                        type="time"
                        name="break_start_time"
                        value="{{ substr($settings->break_start_time, 0, 5) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Fin de la pause
                    </label>
                    <input
                        type="time"
                        name="break_end_time"
                        value="{{ substr($settings->break_end_time, 0, 5) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                </div>
            </div>

            <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3">
                <p class="text-xs text-green-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    Pendant la pause, les notifications de présence ne seront pas envoyées.
                    Le temps de pause est automatiquement soustrait des heures travaillées pour les permanents et semi-permanents.
                    Les vacataires ne sont pas concernés par cette déduction.
                </p>
            </div>
        </div>

        <!-- Rappels de Cours -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-bell text-purple-500 mr-2"></i>
                Rappels de Cours
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Activer les rappels -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Activer les rappels de cours
                    </label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="course_reminders_enabled" value="1"
                            {{ \App\Models\Setting::get('course_reminders_enabled', '1') == '1' ? 'checked' : '' }}
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <!-- Délai de rappel -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Rappel avant le cours (minutes)
                    </label>
                    <select name="course_reminder_minutes"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        @php $currentReminder = \App\Models\Setting::get('course_reminder_minutes', '30'); @endphp
                        <option value="15" {{ $currentReminder == '15' ? 'selected' : '' }}>15 minutes</option>
                        <option value="30" {{ $currentReminder == '30' ? 'selected' : '' }}>30 minutes</option>
                        <option value="45" {{ $currentReminder == '45' ? 'selected' : '' }}>45 minutes</option>
                        <option value="60" {{ $currentReminder == '60' ? 'selected' : '' }}>1 heure</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Les enseignants recevront une notification X minutes avant le début de leur cours</p>
                </div>
            </div>

            <div class="mt-4 bg-purple-50 border border-purple-200 rounded-lg p-3">
                <p class="text-xs text-purple-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    Les rappels incluent le nom de la matière, l'heure, le campus et la salle.
                    Ils sont envoyés à tous les enseignants (vacataires, titulaires, semi-permanents) ayant des cours programmés.
                </p>
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-700"><strong>Arrivée:</strong> {{ $workSchedule['morning_start_time'] }}</p>
                    <p class="text-gray-700"><strong>Fin:</strong> {{ $workSchedule['morning_end_time'] }}</p>
                    <p class="text-gray-700"><strong>Tolérance retard:</strong> {{ $workSchedule['late_tolerance'] }} min</p>
                </div>
                <div>
                    <p class="text-gray-700"><strong>Pause:</strong> {{ substr($settings->break_start_time, 0, 5) }} - {{ substr($settings->break_end_time, 0, 5) }}</p>
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
