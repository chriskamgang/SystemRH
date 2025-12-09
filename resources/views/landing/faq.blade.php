@extends('landing.layout')

@section('title', 'FAQ')
@section('description', 'Questions fréquemment posées sur INSAM Presence')

@section('content')
<!-- Hero Section -->
<section class="gradient-bg text-white py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl md:text-6xl font-bold mb-6">
            Questions Fréquentes
        </h1>
        <p class="text-xl text-blue-100">
            Trouvez rapidement les réponses à vos questions
        </p>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div x-data="{ openFaq: null }" class="space-y-4">
            <!-- General Questions -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                    Questions Générales
                </h2>

                <!-- FAQ Item 1 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 1 ? null : 1" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Qu'est-ce que INSAM Presence ?</span>
                        <i class="fas" :class="openFaq === 1 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 1" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            INSAM Presence est une solution complète de gestion de présence par géolocalisation, spécialement conçue pour les établissements d'enseignement supérieur. Elle permet de suivre en temps réel la présence des employés sur les différents campus grâce à la technologie GPS.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 2 ? null : 2" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Comment fonctionne le système de géolocalisation ?</span>
                        <i class="fas" :class="openFaq === 2 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 2" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Le système utilise le GPS du smartphone pour vérifier que l'employé se trouve physiquement dans le périmètre défini du campus (rayon configurable). Le pointage n'est possible que si l'employé est à l'intérieur de cette zone. La précision est d'environ 5 mètres.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 3 ? null : 3" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Est-ce que mes données sont sécurisées ?</span>
                        <i class="fas" :class="openFaq === 3 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 3" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Oui, absolument. Toutes les communications sont chiffrées avec SSL/TLS. Les données sont stockées sur des serveurs sécurisés avec sauvegardes quotidiennes automatiques. Nous sommes conformes aux normes RGPD pour la protection des données personnelles.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 4 ? null : 4" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Combien de campus puis-je gérer ?</span>
                        <i class="fas" :class="openFaq === 4 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 4" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Cela dépend de votre forfait : 1-2 campus pour le Starter, 3-6 pour le Professional, et illimité pour l'Enterprise. Chaque campus peut avoir ses propres horaires, tolérances de retard et zones GPS.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Technical Questions -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-cog text-purple-600 mr-3"></i>
                    Questions Techniques
                </h2>

                <!-- FAQ Item 5 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 5 ? null : 5" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">L'application fonctionne-t-elle hors ligne ?</span>
                        <i class="fas" :class="openFaq === 5 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 5" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Oui, l'application peut enregistrer les pointages en mode hors ligne. Dès que la connexion internet est rétablie, les données sont automatiquement synchronisées avec le serveur.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 6 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 6 ? null : 6" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Quels sont les téléphones compatibles ?</span>
                        <i class="fas" :class="openFaq === 6 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 6" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            L'application est compatible avec iOS 13.0+ (iPhone 6s et plus récent) et Android 8.0+. Le téléphone doit avoir un GPS fonctionnel et une connexion internet (WiFi ou données mobiles).
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 7 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 7 ? null : 7" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Que se passe-t-il si le GPS ne fonctionne pas ?</span>
                        <i class="fas" :class="openFaq === 7 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 7" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Si le GPS est désactivé ou ne fonctionne pas, l'application affiche un message d'erreur et l'employé ne peut pas pointer. Les administrateurs sont également notifiés des tentatives de pointage échouées.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 8 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 8 ? null : 8" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Peut-on intégrer avec d'autres systèmes ?</span>
                        <i class="fas" :class="openFaq === 8 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 8" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Oui, le forfait Enterprise inclut une API REST pour intégrer INSAM Presence avec vos systèmes RH, paie ou ERP existants. Contactez-nous pour discuter de vos besoins spécifiques.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Usage Questions -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-user-check text-green-600 mr-3"></i>
                    Utilisation
                </h2>

                <!-- FAQ Item 9 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 9 ? null : 9" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Comment se passe l'installation initiale ?</span>
                        <i class="fas" :class="openFaq === 9 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 9" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            1) L'administrateur crée les campus et départements dans le tableau de bord web.
                            2) Import des employés via Excel ou saisie manuelle.
                            3) Les employés téléchargent l'app et se connectent avec leurs identifiants.
                            4) C'est prêt ! Le tout prend environ 30 minutes.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 10 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 10 ? null : 10" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Comment fonctionne le calcul de paie pour vacataires ?</span>
                        <i class="fas" :class="openFaq === 10 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 10" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Pour chaque vacataire, vous définissez un taux horaire et assignez des Unités d'Enseignement (UE/matières). Le système calcule automatiquement le salaire : heures enseignées × taux horaire, avec option d'appliquer l'impôt (5%). Les retards et absences peuvent être déduits automatiquement.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 11 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 11 ? null : 11" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Puis-je personnaliser les horaires par campus ?</span>
                        <i class="fas" :class="openFaq === 11 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 11" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Oui, absolument. Chaque campus peut avoir ses propres horaires de début/fin, sa tolérance de retard (en minutes), et son rayon GPS. Vous pouvez même définir des horaires différents pour certains types d'employés.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 12 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 12 ? null : 12" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Quels types de rapports puis-je générer ?</span>
                        <i class="fas" :class="openFaq === 12 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 12" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Le système génère 7 types de rapports paiement : personnel payé, cours payés/non payés, masse salariale par spécialité et par cycle. Tous les rapports sont exportables en PDF. Vous pouvez filtrer par période, département, campus, etc.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Support Questions -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-headset text-red-600 mr-3"></i>
                    Support
                </h2>

                <!-- FAQ Item 13 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 13 ? null : 13" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Quel support est inclus ?</span>
                        <i class="fas" :class="openFaq === 13 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 13" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Tous les forfaits incluent un support technique. Starter a le support par email, Professional a le support prioritaire 24/7, et Enterprise bénéficie d'un support dédié avec SLA garantis et un account manager.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 14 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 14 ? null : 14" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Y a-t-il une formation pour les utilisateurs ?</span>
                        <i class="fas" :class="openFaq === 14 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 14" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            L'interface est très intuitive et ne nécessite généralement pas de formation. Cependant, le forfait Professional et Enterprise incluent une session de formation pour les administrateurs. Des tutoriels vidéo sont également disponibles.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item 15 -->
                <div class="bg-white rounded-xl shadow mb-4">
                    <button @click="openFaq = openFaq === 15 ? null : 15" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Comment puis-je contacter le support ?</span>
                        <i class="fas" :class="openFaq === 15 ? 'fa-chevron-up' : 'fa-chevron-down'" class="text-gray-400"></i>
                    </button>
                    <div x-show="openFaq === 15" x-transition class="px-6 pb-4">
                        <p class="text-gray-600">
                            Vous pouvez nous contacter directement via le tableau de bord web en vous connectant à votre compte administrateur. L'équipe support répond généralement en moins de 2 heures pour les forfaits Professional et Enterprise.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Still have questions? -->
<section class="py-20 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Vous avez encore des questions ?</h2>
        <p class="text-xl text-gray-600 mb-8">
            Notre équipe est là pour vous aider à trouver la solution adaptée à votre établissement.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('login') }}" class="gradient-bg text-white px-8 py-4 rounded-full font-bold text-lg hover:opacity-90 transition">
                <i class="fas fa-sign-in-alt mr-2"></i>Accéder au tableau de bord
            </a>
            <a href="{{ route('landing.download') }}" class="border-2 border-blue-600 text-blue-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-blue-600 hover:text-white transition">
                <i class="fas fa-download mr-2"></i>Télécharger l'app
            </a>
        </div>
    </div>
</section>
@endsection
