@extends('landing.layout')

@section('title', 'Avantages')
@section('description', 'Découvrez les avantages de INSAM Presence pour votre établissement')

@section('content')
<!-- Hero Section -->
<section class="gradient-bg text-white py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl md:text-6xl font-bold mb-6">
            Pourquoi Choisir INSAM Presence ?
        </h1>
        <p class="text-xl text-blue-100">
            Des bénéfices concrets pour votre établissement et vos équipes
        </p>
    </div>
</section>

<!-- Main Advantages -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-20">
            <div>
                <div class="inline-block px-4 py-2 bg-blue-100 text-blue-600 rounded-full font-semibold mb-4">
                    Gain de Temps
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-6">
                    Économisez jusqu'à <span class="gradient-text">15 heures par semaine</span>
                </h2>
                <p class="text-lg text-gray-600 mb-6">
                    Fini les feuilles d'émargement papier et la saisie manuelle des données. Tout est automatisé, du pointage à la génération des rapports de paie.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Pointage en 2 secondes</h4>
                            <p class="text-gray-600">L'employé ouvre l'app et pointe automatiquement</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Rapports instantanés</h4>
                            <p class="text-gray-600">Générez n'importe quel rapport en 1 clic</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Calculs automatiques</h4>
                            <p class="text-gray-600">Paie calculée automatiquement avec déductions</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="relative">
                <div class="bg-gradient-to-br from-blue-100 to-purple-100 rounded-3xl p-12 shadow-2xl">
                    <div class="text-center">
                        <div class="text-6xl font-bold gradient-text mb-2">15h</div>
                        <p class="text-gray-600 text-lg">gagnées par semaine</p>
                        <div class="mt-8 pt-8 border-t border-gray-300">
                            <div class="text-4xl font-bold text-gray-900 mb-2">60h</div>
                            <p class="text-gray-600">économisées par mois</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-20">
            <div class="order-2 lg:order-1">
                <div class="bg-gradient-to-br from-green-100 to-blue-100 rounded-3xl p-12 shadow-2xl">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 font-medium">Erreurs de pointage</span>
                            <span class="text-3xl font-bold text-red-600">-95%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 font-medium">Fraudes détectées</span>
                            <span class="text-3xl font-bold text-green-600">100%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 font-medium">Précision GPS</span>
                            <span class="text-3xl font-bold text-blue-600">99.9%</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                <div class="inline-block px-4 py-2 bg-green-100 text-green-600 rounded-full font-semibold mb-4">
                    Fiabilité
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-6">
                    Une <span class="gradient-text">précision inégalée</span> à chaque pointage
                </h2>
                <p class="text-lg text-gray-600 mb-6">
                    Le système de géolocalisation GPS élimine totalement les fraudes de pointage et garantit que chaque employé est physiquement présent sur le campus.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Impossible de tricher</h4>
                            <p class="text-gray-600">Pointage uniquement dans le périmètre GPS défini</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Données vérifiables</h4>
                            <p class="text-gray-600">Coordonnées GPS enregistrées à chaque pointage</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Audit trail complet</h4>
                            <p class="text-gray-600">Historique immuable de tous les pointages</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="inline-block px-4 py-2 bg-purple-100 text-purple-600 rounded-full font-semibold mb-4">
                    Productivité
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-6">
                    Augmentez la <span class="gradient-text">ponctualité de 40%</span>
                </h2>
                <p class="text-lg text-gray-600 mb-6">
                    Le système de détection automatique des retards et les rappels intelligents encouragent la ponctualité et réduisent drastiquement les absences.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Rappels automatiques</h4>
                            <p class="text-gray-600">Notifications push pour début et fin de journée</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Détection de retards</h4>
                            <p class="text-gray-600">Calcul automatique basé sur les horaires du campus</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <i class="fas fa-check text-green-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Statistiques en temps réel</h4>
                            <p class="text-gray-600">Visualisez les taux de ponctualité par département</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="relative">
                <div class="bg-gradient-to-br from-purple-100 to-pink-100 rounded-3xl p-12 shadow-2xl">
                    <div class="text-center">
                        <i class="fas fa-chart-line text-6xl text-purple-600 mb-6"></i>
                        <div class="text-5xl font-bold gradient-text mb-2">+40%</div>
                        <p class="text-gray-600 text-lg mb-8">de ponctualité</p>
                        <div class="grid grid-cols-2 gap-4 text-left">
                            <div class="bg-white rounded-xl p-4">
                                <div class="text-2xl font-bold text-red-600">-60%</div>
                                <p class="text-sm text-gray-600">Retards</p>
                            </div>
                            <div class="bg-white rounded-xl p-4">
                                <div class="text-2xl font-bold text-green-600">+85%</div>
                                <p class="text-sm text-gray-600">Assiduité</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Additional Benefits -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Autres Avantages</h2>
            <p class="text-xl text-gray-600">Des bénéfices à tous les niveaux de votre organisation</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-2xl p-8 shadow-lg hover-lift">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-leaf text-3xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Écologique</h3>
                <p class="text-gray-600">
                    Éliminez le papier : zéro feuille d'émargement, zéro impression. Contribuez à la protection de l'environnement tout en modernisant vos processus.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg hover-lift">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-money-bill-wave text-3xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Économique</h3>
                <p class="text-gray-600">
                    ROI en moins de 3 mois. Réduisez les coûts liés aux heures supplémentaires non justifiées et aux erreurs de paie coûteuses.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg hover-lift">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-graduation-cap text-3xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Spécialisé Éducation</h3>
                <p class="text-gray-600">
                    Conçu spécifiquement pour les établissements d'enseignement supérieur avec gestion des UE, vacataires et multi-campus intégrée.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg hover-lift">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-mobile-alt text-3xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Facilité d'Utilisation</h3>
                <p class="text-gray-600">
                    Interface intuitive ne nécessitant aucune formation. Vos employés sont opérationnels dès le premier jour d'utilisation.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg hover-lift">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-headset text-3xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Support 24/7</h3>
                <p class="text-gray-600">
                    Équipe de support technique disponible en permanence pour répondre à vos questions et résoudre rapidement tout problème.
                </p>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg hover-lift">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-shield-alt text-3xl text-indigo-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Sécurité Maximale</h3>
                <p class="text-gray-600">
                    Chiffrement SSL/TLS, sauvegardes automatiques quotidiennes et conformité RGPD pour la protection totale de vos données.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
            Convaincu par les avantages ?
        </h2>
        <p class="text-xl text-gray-600 mb-8">
            Rejoignez les établissements qui ont déjà optimisé leur gestion de présence avec INSAM Presence.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('landing.download') }}" class="gradient-bg text-white px-8 py-4 rounded-full font-bold text-lg hover:opacity-90 transition">
                <i class="fas fa-download mr-2"></i>Commencer maintenant
            </a>
            <a href="{{ route('landing.pricing') }}" class="border-2 border-blue-600 text-blue-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-blue-600 hover:text-white transition">
                Voir les tarifs
            </a>
        </div>
    </div>
</section>
@endsection
