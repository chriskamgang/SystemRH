<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'INSAM Presence') - Système de Gestion de Présence</title>
    <meta name="description" content="@yield('description', 'Solution moderne de gestion de présence par géolocalisation pour les établissements d\'enseignement supérieur')">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('landing/images/logo.png') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom styles -->
    <style>
        :root {
            --primary-blue: #1e40af;
            --primary-purple: #7c3aed;
            --accent-gold: #f59e0b;
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .scroll-smooth {
            scroll-behavior: smooth;
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 scroll-smooth">
    <!-- Navigation -->
    <nav x-data="{ mobileMenuOpen: false }" class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('landing/images/logo.png') }}" alt="INSAM Logo" class="h-16 w-16">
                    <div>
                        <h1 class="text-2xl font-bold gradient-text">INSAM Presence</h1>
                        <p class="text-xs text-gray-500">Gestion de Présence Intelligente</p>
                    </div>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('landing.index') }}" class="text-gray-700 hover:text-blue-600 font-medium transition {{ request()->routeIs('landing.index') ? 'text-blue-600 border-b-2 border-blue-600' : '' }}">
                        Accueil
                    </a>
                    <a href="{{ route('landing.features') }}" class="text-gray-700 hover:text-blue-600 font-medium transition {{ request()->routeIs('landing.features') ? 'text-blue-600 border-b-2 border-blue-600' : '' }}">
                        Fonctionnalités
                    </a>
                    <a href="{{ route('landing.advantages') }}" class="text-gray-700 hover:text-blue-600 font-medium transition {{ request()->routeIs('landing.advantages') ? 'text-blue-600 border-b-2 border-blue-600' : '' }}">
                        Avantages
                    </a>
                    <a href="{{ route('landing.pricing') }}" class="text-gray-700 hover:text-blue-600 font-medium transition {{ request()->routeIs('landing.pricing') ? 'text-blue-600 border-b-2 border-blue-600' : '' }}">
                        Tarifs
                    </a>
                    <a href="{{ route('landing.faq') }}" class="text-gray-700 hover:text-blue-600 font-medium transition {{ request()->routeIs('landing.faq') ? 'text-blue-600 border-b-2 border-blue-600' : '' }}">
                        FAQ
                    </a>
                    <a href="{{ route('landing.download') }}" class="gradient-bg text-white px-6 py-2 rounded-full font-semibold hover:opacity-90 transition">
                        <i class="fas fa-download mr-2"></i>Télécharger
                    </a>
                </div>

                <!-- Mobile menu button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-700">
                    <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'" class="text-2xl"></i>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" x-transition class="md:hidden pb-4">
                <a href="{{ route('landing.index') }}" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Accueil</a>
                <a href="{{ route('landing.features') }}" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Fonctionnalités</a>
                <a href="{{ route('landing.advantages') }}" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Avantages</a>
                <a href="{{ route('landing.pricing') }}" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Tarifs</a>
                <a href="{{ route('landing.faq') }}" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">FAQ</a>
                <a href="{{ route('landing.download') }}" class="block py-2 gradient-bg text-white px-4 rounded-lg text-center mt-2">
                    <i class="fas fa-download mr-2"></i>Télécharger
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Logo & Description -->
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="{{ asset('landing/images/logo.png') }}" alt="INSAM Logo" class="h-12 w-12">
                        <h3 class="text-2xl font-bold">INSAM Presence</h3>
                    </div>
                    <p class="text-gray-400 max-w-md">
                        Solution moderne de gestion de présence par géolocalisation pour les établissements d'enseignement supérieur. Simplifiez le suivi de vos employés avec notre technologie innovante.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Liens Rapides</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('landing.features') }}" class="text-gray-400 hover:text-white transition">Fonctionnalités</a></li>
                        <li><a href="{{ route('landing.advantages') }}" class="text-gray-400 hover:text-white transition">Avantages</a></li>
                        <li><a href="{{ route('landing.pricing') }}" class="text-gray-400 hover:text-white transition">Tarifs</a></li>
                        <li><a href="{{ route('landing.faq') }}" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Téléchargement</h4>
                    <div class="space-y-3">
                        <a href="{{ route('landing.download') }}" class="flex items-center space-x-2 text-gray-400 hover:text-white transition">
                            <i class="fab fa-android text-2xl"></i>
                            <span>Android App</span>
                        </a>
                        <a href="{{ route('landing.download') }}" class="flex items-center space-x-2 text-gray-400 hover:text-white transition">
                            <i class="fab fa-apple text-2xl"></i>
                            <span>iOS App</span>
                        </a>
                        <a href="{{ route('login') }}" class="flex items-center space-x-2 text-gray-400 hover:text-white transition">
                            <i class="fas fa-laptop text-2xl"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} INSAM Presence. Tous droits réservés.</p>
                <p class="text-sm mt-2">Développé avec ❤️ pour les établissements d'enseignement supérieur</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
