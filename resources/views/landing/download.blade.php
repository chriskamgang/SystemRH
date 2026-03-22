@extends('landing.layout')

@section('title', 'Télécharger')
@section('description', 'Téléchargez l\'application INSAM Presence sur iOS et Android')

@section('content')
<!-- Hero Section -->
<section class="gradient-bg text-white py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-5xl md:text-6xl font-bold mb-6">
            Téléchargez INSAM Presence
        </h1>
        <p class="text-xl text-blue-100 mb-8">
            Disponible sur iOS et Android. Commencez à gérer vos présences en quelques minutes.
        </p>
    </div>
</section>

<!-- Download Section -->
<section class="py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Mobile Apps -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Applications Mobile</h2>

                <!-- iOS Download - Beta TestFlight -->
                <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 hover-lift">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mr-4">
                            <i class="fab fa-apple text-4xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">iOS App</h3>
                            <span class="inline-block bg-orange-100 text-orange-700 text-xs font-semibold px-2 py-1 rounded-full">
                                <i class="fas fa-flask mr-1"></i>Beta TestFlight
                            </span>
                        </div>
                    </div>

                    <p class="text-gray-600 mb-4">
                        L'application iOS est actuellement en phase de test via <strong>Apple TestFlight</strong>.
                        Inscrivez-vous ci-dessous pour recevoir une invitation.
                    </p>

                    <!-- Messages de succès/info -->
                    @if(session('ios_beta_success'))
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-0.5 mr-3"></i>
                                <div>
                                    <p class="text-green-800 font-semibold">{{ session('ios_beta_success') }}</p>
                                    <p class="text-green-700 text-sm mt-1">Consultez les instructions ci-dessous pour savoir comment installer l'app.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('ios_beta_info'))
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                                <p class="text-blue-800">{{ session('ios_beta_info') }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Formulaire d'inscription -->
                    <form action="{{ route('landing.ios-beta') }}" method="POST" class="mb-6">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <input type="text" name="full_name" placeholder="Votre nom complet"
                                    value="{{ old('full_name') }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>
                            <div>
                                <input type="email" name="email" placeholder="Votre adresse email Apple ID *" required
                                    value="{{ old('email') }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                @error('email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="w-full bg-black text-white px-6 py-4 rounded-xl font-semibold text-center hover:bg-gray-800 transition flex items-center justify-center">
                                <i class="fab fa-apple mr-2 text-xl"></i>
                                S'inscrire pour la beta iOS
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            <i class="fas fa-lock mr-1"></i>
                            Utilisez l'email associé à votre Apple ID
                        </p>
                    </form>

                    <!-- Instructions TestFlight -->
                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-5">
                        <h4 class="font-bold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-list-ol text-blue-600 mr-2"></i>
                            Comment installer via TestFlight ?
                        </h4>
                        <ol class="space-y-3 text-sm text-gray-700">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</span>
                                <span>Installez l'app <strong>TestFlight</strong> depuis l'App Store sur votre iPhone
                                    <a href="https://apps.apple.com/app/testflight/id899247664" target="_blank" class="text-blue-600 underline ml-1">
                                        <i class="fas fa-external-link-alt text-xs"></i> Ouvrir
                                    </a>
                                </span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</span>
                                <span>Inscrivez-vous ci-dessus avec <strong>l'email de votre Apple ID</strong></span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</span>
                                <span>Vous recevrez un <strong>email d'invitation</strong> de la part d'Apple (vérifiez aussi les spams)</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">4</span>
                                <span>Ouvrez le lien dans l'email, puis cliquez sur <strong>"Accepter"</strong> dans TestFlight</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">5</span>
                                <span>L'application <strong>INSAM Presence</strong> sera installée automatiquement sur votre iPhone</span>
                            </li>
                        </ol>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-4 text-center text-sm text-gray-600">
                        <div>
                            <div class="font-bold text-blue-600">iOS 13+</div>
                            <div>Version min</div>
                        </div>
                        <div>
                            <div class="font-bold text-blue-600">50 MB</div>
                            <div>Taille</div>
                        </div>
                        <div>
                            <div class="font-bold text-orange-500">Beta</div>
                            <div>Statut</div>
                        </div>
                    </div>
                </div>

                <!-- Android Download -->
                <div class="bg-white rounded-2xl shadow-lg p-8 hover-lift">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-blue-600 rounded-2xl flex items-center justify-center mr-4">
                            <i class="fab fa-android text-4xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Android App</h3>
                            <p class="text-gray-600">Pour téléphones et tablettes</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-6">
                        Téléchargez l'application depuis le Google Play Store. Compatible avec Android 8.0 et versions ultérieures.
                    </p>
                    <a href="#" class="block bg-green-600 text-white px-6 py-4 rounded-xl font-semibold text-center hover:bg-green-700 transition mb-3">
                        <i class="fab fa-google-play mr-2"></i>
                        Télécharger sur Google Play
                    </a>
                    <a href="/downloads/insam-presence.apk" class="block bg-gray-800 text-white px-6 py-4 rounded-xl font-semibold text-center hover:bg-gray-900 transition">
                        <i class="fas fa-download mr-2"></i>
                        Télécharger l'APK directement
                    </a>
                    <p class="text-xs text-gray-500 mt-2 text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Installation directe sans Google Play Store (54 MB)
                    </p>
                    <div class="mt-4 grid grid-cols-3 gap-4 text-center text-sm text-gray-600">
                        <div>
                            <div class="font-bold text-green-600">Android 8+</div>
                            <div>Version min</div>
                        </div>
                        <div>
                            <div class="font-bold text-green-600">45 MB</div>
                            <div>Taille</div>
                        </div>
                        <div>
                            <div class="font-bold text-green-600">v2.1</div>
                            <div>Actuelle</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Web -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Tableau de Bord Web</h2>

                <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mr-4">
                            <i class="fas fa-laptop text-4xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Interface d'Administration</h3>
                            <p class="text-gray-600">Accès depuis n'importe quel navigateur</p>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-6">
                        Le tableau de bord web permet aux administrateurs de gérer tous les aspects du système : employés, campus, présences, rapports et paiements.
                    </p>
                    <a href="{{ route('login') }}" class="block gradient-bg text-white px-6 py-4 rounded-xl font-semibold text-center hover:opacity-90 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Accéder au tableau de bord
                    </a>
                </div>

                <!-- Features List -->
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-8">
                    <h4 class="text-xl font-bold text-gray-900 mb-4">Fonctionnalités du tableau de bord</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700">Gestion des employés et départements</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700">Configuration des campus et zones GPS</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700">Suivi en temps réel des présences</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700">Rapports et exports (PDF, Excel)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700">Calcul automatique de la paie</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700">Gestion des unités d'enseignement</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700">Système de permissions granulaire</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Requirements Section -->
<section class="py-20 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Configuration Requise</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- iOS Requirements -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <i class="fab fa-apple text-3xl text-blue-600 mr-3"></i>
                    <h3 class="text-xl font-bold text-gray-900">iOS</h3>
                </div>
                <ul class="space-y-2 text-gray-600">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>iOS 13.0 ou ultérieur</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>iPhone 6s ou plus récent</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>GPS activé</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Connexion Internet</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>50 MB d'espace libre</li>
                </ul>
            </div>

            <!-- Android Requirements -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <i class="fab fa-android text-3xl text-green-600 mr-3"></i>
                    <h3 class="text-xl font-bold text-gray-900">Android</h3>
                </div>
                <ul class="space-y-2 text-gray-600">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Android 8.0 ou ultérieur</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Services Google Play</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>GPS activé</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Connexion Internet</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>45 MB d'espace libre</li>
                </ul>
            </div>

            <!-- Web Requirements -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-laptop text-3xl text-purple-600 mr-3"></i>
                    <h3 class="text-xl font-bold text-gray-900">Web</h3>
                </div>
                <ul class="space-y-2 text-gray-600">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Chrome, Firefox, Safari</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Edge (dernière version)</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Résolution 1280x720 min</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Connexion Internet stable</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>JavaScript activé</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Support Section -->
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="gradient-bg rounded-2xl p-12 text-white text-center">
            <i class="fas fa-headset text-6xl mb-6"></i>
            <h2 class="text-3xl font-bold mb-4">Besoin d'aide ?</h2>
            <p class="text-xl text-blue-100 mb-8">
                Notre équipe de support est disponible 24/7 pour vous aider avec l'installation et la configuration.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('landing.faq') }}" class="bg-white text-blue-900 px-8 py-3 rounded-full font-semibold hover:bg-yellow-300 transition">
                    <i class="fas fa-question-circle mr-2"></i>Consulter la FAQ
                </a>
                <a href="{{ route('login') }}" class="border-2 border-white text-white px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-blue-900 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
