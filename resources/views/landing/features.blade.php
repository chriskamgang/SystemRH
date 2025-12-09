@extends('landing.layout')

@section('title', 'Fonctionnalités')
@section('description', 'Découvrez toutes les fonctionnalités de INSAM Presence pour gérer efficacement vos équipes')

@section('content')
<!-- Hero Section -->
<section class="gradient-bg text-white py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl md:text-6xl font-bold mb-6">
            Fonctionnalités Complètes
        </h1>
        <p class="text-xl text-blue-100">
            Tout ce dont vous avez besoin pour une gestion moderne et efficace de vos équipes
        </p>
    </div>
</section>

<!-- Features Grid -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <!-- Feature 1 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Géolocalisation GPS Précise</h3>
                    <p class="text-gray-600 mb-4">
                        Système de géofencing avancé basé sur la formule de Haversine pour une précision au mètre près. Les employés ne peuvent pointer qu'à l'intérieur du périmètre défini du campus.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Précision GPS jusqu'à 5 mètres</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Détection automatique de la zone</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Périmètre configurable par campus</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Applications Mobiles Natives</h3>
                    <p class="text-gray-600 mb-4">
                        Applications iOS et Android optimisées pour une expérience utilisateur fluide. Pointage en un clic avec interface intuitive et notifications push.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Mode hors ligne disponible</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Synchronisation automatique</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Interface multilingue (FR, EN)</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-clock text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Suivi en Temps Réel</h3>
                    <p class="text-gray-600 mb-4">
                        Dashboard en direct pour visualiser instantanément qui est présent, qui est en retard, et les statistiques de présence en temps réel pour tous vos campus.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Dashboard interactif</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Actualisation automatique</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Alertes configurables</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-users-cog text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Gestion Multi-Campus</h3>
                    <p class="text-gray-600 mb-4">
                        Gérez plusieurs sites depuis une seule plateforme. Chaque campus possède ses propres horaires, tolérances de retard et règles de pointage personnalisables.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Horaires personnalisés par campus</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Gestion centralisée</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Support multi-sites illimité</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-bell text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Vérifications de Présence</h3>
                    <p class="text-gray-600 mb-4">
                        Système intelligent de vérification toutes les 3 heures. Les employés présents reçoivent des notifications pour confirmer leur présence continue sur site.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Notifications push automatiques</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Intervalle configurable</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Historique des réponses</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Rapports et Statistiques</h3>
                    <p class="text-gray-600 mb-4">
                        Générez des rapports détaillés sur les présences, retards, absences par période, département ou employé. Export en PDF et Excel intégré.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>7 types de rapports paiement</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Export PDF/Excel</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Filtres avancés</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 7 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Calcul de Paie Automatique</h3>
                    <p class="text-gray-600 mb-4">
                        Calcul automatique des salaires pour enseignants vacataires avec prise en compte des heures, retards et absences. Application optionnelle de l'impôt (5%).
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Calcul par UE (matière)</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Déductions automatiques</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Impôt configurable</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 8 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-shield-alt text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Gestion des Permissions</h3>
                    <p class="text-gray-600 mb-4">
                        Système de rôles et permissions granulaire. Attribuez des accès spécifiques par département, campus ou fonctionnalité selon les responsabilités.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>4 rôles prédéfinis</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Permissions personnalisables</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Accès par département</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 9 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-book text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Gestion des Unités d'Enseignement</h3>
                    <p class="text-gray-600 mb-4">
                        Gérez les matières enseignées (UE) avec affectation aux enseignants, suivi des heures effectuées et validation pour le calcul de paie.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Import Excel massif</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Activation par UE</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Suivi heures validées</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 10 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-user-tie text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Types d'Employés Multiples</h3>
                    <p class="text-gray-600 mb-4">
                        Support de 6 types d'employés : enseignants titulaires, vacataires, semi-permanents, administratifs, techniques et direction avec règles spécifiques.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Horaires personnalisés</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Calculs de paie adaptés</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Import/Export par type</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 11 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-cloud text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Hébergement Cloud Sécurisé</h3>
                    <p class="text-gray-600 mb-4">
                        Données hébergées sur des serveurs sécurisés avec sauvegardes automatiques quotidiennes. SSL/TLS pour toutes les communications.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Sauvegarde automatique</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Chiffrement des données</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Uptime 99.9%</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 12 -->
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-history text-3xl text-white"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Historique Complet</h3>
                    <p class="text-gray-600 mb-4">
                        Conservation illimitée de l'historique des pointages avec horodatage précis et coordonnées GPS pour traçabilité et audit.
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Archivage illimité</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Recherche avancée</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Export par période</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
            Prêt à découvrir toutes ces fonctionnalités ?
        </h2>
        <p class="text-xl text-gray-600 mb-8">
            Téléchargez l'application et commencez à optimiser la gestion de vos équipes dès aujourd'hui.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('landing.download') }}" class="gradient-bg text-white px-8 py-4 rounded-full font-bold text-lg hover:opacity-90 transition">
                <i class="fas fa-download mr-2"></i>Télécharger l'application
            </a>
            <a href="{{ route('landing.pricing') }}" class="border-2 border-blue-600 text-blue-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-blue-600 hover:text-white transition">
                Voir les tarifs
            </a>
        </div>
    </div>
</section>
@endsection
