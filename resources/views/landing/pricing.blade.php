@extends('landing.layout')

@section('title', 'Tarifs')
@section('description', 'Plans et tarifs INSAM Presence adaptés à votre établissement')

@section('content')
<!-- Hero Section -->
<section class="gradient-bg text-white py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl md:text-6xl font-bold mb-6">
            Tarifs Transparents
        </h1>
        <p class="text-xl text-blue-100">
            Des forfaits adaptés à la taille de votre établissement, sans coûts cachés
        </p>
    </div>
</section>

<!-- Pricing Cards -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Starter Plan -->
            <div class="bg-white rounded-2xl shadow-lg p-8 hover-lift">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
                    <p class="text-gray-600 mb-6">Pour les petits établissements</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">50 000</span>
                        <span class="text-gray-600 ml-2">FCFA/mois</span>
                    </div>
                    <p class="text-sm text-gray-500">Jusqu'à 100 employés</p>
                </div>

                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">1-2 campus</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Applications iOS & Android</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Dashboard web complet</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Rapports basiques</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Support par email</span>
                    </li>
                </ul>

                <a href="{{ route('landing.download') }}" class="block text-center border-2 border-blue-600 text-blue-600 px-6 py-3 rounded-full font-semibold hover:bg-blue-600 hover:text-white transition">
                    Commencer
                </a>
            </div>

            <!-- Professional Plan (Recommended) -->
            <div class="bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white transform scale-105 relative">
                <div class="absolute top-0 right-0 bg-yellow-400 text-gray-900 px-4 py-1 rounded-bl-xl rounded-tr-xl font-bold text-sm">
                    RECOMMANDÉ
                </div>

                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold mb-2">Professional</h3>
                    <p class="text-blue-100 mb-6">Pour établissements moyens</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold">120 000</span>
                        <span class="text-blue-100 ml-2">FCFA/mois</span>
                    </div>
                    <p class="text-sm text-blue-100">Jusqu'à 500 employés</p>
                </div>

                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <i class="fas fa-check text-yellow-300 mt-1 mr-3"></i>
                        <span>3-6 campus</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-yellow-300 mt-1 mr-3"></i>
                        <span>Applications iOS & Android</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-yellow-300 mt-1 mr-3"></i>
                        <span>Dashboard web avancé</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-yellow-300 mt-1 mr-3"></i>
                        <span>Tous les rapports + export PDF</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-yellow-300 mt-1 mr-3"></i>
                        <span>Gestion UE & paie vacataires</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-yellow-300 mt-1 mr-3"></i>
                        <span>Support prioritaire 24/7</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-yellow-300 mt-1 mr-3"></i>
                        <span>Formation incluse</span>
                    </li>
                </ul>

                <a href="{{ route('landing.download') }}" class="block text-center bg-white text-blue-600 px-6 py-3 rounded-full font-semibold hover:bg-yellow-300 transition">
                    Commencer
                </a>
            </div>

            <!-- Enterprise Plan -->
            <div class="bg-white rounded-2xl shadow-lg p-8 hover-lift">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Enterprise</h3>
                    <p class="text-gray-600 mb-6">Pour grandes universités</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">Sur devis</span>
                    </div>
                    <p class="text-sm text-gray-500">Illimité</p>
                </div>

                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Campus illimités</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Employés illimités</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Toutes les fonctionnalités</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">API personnalisée</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Intégrations sur mesure</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">Support dédié 24/7</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                        <span class="text-gray-600">SLA garantis</span>
                    </li>
                </ul>

                <a href="{{ route('login') }}" class="block text-center gradient-bg text-white px-6 py-3 rounded-full font-semibold hover:opacity-90 transition">
                    Nous contacter
                </a>
            </div>
        </div>

        <!-- Included in all plans -->
        <div class="mt-16 bg-gray-50 rounded-2xl p-8">
            <h3 class="text-2xl font-bold text-gray-900 text-center mb-8">Inclus dans tous les forfaits</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <i class="fas fa-cloud text-3xl text-blue-600 mb-3"></i>
                    <p class="font-semibold text-gray-900">Hébergement Cloud</p>
                    <p class="text-sm text-gray-600">Sécurisé et performant</p>
                </div>
                <div class="text-center">
                    <i class="fas fa-sync text-3xl text-green-600 mb-3"></i>
                    <p class="font-semibold text-gray-900">Mises à jour</p>
                    <p class="text-sm text-gray-600">Gratuites et automatiques</p>
                </div>
                <div class="text-center">
                    <i class="fas fa-database text-3xl text-purple-600 mb-3"></i>
                    <p class="font-semibold text-gray-900">Sauvegardes</p>
                    <p class="text-sm text-gray-600">Quotidiennes incluses</p>
                </div>
                <div class="text-center">
                    <i class="fas fa-shield-alt text-3xl text-red-600 mb-3"></i>
                    <p class="font-semibold text-gray-900">Sécurité SSL</p>
                    <p class="text-sm text-gray-600">Données chiffrées</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Questions Fréquentes sur les Tarifs</h2>

        <div class="space-y-6">
            <div class="bg-white rounded-xl p-6 shadow">
                <h4 class="text-lg font-bold text-gray-900 mb-2">Y a-t-il des frais d'installation ?</h4>
                <p class="text-gray-600">Non, aucun frais d'installation. Vous payez uniquement l'abonnement mensuel choisi.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow">
                <h4 class="text-lg font-bold text-gray-900 mb-2">Puis-je changer de forfait ?</h4>
                <p class="text-gray-600">Oui, vous pouvez passer à un forfait supérieur ou inférieur à tout moment. Le changement prend effet immédiatement.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow">
                <h4 class="text-lg font-bold text-gray-900 mb-2">Quelle est la durée d'engagement ?</h4>
                <p class="text-gray-600">Aucun engagement. Vous pouvez annuler à tout moment avec un préavis de 30 jours.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow">
                <h4 class="text-lg font-bold text-gray-900 mb-2">Les mises à jour sont-elles incluses ?</h4>
                <p class="text-gray-600">Oui, toutes les mises à jour de l'application et du système sont gratuites et automatiques pour tous les forfaits.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
            Prêt à démarrer ?
        </h2>
        <p class="text-xl text-gray-600 mb-8">
            Téléchargez l'application et profitez d'un essai gratuit de 14 jours sur tous les forfaits.
        </p>
        <a href="{{ route('landing.download') }}" class="gradient-bg text-white px-10 py-5 rounded-full font-bold text-xl hover:opacity-90 transition inline-block">
            <i class="fas fa-download mr-2"></i>Essai Gratuit 14 Jours
        </a>
    </div>
</section>
@endsection
