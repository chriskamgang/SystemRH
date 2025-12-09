@extends('landing.layout')

@section('title', 'Accueil')
@section('description', 'INSAM Presence - Solution moderne de gestion de présence par géolocalisation pour les établissements d\'enseignement supérieur')

@section('content')
<!-- Hero Section -->
<section class="gradient-bg text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    Gérez la présence de vos employés
                    <span class="text-yellow-300">simplement</span>
                </h1>
                <p class="text-xl text-blue-100 mb-8">
                    Solution moderne de gestion de présence par géolocalisation pour les établissements d'enseignement supérieur. Suivez, contrôlez et optimisez le temps de travail en temps réel.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('landing.download') }}" class="bg-white text-blue-900 px-8 py-4 rounded-full font-bold text-lg hover:bg-yellow-300 transition transform hover:scale-105 text-center">
                        <i class="fas fa-download mr-2"></i>Télécharger l'app
                    </a>
                    <a href="{{ route('landing.features') }}" class="border-2 border-white text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-white hover:text-blue-900 transition text-center">
                        En savoir plus
                    </a>
                </div>
            </div>
            <div class="hidden lg:block">
                <div class="relative">
                    <div class="absolute inset-0 bg-yellow-300 rounded-full opacity-20 blur-3xl"></div>
                    <img src="{{ asset('landing/images/logo.png') }}" alt="INSAM Presence" class="relative z-10 w-full max-w-md mx-auto animate-pulse">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
            <div class="hover-lift p-6">
                <div class="text-5xl font-bold gradient-text mb-2">500+</div>
                <p class="text-gray-600">Employés gérés</p>
            </div>
            <div class="hover-lift p-6">
                <div class="text-5xl font-bold gradient-text mb-2">6</div>
                <p class="text-gray-600">Campus actifs</p>
            </div>
            <div class="hover-lift p-6">
                <div class="text-5xl font-bold gradient-text mb-2">99.9%</div>
                <p class="text-gray-600">Précision GPS</p>
            </div>
            <div class="hover-lift p-6">
                <div class="text-5xl font-bold gradient-text mb-2">24/7</div>
                <p class="text-gray-600">Support technique</p>
            </div>
        </div>
    </div>
</section>

<!-- Features Preview -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Fonctionnalités Principales</h2>
            <p class="text-xl text-gray-600">Tout ce dont vous avez besoin pour gérer efficacement vos équipes</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-white p-8 rounded-2xl shadow-lg hover-lift">
                <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-map-marker-alt text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Géolocalisation Précise</h3>
                <p class="text-gray-600">
                    Pointage automatique basé sur la position GPS. Les employés ne peuvent pointer qu'à l'intérieur du périmètre du campus défini.
                </p>
            </div>

            <!-- Feature 2 -->
            <div class="bg-white p-8 rounded-2xl shadow-lg hover-lift">
                <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-clock text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Suivi en Temps Réel</h3>
                <p class="text-gray-600">
                    Dashboard en temps réel pour visualiser qui est présent, qui est en retard, et suivre les heures de travail instantanément.
                </p>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white p-8 rounded-2xl shadow-lg hover-lift">
                <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-chart-line text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Rapports Détaillés</h3>
                <p class="text-gray-600">
                    Exportez des rapports complets sur les présences, retards, absences et calculs de paie en PDF ou Excel.
                </p>
            </div>

            <!-- Feature 4 -->
            <div class="bg-white p-8 rounded-2xl shadow-lg hover-lift">
                <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-mobile-alt text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Application Mobile</h3>
                <p class="text-gray-600">
                    Applications iOS et Android natives pour un pointage simple et rapide, même en mode hors ligne.
                </p>
            </div>

            <!-- Feature 5 -->
            <div class="bg-white p-8 rounded-2xl shadow-lg hover-lift">
                <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-users-cog text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Multi-Campus</h3>
                <p class="text-gray-600">
                    Gérez plusieurs campus depuis une seule plateforme. Horaires et règles personnalisables par site.
                </p>
            </div>

            <!-- Feature 6 -->
            <div class="bg-white p-8 rounded-2xl shadow-lg hover-lift">
                <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-money-bill-wave text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Calcul de Paie</h3>
                <p class="text-gray-600">
                    Calcul automatique des salaires pour vacataires avec déductions pour retards et absences intégrées.
                </p>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('landing.features') }}" class="gradient-bg text-white px-8 py-4 rounded-full font-bold text-lg hover:opacity-90 transition inline-block">
                Voir toutes les fonctionnalités <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 gradient-bg text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl md:text-5xl font-bold mb-6">
            Prêt à moderniser votre gestion de présence ?
        </h2>
        <p class="text-xl text-blue-100 mb-8">
            Téléchargez l'application mobile dès aujourd'hui et découvrez la simplicité de INSAM Presence.
        </p>
        <a href="{{ route('landing.download') }}" class="bg-white text-blue-900 px-10 py-5 rounded-full font-bold text-xl hover:bg-yellow-300 transition transform hover:scale-105 inline-block">
            <i class="fas fa-download mr-2"></i>Télécharger Gratuitement
        </a>
    </div>
</section>
@endsection
